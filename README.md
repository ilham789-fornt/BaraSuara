# Ruang BaraSuara

Website fanbase dan sistem pemesanan tiket konser Barasuara yang dikembangkan sebagai proyek pembelajaran/pengembangan web. Aplikasi ini menggabungkan halaman informasi band, katalog musik, informasi tur, akun pengguna, pemesanan tiket, serta dashboard administrasi.

> **Catatan:** Repository ini merupakan proyek pengembangan. Data konser, harga, kuota, dan akun contoh yang terdapat pada database adalah data demonstrasi dan harus diverifikasi atau diganti sebelum aplikasi digunakan untuk transaksi maupun publikasi. Proyek ini tidak menyatakan afiliasi resmi dengan Barasuara atau penyelenggara acara.

## Fitur

### Halaman publik
- **Beranda** — menampilkan konten berita, album, dan informasi tur yang tersedia di database.
- **Tentang** — halaman informasi mengenai Barasuara.
- **Musik** — menampilkan katalog album dan daftar lagu.
- **Tur & Tiket** — menampilkan jadwal acara, kategori tiket, harga, periode penjualan, dan informasi kuota.
- **Kontak** — formulir pengiriman pesan atau pertanyaan.

### Akun dan pemesanan
- Registrasi, login, dan logout pengguna.
- Penyimpanan password menggunakan mekanisme hashing PHP.
- Pemesanan tiket untuk pengguna yang sudah login.
- Riwayat pesanan dan halaman detail/status pesanan.
- Pembuatan kode tiket individual dan tampilan e-ticket.
- Pemeriksaan tiket/check-in melalui halaman pemindaian panitia.

### Dashboard administrasi
Dashboard menyediakan pengelolaan data sesuai hak akses, termasuk data pengguna, musik, berita, tur, pesanan, dan pesan masuk. Halaman pemindaian tiket disediakan untuk petugas gate scanner.

## Hak akses pengguna

| Role | Hak akses utama |
|---|---|
| Guest | Melihat halaman publik. Perlu login untuk melakukan pemesanan dan mengirim pesan melalui formulir kontak. |
| `non_member` | Mengelola akun sendiri, melakukan pemesanan, dan melihat pesanan miliknya. |
| `member` | Mengelola akun sendiri, melakukan pemesanan, dan melihat pesanan miliknya. |
| `admin` | Mengakses dashboard dan pengelolaan data administrasi. |
| `gate_scanner` | Mengakses halaman pemeriksaan/check-in tiket. |

Registrasi baru secara default membuat akun dengan role `non_member`. Pada implementasi saat ini, role `member` dan `non_member` menggunakan alur pengguna dasar yang sama; fitur atau keuntungan khusus member belum menjadi bagian dari fungsi yang terverifikasi.

## Teknologi

- **Backend:** PHP
- **Database:** MySQL atau MariaDB
- **Database access:** PDO dengan prepared statements
- **Frontend:** HTML, CSS, dan JavaScript
- **Admin dashboard:** aset AdminLTE yang disimpan di dalam repository
- **Local development:** dapat dijalankan menggunakan Laragon atau XAMPP

## Struktur proyek

```text
BaraSuara/
├── admin/                    # Dashboard admin dan gate scanner
│   ├── includes/             # Autentikasi dan layout admin
│   └── dist/                 # Aset dashboard
├── config/
│   └── database.php          # Konfigurasi koneksi database
├── images/                   # Gambar band dan artwork
├── includes/                 # Bootstrap, navigasi, layout, dan ticketing
├── lib/                      # JavaScript interaksi halaman dan pemesanan
├── style/
│   └── style.css             # Styling utama website
├── index.php                 # Beranda
├── about.php                 # Halaman tentang
├── music.php                 # Katalog musik
├── tour.php                  # Tur dan tiket
├── contact.php               # Formulir kontak
├── register.php              # Registrasi
├── login.php                 # Login
├── logout.php                # Logout
├── order.php                 # Form pemesanan
├── process-order.php         # Proses pembuatan pesanan
├── my-orders.php             # Riwayat pesanan pengguna
├── order-status.php          # Detail/status pesanan dan e-ticket
├── order-action.php          # Aksi lanjutan pesanan
├── database.sql              # Skema dan data awal database
├── database_update_ticketing.sql
├── crud_operations.sql       # Contoh operasi CRUD untuk pengujian
└── README.md
```

## Struktur database

Database yang digunakan aplikasi bernama `db_barasuara` dan terdiri dari 10 tabel utama:

| Tabel | Fungsi |
|---|---|
| `admins` | Akun administrator dan petugas gate scanner. |
| `users` | Akun pengguna/member dan non-member. |
| `tours` | Data jadwal tur atau acara. |
| `ticket_tiers` | Kategori/gelombang tiket, harga, kuota, dan periode penjualan. |
| `orders` | Data pesanan dan status pembayaran. |
| `order_tickets` | Data tiket individual, kode tiket, dan status check-in. |
| `albums` | Data album musik. |
| `tracks` | Daftar lagu yang terkait dengan album. |
| `news_posts` | Berita atau informasi yang ditampilkan di website. |
| `contact_inquiries` | Pesan yang dikirim melalui formulir kontak. |

Relasi utama menghubungkan tur dengan kategori tiket, pengguna dengan pesanan, pesanan dengan tiket, album dengan lagu, dan admin dengan konten berita atau aktivitas pemindaian tiket.

## Persiapan dan instalasi lokal

### 1. Persyaratan

Siapkan:
- PHP 8.x dengan ekstensi **PDO MySQL** aktif.
- MySQL atau MariaDB.
- Web server lokal, misalnya Laragon atau XAMPP.
- Browser modern.

