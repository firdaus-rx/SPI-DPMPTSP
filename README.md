# SPI DPMPTSP Kabupaten Pidie

Sistem Pengawasan Kepatuhan terintegrasi — kelola **Daftar List Sanksi Pencabutan** & **Usulan Pencabutan Perizinan Berusaha** dengan OCR PDF, validasi NIB, dan cetak SP1/Rekap langsung dari hasil impor.

- **Welcome Guest** (`/`) dari `public/guest/index.html` — `resources/views/guest/welcome.blade.php:1` (assets tetap `public/guest/assets/*`)
- **Dashboard** analitik — KPI, sebaran risiko, skala, penanaman modal, top kecamatan/kabupaten, data terbaru
- **Auth** — hanya login, tanpa register (`app/Http/Controllers/Auth/LoginController.php:1`)
- **OCR** — Tesseract `ind+eng` + Poppler `pdftoppm` (`config/ocr.php:1`)
- **Cetak** — SP1 per data/massal checklist + Rekap 6 kolom landscape (`app/Http/Controllers/SanksiAdministratif/Sp1Controller.php:1`, `RekapController.php:1`) — `dompdf/dompdf` stream inline

## Fitur Utama

| Modul | Route | Keterangan |
|-------|-------|------------|
| Dashboard | `GET /dashboard` | Ringkasan eksekutif pengawasan & usulan |
| Daftar List Sanksi Pencabutan | `resource /pengawasan` | Tabel `pengawasan` — risiko, status sanksi |
| Import Pengawasan | `/pengawasan/import` | OCR `OcrPengawasanService` |
| Usulan Pencabutan PB | `resource /sanksi-administratif` | Tabel `sanksi_administratif` — `no, nib, alamat nested, penanaman_modal, skala` |
| Import Usulan | `/sanksi-administratif/import` | OCR `OcrSanksiAdministratifService` — handle `v 2 NAMA` + NIB split + `No.1]` |
| Cetak SP1 | `/{id}/sp1`, `/sp1/cetak?ids=1,2` | `template/sp1.blade.php:1` — stream PDF F4 215×330mm |
| Rekap | `/rekap/cetak?ids=&search=&skala&kecamatan` | `template/rekap-sanksi.blade.php:1` — landscape 330×215mm, 6 kolom |
| Auth | `GET /login`, `POST /login`, `POST /logout` | Tanpa register — seeder saja |

Menu & breadcrumb single source: `resources/js/Components/Layout/menu.js:1` (`NAV_SECTIONS`, `breadcrumbs()`)

## Stack

- **Backend:** Laravel 12.69 (`php ^8.2`), Inertia 3.3, `dompdf/dompdf *`, `mayaram/laravel-ocr`
- **Frontend:** Vue 3.5 + Inertia Vue3 3.7 + Vite 7 + Tailwind 4 + Lucide
- **DB:** SQLite (`database/database.sqlite`) — tanpa MySQL
- **PDF:** `dompdf` stream inline (`Content-Disposition: inline`) + `?download=1` / `?html=1`
- **OCR:** `tesseract` + `poppler-utils` (`pdftoppm -r 300 -png`)
- **Proxy:** `nginx:1.27-alpine` → `php:8.2-fpm-bookworm`

## Struktur Penting

```
Dockerfile                         # multi-stage node20 + php8.2-fpm (tesseract ind+eng + poppler + libsqlite3-dev)
docker-compose.yml                 # app + web (nginx), SQLite, APP_PORT 8050:80
docker/php/local.ini               # upload 25M, memory 512M
docker/nginx/default.conf          # try_files + fastcgi_pass app:9000
docker/entrypoint.sh               # key:generate, migrate --force, db:seed, storage:link, paksa binary Linux
config/ocr.php                     # driver tesseract, binary, language ind+eng, dpi 300
app/Services/SanksiAdministratif/  # OcrSanksiAdministratifService (parseOssLayout)
resources/views/template/sp1.blade.php
resources/views/template/rekap-sanksi.blade.php
resources/views/guest/welcome.blade.php  # GET / welcome (auth → dashboard)
resources/js/Pages/Dashboard.vue
resources/js/Pages/SanksiAdministratif/* # Index (checklist No 1..) + ImportPdf + Show (Cetak SP1)
```

