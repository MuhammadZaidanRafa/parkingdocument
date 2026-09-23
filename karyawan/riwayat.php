<?php

/* =========================================================
   SESSION
========================================================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================================================
   DATABASE
========================================================= */
require_once __DIR__ . '/../db.php';

/* =========================================================
   CEK LOGIN
========================================================= */
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'pengguna';

$inisial = strtoupper(
    substr($nama, 0, 1)
);

/* =========================================================
   PESAN SESSION
========================================================= */
$pesan_sukses =
    $_SESSION['pesan_sukses'] ?? null;

$pesan_error =
    $_SESSION['pesan_error'] ?? null;

unset(
    $_SESSION['pesan_sukses'],
    $_SESSION['pesan_error']
);

/* =========================================================
   AMBIL RIWAYAT BOOKING
   SUPPORT:
   - AREA UMUM
   - AREA KARYAWAN
========================================================= */
$sql = "

    SELECT

        b.id_booking,
        b.id_user,
        b.id_kendaraan,

        b.id_area,
        b.id_area_karyawan,

        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        b.status,
        b.created_at,

        k.plat_nomor,
        k.jenis_kendaraan,

        /* AREA UMUM */
        a.nama_area AS nama_area_umum,

        /* AREA KARYAWAN */
        ak.nama_area AS nama_area_karyawan,

        /* KAPASITAS AREA KARYAWAN */
        ak.kapasitas AS kapasitas_karyawan,
        ak.terisi AS terisi_karyawan,
        ak.status AS status_area_karyawan,

        /* TARIF */
        MIN(t.tarif_per_jam) AS tarif_per_jam,

        /* TRANSAKSI */
        MAX(tr.waktu_masuk) AS aktual_waktu_masuk,
        MAX(tr.waktu_keluar) AS aktual_waktu_keluar,
        MAX(tr.durasi_jam) AS aktual_durasi_jam,
        MAX(tr.biaya_total) AS aktual_biaya_total

    FROM tb_booking b

    /* KENDARAAN */
    LEFT JOIN tb_kendaraan k
        ON b.id_kendaraan = k.id_kendaraan

    /* AREA UMUM */
    LEFT JOIN tb_area_parkir a
        ON b.id_area = a.id_area

    /* AREA KARYAWAN */
    LEFT JOIN tb_area_parkir_karyawan ak
        ON b.id_area_karyawan = ak.id_area_karyawan

    /* TARIF */
    LEFT JOIN tb_tarif t
        ON LOWER(TRIM(t.jenis_kendaraan))
         =
           LOWER(TRIM(k.jenis_kendaraan))

    /* TRANSAKSI */
    LEFT JOIN tb_transaksi tr
        ON tr.id_booking = b.id_booking

    WHERE b.id_user = ?

    GROUP BY
        b.id_booking,
        b.id_user,
        b.id_kendaraan,
        b.id_area,
        b.id_area_karyawan,
        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        b.status,
        b.created_at,
        k.plat_nomor,
        k.jenis_kendaraan,
        a.nama_area,
        ak.nama_area,
        ak.kapasitas,
        ak.terisi,
        ak.status

    ORDER BY
        b.id_booking DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die(
        "Gagal mengambil riwayat booking: " .
        $conn->error
    );
}

$stmt->bind_param(
    "i",
    $id_user
);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Riwayat Booking Parkir
</title>

<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;

    font-family:
        Arial,
        Helvetica,
        sans-serif;
}


/* =====================================================
   BODY
===================================================== */

body {
    background: #f4f6f9;
    color: #333;
}


/* =====================================================
   LAYOUT
===================================================== */

.wrapper {
    display: flex;
    min-height: 100vh;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    width: 260px;

    background: #fff;

    border-right:
        1px solid #e5e7eb;

    display: flex;

    flex-direction: column;

    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    z-index: 1000;

    transition:
        transform .3s ease;

    box-shadow:
        2px 0 12px
        rgba(0,0,0,.05);
}


/* =====================================================
   SIDEBAR HEADER
===================================================== */

.sidebar-header {
    background: #007bff;

    color: #fff;

    padding:
        22px 20px;
}

.sidebar-header h2 {
    font-size: 1.15rem;

    margin-bottom: 16px;
}


