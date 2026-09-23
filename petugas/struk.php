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
   CEK ROLE
   Disamakan dengan scan_qr.php.
   Halaman ini membuka transaksi berdasarkan id_parkir,
   jadi hanya boleh diakses staf.
   ========================================================= */
$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['petugas', 'karyawan', 'admin'])) {
    die("Akses ditolak.");
}

/* =========================================================
   CEK ID TRANSAKSI (id_parkir)
   Dipanggil dari transaksi.php: struk.php?id=<id_parkir>
   ========================================================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID transaksi tidak ditemukan.");
}

$id = (int) $_GET['id'];

/* =========================================================
   AMBIL DATA TRANSAKSI
   Area reguler dan area karyawan sama-sama didukung.
   tb_user.nama_lengkap = petugas yang memproses transaksi.
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        tb_transaksi.*,

        tb_booking.tanggal AS tanggal_booking,
        tb_booking.jam_masuk AS jam_booking,
        tb_booking.estimasi_jam AS estimasi_booking,
        tb_booking.status AS status_booking,

        tb_kendaraan.plat_nomor,
        tb_kendaraan.pemilik,
        tb_kendaraan.jenis_kendaraan,

        COALESCE(tb_area_parkir.nama_area, ak.nama_area) AS nama_area,

        tb_user.nama_lengkap

    FROM tb_transaksi

    LEFT JOIN tb_booking
        ON tb_transaksi.id_booking = tb_booking.id_booking

    LEFT JOIN tb_kendaraan
        ON tb_transaksi.id_kendaraan = tb_kendaraan.id_kendaraan

    LEFT JOIN tb_area_parkir
        ON tb_transaksi.id_area = tb_area_parkir.id_area

    LEFT JOIN tb_area_parkir_karyawan ak
        ON tb_transaksi.id_area_karyawan = ak.id_area_karyawan

    LEFT JOIN tb_user
        ON tb_transaksi.id_user = tb_user.id_user

    WHERE tb_transaksi.id_parkir = ?

    LIMIT 1
");

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$data) {
    die("Transaksi #" . $id . " tidak ditemukan.");
}

/* =========================================================
   VARIABEL TAMPILAN
   ========================================================= */
$id_parkir  = $data['id_parkir'] ?? '-';
$id_booking = !empty($data['id_booking']) ? $data['id_booking'] : '-';

$plat_nomor      = $data['plat_nomor'] ?? '-';
$pemilik         = !empty($data['pemilik']) ? $data['pemilik'] : '-';
$jenis_kendaraan = $data['jenis_kendaraan'] ?? '-';

$nama_area    = !empty($data['nama_area']) ? $data['nama_area'] : 'Belum ditentukan';
$nama_petugas = !empty($data['nama_lengkap']) ? $data['nama_lengkap'] : '-';

$waktu_masuk  = !empty($data['waktu_masuk']) ? $data['waktu_masuk'] : '-';
$waktu_keluar = !empty($data['waktu_keluar']) ? $data['waktu_keluar'] : '-';

/* Tanggal: dari booking bila ada, kalau parkir langsung pakai tanggal masuk */
if (!empty($data['tanggal_booking'])) {
    $tanggal = $data['tanggal_booking'];
} elseif (!empty($data['waktu_masuk'])) {
    $tanggal = date('Y-m-d', strtotime($data['waktu_masuk']));
} else {
    $tanggal = '-';
}

$jam_booking  = !empty($data['jam_booking']) ? $data['jam_booking'] : '-';
$estimasi_txt = !empty($data['estimasi_booking']) ? $data['estimasi_booking'] . ' Jam' : '-';

$durasi_jam  = $data['durasi_jam'] ?? 0;
$biaya_total = $data['biaya_total'] ?? 0;

$metode_pembayaran = !empty($data['metode_pembayaran']) ? $data['metode_pembayaran'] : '-';

/*
 * Status dinormalisasi (huruf kecil) supaya sama dengan scan_qr.php.
 *
 * status_parkir  = status di tb_transaksi (masuk / keluar)
 * status_booking = status di tb_booking (booking / aktif / selesai / batal)
 */
$status_parkir  = strtolower(trim((string) ($data['status'] ?? '')));
$status_booking = strtolower(trim((string) ($data['status_booking'] ?? '')));

/* =========================================================
   QR DINAMIS - HANYA 1 QR SESUAI STATUS

   Format yang dibaca scan_qr.php:

   PARKIR|MASUK|ID_BOOKING       -> check-in booking
   PARKIR|KELUAR|ID_BOOKING      -> keluar (booking aktif)
   PARKIR|TRANSAKSI|ID_PARKIR    -> keluar berdasarkan transaksi
                                    (parkir langsung / booking
                                    yang statusnya belum 'aktif')

   Kendaraan masih parkir (transaksi 'masuk') = QR KELUAR
   Booking belum masuk                        = QR MASUK
   Sudah keluar / batal                       = tidak ada QR
   ========================================================= */