### 2. Salin repository

Clone repository ke direktori web server lokal:

```bash
git clone https://github.com/ilham789-fornt/BaraSuara.git
```

Untuk Laragon, letakkan folder proyek di direktori `www`. Untuk XAMPP, letakkan di direktori `htdocs`.

### 3. Buat database

1. Jalankan Apache dan MySQL melalui Laragon atau XAMPP.
2. Buka phpMyAdmin.
3. Buat database baru bernama `db_barasuara`.
4. Pilih database tersebut, lalu import file `database.sql`.

> **Penting:** `database.sql` berisi perintah `DROP TABLE IF EXISTS` sebelum membuat tabel. Gunakan file ini hanya untuk instalasi database baru atau setelah membuat backup. Mengimpornya ke database yang sudah berisi data dapat menghapus tabel dan data sebelumnya.

File `database_update_ticketing.sql` merupakan skrip pembaruan data demonstrasi ticketing untuk database yang sudah dibuat. Jalankan hanya setelah memeriksa isi skrip dan kondisi database. Skrip ini mengatur contoh jadwal/gelombang tiket untuk kebutuhan pengujian, bukan sumber jadwal acara resmi.

### 4. Atur koneksi database

Buka `config/database.php` dan sesuaikan konfigurasi dengan MySQL lokal:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'db_barasuara';
const DB_USER = 'root';
const DB_PASS = '';
```

Nilai di atas merupakan konfigurasi bawaan untuk lingkungan lokal tertentu. Sesuaikan username, password, host, atau port apabila konfigurasi perangkat berbeda. Jangan menggunakan kredensial lokal tersebut sebagai konfigurasi server produksi.

### 5. Jalankan aplikasi

Pastikan Apache dan MySQL aktif, kemudian akses melalui browser:

```text
http://localhost/BaraSuara/
```

Nama path URL perlu disesuaikan dengan nama folder proyek di direktori web server lokal.

## Alur penggunaan

1. Pengunjung membuka website dan melihat informasi musik atau tur.
2. Pengguna membuat akun atau login.
3. Pengguna memilih acara dan kategori tiket yang tersedia.
4. Aplikasi memproses pesanan dan menyimpan data transaksi.
5. Pengguna melihat detail pesanan dan status pembayaran pada halaman pesanan.
6. Setelah tiket berstatus `paid`, petugas gate scanner dapat memeriksa tiket dan mencatat check-in.

## Catatan pembayaran dan data demonstrasi

Alur pembayaran pada repository ini masih berupa **simulasi untuk kebutuhan pengembangan dan pengujian**. Tombol konfirmasi pembayaran pengguna bukan bukti bahwa dana telah diterima oleh penyedia pembayaran. Belum ada integrasi payment gateway/QRIS produksi yang dapat memverifikasi transaksi secara otomatis.

Data akun, jadwal acara, harga, kuota, serta status tiket yang ada pada seed database adalah data contoh. Periksa dan ganti data tersebut dengan informasi yang valid sebelum aplikasi dipublikasikan atau digunakan untuk acara nyata. Jangan mengandalkan data demo sebagai jadwal maupun penawaran tiket resmi.

## Praktik keamanan yang digunakan

Implementasi saat ini telah menggunakan beberapa fondasi keamanan berikut:
- Password disimpan dalam bentuk hash dan diverifikasi menggunakan fungsi password PHP.
- Akses database menggunakan PDO dan prepared statements.
- Form tertentu dilengkapi token CSRF dan validasi sisi server.
- Session login diregenerasi saat autentikasi.
- Halaman pesanan membatasi akses berdasarkan pemilik pesanan.
- Proses pembuatan pesanan menggunakan transaksi database dan penguncian data kuota.

Pemeriksaan keamanan tetap perlu dilakukan sebelum deployment. Gunakan HTTPS, simpan rahasia konfigurasi di luar source code, ganti atau hapus akun demonstrasi, terapkan pembatasan percobaan login, dan pastikan petugas gate scanner hanya memiliki izin yang dibutuhkan untuk check-in.

## Pengujian dan pengembangan

File `crud_operations.sql` berisi contoh query INSERT, SELECT, UPDATE, dan DELETE untuk mendukung pengujian operasi database. Periksa setiap query sebelum dijalankan karena sebagian contoh menggunakan ID data tertentu dan dapat mengubah atau menghapus data.

Checklist pengujian yang disarankan:
- [ ] Registrasi, login, dan logout pengguna.
- [ ] Login admin dan gate scanner.
- [ ] Pengujian hak akses setiap role.
- [ ] Operasi CRUD pada tabel yang dikelola dashboard.
- [ ] Pemesanan tiket dan validasi batas kuota.
- [ ] Pemeriksaan kepemilikan pesanan oleh pengguna.
- [ ] Validasi pembayaran simulasi dan status tiket.
- [ ] Check-in tiket valid serta penolakan tiket yang sudah digunakan.
- [ ] Pengujian validasi form, CSRF, dan session.
- [ ] Pengujian tampilan pada desktop dan perangkat seluler.

Jangan menandai fitur sebagai lulus hanya karena halaman berhasil dibuka. Catat hasil uji, kondisi database, langkah pengujian, dan bukti tangkapan layar untuk setiap skenario.

## Kontribusi

Untuk mengembangkan proyek:
1. Buat branch terpisah dari `main`.
2. Kerjakan perubahan dalam lingkup yang jelas dan hindari mengubah data atau skema database tanpa koordinasi.
3. Uji perubahan secara lokal menggunakan salinan database.
4. Sertakan ringkasan perubahan dan hasil pengujian pada pull request.

## Lisensi

Lihat file [LICENSE](LICENSE) untuk informasi lisensi repository.
