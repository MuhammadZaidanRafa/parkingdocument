
<?php

session_start();

require_once 'db.php';

/*
|--------------------------------------------------------------------------
| STATISTIK DATABASE
|--------------------------------------------------------------------------
*/

function get_total_count($conn, $query)
{
    $res = $conn->query($query);

    if ($res && $row = $res->fetch_assoc()) {
        return (int) ($row['total'] ?? 0);
    }

    return 0;
}

$total_area = get_total_count(
    $conn,
    "SELECT COUNT(*) AS total FROM tb_area_parkir"
);

$total_transaksi = get_total_count(
    $conn,
    "SELECT COUNT(*) AS total FROM tb_transaksi"
);

$total_kendaraan = get_total_count(
    $conn,
    "SELECT COUNT(*) AS total FROM tb_kendaraan"
);


/*
|--------------------------------------------------------------------------
| DATA AREA PARKIR
|--------------------------------------------------------------------------
*/

$query_area = $conn->query("
    SELECT *
    FROM tb_area_parkir
    LIMIT 6
");


/*
|--------------------------------------------------------------------------
| SESSION / ROLE
|--------------------------------------------------------------------------
*/

$is_login = isset($_SESSION['id_user']);
$id_user  = $_SESSION['id_user'] ?? null;
$nama     = $_SESSION['nama_lengkap'] ?? '';
$role     = $_SESSION['role'] ?? '';


/*
|--------------------------------------------------------------------------
| TENTUKAN DASHBOARD SESUAI ROLE
|--------------------------------------------------------------------------
*/

$dashboard_url = 'login.php';

switch ($role) {

    case 'admin':
        $dashboard_url = 'dashboard_admin.php';
        break;

    case 'petugas':
        $dashboard_url = 'dashboard_petugas.php';
        break;

    case 'owner':
        $dashboard_url = 'dashboard_owner.php';
        break;

    case 'pengguna':
        $dashboard_url = 'dashboard_pengguna.php';
        break;

    case 'karyawan':
        $dashboard_url = 'dashboard_karyawan.php';
        break;
}


/*
|--------------------------------------------------------------------------
| LABEL ROLE
|--------------------------------------------------------------------------
*/

$role_label = '';

switch ($role) {

    case 'admin':
        $role_label = 'Administrator';
        break;

    case 'petugas':
        $role_label = 'Petugas Parkir';
        break;

    case 'owner':
        $role_label = 'Owner';
        break;

    case 'pengguna':
        $role_label = 'Pengguna';
        break;

    case 'karyawan':
        $role_label = 'Karyawan Rumah Sakit';
        break;

    default:
        $role_label = '';
        break;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>E-Parkir | Sistem Manajemen Parkir Rumah Sakit</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        rel="stylesheet"
    >


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        :root {
            --brand-dark: #101d33;
            --brand-blue: #1e3c72;
            --brand-blue-light: #2a5298;
            --brand-gold: #f7b733;
        }


        * {
            box-sizing: border-box;
        }


        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }


        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }


        /*
        |--------------------------------------------------------------------------
        | NAVBAR
        |--------------------------------------------------------------------------
        */

        .navbar {
            background-color: rgba(16, 29, 51, 0.97) !important;

            box-shadow:
                0 2px 12px rgba(0, 0, 0, 0.15);
        }


        .navbar-brand {
            letter-spacing: 0.5px;
        }


        .nav-link {
            font-weight: 500;

            transition:
                color 0.2s ease;
        }


        .nav-link:hover {
            color: var(--brand-gold) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .hero-section {
            position: relative;

            background-image:
                linear-gradient(
                    135deg,
                    rgba(15, 25, 45, 0.90) 0%,
                    rgba(30, 60, 114, 0.82) 55%,
                    rgba(42, 82, 152, 0.74) 100%
                ),
                url('rsjbg.jpeg');

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

            color: white;

            padding: 120px 0 100px;
        }


        @media (max-width: 991.98px) {

            .hero-section {
                background-attachment: scroll;

                padding:
                    80px 0 60px;
            }
        }


        .hero-section h1 {
            text-shadow:
                0 2px 10px rgba(0, 0, 0, 0.35);
        }


        .hero-section .lead {
            text-shadow:
                0 1px 6px rgba(0, 0, 0, 0.3);
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTON
        |--------------------------------------------------------------------------
        */

        .btn-warning {
            background-color:
                var(--brand-gold);

            border-color:
                var(--brand-gold);

            color:
                #1a1a1a;
        }


        .btn-warning:hover {
            background-color:
                #e0a52a;

            border-color:
                #e0a52a;
        }


        .btn-lg,
        .btn-outline-light {
            border-radius:
                10px;
        }


        /*
        |--------------------------------------------------------------------------
        | STAT BOX
        |--------------------------------------------------------------------------
        */

        .stat-box {
            background:
                rgba(255, 255, 255, 0.12);

            backdrop-filter:
                blur(12px);

            -webkit-backdrop-filter:
                blur(12px);

            border:
                1px solid rgba(255, 255, 255, 0.18);

            border-radius:
                14px;

            padding:
                22px;

            transition:
                transform 0.25s ease,
                background 0.25s ease;
        }


        .stat-box:hover {
            transform:
                translateY(-4px);

            background:
                rgba(255, 255, 255, 0.18);
        }


        .stat-box h2 {
            font-weight:
                800;
        }


        /*
        |--------------------------------------------------------------------------
        | VIDEO
        |--------------------------------------------------------------------------
        */

        #video {
            scroll-margin-top:
                80px;

            background-color:
                #fff;
        }


        .video-wrapper {
            position:
                relative;

            width:
                100%;

            max-width:
                900px;

            margin:
                0 auto;

            aspect-ratio:
                16 / 9;

            border-radius:
                18px;

            overflow:
                hidden;

            box-shadow:
                0 14px 34px rgba(16, 29, 51, 0.18);

            background:
                #000;
        }


        .video-wrapper video {
            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

            display:
                block;
        }


        /*
        |--------------------------------------------------------------------------
        | SECTION
        |--------------------------------------------------------------------------
        */

        #fitur,
        #area {
            scroll-margin-top:
                80px;
        }


        .section-title {
            font-weight:
                700;

            color:
                var(--brand-dark);
        }


        /*
        |--------------------------------------------------------------------------
        | FEATURE CARD
        |--------------------------------------------------------------------------
        */

        .feature-card {
            transition:
                transform 0.3s ease,
                box-shadow 0.3s ease;

            border:
                none;

            border-radius:
                16px;

            box-shadow:
                0 4px 14px rgba(0, 0, 0, 0.06);
        }


        .feature-card:hover {
            transform:
                translateY(-6px);

            box-shadow:
                0 14px 28px rgba(30, 60, 114, 0.15);
        }


        .feature-card .display-5 {
            width:
                72px;

            height:
                72px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            margin:
                0 auto 16px;

            border-radius:
                50%;

            background:
                linear-gradient(
                    135deg,
                    rgba(30, 60, 114, 0.1),
                    rgba(42, 82, 152, 0.1)
                );
        }


        /*
        |--------------------------------------------------------------------------
        | AREA CARD
        |--------------------------------------------------------------------------
        */

        .area-card {
            border-radius:
                14px;

            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease;
        }


        .area-card:hover {
            transform:
                translateY(-4px);

            box-shadow:
                0 12px 24px rgba(0, 0, 0, 0.10) !important;
        }


        .badge.bg-success {
            border-radius:
                20px;

            padding:
                6px 12px;

            font-weight:
                500;
        }


        /*
        |--------------------------------------------------------------------------
        | LOGIN USER INFO
        |--------------------------------------------------------------------------
        */

        .user-menu {
            display:
                flex;

            align-items:
                center;

            gap:
                8px;
        }


        .user-avatar {
            width:
                32px;

            height:
                32px;

            border-radius:
                50%;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                var(--brand-gold);

            color:
                #111;

            font-weight:
                700;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        footer {
            background-color:
                var(--brand-dark) !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 576px) {

            .video-wrapper {
                border-radius:
                    12px;
            }


            .hero-section h1 {
                font-size:
                    2.1rem;
            }


            .stat-box {
                padding:
                    18px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">

    <div class="container">


        <a
            class="navbar-brand fw-bold"
            href="dashboard.php"
        >

            <i class="fa-solid fa-square-parking text-warning me-2"></i>

            E-Parkir

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Buka menu"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="navbarNav"
        >

            <ul class="navbar-nav ms-auto align-items-center">


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="dashboard.php"
                    >
                        Beranda
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#video"
                    >
                        Video Profil
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#fitur"
                    >
                        Fitur
                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#area"
                    >
                        Area Parkir
                    </a>

                </li>


                <?php if ($is_login): ?>

                    <!-- USER LOGIN -->

                    <li class="nav-item dropdown ms-lg-2">

                        <a
                            class="nav-link dropdown-toggle user-menu"
                            href="#"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >

                            <span class="user-avatar">

                                <?= htmlspecialchars(
                                    strtoupper(
                                        substr(
                                            $nama,
                                            0,
                                            1
                                        )
                                    )
                                ) ?>

                            </span>

                            <span>

                                <?= htmlspecialchars($nama) ?>

                            </span>

                        </a>


                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>

                                <h6 class="dropdown-header">

                                    <?= htmlspecialchars($role_label) ?>

                                </h6>

                            </li>


                            <li>

                                <hr class="dropdown-divider">

                            </li>


                            <li>

                                <a
                                    class="dropdown-item"
                                    href="<?= htmlspecialchars($dashboard_url) ?>"
                                >

                                    <i class="fa-solid fa-gauge me-2"></i>

                                    Dashboard Saya

                                </a>

                            </li>


                            <?php if (
                                $role === 'pengguna' ||
                                $role === 'karyawan'
                            ): ?>

                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="<?= $role === 'karyawan'
                                            ? 'karyawan/kendaraan_saya.php'
                                            : 'pengguna/kendaraan_saya.php'
                                        ?>"
                                    >

                                        <i class="fa-solid fa-car me-2"></i>

                                        Kendaraan Saya

                                    </a>

                                </li>


                                <li>

                                    <a
                                        class="dropdown-item"
                                        href="<?= $role === 'karyawan'
                                            ? 'karyawan/pesan_tempat.php'
                                            : 'pengguna/pesan_tempat.php'
                                        ?>"
                                    >

                                        <i class="fa-solid fa-calendar-check me-2"></i>

                                        Booking Parkir

                                    </a>

                                </li>

                            <?php endif; ?>


                            <li>

                                <hr class="dropdown-divider">

                            </li>


                            <li>

                                <a
                                    class="dropdown-item text-danger"
                                    href="logout.php"
                                >

                                    <i class="fa-solid fa-right-from-bracket me-2"></i>

                                    Logout

                                </a>

                            </li>

                        </ul>

                    </li>


                <?php else: ?>

                    <!-- BELUM LOGIN -->

                    <li class="nav-item ms-lg-2">

                        <a
                            class="btn btn-outline-light btn-sm me-2"
                            href="login_pengguna.php"
                        >

                            <i class="fa-solid fa-user me-1"></i>

                            Masuk Pengguna

                        </a>

                    </li>


                    <li class="nav-item">

                        <a
                            class="btn btn-warning btn-sm fw-semibold"
                            href="register.php"
                        >

                            Daftar

                        </a>

                    </li>


                    <!-- LOGIN PETUGAS -->

                    <li class="nav-item ms-lg-3 border-start ps-lg-3">

                        <a
                            class="btn btn-sm btn-secondary"
                            href="login.php"
                            title="Login Admin / Petugas / Owner"
                        >

                            <i class="fa-solid fa-user-shield me-1"></i>

                            Login Petugas

                        </a>

                    </li>


                    <!-- LOGIN KARYAWAN -->

                    <li class="nav-item ms-lg-2">

                        <a
                            class="btn btn-sm btn-warning fw-semibold"
                            href="login_karyawan.php"
                            title="Login Karyawan Rumah Sakit"
                        >

                            <i class="fa-solid fa-id-badge me-1"></i>

                            Login Karyawan

                        </a>

                    </li>

                <?php endif; ?>


            </ul>

        </div>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero-section text-center text-lg-start">

    <div class="container">

        <div class="row align-items-center">


            <div class="col-lg-6 mb-4 mb-lg-0">


                <?php if ($is_login): ?>

                    <span class="badge bg-warning text-dark mb-3 px-3 py-2">

                        <i class="fa-solid fa-circle-check me-1"></i>

                        <?= htmlspecialchars($role_label) ?>

                    </span>

                <?php endif; ?>


                <h1 class="display-4 fw-bold mb-3">

                    Solusi Parkir Cerdas,
                    Cepat & Aman

                </h1>


                <p class="lead mb-4 text-white-50">

                    Sistem manajemen parkir rumah sakit
                    yang memudahkan proses booking,
                    pengelolaan kendaraan,
                    dan riwayat parkir.

                </p>


                <div
                    class="d-sm-flex justify-content-center justify-content-lg-start gap-3"
                >

                    <?php if ($is_login): ?>

                        <a
                            href="<?= htmlspecialchars($dashboard_url) ?>"
                            class="btn btn-warning btn-lg fw-bold mb-2 mb-sm-0"
                        >

                            <i class="fa-solid fa-gauge me-2"></i>

                            Dashboard Saya

                        </a>

                    <?php else: ?>

                        <a
                            href="login_pengguna.php"
                            class="btn btn-warning btn-lg fw-bold mb-2 mb-sm-0"
                        >

                            <i class="fa-solid fa-calendar-check me-2"></i>

                            Pesan Tempat Sekarang

                        </a>

                    <?php endif; ?>


                    <a
                        href="#area"
                        class="btn btn-outline-light btn-lg"
                    >

                        Lihat Area Parkir

                    </a>

                </div>

            </div>


            <!-- STATISTIK -->

            <div class="col-lg-6">

                <div class="row g-3">


                    <div class="col-6">

                        <div class="stat-box text-center">

                            <h2 class="fw-bold text-warning">

                                <?= $total_area ?>

                            </h2>

                            <p class="mb-0 small">

                                Area Parkir

                            </p>

                        </div>

                    </div>


                    <div class="col-6">

                        <div class="stat-box text-center">

                            <h2 class="fw-bold text-warning">

                                <?= $total_kendaraan ?>

                            </h2>

                            <p class="mb-0 small">

                                Kendaraan Terdaftar

                            </p>

                        </div>

                    </div>


                    <div class="col-12">

                        <div class="stat-box text-center">

                            <h2 class="fw-bold text-warning">

                                <?= number_format($total_transaksi) ?>+

                            </h2>

                            <p class="mb-0 small">

                                Total Transaksi

                            </p>

                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     VIDEO PROFIL
========================================================= -->

<section
    id="video"
    class="py-5"
>

    <div class="container py-4">


        <div class="text-center mb-4">

            <h2 class="section-title">

                Kenali E-Parkir Lebih Dekat

            </h2>

            <p class="text-muted">

                Tonton video singkat tentang layanan
                parkir rumah sakit.

            </p>

        </div>


        <div class="video-wrapper">

            <video
                controls
                preload="metadata"
                poster="rsjbg.jpeg"
            >

                <source
                    src="grhasia.mp4"
                    type="video/mp4"
                >

                Browser Anda tidak mendukung
                pemutaran video.

            </video>

        </div>


    </div>

</section>



<!-- =========================================================
     FITUR
========================================================= -->

<section
    id="fitur"
    class="py-5 bg-light"
>

    <div class="container py-4">


        <div class="text-center mb-5">

            <h2 class="section-title">

                Layanan Utama Kami

            </h2>

            <p class="text-muted">

                Kemudahan akses untuk kebutuhan
                perparkiran rumah sakit.

            </p>

        </div>


        <div class="row g-4">


            <!-- BOOKING -->

            <div class="col-md-4">

                <div
                    class="card feature-card h-100 p-4 text-center"
                >

                    <div
                        class="display-5 text-primary mb-3"
                    >

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>


                    <h5 class="fw-bold">

                        Booking Slot Online

                    </h5>


                    <p class="text-muted small">

                        Amankan tempat parkir sebelum
                        tiba di rumah sakit melalui
                        sistem pemesanan online.

                    </p>


                    <a
                        href="<?= $is_login
                            ? (
                                $role === 'karyawan'
                                    ? 'karyawan/pesan_tempat.php'
                                    : (
                                        $role === 'pengguna'
                                            ? 'pengguna/pesan_tempat.php'
                                            : $dashboard_url
                                    )
                            )
                            : 'login_pengguna.php'
                        ?>"
                        class="mt-auto text-decoration-none text-primary fw-semibold"
                    >

                        Pesan Slot &rarr;

                    </a>

                </div>

            </div>



            <!-- KENDARAAN -->

            <div class="col-md-4">

                <div
                    class="card feature-card h-100 p-4 text-center"
                >

                    <div
                        class="display-5 text-primary mb-3"
                    >

                        <i class="fa-solid fa-car"></i>

                    </div>


                    <h5 class="fw-bold">

                        Kelola Kendaraan

                    </h5>


                    <p class="text-muted small">

                        Daftarkan kendaraan Anda
                        untuk mempermudah proses
                        identifikasi parkir.

                    </p>


                    <a
                        href="<?= $is_login
                            ? (
                                $role === 'karyawan'
                                    ? 'karyawan/kendaraan_saya.php'
                                    : (
                                        $role === 'pengguna'
                                            ? 'pengguna/kendaraan_saya.php'
                                            : $dashboard_url
                                    )
                            )
                            : 'login_pengguna.php'
                        ?>"
                        class="mt-auto text-decoration-none text-primary fw-semibold"
                    >

                        Kelola Kendaraan &rarr;

                    </a>

                </div>

            </div>



            <!-- RIWAYAT -->

            <div class="col-md-4">

                <div
                    class="card feature-card h-100 p-4 text-center"
                >

                    <div
                        class="display-5 text-primary mb-3"
                    >

                        <i class="fa-solid fa-clock-rotate-left"></i>

                    </div>


                    <h5 class="fw-bold">

                        Riwayat Parkir

                    </h5>


                    <p class="text-muted small">

                        Cek riwayat penggunaan
                        parkir dan transaksi Anda
                        kapan saja.

                    </p>


                    <a
                        href="<?= $is_login
                            ? (
                                $role === 'karyawan'
                                    ? 'karyawan/riwayat.php'
                                    : (
                                        $role === 'pengguna'
                                            ? 'pengguna/riwayat.php'
                                            : $dashboard_url
                                    )
                            )
                            : 'login_pengguna.php'
                        ?>"
                        class="mt-auto text-decoration-none text-primary fw-semibold"
                    >

                        Cek Riwayat &rarr;

                    </a>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =========================================================
     AREA PARKIR
========================================================= -->

<section
    id="area"
    class="py-5"
>

    <div class="container py-4">


        <div
            class="d-flex justify-content-between align-items-end mb-4"
        >

            <div>

                <h2 class="section-title mb-1">

                    Daftar Area Parkir

                </h2>

                <p class="text-muted mb-0">

                    Lokasi area parkir yang tersedia
                    saat ini.

                </p>

            </div>


            <a
                href="cek_area.php"
                class="btn btn-outline-primary btn-sm"
            >

                Lihat Semua Area

            </a>

        </div>


        <div class="row g-4">


            <?php if (
                $query_area &&
                $query_area->num_rows > 0
            ): ?>


                <?php while (
                    $area = $query_area->fetch_assoc()
                ): ?>


                    <div class="col-md-4">

                        <div
                            class="card area-card border-0 shadow-sm h-100"
                        >

                            <div class="card-body">


                                <div
                                    class="d-flex justify-content-between align-items-center mb-2"
                                >

                                    <h5
                                        class="card-title fw-bold mb-0"
                                    >

                                        <?= htmlspecialchars(
                                            $area['nama_area']
                                            ?? 'Area Parkir'
                                        ) ?>

                                    </h5>


                                    <span
                                        class="badge bg-success"
                                    >

                                        Tersedia

                                    </span>

                                </div>


                                <p
                                    class="card-text text-muted small"
                                >

                                    <i
                                        class="fa-solid fa-location-dot me-1"
                                    ></i>

                                    Kapasitas:

                                    <?= htmlspecialchars(
                                        $area['kapasitas']
                                        ?? '-'
                                    ) ?>

                                    Slot

                                </p>


                                <?php if ($is_login): ?>


                                    <?php if (
                                        $role === 'pengguna'
                                    ): ?>

                                        <a
                                            href="pengguna/pesan_tempat.php?area_id=<?= (int) ($area['id_area'] ?? 0) ?>"
                                            class="btn btn-sm btn-primary w-100"
                                        >

                                            <i
                                                class="fa-solid fa-calendar-check me-1"
                                            ></i>

                                            Pesan di Area Ini

                                        </a>


                                    <?php elseif (
                                        $role === 'karyawan'
                                    ): ?>

                                        <a
                                            href="karyawan/pesan_tempat.php?area_id=<?= (int) ($area['id_area'] ?? 0) ?>"
                                            class="btn btn-sm btn-primary w-100"
                                        >

                                            <i
                                                class="fa-solid fa-calendar-check me-1"
                                            ></i>

                                            Pesan di Area Ini

                                        </a>


                                    <?php else: ?>


                                        <a
                                            href="<?= htmlspecialchars($dashboard_url) ?>"
                                            class="btn btn-sm btn-outline-primary w-100"
                                        >

                                            <i
                                                class="fa-solid fa-gauge me-1"
                                            ></i>

                                            Buka Dashboard

                                        </a>


                                    <?php endif; ?>


                                <?php else: ?>


                                    <a
                                        href="login_pengguna.php"
                                        class="btn btn-sm btn-primary w-100"
                                    >

                                        <i
                                            class="fa-solid fa-right-to-bracket me-1"
                                        ></i>

                                        Login untuk Booking

                                    </a>


                                <?php endif; ?>


                            </div>

                        </div>

                    </div>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="col-12">

                    <div
                        class="alert alert-info text-center"
                    >

                        <i
                            class="fa-solid fa-circle-info me-1"
                        ></i>

                        Belum ada data area parkir
                        yang ditambahkan.

                    </div>

                </div>


            <?php endif; ?>


        </div>

    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="text-white pt-4 pb-3">

    <div class="container text-center">

        <p class="small text-white-50 mb-0">

            &copy;

            <?= date('Y') ?>

            Sistem Manajemen Parkir Rumah Sakit.

            All rights reserved.

        </p>

    </div>

</footer>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>