## Instalasi — Lokal (XAMPP, Host Windows)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed          # admin@dpmptsp.pidie.go.id / password
npm install
npm run build                # atau npm run dev
php artisan serve            # http://localhost:8000
```

`.env.example` host Windows sudah berisi:

```env
LARAVEL_OCR_DRIVER=tesseract
TESSERACT_BINARY="C:\Program Files\Tesseract-OCR\tesseract.exe"
TESSERACT_LANGUAGE=ind+eng
TESSERACT_TIMEOUT=60
POPPLER_BINARY="C:\Users\ASUS\AppData\Local\Microsoft\WinGet\Packages\...\poppler-25.07.0\Library\bin\pdftoppm.exe"
```

## Instalasi — Docker (SQLite, tanpa MySQL)

```powershell
Copy-Item .env.example .env
# APP_KEY akan diisi otomatis oleh entrypoint jika kosong

docker compose up --build -d
docker compose logs -f app

# Buka:
# http://localhost:8050          -> welcome (guest) / dashboard (auth)
# http://localhost:8050/login    -> admin@dpmptsp.pidie.go.id / password

# Hentikan:
docker compose down

# Reset DB (di dalam container):
docker compose exec app php artisan migrate:fresh --seed --force
```

`Dockerfile:16` memasang `tesseract-ocr tesseract-ocr-eng tesseract-ocr-ind poppler-utils libsqlite3-dev pkg-config` + `docker-php-ext-install pdo pdo_sqlite gd zip` — gagal build `Package 'sqlite3' not found` teratasi. `docker/entrypoint.sh:28` memaksa `TESSERACT_BINARY=/usr/bin/tesseract` `POPPLER_BINARY=/usr/bin/pdftoppm` di container (override path Windows).

Volume `docker-compose.yml:22`:
- `app_storage:/var/www/html/storage` — upload `storage/app/private/import`
- `app_db:/var/www/html/database` — `database/database.sqlite` persisten

## Konfigurasi OCR

`config/ocr.php:24`:

```php
'tesseract' => ['binary' => env('TESSERACT_BINARY', '/usr/bin/tesseract'), 'language' => env('TESSERACT_LANGUAGE', 'ind+eng'), 'timeout' => 60],
'poppler'   => ['binary' => env('POPPLER_BINARY', '/usr/bin/pdftoppm'), 'dpi' => 300],
```

- Host: path Windows (di atas)
- Docker: `docker-compose.yml:12` & `docker/entrypoint.sh:28` override ke `/usr/bin/tesseract` & `/usr/bin/pdftoppm` (Linux)

Pipeline sanksi `OcrSanksiAdministratifService::parseOssLayout()` — tangkap `v 2 BUMG...`, NIB split `91203009508 + 31 → 9120300950831`, `No.1] → No.11`, blank lines `Kelurahan:` → value.

Verifikasi tanpa PDF:

```
php artisan tinker
>>> app(App\Services\SanksiAdministratif\OcrSanksiAdministratifService::class)->parse($textDariLog, 'file.pdf')
```

## Cloudflare Tunnel — Port Pointing

`docker-compose.yml:36` service **web (nginx)** memetakan:

```
ports:
  - "${APP_PORT:-8050}:80"
```

| Skenario `cloudflared` | Pointing `service` | Keterangan |
|------------------------|--------------------|------------|
| **Di host (Windows) — paling umum** | `http://localhost:8050` | `cloudflared` di host meneruskan ke `web` via port host `APP_PORT` |
| **Di dalam Docker (compose)** | `http://web:80` | `cloudflared` satu network `spi` → langsung ke container `web:80` (bukan `app:9000` php-fpm) |
| **Reverse proxy lain (Caddy/Nginx host)** | `http://localhost:8050` atau `http://127.0.0.1:8050` | Sama — target `web`, bukan `app` |

