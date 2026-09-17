# SPI DPMPTSP Kabupaten Pidie

Sistem Pengawasan Kepatuhan — **Daftar List Sanksi Pencabutan** & **Usulan Pencabutan Perizinan Berusaha** (OCR PDF, cetak SP1 & Rekap).

---

## Persyaratan Server

| Komponen | Minimal | Rekomendasi |
|----------|---------|-------------|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disk | 20 GB SSD | 40 GB SSD |
| OS | Ubuntu 22.04 / 24.04 LTS | Ubuntu 24.04 LTS |
| Akses | SSH root / sudo | SSH root / sudo |

Software akan diinstal otomatis oleh installer (Docker atau Nginx): Docker Engine 24+, PHP 8.2/8.3-FPM, SQLite 3, Nginx, Tesseract `ind`+`eng`, Poppler `pdftoppm`, Node 20, Composer.

Port yang dipakai: **`8050`** (host) → container/app `8000` atau Nginx `80`. Pastikan `8050` tidak bentrok (`ss -tulpn | grep 8050`).

---

## Opsi A — Docker (1 container, paling mudah)

Image single-stage `php:8.2-cli` — `artisan serve 0.0.0.0:8000`, tanpa Nginx terpisah. DB SQLite volume `app_db`.

```bash
# 1. Clone
git clone https://github.com/firdaus-rx/SPI-DPMPTSP.git spi-dpmptsp
cd spi-dpmptsp

# 2. Env
cp .env.example .env
nano .env
# Wajib:
# APP_NAME="SPI DPMPTSP"
# APP_ENV=production
# APP_DEBUG=false
# APP_URL=https://dpmptsp.pidie.pipay.id
# APP_PORT=8050
# DB_CONNECTION=sqlite
# DB_DATABASE=/var/www/html/database/database.sqlite

# 3. Up
docker compose up --build -d
docker compose ps
docker compose logs -f app
```

Tunggu log:

```
[ready] http://0.0.0.0:8000
tesseract 5.x — eng, ind
pdftoppm version 22.x
```

Buka `http://SERVER_IP:8050` → `http://SERVER_IP:8050/login` (`admin@dpmptsp.pidie.go.id / password`)

Update:

```bash
git pull origin main
docker compose up --build -d
```

Hentikan / reset:

```bash
docker compose down
docker compose down -v          # hapus volume DB
docker compose exec app php artisan migrate:fresh --seed --force
```

---

## Opsi B — VPS Nginx tanpa Docker

Installer `scripts/install-vps-nginx.sh` — Nginx + PHP-FPM auto-detect `8.2/8.3/8.4` + SQLite + OCR. Port `8050` → `php-fpm` socket.

```bash
# Clone dahulu (atau langsung curl installer)
git clone https://github.com/firdaus-rx/SPI-DPMPTSP.git /var/www/spi-dpmptsp
cd /var/www/spi-dpmptsp

# Install (butuh root)
sudo bash scripts/install-vps-nginx.sh

# Kustom (opsional)
sudo APP_PORT=8050 DOMAIN=dpmptsp.pidie.pipay.id \
  ADMIN_EMAIL=admin@dpmptsp.pidie.go.id ADMIN_PASSWORD=password \
  bash scripts/install-vps-nginx.sh

# Cek
curl -I http://127.0.0.1:8050/login
systemctl status nginx php*-fpm
tail -n 50 /var/www/spi-dpmptsp/storage/logs/laravel.log
```

Update:

```bash
cd /var/www/spi-dpmptsp
git pull origin main
sudo bash scripts/install-vps-nginx.sh
```

---

## Konfigurasi Penting

`.env`:

```env
APP_URL=https://dpmptsp.pidie.pipay.id
APP_PORT=8050
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite   # Docker
# DB_DATABASE=database/database.sqlite                # VPS Nginx

LARAVEL_OCR_DRIVER=tesseract
TESSERACT_LANGUAGE=ind+eng
TESSERACT_TIMEOUT=60
# Host Windows (XAMPP) — di VPS/Docker otomatis jadi /usr/bin/tesseract & /usr/bin/pdftoppm
TESSERACT_BINARY=/usr/bin/tesseract
POPPLER_BINARY=/usr/bin/pdftoppm
```

Akun default seeder `database/seeders/AdminUserSeeder.php`:

```
admin@dpmptsp.pidie.go.id / password
```

Ubah via `.env` `ADMIN_EMAIL` / `ADMIN_PASSWORD` lalu `php artisan db:seed --force`.

---

## Operasional

```bash
# Docker
docker compose logs -f app
docker compose exec app php artisan route:list --path=sanksi
docker compose exec app tesseract --list-langs | grep -E 'ind|eng'
docker compose exec app cat storage/logs/laravel.log | tail -n 80

# VPS
tail -n 80 /var/www/spi-dpmptsp/storage/logs/laravel.log
php artisan route:list --path=sanksi
tesseract --list-langs | grep -E 'ind|eng'
pdftoppm -v 2>&1 | head -n 1
```

---

## Lisensi

MIT — Laravel. Data sanksi milik DPMPTSP Kabupaten Pidie.
