# Konseling Kampus (Laravel 12 + MySQL)

Folder ini berisi **file aplikasi saja**, bukan seluruh kerangka Laravel. Tempelkan ke proyek Laravel 12 yang baru.

## Pasang

```bash
composer create-project laravel/laravel konseling
cd konseling
# salin isi folder ini ke dalam proyek; timpa bootstrap/app.php dan app/Models/User.php bila diminta
```

Atur `.env`:

```
APP_LOCALE=id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=konseling
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `konseling` di MySQL, lalu:

```bash
php artisan migrate --seed
php artisan serve
```

## Akun uji coba (kata sandi semua: `password`)

| Peran | Email |
|---|---|
| Admin | admin@konseling.test |
| Mahasiswa | mahasiswa@konseling.test |
| Konselor | konselor@konseling.test |

Akun mahasiswa dan konselor hanya dibuat saat `APP_ENV=local`. **Ganti kata sandi admin sebelum dipakai sungguhan.**

## Catatan

- Tampilan memakai Tailwind lewat CDN, jadi butuh internet dan tidak perlu `npm`. Untuk produksi, pindahkan ke Vite.
- Slot konselor berulang mingguan. Slot dianggap terisi bila ada booking berstatus *menunggu* atau *dikonfirmasi*; ditolak atau dibatalkan membebaskan slot lagi.
- Sesi paling awal bisa dipesan adalah besok (`ScheduleService::earliestDate`).
- Skor kuesioner 0-30: rendah 0-9, sedang 10-19, tinggi 20-30. Pertanyaan ke-10 yang dijawab selain 0 menandai booking sebagai prioritas. Skor ini bukan diagnosis; teks pertanyaan sebaiknya ditinjau psikolog kampus sebelum dipakai.
- Pengiriman email, video call, dan notifikasi belum ada.
