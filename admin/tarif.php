<?php

session_start();
require_once "../db.php";

/* =========================================================
   CEK LOGIN
   ========================================================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================================
   CEK ROLE ADMIN
   ========================================================= */

if (($_SESSION['role'] ?? '') !== "admin") {
    die("Akses ditolak!");
}

$nama = $_SESSION['nama_lengkap'] ?? 'Admin';
$role = $_SESSION['role'] ?? 'admin';

$error = "";

/* =========================================================
   FUNGSI REDIRECT
   ========================================================= */

function redirectTarif($status)
{
    header("Location: tarif.php?status=" . urlencode($status));
    exit;
}

/* =========================================================
   TAMBAH TARIF
   ========================================================= */

if (isset($_POST['tambah'])) {

    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $tarif_per_jam = (int) ($_POST['tarif_per_jam'] ?? 0);

    $jenis_valid = ['motor', 'mobil', 'lainnya'];

    if (!in_array($jenis_kendaraan, $jenis_valid, true)) {

        $error = "Jenis kendaraan tidak valid.";

    } elseif ($tarif_per_jam <= 0) {

        $error = "Tarif per jam harus lebih dari Rp 0.";

    } else {

        /* Cek apakah jenis kendaraan sudah ada */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_tarif
             FROM tb_tarif
             WHERE jenis_kendaraan = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $jenis_kendaraan
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $sudah_ada = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($sudah_ada) {

            $error = "Tarif untuk jenis kendaraan tersebut sudah ada.";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO tb_tarif
                (jenis_kendaraan, tarif_per_jam)
                VALUES (?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $jenis_kendaraan,
                $tarif_per_jam
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                redirectTarif("success_add");

            } else {

                $error = "Gagal menambah tarif: " . mysqli_error($conn);

                mysqli_stmt_close($stmt);
            }
        }
    }
}

/* =========================================================
   UPDATE TARIF
   ========================================================= */

if (isset($_POST['edit'])) {

    $id_tarif = (int) ($_POST['id_tarif'] ?? 0);
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $tarif_per_jam = (int) ($_POST['tarif_per_jam'] ?? 0);

    $jenis_valid = ['motor', 'mobil', 'lainnya'];

    if ($id_tarif <= 0) {

        $error = "ID tarif tidak valid.";

    } elseif (!in_array($jenis_kendaraan, $jenis_valid, true)) {

        $error = "Jenis kendaraan tidak valid.";

    } elseif ($tarif_per_jam <= 0) {

        $error = "Tarif per jam harus lebih dari Rp 0.";

    } else {

        /* Cek duplikasi jenis kendaraan */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_tarif
             FROM tb_tarif
             WHERE jenis_kendaraan = ?
             AND id_tarif != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $jenis_kendaraan,
            $id_tarif
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $duplikat = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if ($duplikat) {

            $error = "Jenis kendaraan tersebut sudah memiliki tarif.";

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE tb_tarif
                 SET jenis_kendaraan = ?,
                     tarif_per_jam = ?
                 WHERE id_tarif = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sii",
                $jenis_kendaraan,
                $tarif_per_jam,
                $id_tarif
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                redirectTarif("success_update");

            } else {

                $error = "Gagal memperbarui tarif: " . mysqli_error($conn);

                mysqli_stmt_close($stmt);
            }
        }
    }
}

/* =========================================================
   HAPUS TARIF
   ========================================================= */

if (isset($_GET['hapus'])) {

    $id_tarif = (int) $_GET['hapus'];

    if ($id_tarif > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM tb_tarif
             WHERE id_tarif = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id_tarif
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            redirectTarif("success_delete");

        } else {

            $error = "Gagal menghapus tarif: " . mysqli_error($conn);

            mysqli_stmt_close($stmt);
        }
    }
}

/* =========================================================
   DATA EDIT
   ========================================================= */

$edit_data = null;

if (isset($_GET['edit'])) {

    $id_edit = (int) $_GET['edit'];

    if ($id_edit > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT *
             FROM tb_tarif
             WHERE id_tarif = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id_edit
        );

        mysqli_stmt_execute($stmt);

        $result_edit = mysqli_stmt_get_result($stmt);

        $edit_data = mysqli_fetch_assoc($result_edit);

        mysqli_stmt_close($stmt);
    }
}

