<?php

session_start();

require '../db.php';

/* =====================================================
*   CEK LOGIN
===================================================== */

if (!isset($_SESSION['id_user'])) {

    header("Location: ../login_pengguna.php");

    exit;
}

/* =====================================================
*   CEK ROLE
===================================================== */

if (($_SESSION['role'] ?? '') !== 'pengguna') {

    die("Akses ditolak.");

}

/* =====================================================
*   DATA USER
===================================================== */

$id_user = (int) $_SESSION['id_user'];

$nama = trim($_SESSION['nama_lengkap'] ?? 'Pengguna');

$role = $_SESSION['role'] ?? 'pengguna';

$inisial = !empty($nama)

    ? strtoupper(substr($nama, 0, 1))

    : 'P';


/* =====================================================
*   NOTIFIKASI BOOKING
===================================================== */

$notifikasi_booking = "";
$tipe_notifikasi_booking = "";

/*
 * Membaca pesan dari session jika proses_pesan.php
 * mengirimkan pesan menggunakan session.
 */
if (isset($_SESSION['pesan_booking'])) {

    $notifikasi_booking = $_SESSION['pesan_booking'];

    unset($_SESSION['pesan_booking']);

    if (isset($_SESSION['tipe_pesan_booking'])) {

        $tipe_notifikasi_booking = $_SESSION['tipe_pesan_booking'];

        unset($_SESSION['tipe_pesan_booking']);

    } else {

        $tipe_notifikasi_booking = "success";
    }
}


/*
 * Membaca status dari URL.
 *
 * Contoh:
 * pesan_tempat.php?status=success&pesan=Booking+berhasil
 *
 * atau:
 * pesan_tempat.php?status=error&pesan=Booking+gagal
 */
if (isset($_GET['status'])) {

    $status_booking = strtolower(trim($_GET['status']));

    if ($status_booking === 'success' || $status_booking === 'berhasil') {

        $tipe_notifikasi_booking = "success";

    } elseif (
        $status_booking === 'error' ||
        $status_booking === 'gagal' ||
        $status_booking === 'danger'
    ) {

        $tipe_notifikasi_booking = "danger";
    }

    if (isset($_GET['pesan'])) {

        $notifikasi_booking = trim($_GET['pesan']);

    } elseif ($tipe_notifikasi_booking === "success") {

        $notifikasi_booking = "Booking tempat parkir berhasil!";

    } elseif ($tipe_notifikasi_booking === "danger") {

        $notifikasi_booking = "Booking tempat parkir gagal.";

    }
}


/* =====================================================
*   AMBIL KENDARAAN MILIK USER
===================================================== */

$query_kendaraan = "

    SELECT *

    FROM tb_kendaraan

    WHERE id_user = $id_user
      AND is_dihapus = 0

    ORDER BY id_kendaraan DESC

";

$kendaraan = mysqli_query($conn, $query_kendaraan);

if ($kendaraan === false) {

    die("Query kendaraan error: " . mysqli_error($conn));

}

$jumlah_kendaraan = mysqli_num_rows($kendaraan);


/* =====================================================
*   AMBIL SEMUA AREA PARKIR
===================================================== */

$query_area = "

    SELECT

        id_area,

        nama_area,

        kapasitas,

        terisi,

        rating

    FROM tb_area_parkir

    ORDER BY nama_area ASC

";

$area = mysqli_query($conn, $query_area);

if ($area === false) {

    die("Query area parkir error: " . mysqli_error($conn));

}


/* =====================================================
*   PROSES DATA AREA
===================================================== */

$daftar_area = [];

$ada_area_tersedia = false;

while ($a = mysqli_fetch_assoc($area)) {

    $kapasitas = (int) ($a['kapasitas'] ?? 0);

    $terisi = (int) ($a['terisi'] ?? 0);

    /*
     * Pastikan nilai tidak melebihi kapasitas
     */

    if ($terisi < 0) {

        $terisi = 0;

    }

    if ($terisi > $kapasitas) {

        $terisi = $kapasitas;

    }

    $tersedia = max(0, $kapasitas - $terisi);

    $a['kapasitas'] = $kapasitas;

    $a['terisi'] = $terisi;

    $a['tersedia'] = $tersedia;

    /*
     * Area penuh apabila tidak ada slot tersisa
     */

    $a['penuh'] = ($tersedia <= 0);

    if (!$a['penuh']) {

        $ada_area_tersedia = true;

    }

    $daftar_area[] = $a;
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Booking Parkir</title>

<style>

/* =====================================================
*   RESET
===================================================== */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

}


