
<?php

session_start();

require_once "db.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama    = $_SESSION['nama_lengkap'] ?? 'Karyawan';
$role    = $_SESSION['role'] ?? '';

/*
|--------------------------------------------------------------------------
| PROTEKSI ROLE
|--------------------------------------------------------------------------
*/

if ($role !== 'karyawan') {
    header("Location: dashboard.php");
    exit;
}

$inisial = strtoupper(substr($nama, 0, 1));

/*
|--------------------------------------------------------------------------
| DATA DASHBOARD
|--------------------------------------------------------------------------
*/

$totalKendaraan = 0;
$totalParkir    = 0;
$parkirAktif    = 0;
$totalRiwayat   = 0;

/*
|--------------------------------------------------------------------------
| FUNGSI HITUNG DATA
|--------------------------------------------------------------------------
*/

function getCount($conn, $sql, $id_user)
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        mysqli_stmt_close($stmt);
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int) ($row['total'] ?? 0);
}

/*
|--------------------------------------------------------------------------
| JUMLAH KENDARAAN KARYAWAN
|--------------------------------------------------------------------------
*/

$totalKendaraan = getCount(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM tb_kendaraan
        WHERE id_user = ?
    ",
    $id_user
);

/*
|--------------------------------------------------------------------------
| TOTAL TRANSAKSI PARKIR
|--------------------------------------------------------------------------
*/

$totalParkir = getCount(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM tb_transaksi t
        INNER JOIN tb_booking b
            ON t.id_booking = b.id_booking
        WHERE b.id_user = ?
    ",
    $id_user
);

/*
|--------------------------------------------------------------------------
| PARKIR AKTIF
|--------------------------------------------------------------------------
*/

$parkirAktif = getCount(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM tb_transaksi t
        INNER JOIN tb_booking b
            ON t.id_booking = b.id_booking
        WHERE b.id_user = ?
        AND t.waktu_keluar IS NULL
    ",
    $id_user
);

/*
|--------------------------------------------------------------------------
| RIWAYAT PARKIR SELESAI
|--------------------------------------------------------------------------
*/

