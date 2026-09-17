#!/usr/bin/env bash
# SPI DPMPTSP — Installer VPS Linux (Ubuntu/Debian) — Nginx + PHP-FPM 8.2 + SQLite + OCR
# Single-stage: langsung inti, tanpa Docker.
# Sumber: clone → install deps → build → nginx → systemd → Cloudflare Tunnel
set -euo pipefail

# ── Config (override via ENV) ──
REPO_URL="${REPO_URL:-https://github.com/firdaus-rx/SPI-DPMPTSP.git}"
APP_DIR="${APP_DIR:-/var/www/spi-dpmptsp}"
APP_PORT="${APP_PORT:-8050}"
DOMAIN="${DOMAIN:-dpmptsp.pidie.pipay.id}"
APP_URL="${APP_URL:-https://$DOMAIN}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@dpmptsp.pidie.go.id}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-password}"
PHP_VERSION="${PHP_VERSION:-auto}"
BRANCH="${BRANCH:-main}"
SKIP_OCR="${SKIP_OCR:-0}"
SKIP_BUILD="${SKIP_BUILD:-0}"   # 1 = skip npm run build (jika public/build sudah ada di repo)
NGINX_CONF="${NGINX_CONF:-/etc/nginx/sites-available/spi-dpmptsp}"

log(){ echo -e "\033[1;34m[install]\033[0m $*"; }
warn(){ echo -e "\033[1;33m[warn]\033[0m $*"; }
err(){ echo -e "\033[1;31m[err]\033[0m $*" >&2; }

need_root(){
  if [ "$(id -u)" -ne 0 ]; then
    err "Jalankan sebagai root: sudo bash $0"
    exit 1
  fi
}

need_root

log "1/8 — Update & deps (nginx, php-fpm, sqlite, tesseract, poppler, nodejs)"
apt-get update

# Auto-detect PHP: Ubuntu Noble (24.04) default 8.3, bukan 8.2 — jalankan SETELAH apt-get update
if [ "$PHP_VERSION" = "auto" ] || ! apt-cache policy php${PHP_VERSION}-fpm 2>/dev/null | grep -q "Candidate: [0-9]"; then
  DETECTED=""
  for v in 8.4 8.3 8.2 8.1; do
    if apt-cache policy php${v}-fpm 2>/dev/null | grep -q "Candidate: [0-9]"; then DETECTED="$v"; break; fi
  done
  if [ -n "$DETECTED" ] && [ "$DETECTED" != "$PHP_VERSION" ]; then
    warn "php${PHP_VERSION}-fpm tidak tersedia, pakai php${DETECTED}-fpm (Ubuntu $(lsb_release -rs 2>/dev/null || echo Noble))"
    PHP_VERSION="$DETECTED"
  elif [ -z "$DETECTED" ]; then
    warn "Tidak ada php-fpm candidate, tambah PPA ondrej/php..."
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update
    for v in 8.4 8.3 8.2; do if apt-cache policy php${v}-fpm 2>/dev/null | grep -q "Candidate: [0-9]"; then DETECTED="$v"; break; fi; done
    if [ -n "$DETECTED" ]; then PHP_VERSION="$DETECTED"; else PHP_VERSION="8.3"; warn "Fallback paksa ke php8.3"; fi
  fi
fi
# Fallback terakhir — jangan biarkan auto lolos ke apt-get
if [ "$PHP_VERSION" = "auto" ]; then
  warn "auto-detect gagal, fallback ke 8.3"
  PHP_VERSION="8.3"
fi
log "PHP versi terpilih: $PHP_VERSION"
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
  git curl zip unzip pkg-config file openssl ca-certificates lsb-release \
  nginx \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-common \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip \
  php${PHP_VERSION}-gd php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-curl \
  php${PHP_VERSION}-bcmath \
  sqlite3 libsqlite3-dev \
  tesseract-ocr tesseract-ocr-eng tesseract-ocr-ind poppler-utils

# Node.js 20 (Nodesource)
if ! command -v node >/dev/null 2>&1 || ! node -v | grep -q "v20"; then
  log "Install Node.js 20..."
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

# Composer
if ! command -v composer >/dev/null 2>&1; then
  log "Install Composer..."
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

log "Versi: php $(php -v | head -n1) | node $(node -v) | nginx $(nginx -v 2>&1) | tesseract $(tesseract --version 2>&1 | head -n1)"

log "2/8 — Clone $REPO_URL ($BRANCH) → $APP_DIR"
if [ -d "$APP_DIR/.git" ]; then
  log "Repo sudah ada, update..."
  git -C "$APP_DIR" fetch origin
  git -C "$APP_DIR" checkout "$BRANCH"
  git -C "$APP_DIR" pull --ff-only origin "$BRANCH" || git -C "$APP_DIR" pull origin "$BRANCH"
