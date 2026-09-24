# 🚗 SISTEM PARKING BERBASIS WEB

Aplikasi pengelolaan parkir berbasis web yang dirancang untuk membantu proses pengelolaan kendaraan, area parkir, booking, transaksi, tarif, pengguna, dan laporan secara digital.

---

## 📌 Informasi Project

| Informasi              | Detail                                                               |
| ---------------------- | -------------------------------------------------------------------- |
| **Nama Project**       | Sistem Parking                                                       |
| **Jenis Project**      | Aplikasi Pengelolaan Parkir Berbasis Web                             |
| **Bahasa Pemrograman** | PHP                                                                  |
| **Database**           | MySQL (mysqli)                                                       |
| **Frontend**           | HTML5, CSS3, JavaScript                                              |
| **Web Server**         | Apache                                                               |
| **Server Lokal**       | XAMPP                                                                |
| **Hosting**            | InfinityFree                                                         |
| **Link Project**       | https://parkingrafa.freedev.app/?i=2                                 |
| **Link Canva**         | https://www.canva.com/design/DAHQLNu2wRM/riwvKSD-XP3-ayNR7PuOCg/edit |

---

## 📖 Tentang Project

Perkembangan teknologi informasi memberikan banyak kemudahan dalam pengelolaan berbagai aktivitas, termasuk pengelolaan tempat parkir.

Sistem parkir yang masih dilakukan secara manual dapat menyebabkan berbagai masalah, seperti pencatatan kendaraan yang kurang terorganisir, kesulitan mengetahui ketersediaan tempat parkir, serta proses pembuatan laporan yang membutuhkan waktu.

Oleh karena itu, dibuat **Sistem Parking berbasis web** yang dapat membantu mengelola:

* Data pengguna
* Data kendaraan
* Area parkir
* Tarif parkir
* Booking
* Transaksi (check-in, check-out via QR, pembayaran)
* Ulasan & rating area parkir
* Laporan
* Log aktivitas

Sistem ini diharapkan dapat membuat proses pengelolaan parkir menjadi lebih cepat, terstruktur, dan mudah digunakan.

---

## 🎯 Tujuan Project

Sistem Parking dibuat dengan beberapa tujuan:

1. Membantu pengelolaan data kendaraan secara digital.
2. Memudahkan pengguna melakukan booking tempat parkir.
3. Menampilkan informasi ketersediaan area parkir.
4. Membantu petugas mengelola proses parkir, termasuk check-in dan check-out via QR Code.
5. Memudahkan admin mengelola data master sistem (area, kendaraan, tarif, pengguna).
6. Membantu owner melihat laporan parkir.
7. Menyimpan data transaksi secara terstruktur menggunakan MySQL.
8. Meningkatkan efisiensi pengelolaan tempat parkir.

---

## 🛠️ Teknologi yang Digunakan

| Teknologi        | Fungsi                        |
| ---------------- | ----------------------------- |
| **PHP**          | Backend dan pemrosesan sistem |
| **MySQL**        | Penyimpanan database          |
| **HTML5**        | Struktur halaman website      |
| **CSS3**         | Tampilan dan desain website   |
| **JavaScript**   | Interaksi pada halaman        |
| **Apache**       | Web server                    |
| **XAMPP**        | Server pengembangan lokal     |
| **InfinityFree** | Hosting website               |

---

## 👥 Hak Akses Pengguna

Sistem memiliki beberapa jenis pengguna dengan hak akses yang berbeda. Sejak versi ini, hak akses **Admin** dan **Petugas** sudah dipisah sesuai fungsinya (tidak lagi identik).

### 🔐 Admin

Admin bertugas mengelola **data master** sistem, seperti:

* Area parkir
* Kendaraan
* Tarif
* Pengguna (user)
* Log aktivitas
* Struk transaksi

> Admin tidak lagi memiliki halaman transaksi sendiri — pengelolaan transaksi harian sepenuhnya dilakukan oleh Petugas.

### 👑 Owner

Owner dapat:

* Melihat laporan
* Menambahkan admin
* Memantau aktivitas sistem

### 👮 Petugas

