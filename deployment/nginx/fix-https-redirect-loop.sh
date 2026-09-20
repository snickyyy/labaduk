#!/usr/bin/env bash

set -Eeuo pipefail
umask 077

readonly SITE_CONFIG="/etc/nginx/sites-available/labaduk"
readonly SITE_LINK="/etc/nginx/sites-enabled/labaduk"
readonly RATE_CONFIG="/etc/nginx/conf.d/labaduk-rate-limits.conf"
readonly APP_PUBLIC="/var/www/labaduk/public"
readonly PRIMARY_DOMAIN="lingking.space"
readonly WWW_DOMAIN="www.lingking.space"
readonly DEFAULT_ACME_WEBROOT="/var/www/letsencrypt"

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_dir="/var/backups/nginx-labaduk-${timestamp}"
rollback_armed=0
created_rate_config=0
created_site_link=0

log() {
    printf '\n==> %s\n' "$*"
}

die() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

trim_ini_value() {
    local key="$1"
    local file="$2"

    awk -F= -v wanted="$key" '
        {
            name = $1
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
        }
        name == wanted {
            sub(/^[^=]*=[[:space:]]*/, "")
            sub(/[[:space:],]+$/, "")
            print
            exit
        }
    ' "$file"
}

validate_path() {
    local label="$1"
    local path="$2"

    [[ "$path" =~ ^/[A-Za-z0-9._/+:-]+$ ]] ||
        die "$label contains unsupported characters: $path"
}

restore_backup() {
    local status="$?"

    if (( status == 0 )); then
        status=1
    fi

    set +e
    trap - ERR INT TERM
    if (( rollback_armed == 1 )); then
        printf '\nApply failed; restoring %s\n' "$backup_dir/labaduk.conf" >&2
        cp -a -- "$backup_dir/labaduk.conf" "$SITE_CONFIG"

        if (( created_rate_config == 1 )); then
            rm -f -- "$RATE_CONFIG"
        fi

        if (( created_site_link == 1 )); then
            rm -f -- "$SITE_LINK"
        fi

        if nginx -t; then
            systemctl reload nginx || true
        else
            printf 'WARNING: the restored Nginx configuration did not validate.\n' >&2
        fi
    fi

    exit "$status"
}

trap restore_backup ERR INT TERM

[[ "${EUID}" -eq 0 ]] || die "run this script as root (for example: sudo bash $0)"

for command in awk basename cat certbot chmod cp curl date dirname find grep install \
    journalctl ln mkdir mktemp nginx openssl readlink rm sed sleep sort systemctl tail; do
    command -v "$command" >/dev/null 2>&1 || die "required command is missing: $command"
done

[[ -f "$SITE_CONFIG" ]] || die "expected existing site configuration: $SITE_CONFIG"
[[ -d "$APP_PUBLIC" ]] || die "Laravel public directory is missing: $APP_PUBLIC"
[[ -f "$APP_PUBLIC/index.php" ]] || die "Laravel front controller is missing: $APP_PUBLIC/index.php"

if [[ -e "$SITE_LINK" || -L "$SITE_LINK" ]]; then
    [[ "$(readlink -f "$SITE_LINK")" == "$SITE_CONFIG" ]] ||
        die "$SITE_LINK does not point to $SITE_CONFIG"
fi

[[ -d /var/backups ]] || die "/var/backups does not exist"
mkdir -- "$backup_dir"
chmod 0700 "$backup_dir"
cp -a -- "$SITE_CONFIG" "$backup_dir/labaduk.conf"
if [[ -e "$SITE_LINK" || -L "$SITE_LINK" ]]; then
    cp -a -- "$SITE_LINK" "$backup_dir/sites-enabled-labaduk"
fi

log "Reading the complete active Nginx configuration"
nginx -T >"$backup_dir/nginx-T.before.txt" 2>&1 ||
    die "the current Nginx configuration is already invalid; see $backup_dir/nginx-T.before.txt"
printf 'Saved: %s\n' "$backup_dir/nginx-T.before.txt"

log "Server-name occurrences for $PRIMARY_DOMAIN"
grep -nE "server_name[^;]*(${PRIMARY_DOMAIN//./\\.}|${WWW_DOMAIN//./\\.})" \
    "$backup_dir/nginx-T.before.txt" || true