$totalRiwayat = getCount(
    $conn,
    "
        SELECT COUNT(*) AS total
        FROM tb_transaksi t
        INNER JOIN tb_booking b
            ON t.id_booking = b.id_booking
        WHERE b.id_user = ?
        AND t.waktu_keluar IS NOT NULL
    ",
    $id_user
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Karyawan - Parkir Rumah Sakit</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background: #f4f7fb;
            color: #1f2937;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

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
            transition: transform 0.3s ease;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
        }

        .sidebar-header {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #0056b3
            );

            color: white;
            padding: 22px 20px;
        }

        .sidebar-header h2 {
            font-size: 1.15rem;
            margin-bottom: 18px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .user-name {
            font-weight: bold;
            font-size: 0.95rem;
            line-height: 1.3;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.85;
            letter-spacing: 0.5px;
        }

        /*
        |--------------------------------------------------------------------------
        | MENU
        |--------------------------------------------------------------------------
        */

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
            color: #374151;
            text-decoration: none;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-link .icon {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #eaf2ff;
            color: #0d6efd;
            border-left-color: #0d6efd;
        }

        .sidebar-footer {
            padding: 12px 0;
            border-top: 1px solid #eee;
        }

        .logout-link:hover {
            background: #fdeaea;
            color: #dc3545;
            border-left-color: #dc3545;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
        }

        /*
        |--------------------------------------------------------------------------
        | TOPBAR
        |--------------------------------------------------------------------------
        */

        .topbar {
            background: white;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .topbar h2 {
            font-size: 1.1rem;
            color: #333;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            width: 32px;
            height: 32px;
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

        /*
        |--------------------------------------------------------------------------
        | CONTAINER
        |--------------------------------------------------------------------------
        */

        .container {
            width: 92%;
            max-width: 1200px;
            margin: 30px auto;
        }

        /*
        |--------------------------------------------------------------------------
        | WELCOME
        |--------------------------------------------------------------------------
        */

        .welcome {
            background: linear-gradient(
                135deg,
                #0d6efd,
                #0056b3
            );

            color: white;
            padding: 28px;
            border-radius: 14px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.18);
        }

        .welcome h1 {
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .welcome p {
            opacity: 0.9;
        }

        /*
        |--------------------------------------------------------------------------
        | STATISTICS
        |--------------------------------------------------------------------------
        */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow:
                0 3px 12px rgba(0, 0, 0, 0.07);

            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #eaf2ff;
            flex-shrink: 0;
        }

        .stat-info span {
            display: block;
            color: #6b7280;
            font-size: 0.8rem;
            margin-bottom: 4px;
        }

        .stat-info strong {
            font-size: 1.5rem;
            color: #111827;
        }

        /*
        |--------------------------------------------------------------------------
        | MENU CARDS
        |--------------------------------------------------------------------------
        */

        .section-title {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #111827;
        }

        .menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            text-align: left;
            text-decoration: none;
            color: #333;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.07);

            transition: all 0.25s ease;
            border: 1px solid #f0f0f0;
        }

        .box:hover {
            transform: translateY(-5px);

            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.1);

            border-color: #0d6efd;
        }

        .box-icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }

        .box h3 {
            margin-bottom: 7px;
            font-size: 1rem;
        }

        .box p {
            color: #6b7280;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | OVERLAY
        |--------------------------------------------------------------------------
        */

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 900;
        }

        .overlay.active {
            display: block;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

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

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {

            .container {
                width: 94%;
                margin: 15px auto;
            }

            .topbar {
                padding: 12px 15px;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h1 {
                font-size: 1.3rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .menu {
                grid-template-columns: 1fr;
            }

            .sidebar {
                width: 85%;
                max-width: 300px;
            }
        }

    </style>

</head>

<body>

<div class="wrapper">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">

            <h2>🏥 Parkir Rumah Sakit</h2>

            <div class="user-info">

                <div class="avatar">
                    <?= htmlspecialchars($inisial) ?>
                </div>

                <div>

                    <p class="user-name">
                        <?= htmlspecialchars($nama) ?>
                    </p>

                    <span class="user-role">
                        KARYAWAN
                    </span>

                </div>

            </div>

        </div>


        <nav class="sidebar-menu">

            <a
                href="dashboard_karyawan.php"
                class="nav-link active"
            >
                <span class="icon">📊</span>
                Dashboard
            </a>


            <a
                href="karyawan/kendaraan_saya.php"
                class="nav-link"
            >
                <span class="icon">🚗</span>
                Kendaraan Saya
            </a>


            <a
                href="karyawan/pesan_tempat.php"
                class="nav-link"
            >
                <span class="icon">🅿️</span>
                Pesan Tempat Parkir
            </a>


            <a
                href="karyawan/parkir_aktif.php"
                class="nav-link"
            >
                <span class="icon">🟢</span>
                Parkir Aktif
            </a>


            <a
                href="karyawan/riwayat.php"
                class="nav-link"
            >
                <span class="icon">🕒</span>
                Riwayat Parkir
            </a>


            <a
                href="karyawan/profil.php"
                class="nav-link"
            >
                <span class="icon">👤</span>
                Profil Saya
            </a>


            <a
                href="karyawan/help.php"
                class="nav-link"
            >
                <span class="icon">❓</span>
                Bantuan
            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="index.php"
                class="nav-link"
            >
                <span class="icon">🏠</span>
                Landing Page
            </a>


            <a
                href="logout.php"
                class="nav-link logout-link"
            >
                <span class="icon">🚪</span>
                Logout
            </a>

        </div>

    </aside>


    <div
        class="overlay"
        id="overlay"
    ></div>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <div class="main-content">

        <header class="topbar">

            <button
                class="hamburger"
                id="hamburgerBtn"
                aria-label="Buka menu"
                type="button"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <h2>
                Dashboard Karyawan
            </h2>

        </header>


        <main class="container">

            <!-- WELCOME -->

            <section class="welcome">

                <h1>
                    Selamat datang,
                    <?= htmlspecialchars($nama) ?> 👋
                </h1>

                <p>
                    Selamat bekerja. Kelola kebutuhan parkir
                    kendaraan Anda melalui dashboard ini.
                </p>

            </section>


            <!-- STATISTICS -->

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-icon">
                        🚗
                    </div>

                    <div class="stat-info">

                        <span>
                            Kendaraan Saya
                        </span>

                        <strong>
                            <?= $totalKendaraan ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🅿️
                    </div>

                    <div class="stat-info">

                        <span>
                            Total Parkir
                        </span>

                        <strong>
                            <?= $totalParkir ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🟢
                    </div>

                    <div class="stat-info">

                        <span>
                            Parkir Aktif
                        </span>

                        <strong>
                            <?= $parkirAktif ?>
                        </strong>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        🕒
                    </div>

                    <div class="stat-info">

                        <span>
                            Riwayat Selesai
                        </span>

                        <strong>
                            <?= $totalRiwayat ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- MENU -->

            <h3 class="section-title">
                Menu Karyawan
            </h3>


            <div class="menu">

                <a
                    href="karyawan/kendaraan_saya.php"
                    class="box"
                >

                    <div class="box-icon">
                        🚗
                    </div>

                    <h3>
                        Kendaraan Saya
                    </h3>

                    <p>
                        Tambah, edit, dan kelola
                        kendaraan yang Anda gunakan.
                    </p>

                </a>


                <a
                    href="karyawan/pesan_tempat.php"
                    class="box"
                >

                    <div class="box-icon">
                        🅿️
                    </div>

                    <h3>
                        Pesan Tempat Parkir
                    </h3>

                    <p>
                        Pesan tempat parkir sebelum
                        datang ke rumah sakit.
                    </p>

                </a>


                <a
                    href="karyawan/parkir_aktif.php"
                    class="box"
                >

                    <div class="box-icon">
                        🟢
                    </div>

                    <h3>
                        Parkir Aktif
                    </h3>

                    <p>
                        Lihat informasi parkir Anda
                        yang sedang berlangsung.
                    </p>

                </a>


                <a
                    href="karyawan/riwayat.php"
                    class="box"
                >

                    <div class="box-icon">
                        🕒
                    </div>

                    <h3>
                        Riwayat Parkir
                    </h3>

                    <p>
                        Lihat seluruh riwayat
                        penggunaan parkir Anda.
                    </p>

                </a>


                <a
                    href="karyawan/profil.php"
                    class="box"
                >

                    <div class="box-icon">
                        👤
                    </div>

                    <h3>
                        Profil Saya
                    </h3>

                    <p>
                        Kelola informasi akun
                        karyawan Anda.
                    </p>

                </a>


                <a
                    href="karyawan/help.php"
                    class="box"
                >

                    <div class="box-icon">
                        ❓
                    </div>

                    <h3>
                        Bantuan
                    </h3>

                    <p>
                        Dapatkan informasi mengenai
                        sistem parkir rumah sakit.
                    </p>

                </a>

            </div>

        </main>

    </div>

</div>


<script>

const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');


function toggleSidebar() {

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');

}


hamburgerBtn.addEventListener(
    'click',
    toggleSidebar
);


overlay.addEventListener(
    'click',
    toggleSidebar
);


/*
|--------------------------------------------------------------------------
| Tutup sidebar setelah memilih menu di HP
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll('.sidebar .nav-link')
    .forEach(function (link) {

        link.addEventListener(
            'click',
            function () {

                if (window.innerWidth <= 992) {

                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');

                }

            }
        );

    });

</script>

</body>

</html>

