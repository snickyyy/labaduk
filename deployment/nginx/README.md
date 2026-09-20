# Production-развёртывание `lingking.space` на VM

Инструкция подготовлена для текущей конфигурации проекта: Laravel 13,
PHP 8.4-FPM, Nginx, PostgreSQL 16 в Docker и Debian 13 (Trixie).

Vercel в этой схеме используется только как авторитетный DNS-сервис:

```text
браузер -> Vercel DNS -> публичный IP VM -> Nginx -> PHP-FPM -> Laravel
                                                     |
                                                     +-> PostgreSQL на 127.0.0.1:5432
```

После замены DNS веб-трафик через Vercel не проходит. Сертификат Vercel на VM
также не переносится: Nginx получает отдельный сертификат Let's Encrypt.

## 0. Что известно о проекте и домене

На 20 сентября 2026 года публичная конфигурация выглядит так:

- DNS обслуживают `ns1.vercel-dns.com` и `ns2.vercel-dns.com`;
- `lingking.space` имеет `A 216.198.79.1` и отвечает с Vercel ошибкой
  `DEPLOYMENT_NOT_FOUND`;
- `www.lingking.space` — CNAME на
  `202d545f02a6deb6.vercel-dns-017.com`, при этом его сертификат просрочен;
- AAAA-записи у корня нет;
- CAA уже разрешает выпуск сертификатов Let's Encrypt.

Целевой результат:

| Имя | Тип | Значение |
| --- | --- | --- |
| `@` | `A` | публичный статический IPv4 VM |
| `@` | `AAAA` | IPv6 VM, только если он реально настроен |
| `www` | `CNAME` | `lingking.space` |

Остальные DNS-записи, особенно MX/TXT для почты, при переключении не удалять.

## 1. Обязательные проверки до публикации

Не переключайте DNS, пока не закрыты следующие пункты.

### 1.1. Закрыть посторонним доступ к админке

Публичная регистрация отключена на уровне Fortify, а доступ к Filament по
`/admin` разрешён только пользователям с `is_admin = true`. После применения
миграций создайте первого администратора доверенной CLI-командой из раздела 8
и проверьте обычным тестовым пользователем, что `/admin` возвращает запрет.

`DatabaseSeeder` не создаёт пользователей. Не используйте фабрики приложения
для production-аккаунтов: они предназначены только для автоматических тестов.

### 1.2. Настроить настоящую отправку почты

В `.env.example` стоит `MAIL_MAILER=log`. В таком режиме письма восстановления
пароля не уходят пользователю, а записываются в лог. Перед открытием входа
задайте SMTP или другой production-mailer и проверьте письмо восстановления.

### 1.3. Обновить PostgreSQL и включить автозапуск контейнера

В `docker-compose.yml` зафиксирован устаревший `postgres:16.4-alpine`.
Перед первым запуском новой базы замените его как минимум на актуальный patch
релиз ветки 16, сейчас `postgres:16.15-alpine3.24`, и добавьте сервису:

```yaml
restart: unless-stopped
```

Минорные версии PostgreSQL содержат исправления безопасности. Для уже
существующего production volume сначала сделайте `pg_dump`, затем обновляйте
образ в пределах той же major-ветки 16.

### 1.4. Проверить обработку корневого URL Nginx

В текущем `deployment/nginx/labaduk.conf` запрос `/` может попасть в реально
существующий каталог `public/`, после чего внутренний переход на `index.php`
будет остановлен защитным правилом, запрещающим прямые PHP URL. Перед копированием
конфига добавьте перед общим `location /` отдельное правило:

```nginx
location = / {
    limit_req zone=labaduk_general burst=40 nodelay;
    try_files "" @laravel;
}
```

После включения HTTPS обязательно проверьте, что `/` отдаёт Laravel-редирект на
`/en`, а не `403` или `404`.

### 1.5. Определиться с `www`