Petugas fokus pada **operasional harian** parkir, seperti:

* Area parkir
* Kendaraan
* Log aktivitas
* Transaksi (check-in langsung, validasi booking, edit durasi, pembayaran cash/QRIS)
* Scan QR Code untuk proses kendaraan Masuk
* Scan QR Code untuk proses kendaraan keluar (`scan_qr.php` → `proses_keluar.php`)
* Struk transaksi

> Petugas tidak memiliki akses ke halaman Tarif dan Pengguna — pengelolaan data master tersebut menjadi tanggung jawab Admin.

### 🧑‍💼 Karyawan

Karyawan (pengguna internal) dapat:

* Mendaftarkan kendaraan
* Mengelola kendaraan miliknya
* Melakukan booking tempat parkir
* Mengonfirmasi kedatangan
* Melihat riwayat parkir
* Melihat kuitansi
* Melihat struk

### 👤 Pengguna / Pelanggan

Pengguna (didaftarkan lewat halaman register) dapat:

* Mendaftarkan dan menghapus kendaraan
* Mengelola kendaraan
* Melihat area parkir dan memberi ulasan/rating
* Melakukan booking
* Membatalkan booking
* Mengonfirmasi kedatangan
* Melihat riwayat parkir
* Melihat kuitansi
* Melihat struk

> Catatan: folder `karyawan/` dan `pengguna/` berisi kumpulan fitur yang serupa (kendaraan, booking, konfirmasi, riwayat, kuitansi, struk), dengan alur login (`login_karyawan.php` dan `login_pengguna.php`) dan dashboard (`dashboard_karyawan.php` dan `dashboard_pengguna.php`) masing-masing. Saat ini folder `pengguna/` memiliki dua fitur tambahan yang belum ada di `karyawan/`: menghapus kendaraan (`hapus_kendaraan.php`) dan membatalkan booking (`batal_booking.php`).

---

## 📁 Struktur Folder

```text
parking/
│
├── admin/
│   ├── area.php
│   ├── kendaraan.php
│   ├── log.php
│   ├── qris.png
│   ├── struk.php
│   ├── tarif.php
│   └── user.php
│
├── owner/
│   ├── add_admin.php
│   └── laporan.php
│
├── petugas/
│   ├── area.php
│   ├── kendaraan.php
│   ├── log.php
│   ├── proses_keluar.php
│   ├── qris.png
│   ├── scan_qr.php
│   ├── struk.php
│   └── transaksi.php
│
├── karyawan/
│   ├── daftarkan_kendaraan.php
│   ├── help.php
│   ├── kendaraan_saya.php
│   ├── konfirmasi_sudah_ditempat.php
│   ├── pesan_tempat.php
│   ├── profil.php
│   ├── proses_pesan.php
│   ├── quitansi.php
│   ├── riwayat.php
│   └── struk.php
│
├── pengguna/
│   ├── batal_booking.php
│   ├── daftarkan_kendaraan.php
│   ├── hapus_kendaraan.php
│   ├── help.php
│   ├── kendaraan_saya.php
│   ├── konfirmasi_sudah_ditempat.php
│   ├── pesan_tempat.php
│   ├── profil.php
│   ├── proses_pesan.php
│   ├── quitansi.php
│   ├── riwayat.php
│   └── struk.php
│
├── db.php
├── dashboard.php
├── dashboard_admin.php
├── dashboard_karyawan.php
├── dashboard_owner.php
├── dashboard_pengguna.php
├── dashboard_petugas.php
├── cek_area.php
├── index.php
├── login.php
├── login_karyawan.php
├── login_pengguna.php
├── logout.php
├── register.php
├── grhasia.mp4
├── rsjbg.jpeg
└── README.md
```

> Catatan: `petugas/` kini berisi file operasional transaksi (`transaksi.php`, `scan_qr.php`, `proses_keluar.php`) yang tidak dimiliki `admin/`, sementara `admin/` menyimpan file pengelolaan master (`tarif.php`, `user.php`) yang tidak dimiliki `petugas/`. Berkas duplikat lama `dhasboard.php` sudah dibersihkan dari project.