$qr_action = null;
$qr_code   = '';

if ($status_parkir === 'masuk') {

    $qr_action = 'KELUAR';

    if (!empty($data['id_booking']) && $status_booking === 'aktif') {

        $qr_code = 'PARKIR|KELUAR|' . (int) $data['id_booking'];

    } else {

        $qr_code = 'PARKIR|TRANSAKSI|' . (int) $data['id_parkir'];
    }

} elseif (!empty($data['id_booking']) && $status_booking === 'booking') {

    $qr_action = 'MASUK';

    $qr_code = 'PARKIR|MASUK|' . (int) $data['id_booking'];
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Struk Parkir #<?= htmlspecialchars((string) $id_parkir) ?></title>

<!-- QR CODE LIBRARY -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    font-family: "Courier New", monospace;
    background: #eeeeee;
    color: #111111;
}

.struk {
    width: 380px;
    max-width: 100%;
    margin: 20px auto;
    background: #ffffff;
    border: 1px solid #000000;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
}

.header {
    text-align: center;
}

.logo {
    font-size: 27px;
    font-weight: bold;
    letter-spacing: 2px;
}

.subtitle {
    font-size: 12px;
    margin-top: 3px;
}

.alamat {
    margin-top: 8px;
    font-size: 12px;
    line-height: 1.5;
}

hr {
    border: 0;
    border-top: 1px dashed #000000;
    margin: 15px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

table td {
    padding: 4px 0;
    vertical-align: top;
}

table td:first-child {
    width: 42%;
}

.status-box {
    text-align: center;
    margin: 12px 0;
    padding: 8px;
    border: 1px solid #000000;
    font-weight: bold;
    font-size: 13px;
    line-height: 1.6;
}

.qr-box {
    text-align: center;
    padding: 12px 5px;
    border: 1px dashed #000000;
    margin-top: 12px;
}

.qr-title {
    text-align: center;
    font-size: 15px;
    font-weight: bold;
    margin-bottom: 5px;
}

.qr-subtitle {
    text-align: center;
    font-family: Arial, sans-serif;
    font-size: 11px;
    margin-bottom: 10px;
}

.qr-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 10px auto;
}

.qr-container canvas,
.qr-container img {
    display: block;
    margin: auto;
}

.qr-info {
    font-family: Arial, sans-serif;
    font-size: 11px;
    line-height: 1.4;
    margin-top: 8px;
}

.qr-code-text {
    margin-top: 8px;
    font-size: 10px;
    word-break: break-all;
}

.total {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    margin-top: 10px;
}

.total-rupiah {
    font-size: 22px;
    margin-top: 4px;
}

.footer {
    text-align: center;
    font-size: 12px;
    line-height: 1.5;
}

.btn {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}

button,
a {
    display: inline-block;
    padding: 10px 16px;
    border: none;
    background: #007bff;
    color: #ffffff;
    text-decoration: none;
    cursor: pointer;
    border-radius: 5px;
    font-family: Arial, sans-serif;
    font-size: 14px;
}

button:hover,
a:hover {
    opacity: 0.9;
}

