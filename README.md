# SPI DPMPTSP Kabupaten Pidie

Sistem Pengawasan Kepatuhan — **Daftar List Sanksi Pencabutan** & **Usulan Pencabutan Perizinan Berusaha** (OCR PDF, validasi NIB, cetak SP1 & Rekap).

> Deployment **Linux + Docker only** — SQLite, port `8050` → container `web:80` (nginx). Tanpa XAMPP/MySQL.

---

## Stack

- **Backend** Laravel 12.69 (`php 8.2`), Inertia 3.3, `dompdf/dompdf`
- **Frontend** Vue 3.5 + Vite 7 + Tailwind 4
- **DB** SQLite `database/database.sqlite` (volume `app_db`)
- **OCR** `tesseract-ocr` (`ind`+`eng`) + `poppler-utils` (`pdftoppm`) di image
- **Web** `nginx:1.27-alpine` → `php:8.2-fpm-bookworm`

---

## Prasyarat (Linux)

- Docker Engine + Compose v2 (`docker compose version`)
- Git, `curl`
- Port `8050` bebas (cek `ss -tulpn | grep 8050` / `docker ps`)

---

## Deploy — Linux + Docker

### 1. Clone & env

```bash
git clone https://github.com/firdaus-rx/SPI-DPMPTSP.git spi-dpmptsp
cd spi-dpmptsp
cp .env.example .env
nano .env
```

Wajib isi di `.env`:

```env
APP_NAME="SPI DPMPTSP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https:/dpmptsp.pidie.pipay.id/   # ganti ke domain Cloudflare Tunnel kamu
APP_PORT=8050

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

# OCR — di container otomatis di-override ke Linux, biarkan saja
LARAVEL_OCR_DRIVER=tesseract
TESSERACT_LANGUAGE=ind+eng
TESSERACT_TIMEOUT=60
```

`.env.example` sudah berisi `APP_PORT=8050` dan path Windows (`C:\...`) — di container ditimpa jadi `/usr/bin/tesseract` & `/usr/bin/pdftoppm` oleh `docker-compose.yml` + `docker/entrypoint.sh`.

### 2. Build & up

```bash
docker compose up --build -d
docker compose ps
docker compose logs -f app
```

Tunggu `entrypoint` sampai:

```
[entrypoint] APP_KEY OK: base64:...
[entrypoint] SQLite DB: /var/www/html/database/database.sqlite (exists: yes)
[entrypoint] Cek binary OCR...
tesseract 5.x
pdftoppm version 22.x
ind
eng
[entrypoint] Migrate & seed...
```

### 3. Buka

- `http://SERVER_IP:8050` — welcome `GET /` (`resources/views/guest/welcome.blade.php`)
- `http://SERVER_IP:8050/login` — `admin@dpmptsp.pidie.go.id / password`

```
Seeder: database/seeders/AdminUserSeeder.php
User casts password => hashed — assign plain di seeder
```

### 4. Update

```bash
git pull origin main
docker compose up --build -d
docker compose exec app php artisan config:clear
```

### 5. Hentikan / reset

```bash
docker compose down              # stop, volume tetap
docker compose down -v           # hapus volume app_db (reset DB)
docker compose exec app php artisan migrate:fresh --seed --force
```

---

## Port — `8050:80`

`docker-compose.yml`:

```yaml
services:
  web:
    image: nginx:1.27-alpine
    ports:
      - "${APP_PORT:-8050}:80"   # host:8050 → container web:80 (expose tetap 80)
    depends_on:
      app:
        condition: service_started
  app:
    build: .
    expose: [9000]               # php-fpm, tidak dipublish ke host
    environment:
      TESSERACT_BINARY: "/usr/bin/tesseract"
      POPPLER_BINARY: "/usr/bin/pdftoppm"
```

| Akses | URL |
|-------|-----|
| Lokal server | `http://localhost:8050` |
| LAN | `http://SERVER_IP:8050` |
| Cloudflared di host | `--url http://localhost:8050` |
| Cloudflared di compose | `service: http://web:80` (satu network `spi`) |

