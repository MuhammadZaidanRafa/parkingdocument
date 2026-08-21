# 🚗 SISTEM PARKING BERBASIS WEB

Aplikasi pengelolaan parkir berbasis web yang dirancang untuk membantu proses pengelolaan kendaraan, area parkir, booking, transaksi, tarif, pengguna, dan laporan secara digital.

---

## 📌 Informasi Project

| Informasi              | Detail                                                               |
| ---------------------- | -------------------------------------------------------------------- |
| **Nama Project**       | Sistem Parking                                                       |
| **Jenis Project**      | Aplikasi Pengelolaan Parkir Berbasis Web                             |
| **Bahasa Pemrograman** | PHP                                                                  |
| **Database**           | MySQL                                                                |
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
* Transaksi
* Laporan
* Log aktivitas

Sistem ini diharapkan dapat membuat proses pengelolaan parkir menjadi lebih cepat, terstruktur, dan mudah digunakan.

---

## 🎯 Tujuan Project

Sistem Parking dibuat dengan beberapa tujuan:

1. Membantu pengelolaan data kendaraan secara digital.
2. Memudahkan pengguna melakukan booking tempat parkir.
3. Menampilkan informasi ketersediaan area parkir.
4. Membantu petugas mengelola proses parkir.
5. Memudahkan admin mengelola data sistem.
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

Sistem memiliki beberapa jenis pengguna dengan hak akses yang berbeda.

### 🔐 Admin

Admin bertugas mengelola data sistem, seperti:

* Area parkir
* Kendaraan
* Tarif
* Pengguna
* Log aktivitas
* Struk transaksi

### 👑 Owner

Owner dapat:

* Melihat laporan
* Menambahkan admin
* Memantau aktivitas sistem

### 👮 Petugas

Petugas bertugas membantu proses operasional parkir, seperti:

* Check-in kendaraan
* Check-out kendaraan
* Mengelola transaksi parkir

### 👤 Pengguna / Pelanggan

Pengguna dapat:

* Mengelola kendaraan
* Melihat area parkir
* Melakukan booking
* Mengonfirmasi kedatangan
* Melihat riwayat parkir
* Melihat kuitansi
* Melihat struk

---

## 📁 Struktur Folder

```text
parking/
│
├── admin/
│   ├── area.php
│   ├── kendaraan.php
│   ├── log.php
│   ├── struk.php
│   ├── tarif.php
│   └── user.php
│
├── owner/
│   ├── add_admin.php
│   └── laporan.php
│
├── pengguna/
│   ├── cek_area.php
│   ├── kendaraan_saya.php
│   ├── konfirmasi_sudah_ditempat.php
│   ├── pesan_tempat.php
│   ├── profil.php
│   ├── proses_pesan.php
│   ├── kuitansi.php
│   ├── riwayat.php
│   └── struk.php
│
├── db.php
├── dashboard.php
├── dashboard_admin.php
├── dashboard_owner.php
├── dashboard_pengguna.php
├── dashboard_petugas.php
├── index.php
├── login.php
├── login_pengguna.php
├── logout.php
├── register.php
├── transaksi.php
├── kendaraan.php
├── struk.php
├── user.php
├── index.html
└── README.md
```

---

## 🗄️ Database

Database yang digunakan dalam Sistem Parking adalah:

```text
if0_42701946_parking
```

Beberapa tabel utama yang digunakan antara lain:

```text
users
kendaraan
transaksi
booking
area
tarif
log
```

Database digunakan untuk menyimpan seluruh data yang diperlukan oleh sistem parkir.

---

## 🔑 Sistem Login & Hak Akses

Sistem menggunakan **PHP Session** untuk menjaga status login pengguna.

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
                ┌──────────────┼──────────────┐
                │              │              │
              ADMIN          OWNER         PENGGUNA
                │              │              │
                ▼              ▼              ▼
          Kelola Sistem     Laporan        Booking
                │                             │
                │                             ▼
                │                       Area Parkir
                │                             │
                └──────────────┬──────────────┘
                               ▼
                         DATABASE MYSQL
                               │
                               ▼
                       DATA SISTEM PARKIR
