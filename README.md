# 🚗 SISTEM PARKING

## 1. Deskripsi Project

**Sistem Parking** adalah aplikasi web untuk mengelola sistem parkir secara digital.

Aplikasi ini menggunakan:

* **PHP** sebagai bahasa pemrograman backend
* **MySQL** sebagai database
* **HTML5** untuk struktur halaman
* **CSS3** untuk tampilan
* **JavaScript** untuk interaksi halaman
* **XAMPP** untuk menjalankan server secara lokal
* **InfinityFree** sebagai hosting online

Sistem memiliki beberapa jenis pengguna, yaitu:

* Admin
* Owner
* Petugas
* Pengguna/Pelanggan

---

## 2. Struktur Folder Project

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
│
├── index.php
├── login.php
├── login_pengguna.php
├── logout.php
├── register.php
│
├── transaksi.php
├── kendaraan.php
├── struk.php
├── user.php
│
├── index.html
└── README.md
```

---

## 3. File Utama

### `index.php`

Merupakan halaman utama aplikasi Parking.

Fungsinya dapat digunakan untuk:

* Menampilkan halaman awal
* Menampilkan informasi sistem
* Mengarahkan pengguna ke login
* Menampilkan dashboard sesuai role pengguna

---

### `db.php`

File ini digunakan untuk koneksi aplikasi dengan database MySQL.

Pada server InfinityFree, konfigurasi database menggunakan:

```text
Host     : sql313.infinityfree.com
Username : if0_42701946
Database : if0_42701946_parking
Port     : 3306
```

Jangan menggunakan konfigurasi XAMPP seperti:

```text
localhost
root
parkir
```

ketika website sudah berada di InfinityFree.

---

## 4. Folder `admin`

Folder `admin` digunakan untuk halaman yang hanya dapat diakses oleh administrator.

### `area.php`

Mengelola area atau lokasi parkir.

### `kendaraan.php`

Mengelola data kendaraan.

### `log.php`

Menampilkan aktivitas atau log sistem.

### `struk.php`

Mengelola atau menampilkan struk transaksi.

### `tarif.php`

Mengatur tarif parkir.

### `user.php`

Mengelola akun pengguna.

---

## 5. Folder `owner`

Folder `owner` digunakan untuk fitur khusus pemilik sistem.

### `add_admin.php`

Digunakan untuk menambahkan administrator.

### `laporan.php`

Digunakan untuk melihat laporan sistem parkir.

---

## 6. Folder `pengguna`

Folder `pengguna` digunakan untuk fitur pelanggan.

### `cek_area.php`

Digunakan untuk melihat ketersediaan area parkir.

### `kendaraan_saya.php`

Menampilkan kendaraan milik pengguna.

### `pesan_tempat.php`

Digunakan untuk melakukan pemesanan tempat parkir.

### `proses_pesan.php`

Memproses pemesanan tempat parkir.

### `konfirmasi_sudah_ditempat.php`

Memproses konfirmasi pengguna setelah berada di lokasi.

### `profil.php`

Menampilkan dan mengelola profil pengguna.

### `kuitansi.php`

Menampilkan kuitansi pembayaran.

### `riwayat.php`

Menampilkan riwayat parkir.

### `struk.php`

Menampilkan struk parkir.

---

## 7. Sistem Login

Aplikasi menggunakan session PHP.

Contoh pengecekan login:

```php
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
```

Sistem juga dapat membedakan hak akses berdasarkan role.

Contoh:

```php
if ($_SESSION['role'] != "admin") {
    die("Akses ditolak!");
}
```

---

## 8. Database

Database yang digunakan:

```text
if0_42701946_parking
```

Database berisi tabel-tabel yang digunakan untuk sistem Parking, seperti:

```text
users
kendaraan
transaksi
booking
area
tarif
log
```

Nama tabel harus disesuaikan dengan database yang sebenarnya digunakan oleh project.

---

## 9. Menjalankan Secara Lokal

Jika menggunakan XAMPP:

1. Jalankan **Apache**
2. Jalankan **MySQL**
3. Letakkan project pada:

```text
C:\xampp\htdocs\parking
```

4. Buka browser:

```text
http://localhost/parking/
```

---

## 10. Deployment ke InfinityFree

Setelah project selesai:

1. Buat akun InfinityFree.
2. Buat domain/subdomain.
3. Buat database MySQL.
4. Import database melalui phpMyAdmin.
5. Upload file project ke folder `htdocs`.
6. Ubah konfigurasi `db.php`.
7. Buka domain website.

Contoh:

```text
https://parkingrafa.freedev.app/
```

---

## 11. Keamanan

Beberapa hal yang perlu diperhatikan:

* Jangan membagikan password database.
* Jangan menyimpan password database di repository GitHub publik.
* Gunakan `password_hash()` untuk password.
* Gunakan `password_verify()` saat login.
* Validasi input pengguna.
* Gunakan prepared statement untuk query yang menerima input pengguna.
* Batasi akses halaman berdasarkan role.
* Jangan menampilkan error database di website production.

---

## 12. Teknologi

| Teknologi    | Fungsi           |
| ------------ | ---------------- |
| PHP          | Backend          |
| MySQL        | Database         |
| HTML         | Struktur halaman |
| CSS          | Tampilan         |
| JavaScript   | Interaksi        |
| Apache       | Web server       |
| XAMPP        | Server lokal     |
| InfinityFree | Hosting          |

---

## 13. Alur Sistem

```text
                    ┌──────────────┐
                    │    WEBSITE   │
                    │   PARKING    │
                    └──────┬───────┘
                           │
             ┌─────────────┼─────────────┐
             │             │             │
          Admin          Owner        Pengguna
             │             │             │
             ▼             ▼             ▼
          Kelola        Laporan       Booking
          Sistem                       Parkir
             │                           │
             └─────────────┬─────────────┘
                           ▼
                    ┌──────────────┐
                    │    MySQL     │
                    │   Database   │
                    └──────────────┘
```

---

## 14. Kesimpulan

Sistem Parking merupakan aplikasi berbasis web yang digunakan untuk membantu proses pengelolaan parkir secara digital.

Dengan pembagian akses Admin, Owner, Petugas, dan Pengguna, sistem dapat mengatur proses parkir, kendaraan, booking, tarif, transaksi, laporan, dan data pengguna secara terstruktur.