else
  rm -rf "$APP_DIR"
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"

log "3/8 — .env & APP_KEY"
if [ ! -f .env ]; then
  cp .env.example .env
  log "Copy .env.example → .env"
fi
# Normalisasi CRLF jika dari Windows
if file .env | grep -q CRLF 2>/dev/null; then
  sed -i 's/\r$//' .env
fi
# Isi APP_URL & PORT
if grep -q "^APP_URL=" .env; then sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env; else echo "APP_URL=$APP_URL" >> .env; fi
if grep -q "^APP_PORT=" .env; then sed -i "s|^APP_PORT=.*|APP_PORT=$APP_PORT|" .env; else echo "APP_PORT=$APP_PORT" >> .env; fi
if grep -q "^APP_NAME=" .env; then sed -i "s|^APP_NAME=.*|APP_NAME=\"SPI DPMPTSP\"|" .env; else echo 'APP_NAME="SPI DPMPTSP"' >> .env; fi
# APP_KEY
if ! grep -q "APP_KEY=base64" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
  log "Generate APP_KEY..."
  php artisan key:generate --force || {
    GEN_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
    sed -i "s|^APP_KEY=.*|APP_KEY=$GEN_KEY|" .env || echo "APP_KEY=$GEN_KEY" >> .env
  }