Сейчас Nginx обслуживает `lingking.space` и `www.lingking.space` как два
равноправных адреса. Для одной канонической версии лучше оставить
`APP_URL=https://lingking.space`, а `www` перенаправлять постоянным `301` на
`https://lingking.space$request_uri`. Сертификат всё равно должен включать оба
имени.

## 2. Что понадобится

- VM с Debian 13, статическим публичным IPv4 и минимум 1 vCPU / 2 ГБ RAM /
  20 ГБ диска; 2 vCPU удобнее, если frontend собирается прямо на VM;
- SSH-ключ и пользователь с `sudo`;
- доступ к репозиторию `git@github.com:snickyyy/labaduk.git`;
- доступ к Vercel-команде, которой принадлежит DNS-зона `lingking.space`;
- адрес администратора сайта и рабочие SMTP-реквизиты;
- подтверждённый у SMTP-провайдера адрес отправителя;
- email для уведомлений Let's Encrypt;
- открытые на уровне облачного firewall порты 80 и 443, а SSH — только с
  доверенных адресов, если провайдер это позволяет.

В командах ниже замените:

- `<VM_IPV4>` — на публичный IPv4 VM;
- `<LETSENCRYPT_EMAIL>` — на email владельца сертификата;
- `<ADMIN_EMAIL>` — на email администратора приложения;
- `<MAIL_FROM_ADDRESS>` — на разрешённый SMTP-провайдером адрес отправителя.

## 3. Подготовить VM

Подключитесь по SSH, обновите систему и установите runtime проекта:

```bash
sudo apt update
sudo apt full-upgrade -y
sudo apt install -y nginx php8.4-fpm php8.4-cli php8.4-pgsql \
    php8.4-curl php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath \
    php8.4-intl composer git unzip certbot ufw ca-certificates curl \
    nodejs npm postgresql-client
```

Debian 13 поставляет Node.js 20.19.2, что удовлетворяет требованию текущего
Vite (`^20.19` или `>=22.12`). Проверьте версии:

```bash
php -v
php -m | grep -E 'curl|dom|fileinfo|intl|mbstring|openssl|pdo_pgsql|xml|zip'
composer --version
node --version
npm --version
```

Проект требует PHP `>=8.3`; production-конфиг Nginx ожидает сокет
`/run/php/php8.4-fpm.sock`.

### Установить Docker Engine из официального репозитория

```bash
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg \
    -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: trixie
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io \
    docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker version
sudo docker compose version
```

Добавлять deploy-пользователя в группу `docker` необязательно: членство в этой
группе практически равнозначно root-доступу. В инструкции Docker вызывается
через `sudo`.

### Настроить PHP-FPM

В `/etc/php/8.4/fpm/php.ini` задайте:

```ini
expose_php = Off
display_errors = Off
cgi.fix_pathinfo = 0
upload_max_filesize = 10M
post_max_size = 12M
```

В `/etc/php/8.4/fpm/pool.d/www.conf` проверьте:

```ini
security.limit_extensions = .php
```

Примените и проверьте:

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl enable php8.4-fpm nginx
sudo systemctl status php8.4-fpm --no-pager
ls -l /run/php/php8.4-fpm.sock
```

### Настроить firewall

Сначала проверьте, что правило `OpenSSH` соответствует реальному SSH-порту,
и только потом включайте UFW:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status verbose
```

Порт PostgreSQL `5432` наружу не открывать. Docker Compose публикует его только
на loopback VM: `127.0.0.1:5432`.

## 4. Развернуть приложение

### Получить код

```bash
sudo install -d -o "$USER" -g www-data -m 0755 /var/www/labaduk
git clone git@github.com:snickyyy/labaduk.git /var/www/labaduk
cd /var/www/labaduk
cp .env.example .env
```

Для приватного репозитория используйте отдельный read-only deploy key. Не
вставляйте GitHub token в URL команды: он попадёт в историю shell.

### Настроить `.env`

Сгенерируйте пароль БД без специальных символов, мешающих dotenv-парсеру:

```bash
openssl rand -hex 32
```

