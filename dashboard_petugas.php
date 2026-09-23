
<?php

session_start();

require_once "db.php";

/* =========================================================
   CEK LOGIN
   ========================================================= */
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama    = $_SESSION['nama_lengkap'] ?? 'Petugas Parkir';
$role    = $_SESSION['role'] ?? '';

/* =========================================================
   PROTEKSI ROLE
   ========================================================= */
if ($role !== 'petugas') {
    header("Location: dashboard.php");
    exit;
}

$inisial = strtoupper(substr(trim($nama), 0, 1));

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Petugas - E-Parkir</title>

<style>

/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #212529;
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {

    width: 250px;

    background: #1e293b;

    color: #fff;

    display: flex;

    flex-direction: column;

    position: fixed;

    top: 0;
    bottom: 0;
    left: 0;

    z-index: 2000;

    box-shadow: 3px 0 15px rgba(0,0,0,.15);
}


/* BRAND */

.sidebar .brand {

    padding: 20px;

    font-size: 20px;

    font-weight: bold;

    background: #0f172a;

    border-bottom: 1px solid #334155;

    text-align: center;
}


/* USER INFO */

.sidebar .user-info {

    padding: 15px 20px;

    background: #1e293b;

    border-bottom: 1px solid #334155;

    font-size: 13px;

    color: #94a3b8;
}

.sidebar .user-info b {

    color: #fff;

    display: block;

    font-size: 15px;

    margin-top: 3px;

    margin-bottom: 8px;
}


/* NAVIGATION */

.sidebar .nav-links {

    list-style: none;

    padding: 15px 0;

    flex-grow: 1;

    overflow-y: auto;
}

.sidebar .nav-links li a {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 20px;

    color: #cbd5e1;

    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}

.sidebar .nav-links li a:hover,

.sidebar .nav-links li a.active {

    background: #007bff;

    color: #fff;
}

.sidebar .nav-links li a span {

    width: 22px;

    text-align: center;

    font-size: 17px;
}


/* LOGOUT */

.sidebar .logout-container {

    padding: 15px 20px;

    border-top: 1px solid #334155;
}

.sidebar .logout-btn {

    display: block;

    width: 100%;

    padding: 10px;

    background: #dc3545;

    color: white;

    text-decoration: none;

    text-align: center;

    border-radius: 5px;

    font-weight: bold;

    transition: .2s;
}

.sidebar .logout-btn:hover {

    background: #bd2130;
}


/* =========================================================
   MOBILE BUTTON
   ========================================================= */

.mobile-menu-btn {

    display: none;

    position: fixed;

    top: 15px;
    left: 15px;

    z-index: 3000;

    width: 45px;
    height: 45px;

    border: none;

    border-radius: 10px;

    background: #007bff;

    color: white;

    font-size: 22px;

    cursor: pointer;

    box-shadow: 0 4px 12px rgba(0,0,0,.2);
}


/* OVERLAY */

.overlay {

    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.45);

    z-index: 1500;
}


/* =========================================================
   MAIN CONTENT
   ========================================================= */

.main-content {

    margin-left: 250px;

    padding: 30px;

    min-height: 100vh;
}


/* =========================================================
   TOP HEADER
   ========================================================= */

.top-header {

    max-width: 1200px;

    margin: 0 auto 20px;

    background: white;

    padding: 18px 22px;

    border-radius: 12px;

    box-shadow: 0 3px 15px rgba(0,0,0,.06);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.top-header h1 {

    font-size: 23px;

    color: #212529;
}

.top-header p {

    margin-top: 5px;

    font-size: 13px;

    color: #6c757d;
}

.date {

    color: #6c757d;

    font-size: 13px;

    white-space: nowrap;
}


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    max-width: 1200px;

    margin: auto;
}


/* =========================================================
   WELCOME CARD
   ========================================================= */

.welcome {

    background: linear-gradient(
        135deg,
        #007bff,
        #0056b3
    );

    color: white;

    padding: 28px;

    border-radius: 15px;

    margin-bottom: 22px;

    box-shadow: 0 5px 20px rgba(0,123,255,.18);
}

.welcome h2 {

    font-size: 24px;

    margin-bottom: 8px;
}

.welcome p {

    font-size: 14px;

    line-height: 1.6;

    opacity: .95;
}


