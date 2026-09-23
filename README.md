# WOW SAPI — Laravel 12

Dashboard admin/peternak + REST API `/api/v1` (Flutter) + gateway ke FastAPI AI.

## Prasyarat

- PHP 8.2+, Composer, Node.js
- MySQL/MariaDB database **`wowsapi`**
- FastAPI AI di `http://127.0.0.1:8003` (opsional; dashboard tetap jalan jika AI mati)

## Menjalankan

```powershell
cd "d:\WOW SAPI\wow-sapi"
copy .env.example .env   # jika belum ada
php artisan key:generate
# Atur DB_USERNAME / DB_PASSWORD di .env bila bukan XAMPP root kosong
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Buka http://127.0.0.1:8000

Untuk notifikasi vaksin (H-7 / H-1 / Hari H):

```powershell
php artisan schedule:work
```

## Akun demo

| Peran | Email | Password |
|---|---|---|
| Admin | admin@wowsapi.id | password |
| Peternak | budi@wowsapi.id | password |

Akun seed sudah terverifikasi: **masuk cukup email + kata sandi, tanpa OTP**.

OTP 6 digit hanya untuk:
1. **Daftar** — verifikasi email sekali
2. **Lupa password** — kode dikirim ke email, lalu buat kata sandi baru

### Email (Gmail SMTP)

Isi `MAIL_PASSWORD` di `.env` dengan App Password Gmail. Kosongkan dulu jika belum ada; pengiriman email akan gagal sampai diisi, alur aplikasi tetap jalan.

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=dcloth377@gmail.com
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=dcloth377@gmail.com
MAIL_FROM_NAME="WOW SAPI"
```

## FastAPI

```
WOWSAPI_AI_URL=http://127.0.0.1:8003
WOWSAPI_AI_TIMEOUT=60
FASTAPI_URL=http://127.0.0.1:8003
WOWSAPI_AI_HEALTH_PATH=/health
WOWSAPI_AI_WEIGHT_PATH=/predict/weight
WOWSAPI_AI_LUMPY_PATH=/predict/lumpy
WOWSAPI_AI_ANALYZE_PATH=/predict/all
```

Endpoint gabungan FastAPI yang berjalan saat ini adalah `/predict/all` (bukan `/predict/analyze`). Path bisa diubah lewat `.env`.