/* =========================================================
   AMBIL SEMUA DATA TARIF
   ========================================================= */

$data_tarif = mysqli_query(
    $conn,
    "SELECT *
     FROM tb_tarif
     ORDER BY id_tarif DESC"
);

if (!$data_tarif) {
    die("Query tarif gagal: " . mysqli_error($conn));
}

/* =========================================================
   STATISTIK
   ========================================================= */

$total_tarif = 0;
$total_nilai = 0;

$tarif_motor = null;
$tarif_mobil = null;
$tarif_lainnya = null;

$stat_query = mysqli_query(
    $conn,
    "SELECT jenis_kendaraan, tarif_per_jam
     FROM tb_tarif"
);

while ($stat = mysqli_fetch_assoc($stat_query)) {

    $total_tarif++;

    $total_nilai += (int) $stat['tarif_per_jam'];

    if ($stat['jenis_kendaraan'] === 'motor') {
        $tarif_motor = (int) $stat['tarif_per_jam'];
    }

    if ($stat['jenis_kendaraan'] === 'mobil') {
        $tarif_mobil = (int) $stat['tarif_per_jam'];
    }

    if ($stat['jenis_kendaraan'] === 'lainnya') {
        $tarif_lainnya = (int) $stat['tarif_per_jam'];
    }
}

$rata_rata = $total_tarif > 0
    ? round($total_nilai / $total_tarif)
    : 0;

/* =========================================================
   DATA GRAFIK
   ========================================================= */

$label = [];
$data = [];

$grafik = mysqli_query(
    $conn,
    "SELECT jenis_kendaraan, tarif_per_jam
     FROM tb_tarif
     ORDER BY FIELD(
         jenis_kendaraan,
         'motor',
         'mobil',
         'lainnya'
     )"
);

while ($g = mysqli_fetch_assoc($grafik)) {

    $nama_jenis = ucfirst(
        htmlspecialchars($g['jenis_kendaraan'])
    );

    $label[] = $nama_jenis;
    $data[] = (int) $g['tarif_per_jam'];
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
   content="width=device-width, initial-scale=1.0">

<title>Kelola Tarif | E-Parkir</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f1f5f9;
    color: #1e293b;
    min-height: 100vh;
}

/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {
    width: 250px;
    background: #0f172a;
    color: white;

    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    display: flex;
    flex-direction: column;

    z-index: 1000;

    box-shadow:
        4px 0 15px rgba(0,0,0,.08);
}

.brand {
    padding: 22px 18px;

    background: #020617;

    text-align: center;

    font-size: 20px;
    font-weight: 800;

    border-bottom:
        1px solid #1e293b;
}

.user-info {
    padding: 18px 20px;

    border-bottom:
        1px solid #1e293b;

    color: #94a3b8;

    font-size: 12px;
}

.user-info b {
    display: block;

    color: white;

    font-size: 14px;

    margin:
        4px 0 10px;
}

.nav-links {
    list-style: none;

    padding: 15px 10px;

    flex: 1;

    overflow-y: auto;
}

.nav-links li {
    margin-bottom: 4px;
}

.nav-links a {
    display: flex;

    align-items: center;

    gap: 12px;

    padding: 12px 14px;

    color: #cbd5e1;

    text-decoration: none;

    border-radius: 8px;

    font-size: 14px;

    transition: .2s;
}

.nav-links a:hover,
.nav-links a.active {
    background: #2563eb;
    color: white;

    transform:
        translateX(2px);
}

.nav-icon {
    width: 25px;
    text-align: center;
}

/* =========================================================
   LOGOUT
   ========================================================= */

.logout-container {
    padding: 15px;

    border-top:
        1px solid #1e293b;
}

.logout-btn {
    display: block;

    width: 100%;

    padding: 11px;

    background: #dc2626;

    color: white;

    text-decoration: none;

    text-align: center;

    border-radius: 8px;

    font-weight: bold;

    transition: .2s;
}