/* =========================================================
   MENU CARD
   ========================================================= */

.menu-title {

    background: white;

    padding: 20px;

    border-radius: 12px 12px 0 0;

    border-bottom: 1px solid #e9ecef;
}

.menu-title h2 {

    font-size: 19px;

    color: #343a40;
}

.menu-title p {

    margin-top: 5px;

    color: #6c757d;

    font-size: 13px;
}


.menu-grid {

    background: white;

    padding: 20px;

    border-radius: 0 0 12px 12px;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;
}


/* MENU */

.menu-card {

    display: block;

    text-decoration: none;

    color: #212529;

    border: 1px solid #e9ecef;

    border-radius: 10px;

    padding: 22px;

    background: #fff;

    transition: .25s;

    min-height: 150px;
}

.menu-card:hover {

    transform: translateY(-4px);

    border-color: #007bff;

    box-shadow:
        0 6px 18px
        rgba(0,123,255,.12);
}


/* ICON */

.menu-icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e7f1ff;

    border-radius: 10px;

    font-size: 22px;

    margin-bottom: 15px;
}

.menu-card h3 {

    font-size: 16px;

    margin-bottom: 7px;

    color: #212529;
}

.menu-card p {

    font-size: 12px;

    color: #6c757d;

    line-height: 1.5;
}


/* =========================================================
   QUICK INFO
   ========================================================= */

.info-card {

    margin-top: 22px;

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.05);
}

.info-card h3 {

    margin-bottom: 10px;

    color: #343a40;
}

.info-card p {

    color: #6c757d;

    font-size: 13px;

    line-height: 1.6;
}


/* =========================================================
   FOOTER
   ========================================================= */

footer {

    max-width: 1200px;

    margin: 20px auto 0;

    text-align: center;

    color: #6c757d;

    font-size: 12px;

    padding: 15px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1000px) {

    .menu-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }
}


@media (max-width: 900px) {

    .sidebar {

        transform:
            translateX(-100%);

        transition: .3s ease;
    }

    .sidebar.open {

        transform:
            translateX(0);
    }

    .mobile-menu-btn {

        display: block;
    }

    .overlay.show {

        display: block;
    }

    .main-content {

        margin-left: 0;

        padding:
            75px 15px 25px;
    }

    .top-header {

        padding-left: 70px;
    }
}


@media (max-width: 600px) {

    .main-content {

        padding:
            70px 10px 20px;
    }

    .top-header {

        display: block;

        padding: 15px;

        padding-left: 65px;
    }

    .top-header h1 {

        font-size: 20px;
    }

    .date {

        margin-top: 8px;
    }

    .welcome {

        padding: 20px;
    }

    .welcome h2 {

        font-size: 20px;
    }

    .menu-grid {

        grid-template-columns: 1fr;

        padding: 15px;
    }

    .menu-card {

        min-height: auto;
    }
}

</style>

</head>

<body>


<!-- =========================================================
     MOBILE BUTTON
     ========================================================= -->

<button
    type="button"
    class="mobile-menu-btn"
    onclick="toggleSidebar()"
>
    ☰
</button>


<div
    class="overlay"
    id="overlay"
    onclick="toggleSidebar()"
></div>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside
    class="sidebar"
    id="sidebar"
>

    <div class="brand">
        🅿️ E-Parkir Petugas
    </div>


    <div class="user-info">

        Role:

        <b>
            <?= htmlspecialchars(
                strtoupper($role)
            ) ?>
        </b>

        User:

        <b>
            <?= htmlspecialchars($nama) ?>
        </b>

    </div>


    <ul class="nav-links">

        <!-- DASHBOARD -->

        <li>

            <a
                href="dashboard.php"
                class="active"
            >

                <span>🏠</span>

                Dashboard

            </a>

        </li>


        <!-- TRANSAKSI -->

        <li>

            <a href="petugas/transaksi.php">

                <span>🎫</span>

                Transaksi Parkir

            </a>

        </li>


        <!-- AREA -->

        <li>

            <a href="petugas/area.php">

                <span>🅿️</span>

                Area Parkir

            </a>

        </li>


        <!-- KENDARAAN -->

        <li>

            <a href="petugas/kendaraan.php">

                <span>🚗</span>

                Kelola Kendaraan

            </a>

        </li>


        <!-- LOG -->

        <li>

            <a href="petugas/log.php">

                <span>📋</span>

                Log Aktivitas

            </a>

        </li>

    </ul>


    <!-- LOGOUT -->

    <div class="logout-container">

        <a
            href="logout.php"
            class="logout-btn"
            onclick="
                return confirm(
                    'Yakin ingin logout?'
                );
            "
        >

            🚪 Logout

        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN
     ========================================================= -->

