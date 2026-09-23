<?php

session_start();

require_once "../db.php";

/* =========================================================
   KONFIGURASI
   Sesuaikan dengan sistem Anda bila perlu.
   ========================================================= */

/*
 * Gambar QRIS toko: qris.png
 * File dicari otomatis di lokasi berikut
 * (relatif terhadap folder proses_keluar.php).
 * Kalau qris.png Anda ada di tempat lain, tambahkan path-nya di sini.
 */
$QRIS_KANDIDAT = [
    'qris.png',
    '../qris.png',
    '../assets/qris.png',
    '../assets/img/qris.png',
    '../img/qris.png',
    '../images/qris.png',
    '../uploads/qris.png'
];

$QRIS_IMAGE = '';

foreach ($QRIS_KANDIDAT as $kandidat) {

    if (is_file(__DIR__ . '/' . $kandidat)) {

        $QRIS_IMAGE = $kandidat;
        break;
    }
}

/*
 * Nama kolom tarif per jam di tb_tarif.
 * Dicari berurutan, kolom pertama yang ada dan berupa angka dipakai.
 */
$KOLOM_TARIF = ['tarif_per_jam', 'harga_per_jam', 'biaya_per_jam', 'tarif', 'harga'];

/*
 * Nilai metode_pembayaran yang disimpan ke tb_transaksi.
 */
$METODE_CASH = 'cash';
$METODE_QRIS = 'qris';

/* =========================================================
   CEK LOGIN
   ========================================================= */

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$nama    = $_SESSION['nama_lengkap'] ?? 'Petugas';
$role    = $_SESSION['role'] ?? '';

/* =========================================================
   CEK ROLE (sama dengan scan_qr.php)
   ========================================================= */

if (!in_array($role, ['petugas', 'karyawan', 'admin'])) {
    die("Akses ditolak.");
}

/* =========================================================
   CSRF TOKEN
   ========================================================= */

if (empty($_SESSION['csrf_keluar'])) {
    $_SESSION['csrf_keluar'] = bin2hex(random_bytes(16));
}

$csrf = $_SESSION['csrf_keluar'];

/* =========================================================
   FUNGSI BANTU
   ========================================================= */

function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/*
 * Durasi dibulatkan ke atas per jam, minimal 1 jam.
 */
function hitungDurasiJam($waktu_masuk, $waktu_keluar)
{
    $detik = strtotime($waktu_keluar) - strtotime($waktu_masuk);

    if ($detik < 0) {
        $detik = 0;
    }

    return max(1, (int) ceil($detik / 3600));
}

/*
 * Ambil tarif per jam dari satu baris tb_tarif.
 */
function ambilTarifPerJam(array $baris, array $kandidat)
{
    foreach ($kandidat as $kolom) {
        if (array_key_exists($kolom, $baris) && is_numeric($baris[$kolom])) {
            return (float) $baris[$kolom];
        }
    }

    return null;
}

/*
 * Ubah semua angka di teks jadi integer ("50.000" -> 50000).
 */
function angkaSaja($teks)
{
    return (int) preg_replace('/\D/', '', (string) $teks);
}

