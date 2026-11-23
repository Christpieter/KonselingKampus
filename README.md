<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Asisten chat NVIDIA NIM

Halaman `Teman bicara` hanya tersedia untuk mahasiswa yang sudah masuk. API NIM memakai format chat completions yang kompatibel dengan OpenAI. Untuk pengembangan lokal, isi `.env` Laravel seperti ini:

```env
NVIDIA_NIM_CHAT_ENABLED=true
NVIDIA_NIM_BASE_URL=https://integrate.api.nvidia.com/v1
NVIDIA_API_KEY=your-server-side-key
NVIDIA_NIM_MODEL=openai/gpt-oss-20b
```

Buat API key di NVIDIA API Catalog dan simpan sebagai environment variable server. Jangan gunakan awalan `VITE_` atau kirim key ke browser. Ganti model dengan model ID yang tersedia di akun/API Catalog Anda. Setelah mengubah environment lokal, jalankan `php artisan config:clear`.

Pesan dikirim ke NVIDIA NIM untuk menghasilkan balasan. Laravel tidak menyimpan transkrip; riwayat percakapan hanya disimpan sementara di browser selama halaman terbuka. Penyedia AI dapat memiliki kebijakan logging atau retensi sendiri. Beri tahu pengguna dan tinjau kebijakan privasi sebelum peluncuran. Teman bicara bukan pengganti konselor maupun layanan darurat.

## Rekap sesi konseling melalui n8n

Konselor dapat memilih **Rekap data** pada sesi yang sudah selesai. Laravel mengirim data sesi, identitas mahasiswa dan konselor, catatan privat konselor, serta skor/jawaban triage ke webhook n8n. Atur URL webhook dan secret di environment Laravel:

```env
N8N_COUNSELING_REPORT_WEBHOOK_URL=https://n8n.example/webhook/counseling-report
N8N_WEBHOOK_SECRET=use-a-long-random-secret
```

Workflow n8n harus menerima JSON berisi `report_id`, `callback_token`, `callback_url`, dan `report`. Workflow membuat PDF, mengubah isi file PDF menjadi Base64, lalu melakukan HTTP POST ke `callback_url` dengan header `X-Webhook-Secret` yang sama. Body callback:

```json
{
  "report_id": 123,
  "callback_token": "token-dari-permintaan",
  "pdf_base64": "JVBERi0xLjQ..."
}
```

Laravel memvalidasi secret, token per permintaan, dan signature PDF (maksimal 5 MB); file disimpan di disk privat. PDF hanya dapat diunduh oleh konselor pemilik booking melalui panel. Workflow harus mengembalikan respons sukses ketika permintaan diterima; portal akan memantau callback dan menampilkan link unduh setelah file tersedia. Jalankan `php artisan migrate` dan `php artisan config:clear` setelah menyiapkan integrasi.

Data yang dikirim mencakup informasi sensitif konseling. Batasi akses workflow dan penyimpanan n8n, jangan mencatat payload atau file ke log yang bisa diakses umum, dan gunakan koneksi HTTPS.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