<main class="main-content">


    <!-- TOP HEADER -->

    <div class="top-header">

        <div>

            <h1>
                Dashboard Petugas
            </h1>

            <p>
                Sistem Informasi Pengelolaan Parkir
            </p>

        </div>

        <div class="date">

            📅
            05 September 2026
            •
            18:39

        </div>

    </div>


    <div class="container">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>

                Selamat datang,
                <?= htmlspecialchars($nama) ?>
                👋

            </h2>

            <p>

                Anda masuk sebagai
                <strong>
                    <?= htmlspecialchars(
                        strtoupper($role)
                    ) ?>
                </strong>.

                Gunakan menu di bawah atau sidebar
                untuk mengelola operasional E-Parkir.

            </p>

        </div>


        <!-- MENU -->

        <div class="menu-title">

            <h2>
                Menu Operasional
            </h2>

            <p>
                Pilih fitur yang ingin digunakan.
            </p>

        </div>


        <div class="menu-grid">


            <!-- TRANSAKSI -->

            <a
                href="petugas/transaksi.php"
                class="menu-card"
            >

                <div class="menu-icon">
                    🎫
                </div>

                <h3>
                    Transaksi Parkir
                </h3>

                <p>

                    Kelola kendaraan masuk,
                    kendaraan keluar,
                    durasi parkir,
                    dan biaya transaksi.

                </p>

            </a>




            <!-- KENDARAAN -->

            <a
                href="petugas/kendaraan.php"
                class="menu-card"
            >

                <div class="menu-icon">
                    🚗
                </div>

                <h3>
                    Kelola Kendaraan
                </h3>

                <p>

                    Tambah, edit, hapus,
                    dan melihat data
                    kendaraan.

                </p>

            </a>


            <!-- AREA -->

            <a
                href="petugas/area.php"
                class="menu-card"
            >

                <div class="menu-icon">
                    🅿️
                </div>

                <h3>
                    Area Parkir
                </h3>

                <p>

                    Mengelola area dan
                    tempat parkir yang
                    tersedia.

                </p>

            </a>


            <!-- LOG -->

            <a
                href="petugas/log.php"
                class="menu-card"
            >

                <div class="menu-icon">
                    📋
                </div>

                <h3>
                    Log Aktivitas
                </h3>

                <p>

                    Melihat riwayat aktivitas
                    sistem dan pengguna.

                </p>

            </a>



        </div>


        <!-- INFO -->

        <div class="info-card">

            <h3>
                ℹ️ Informasi Petugas
            </h3>

            <p>

                Anda dapat menggunakan sidebar
                untuk berpindah antar halaman
                sistem E-Parkir. Semua halaman
                operasional menggunakan desain
                navigasi yang sama.

            </p>

        </div>


    </div>


    <!-- FOOTER -->

    <footer>

            © 2026 E-Parkir — Panel Petugas | Muhammad Zaidan Rafa (SMK Sanden)

    </footer>


</main>


<script>

/* =========================================================
   SIDEBAR RESPONSIVE
   ========================================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById('sidebar');

    const overlay =
        document.getElementById('overlay');

    sidebar.classList.toggle('open');

    overlay.classList.toggle('show');

}


/* =========================================================
   TUTUP SIDEBAR SETELAH KLIK MENU DI HP
   ========================================================= */

document
    .querySelectorAll(
        '.sidebar .nav-links a'
    )
    .forEach(function(link) {

        link.addEventListener(
            'click',
            function() {

                if (
                    window.innerWidth <= 900
                ) {

                    document
                        .getElementById('sidebar')
                        .classList
                        .remove('open');

                    document
                        .getElementById('overlay')
                        .classList
                        .remove('show');

                }

            }
        );

    });

</script>

</body>
</html>