/* =====================================================
*   BODY
===================================================== */

body {

    background: linear-gradient(135deg, #e3f2fd, #f4f6f9);

    min-height: 100vh;

}


/* =====================================================
*   SIDEBAR
===================================================== */

.sidebar {

    width: 260px;

    background: #ffffff;

    border-right: 1px solid #e5e7eb;

    display: flex;

    flex-direction: column;

    position: fixed;

    top: 0;

    left: 0;

    bottom: 0;

    z-index: 1000;

    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);

    transition: transform 0.3s ease;

}

.sidebar-header {

    background: #007bff;

    color: #ffffff;

    padding: 22px 20px;

}

.sidebar-header h2 {

    font-size: 18px;

    margin-bottom: 16px;

}

.user-info {

    display: flex;

    align-items: center;

    gap: 12px;

}

.avatar {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    border-radius: 50%;

    background: rgba(255,255,255,0.25);

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;

    font-size: 18px;

}

.user-name {

    font-weight: bold;

    font-size: 15px;

    line-height: 1.3;

}

.user-role {

    font-size: 12px;

    opacity: 0.85;

    letter-spacing: 0.5px;

}

.sidebar-menu {

    flex: 1;

    padding: 14px 0;

    overflow-y: auto;

}

.nav-link {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 20px;

    color: #333;

    text-decoration: none;

    font-size: 15px;

    border-left: 3px solid transparent;

    transition: all 0.2s ease;

}

.nav-link .icon {

    width: 22px;

    text-align: center;

    font-size: 18px;

}

.nav-link:hover,

.nav-link.active {

    background: #eaf2ff;

    color: #007bff;

    border-left-color: #007bff;

}

.sidebar-footer {

    padding: 12px 0;

    border-top: 1px solid #eeeeee;

}

.logout-link:hover {

    background: #fdeaea;

    color: #dc3545;

    border-left-color: #dc3545;

}


/* =====================================================
*   MAIN
===================================================== */

.main-content {

    margin-left: 260px;

    min-height: 100vh;

}


/* =====================================================
*   TOPBAR
===================================================== */

.topbar {

    background: #ffffff;

    padding: 15px 25px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow: 0 1px 4px rgba(0,0,0,0.06);

    position: sticky;

    top: 0;

    z-index: 500;

}

.hamburger {

    display: none;

    flex-direction: column;

    justify-content: center;

    gap: 5px;

    width: 36px;

    height: 36px;

    background: none;

    border: none;

    cursor: pointer;

    padding: 6px;

}

.hamburger span {

    width: 100%;

    height: 3px;

    background: #333;

    border-radius: 2px;

}


/* =====================================================
*   CONTENT
===================================================== */

.content {

    padding: 40px;

}

.container {

    width: 100%;

    max-width: 850px;

    margin: 0 auto;

    background: #ffffff;

    padding: 30px;

    border-radius: 15px;

    box-shadow: 0 10px 30px rgba(0,0,0,0.12);

}

.container h2 {

    text-align: center;

    color: #0d6efd;

    margin-bottom: 10px;

    font-size: 28px;

}

.subtitle {

    text-align: center;

    color: #6c757d;

    margin-bottom: 30px;

}


/* =====================================================
*   NOTIFIKASI BOOKING
===================================================== */

.booking-notification {

    position: relative;

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 14px 45px 14px 16px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-size: 14px;

    font-weight: 600;

    line-height: 1.5;

    animation: notificationSlide .35s ease;

}

.booking-notification.success {

    background: #d1e7dd;

    color: #0f5132;

    border: 1px solid #badbcc;

}

.booking-notification.danger {

    background: #f8d7da;

    color: #842029;

    border: 1px solid #f5c2c7;

}

.booking-notification-icon {

    width: 30px;

    height: 30px;

    flex-shrink: 0;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;

}

.booking-notification.success .booking-notification-icon {

    background: #198754;

    color: #ffffff;

}

.booking-notification.danger .booking-notification-icon {

    background: #dc3545;

    color: #ffffff;

}