/* =====================================================
   USER
===================================================== */

.user-info {
    display: flex;

    align-items: center;

    gap: 12px;
}

.avatar {
    flex-shrink: 0;

    width: 42px;
    height: 42px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.25);

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: bold;

    font-size: 1.1rem;
}

.user-name {
    font-weight: bold;

    font-size: .95rem;

    line-height: 1.3;
}

.user-role {
    font-size: .75rem;

    opacity: .85;

    letter-spacing: .5px;
}


/* =====================================================
   MENU
===================================================== */

.sidebar-menu {
    flex: 1;

    padding: 14px 0;

    overflow-y: auto;
}

.nav-link {
    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        12px 20px;

    color: #333;

    text-decoration: none;

    font-size: .95rem;

    border-left:
        3px solid transparent;

    transition: .2s ease;
}

.nav-link .icon {
    font-size: 1.1rem;

    width: 22px;

    text-align: center;
}

.nav-link:hover,
.nav-link.active {
    background: #eaf2ff;

    color: #007bff;

    border-left-color:
        #007bff;
}


/* =====================================================
   FOOTER
===================================================== */

.sidebar-footer {
    padding: 12px 0;

    border-top:
        1px solid #eee;
}

.logout-link:hover {
    background: #fdeaea;

    color: #dc3545;

    border-left-color:
        #dc3545;
}


/* =====================================================
   OVERLAY
===================================================== */

.overlay {
    display: none;

    position: fixed;

    inset: 0;

    background:
        rgba(0,0,0,.4);

    z-index: 900;
}

.overlay.active {
    display: block;
}


/* =====================================================
   MAIN
===================================================== */