log "Existing Laravel, FastCGI, TLS, and rate-limit directives"
grep -nE \
    'root /var/www/labaduk|try_files|fastcgi_pass|ssl_certificate|limit_req|limit_conn' \
    "$backup_dir/nginx-T.before.txt" || true

duplicate_vhosts=()
while IFS= read -r candidate; do
    [[ -n "$candidate" ]] || continue
    resolved="$(readlink -f "$candidate")"
    if [[ "$resolved" != "$SITE_CONFIG" ]]; then
        duplicate_vhosts+=("$candidate -> $resolved")
    fi
done < <(
    grep -RIlE \
        "server_name[^;]*(${PRIMARY_DOMAIN//./\\.}|${WWW_DOMAIN//./\\.})" \
        /etc/nginx/sites-enabled /etc/nginx/conf.d 2>/dev/null || true
)

if (( ${#duplicate_vhosts[@]} > 0 )); then
    printf 'Conflicting enabled virtual hosts were found:\n' >&2
    printf '  %s\n' "${duplicate_vhosts[@]}" >&2
    die "disable or reconcile the duplicate virtual host before rerunning"
fi

log "Installed PHP and PHP-FPM sockets"
if command -v php >/dev/null 2>&1; then
    php -v | sed -n '1,2p'
else
    printf 'PHP CLI is not installed; continuing with the FPM socket check.\n'
fi
find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' -print 2>/dev/null | sort || true

php_fpm_socket="${PHP_FPM_SOCKET:-}"
if [[ -n "$php_fpm_socket" ]]; then
    validate_path "PHP_FPM_SOCKET" "$php_fpm_socket"
    [[ -S "$php_fpm_socket" ]] || die "PHP_FPM_SOCKET is not a live Unix socket: $php_fpm_socket"
else
    mapfile -t fpm_sockets < <(
        find /run/php -maxdepth 1 -type s -name 'php*-fpm.sock' -print 2>/dev/null | sort
    )

    if (( ${#fpm_sockets[@]} == 1 )); then
        php_fpm_socket="${fpm_sockets[0]}"
    elif (( ${#fpm_sockets[@]} > 1 )); then
        active_fpm_sockets=()
        for socket in "${fpm_sockets[@]}"; do
            service="$(basename "$socket" .sock)"
            if systemctl is-active --quiet "$service"; then
                active_fpm_sockets+=("$socket")
            fi
        done

        if (( ${#active_fpm_sockets[@]} == 1 )); then
            php_fpm_socket="${active_fpm_sockets[0]}"
        else
            printf 'Candidate sockets:\n  %s\n' "${fpm_sockets[@]}" >&2
            die "the PHP-FPM socket is ambiguous; rerun with PHP_FPM_SOCKET=/run/php/<socket>"
        fi
    else
        die "no live PHP-FPM socket was found under /run/php"
    fi
fi
validate_path "PHP-FPM socket" "$php_fpm_socket"
printf 'Selected PHP-FPM socket: %s\n' "$php_fpm_socket"

log "Locating the existing Let's Encrypt certificate"
certificate_dir="${LE_CERTIFICATE_DIR:-}"
if [[ -n "$certificate_dir" ]]; then
    validate_path "LE_CERTIFICATE_DIR" "$certificate_dir"
else
    matching_certificate_dirs=()
    shopt -s nullglob
    for fullchain in /etc/letsencrypt/live/*/fullchain.pem; do
        san_text="$(openssl x509 -in "$fullchain" -noout -ext subjectAltName 2>/dev/null || true)"
        if grep -Eq "DNS:${PRIMARY_DOMAIN//./\\.}([,[:space:]]|$)" <<<"$san_text" &&
            grep -Eq "DNS:${WWW_DOMAIN//./\\.}([,[:space:]]|$)" <<<"$san_text"; then
            matching_certificate_dirs+=("$(dirname "$fullchain")")
        fi
    done
    shopt -u nullglob

    if (( ${#matching_certificate_dirs[@]} == 1 )); then
        certificate_dir="${matching_certificate_dirs[0]}"
    elif (( ${#matching_certificate_dirs[@]} > 1 )); then
        existing_certificate="$(
            awk '$1 == "ssl_certificate" {gsub(/;/, "", $2); print $2; exit}' "$SITE_CONFIG"
        )"
        for candidate_dir in "${matching_certificate_dirs[@]}"; do
            if [[ "$existing_certificate" == "$candidate_dir/fullchain.pem" ]]; then
                certificate_dir="$candidate_dir"
                break
            fi
        done
        if [[ -z "$certificate_dir" ]]; then
            printf 'Matching certificate directories:\n  %s\n' \
                "${matching_certificate_dirs[@]}" >&2
            die "multiple certificates cover both domains; rerun with LE_CERTIFICATE_DIR=/etc/letsencrypt/live/<name>"
        fi
    else
        die "no Let's Encrypt certificate covers both $PRIMARY_DOMAIN and $WWW_DOMAIN"
    fi
fi

validate_path "certificate directory" "$certificate_dir"
ssl_certificate="$certificate_dir/fullchain.pem"
ssl_certificate_key="$certificate_dir/privkey.pem"
ssl_trusted_certificate="$certificate_dir/chain.pem"
for certificate_file in "$ssl_certificate" "$ssl_certificate_key" "$ssl_trusted_certificate"; do
    [[ -r "$certificate_file" ]] || die "certificate file is missing or unreadable: $certificate_file"
done
openssl x509 -in "$ssl_certificate" -noout -checkend 86400 >/dev/null ||
    die "the selected certificate is expired or expires within 24 hours"

certificate_public_key="$(
    openssl x509 -in "$ssl_certificate" -pubkey -noout |
        openssl pkey -pubin -outform DER 2>/dev/null |
        openssl dgst -sha256
)"
private_public_key="$(
    openssl pkey -in "$ssl_certificate_key" -pubout -outform DER 2>/dev/null |
        openssl dgst -sha256
)"
[[ "$certificate_public_key" == "$private_public_key" ]] ||
    die "the selected certificate and private key do not match"

certificate_name="$(basename "$certificate_dir")"
renewal_config="/etc/letsencrypt/renewal/${certificate_name}.conf"
[[ -r "$renewal_config" ]] || die "Certbot renewal configuration is missing: $renewal_config"
cp -a -- "$renewal_config" "$backup_dir/certbot-renewal.conf"

authenticator="$(trim_ini_value authenticator "$renewal_config")"
[[ -n "$authenticator" ]] || die "could not identify the Certbot authenticator in $renewal_config"
printf 'Certificate: %s\n' "$ssl_certificate"
printf 'Renewal configuration: %s\n' "$renewal_config"
printf 'Renewal authenticator: %s\n' "$authenticator"

acme_webroot="$DEFAULT_ACME_WEBROOT"
case "$authenticator" in
    nginx)
        ;;
    webroot)
        configured_webroot="$(trim_ini_value webroot_path "$renewal_config")"
        if [[ -n "$configured_webroot" ]]; then
            acme_webroot="$configured_webroot"
        else
            mapfile -t mapped_webroots < <(
                awk '
                    /^\[\[webroot_map\]\]/ { in_map = 1; next }
                    /^\[/ && in_map { exit }
                    in_map && /=/ {
                        sub(/^[^=]*=[[:space:]]*/, "")
                        sub(/[[:space:],]+$/, "")
                        print
                    }
                ' "$renewal_config" | sort -u
            )
            if (( ${#mapped_webroots[@]} == 1 )); then
                acme_webroot="${mapped_webroots[0]}"
            else
                die "the webroot renewal path is missing or ambiguous in $renewal_config"
            fi
        fi
        ;;
    dns-*)
        printf 'DNS-based renewal does not require the HTTP challenge location; keeping it available.\n'
        ;;
    *)
        die "renewal authenticator '$authenticator' is not safely handled by this script"
        ;;
esac

validate_path "ACME webroot" "$acme_webroot"
mkdir -p -- "$acme_webroot/.well-known/acme-challenge"
printf 'ACME webroot: %s\n' "$acme_webroot"

log "Checking application-specific Nginx rate-limit zones"
rate_zones=(
    labaduk_general
    labaduk_api_read
    labaduk_appointments
    labaduk_auth
    labaduk_connections
)
present_rate_zones=0
for zone in "${rate_zones[@]}"; do
    if grep -Eq "zone=${zone}:" "$backup_dir/nginx-T.before.txt"; then
        (( present_rate_zones += 1 ))
    fi
done

need_rate_config=0
if (( present_rate_zones == 0 )); then
    [[ ! -e "$RATE_CONFIG" ]] ||
        die "$RATE_CONFIG exists but its zones are not active; inspect the Nginx include layout"
    need_rate_config=1
elif (( present_rate_zones != ${#rate_zones[@]} )); then
    die "only some required Labaduk rate-limit zones are defined; reconcile them manually"
fi

log "Writing the corrected virtual host"
rollback_armed=1

if (( need_rate_config == 1 )); then
    rate_tmp="$(mktemp /etc/nginx/conf.d/.labaduk-rate-limits.XXXXXX)"
    cat >"$rate_tmp" <<'NGINX_RATES'
limit_req_zone  $binary_remote_addr  zone=labaduk_general:10m      rate=20r/s;
limit_req_zone  $binary_remote_addr  zone=labaduk_api_read:10m     rate=60r/m;
limit_req_zone  $binary_remote_addr  zone=labaduk_appointments:10m rate=5r/m;
limit_req_zone  $binary_remote_addr  zone=labaduk_auth:10m         rate=10r/m;
limit_conn_zone $binary_remote_addr  zone=labaduk_connections:10m;
limit_req_status 429;
limit_conn_status 429;
limit_req_log_level warn;
NGINX_RATES
    install -o root -g root -m 0644 "$rate_tmp" "$RATE_CONFIG"
    rm -f -- "$rate_tmp"
    created_rate_config=1
fi

site_template="$(mktemp /etc/nginx/sites-available/.labaduk.XXXXXX)"
site_rendered="$(mktemp /etc/nginx/sites-available/.labaduk-rendered.XXXXXX)"

cat >"$site_template" <<'NGINX_SITE'
server {
    listen 80;
    listen [::]:80;

    server_name lingking.space www.lingking.space;

    location ^~ /.well-known/acme-challenge/ {
        root __ACME_WEBROOT__;
        default_type text/plain;
        try_files $uri =404;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;

    server_name lingking.space www.lingking.space;

    root /var/www/labaduk/public;
    index index.php;

    ssl_certificate __SSL_CERTIFICATE__;
    ssl_certificate_key __SSL_CERTIFICATE_KEY__;
    ssl_trusted_certificate __SSL_TRUSTED_CERTIFICATE__;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:labaduk_ssl:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    server_tokens off;
    client_max_body_size 10m;
    client_body_timeout 15s;
    client_header_timeout 15s;
    keepalive_timeout 30s;
    send_timeout 30s;

    limit_conn labaduk_connections 30;

    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), geolocation=(), microphone=()" always;
    add_header X-Permitted-Cross-Domain-Policies "none" always;
    add_header Cross-Origin-Opener-Policy "same-origin" always;
    add_header Content-Security-Policy "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; connect-src 'self'" always;

    access_log /var/log/nginx/labaduk.access.log;
    error_log /var/log/nginx/labaduk.error.log warn;

    location ^~ /.well-known/acme-challenge/ {
        root __ACME_WEBROOT__;
        default_type text/plain;
        try_files $uri =404;
    }

    location = /up {
        allow 127.0.0.1;
        allow ::1;
        deny all;
        try_files "" @laravel;
    }

    location = /api/appointments {
        limit_req zone=labaduk_appointments burst=5 nodelay;
        try_files "" @laravel;
    }

    location = /api/appointments/slots {
        limit_req zone=labaduk_api_read burst=20 nodelay;
        try_files "" @laravel;
    }

    location ~ ^/(?:login|register|forgot-password|reset-password|two-factor-challenge|passkeys/login)/?$ {
        limit_req zone=labaduk_auth burst=5 nodelay;
        try_files "" @laravel;
    }

    location = / {
        limit_req zone=labaduk_general burst=40 nodelay;
        try_files "" @laravel;
    }

    location ~* ^/(?:build|css|js|fonts|images)/.+\.(?:css|js|mjs|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf)$ {
        try_files $uri =404;
        expires 7d;
        access_log off;
    }

    location / {
        limit_req zone=labaduk_general burst=40 nodelay;
        try_files $uri $uri/ @laravel;
    }

    location ~ /\.(?!well-known(?:/|$)) {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~* \.(?:bak|conf|env|ini|log|old|orig|sql|sqlite|swp|ya?ml)$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ \.php(?:/|$) {
        return 404;
    }

    location @laravel {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param SCRIPT_NAME /index.php;
        fastcgi_param HTTPS on;
        fastcgi_param HTTP_X_FORWARDED_PROTO https;
        fastcgi_param HTTP_PROXY "";

        fastcgi_pass unix:__PHP_FPM_SOCKET__;
        fastcgi_connect_timeout 5s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
        fastcgi_hide_header X-Powered-By;
    }
}
NGINX_SITE

sed \
    -e "s|__ACME_WEBROOT__|$acme_webroot|g" \
    -e "s|__SSL_CERTIFICATE__|$ssl_certificate|g" \
    -e "s|__SSL_CERTIFICATE_KEY__|$ssl_certificate_key|g" \
    -e "s|__SSL_TRUSTED_CERTIFICATE__|$ssl_trusted_certificate|g" \
    -e "s|__PHP_FPM_SOCKET__|$php_fpm_socket|g" \
    "$site_template" >"$site_rendered"

install -o root -g root -m 0644 "$site_rendered" "$SITE_CONFIG"
rm -f -- "$site_template" "$site_rendered"

if [[ ! -e "$SITE_LINK" && ! -L "$SITE_LINK" ]]; then
    ln -s "$SITE_CONFIG" "$SITE_LINK"
    created_site_link=1
fi

log "Validating and reloading Nginx"
nginx -t
systemctl reload nginx
rollback_armed=0

log "HTTP and HTTPS verification"
curl -sS -I --max-time 20 "http://$PRIMARY_DOMAIN/"
https_headers=""
for attempt in {1..10}; do
    candidate_headers=""
    if candidate_headers="$(curl -sS -I --max-time 20 "https://$PRIMARY_DOMAIN/")"; then
        candidate_status="$(awk 'NR == 1 {print $2}' <<<"$candidate_headers")"
        candidate_location="$(
            awk 'BEGIN {IGNORECASE=1} /^Location:/ {sub(/^[^:]*:[[:space:]]*/, ""); sub(/\r$/, ""); print; exit}' \
                <<<"$candidate_headers"
        )"

        if [[ ! "$candidate_status" =~ ^30[12378]$ ||
            "$candidate_location" != "https://$PRIMARY_DOMAIN/" ]]; then
            https_headers="$candidate_headers"
            break
        fi
    fi

    if (( attempt < 10 )); then
        printf 'Waiting for the graceful Nginx reload to reach new connections (%d/10)...\n' \
            "$attempt"
        sleep 1
    fi
done

[[ -n "$https_headers" ]] || die "HTTPS still redirects to itself after the reload grace period"
printf '%s\n' "$https_headers"

https_status="$(awk 'NR == 1 {print $2}' <<<"$https_headers")"

if [[ "$https_status" =~ ^5 ]]; then
    printf '\nHTTPS returned %s. Diagnostic output follows.\n' "$https_status" >&2
    tail -n 100 /var/log/nginx/labaduk.error.log 2>/dev/null || true
    tail -n 100 /var/www/labaduk/storage/logs/laravel.log 2>/dev/null || true
    service="$(basename "$php_fpm_socket" .sock)"
    journalctl -u "$service" --since '-15 minutes' --no-pager 2>/dev/null || true
    die "Laravel/PHP-FPM returned a server error; DNS and SSL were left unchanged"
fi

curl -sS -I -L --max-redirs 5 --max-time 30 "https://$PRIMARY_DOMAIN/"

log "Certificate and renewal verification"
openssl x509 -in "$ssl_certificate" -noout -subject -issuer -dates
certbot renew --dry-run --cert-name "$certificate_name"

log "Completed"
printf 'Backup: %s\n' "$backup_dir/labaduk.conf"
printf 'Rollback:\n'
printf '  sudo cp -a -- %q %q && sudo nginx -t && sudo systemctl reload nginx\n' \
    "$backup_dir/labaduk.conf" "$SITE_CONFIG"