> Jangan pointing ke `app:9000` (php-fpm FastCGI) dan jangan ke `3306/3307` (tidak ada MySQL).

### Quick tunnel (host)

```powershell
cloudflared tunnel --url http://localhost:8050
# akan dapat https://xxx.trycloudflare.com → localhost:8050 → web:80 → app:9000
```

### Config file `config.yml` (host)

```yaml
tunnel: <TUNNEL_ID>
credentials-file: C:\Users\ASUS\.cloudflared\<TUNNEL_ID>.json

ingress:
  - hostname: spi.example.com
    service: http://localhost:8050
  - service: http_status:404
```

Jalankan: `cloudflared tunnel run spi`

### Cloudflared sebagai service Docker (opsional)

Tambah ke `docker-compose.yml`:

```yaml
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: spi-cloudflared
    command: tunnel --no-autoupdate run --token <TUNNEL_TOKEN>
    # atau config file: mount C:\...\config.yml
    depends_on:
      web:
        condition: service_started
    networks:
      - spi
    restart: unless-stopped
```

Jika pakai token, pointing tetap ke `http://web:80` (internal), bukan `localhost`.

### Env penting untuk tunnel

```env
APP_URL=https://spi.example.com   # samakan dengan hostname tunnel agar URL & asset benar
APP_PORT=8050                      # ganti jika bentrok, lalu pointing ikut ganti localhost:<APP_PORT>
```

Cek route setelah `APP_URL` diubah: `php artisan config:clear && php artisan route:list --path=sanksi`

## Akun Default

Seeder `database/seeders/AdminUserSeeder.php:1`:

```
admin@dpmptsp.pidie.go.id / password
```

`User.php:44` `casts: ['password' => 'hashed']` — seeder assign plain password, model hash otomatis. Ubah via `.env`:

```env
ADMIN_EMAIL=admin@dpmptsp.pidie.go.id
ADMIN_PASSWORD=password
```

Tanpa register UI — tambah akun edit `AdminUserSeeder` array.

## Alur Cetak

- **SP1 per data:** `Show` → `Cetak SP1` → `GET /sanksi-administratif/{id}/sp1` → `Sp1Controller.php:17` stream `inline; filename="SP1-<nib>.pdf"` — preview PDF native (Print & Download). `?download=1` → `attachment`, `?html=1` → preview HTML `window.print()`.
- **SP1 massal:** `Index` checklist `No 1..` (`sanksi.from + index`) → `Cetak SP1 (n)` → `?ids=1,2,5` → `Sp1Controller.php:24` `whereIn(ids)` stream `SP1-massal-N-data.pdf`. Header checkbox `allChecked` `Index.vue:103`.
- **Rekap:** `Cetak Rekap` → `GET /sanksi-administratif/rekap/cetak?ids=&search=&skala_usaha=&kecamatan` → `RekapController.php:1` — jika ada `ids` pakai terpilih, else filter. Template `rekap-sanksi.blade.php:1` landscape, 6 kolom `No | Pelaku Usaha | NIB | Penanaman Modal | Skala | Lokasi`, tanpa warna/badge — border hitam polos.

## Catatan Docker

- SQLite saja — tidak ada `mysql:8.0` service (di arsip `docker-compose.mysql.yml.bak` bila perlu).
- `Dockerfile:29` `tesseract --list-langs | grep ind|eng` memastikan tessdata siap.
- `docker/php/local.ini:1` `upload_max_filesize 25M` sinkron `ImportPdfController` `max:20480`.
- `docker/nginx/default.conf:1` `client_max_body_size 25m`, `fastcgi_pass app:9000`.

## Lisensi

MIT — Laravel framework. Konten & data sanksi milik DPMPTSP Kabupaten Pidie.
