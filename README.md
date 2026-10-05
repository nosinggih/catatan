# Catatan

Aplikasi pencatat kegiatan rutin: catat dengan satu tap, tahu kapan terakhir melakukannya dan kapan perlu melakukannya lagi.

Web mobile-first (PWA). Laravel 12 sebagai API, Vue 3 sebagai SPA, dalam satu repo dan satu domain.

## Menjalankan di lokal

Butuh PHP 8.2+, Composer, dan Node 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Untuk mencoba tanpa menyiapkan Google, nyalakan login dev di `.env`:

```
CATATAN_DEV_LOGIN=true
```

Lalu jalankan server, queue, log, dan Vite sekaligus:

```bash
composer run dev
```

Buka http://localhost:8000. Tombol "Masuk tanpa Google (dev)" hanya muncul kalau `APP_ENV=local` dan `CATATAN_DEV_LOGIN=true`.

## Login Google

1. Buka [Google Cloud Console](https://console.cloud.google.com/apis/credentials), buat **OAuth client ID** tipe *Web application*.
2. Tambahkan *Authorized redirect URI*: `http://localhost:8000/auth/google/callback` (dan nanti URL produksi, misalnya `https://notes.sinarsurya.com/auth/google/callback`).
3. Isi `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env`.

## Mencoba di HP

PWA (install ke layar utama) dan notifikasi butuh HTTPS. Sebelum ada hosting, pakai tunnel gratis, misalnya [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/do-more-with-tunnels/trycloudflare/):

```bash
cloudflared tunnel --url http://localhost:8000
```

Set `APP_URL` ke URL tunnel, jalankan `npm run build`, lalu buka URL itu di HP. Untuk login Google lewat tunnel, tambahkan juga redirect URI tunnel di Google Cloud Console.

## Tes

```bash
php artisan test
vendor/bin/pint --test
```

## Struktur singkat

| Bagian | Lokasi |
| --- | --- |
| Route API (JSON, butuh login) | `routes/api.php` |
| Route login Google, logout, dan SPA | `routes/web.php` |
| Halaman Vue | `resources/js/pages/` |
| Router dan guard login/persetujuan | `resources/js/router.js` |
| Manifest, service worker, ikon | `public/manifest.webmanifest`, `public/sw.js`, `public/icons/` |
| Versi teks persetujuan | `config/catatan.php` (`terms_version`) |