fi
# SQLite file
mkdir -p database
DB_FILE=$(grep -E "^DB_DATABASE=" .env | cut -d= -f2- | tr -d '"' | tr -d "'" | xargs 2>/dev/null || echo "database/database.sqlite")
[ -z "$DB_FILE" ] && DB_FILE="database/database.sqlite"
case "$DB_FILE" in
  :memory:) ;;
  /*) mkdir -p "$(dirname "$DB_FILE")" 2>/dev/null || true; touch "$DB_FILE" 2>/dev/null || true ;;
  *)  mkdir -p "$(dirname "$APP_DIR/$DB_FILE")" 2>/dev/null || true; touch "$APP_DIR/$DB_FILE" 2>/dev/null || true ;;
esac
# OCR path Linux
if grep -q "^TESSERACT_BINARY=" .env; then sed -i 's|^TESSERACT_BINARY=.*|TESSERACT_BINARY=/usr/bin/tesseract|' .env; else echo 'TESSERACT_BINARY=/usr/bin/tesseract' >> .env; fi
if grep -q "^POPPLER_BINARY=" .env; then sed -i 's|^POPPLER_BINARY=.*|POPPLER_BINARY=/usr/bin/pdftoppm|' .env; else echo 'POPPLER_BINARY=/usr/bin/pdftoppm' >> .env; fi
# Admin env untuk seeder
if ! grep -q "^ADMIN_EMAIL=" .env; then echo "ADMIN_EMAIL=$ADMIN_EMAIL" >> .env; fi
if ! grep -q "^ADMIN_PASSWORD=" .env; then echo "ADMIN_PASSWORD=$ADMIN_PASSWORD" >> .env; fi
log "Env OK: APP_URL=$APP_URL APP_PORT=$APP_PORT DB=$DB_FILE"

log "4/8 — Composer & permissions"
composer install --no-dev --no-interaction --optimize-autoloader --no-progress
mkdir -p storage/app/private/import storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache database
# Pastikan sqlite file ada & writable oleh www-data (fix attempt to write a readonly database)
if [ "$DB_FILE" != ":memory:" ]; then
  case "$DB_FILE" in
    /*) DB_ABS="$DB_FILE" ;;
    *)  DB_ABS="$APP_DIR/$DB_FILE" ;;
  esac
  mkdir -p "$(dirname "$DB_ABS")" 2>/dev/null || true
  touch "$DB_ABS" 2>/dev/null || true
  chown www-data:www-data "$DB_ABS" 2>/dev/null || chown -R www-data:www-data "$(dirname "$DB_ABS")" 2>/dev/null || true
  chmod 664 "$DB_ABS" 2>/dev/null || true
  chmod 775 "$(dirname "$DB_ABS")" 2>/dev/null || true
fi
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R 775 storage bootstrap/cache database 2>/dev/null || true

log "5/8 — Frontend build"
if [ "$SKIP_BUILD" = "1" ] && [ -f public/build/manifest.json ]; then
  log "SKIP_BUILD=1 & public/build ada → skip npm"
else
  if [ ! -f public/build/manifest.json ]; then
    log "public/build belum ada → npm ci & build"
  fi
  # Gunakan npm ci jika lock ada, else npm install
  if [ -f package-lock.json ]; then npm ci --ignore-scripts || npm install; else npm install; fi
  npm run build || { warn "vite build gagal, lanjut"; }
fi

log "6/8 — Migrate & seed & storage:link"
php artisan migrate --force
php artisan db:seed --force || true
php artisan storage:link || true
php artisan config:clear || true
php artisan view:clear || true
php artisan optimize:clear || true
# Cache prod
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

log "7/8 — Nginx site :$APP_PORT → php$PHP_VERSION-fpm"
# Deteksi socket php-fpm
FPM_SOCK=""
for cand in "/run/php/php${PHP_VERSION}-fpm.sock" "/var/run/php/php${PHP_VERSION}-fpm.sock" "/run/php/php-fpm.sock"; do
  if [ -S "$cand" ]; then FPM_SOCK="$cand"; break; fi
done
if [ -z "$FPM_SOCK" ]; then
  # fallback TCP
  FPM_SOCK="127.0.0.1:9000"
  FPM_DIRECTIVE="fastcgi_pass $FPM_SOCK;"
else
  FPM_DIRECTIVE="fastcgi_pass unix:$FPM_SOCK;"
fi
log "PHP-FPM: $FPM_SOCK"

cat > "$NGINX_CONF" <<NGINX
server {
    listen $APP_PORT;
    server_name $DOMAIN localhost;
    root $APP_DIR/public;
    index index.php index.html;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ /\. { deny all; access_log off; log_not_found off; }

    location ~ \.php\$ {
        $FPM_DIRECTIVE
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120s;
        client_max_body_size 25m;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files \$uri =404;
    }

    location /guest/ {
        alias $APP_DIR/public/guest/;
        try_files \$uri \$uri/ =404;
    }

    access_log /var/log/nginx/spi-access.log;
    error_log /var/log/nginx/spi-error.log warn;
}
NGINX

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/spi-dpmptsp 2>/dev/null || true
# Hapus default jika bentrok port 80
if [ -f /etc/nginx/sites-enabled/default ] && grep -q "listen 80" /etc/nginx/sites-enabled/default 2>/dev/null; then
  warn "sites-enabled/default masih listen 80 — tidak dihapus (aman, port berbeda $APP_PORT)"
fi

nginx -t
systemctl enable nginx 2>/dev/null || true
systemctl enable php${PHP_VERSION}-fpm 2>/dev/null || true
systemctl restart php${PHP_VERSION}-fpm || service php${PHP_VERSION}-fpm restart || true
systemctl restart nginx || service nginx restart || true
systemctl is-active --quiet nginx && log "Nginx OK :$APP_PORT" || warn "Nginx gagal start, cek: journalctl -u nginx -n 50"
systemctl is-active --quiet php${PHP_VERSION}-fpm && log "PHP-FPM OK" || warn "FPM gagal, cek: journalctl -u php${PHP_VERSION}-fpm -n 50"

# Trust proxy sudah ada di bootstrap/app.php (Cloudflare Tunnel)

log "8/8 — Verifikasi"
echo ""
echo "────────────────────────────────────────────────"
echo "  SPI DPMPTSP — VPS Nginx (tanpa Docker)"
echo "────────────────────────────────────────────────"
echo "  App:     $APP_DIR"
echo "  URL:     $APP_URL (APP_PORT=$APP_PORT → nginx $APP_PORT)"
echo "  Nginx:   $NGINX_CONF → :$APP_PORT → $FPM_SOCK"
echo "  DB:      $DB_FILE (sqlite)"
echo "  OCR:     /usr/bin/tesseract (ind+eng), /usr/bin/pdftoppm"
echo "  Login:   $ADMIN_EMAIL / $ADMIN_PASSWORD"
echo "────────────────────────────────────────────────"
echo ""
if curl -fsI "http://127.0.0.1:$APP_PORT/login" >/dev/null 2>&1; then
  log "curl http://127.0.0.1:$APP_PORT/login → OK"
else
  warn "curl login belum OK — cek: curl -I http://127.0.0.1:$APP_PORT/ && tail -n 50 $APP_DIR/storage/logs/laravel.log"
fi
echo ""
echo "Cloudflare Tunnel (host):"
echo "  tunnel: cloudflared tunnel --url http://localhost:$APP_PORT"
echo "  config: service: http://localhost:$APP_PORT  (hostname: $DOMAIN)"
echo "  env:    APP_URL=$APP_URL (https)"
echo ""
echo "Update:"
echo "  cd $APP_DIR && git pull origin $BRANCH && sudo bash scripts/install-vps-nginx.sh"
echo "  # atau: APP_DIR=$APP_DIR APP_PORT=$APP_PORT DOMAIN=$DOMAIN bash scripts/install-vps-nginx.sh"
echo ""
log "Selesai — buka http://\$(hostname -I | awk '{print \$1}'):$APP_PORT atau $APP_URL (tunnel)"