function ambilTransaksi(mysqli $conn, $id_parkir)
{
    $stmt = $conn->prepare("
        SELECT
            t.*,

            k.plat_nomor,
            k.pemilik,
            k.jenis_kendaraan,
            k.merk,
            k.warna,

            b.tanggal AS tanggal_booking,
            b.jam_masuk AS jam_booking,
            b.estimasi_jam AS estimasi_booking,
            b.status AS status_booking,

            COALESCE(a.nama_area, ak.nama_area) AS nama_area

        FROM tb_transaksi t

        LEFT JOIN tb_kendaraan k
            ON t.id_kendaraan = k.id_kendaraan

        LEFT JOIN tb_booking b
            ON t.id_booking = b.id_booking

        LEFT JOIN tb_area_parkir a
            ON t.id_area = a.id_area

        LEFT JOIN tb_area_parkir_karyawan ak
            ON t.id_area_karyawan = ak.id_area_karyawan

        WHERE t.id_parkir = ?

        LIMIT 1
    ");

    if (!$stmt) {
        die("Query transaksi gagal: " . $conn->error);
    }

    $stmt->bind_param("i", $id_parkir);
    $stmt->execute();

    $baris = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $baris;
}

function ambilTarif(mysqli $conn, $trx)
{
    $baris = null;

    /* Prioritas: tarif yang tersimpan di transaksi */
    if (!empty($trx['id_tarif'])) {

        $stmt = $conn->prepare("
            SELECT *
            FROM tb_tarif
            WHERE id_tarif = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die("Query tarif gagal: " . $conn->error);
        }

        $id_tarif = (int) $trx['id_tarif'];

        $stmt->bind_param("i", $id_tarif);
        $stmt->execute();

        $baris = $stmt->get_result()->fetch_assoc();

        $stmt->close();
    }

    /* Cadangan: cari berdasarkan jenis kendaraan */
    if (!$baris && !empty($trx['jenis_kendaraan'])) {

        $stmt = $conn->prepare("
            SELECT *
            FROM tb_tarif
            WHERE jenis_kendaraan = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die("Query tarif gagal: " . $conn->error);
        }

        $stmt->bind_param("s", $trx['jenis_kendaraan']);
        $stmt->execute();

        $baris = $stmt->get_result()->fetch_assoc();

        $stmt->close();
    }

    return $baris;
}

/* =========================================================
   AMBIL TRANSAKSI
   ========================================================= */

$id_parkir = (int) ($_POST['id_parkir'] ?? $_GET['id_parkir'] ?? 0);

$fatal        = '';
$link_struk   = false;
$trx          = null;
$tarif_per_jam = 0;

if ($id_parkir <= 0) {

    $fatal = "ID transaksi tidak valid. Silakan scan ulang QR.";

} else {

    $trx = ambilTransaksi($conn, $id_parkir);

    if (!$trx) {

        $fatal = "Transaksi #{$id_parkir} tidak ditemukan.";

    } elseif (strtolower(trim($trx['status'] ?? '')) !== 'masuk') {

        $fatal = "Transaksi #{$id_parkir} sudah selesai. Kendaraan sudah keluar.";
        $link_struk = true;

    } else {

        $baris_tarif = ambilTarif($conn, $trx);

        if (!$baris_tarif) {

            $fatal = "Tarif untuk kendaraan "
                . ($trx['jenis_kendaraan'] ?? '-')
                . " belum tersedia.";

        } else {

            $hasil_tarif = ambilTarifPerJam($baris_tarif, $KOLOM_TARIF);

            if ($hasil_tarif === null) {

                $fatal = "Kolom tarif per jam tidak dikenali di tb_tarif. "
                    . "Kolom yang ada: " . implode(', ', array_keys($baris_tarif))
                    . ". Sesuaikan variabel \$KOLOM_TARIF di proses_keluar.php.";

            } else {

                $tarif_per_jam = $hasil_tarif;
            }
        }
    }
}

/* =========================================================
   VARIABEL FORM
   ========================================================= */

$pesan      = '';
$tipe_pesan = '';

$durasi_default = 1;
$durasi_input   = 1;
$metode_input   = $METODE_CASH;
$uang_input     = '';

if ($fatal === '') {

    $sekarang       = date('Y-m-d H:i:s');
    $durasi_default = hitungDurasiJam($trx['waktu_masuk'], $sekarang);
    $durasi_input   = $durasi_default;
}

/* =========================================================
   PROSES PEMBAYARAN
   ========================================================= */

if ($fatal === '' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $durasi_input = (int) ($_POST['durasi_jam'] ?? 0);
    $metode_input = strtolower(trim($_POST['metode'] ?? ''));
    $uang_input   = (string) ($_POST['uang_diterima'] ?? '');

    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {

        $pesan = "Sesi tidak valid. Muat ulang halaman lalu coba lagi.";
        $tipe_pesan = "error";

    } else {

        if ($durasi_input < 1) {
            $durasi_input = $durasi_default;
        }

        if ($durasi_input > 999) {
            $durasi_input = 999;
        }

        /* Total SELALU dihitung ulang di server */
        $biaya_total = round($durasi_input * $tarif_per_jam);

        if (!in_array($metode_input, [$METODE_CASH, $METODE_QRIS], true)) {

            $pesan = "Pilih metode pembayaran terlebih dahulu.";
            $tipe_pesan = "error";

        } elseif ($metode_input === $METODE_CASH && angkaSaja($uang_input) < $biaya_total) {

            $pesan = "Uang diterima kurang. Total yang harus dibayar "
                . rupiah($biaya_total) . ".";
            $tipe_pesan = "error";

        } else {

            $waktu_keluar = date('Y-m-d H:i:s');

            $sukses = false;
            $galat  = '';

            $conn->begin_transaction();

            try {

                /*
                 * UPDATE bersyarat status = 'masuk':
                 * kalau dua petugas menekan tombol bersamaan,
                 * hanya satu yang berhasil.
                 */
                $stmt = $conn->prepare("
                    UPDATE tb_transaksi
                    SET
                        waktu_keluar      = ?,
                        durasi_jam        = ?,
                        biaya_total       = ?,
                        metode_pembayaran = ?,
                        status            = 'keluar'
                    WHERE id_parkir = ?
                    AND status = 'masuk'
                ");

                if (!$stmt) {
                    throw new Exception("Prepare update transaksi: " . $conn->error);
                }

                $stmt->bind_param(
                    "sidsi",
                    $waktu_keluar,
                    $durasi_input,
                    $biaya_total,
                    $metode_input,
                    $id_parkir
                );

                $stmt->execute();

                $terubah = $stmt->affected_rows;

                $stmt->close();

                if ($terubah !== 1) {

                    throw new Exception("Transaksi sudah diproses petugas lain.");
                }

                /* Booking (kalau ada) menjadi selesai */
                if (!empty($trx['id_booking'])) {

                    $stmt2 = $conn->prepare("
                        UPDATE tb_booking
                        SET status = 'selesai'
                        WHERE id_booking = ?
                    ");

                    if (!$stmt2) {
                        throw new Exception("Prepare update booking: " . $conn->error);
                    }

                    $id_booking = (int) $trx['id_booking'];

                    $stmt2->bind_param("i", $id_booking);
                    $stmt2->execute();
                    $stmt2->close();
                }

                $conn->commit();

                $sukses = true;

            } catch (Throwable $ex) {

                $conn->rollback();

                $galat = $ex->getMessage();
            }

            if ($sukses) {

                header("Location: struk.php?id=" . $id_parkir);
                exit;
            }

            $pesan = "Gagal menyelesaikan transaksi: " . $galat;
            $tipe_pesan = "error";
        }
    }
}

/* Tampilan */
$total_tampil = $fatal === '' ? round($durasi_input * $tarif_per_jam) : 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Proses Keluar - Parking</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #0f172a, #111827, #020617);
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 560px;
        }

        .card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(18px);
            border-radius: 24px;
            padding: 25px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            font-size: 32px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.25);
        }

        h1 {
            font-size: 24px;
            margin-bottom: 6px;
        }

        .subtitle {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert {
            padding: 14px;
            border-radius: 13px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .detail {
            background: rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .detail table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .detail td {
            padding: 5px 0;
            vertical-align: top;
        }

        .detail td:first-child {
            color: #94a3b8;
            width: 40%;
        }

        .plat {
            display: inline-block;
            background: #fff;
            color: #111827;
            font-weight: 800;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 8px;
        }

        label.field {
            display: block;
            font-size: 13px;
            color: #cbd5e1;
            margin: 14px 0 6px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.25);
            color: #fff;
            font-size: 16px;
            outline: none;
        }

        input:focus {
            border-color: #f59e0b;
        }

        .hint {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
            line-height: 1.5;
        }

        .warn {
            color: #fcd34d;
        }

        .total-box {
            margin-top: 16px;
            padding: 16px;
            text-align: center;
            border-radius: 16px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .total-label {
            font-size: 13px;
            color: #fcd34d;
        }

        .total-nilai {
            font-size: 30px;
            font-weight: 800;
            margin-top: 4px;
        }

        .metode {
            display: flex;
            gap: 10px;
        }

        .metode label {
            flex: 1;
            cursor: pointer;
        }

        .metode input {
            display: none;
        }

        .metode span {
            display: block;
            text-align: center;
            padding: 13px;
            border-radius: 12px;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.25);
            color: #cbd5e1;
            transition: 0.2s;
        }

        .metode input:checked + span {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #111827;
        }

        .quick {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .quick button {
            padding: 8px 12px;
            font-size: 13px;
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
        }

        .kembalian {
            margin-top: 10px;
            font-size: 14px;
        }

        .kembalian strong {
            font-size: 18px;
        }

        .ok {
            color: #86efac;
        }

        .kurang {
            color: #fca5a5;
        }

        .qris-box {
            text-align: center;
            padding: 14px;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #bfdbfe;
            font-size: 13px;
            line-height: 1.6;
        }

        .qris-box img {
            display: block;
            max-width: 260px;
            width: 100%;
            margin: 0 auto 10px;
            border-radius: 12px;
            background: #fff;
            padding: 8px;
        }

        button {
            border: none;
            padding: 14px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: 0.2s;
        }

        .btn-submit {
            width: 100%;
            margin-top: 18px;
            background: #f59e0b;
            color: #111827;
        }

        .btn-submit:hover {
            background: #fbbf24;
            transform: translateY(-1px);
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-link {
            display: block;
            text-align: center;
            margin-top: 12px;
            padding: 13px;
            border-radius: 12px;
            color: #111827;
            font-weight: 700;
            text-decoration: none;
            background: #f59e0b;
        }

        .hidden {
            display: none;
        }

        .footer {
            text-align: center;
            margin-top: 18px;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 480px) {

            body {
                padding: 12px;
            }

            .card {
                padding: 18px;
                border-radius: 20px;
            }

            h1 {
                font-size: 21px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <div class="icon">
                🚗
            </div>

            <h1>Proses Keluar</h1>

            <div class="subtitle">
                Petugas: <?= e($nama) ?>
            </div>

        </div>


        <?php if ($fatal !== ''): ?>

            <div class="alert error">
                <?= e($fatal) ?>
            </div>

            <?php if ($link_struk): ?>
                <a class="btn-link" href="struk.php?id=<?= (int) $id_parkir ?>">
                    🧾 Lihat Struk
                </a>
            <?php endif; ?>

            <a class="btn-back" href="scan_qr.php">
                ← Kembali ke Scan QR
            </a>

        <?php else: ?>

            <?php if ($pesan !== ''): ?>
                <div class="alert <?= e($tipe_pesan) ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>


            <!-- DETAIL KENDARAAN -->

            <div class="detail">

                <table>

                    <tr>
                        <td>No Transaksi</td>
                        <td>#<?= (int) $trx['id_parkir'] ?></td>
                    </tr>

                    <?php if (!empty($trx['id_booking'])): ?>
                    <tr>
                        <td>ID Booking</td>
                        <td>#<?= (int) $trx['id_booking'] ?></td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <td>Plat Nomor</td>
                        <td><span class="plat"><?= e($trx['plat_nomor'] ?? '-') ?></span></td>
                    </tr>

                    <tr>
                        <td>Kendaraan</td>
                        <td>
                            <?= e(ucfirst($trx['jenis_kendaraan'] ?? '-')) ?>
                            <?php
                            $ket = trim(($trx['merk'] ?? '') . ' ' . ($trx['warna'] ?? ''));
                            if ($ket !== '') {
                                echo '· ' . e($ket);
                            }
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Pemilik</td>
                        <td><?= e(!empty($trx['pemilik']) ? $trx['pemilik'] : '-') ?></td>
                    </tr>

                    <tr>
                        <td>Area</td>
                        <td><?= e(!empty($trx['nama_area']) ? $trx['nama_area'] : 'Belum ditentukan') ?></td>
                    </tr>

                    <tr>
                        <td>Waktu Masuk</td>
                        <td><?= e($trx['waktu_masuk']) ?></td>
                    </tr>

                    <tr>
                        <td>Waktu Keluar</td>
                        <td><?= e($sekarang) ?></td>
                    </tr>

                    <?php if (!empty($trx['estimasi_booking'])): ?>
                    <tr>
                        <td>Estimasi Booking</td>
                        <td><?= e($trx['estimasi_booking']) ?> Jam</td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <td>Tarif</td>
                        <td><?= rupiah($tarif_per_jam) ?> / jam</td>
                    </tr>

                </table>

            </div>


            <form method="POST" id="formKeluar" autocomplete="off">

                <input type="hidden" name="id_parkir" value="<?= (int) $id_parkir ?>">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">


                <!-- DURASI -->

                <label class="field" for="durasi_jam">Durasi (jam)</label>

                <input
                    type="number"
                    name="durasi_jam"
                    id="durasi_jam"
                    min="1"
                    max="999"
                    step="1"
                    value="<?= (int) $durasi_input ?>"
                    required
                >

                <div class="hint">
                    Dihitung otomatis <?= (int) $durasi_default ?> jam
                    (dibulatkan ke atas per jam). Bisa diubah bila perlu.
                </div>

                <?php if (!empty($trx['estimasi_booking']) && $durasi_default > (int) $trx['estimasi_booking']): ?>
                    <div class="hint warn">
                        ⚠ Melebihi estimasi booking (<?= (int) $trx['estimasi_booking'] ?> jam).
                    </div>
                <?php endif; ?>


                <!-- TOTAL -->

                <div class="total-box">
                    <div class="total-label">TOTAL BAYAR</div>
                    <div class="total-nilai" id="totalTampil"><?= rupiah($total_tampil) ?></div>
                </div>


                <!-- METODE -->

                <label class="field">Metode Pembayaran</label>

                <div class="metode">

                    <label>
                        <input
                            type="radio"
                            name="metode"
                            value="<?= e($METODE_CASH) ?>"
                            <?= $metode_input !== $METODE_QRIS ? 'checked' : '' ?>
                        >
                        <span>💵 Cash</span>
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="metode"
                            value="<?= e($METODE_QRIS) ?>"
                            <?= $metode_input === $METODE_QRIS ? 'checked' : '' ?>
                        >
                        <span>📱 QRIS</span>
                    </label>

                </div>


                <!-- PANEL CASH -->

                <div id="panelCash">

                    <label class="field" for="uang_diterima">Uang diterima</label>

                    <input
                        type="text"
                        name="uang_diterima"
                        id="uang_diterima"
                        inputmode="numeric"
                        placeholder="0"
                        value="<?= e($uang_input) ?>"
                    >

                    <div class="quick">
                        <button type="button" data-uang="pas">Uang Pas</button>
                        <button type="button" data-uang="20000">20.000</button>
                        <button type="button" data-uang="50000">50.000</button>
                        <button type="button" data-uang="100000">100.000</button>
                    </div>

                    <div class="kembalian" id="kembalianBox"></div>

                </div>


                <!-- PANEL QRIS -->

                <div id="panelQris" class="hidden">

                    <div class="qris-box">

                        <?php if ($QRIS_IMAGE !== ''): ?>
                            <img src="<?= e($QRIS_IMAGE) ?>" alt="QRIS">
                        <?php else: ?>
                            <div class="hint warn" style="margin-bottom: 10px;">
                                ⚠ File qris.png tidak ditemukan.
                                Letakkan qris.png di folder yang sama dengan
                                proses_keluar.php.
                            </div>
                        <?php endif; ?>

                        Minta pengguna membayar <strong id="totalQris"><?= rupiah($total_tampil) ?></strong>
                        lewat QRIS.<br>
                        Tekan tombol di bawah <strong>setelah pembayaran terkonfirmasi</strong>.

                    </div>

                </div>


                <button type="submit" class="btn-submit">
                    ✅ Selesaikan &amp; Cetak Struk
                </button>

            </form>

            <a class="btn-back" href="scan_qr.php">
                ← Batal, kembali ke Scan QR
            </a>

        <?php endif; ?>


        <div class="footer">
            Parking Management System
        </div>

    </div>

</div>


<?php if ($fatal === ''): ?>

<script>

const TARIF_PER_JAM = <?= json_encode((float) $tarif_per_jam) ?>;
const METODE_CASH   = <?= json_encode($METODE_CASH) ?>;

const elDurasi   = document.getElementById("durasi_jam");
const elTotal    = document.getElementById("totalTampil");
const elTotalQr  = document.getElementById("totalQris");
const elUang     = document.getElementById("uang_diterima");
const elKembali  = document.getElementById("kembalianBox");
const panelCash  = document.getElementById("panelCash");
const panelQris  = document.getElementById("panelQris");


function rupiah(angka) {

    return "Rp " + Math.round(angka).toLocaleString("id-ID");
}

function hitungTotal() {

    let durasi = parseInt(elDurasi.value, 10);

    if (isNaN(durasi) || durasi < 1) {
        durasi = 1;
    }

    return Math.round(durasi * TARIF_PER_JAM);
}

function angkaUang() {

    return parseInt((elUang.value || "").replace(/\D/g, ""), 10) || 0;
}

function metodeAktif() {

    const terpilih = document.querySelector('input[name="metode"]:checked');

    return terpilih ? terpilih.value : "";
}

function perbarui() {

    const total = hitungTotal();

    elTotal.textContent = rupiah(total);
    elTotalQr.textContent = rupiah(total);

    const cash = metodeAktif() === METODE_CASH;

    panelCash.classList.toggle("hidden", !cash);
    panelQris.classList.toggle("hidden", cash);

    if (!cash) {
        return;
    }

    const uang = angkaUang();

    if (uang === 0) {

        elKembali.innerHTML = "";

    } else if (uang >= total) {

        elKembali.innerHTML =
            '<span class="ok">Kembalian: <strong>' +
            rupiah(uang - total) + '</strong></span>';

    } else {

        elKembali.innerHTML =
            '<span class="kurang">Kurang: <strong>' +
            rupiah(total - uang) + '</strong></span>';
    }
}


/* Format ribuan saat mengetik uang */

elUang.addEventListener("input", function () {

    const angka = angkaUang();

    elUang.value = angka > 0 ? angka.toLocaleString("id-ID") : "";

    perbarui();
});

elDurasi.addEventListener("input", perbarui);

document.querySelectorAll('input[name="metode"]').forEach(function (r) {
    r.addEventListener("change", perbarui);
});

document.querySelectorAll(".quick button").forEach(function (tombol) {

    tombol.addEventListener("click", function () {

        const nilai = tombol.getAttribute("data-uang");

        const uang = nilai === "pas" ? hitungTotal() : parseInt(nilai, 10);

        elUang.value = uang.toLocaleString("id-ID");

        perbarui();
    });
});


/* Cegah submit ganda */

document.getElementById("formKeluar").addEventListener("submit", function (ev) {

    const total = hitungTotal();

    if (metodeAktif() === METODE_CASH && angkaUang() < total) {

        ev.preventDefault();

        alert("Uang diterima kurang dari total bayar.");

        return;
    }

    const tombol = this.querySelector(".btn-submit");

    tombol.disabled = true;

    tombol.textContent = "Memproses...";
});

perbarui();

</script>

<?php endif; ?>

</body>

</html>