Заполните `/var/www/labaduk/.env` как минимум так:

```dotenv
APP_NAME=Labaduk
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://lingking.space

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=labaduk
DB_USERNAME=labaduk
DB_PASSWORD=<СГЕНЕРИРОВАННЫЙ_ПАРОЛЬ>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=<SMTP_PORT>
MAIL_USERNAME=<SMTP_USERNAME>
MAIL_PASSWORD=<SMTP_PASSWORD>
MAIL_FROM_ADDRESS=<MAIL_FROM_ADDRESS>
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

Параметры `MAIL_SCHEME` и TLS задайте по документации конкретного почтового
провайдера. `APP_URL` должен оставаться ровно `https://lingking.space`: он также
используется как WebAuthn origin для passkeys.

Оставьте `APP_KEY` пустым до установки Composer-зависимостей. Затем
сгенерируйте ключ один раз по инструкции ниже.

```bash
sudo chown "$USER":www-data .env
sudo chmod 0640 .env
```

После запуска production не выполняйте `key:generate` повторно: смена ключа
сломает существующие зашифрованные данные, сессии и passkey user handles.

### Запустить PostgreSQL

Сначала внесите изменения из пункта 1.3 в `docker-compose.yml`, затем:

```bash
sudo docker compose config
sudo docker compose up -d db
sudo docker compose ps
sudo docker compose exec db sh -c \
    'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
sudo ss -lntp | grep 5432
```

Последняя команда должна показывать только `127.0.0.1:5432`, не
`0.0.0.0:5432` и не `[::]:5432`.

Важно: `POSTGRES_DB`, `POSTGRES_USER` и `POSTGRES_PASSWORD` применяются только
при создании пустого volume. Изменение этих переменных позже не меняет пароль
в уже инициализированной базе.

### Установить зависимости и собрать frontend

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan key:generate
npm ci
npm run build
```

Настройте права и подготовьте Laravel:

```bash
sudo chown -R "$USER":www-data /var/www/labaduk
sudo find /var/www/labaduk -type d -exec chmod 0755 {} \;
sudo find /var/www/labaduk -type f -exec chmod 0644 {} \;
sudo chmod -R u=rwX,g=rwX,o= storage bootstrap/cache
sudo chmod 0640 .env

php artisan migrate --force
php artisan optimize
php artisan about
php artisan migrate:status
```

```
Сейчас приложение не хранит пользовательские upload-файлы в public disk. Если
они появятся, один раз выполните `php artisan storage:link` и включите
`storage/app/public` в резервные копии.

Очередь настроена на БД, но текущий код не содержит queued jobs. Отдельный
worker пока не нужен. Как только появится `ShouldQueue`, добавьте управляемый
systemd/Supervisor worker и перезапускайте его после каждого deploy.

## 5. Подготовить Nginx до изменения DNS

Сначала внесите правку из пункта 1.4 в `deployment/nginx/labaduk.conf`.

```bash
sudo mkdir -p /var/www/letsencrypt/.well-known/acme-challenge
sudo cp deployment/nginx/rate-limits.conf \
    /etc/nginx/conf.d/labaduk-rate-limits.conf
sudo cp deployment/nginx/labaduk-http.conf \
    /etc/nginx/sites-available/labaduk
sudo ln -s /etc/nginx/sites-available/labaduk \
    /etc/nginx/sites-enabled/labaduk
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Если symlink уже существует, повторно создавать его не нужно.

Проверьте HTTP-vhost локально:

```bash
printf 'ok\n' | sudo tee \
    /var/www/letsencrypt/.well-known/acme-challenge/probe >/dev/null
curl -H 'Host: lingking.space' \
    http://127.0.0.1/.well-known/acme-challenge/probe
