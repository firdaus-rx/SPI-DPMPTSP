#!/bin/sh
set -e

# ── Tunggu jika butuh DB eksternal (jika DB_HOST bukan sqlite) ──
# Tidak ada wait-for-it bila sqlite

echo "[entrypoint] Storage & cache setup..."
mkdir -p storage/app/private/import storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ ! -f .env ]; then
  echo "[entrypoint] .env tidak ada → copy .env.example"
  cp .env.example .env
fi

# Isi APP_KEY jika kosong
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
  echo "[entrypoint] Generate APP_KEY..."
  php artisan key:generate --force || true
fi

echo "[entrypoint] Cek binary OCR..."
which tesseract && tesseract --version | head -n 1 || echo "[warn] tesseract tidak ditemukan"
which pdftoppm && pdftoppm -v 2>&1 | head -n 1 || echo "[warn] pdftoppm tidak ditemukan"
tesseract --list-langs 2>/dev/null | grep -E "ind|eng" || echo "[warn] tessdata ind/eng belum ada"

# Selalu paksa binary Linux di dalam container (override .env Windows)
# Jika sudah ada env di docker-compose, ini tetap konsisten
if grep -q "^TESSERACT_BINARY=" .env; then
  sed -i 's|^TESSERACT_BINARY=.*|TESSERACT_BINARY=/usr/bin/tesseract|' .env
else
  echo 'TESSERACT_BINARY=/usr/bin/tesseract' >> .env
fi
if grep -q "^POPPLER_BINARY=" .env; then
  sed -i 's|^POPPLER_BINARY=.*|POPPLER_BINARY=/usr/bin/pdftoppm|' .env
else
  echo 'POPPLER_BINARY=/usr/bin/pdftoppm' >> .env
fi

echo "[entrypoint] Env OCR (container):"
grep -E "^(LARAVEL_OCR_DRIVER|TESSERACT_|POPPLER_)" .env || true

echo "[entrypoint] Migrate & seed..."
php artisan migrate --force || true
php artisan db:seed --force || true
php artisan storage:link || true
php artisan config:clear || true
php artisan view:clear || true
php artisan optimize:clear || true

# Pastikan build ada; jika tidak, build di dalam container (butuh node)
if [ ! -f public/build/manifest.json ]; then
  echo "[entrypoint] public/build tidak ada → npm install & build..."
  if command -v npm >/dev/null 2>&1; then
    npm ci --ignore-scripts || npm install
    npm run build || echo "[warn] vite build gagal, lanjut tanpa build"
  else
    echo "[warn] npm tidak ada — lewati build frontend"
  fi
fi

echo "[entrypoint] Siap → $@"
exec "$@"
