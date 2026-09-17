# syntax=docker/dockerfile:1

# ── Stage frontend: build Vite (agar public/build ikut ke image) ──
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts 2>/dev/null || npm install --ignore-scripts
COPY . .
RUN npm run build || echo "vite build skipped"

# ── Stage app: PHP-FPM + OCR (single deploy inti) ──
FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip pkg-config file openssl \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev libsqlite3-dev \
    tesseract-ocr tesseract-ocr-eng tesseract-ocr-ind poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN tesseract --version && pdftoppm -v 2>&1 | head -n 1; \
    tesseract --list-langs | grep -E "ind|eng" || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --no-progress || true

COPY . .
# Vite build dari stage frontend (wajib — host tidak perlu npm)
COPY --from=frontend /app/public/build ./public/build

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress \
    && mkdir -p storage/app/private/import storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
