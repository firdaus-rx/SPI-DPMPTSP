# syntax=docker/dockerfile:1
FROM php:8.2-cli-bookworm

# deps: tesseract ind+eng + poppler + sqlite + gd + zip + node20
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip pkg-config file openssl ca-certificates gnupg \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libsqlite3-dev \
    tesseract-ocr tesseract-ocr-eng tesseract-ocr-ind poppler-utils sqlite3 \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && node -v && npm -v

RUN tesseract --version && pdftoppm -v 2>&1 | head -n 1; tesseract --list-langs | grep -E "ind|eng" || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --no-progress || true

COPY . .

# Vite build — WAJIB tiap build image (npm ci + build, bukan fallback echo)
RUN npm ci --ignore-scripts 2>/dev/null || npm install --ignore-scripts \
    && npm run build

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress \
    && mkdir -p storage/app/private/import storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache database \
    && touch database/database.sqlite || true \
    && chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true \
    && chmod -R 775 storage bootstrap/cache 2>/dev/null || true

EXPOSE 8050

CMD sh -c " \
    PORT=\${APP_PORT:-8050}; \
    if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi; \
    if file .env 2>/dev/null | grep -q CRLF; then sed -i 's/\r$//' .env || true; fi; \
    if ! grep -q 'APP_KEY=base64' .env 2>/dev/null || grep -q '^APP_KEY=$' .env 2>/dev/null; then php artisan key:generate --force 2>/dev/null || GEN_KEY=\"base64:\$(openssl rand -base64 32 | tr -d '\n')\" && sed -i \"s|^APP_KEY=.*|APP_KEY=\$GEN_KEY|\" .env 2>/dev/null || echo \"APP_KEY=\$GEN_KEY\" >> .env; fi; \
    sed -i 's|^TESSERACT_BINARY=.*|TESSERACT_BINARY=/usr/bin/tesseract|' .env 2>/dev/null || echo 'TESSERACT_BINARY=/usr/bin/tesseract' >> .env; \
    sed -i 's|^POPPLER_BINARY=.*|POPPLER_BINARY=/usr/bin/pdftoppm|' .env 2>/dev/null || echo 'POPPLER_BINARY=/usr/bin/pdftoppm' >> .env; \
    touch database/database.sqlite 2>/dev/null || true; \
    php artisan migrate --force 2>&1 | tail -n 20; \
    php artisan db:seed --force 2>&1 | tail -n 20; \
    php artisan storage:link 2>/dev/null || true; \
    php artisan config:clear 2>/dev/null || true; \
    echo \"[ready] http://0.0.0.0:\$PORT\"; \
    php artisan serve --host=0.0.0.0 --port=\$PORT \
    "
