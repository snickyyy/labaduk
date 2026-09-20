# Безопасная установка lingking.space на VM

Конфигурация рассчитана на Debian 13 (Trixie), Nginx, PHP 8.4-FPM, один домен и
проект в `/var/www/labaduk`. База PostgreSQL может работать через имеющийся
`docker-compose.yml`; наружу её порт не публикуется.

Публичный домен приложения — `lingking.space`, дополнительный адрес —
`www.lingking.space`. Перед началом A/AAAA-записи должны указывать на VM. Если
IPv6 на VM не настроен, удалите AAAA-запись, иначе проверка сертификата может
завершиться ошибкой.

## 1. Подготовить VM

Обновите систему и установите необходимые пакеты:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y nginx php-fpm php-cli php-pgsql php-curl php-mbstring \
    php-xml php-zip php-bcmath php-intl composer git unzip certbot ufw
```

В `/etc/php/8.4/fpm/php.ini` проверьте следующие параметры:

```ini
expose_php = Off
display_errors = Off
cgi.fix_pathinfo = 0
upload_max_filesize = 10M
post_max_size = 12M
```

В пуле `/etc/php/8.4/fpm/pool.d/www.conf` ограничьте исполняемые расширения:

```ini
security.limit_extensions = .php
```

После изменения выполните `sudo systemctl restart php8.4-fpm`.

Разрешите только SSH, HTTP и HTTPS. Сначала убедитесь, что SSH-доступ работает
и порт SSH совпадает с разрешённым правилом:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Не открывайте `5432/tcp`. В проекте Docker публикует PostgreSQL только на
`127.0.0.1`.

## 2. Развернуть приложение

Клонируйте проект в `/var/www/labaduk`, затем:

```bash
cd /var/www/labaduk
cp .env.example .env
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan key:generate
```

В `.env` как минимум задайте:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lingking.space
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=labaduk
DB_USERNAME=labaduk
DB_PASSWORD=СЛУЧАЙНЫЙ_ДЛИННЫЙ_ПАРОЛЬ

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true
```

Не копируйте `.env` в репозиторий и не используйте пример пароля. Создать
пароль можно командой `openssl rand -base64 32`.

Если PostgreSQL запускается через Docker, установите Docker Engine из
официального репозитория Docker, затем запустите только базу:

```bash
docker compose up -d db
```

Для сборки фронтенда нужен Node.js `^20.19` или `>=22.12` (требование текущей
версии Vite). Лучше собирать `public/build` в CI. При сборке на VM:

```bash
npm ci
npm run build
```

Завершите подготовку Laravel:

```bash
sudo chown -R "$USER":www-data /var/www/labaduk
sudo find /var/www/labaduk -type d -exec chmod 0755 {} \;
sudo find /var/www/labaduk -type f -exec chmod 0644 {} \;
sudo chmod -R u=rwX,g=rwX,o= storage bootstrap/cache
sudo chmod 0640 .env

php artisan migrate --force
php artisan optimize
```

Если приложению нужны загруженные публичные файлы, один раз выполните
`php artisan storage:link`.

## 3. Подключить lingking.space и получить TLS

Сейчас DNS домена направлен на Vercel. На момент подготовки инструкции
`lingking.space` отвечает ошибкой Vercel `DEPLOYMENT_NOT_FOUND`, а сертификат
`www.lingking.space` не проходит проверку.

Сертификат, автоматически выпущенный Vercel, хранится на инфраструктуре Vercel
и не может быть установлен в Nginx на этой VM. Для текущего варианта, где
Laravel работает непосредственно на VM, измените DNS в панели Vercel:

- `A` для `@` → публичный IPv4-адрес VM;
- `AAAA` для `@` → IPv6-адрес VM, только если IPv6 действительно настроен;
- `CNAME` для `www` → `lingking.space`.

Удалите старые конфликтующие A/AAAA/CNAME-записи. Дождитесь, пока команды
`dig +short A lingking.space` и `dig +short CNAME www.lingking.space` покажут
новые значения. Управление DNS можно оставить в Vercel, но TLS для Nginx
выпускается отдельно.

Сначала включите временный HTTP-конфиг:

```bash
sudo mkdir -p /var/www/letsencrypt/.well-known/acme-challenge
sudo cp deployment/nginx/rate-limits.conf /etc/nginx/conf.d/labaduk-rate-limits.conf
sudo cp deployment/nginx/labaduk-http.conf /etc/nginx/sites-available/labaduk
sudo ln -s /etc/nginx/sites-available/labaduk /etc/nginx/sites-enabled/labaduk
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

sudo certbot certonly --webroot -w /var/www/letsencrypt \
    -d lingking.space -d www.lingking.space
```

Теперь установите финальный HTTPS-конфиг:

```bash
sudo cp deployment/nginx/labaduk.conf /etc/nginx/sites-available/labaduk
sudo nginx -t
sudo systemctl reload nginx
```

Проверьте автоматическое продление сертификата:

```bash
sudo certbot renew --dry-run
systemctl status certbot.timer
```

## 4. Проверить установку

```bash
curl -I http://lingking.space
curl -I https://lingking.space
curl -i 'https://lingking.space/api/appointments/slots?date=2026-09-21'
sudo nginx -t
sudo systemctl status nginx php8.4-fpm
```

Ожидается перенаправление HTTP на HTTPS. После превышения лимита Nginx или
Laravel должен вернуть `429 Too Many Requests`.

Текущие лимиты на один IP:

| Зона | Nginx | Laravel |
| --- | ---: | ---: |
| Общие страницы | 20 запросов/с, всплеск 40 | — |
| Просмотр свободных слотов | 60 запросов/мин, всплеск 20 | 60 запросов/мин |
| Создание записи | 5 запросов/мин, всплеск 5 | 5 запросов/мин |
| Вход, регистрация, сброс пароля | 10 запросов/мин, всплеск 5 | Fortify: вход 5/мин; passkeys и 2FA имеют отдельные лимиты |

`burst` разрешает короткий всплеск, но не меняет среднюю скорость. Nginx
считает по IP, поэтому за корпоративным NAT пользователи делят один лимит.

## 5. Регулярное обновление

После получения новой версии приложения:

```bash
cd /var/www/labaduk
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.4-fpm
```

До `git pull` сохраните резервную копию базы и пользовательских файлов.
Периодически устанавливайте обновления ОС и проверяйте журналы
`/var/log/nginx/labaduk.error.log` и `storage/logs/laravel.log`.

## Важные варианты окружения

- Если версия PHP другая, измените сокет `fastcgi_pass` в `labaduk.conf` и
  проверьте реальный путь командой `ls /run/php/`.
- Если перед VM стоит Cloudflare или другой reverse proxy, текущая
  конфигурация будет видеть IP прокси. Не включайте `real_ip_header` без
  строгого списка доверенных адресов прокси; сначала настройте trusted proxies
  в Laravel и Nginx.
- HSTS включается только в финальном HTTPS-конфиге. Не добавляйте
  `includeSubDomains` или `preload`, пока все поддомены не переведены на HTTPS.
- Неизвестные значения `Host` и SNI получают закрытие соединения (`444`), а не
  ответ приложения. Разрешены только `lingking.space` и `www.lingking.space`.
- CSP совместима с текущими Livewire/Filament-страницами, но содержит
  `unsafe-inline` и `unsafe-eval`. Для более строгой политики потребуется
  nonce/CSP-сборка Livewire и отдельное тестирование интерфейса.