.logout-btn:hover {
    background: #b91c1c;
}

/* =========================================================
   MAIN
   ========================================================= */

.main-content {
    margin-left: 250px;

    min-height: 100vh;
}

/* =========================================================
   HEADER
   ========================================================= */

header {
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color: white;

    padding: 28px 35px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);
}

.header-content {
    max-width: 1250px;
    margin: auto;
}

header h1 {
    font-size: 26px;
    margin-bottom: 7px;
}

header p {
    color: #dbeafe;
    font-size: 14px;
}

/* =========================================================
   CONTAINER
   ========================================================= */

.container {
    width: 100%;

    max-width: 1250px;

    margin: auto;

    padding: 30px;
}

/* =========================================================
   ALERT
   ========================================================= */

.alert {
    padding: 14px 17px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-weight: 600;

    display: flex;

    align-items: center;

    gap: 10px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;

    border:
        1px solid #bbf7d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;

    border:
        1px solid #fecaca;
}

/* =========================================================
   STATISTICS
   ========================================================= */

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}

.stat-card {
    background: white;

    padding: 20px;

    border-radius: 14px;

    box-shadow:
        0 5px 18px rgba(15,23,42,.07);

    border:
        1px solid #e2e8f0;
}

.stat-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 15px;
}

.stat-icon {
    width: 45px;
    height: 45px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: #eff6ff;

    font-size: 22px;
}

.stat-title {
    color: #64748b;

    font-size: 13px;

    font-weight: 600;
}

.stat-value {
    font-size: 22px;

    font-weight: 800;

    color: #0f172a;
}

/* =========================================================
   CARD
   ========================================================= */

.card {
    background: white;

    padding: 24px;

    border-radius: 14px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 18px rgba(15,23,42,.07);

    border:
        1px solid #e2e8f0;
}

.card-title {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

    gap: 15px;
}

.card-title h2 {
    font-size: 19px;

    color: #0f172a;
}

.card-title p {
    color: #64748b;

    font-size: 13px;

    margin-top: 5px;
}

/* =========================================================
   FORM
   ========================================================= */

.form-grid {
    display: grid;

    grid-template-columns:
        1fr 1fr auto;

    gap: 16px;

    align-items: end;
}

.form-group label {
    display: block;

    margin-bottom: 8px;

    font-size: 13px;

    font-weight: 700;

    color: #334155;
}

.form-control {
    width: 100%;

    padding: 12px 13px;

    border:
        1px solid #cbd5e1;

    border-radius: 9px;

    background: white;

    font-size: 14px;

    outline: none;

    transition: .2s;
}

.form-control:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.1);
}

/* =========================================================
   BUTTON
   ========================================================= */

.btn {
    border: none;

    padding: 11px 16px;

    border-radius: 8px;

    cursor: pointer;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    font-size: 13px;

    font-weight: 700;

    transition: .2s;
}

.btn:hover {
    transform:
        translateY(-1px);
}

.btn-primary {
    background: #2563eb;
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-warning {
    background: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background: #d97706;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn-secondary {
    background: #64748b;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
}

/* =========================================================
   TARIF CARDS
   ========================================================= */

.tarif-cards {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}

.tarif-card {
    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    padding: 20px;

    background:
        linear-gradient(
            145deg,
            #ffffff,
            #f8fafc
        );
}

.tarif-card-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 15px;
}

.vehicle-icon {
    font-size: 28px;
}

.status-badge {
    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

    background: #dcfce7;

    color: #166534;
}

.tarif-card h3 {
    font-size: 15px;

    color: #475569;

    margin-bottom: 7px;
}

.tarif-price {
    font-size: 23px;

    font-weight: 800;

    color: #2563eb;
}

.not-available {
    color: #94a3b8;

    font-size: 14px;
}

/* =========================================================
   TABLE TOOLBAR
   ========================================================= */

.table-toolbar {
    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-bottom: 18px;
}

.search-box {
    position: relative;

    max-width: 320px;

    width: 100%;
}

