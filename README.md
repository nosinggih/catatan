# Catatan

Aplikasi pencatat kegiatan rutin: catat dengan satu tap, tahu kapan terakhir melakukannya dan kapan perlu melakukannya lagi.

Web mobile-first (PWA). Laravel 12 sebagai API, Vue 3 sebagai SPA, dalam satu repo dan satu domain.

## Menjalankan di lokal

Butuh PHP 8.2+ (dengan ekstensi `gd` dan `gmp` atau `bcmath`), Composer, dan Node 20+.

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

## Pengingat (notifikasi)

Pengingat dikirim lewat Web Push, gratis tanpa layanan berbayar. Buat kunci VAPID sekali saja; perintah ini mengisi `VAPID_PUBLIC_KEY` dan `VAPID_PRIVATE_KEY` di `.env`:

```bash
php artisan webpush:vapid
```

Isi juga `VAPID_SUBJECT` dengan email admin, misalnya `mailto:admin@sinarsurya.com`. Jangan ganti kunci setelah ada pengguna, karena semua langganan notifikasi lama jadi tidak berlaku.

Perintah `catatan:send-reminders` jalan tiap jam lewat scheduler Laravel dan hanya mengirim pukul 07.00–20.59 WIB. Di server, tambahkan cron ini:

```
* * * * * cd /path/ke/catatan && php artisan schedule:run >> /dev/null 2>&1
```

Untuk mencoba di lokal tanpa menunggu jadwal: `php artisan catatan:send-reminders --force` (abaikan jam tenang). Foto tersimpan di `storage/app/private/photos`, jadi ikutkan folder itu saat backup.

## Mencoba di HP

PWA (install ke layar utama) dan notifikasi butuh HTTPS. Sebelum ada hosting, pakai tunnel gratis, misalnya [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/do-more-with-tunnels/trycloudflare/):

```bash
cloudflared tunnel --url http://localhost:8000
```

Set `APP_URL` ke URL tunnel, jalankan `npm run build`, lalu buka URL itu di HP. Untuk login Google lewat tunnel, tambahkan juga redirect URI tunnel di Google Cloud Console.

Di iPhone, notifikasi hanya muncul kalau Catatan sudah dipasang lewat Safari: Bagikan → Tambah ke Layar Utama (butuh iOS 16.4+). Mode offline bisa dicoba dengan mode pesawat: catatan yang di-tap tersimpan di HP dan terkirim otomatis saat online lagi.

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
| Versi teks persetujuan, template kegiatan, ikon otomatis | `config/catatan.php` |
| Model data (rumah, kegiatan, catatan) | `app/Models/` |
| Antrean offline dan cache data | `resources/js/stores/activities.js`, `resources/js/lib/storage.js` |
| Pengingat: perintah, notifikasi, jadwal | `app/Console/Commands/SendReminders.php`, `app/Notifications/ActivityDue.php`, `routes/console.php` |