.booking-notification-close {

    position: absolute;

    right: 12px;

    top: 50%;

    transform: translateY(-50%);

    width: 30px;

    height: 30px;

    margin: 0;

    padding: 0;

    border: none;

    background: transparent;

    color: inherit;

    font-size: 20px;

    line-height: 1;

    cursor: pointer;

}

.booking-notification-close:hover {

    background: rgba(0,0,0,.08);

    transform: translateY(-50%);

}

@keyframes notificationSlide {

    from {

        opacity: 0;

        transform: translateY(-10px);

    }

    to {

        opacity: 1;

        transform: translateY(0);

    }

}


/* =====================================================
*   FORM
===================================================== */

label {

    display: block;

    margin-top: 18px;

    margin-bottom: 8px;

    font-weight: 600;

    color: #444;

}

input,

select {

    width: 100%;

    padding: 12px 15px;

    border: 1px solid #ced4da;

    border-radius: 8px;

    font-size: 15px;

    background: #fff;

    outline: none;

    transition: 0.3s;

}

input:focus,

select:focus {

    border-color: #0d6efd;

    box-shadow: 0 0 0 3px rgba(13,110,253,.2);

}

option:disabled {

    color: #adb5bd;

}


/* =====================================================
*   AREA PARKIR
===================================================== */

.area-title {

    margin-top: 25px;

    margin-bottom: 12px;

    font-size: 17px;

    font-weight: 700;

    color: #333;

}

.area-grid {

    display: grid;

    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));

    gap: 15px;

}

.area-card {

    position: relative;

}

.area-card input {

    position: absolute;

    opacity: 0;

    pointer-events: none;

}

.area-label {

    display: block;

    padding: 18px;

    border: 2px solid #dee2e6;

    border-radius: 12px;

    cursor: pointer;

    background: #fff;

    transition: 0.25s;

}

.area-label:hover {

    border-color: #0d6efd;

    transform: translateY(-2px);

    box-shadow: 0 5px 15px rgba(13,110,253,.10);

}

.area-card input:checked + .area-label {

    border-color: #0d6efd;

    background: #eaf3ff;

    box-shadow: 0 0 0 3px rgba(13,110,253,.12);

}

.area-name {

    font-size: 17px;

    font-weight: 700;

    color: #222;

    margin-bottom: 8px;

}

.area-info {

    font-size: 13px;

    color: #666;

    line-height: 1.6;

}

.area-available {

    display: inline-block;

    margin-top: 8px;

    padding: 4px 9px;

    border-radius: 20px;

    background: #d1e7dd;

    color: #0f5132;

    font-size: 12px;

    font-weight: 600;

}

.area-full {

    display: inline-block;

    margin-top: 8px;

    padding: 4px 9px;

    border-radius: 20px;

    background: #f8d7da;

    color: #842029;

    font-size: 12px;

    font-weight: 600;

}

.rating {

    float: right;

    color: #f59e0b;

    font-weight: 600;

}


/* =====================================================
*   BUTTON
===================================================== */

button {

    width: 100%;

    margin-top: 30px;

    padding: 14px;

    border: none;

    border-radius: 8px;

    background: #0d6efd;

    color: #fff;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;

    transition: 0.3s;

}

button:hover {

    background: #0b5ed7;

    transform: translateY(-2px);

}

button:active {

    transform: scale(.98);

}


/* =====================================================
*   NOTICE
===================================================== */

.notice {

    text-align: center;

    padding: 20px;

    border-radius: 10px;

    background: #fff3cd;

    color: #664d03;

    font-size: 15px;

    line-height: 1.6;

}

.notice a {

    display: inline-block;

    margin-top: 12px;

    color: #0d6efd;

    font-weight: 600;

    text-decoration: none;

}

.notice a:hover {

    text-decoration: underline;

}


/* =====================================================
*   MOBILE
===================================================== */

.overlay {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,0.4);

    z-index: 900;

}

.overlay.active {

    display: block;

}

@media (max-width: 992px) {

    .sidebar {

        transform: translateX(-100%);

    }

    .sidebar.active {

        transform: translateX(0);

    }

    .main-content {

        margin-left: 0;

    }

    .hamburger {

        display: flex;

    }

    .content {

        padding: 25px;

    }

}