.search-box input {
    width: 100%;

    padding: 11px 13px 11px 40px;

    border:
        1px solid #cbd5e1;

    border-radius: 9px;

    outline: none;

    font-size: 13px;
}

.search-box span {
    position: absolute;

    left: 13px;

    top: 10px;

    font-size: 16px;

    color: #64748b;
}

/* =========================================================
   TABLE
   ========================================================= */

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;

    min-width: 650px;
}

thead th {
    background: #f8fafc;

    color: #475569;

    padding: 14px;

    text-align: left;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: .4px;

    border-bottom:
        1px solid #e2e8f0;
}

tbody td {
    padding: 15px 14px;

    border-bottom:
        1px solid #e2e8f0;

    font-size: 14px;
}

tbody tr:hover {
    background: #f8fafc;
}

.vehicle-badge {
    display: inline-flex;

    align-items: center;

    gap: 8px;

    padding: 6px 10px;

    border-radius: 7px;

    background: #eff6ff;

    color: #1d4ed8;

    font-weight: 700;

    font-size: 12px;
}

.price {
    font-weight: 800;

    color: #0f172a;
}

.action-buttons {
    display: flex;

    gap: 7px;

    flex-wrap: wrap;
}

.action-buttons .btn {
    padding: 8px 11px;

    font-size: 12px;
}

/* =========================================================
   EMPTY
   ========================================================= */

.empty-state {
    text-align: center;

    padding: 40px 20px;

    color: #64748b;
}

.empty-icon {
    font-size: 42px;

    margin-bottom: 10px;
}

/* =========================================================
   CHART
   ========================================================= */

.chart-wrapper {
    position: relative;

    width: 100%;

    height: 330px;
}

/* =========================================================
   FOOTER
   ========================================================= */

.back-wrapper {
    margin-top: 5px;

    padding-top: 20px;

    border-top:
        1px solid #e2e8f0;
}

