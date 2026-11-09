## Accurate Explorer

Accurate Explorer adalah aplikasi Laravel 13 untuk membaca data Accurate Online melalui
proxy server-side. Frontend menggunakan Blade Laravel dan JavaScript ringan; token
Accurate tidak pernah dikirim ke browser.

### Development setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan serve
```

Atur `ACCURATE_ACCESS_TOKEN` dan `ACCURATE_SIGNATURE_SECRET` di `.env`.
Untuk token berformat `aat.`, token sudah terikat ke database sehingga
`ACCURATE_DEFAULT_DB_ID` tidak diperlukan dan `open-db.do` akan dilewati.
Request bisnis menggunakan `ACCURATE_API_TOKEN_URL` (default:
`https://zeus.accurate.id`). Token OAuth biasa tetap memerlukan
`ACCURATE_DEFAULT_DB_ID` untuk membuka sesi database.
Alur koneksi menggunakan `db-list.do`, `open-db.do`, `Authorization: Bearer`, dan
`X-Session-ID` dari sesi database Accurate. Detail endpoint provider dapat disesuaikan
di [config/accurate.php](config/accurate.php).

### GitHub Codespaces

Project ini mendukung debugging melalui GitHub Codespaces. Dev Container akan
menjalankan build asset Vite dan mengaktifkan `CODESPACES=true`. URL asset Laravel
kemudian mengikuti host forwarded request Codespaces, bukan `localhost`, sehingga
CSS dan JavaScript tetap termuat dari browser.

Jika container atau server sudah berjalan sebelum perubahan konfigurasi, jalankan:

```bash
npm install
npm run build
php artisan optimize:clear
php artisan serve --host=0.0.0.0
```

Pastikan port `8000` berstatus **Forwarded** atau **Public**, lalu buka URL port
tersebut. Lakukan hard refresh dengan `Ctrl+Shift+R` bila browser masih memakai
HTML atau asset lama.

Route awal:

- `/` - dashboard
- `/accurate/databases` - database yang dapat diakses
- `/accurate/purchase-order/list` - daftar Purchase Order
- `/accurate/inbound` - tabel inbound berbasis payload Purchase Order
- `/accurate/inbound-pr` - tabel inbound (PR) berbasis Purchase Order, dengan tanggal request dari detail Purchase Requisition dan qty delivery dari detail Receive Item
- `/accurate/item-transfer/list` - daftar Internal Transfer

Setiap halaman menampilkan API Inspector yang hanya menyimpan envelope request aktif
di browser dan tidak menampilkan credential.

### Testing

```bash
php artisan test
npm run build
```

---

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

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
