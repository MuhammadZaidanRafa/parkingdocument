
<?php

session_start();

require_once "../db.php";


/*
|--------------------------------------------------------------------------
| ERROR REPORTING
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_karyawan.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];


/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'karyawan') {
    header("Location: ../dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATA USER
|--------------------------------------------------------------------------
*/

$nama = trim($_SESSION['nama_lengkap'] ?? 'Karyawan');
$role = $_SESSION['role'] ?? 'karyawan';

$inisial = !empty($nama)
    ? strtoupper(substr($nama, 0, 1))
    : 'K';


/*
|--------------------------------------------------------------------------
| AMBIL PESAN SESSION
|--------------------------------------------------------------------------
*/

$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
$pesan_error = $_SESSION['pesan_error'] ?? '';

unset($_SESSION['pesan_sukses']);
unset($_SESSION['pesan_error']);


/*
|--------------------------------------------------------------------------
| AMBIL KENDARAAN MILIK KARYAWAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id_kendaraan,
        plat_nomor,
        jenis_kendaraan
    FROM tb_kendaraan
    WHERE id_user = ?
    ORDER BY id_kendaraan DESC
");

$stmt->bind_param("i", $id_user);
$stmt->execute();

$result_kendaraan = $stmt->get_result();

$daftar_kendaraan = [];

while ($k = $result_kendaraan->fetch_assoc()) {
    $daftar_kendaraan[] = $k;
}

$stmt->close();

$jumlah_kendaraan = count($daftar_kendaraan);


/*
|--------------------------------------------------------------------------
| AMBIL AREA PARKIR KHUSUS KARYAWAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id_area_karyawan,
        nama_area,
        kapasitas,
        terisi,
        rating,
        status
    FROM tb_area_parkir_karyawan
    WHERE status != 'nonaktif'
    ORDER BY nama_area ASC
");

$stmt->execute();

$result_area = $stmt->get_result();

$daftar_area = [];
$ada_area_tersedia = false;

while ($a = $result_area->fetch_assoc()) {

    $kapasitas = max(0, (int) ($a['kapasitas'] ?? 0));
    $terisi = max(0, (int) ($a['terisi'] ?? 0));

    if ($terisi > $kapasitas) {
        $terisi = $kapasitas;
    }

    $status = $a['status'] ?? 'tersedia';

    $tersedia = max(
        0,
        $kapasitas - $terisi
    );

    $penuh = (
        $tersedia <= 0 ||
        $status === 'penuh'
    );

    $a['kapasitas'] = $kapasitas;
    $a['terisi'] = $terisi;
    $a['tersedia'] = $tersedia;
    $a['status'] = $status;
    $a['penuh'] = $penuh;

    if (!$penuh && $status === 'tersedia') {
        $ada_area_tersedia = true;
    }

    $daftar_area[] = $a;
}

$stmt->close();

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pesan Tempat Parkir - Karyawan</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family:
                'Segoe UI',
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }

        body {
            background:
                linear-gradient(
                    135deg,
                    #e3f2fd,
                    #f4f6f9
                );
            min-height: 100vh;
            color: #222;
        }


        /* =====================================================
           SIDEBAR
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

            box-shadow:
                2px 0 12px rgba(0, 0, 0, 0.05);

            transition:
                transform 0.3s ease;
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

            background:
                rgba(255, 255, 255, 0.25);

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

            border-left:
                3px solid transparent;

            transition:
                all 0.2s ease;
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
           MAIN
        ===================================================== */

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }

        .topbar {
            background: #ffffff;

            padding: 15px 25px;

            display: flex;
            align-items: center;
            gap: 15px;

            box-shadow:
                0 1px 4px rgba(0, 0, 0, 0.06);

            position: sticky;
            top: 0;

            z-index: 500;
        }

        .topbar h2 {
            color: #222;
            font-size: 21px;
        }


        /* =====================================================
           HAMBURGER
        ===================================================== */

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
           CONTENT
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

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.12);
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

            margin-bottom: 25px;
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .karyawan-badge {
            display: block;

            width: fit-content;

            margin: 0 auto 25px;

            padding: 7px 13px;

            border-radius: 20px;

            background: #eaf2ff;

            color: #0d6efd;

            font-size: 13px;

            font-weight: 600;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert {
            padding: 14px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            line-height: 1.5;

            font-size: 14px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;

            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;

            border: 1px solid #f5c2c7;
        }


        /* =====================================================
           FORM
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

            border:
                1px solid #ced4da;

            border-radius: 8px;

            font-size: 15px;

            background: #ffffff;

            outline: none;

            transition: 0.3s;
        }

        input:focus,
        select:focus {
            border-color: #0d6efd;

            box-shadow:
                0 0 0 3px
                rgba(13, 110, 253, 0.2);
        }


        /* =====================================================
           AREA
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

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(220px, 1fr)
                );

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

            border:
                2px solid #dee2e6;

            border-radius: 12px;

            cursor: pointer;

            background: #ffffff;

            transition: 0.25s;
        }

        .area-label:hover {
            border-color: #0d6efd;

            transform:
                translateY(-2px);

            box-shadow:
                0 5px 15px
                rgba(13, 110, 253, 0.10);
        }

        .area-card input:checked
        + .area-label {
            border-color: #0d6efd;

            background: #eaf3ff;

            box-shadow:
                0 0 0 3px
                rgba(13, 110, 253, 0.12);
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

        .area-available,
        .area-full {
            display: inline-block;

            margin-top: 8px;

            padding: 4px 9px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;
        }

        .area-available {
            background: #d1e7dd;
            color: #0f5132;
        }

        .area-full {
            background: #f8d7da;
            color: #842029;
        }

        .rating {
            float: right;
            color: #f59e0b;
            font-weight: 600;
        }


        /* =====================================================
           NOTICE
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
           BUTTON
        ===================================================== */

        button[type="submit"] {
            width: 100%;

            margin-top: 30px;

            padding: 14px;

            border: none;

            border-radius: 8px;

            background: #0d6efd;

            color: #ffffff;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            transition: 0.3s;
        }

        button[type="submit"]:hover {
            background: #0b5ed7;

            transform:
                translateY(-2px);
        }

        button[type="submit"]:active {
            transform: scale(0.98);
        }


        /* =====================================================
           OVERLAY
        ===================================================== */

        .overlay {
            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0, 0, 0, 0.4);

            z-index: 900;
        }

        .overlay.active {
            display: block;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 992px) {

            .sidebar {
                transform:
                    translateX(-100%);
            }

            .sidebar.active {
                transform:
                    translateX(0);
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

            .topbar h2 {
                font-size: 18px;
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
        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
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

        <a
            href="../dashboard_karyawan.php"
            class="nav-link"
        >
            <span class="icon">📊</span>
            Dashboard
        </a>

        <a
            href="riwayat.php"
            class="nav-link"
        >
            <span class="icon">🕒</span>
            Riwayat Parkir
        </a>

        <a
            href="kendaraan_saya.php"
            class="nav-link"
        >
            <span class="icon">🚗</span>
            Kendaraan Saya
        </a>

        <a
            href="pesan_tempat.php"
            class="nav-link active"
        >
            <span class="icon">🅿️</span>
            Pesan Tempat
        </a>

        <a
            href="help.php"
            class="nav-link"
        >
            <span class="icon">❓</span>
            Bantuan
        </a>

        <a
            href="profil.php"
            class="nav-link"
        >
            <span class="icon">👤</span>
            Profil
        </a>

    </nav>


    <div class="sidebar-footer">

        <a
            href="../index.php"
            class="nav-link"
        >
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
     MAIN
===================================================== -->

<div class="main-content">

    <div class="topbar">

        <button
            type="button"
            class="hamburger"
            onclick="toggleSidebar()"
            aria-label="Buka menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <h2>Pesan Tempat Parkir</h2>

    </div>


    <main class="content">

        <div class="container">

            <h2>🅿️ Booking Parkir Karyawan</h2>

            <p class="subtitle">
                Pilih kendaraan dan area parkir khusus karyawan.
            </p>

            <div class="karyawan-badge">
                👨‍💼 Area Khusus Karyawan
            </div>


            <!-- =================================================
                 PESAN SUKSES
            ================================================== -->

            <?php if (!empty($pesan_sukses)): ?>

                <div class="alert alert-success">
                    ✅
                    <?= htmlspecialchars($pesan_sukses); ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 PESAN ERROR
            ================================================== -->

            <?php if (!empty($pesan_error)): ?>

                <div class="alert alert-error">
                    ❌
                    <?= htmlspecialchars($pesan_error); ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 TIDAK ADA KENDARAAN
            ================================================== -->

            <?php if ($jumlah_kendaraan === 0): ?>

                <div class="notice">

                    🚗 Anda belum memiliki kendaraan
                    terdaftar.

                    <br>

                    Silakan tambahkan kendaraan
                    terlebih dahulu sebelum melakukan
                    booking.

                    <br>

                    <a href="kendaraan_saya.php">
                        + Tambah Kendaraan
                    </a>

                </div>


            <!-- =================================================
                 SEMUA AREA PENUH
            ================================================== -->

            <?php elseif (!$ada_area_tersedia): ?>

                <div class="notice">

                    🅿️ Mohon maaf, semua area parkir
                    karyawan sedang penuh atau tidak
                    tersedia.

                    <br>

                    Silakan coba lagi beberapa saat lagi.

                </div>


            <!-- =================================================
                 FORM BOOKING
            ================================================== -->

            <?php else: ?>

                <form
                    method="POST"
                    action="proses_pesan.php"
                >

                    <!-- KENDARAAN -->

                    <label for="id_kendaraan">
                        🚗 Kendaraan
                    </label>

                    <select
                        name="id_kendaraan"
                        id="id_kendaraan"
                        required
                    >

                        <option value="">
                            -- Pilih Kendaraan --
                        </option>

                        <?php foreach ($daftar_kendaraan as $k): ?>

                            <option
                                value="<?= (int) $k['id_kendaraan']; ?>"
                            >

                                <?= htmlspecialchars(
                                    $k['plat_nomor']
                                ); ?>

                                -

                                <?= htmlspecialchars(
                                    $k['jenis_kendaraan']
                                ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <!-- AREA PARKIR -->

                    <div class="area-title">
                        🅿️ Pilih Area Parkir Karyawan
                    </div>


                    <div class="area-grid">

                        <?php foreach ($daftar_area as $a): ?>

                            <?php
                            if (
                                $a['penuh'] ||
                                $a['status'] !== 'tersedia'
                            ) {
                                continue;
                            }
                            ?>

                            <div class="area-card">

                                <input
                                    type="radio"
                                    name="id_area_karyawan"
                                    id="area_<?= (int) $a['id_area_karyawan']; ?>"
                                    value="<?= (int) $a['id_area_karyawan']; ?>"
                                    required
                                >

                                <label
                                    for="area_<?= (int) $a['id_area_karyawan']; ?>"
                                    class="area-label"
                                >

                                    <div class="area-name">

                                        <?= htmlspecialchars(
                                            $a['nama_area']
                                        ); ?>


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

                                        <span
                                            class="area-available"
                                        >

                                            🟢 Tersedia

                                            <?= $a['tersedia']; ?>

                                            slot

                                        </span>

                                    </div>

                                </label>

                            </div>

                        <?php endforeach; ?>

                    </div>


                    <!-- TANGGAL -->

                    <label for="tanggal">
                        📅 Tanggal Parkir
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
                        🕐 Jam Masuk
                    </label>

                    <input
                        type="time"
                        name="jam_masuk"
                        id="jam_masuk"
                        required
                    >


                    <!-- ESTIMASI -->

                    <label for="estimasi_jam">
                        ⏱️ Estimasi Durasi (Jam)
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


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        name="booking"
                        value="1"
                    >
                        🅿️ Pesan Tempat Sekarang
                    </button>

                </form>

            <?php endif; ?>

        </div>

    </main>

</div>


<!-- =====================================================
     OVERLAY
===================================================== -->

<div
    class="overlay"
    id="overlay"
></div>


<script>

function toggleSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('overlay');

    sidebar.classList.toggle('active');

    overlay.classList.toggle('active');
}


document
    .getElementById('overlay')
    .addEventListener(
        'click',
        function () {

            document
                .getElementById('sidebar')
                .classList.remove('active');

            this.classList.remove('active');

        }
    );

</script>


</body>

</html>