@media (max-width: 576px) {

    .sidebar {

        width: 85%;

        max-width: 300px;

    }

    .topbar {

        padding: 12px 15px;

    }

    .content {

        padding: 15px;

    }

    .container {

        padding: 20px;

    }

    .container h2 {

        font-size: 24px;

    }

    .area-grid {

        grid-template-columns: 1fr;

    }

    .booking-notification {

        font-size: 13px;

        padding-left: 12px;

    }

}

</style>

</head>

<body>

<!-- =====================================================
*     SIDEBAR
===================================================== -->

<aside class="sidebar" id="sidebar">

<div class="sidebar-header">

    <h2>🅿️ Aplikasi Parkir</h2>

    <div class="user-info">

        <div class="avatar">

            <?= htmlspecialchars($inisial); ?>

        </div>

        <div>

            <p class="user-name">

                <?= htmlspecialchars($nama); ?>

            </p>

            <span class="user-role">

                <?= strtoupper(htmlspecialchars($role)); ?>

            </span>

        </div>

    </div>

</div>


<nav class="sidebar-menu">

    <a href="../dashboard_pengguna.php" class="nav-link">

        <span class="icon">📊</span>

        Dashboard

    </a>

    <a href="riwayat.php" class="nav-link">

        <span class="icon">🕒</span>

        Riwayat Parkir

    </a>

    <a href="kendaraan_saya.php" class="nav-link">

        <span class="icon">🚗</span>

        Kendaraan Saya

    </a>

    <a href="pesan_tempat.php" class="nav-link active">

        <span class="icon">🅿️</span>

        Pesan Tempat

    </a>

    <a href="help.php" class="nav-link">

        <span class="icon">❓</span>

        Bantuan

    </a>

    <a href="profil.php" class="nav-link">

        <span class="icon">👤</span>

        Profil

    </a>

</nav>


<div class="sidebar-footer">

    <a href="../index.php" class="nav-link">

        <span class="icon">🏠</span>

        Landing Page

    </a>

    <a

        href="../logout.php"

        class="nav-link logout-link"

        onclick="return confirm('Apakah Anda yakin ingin keluar?');"

    >

        <span class="icon">🚪</span>

        Logout

    </a>

</div>

</aside>

<!-- =====================================================
*     MAIN
===================================================== -->

<div class="main-content">

<div class="topbar">

    <button

        type="button"

        class="hamburger"

        onclick="toggleSidebar()"

    >

        <span></span>

        <span></span>

        <span></span>

    </button>

    <h2>Pesan Tempat Parkir</h2>

</div>