---

## 🗄️ Database

Database yang digunakan dalam Sistem Parking adalah:

```text
Lokal (XAMPP)   : parkir
Production      : if0_42701946_parking
```

Tabel-tabel utama yang digunakan:

```text
tb_user                    -- data pengguna & role (admin, owner, petugas, karyawan, pengguna)
tb_kendaraan                -- data kendaraan terdaftar
tb_area_parkir              -- data area/slot parkir reguler
tb_area_parkir_karyawan     -- data area/slot parkir khusus karyawan
tb_tarif                    -- data tarif parkir
tb_booking                  -- data booking tempat parkir
tb_transaksi                -- data transaksi parkir (masuk, keluar, pembayaran)
tb_log                      -- log aktivitas sistem
tb_ulasan                   -- ulasan & rating area parkir (dibuat otomatis oleh cek_area.php jika belum ada)
```

Database digunakan untuk menyimpan seluruh data yang diperlukan oleh sistem parkir.

---

## 🔑 Sistem Login & Hak Akses

Sistem menggunakan **PHP Session** untuk menjaga status login pengguna.

Sistem memiliki beberapa pintu masuk login sesuai role:

* `login.php` — login untuk Admin / Owner / Petugas
* `login_karyawan.php` — login untuk Karyawan
* `login_pengguna.php` — login untuk Pengguna (pendaftaran lewat `register.php`, role otomatis `pengguna`)

Setelah login, `dashboard.php` bertindak sebagai router yang mengarahkan setiap role ke dashboard-nya masing-masing (`dashboard_admin.php`, `dashboard_owner.php`, `dashboard_petugas.php`, `dashboard_karyawan.php`, `dashboard_pengguna.php`) berdasarkan nilai `$_SESSION['role']`.

Contoh pengecekan login:

```php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
```

Sistem juga menerapkan pembatasan akses berdasarkan role.

Contoh:

```php
if ($_SESSION['role'] != "admin") {
    die("Akses ditolak!");
}
```

Dengan sistem tersebut, halaman tertentu hanya dapat diakses oleh pengguna yang memiliki hak akses sesuai.

---

## 🔄 Alur Sistem

```text
                              WEBSITE PARKING
                                     │
              ┌───────────┬──────────┼───────────┬────────────┐
              │           │          │           │            │
            ADMIN       OWNER     PETUGAS     KARYAWAN     PENGGUNA
              │           │          │           │            │
              ▼           ▼          ▼           ▼            ▼
        Kelola Data    Laporan   Kelola Transaksi   Booking      Booking
         Master                  & Scan QR Keluar     │            │
                                      │                ▼            ▼
                                      │           Area Parkir   Area Parkir
                                      └─────┬──────────┴────────────┘
                                            ▼
                                     DATABASE MYSQL
                                            │
                                            ▼
                                    DATA SISTEM PARKIR
```

---

## 🅿️ Proses Booking Parkir

Proses booking dilakukan melalui beberapa tahap (berlaku untuk Karyawan maupun Pengguna):

1. Pengguna/karyawan login ke sistem sesuai role masing-masing.
2. Memilih menu cek area (`cek_area.php`), yang juga menampilkan ulasan/rating dari pengguna lain.
3. Sistem menampilkan area parkir yang tersedia.
4. Memilih atau mendaftarkan kendaraan (`daftarkan_kendaraan.php`).
5. Melakukan pemesanan tempat (`pesan_tempat.php` → `proses_pesan.php`), atau membatalkannya bila perlu (`batal_booking.php`, khusus Pengguna).
6. Sistem menyimpan data booking dan membuat kode QR unik untuk kendaraan masuk (`PARKIR|MASUK|id_booking`) dan keluar (`PARKIR|KELUAR|id_booking`).
7. Melakukan konfirmasi ketika sudah berada di lokasi (`konfirmasi_sudah_ditempat.php`).
8. Petugas memproses kendaraan masuk di `transaksi.php` (booking maupun kendaraan langsung/non-booking).
9. Saat kendaraan keluar, Petugas memindai QR Code di `scan_qr.php`, lalu sistem meneruskan ke halaman pembayaran (`proses_keluar.php`) untuk memproses tarif dan pembayaran (cash/QRIS).
10. Data transaksi disimpan ke database.
11. Pengguna/karyawan dapat melihat kuitansi (`quitansi.php`), struk (`struk.php`), dan riwayat parkir (`riwayat.php`).

