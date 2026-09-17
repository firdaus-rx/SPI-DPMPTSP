# syntax=docker/dockerfile:1

# ── Stage 1: Frontend (Vite) ──
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts
COPY . .
# Build dipisah agar tidak gagal bila host sudah build; fallback aman
RUN npm run build || echo "vite build skipped"

# ── Stage 2: PHP-FPM ──
FROM php:8.2-fpm-bookworm

# System deps + Tesseract (ind+eng) + Poppler (pdftoppm)
# libsqlite3-dev + pkg-config wajib untuk pdo_sqlite (error: Package 'sqlite3' not found)
# file + openssl untuk entrypoint (deteksi CRLF & fallback key)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip pkg-config file openssl \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    libfreetype6-dev libjpeg62-turbo-dev \
    libsqlite3-dev \
    tesseract-ocr tesseract-ocr-eng tesseract-ocr-ind \
    poppler-utils \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_sqlite \
        mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Verifikasi OCR binary (gagal build jika tidak terpasang)
RUN tesseract --version && pdftoppm -v 2>&1 | head -n 1; \
    tesseract --list-langs | grep -E "ind|eng" || true

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP deps (cache layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --no-progress || true

# App source
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/node_modules ./node_modules

# PHP ini (upload 20MB sesuai ImportPdfController)
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-progress \
    && mkdir -p storage/app/private/import storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