/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1000px) {

    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .tarif-cards {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .form-grid {
        grid-template-columns:
            1fr 1fr;
    }

}

@media (max-width: 768px) {

    .sidebar {
        position: relative;

        width: 100%;

        height: auto;
    }

    .main-content {
        margin-left: 0;
    }

    .nav-links {
        max-height: 350px;
    }

    header {
        padding: 22px 18px;
    }

    header h1 {
        font-size: 21px;
    }

    .container {
        padding: 18px 14px;
    }

    .stats-grid,
    .tarif-cards {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 18px;
    }

    .table-toolbar {
        align-items: stretch;

        flex-direction: column;
    }

    .search-box {
        max-width: none;
    }

    .chart-wrapper {
        height: 280px;
    }

}

</style>

</head>

<body>

<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

<div class="brand">
    🅿️ E-Parkir
</div>

<div class="user-info">

    Role

    <b>
        <?= strtoupper(
            htmlspecialchars($role)
        ); ?>
    </b>

    Pengguna

    <b>
        <?= htmlspecialchars($nama); ?>
    </b>

</div>

<ul class="nav-links">

    <li>
        <a href="../dashboard.php">

            <span class="nav-icon">🏠</span>

            Dashboard

        </a>
    </li>

    <li>
        <a href="user.php">

            <span class="nav-icon">👤</span>

            Kelola User

        </a>
    </li>

    <li>
        <a
            href="tarif.php"
            class="active"
        >

            <span class="nav-icon">💰</span>

            Kelola Tarif

        </a>
    </li>


    <li>
        <a href="area.php">

            <span class="nav-icon">🅿️</span>

            Area Parkir

        </a>
    </li>

    <li>
        <a href="kendaraan.php">

            <span class="nav-icon">🚗</span>

            Kelola Kendaraan

        </a>
    </li>

    <li>
        <a href="log.php">

            <span class="nav-icon">📋</span>

            Log Aktivitas

        </a>
    </li>

</ul>

<div class="logout-container">

    <a
        href="../logout.php"
        class="logout-btn"
    >
        🚪 Logout
    </a>

</div>

</aside>

<!-- =====================================================
     MAIN
===================================================== -->

<main class="main-content">


<header>

    <div class="header-content">

        <h1>
            💰 Kelola Tarif Parkir
        </h1>

        <p>
            Atur tarif parkir berdasarkan jenis kendaraan.
        </p>

    </div>

</header>


<div class="container">

    <!-- =================================================
         ALERT
    ================================================= -->

    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'success_add'): ?>

            <div class="alert alert-success">
                ✅ Tarif berhasil ditambahkan.
            </div>

        <?php elseif ($_GET['status'] === 'success_update'): ?>

            <div class="alert alert-success">
                ✅ Tarif berhasil diperbarui.
            </div>

        <?php elseif ($_GET['status'] === 'success_delete'): ?>

            <div class="alert alert-success">
                🗑️ Tarif berhasil dihapus.
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTIK
    ================================================= -->

    <div class="stats-grid">

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Total Tarif
                </div>

                <div class="stat-icon">
                    💰
                </div>

            </div>

            <div class="stat-value">
                <?= $total_tarif; ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Tarif Motor
                </div>

                <div class="stat-icon">
                    🏍️
                </div>

            </div>

            <div class="stat-value">

                <?php if ($tarif_motor !== null): ?>

                    Rp <?= number_format(
                        $tarif_motor,
                        0,
                        ',',
                        '.'
                    ); ?>

                <?php else: ?>

                    -

                <?php endif; ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Tarif Mobil
                </div>

                <div class="stat-icon">
                    🚗
                </div>

            </div>

            <div class="stat-value">

                <?php if ($tarif_mobil !== null): ?>

                    Rp <?= number_format(
                        $tarif_mobil,
                        0,
                        ',',
                        '.'
                    ); ?>

                <?php else: ?>

                    -

                <?php endif; ?>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-title">
                    Rata-rata Tarif
                </div>

                <div class="stat-icon">
                    📊
                </div>

            </div>

            <div class="stat-value">

                Rp <?= number_format(
                    $rata_rata,
                    0,
                    ',',
                    '.'
                ); ?>

            </div>

        </div>

    </div>


    <!-- =================================================
         TARIF PER KENDARAAN
    ================================================= -->

    <div class="tarif-cards">

        <div class="tarif-card">

            <div class="tarif-card-top">

                <span class="vehicle-icon">
                    🏍️
                </span>

                <span class="status-badge">
                    <?= $tarif_motor !== null
                        ? 'Aktif'
                        : 'Belum diatur'; ?>
                </span>

            </div>

            <h3>
                Kendaraan Motor
            </h3>

            <?php if ($tarif_motor !== null): ?>

                <div class="tarif-price">
                    Rp <?= number_format(
                        $tarif_motor,
                        0,
                        ',',
                        '.'
                    ); ?>
                    <small>/ jam</small>
                </div>

            <?php else: ?>

                <div class="not-available">
                    Tarif belum tersedia
                </div>

            <?php endif; ?>

        </div>


        <div class="tarif-card">

            <div class="tarif-card-top">

                <span class="vehicle-icon">
                    🚗
                </span>

                <span class="status-badge">
                    <?= $tarif_mobil !== null
                        ? 'Aktif'
                        : 'Belum diatur'; ?>
                </span>

            </div>

            <h3>
                Kendaraan Mobil
            </h3>

            <?php if ($tarif_mobil !== null): ?>

                <div class="tarif-price">
                    Rp <?= number_format(
                        $tarif_mobil,
                        0,
                        ',',
                        '.'
                    ); ?>
                    <small>/ jam</small>
                </div>

            <?php else: ?>

                <div class="not-available">
                    Tarif belum tersedia
                </div>

            <?php endif; ?>

        </div>


        <div class="tarif-card">

            <div class="tarif-card-top">

                <span class="vehicle-icon">
                    🚐
                </span>

                <span class="status-badge">
                    <?= $tarif_lainnya !== null
                        ? 'Aktif'
                        : 'Belum diatur'; ?>
                </span>

            </div>

            <h3>
                Kendaraan Lainnya
            </h3>

            <?php if ($tarif_lainnya !== null): ?>

                <div class="tarif-price">
                    Rp <?= number_format(
                        $tarif_lainnya,
                        0,
                        ',',
                        '.'
                    ); ?>
                    <small>/ jam</small>
                </div>

            <?php else: ?>

                <div class="not-available">
                    Tarif belum tersedia
                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         FORM
    ================================================= -->

    <div class="card">

        <div class="card-title">

            <div>

                <h2>

                    <?= $edit_data
                        ? '✏️ Edit Tarif'
                        : '➕ Tambah Tarif'; ?>

                </h2>

                <p>
                    <?= $edit_data
                        ? 'Perbarui data tarif kendaraan.'
                        : 'Tambahkan tarif parkir baru.'; ?>
                </p>

            </div>

        </div>


        <form
            action="tarif.php"
            method="POST"
        >

            <?php if ($edit_data): ?>

                <input
                    type="hidden"
                    name="id_tarif"
                    value="<?= (int)
                        $edit_data['id_tarif']; ?>"
                >

            <?php endif; ?>


            <div class="form-grid">

                <div class="form-group">

                    <label for="jenis_kendaraan">
                        Jenis Kendaraan
                    </label>

                    <select
                        class="form-control"
                        name="jenis_kendaraan"
                        id="jenis_kendaraan"
                        required
                    >

                        <option value="">
                            -- Pilih Kendaraan --
                        </option>

                        <option
                            value="motor"
                            <?= (
                                $edit_data &&
                                $edit_data[
                                    'jenis_kendaraan'
                                ] === 'motor'
                            )
                            ? 'selected'
                            : ''; ?>
                        >
                            🏍️ Motor
                        </option>

                        <option
                            value="mobil"
                            <?= (
                                $edit_data &&
                                $edit_data[
                                    'jenis_kendaraan'
                                ] === 'mobil'
                            )
                            ? 'selected'
                            : ''; ?>
                        >
                            🚗 Mobil
                        </option>

                        <option
                            value="lainnya"
                            <?= (
                                $edit_data &&
                                $edit_data[
                                    'jenis_kendaraan'
                                ] === 'lainnya'
                            )
                            ? 'selected'
                            : ''; ?>
                        >
                            🚐 Lainnya
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="tarif_per_jam">
                        Tarif Per Jam
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        name="tarif_per_jam"
                        id="tarif_per_jam"

                        value="<?= $edit_data
                            ? (int)
                              $edit_data[
                                  'tarif_per_jam'
                              ]
                            : ''; ?>"

                        min="1"
                        step="100"

                        required

                        placeholder="Contoh: 3000"
                    >

                </div>


                <div>

                    <?php if ($edit_data): ?>

                        <button
                            type="submit"
                            name="edit"
                            class="btn btn-warning"
                        >
                            💾 Simpan
                        </button>

                        <a
                            href="tarif.php"
                            class="btn btn-secondary"
                        >
                            Batal
                        </a>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="tambah"
                            class="btn btn-primary"
                        >
                            ➕ Tambah Tarif
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        </form>

    </div>


    <!-- =================================================
         TABEL
    ================================================= -->

    <div class="card">

        <div class="card-title">

            <div>

                <h2>
                    📋 Daftar Tarif
                </h2>

                <p>
                    Semua tarif parkir yang tersimpan di MariaDB.
                </p>

            </div>

        </div>


        <div class="table-toolbar">

            <div class="search-box">

                <span>🔎</span>

                <input
                    type="text"
                    id="searchTarif"
                    placeholder="Cari jenis kendaraan..."
                    onkeyup="filterTarif()"
                >

            </div>

        </div>


        <div class="table-wrapper">

            <table id="tarifTable">

                <thead>

                    <tr>

                        <th width="8%">
                            No
                        </th>

                        <th>
                            Jenis Kendaraan
                        </th>

                        <th>
                            Tarif / Jam
                        </th>

                        <th width="25%">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                $no = 1;

                if (mysqli_num_rows($data_tarif) > 0):

                    while (
                        $row =
                        mysqli_fetch_assoc(
                            $data_tarif
                        )
                    ):

                        $jenis =
                            strtolower(
                                $row[
                                    'jenis_kendaraan'
                                ]
                            );

                        $icon = '🚐';

                        if ($jenis === 'motor') {
                            $icon = '🏍️';
                        } elseif ($jenis === 'mobil') {
                            $icon = '🚗';
                        }

                ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>

                            <span
                                class="vehicle-badge"
                            >

                                <?= $icon; ?>

                                <?= ucfirst(
                                    htmlspecialchars(
                                        $row[
                                            'jenis_kendaraan'
                                        ]
                                    )
                                ); ?>

                            </span>

                        </td>

                        <td>

                            <span class="price">

                                Rp
                                <?= number_format(
                                    $row[
                                        'tarif_per_jam'
                                    ],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </span>

                            <span
                                style="
                                color:#64748b;
                                font-size:12px;
                                "
                            >
                                / jam
                            </span>

                        </td>

                        <td>

                            <div class="action-buttons">

                                <a
                                    href="tarif.php?edit=<?= (int)
                                        $row[
                                            'id_tarif'
                                        ]; ?>"
                                    class="btn btn-warning"
                                >
                                    ✏️ Edit
                                </a>

                                <a
                                    href="tarif.php?hapus=<?= (int)
                                        $row[
                                            'id_tarif'
                                        ]; ?>"
                                    class="btn btn-danger"
                                    onclick="
                                        return confirm(
                                            'Yakin ingin menghapus tarif <?= htmlspecialchars(
                                                ucfirst(
                                                    $row[
                                                        'jenis_kendaraan'
                                                    ]
                                                ),
                                                ENT_QUOTES
                                            ); ?>?'
                                        );
                                    "
                                >
                                    🗑️ Hapus
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td
                            colspan="4"
                            class="empty-state"
                        >

                            <div class="empty-icon">
                                💰
                            </div>

                            <strong>
                                Belum ada data tarif
                            </strong>

                            <br>

                            Tambahkan tarif parkir melalui form di atas.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- =================================================
         GRAFIK
    ================================================= -->

    <div class="card">

        <div class="card-title">

            <div>

                <h2>
                    📊 Grafik Tarif Parkir
                </h2>

                <p>
                    Perbandingan tarif per jam berdasarkan jenis kendaraan.
                </p>

            </div>

        </div>


        <div class="chart-wrapper">

            <canvas
                id="grafikTarif"
            ></canvas>

        </div>

    </div>


    <!-- =================================================
         BACK
    ================================================= -->

    <div class="back-wrapper">

        <a
            href="../dashboard.php"
            class="btn btn-secondary"
        >
            ← Kembali ke Dashboard
        </a>

    </div>