sudo rm /var/www/letsencrypt/.well-known/acme-challenge/probe
curl -I -H 'Host: lingking.space' http://127.0.0.1/
```

Первый запрос должен вернуть `ok`, второй — `301` на HTTPS.

## 6. Переключить DNS в Vercel

В Vercel откройте нужную Team, затем **Domains → lingking.space → DNS
Records**. DNS-зона должна оставаться включённой; nameservers менять не нужно.

Измените только веб-записи:

1. замените `A @ 216.198.79.1` на `A @ <VM_IPV4>`;
2. замените текущий `CNAME www ...vercel-dns-017.com` на
   `CNAME www lingking.space`;
3. не добавляйте AAAA, если на VM не настроены IPv6, маршрутизация, Nginx и
   firewall для IPv6;
4. оставьте CAA `0 issue "letsencrypt.org"`;
5. не трогайте MX/TXT и записи подтверждения внешних сервисов.

Если интерфейс показывает, что домен прикреплён к Vercel Project, его можно
отвязать от Project после переключения: это не то же самое, что удалить
DNS-зону или регистрацию домена.

Проверьте сначала авторитетный Vercel DNS, затем публичные резолверы:

```bash
dig @ns1.vercel-dns.com A lingking.space +short
dig @ns1.vercel-dns.com CNAME www.lingking.space +short
dig A lingking.space +short
dig AAAA lingking.space +short
dig CNAME www.lingking.space +short
```

Ожидаются `<VM_IPV4>`, пустой AAAA и `lingking.space.` для CNAME. Vercel обычно
использует TTL 60 секунд, но полное обновление кэшей может занять дольше.

## 7. Выпустить TLS и включить HTTPS

Только после того, как публичные A/CNAME уже показывают VM, выполните:

```bash
sudo certbot certonly --webroot -w /var/www/letsencrypt \
    --cert-name lingking.space \
    -d lingking.space -d www.lingking.space \
    --email <LETSENCRYPT_EMAIL> --agree-tos --no-eff-email
```

Если validation не проходит, проверьте:

- оба имени уже резолвятся на VM;
- нет старой AAAA-записи;
- порты 80/443 разрешены и в UFW, и в firewall хостинг-провайдера;
- HTTP challenge локально отдаётся из `/var/www/letsencrypt`;
- CAA разрешает `letsencrypt.org`.

После успешного выпуска включите production-конфиг:

```bash
sudo cp deployment/nginx/labaduk.conf /etc/nginx/sites-available/labaduk
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable --now certbot.timer
sudo certbot certificates
sudo certbot renew --dry-run
```

Не включайте финальный конфиг до появления файлов сертификата: default HTTPS
server также ссылается на них, и `nginx -t` закономерно завершится ошибкой.

## 8. Создать администратора

После миграций создайте администратора интерактивно, не передавая пароль
аргументом shell:

```bash
cd /var/www/labaduk
php artisan user:create --admin
```

Команда запросит имя, email, скрытый пароль и его подтверждение. Опция
`--admin` требует отдельного явного подтверждения и по умолчанию отвечает
«нет». Выполнять эту команду могут только доверенные операторы с разрешённым
доступом к серверу. После создания войдите в `/admin`, включите 2FA/passkey и
убедитесь, что обычный пользователь не имеет доступа к панели.

## 9. Финальная проверка

```bash
curl -I http://lingking.space/
curl -I https://lingking.space/
curl -I https://lingking.space/en
curl -I https://www.lingking.space/
curl -i "https://lingking.space/api/appointments/slots?date=$(date -d tomorrow +%F)"

sudo nginx -t
sudo systemctl status nginx php8.4-fpm certbot.timer --no-pager
sudo docker compose ps
sudo docker compose exec db sh -c \
    'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
php artisan migrate:status
```

Проверьте в браузере:

- `/` перенаправляется на `/en`;
- landing page и изображения открываются без ошибок CSP;
- форма записи показывает слоты и создаёт запись;
- созданная запись видна в `/admin`;
- password reset действительно приходит на email;
- passkey регистрируется для origin `https://lingking.space`;
- `APP_DEBUG=false`, и production-ошибка не показывает stack trace.

Health endpoint `/up` в текущем Nginx разрешён только с `127.0.0.1` и `::1`:

