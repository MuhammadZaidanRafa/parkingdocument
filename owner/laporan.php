<?php
session_start();
require_once "../db.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
  
if (($_SESSION['role'] ?? '') !== 'owner') {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Akses Ditolak</title>
        <style>
            body{font-family:system-ui,sans-serif;background:#F5F7FA;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;color:#1C2B39;}
            .box{background:#fff;padding:40px 50px;border-radius:12px;box-shadow:0 4px 20px rgba(18,60,93,.08);text-align:center;max-width:360px;}
            h1{font-size:20px;margin:0 0 8px;color:#123C5D;}
            p{margin:0 0 20px;color:#5B6B7A;font-size:14px;}
            a{display:inline-block;padding:10px 22px;background:#123C5D;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;}
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Akses Ditolak</h1>
            <p>Halaman ini hanya bisa diakses oleh pemilik (owner).</p>
            <a href="dashboard.php">Kembali ke Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string {
    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function jamFormat($value): string {
    $value = (float) $value;
    return ($value == (int) $value) ? number_format($value, 0) : number_format($value, 1);
}

$tgl_awal      = trim($_GET['tgl_awal']  ?? '');
$tgl_akhir     = trim($_GET['tgl_akhir'] ?? '');
$statusRaw     = $_GET['status'] ?? '';
$formSubmitted = isset($_GET['filter']);
$errorFilter   = '';
$tanggalAktif  = false;

// whitelist status so it can only ever be masuk, keluar, or "semua" (empty)
$statusFilter = in_array($statusRaw, ['masuk', 'keluar'], true) ? $statusRaw : '';

$sql = "SELECT
            tb_transaksi.*,
            tb_kendaraan.plat_nomor,
            tb_kendaraan.pemilik,
            tb_kendaraan.jenis_kendaraan,
            tb_area_parkir.nama_area
        FROM tb_transaksi
        JOIN tb_kendaraan   ON tb_transaksi.id_kendaraan = tb_kendaraan.id_kendaraan
        JOIN tb_area_parkir ON tb_transaksi.id_area = tb_area_parkir.id_area
        WHERE 1=1";

$params = [];
$types  = '';

if ($statusFilter !== '') {
    $sql .= " AND tb_transaksi.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($formSubmitted && $tgl_awal !== '' && $tgl_akhir !== '') {
    $awalDate  = DateTime::createFromFormat('Y-m-d', $tgl_awal);
    $akhirDate = DateTime::createFromFormat('Y-m-d', $tgl_akhir);

    if ($awalDate && $akhirDate) {
        // swap automatically if the user picked the dates in reverse order
        if ($tgl_awal > $tgl_akhir) {
            [$tgl_awal, $tgl_akhir] = [$tgl_akhir, $tgl_awal];
        }

        // filter by waktu_masuk (always present) so vehicles still parked are included too
        $sql .= " AND DATE(tb_transaksi.waktu_masuk) BETWEEN ? AND ?";
        $params[] = $tgl_awal;
        $params[] = $tgl_akhir;
        $types   .= 'ss';
        $tanggalAktif = true;
    } else {
        $errorFilter = 'Format tanggal tidak valid, filter tanggal diabaikan.';
    }
}

$sql .= " ORDER BY tb_transaksi.waktu_masuk DESC, tb_transaksi.id_parkir DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die('Terjadi kesalahan saat menyiapkan laporan. Silakan coba lagi.');
}
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows   = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$totalTransaksi  = count($rows);
$totalMasuk      = 0;
$totalKeluar     = 0;
$totalPendapatan = 0;

// data untuk grafik
$perHari  = [];   // 'Y-m-d' => ['transaksi' => n, 'pendapatan' => x]
$perJenis = [];   // jenis kendaraan => jumlah transaksi
$perArea  = [];   // nama area => jumlah transaksi

foreach ($rows as $row) {
    if ($row['status'] === 'keluar') {
        $totalKeluar++;
        $totalPendapatan += (float) $row['biaya_total'];
    } else {
        $totalMasuk++;
    }

    // dikelompokkan berdasarkan tanggal masuk (sama dengan dasar filter tanggal)
    $tglKey = date('Y-m-d', strtotime($row['waktu_masuk']));
    if (!isset($perHari[$tglKey])) {
        $perHari[$tglKey] = ['transaksi' => 0, 'pendapatan' => 0];
    }
    $perHari[$tglKey]['transaksi']++;
    if ($row['status'] === 'keluar') {
        $perHari[$tglKey]['pendapatan'] += (float) $row['biaya_total'];
    }

    $jenisKey = ucfirst(strtolower(trim((string) $row['jenis_kendaraan'])));
    if ($jenisKey === '') {
        $jenisKey = 'Lainnya';
    }
    $perJenis[$jenisKey] = ($perJenis[$jenisKey] ?? 0) + 1;

    $areaKey = (string) $row['nama_area'];
    $perArea[$areaKey] = ($perArea[$areaKey] ?? 0) + 1;
}

// jika periode dipilih dan tidak terlalu panjang, tampilkan juga hari tanpa transaksi (nilai 0)
if ($tanggalAktif) {
    $d1 = new DateTime($tgl_awal);
    $d2 = new DateTime($tgl_akhir);
    if ($d1->diff($d2)->days <= 60) {
        for ($d = clone $d1; $d <= $d2; $d->modify('+1 day')) {
            $k = $d->format('Y-m-d');
            if (!isset($perHari[$k])) {
                $perHari[$k] = ['transaksi' => 0, 'pendapatan' => 0];
            }
        }
    }
}
ksort($perHari);
arsort($perArea);

$chartHariLabels     = [];
$chartHariTransaksi  = [];
$chartHariPendapatan = [];
foreach ($perHari as $k => $v) {
    $chartHariLabels[]     = date('d/m', strtotime($k));
    $chartHariTransaksi[]  = $v['transaksi'];
    $chartHariPendapatan[] = $v['pendapatan'];
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
$chartData = [
    'hari' => [
        'labels'     => $chartHariLabels,
        'transaksi'  => $chartHariTransaksi,
        'pendapatan' => $chartHariPendapatan,
    ],
    'jenis' => [
        'labels' => array_keys($perJenis),
        'values' => array_values($perJenis),
    ],
    'area' => [
        'labels' => array_keys($perArea),
        'values' => array_values($perArea),
    ],
];

$statusLabel = $statusFilter === 'keluar'
    ? 'Sudah Keluar'
    : ($statusFilter === 'masuk' ? 'Sedang Parkir' : 'Semua Status');

$dicetakOleh = e($_SESSION['nama'] ?? $_SESSION['username'] ?? 'Owner');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Laporan Transaksi Parkir</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
    :root{
        --navy:#123C5D;
        --navy-dark:#0C2A41;
        --teal:#2F8F82;
        --ink:#1C2B39;
        --muted:#5B6B7A;
        --line:#E1E7EC;
        --bg:#F5F7FA;
        --card:#FFFFFF;
    }

    *{ box-sizing:border-box; }

    body{
        font-family:'Inter',system-ui,-apple-system,sans-serif;
        background:var(--bg);
        color:var(--ink);
        margin:0;
        padding:32px 16px;
    }

    .sheet{
        max-width:1150px;
        margin:0 auto;
        background:var(--card);
        border-radius:14px;
        overflow:hidden;
        box-shadow:0 10px 30px rgba(18,60,93,.08);
    }

    .header{
        background:linear-gradient(135deg,var(--navy),var(--navy-dark));
        color:#fff;
        padding:28px 36px 22px;
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        flex-wrap:wrap;
    }

    .header h1{
        font-family:'Plus Jakarta Sans',sans-serif;
        font-size:22px;
        font-weight:700;
        margin:0 0 4px;
        letter-spacing:.2px;
    }

    .header p{
        margin:0;
        font-size:13px;
        color:#CBDCE8;
    }

    .header .meta{
        text-align:right;
        font-size:12px;
        color:#CBDCE8;
        line-height:1.6;
    }

    .perforation{
        height:0;
        border-top:2px dashed rgba(18,60,93,.18);
        position:relative;
        margin:0 36px;
    }
    .perforation::before,
    .perforation::after{
        content:"";
        position:absolute;
        top:-7px;
        width:14px;
        height:14px;
        border-radius:50%;
        background:var(--bg);
    }
    .perforation::before{ left:-43px; }
    .perforation::after{ right:-43px; }

    .body{
        padding:28px 36px 36px;
    }

    form.filter{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        align-items:flex-end;
        margin-bottom:24px;
    }

    .field label{
        display:block;
        font-size:12px;
        color:var(--muted);
        margin-bottom:6px;
        font-weight:500;
    }

    .field input[type=date],
    .field select{
        padding:9px 12px;
        border:1px solid var(--line);
        border-radius:8px;
        font-size:14px;
        font-family:inherit;
        color:var(--ink);
        background:#fff;
    }

    .field input[type=date]:focus,
    .field select:focus,
    button:focus-visible,
    a.btn:focus-visible{
        outline:2px solid var(--teal);
        outline-offset:2px;
    }

    button, a.btn{
        padding:10px 20px;
        border:none;
        border-radius:8px;
        font-size:14px;
        font-weight:600;
        cursor:pointer;
        font-family:inherit;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }

    .btn-primary{ background:var(--navy); color:#fff; }
    .btn-primary:hover{ background:var(--navy-dark); }

    .btn-outline{ background:#fff; color:var(--navy); border:1px solid var(--line); }
    .btn-outline:hover{ background:var(--bg); }

    .btn-ghost{ background:transparent; color:var(--muted); border:1px solid var(--line); }

    .error-note{
        background:#FDF3EC;
        border:1px solid #F0D9C4;
        color:#8A5322;
        padding:10px 14px;
        border-radius:8px;
        font-size:13px;
        margin-bottom:20px;
    }

    .stats{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:16px;
        margin-bottom:26px;
    }

    .stat{
        border:1px solid var(--line);
        border-radius:10px;
        padding:16px 18px;
    }
    .stat .label{
        font-size:12px;
        color:var(--muted);
        margin-bottom:6px;
    }
    .stat .value{
        font-family:'Plus Jakarta Sans',sans-serif;
        font-size:20px;
        font-weight:700;
        color:var(--navy);
    }
    .stat.accent .value{ color:var(--teal); }

    .period-tag{
        display:inline-block;
        font-family:'Inter',monospace;
        font-size:11px;
        letter-spacing:.4px;
        color:var(--muted);
        margin-bottom:16px;
    }

    /* ===== Grafik ===== */
    .charts{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:16px;
        margin-bottom:28px;
    }

    .chart-card{
        border:1px solid var(--line);
        border-radius:10px;
        padding:16px 18px 14px;
        page-break-inside:avoid;
        break-inside:avoid;
    }
    .chart-card.wide{ grid-column:1 / -1; }

    .chart-card h2{
        font-family:'Plus Jakarta Sans',sans-serif;
        font-size:14px;
        font-weight:700;
        color:var(--navy);
        margin:0 0 2px;
    }
    .chart-card .sub{
        font-size:11.5px;
        color:var(--muted);
        margin:0 0 12px;
    }

    .chart-box{
        position:relative;
        height:260px;
    }
    .chart-card.wide .chart-box{ height:300px; }

    table{
        width:100%;
        border-collapse:collapse;
        font-size:13.5px;
    }

    thead th{
        background:var(--navy);
        color:#fff;
        font-weight:600;
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.4px;
        padding:12px 10px;
        text-align:left;
    }

    thead th.num, td.num{ text-align:right; }
    thead th.center, td.center{ text-align:center; }

    tbody td{
        padding:11px 10px;
        border-bottom:1px solid var(--line);
        vertical-align:middle;
    }

    tbody tr:nth-child(even){ background:#FAFBFC; }
    tbody tr:hover{ background:#F1F6F8; }

    .badge{
        display:inline-block;
        padding:3px 10px;
        border-radius:999px;
        font-size:11.5px;
        font-weight:600;
        white-space:nowrap;
    }
    .badge.motor{ background:#E4F3F1; color:var(--teal); }
    .badge.mobil{ background:#E7EEF4; color:var(--navy); }
    .badge.status-selesai{ background:#E5F4EA; color:#2E7D46; }
    .badge.status-aktif{ background:#FDF0DC; color:#B9770E; }

    .note{
        font-size:12px;
        color:var(--muted);
        margin-top:10px;
    }

    .empty-row td{
        text-align:center;
        padding:48px 20px;
        color:var(--muted);
        font-size:14px;
    }

    tfoot td{
        padding:16px 10px;
        font-weight:700;
        font-size:15px;
        border-top:2px solid var(--navy);
    }
    tfoot .label-cell{ text-align:right; color:var(--muted); font-weight:600; font-size:13px; }
    tfoot .value-cell{ text-align:right; color:var(--navy); }

    .footer-actions{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-top:28px;
        flex-wrap:wrap;
        gap:12px;
    }

    .print-signoff{ display:none; }

    @media print{
        body{ background:#fff; padding:0; }
        .sheet{ box-shadow:none; border-radius:0; max-width:100%; }
        .header{ background:var(--navy) !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        thead th{ background:var(--navy) !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        form.filter, .footer-actions{ display:none; }
        tbody tr{ page-break-inside:avoid; }
        .charts{ page-break-after:always; break-after:page; }
        .chart-card{ -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        @page{ size:A4 landscape; margin:14mm; }
        .print-signoff{
            display:flex;
            justify-content:flex-end;
            gap:60px;
            margin-top:60px;
            font-size:13px;
        }
        .print-signoff div{ text-align:center; }
        .print-signoff .line{
            margin-top:60px;
            border-top:1px solid #333;
            padding-top:6px;
            width:170px;
        }
    }

    @media (max-width:720px){
        .stats{ grid-template-columns:1fr; }
        .charts{ grid-template-columns:1fr; }
        .header{ flex-direction:column; }
        .header .meta{ text-align:left; }
        table{ display:block; overflow-x:auto; white-space:nowrap; }
    }
</style>
</head>
<body>

<div class="sheet">

    <div class="header">
        <div>
            <h1>Laporan Transaksi Parkir</h1>
            <p>Rekap seluruh transaksi &mdash; sistem parkir</p>
        </div>
        <div class="meta">
            Dicetak oleh <?= $dicetakOleh ?><br>
            <?= e(date('d M Y, H:i')) ?> WIB
        </div>
    </div>

    <div class="perforation"></div>

    <div class="body">

        <form class="filter" method="GET">
            <div class="field">
                <label for="tgl_awal">Dari tanggal</label>
                <input type="date" id="tgl_awal" name="tgl_awal" value="<?= e($tgl_awal) ?>">
            </div>
            <div class="field">
                <label for="tgl_akhir">Sampai tanggal</label>
                <input type="date" id="tgl_akhir" name="tgl_akhir" value="<?= e($tgl_akhir) ?>">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="masuk" <?= $statusFilter === 'masuk' ? 'selected' : '' ?>>Sedang Parkir</option>
                    <option value="keluar" <?= $statusFilter === 'keluar' ? 'selected' : '' ?>>Sudah Keluar</option>
                </select>
            </div>
            <button type="submit" name="filter" value="1" class="btn-primary">Filter</button>
            <?php if ($formSubmitted): ?>
                <a href="laporan.php" class="btn-outline">Reset</a>
            <?php endif; ?>
            <button type="button" onclick="window.print()" class="btn-outline">Cetak / Simpan PDF</button>
        </form>

        <?php if ($errorFilter): ?>
            <div class="error-note"><?= e($errorFilter) ?></div>
        <?php endif; ?>

        <span class="period-tag">
            PERIODE:
            <?php if ($tanggalAktif): ?>
                <?= e(date('d/m/Y', strtotime($tgl_awal))) ?> &ndash; <?= e(date('d/m/Y', strtotime($tgl_akhir))) ?>
            <?php else: ?>
                SELURUH TANGGAL
            <?php endif; ?>
            &nbsp;&middot;&nbsp;STATUS: <?= e($statusLabel) ?>
        </span>

        <div class="stats">
            <div class="stat">
                <div class="label">Total Transaksi</div>
                <div class="value"><?= number_format($totalTransaksi, 0, ',', '.') ?></div>
            </div>
            <div class="stat">
                <div class="label">Sedang Parkir</div>
                <div class="value"><?= number_format($totalMasuk, 0, ',', '.') ?></div>
            </div>
            <div class="stat">
                <div class="label">Sudah Keluar</div>
                <div class="value"><?= number_format($totalKeluar, 0, ',', '.') ?></div>
            </div>
            <div class="stat accent">
                <div class="label">Total Pendapatan</div>
                <div class="value"><?= rupiah($totalPendapatan) ?></div>
            </div>
        </div>

        <?php if (!empty($rows)): ?>
        <div class="charts">
            <div class="chart-card wide">
                <h2>Pendapatan &amp; Jumlah Transaksi per Hari</h2>
                <p class="sub">Dikelompokkan berdasarkan tanggal masuk. Pendapatan hanya dari transaksi yang sudah selesai.</p>
                <div class="chart-box"><canvas id="chartHari"></canvas></div>
            </div>
            <div class="chart-card">
                <h2>Komposisi Jenis Kendaraan</h2>
                <p class="sub">Jumlah transaksi per jenis kendaraan.</p>
                <div class="chart-box"><canvas id="chartJenis"></canvas></div>
            </div>
            <div class="chart-card">
                <h2>Transaksi per Area Parkir</h2>
                <p class="sub">Jumlah transaksi di tiap area.</p>
                <div class="chart-box"><canvas id="chartArea"></canvas></div>
            </div>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th class="center">No</th>
                    <th>Plat Nomor</th>
                    <th>Pemilik</th>
                    <th>Jenis</th>
                    <th>Area</th>
                    <th class="center">Status</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Keluar</th>
                    <th class="center">Durasi</th>
                    <th class="num">Biaya</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr class="empty-row">
                    <td colspan="10">Belum ada transaksi pada periode atau status yang dipilih.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $row): ?>
                    <?php
                        $jenis       = strtolower((string) $row['jenis_kendaraan']);
                        $sudahKeluar = $row['status'] === 'keluar';
                    ?>
                    <tr>
                        <td class="center"><?= $i + 1 ?></td>
                        <td><?= e($row['plat_nomor']) ?></td>
                        <td><?= e($row['pemilik']) ?></td>
                        <td>
                            <span class="badge <?= $jenis === 'motor' ? 'motor' : 'mobil' ?>">
                                <?= e(ucfirst($row['jenis_kendaraan'])) ?>
                            </span>
                        </td>
                        <td><?= e($row['nama_area']) ?></td>
                        <td class="center">
                            <span class="badge <?= $sudahKeluar ? 'status-selesai' : 'status-aktif' ?>">
                                <?= $sudahKeluar ? 'Selesai' : 'Sedang Parkir' ?>
                            </span>
                        </td>
                        <td><?= e(date('d/m/Y H:i', strtotime($row['waktu_masuk']))) ?></td>
                        <td><?= $row['waktu_keluar'] ? e(date('d/m/Y H:i', strtotime($row['waktu_keluar']))) : '&ndash;' ?></td>
                        <td class="center"><?= $sudahKeluar ? jamFormat($row['durasi_jam']) . ' jam' : '&ndash;' ?></td>
                        <td class="num"><?= $sudahKeluar ? rupiah($row['biaya_total']) : '&ndash;' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
            <tfoot>
                <tr>
                    <td colspan="9" class="label-cell">Total Pendapatan (dari transaksi selesai)</td>
                    <td class="value-cell"><?= rupiah($totalPendapatan) ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        <?php if ($totalMasuk > 0): ?>
            <p class="note">* Durasi dan biaya untuk kendaraan yang masih parkir baru tersedia setelah kendaraan keluar.</p>
        <?php endif; ?>

        <div class="print-signoff">
            <div>
                <div class="line">Petugas Parkir</div>
            </div>
            <div>
                <div class="line">Mengetahui, Pemilik</div>
            </div>
        </div>

        <div class="footer-actions">
            <a href="../dashboard.php" class="btn-ghost">&larr; Kembali ke Dashboard</a>
        </div>

    </div>
</div>

<?php if (!empty($rows)): ?>
<script>
(function () {
    if (typeof Chart === 'undefined') { return; } // CDN gagal dimuat: halaman tetap berfungsi tanpa grafik

    var data = <?= json_encode($chartData, $jsonFlags) ?>;

    var NAVY = '#123C5D', TEAL = '#2F8F82', MUTED = '#5B6B7A', LINE = '#E1E7EC';
    var PALETTE = [TEAL, NAVY, '#E0A03A', '#8A5CB8', '#C4574B', '#6C8EAD'];

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = MUTED;

    function fmtRupiah(v) {
        return 'Rp ' + Number(v).toLocaleString('id-ID');
    }

    // 1. Pendapatan (bar) + jumlah transaksi (garis) per hari
    var elHari = document.getElementById('chartHari');
    if (elHari) {
        new Chart(elHari, {
            data: {
                labels: data.hari.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Pendapatan',
                        data: data.hari.pendapatan,
                        backgroundColor: TEAL,
                        borderRadius: 4,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Jumlah Transaksi',
                        data: data.hari.transaksi,
                        borderColor: NAVY,
                        backgroundColor: NAVY,
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ' +
                                    (ctx.dataset.yAxisID === 'y' ? fmtRupiah(ctx.parsed.y) : ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: LINE },
                        ticks: { callback: function (v) { return fmtRupiah(v); } }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    // 2. Komposisi jenis kendaraan
    var elJenis = document.getElementById('chartJenis');
    if (elJenis) {
        new Chart(elJenis, {
            type: 'doughnut',
            data: {
                labels: data.jenis.labels,
                datasets: [{
                    data: data.jenis.values,
                    backgroundColor: PALETTE.slice(0, Math.max(data.jenis.values.length, 1)),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total ? Math.round(ctx.parsed / total * 100) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Transaksi per area parkir
    var elArea = document.getElementById('chartArea');
    if (elArea) {
        new Chart(elArea, {
            type: 'bar',
            data: {
                labels: data.area.labels,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: data.area.values,
                    backgroundColor: NAVY,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: LINE }, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
})();
</script>
<?php endif; ?>

</body>
</html>