```

---

## 🅿️ Proses Booking Parkir

Proses booking dilakukan melalui beberapa tahap:

1. Pengguna login ke sistem.
2. Pengguna memilih menu cek area.
3. Sistem menampilkan area parkir yang tersedia.
4. Pengguna memilih kendaraan.
5. Pengguna melakukan pemesanan tempat.
6. Sistem menyimpan data booking.
7. Pengguna melakukan konfirmasi ketika sudah berada di lokasi.
8. Petugas dapat memproses kendaraan.
9. Data transaksi disimpan ke database.
10. Pengguna dapat melihat kuitansi dan riwayat parkir.

---

## 💰 Pengelolaan Tarif

Admin dapat mengatur tarif parkir yang digunakan dalam proses transaksi.

Data tarif digunakan untuk menentukan biaya parkir berdasarkan ketentuan yang diterapkan oleh pengelola.

Dengan adanya fitur tarif, proses perhitungan biaya parkir dapat dilakukan secara lebih terstruktur.

---

## 💳 Transaksi Parkir

Transaksi digunakan untuk mencatat aktivitas kendaraan selama menggunakan tempat parkir.

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

1. Buat database.
2. Import database project.
3. Pastikan nama database sesuai dengan konfigurasi pada `db.php`.

### 4. Periksa Konfigurasi Database

Sesuaikan konfigurasi pada:

```text
db.php
```

dengan database lokal yang digunakan.

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
* Memudahkan proses booking.
* Memudahkan pengguna mengetahui area parkir.
* Memiliki pembagian hak akses.
* Data tersimpan dalam database.
* Memiliki fitur transaksi.
* Memiliki fitur laporan.
* Dapat digunakan secara lokal maupun online.
* Dapat dikembangkan dengan fitur tambahan.

---

## ⚠️ Kekurangan Sistem

Beberapa bagian yang masih dapat dikembangkan:

* Sistem pembayaran online belum sepenuhnya terintegrasi.
* Belum menggunakan sensor parkir secara langsung.
* Sistem notifikasi masih dapat dikembangkan.
* Sistem dapat dikembangkan menjadi aplikasi mobile.
* Keamanan dan optimasi production masih dapat ditingkatkan.

---

## 🚀 Pengembangan Selanjutnya

Sistem Parking dapat dikembangkan dengan fitur:

* 📱 Aplikasi Android
* 🔳 QR Code untuk tiket parkir
* 🎫 QR Code untuk booking
* 💳 Pembayaran online
* 📱 Notifikasi WhatsApp
* 📊 Dashboard statistik
* 🚗 Sensor slot parkir
* 📍 Google Maps untuk lokasi parkir
* 📄 Export laporan ke PDF atau Excel
* ⭐ Sistem membership
* 🔴 Monitoring parkir secara real-time

---

## 📝 Kesimpulan

**Sistem Parking** merupakan aplikasi berbasis web yang dirancang untuk membantu proses pengelolaan tempat parkir secara digital.

Dengan adanya pembagian hak akses **Admin, Owner, Petugas, dan Pengguna**, setiap pengguna dapat menjalankan fungsi sesuai dengan kebutuhannya.

Sistem dapat membantu mengelola data kendaraan, area parkir, booking, tarif, transaksi, pengguna, serta laporan secara lebih terstruktur. Penggunaan PHP dan MySQL juga memungkinkan sistem untuk dikembangkan lebih lanjut sesuai kebutuhan.

Dengan pengembangan fitur tambahan seperti pembayaran online, QR Code, notifikasi, dan monitoring slot secara real-time, Sistem Parking dapat menjadi aplikasi pengelolaan parkir yang lebih lengkap dan modern.

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