<main class="content">

    <div class="container">

        <h2>🅿️ Booking Parkir</h2>

        <p class="subtitle">

            Pilih kendaraan dan area parkir yang Anda inginkan.

        </p>


        <!-- =====================================================
        NOTIFIKASI BOOKING
        ===================================================== -->

        <?php if (!empty($notifikasi_booking)): ?>

            <div
                id="bookingNotification"
                class="booking-notification <?= $tipe_notifikasi_booking === 'danger' ? 'danger' : 'success'; ?>"
            >

                <div class="booking-notification-icon">

                    <?php if ($tipe_notifikasi_booking === 'danger'): ?>

                        ❌

                    <?php else: ?>

                        ✅

                    <?php endif; ?>

                </div>

                <div>

                    <?= htmlspecialchars($notifikasi_booking); ?>

                </div>

                <button
                    type="button"
                    class="booking-notification-close"
                    onclick="closeBookingNotification()"
                    aria-label="Tutup"
                >
                    ×
                </button>

            </div>

        <?php endif; ?>


        <?php if ($jumlah_kendaraan === 0): ?>

            <div class="notice">

                Anda belum memiliki kendaraan terdaftar.

                <br>

                Silakan tambahkan kendaraan terlebih dahulu

                sebelum melakukan booking.

                <br>

                <a href="kendaraan_saya.php">

                    + Tambah Kendaraan

                </a>

            </div>


        <?php elseif (!$ada_area_tersedia): ?>

            <div class="notice">

                Mohon maaf, semua area parkir sedang penuh saat ini.

                <br>

                Silakan coba lagi beberapa saat lagi.

            </div>


        <?php else: ?>

            <form

                method="POST"

                action="proses_pesan.php"

            >

                <!-- KENDARAAN -->

                <label for="id_kendaraan">

                    Kendaraan

                </label>

                <select

                    name="id_kendaraan"

                    id="id_kendaraan"

                    required

                >

                    <option value="">

                        -- Pilih Kendaraan --

                    </option>

                    <?php while ($k = mysqli_fetch_assoc($kendaraan)): ?>

                        <option

                            value="<?= (int) $k['id_kendaraan']; ?>"

                        >

                            <?= htmlspecialchars($k['plat_nomor']); ?>

                            -

                            <?= htmlspecialchars($k['jenis_kendaraan']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>


                <!-- AREA -->

                <div class="area-title">

                    Pilih Area Parkir

                </div>

                <div class="area-grid">

                    <?php foreach ($daftar_area as $a): ?>

                        <?php if (!$a['penuh']): ?>

                            <div class="area-card">

                                <input

                                    type="radio"

                                    name="id_area"

                                    id="area_<?= (int) $a['id_area']; ?>"

                                    value="<?= (int) $a['id_area']; ?>"

                                    required

                                >

                                <label

                                    for="area_<?= (int) $a['id_area']; ?>"

                                    class="area-label"

                                >

                                    <div class="area-name">

                                        <?= htmlspecialchars($a['nama_area']); ?>

                                        <?php if ((float) $a['rating'] > 0): ?>

                                            <span class="rating">

                                                ⭐

                                                <?= number_format(

                                                    (float) $a['rating'],

                                                    1

                                                ); ?>

                                            </span>

                                        <?php endif; ?>

                                    </div>

                                    <div class="area-info">

                                        Terisi:

                                        <strong>

                                            <?= $a['terisi']; ?>

                                        </strong>

                                        /

                                        <?= $a['kapasitas']; ?>

                                        <br>

                                        Kapasitas:

                                        <?= $a['kapasitas']; ?>

                                        kendaraan

                                        <br>

                                        <span class="area-available">

                                            🟢

                                            Tersedia

                                            <?= $a['tersedia']; ?>

                                            slot

                                        </span>

                                    </div>

                                </label>

                            </div>

                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>


                <!-- TANGGAL -->

                <label for="tanggal">

                    Tanggal Parkir

                </label>

                <input

                    type="date"

                    name="tanggal"

                    id="tanggal"

                    min="<?= date('Y-m-d'); ?>"

                    required

                >


                <!-- JAM -->

                <label for="jam_masuk">

                    Jam Masuk

                </label>

                <input

                    type="time"

                    name="jam_masuk"

                    id="jam_masuk"

                    required

                >


                <!-- ESTIMASI -->

                <label for="estimasi_jam">

                    Estimasi Durasi (Jam)

                </label>

                <input

                    type="number"

                    name="estimasi_jam"

                    id="estimasi_jam"

                    min="1"

                    max="24"

                    placeholder="Contoh: 2"

                    required

                >


                <button

                    type="submit"

                    name="booking"

                >

                    🅿️ Pesan Tempat Sekarang

                </button>

            </form>

        <?php endif; ?>

    </div>

</main>

</div>

<div class="overlay" id="overlay"></div>

<script>

function toggleSidebar() {

    const sidebar = document.getElementById('sidebar');

    const overlay = document.getElementById('overlay');

    sidebar.classList.toggle('active');

    overlay.classList.toggle('active');

}


document

    .getElementById('overlay')

    .addEventListener('click', function () {

        document

            .getElementById('sidebar')

            .classList.remove('active');

        this.classList.remove('active');

    });


/* =====================================================
   NOTIFIKASI BOOKING
===================================================== */

function closeBookingNotification() {

    const notification = document.getElementById('bookingNotification');

    if (notification) {

        notification.style.transition = 'opacity .3s ease, transform .3s ease';

        notification.style.opacity = '0';

        notification.style.transform = 'translateY(-10px)';

        setTimeout(function () {

            notification.remove();

        }, 300);

    }

}


/*
 * Notifikasi otomatis hilang setelah 6 detik.
 */

document.addEventListener('DOMContentLoaded', function () {

    const notification = document.getElementById('bookingNotification');

    if (notification) {

        setTimeout(function () {

            closeBookingNotification();

        }, 6000);

    }

});

</script>

</body>

</html>