@media print {

    @page {
        margin: 0;
    }

    body {
        background: #ffffff;
        padding: 0;
    }

    .struk {
        width: 380px;
        margin: 0 auto;
        border: none;
        box-shadow: none;
    }

    .btn {
        display: none;
    }

    .qr-container {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

}

@media (max-width: 400px) {

    body {
        padding: 10px;
    }

    .struk {
        padding: 15px;
    }

}

</style>

</head>

<body>

<div class="struk">

    <!-- HEADER -->

    <div class="header">

        <div class="logo">
            GRHASIA PARKIR
        </div>

        <div class="subtitle">
            STRUK PARKIR
        </div>

        <div class="alamat">
            Jl. Contoh No.123
            <br>
            Telp. 08123456789
        </div>

    </div>

    <hr>

    <!-- DATA PARKIR -->

    <table>

        <tr>
            <td>No Transaksi</td>
            <td>: <?= htmlspecialchars((string) $id_parkir) ?></td>
        </tr>

        <tr>
            <td>ID Booking</td>
            <td>: <?= htmlspecialchars((string) $id_booking) ?></td>
        </tr>

        <tr>
            <td>Plat Nomor</td>
            <td>: <?= htmlspecialchars((string) $plat_nomor) ?></td>
        </tr>

        <tr>
            <td>Pemilik</td>
            <td>: <?= htmlspecialchars((string) $pemilik) ?></td>
        </tr>

        <tr>
            <td>Jenis</td>
            <td>: <?= htmlspecialchars(ucfirst((string) $jenis_kendaraan)) ?></td>
        </tr>

        <tr>
            <td>Area</td>
            <td>: <?= htmlspecialchars((string) $nama_area) ?></td>
        </tr>

        <tr>
            <td>Tanggal</td>
            <td>: <?= htmlspecialchars((string) $tanggal) ?></td>
        </tr>

        <tr>
            <td>Jam Booking</td>
            <td>: <?= htmlspecialchars((string) $jam_booking) ?></td>
        </tr>

        <tr>
            <td>Estimasi</td>
            <td>: <?= htmlspecialchars((string) $estimasi_txt) ?></td>
        </tr>

        <tr>
            <td>Masuk</td>
            <td>: <?= htmlspecialchars((string) $waktu_masuk) ?></td>
        </tr>

        <tr>
            <td>Keluar</td>
            <td>: <?= htmlspecialchars((string) $waktu_keluar) ?></td>
        </tr>

        <tr>
            <td>Durasi</td>
            <td>: <?= htmlspecialchars((string) $durasi_jam) ?> Jam</td>
        </tr>

        <tr>
            <td>Pembayaran</td>
            <td>: <?= htmlspecialchars(strtoupper((string) $metode_pembayaran)) ?></td>
        </tr>

        <tr>
            <td>Petugas</td>
            <td>: <?= htmlspecialchars((string) $nama_petugas) ?></td>
        </tr>

    </table>

    <hr>

    <!-- STATUS -->

    <div class="status-box">

        STATUS PARKIR:
        <?= htmlspecialchars(strtoupper($status_parkir !== '' ? $status_parkir : '-')) ?>

        <?php if ($status_booking !== ''): ?>
            <br>
            STATUS BOOKING:
            <?= htmlspecialchars(strtoupper($status_booking)) ?>
        <?php endif; ?>

    </div>

    <!-- QR DINAMIS (sama dengan struk pengguna & format scan_qr.php) -->

    <?php if ($qr_code !== ''): ?>

    <div class="qr-box">

        <div class="qr-title">
            <?php if ($qr_action === 'MASUK'): ?>
                🟢 QR MASUK
            <?php else: ?>
                🔴 QR KELUAR
            <?php endif; ?>
        </div>

        <div class="qr-subtitle">
            <?php if ($qr_action === 'MASUK'): ?>
                Tunjukkan QR ini kepada petugas saat masuk
            <?php else: ?>
                Tunjukkan QR ini kepada petugas saat keluar
            <?php endif; ?>
        </div>

        <div id="qrcode" class="qr-container"></div>

        <div class="qr-info">
            <?php if ($qr_action === 'MASUK'): ?>
                Scan QR ini untuk <strong>CHECK-IN / MASUK</strong> kendaraan.
            <?php else: ?>
                Scan QR ini untuk <strong>CHECK-OUT / KELUAR</strong> kendaraan.
            <?php endif; ?>
        </div>

        <div class="qr-code-text">
            <?= htmlspecialchars($qr_code) ?>
        </div>

    </div>

    <?php endif; ?>

    <hr>

    <!-- TOTAL -->

    <div class="total">

        TOTAL BAYAR

        <div class="total-rupiah">
            Rp <?= number_format((float) $biaya_total, 0, ",", ".") ?>
        </div>

    </div>

    <hr>

    <!-- FOOTER -->

    <div class="footer">

        Terima Kasih

        <br>

        Selamat Jalan

        <?php if ($qr_code !== ''): ?>

            <br><br>

            <strong>
                <?php if ($qr_action === 'MASUK'): ?>
                    Simpan struk ini untuk masuk.
                <?php else: ?>
                    Simpan struk ini untuk keluar.
                <?php endif; ?>
            </strong>

        <?php endif; ?>

        <br><br>

        <small>
            No Transaksi #<?= htmlspecialchars((string) $id_parkir) ?>
            <?php if ($id_booking !== '-'): ?>
                | ID Booking #<?= htmlspecialchars((string) $id_booking) ?>
            <?php endif; ?>
        </small>

    </div>

    <!-- BUTTON -->

    <div class="btn">

        <button onclick="window.print()">
            🖨 Cetak
        </button>

        <a href="transaksi.php">
            Kembali
        </a>

    </div>

</div>

<script>

/* =========================================================
   QR DINAMIS
   Kosong jika tidak ada QR yang berlaku.
   ========================================================= */

const qrCode = <?= json_encode($qr_code) ?>;

document.addEventListener("DOMContentLoaded", function () {

    if (qrCode === '') {
        return;
    }

    const qrElement = document.getElementById("qrcode");

    if (qrElement) {

        new QRCode(qrElement, {
            text: qrCode,
            width: 220,
            height: 220,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

    }

});

</script>

</body>

</html>