---

## 💰 Pengelolaan Tarif

Admin dapat mengatur tarif parkir yang digunakan dalam proses transaksi.

Data tarif digunakan untuk menentukan biaya parkir berdasarkan ketentuan yang diterapkan oleh pengelola.

Dengan adanya fitur tarif, proses perhitungan biaya parkir dapat dilakukan secara lebih terstruktur.

---

## 💳 Transaksi Parkir

Transaksi digunakan untuk mencatat aktivitas kendaraan selama menggunakan tempat parkir. Seluruh alur transaksi (masuk, keluar, pembayaran) kini dikelola oleh Petugas melalui `petugas/transaksi.php`, `petugas/scan_qr.php`, dan `petugas/proses_keluar.php`.

Data transaksi dapat mencakup:

* Kendaraan
* Pengguna
* Area parkir
* Waktu masuk
* Waktu keluar
* Tarif
* Total pembayaran
* Status transaksi

Data transaksi kemudian dapat digunakan untuk membuat struk dan laporan.

---

## ⭐ Ulasan & Rating Area Parkir

Pengguna dapat memberikan ulasan (komentar) dan rating (skala 1–5) untuk setiap area parkir melalui `cek_area.php`. Data ulasan disimpan pada tabel `tb_ulasan`, yang dibuat otomatis oleh sistem jika belum tersedia di database, dan terhubung ke `tb_area_parkir` berdasarkan `id_area`.

---

## 📊 Laporan

Fitur laporan digunakan oleh owner untuk melihat informasi mengenai aktivitas sistem parkir.

Laporan dapat digunakan untuk mengetahui:

* Data transaksi
* Aktivitas parkir
* Pendapatan
* Jumlah kendaraan
* Data booking
* Aktivitas pengguna

Dengan laporan tersebut, owner dapat memperoleh informasi yang lebih mudah untuk memantau kondisi operasional parkir.

---

## 🔒 Keamanan Sistem

Beberapa aspek keamanan yang diterapkan dalam sistem antara lain:

* Menggunakan **PHP Session** untuk autentikasi pengguna.
* Menggunakan `password_hash()` untuk menyimpan password.
* Menggunakan `password_verify()` saat proses login.
* Membatasi halaman berdasarkan role.
* Melakukan validasi input pengguna.
* Menggunakan prepared statement untuk query yang menerima input pengguna.
* Tidak membagikan password database.
* Tidak menampilkan informasi error database pada production.

> **Catatan:** Jangan menyimpan password database, API key, atau informasi rahasia lainnya di dalam README maupun repository publik.

---

## 💻 Menjalankan Sistem Secara Lokal

### 1. Jalankan XAMPP

Aktifkan:

```text
Apache
MySQL
```

### 2. Letakkan Project

Simpan folder project di:

```text
C:\xampp\htdocs\parking
```

### 3. Siapkan Database

Buka:

```text
http://localhost/phpmyadmin
```

Kemudian:

1. Buat database dengan nama `parkir` (nama default yang dipakai `db.php`).
2. Import database project.
3. Pastikan nama database sesuai dengan konfigurasi pada `db.php`.

### 4. Periksa Konfigurasi Database

Sesuaikan konfigurasi pada:

```text
db.php
```

dengan database lokal yang digunakan (host, user, password, nama database).

### 5. Jalankan Website

Buka browser dan akses:

```text
http://localhost/parking/
```

---

## 🌐 Deployment ke InfinityFree

Tahapan deployment:

1. Membuat akun InfinityFree.
2. Membuat domain atau subdomain.
3. Membuat database MySQL.
4. Membuka phpMyAdmin.
5. Import database project.
6. Upload file project ke folder `htdocs`.
7. Mengubah konfigurasi database pada `db.php`.
8. Memastikan seluruh file dan folder telah ter-upload.
9. Membuka domain website.