</div>

</main>

<script>

/* =========================================================
   SEARCH TARIF
   ========================================================= */

function filterTarif() {

    const input =
        document
        .getElementById('searchTarif')
        .value
        .toLowerCase();

    const rows =
        document
        .querySelectorAll(
            '#tarifTable tbody tr'
        );

    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();

        row.style.display =
            text.includes(input)
                ? ''
                : 'none';

    });

}


/* =========================================================
   GRAFIK
   ========================================================= */

const canvas =
    document.getElementById(
        'grafikTarif'
    );

const labels =
    <?= json_encode(
        $label,
        JSON_UNESCAPED_UNICODE
    ); ?>;

const data =
    <?= json_encode(
        $data
    ); ?>;

if (canvas && labels.length > 0) {

    new Chart(canvas, {

        type: 'bar',

        data: {

            labels: labels,

            datasets: [{

                label:
                    'Tarif Parkir / Jam',

                data: data,

                borderWidth: 1,

                borderRadius: 8

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: false

                },

                tooltip: {

                    callbacks: {

                        label: function(context) {

                            return 'Rp ' +
                                context.raw
                                .toLocaleString(
                                    'id-ID'
                                ) +
                                ' / jam';

                        }

                    }

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {

                        callback:
                            function(value) {

                                return 'Rp ' +
                                    Number(value)
                                    .toLocaleString(
                                        'id-ID'
                                    );

                            }

                    }

                }

            }

        }

    });

}

</script>

</body>

</html>
