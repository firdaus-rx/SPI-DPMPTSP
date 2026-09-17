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

# Normalisasi CRLF (host Windows) → LF agar grep/sed & artisan tidak gagal
if file .env | grep -q CRLF 2>/dev/null; then
  echo "[entrypoint] Konversi .env CRLF → LF..."
  sed -i 's/\r$//' .env || true
fi

# Pastikan SQLite file ada sebelum migrate (hindari error DB)
if grep -q "^DB_CONNECTION=sqlite" .env 2>/dev/null || [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  DB_FILE=$(grep -E "^DB_DATABASE=" .env 2>/dev/null | cut -d= -f2- | tr -d '"' | tr -d "'" | xargs 2>/dev/null || echo "/var/www/html/database/database.sqlite")
  [ -z "$DB_FILE" ] && DB_FILE="/var/www/html/database/database.sqlite"
  # Handle :memory: atau path relatif
  case "$DB_FILE" in
    :memory:) ;;
    /*) mkdir -p "$(dirname "$DB_FILE")" 2>/dev/null || true; touch "$DB_FILE" 2>/dev/null || true ;;
    *)  mkdir -p "$(dirname "/var/www/html/$DB_FILE")" 2>/dev/null || true; touch "/var/www/html/$DB_FILE" 2>/dev/null || true ;;
  esac
  echo "[entrypoint] SQLite DB: $DB_FILE (exists: $(test -f "$DB_FILE" && echo yes || echo no))"
fi

# Isi APP_KEY jika kosong — harus sebelum config:clear & sebelum handle request
# Cek env var dan file; artisan butuh APP_KEY di .env, bukan hanya env var
if ! grep -q "APP_KEY=base64" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null || grep -q "^APP_KEY=\s*$" .env 2>/dev/null; then
  echo "[entrypoint] APP_KEY kosong → generate..."
  # Coba artisan dulu (paling kompatibel dengan Laravel)
  if ! php artisan key:generate --force 2>&1; then
    echo "[entrypoint] artisan key:generate gagal, fallback manual..."
    GEN_KEY="base64:$(openssl rand -base64 32 | tr -d '\n')"
    if grep -q "^APP_KEY=" .env 2>/dev/null; then
      # Escape untuk sed
      ESCAPED=$(printf '%s' "$GEN_KEY" | sed 's/[\/&]/\\&/g')
      sed -i "s|^APP_KEY=.*|APP_KEY=$ESCAPED|" .env || echo "APP_KEY=$GEN_KEY" >> .env
    else
      echo "APP_KEY=$GEN_KEY" >> .env
    fi
    echo "[entrypoint] APP_KEY fallback terpasang."
  fi
  # Verifikasi
  if grep -q "APP_KEY=base64" .env 2>/dev/null; then
    echo "[entrypoint] APP_KEY OK: $(grep "^APP_KEY=" .env | cut -c1-30)..."
  else
    echo "[entrypoint][ERROR] APP_KEY masih kosong! isi manual: php artisan key:generate --show"
    cat .env | grep APP_KEY || true
  fi
else
  echo "[entrypoint] APP_KEY sudah ada."
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