.main-content {
    flex: 1;

    min-width: 0;

    margin-left: 260px;

    transition:
        margin-left .3s ease;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {
    background: #fff;

    padding:
        15px 25px;

    display: flex;

    align-items: center;

    gap: 15px;

    box-shadow:
        0 1px 4px
        rgba(0,0,0,.06);

    position: sticky;

    top: 0;

    z-index: 500;
}

.topbar h2 {
    font-size: 1.1rem;

    color: #333;
}


/* =====================================================
   HAMBURGER
===================================================== */

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


/* =====================================================
   CONTAINER
===================================================== */

.container {
    width: 95%;

    max-width: 1600px;

    margin:
        25px auto;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    padding:
        12px 16px;

    border-radius: 7px;

    margin-bottom: 15px;

    color: #fff;

    font-weight: bold;
}

.alert-sukses {
    background: #28a745;
}

.alert-error {
    background: #dc3545;
}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {
    width: 100%;

    overflow-x: auto;

    background: #fff;

    border-radius: 10px;

    box-shadow:
        0 3px 10px
        rgba(0,0,0,.08);
}

table {
    width: 100%;

    min-width: 1400px;

    border-collapse: collapse;

    background: #fff;
}

table th,
table td {
    padding: 12px;

    border:
        1px solid #ddd;

    text-align: center;

    white-space: nowrap;
}

table th {
    background: #007bff;

    color: #fff;

    font-size: 13px;
}

table td {
    font-size: 13px;
}

table tr:hover td {
    background: #f8fbff;
}


/* =====================================================
   AREA TYPE
===================================================== */

.area-karyawan {
    display: inline-block;

    margin-top: 5px;

    padding:
        4px 8px;

    border-radius: 999px;

    background: #e0e7ff;

    color: #3730a3;

    font-size: 10px;

    font-weight: bold;
}

.area-umum {
    display: inline-block;

    margin-top: 5px;

    padding:
        4px 8px;

    border-radius: 999px;

    background: #dcfce7;

    color: #166534;

    font-size: 10px;

    font-weight: bold;
}


/* =====================================================
   BUTTON
===================================================== */

.btn {
    display: inline-block;

    margin-top: 20px;

    padding:
        10px 20px;

    background: #007bff;

    color: #fff;

    text-decoration: none;

    border-radius: 6px;

    transition: .2s;
}

.btn:hover {
    background: #0056b3;
}


/* =====================================================
   STATUS
===================================================== */

.status {
    display: inline-block;

    padding:
        5px 10px;

    border-radius: 5px;

    color: #fff;

    font-size: 12px;

    font-weight: bold;
}

.booking {
    background: #6c757d;
}

.aktif {
    background: #ff9800;
}

.selesai {
    background: #28a745;
}

.batal {
    background: #dc3545;
}


/* =====================================================
   HARGA
===================================================== */

.harga {
    font-weight: bold;

    color: #007bff;
}

.harga-final {
    font-size: 11px;

    font-weight: normal;

    color: #28a745;

    display: block;

    margin-top: 3px;
}


/* =====================================================
   BUTTON KECIL
===================================================== */

.btn-kecil {
    display: inline-block;

    padding:
        6px 12px;

    background: #28a745;

    color: #fff;

    text-decoration: none;

    border-radius: 5px;

    font-size: 13px;

    transition: .2s;
}

.btn-kecil:hover {
    background: #218838;
}


/* =====================================================
   TIMER
===================================================== */

.timer {
    font-weight: bold;

    font-size: 13px;

    line-height: 1.5;
}

.timer-normal {
    color: #28a745;
}

.timer-telat {
    color: #dc3545;
}

.timer-off {
    color: #999;

    font-weight: normal;
}


/* =====================================================
   RESPONSIVE
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

}


@media (max-width: 576px) {

    .sidebar {
        width: 85%;

        max-width: 300px;
    }

    .container {
        width: 94%;

        margin:
            15px auto;
    }

    .topbar {
        padding:
            12px 15px;
    }

    .topbar h2 {
        font-size: 1rem;
    }

}

</style>

</head>

<body>

<div class="wrapper">

<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside
    class="sidebar"
    id="sidebar"
>

    <div class="sidebar-header">

        <h2>
            🅿️ Aplikasi Parkir
        </h2>

        <div class="user-info">

            <div class="avatar">
                <?= htmlspecialchars($inisial); ?>
            </div>

            <div>

                <p class="user-name">
                    <?= htmlspecialchars($nama); ?>
                </p>

                <span class="user-role">
                    <?= strtoupper(
                        htmlspecialchars($role)
                    ); ?>
                </span>

            </div>

        </div>

    </div>


    <nav class="sidebar-menu">

        <a
            href="dashboard_pengguna.php"
            class="nav-link"
        >
            <span class="icon">📊</span>
            Dashboard
        </a>


        <a
            href="riwayat.php"
            class="nav-link active"
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
            class="nav-link"
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
        >
            <span class="icon">🚪</span>
            Logout
        </a>

    </div>

</aside>


<!-- OVERLAY -->

<div
    class="overlay"
    id="overlay"
></div>


<!-- =====================================================
     MAIN
===================================================== -->

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
        Riwayat Booking Parkir Saya
    </h2>

</header>


<div class="container">


<!-- =====================================================
     ALERT SUKSES
===================================================== -->

<?php if ($pesan_sukses): ?>

    <div class="alert alert-sukses">
        <?= htmlspecialchars($pesan_sukses); ?>
    </div>

<?php endif; ?>


<!-- =====================================================
     ALERT ERROR
===================================================== -->

<?php if ($pesan_error): ?>

    <div class="alert alert-error">
        <?= htmlspecialchars($pesan_error); ?>
    </div>

<?php endif; ?>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="table-wrapper">

<table>

<thead>

<tr>

    <th>No</th>

    <th>No Polisi</th>

    <th>Jenis</th>

    <th>Area</th>

    <th>Tanggal</th>

    <th>Jam Masuk</th>

    <th>Estimasi</th>

    <th>Tarif/Jam</th>

    <th>Total Harga</th>

    <th>Status</th>

    <th>Sisa Waktu / Denda</th>

    <th>Dibuat</th>

    <th>Aksi</th>

</tr>

</thead>


<tbody>

<?php

$no = 1;

if ($result->num_rows === 0):

?>

<tr>

    <td colspan="13">

        Belum ada riwayat booking.

    </td>

</tr>

<?php

else:

while ($data = $result->fetch_assoc()):

    /* =================================================
       DATA DASAR
    ================================================= */

    $tarif_per_jam =
        (int) (
            $data['tarif_per_jam'] ?? 0
        );

    $estimasi_jam =
        (int) (
            $data['estimasi_jam'] ?? 1
        );

    $status =
        $data['status'] ?? 'booking';


    /* =================================================
       TENTUKAN AREA
    ================================================= */

    $is_area_karyawan =
        !empty(
            $data['id_area_karyawan']
        );


    if ($is_area_karyawan) {

        $nama_area =
            $data['nama_area_karyawan']
            ?? '-';

    } else {

        $nama_area =
            $data['nama_area_umum']
            ?? '-';
    }


    /* =================================================
       TOTAL ESTIMASI
    ================================================= */

    $total_estimasi =
        $tarif_per_jam *
        $estimasi_jam;


    /* =================================================
       TOTAL FINAL
    ================================================= */

    $total_final =
        (
            $status === 'selesai'
            &&
            $data['aktual_biaya_total'] !== null
        )
        ? (int) $data['aktual_biaya_total']
        : null;


    /* =================================================
       WAKTU DASAR
    ================================================= */

    if (
        !empty(
            $data['aktual_waktu_masuk']
        )
    ) {

        $waktu_masuk_dasar =
            strtotime(
                $data['aktual_waktu_masuk']
            );

    } else {

        $waktu_masuk_dasar =
            strtotime(
                $data['tanggal']
                . ' '
                . $data['jam_masuk']
            );
    }


    /* =================================================
       DEADLINE
    ================================================= */

    $deadline_unix =
        $waktu_masuk_dasar
        +
        (
            $estimasi_jam *
            3600
        );

?>

<tr>

<!-- NO -->

<td>
    <?= $no++; ?>
</td>


<!-- PLAT -->

<td>

    <?= htmlspecialchars(
        $data['plat_nomor'] ?? '-'
    ); ?>

</td>


<!-- JENIS -->

<td>

    <?= htmlspecialchars(
        $data['jenis_kendaraan'] ?? '-'
    ); ?>

</td>


<!-- AREA -->

<td>

    <?= htmlspecialchars(
        $nama_area
    ); ?>

    <br>

    <?php if ($is_area_karyawan): ?>

        <span class="area-karyawan">
            AREA KARYAWAN
        </span>

    <?php else: ?>

        <span class="area-umum">
            AREA UMUM
        </span>

    <?php endif; ?>

</td>


<!-- TANGGAL -->

<td>

    <?= !empty($data['tanggal'])
        ? date(
            'd-m-Y',
            strtotime($data['tanggal'])
        )
        : '-';
    ?>

</td>


<!-- JAM -->

<td>

    <?= !empty($data['jam_masuk'])
        ? date(
            'H:i',
            strtotime($data['jam_masuk'])
        )
        : '-';
    ?>

</td>


<!-- ESTIMASI -->

<td>

    <?= $estimasi_jam; ?>

    Jam

</td>


<!-- TARIF -->

<td>

    Rp
    <?= number_format(
        $tarif_per_jam,
        0,
        ',',
        '.'
    ); ?>

</td>


<!-- TOTAL -->

<td class="harga">

    Rp
    <?= number_format(
        $total_final ??
        $total_estimasi,
        0,
        ',',
        '.'
    ); ?>


    <?php if (
        $total_final !== null
        &&
        $total_final !== $total_estimasi
    ): ?>

        <span class="harga-final">

            Final,
            estimasi awal Rp

            <?= number_format(
                $total_estimasi,
                0,
                ',',
                '.'
            ); ?>

        </span>

    <?php endif; ?>

</td>


<!-- STATUS -->

<td>

<?php

$status_class =
    preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '',
        strtolower($status)
    );