> Jangan pointing ke `app:9000` (FastCGI) atau `3306` (tidak ada MySQL).

Ganti port: edit `.env` `APP_PORT=8051` → `docker compose up -d` (recreate `web`).

Cek port bentrok:

```bash
ss -tulpn | grep -E '8050|8020|8010|3000'
docker ps --format '{{.Names}} {{.Ports}}'
```

---

## Cloudflare Tunnel

### Quick tunnel (host)

```bash
cloudflared tunnel --url http://localhost:8050
# -> https://xxx.trycloudflare.com
```

### Tunnel terdaftar

`~/.cloudflared/config.yml`:

```yaml
tunnel: <TUNNEL_ID>
credentials-file: /home/<user>/.cloudflared/<TUNNEL_ID>.json
ingress:
  - hostname: spi.example.com
    service: http://localhost:8050
  - service: http_status:404
```

```bash
cloudflared tunnel run spi
```

Env wajib:

```env
APP_URL=https://spi.example.com
```

Lalu `docker compose exec app php artisan config:clear`.

### Cloudflared sebagai service Docker (opsional)

Tambah ke `docker-compose.yml`:

```yaml
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: spi-cloudflared
    command: tunnel --no-autoupdate run --token <TUNNEL_TOKEN>
    depends_on: [web]
    networks: [spi]
    restart: unless-stopped
```

Pointing jadi `service: http://web:80` (internal).

---

## Operasional

```bash
docker compose logs -f app          # laravel log
docker compose logs -f web          # nginx
docker compose exec app php artisan route:list --path=sanksi
docker compose exec app php artisan tinker
docker compose exec app cat storage/logs/laravel.log | tail -n 100
docker compose exec app ls -lh storage/app/private/import
```

Backup DB (volume):

```bash
docker run --rm -v spi-dpmptsp_app_db:/vol -v $(pwd):/backup alpine tar czf /backup/db-$(date +%F).tgz -C /vol .
```

---

## OCR

- Image: `tesseract-ocr`, `tesseract-ocr-ind`, `tesseract-ocr-eng`, `poppler-utils` (`Dockerfile:16`)
- Config: `config/ocr.php` — `binary` env, `language=ind+eng`, `dpi=300`
- Service: `app/Services/SanksiAdministratif/OcrSanksiAdministratifService.php` (`parseOssLayout` — `v 2 NAMA`, NIB split, `No.1]`)

Verifikasi di container:

```bash
docker compose exec app tesseract --list-langs | grep -E 'ind|eng'
docker compose exec app pdftoppm -v 2>&1 | head -n 1
```

---

## Troubleshooting

| Error | Solusi |
|-------|--------|
| `No application encryption key` | `docker/entrypoint.sh` auto `php artisan key:generate --force` + fallback `openssl rand -base64 32` (butuh `file`/`openssl` di `Dockerfile:16`). Jika masih kosong: `docker compose exec app php artisan key:generate --force && docker compose restart app` |
| `Package 'sqlite3' not found` | Butuh `libsqlite3-dev` + `pkg-config` — sudah ada di `Dockerfile:16`. Rebuild: `docker compose build --no-cache app` |
| `port is already allocated` | `APP_PORT` bentrok — ganti `8050` → `8051` di `.env`, `docker compose up -d` |
| `permission denied` `vendor/composer/tmp-*.zip` | `chmod -R 775 storage bootstrap/cache` sudah di `Dockerfile:49` + `entrypoint.sh:8`; di host: `icacls`/`chmod` jika mount Windows |
| Build `vite build skipped` | `public/build/manifest.json` belum ada — entrypoint akan `npm ci && npm run build` jika `npm` ada di image; sebaliknya build di host dulu lalu `docker compose build` |

---

## Lisensi

MIT — Laravel. Data sanksi milik DPMPTSP Kabupaten Pidie.