### Konfigurasi Database Production

```text
Host     : sql313.infinityfree.com
Username : if0_42701946
Database : if0_42701946_parking
Port     : 3306
```

**Penting:** Password database tidak dicantumkan di README untuk menjaga keamanan akun dan database.

---

## ✅ Kelebihan Sistem

Sistem Parking memiliki beberapa kelebihan:

* Pengelolaan data lebih terstruktur.
* Memudahkan proses booking, termasuk pembatalan booking oleh pengguna.
* Memudahkan pengguna mengetahui area parkir, lengkap dengan ulasan dan rating dari pengguna lain.
* Memiliki pembagian hak akses yang jelas per role (Admin, Owner, Petugas, Karyawan, Pengguna).
* Proses kendaraan keluar sudah berbasis QR Code (scan di `scan_qr.php`), tidak hanya input manual.
* Data tersimpan dalam database.
* Memiliki fitur transaksi dan pembayaran (cash/QRIS).
* Memiliki fitur laporan.
* Dapat digunakan secara lokal maupun online.
* Dapat dikembangkan dengan fitur tambahan.

---

## ⚠️ Kekurangan Sistem

Beberapa bagian yang masih dapat dikembangkan:

* Sistem pembayaran online belum sepenuhnya terintegrasi (QRIS masih berupa gambar statis, belum terhubung ke payment gateway).
* Belum menggunakan sensor parkir secara langsung.
* Sistem notifikasi masih dapat dikembangkan.
* Sistem dapat dikembangkan menjadi aplikasi mobile.
* Keamanan dan optimasi production masih dapat ditingkatkan.
* Masih ada duplikasi kode antara folder `karyawan/` dan `pengguna/` untuk fitur yang sama.
* Beberapa tautan menu pada dashboard Karyawan mengarah ke halaman yang belum tersedia di project (mis. `karyawan/parkir_aktif.php` dan `karyawan/hapus_kendaraan.php`).

---

## 🚀 Pengembangan Selanjutnya

Sistem Parking dapat dikembangkan dengan fitur:

* 📱 Aplikasi Android
* 💳 Pembayaran online (integrasi payment gateway untuk QRIS)
* 📱 Notifikasi WhatsApp
* 📊 Dashboard statistik
* 🚗 Sensor slot parkir
* 📍 Google Maps untuk lokasi parkir
* 📄 Export laporan ke PDF atau Excel
* ⭐ Sistem membership
* 🔴 Monitoring parkir secara real-time
* 🧑‍💼 Menyamakan fitur hapus kendaraan & pembatalan booking antara Karyawan dan Pengguna

---

## 📝 Kesimpulan

**Sistem Parking** merupakan aplikasi berbasis web yang dirancang untuk membantu proses pengelolaan tempat parkir secara digital.

Dengan adanya pembagian hak akses **Admin, Owner, Petugas, Karyawan, dan Pengguna**, setiap pengguna dapat menjalankan fungsi sesuai dengan kebutuhannya — Admin kini berfokus pada data master, sementara Petugas menjalankan operasional transaksi harian termasuk proses keluar berbasis QR Code.

Sistem dapat membantu mengelola data kendaraan, area parkir, booking, tarif, transaksi, pengguna, ulasan, serta laporan secara lebih terstruktur. Penggunaan PHP dan MySQL juga memungkinkan sistem untuk dikembangkan lebih lanjut sesuai kebutuhan.

Dengan pengembangan fitur tambahan seperti pembayaran online, notifikasi, dan monitoring slot secara real-time, Sistem Parking dapat menjadi aplikasi pengelolaan parkir yang lebih lengkap dan modern.

---

## 👨‍💻 Developer

**Muhammad Zaidan Rafa**

**Project:** Sistem Parking Berbasis Web

---

<p align="center">
  <b>🚗 SISTEM PARKING</b><br>
  Sistem Pengelolaan Parkir Berbasis Web
</p>

<p align="center">
  Made with ❤️ using PHP & MySQL
</p>