?>

<span
    class="status
    <?= htmlspecialchars(
        $status_class
    ); ?>"
>

    <?= strtoupper(
        htmlspecialchars($status)
    ); ?>

</span>

</td>


<!-- TIMER -->

<td>

<?php if ($status === 'aktif'): ?>

    <span
        class="timer"
        data-timer
        data-deadline="<?= $deadline_unix; ?>"
        data-tarif="<?= $tarif_per_jam; ?>"
    >
        Menghitung...
    </span>

<?php else: ?>

    <span class="timer timer-off">
        -
    </span>

<?php endif; ?>

</td>


<!-- DIBUAT -->

<td>

    <?= !empty($data['created_at'])
        ? date(
            'd-m-Y H:i',
            strtotime(
                $data['created_at']
            )
        )
        : '-';
    ?>

</td>


<!-- AKSI -->

<td>

    <a
        href="quitansi.php?id_booking=<?= (int) $data['id_booking']; ?>"
        class="btn-kecil"
    >
        🧾 Kuitansi
    </a>

</td>

</tr>


<?php

endwhile;

endif;

?>

</tbody>

</table>

</div>


<!-- =====================================================
     KEMBALI
===================================================== -->

<a
    href="dashboard_pengguna.php"
    class="btn"
>
    ← Kembali ke Dashboard