```bash
curl -i --resolve lingking.space:443:127.0.0.1 \
    https://lingking.space/up
```

Внешний uptime-monitor на `/up` получит `403`, пока его конкретный IP не будет
добавлен в allowlist Nginx. Не открывайте этот endpoint всему интернету без
осознанного решения.

## 10. Лимиты и журналы

Текущие лимиты на один IP:

| Зона | Nginx | Laravel |
| --- | ---: | ---: |
| Общие страницы | 20 запросов/с, burst 40 | — |
| Просмотр слотов | 60 запросов/мин, burst 20 | 60 запросов/мин |
| Создание записи | 5 запросов/мин, burst 5 | 5 запросов/мин |
| Вход/reset | 10 запросов/мин, burst 5 | Fortify имеет дополнительные лимиты |

Диагностика:

```bash
sudo tail -f /var/log/nginx/labaduk.error.log
tail -f /var/www/labaduk/storage/logs/laravel.log
sudo journalctl -u nginx -u php8.4-fpm --since today
sudo docker compose logs --tail=200 db
```

Nginx считает лимиты по IP. Если позже перед VM появится reverse proxy, нужно
настроить доверенные адреса proxy одновременно в Nginx и Laravel. Нельзя просто
доверять любому `X-Forwarded-For`.

## 11. Резервное копирование

Создайте закрытый каталог вне репозитория:

```bash
sudo install -d -m 0700 /var/backups/labaduk
```

Ручной backup БД:

```bash
cd /var/www/labaduk
sudo docker compose exec -T db sh -c \
    'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' \
    | sudo tee "/var/backups/labaduk/db-$(date -u +%Y%m%dT%H%M%SZ).dump" >/dev/null
```

Проверяйте не только создание backup, но и тестовое восстановление на отдельной
БД/VM. Если появятся пользовательские файлы, архивируйте также
`storage/app/public`. `.env` храните в защищённом password manager/secret store,
а не в Git и не в публичном backup.

## 12. Обновление приложения

Перед deploy сделайте backup. Затем:

```bash
cd /var/www/labaduk
php artisan down --retry=60
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan reload
sudo systemctl reload php8.4-fpm
php artisan up
```

После этого повторите smoke-check из пункта 9. Если команда между `down` и
`up` завершилась ошибкой, приложение останется в maintenance mode — устраните
ошибку и явно выполните `php artisan up`.

Регулярно:

- устанавливайте security updates Debian;
- обновляйте PostgreSQL в пределах поддерживаемой major-ветки после backup;
- проверяйте свободное место командой `df -h` и размер Docker volume;
- выполняйте `sudo certbot renew --dry-run` после изменений Nginx/firewall;
- проверяйте срок домена и автоматическое продление в Vercel.

## 13. Важные свойства текущей конфигурации

- приложение и расписание работают в UTC; страница записи тоже явно показывает
  UTC — не меняйте timezone VM в попытке сдвинуть слоты;
- Redis не требуется: session, cache и queue используют PostgreSQL;
- наружу должен смотреть только каталог `/var/www/labaduk/public`;
- неизвестные Host/SNI закрываются Nginx кодом `444`;
- HSTS включается в финальном HTTPS-конфиге на один год;
- CSP совместима с текущими Livewire/Filament страницами, но пока содержит
  `unsafe-inline` и `unsafe-eval`;
- текущий `/up` проверяет запуск Laravel, но не делает полноценную проверку
  доступности БД.

## Официальные справочные материалы

- [Vercel: управление DNS-записями](https://vercel.com/docs/domains/managing-dns-records)
- [Docker: установка Engine на Debian](https://docs.docker.com/engine/install/debian/)
- [Laravel 13: production deployment](https://laravel.com/framework/docs/deployment)
- [Certbot: webroot authenticator](https://eff-certbot.readthedocs.io/en/stable/using.html#webroot)
- [PostgreSQL: политика версий](https://www.postgresql.org/support/versioning/)