</a>


</div>

</div>

</div>


<script>

/* =====================================================
   FORMAT DURASI
===================================================== */

function formatDurasi(detik) {

    const jam =
        Math.floor(
            detik / 3600
        );

    const menit =
        Math.floor(
            (detik % 3600) / 60
        );

    const sisaDetik =
        detik % 60;

    let hasil = '';

    if (jam > 0) {

        hasil +=
            jam + 'j ';

    }

    hasil +=
        menit +
        'm ' +
        sisaDetik +
        'd';

    return hasil;
}


/* =====================================================
   FORMAT RUPIAH
===================================================== */

function formatRupiah(angka) {

    return 'Rp ' +
        angka.toLocaleString(
            'id-ID'
        );
}


/* =====================================================
   UPDATE TIMER
===================================================== */

function updateTimers() {

    const timers =
        document.querySelectorAll(
            '[data-timer]'
        );


    timers.forEach(
        function(el) {

            const deadline =
                parseInt(
                    el.dataset.deadline,
                    10
                ) * 1000;


            const tarifPerJam =
                parseInt(
                    el.dataset.tarif,
                    10
                ) || 0;


            const now =
                Date.now();


            const selisihDetik =
                Math.floor(
                    (
                        deadline -
                        now
                    ) / 1000
                );


            /* =========================================
               MASIH DALAM ESTIMASI
            ========================================= */

            if (selisihDetik > 0) {

                el.textContent =
                    'Sisa: ' +
                    formatDurasi(
                        selisihDetik
                    );


                el.classList.remove(
                    'timer-telat'
                );

                el.classList.add(
                    'timer-normal'
                );

            }

            /* =========================================
               TERLAMBAT
            ========================================= */

            else {

                const detikTelat =
                    Math.abs(
                        selisihDetik
                    );


                const jamTelat =
                    Math.ceil(
                        detikTelat /
                        3600
                    );


                const denda =
                    jamTelat *
                    tarifPerJam;


                el.innerHTML =
                    'Telat: ' +
                    formatDurasi(
                        detikTelat
                    ) +
                    '<br>Denda: ' +
                    formatRupiah(
                        denda
                    );


                el.classList.remove(
                    'timer-normal'
                );

                el.classList.add(
                    'timer-telat'
                );

            }

        }
    );

}


/* =====================================================
   JALANKAN TIMER
===================================================== */

updateTimers();

setInterval(
    updateTimers,
    1000
);


/* =====================================================
   SIDEBAR MOBILE
===================================================== */

const sidebar =
    document.getElementById(
        'sidebar'
    );


const overlay =
    document.getElementById(
        'overlay'
    );


const hamburgerBtn =
    document.getElementById(
        'hamburgerBtn'
    );


function toggleSidebar() {

    sidebar.classList.toggle(
        'active'
    );

    overlay.classList.toggle(
        'active'
    );
}


/* BUKA SIDEBAR */

hamburgerBtn.addEventListener(
    'click',
    toggleSidebar
);


/* KLIK OVERLAY */

overlay.addEventListener(
    'click',
    toggleSidebar
);


/* TUTUP SIDEBAR */

document
    .querySelectorAll(
        '.sidebar .nav-link'
    )
    .forEach(
        function(link) {

            link.addEventListener(
                'click',
                function() {

                    if (
                        window.innerWidth <= 992
                    ) {

                        sidebar.classList.remove(
                            'active'
                        );

                        overlay.classList.remove(
                            'active'
                        );

                    }

                }
            );

        }
    );

</script>

</body>

</html>

<?php
$stmt->close();
?>