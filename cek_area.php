<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak ditemukan. Pastikan db.php menggunakan variabel \$conn.");
}

/*
|--------------------------------------------------------------------------
| KONFIGURASI LANDING PAGE
|--------------------------------------------------------------------------
*/
$landingPage = 'index.php';


/*
|--------------------------------------------------------------------------
| BUAT TABEL ULASAN JIKA BELUM ADA
|--------------------------------------------------------------------------
*/
$sqlCreateUlasan = "
    CREATE TABLE IF NOT EXISTS tb_ulasan (
        id_ulasan INT(11) AUTO_INCREMENT PRIMARY KEY,
        id_area INT(11) NOT NULL,
        id_user INT(11) NULL,
        nama_pengulas VARCHAR(100) NOT NULL,
        rating DECIMAL(2,1) NOT NULL DEFAULT 5.0,
        komentar TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_ulasan_area (id_area),

        CONSTRAINT fk_ulasan_area
            FOREIGN KEY (id_area)
            REFERENCES tb_area_parkir(id_area)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

$conn->query($sqlCreateUlasan);


/*
|--------------------------------------------------------------------------
| SINKRONISASI SEMUA AREA
|--------------------------------------------------------------------------
| terisi selalu dihitung dari transaksi aktif.
|
| Kendaraan aktif:
| status = 'masuk'
| DAN waktu_keluar IS NULL
|--------------------------------------------------------------------------
*/
function sinkronisasiSemuaArea(mysqli $conn): bool
{
    $sql = "
        UPDATE tb_area_parkir a
        LEFT JOIN (
            SELECT
                id_area,
                COUNT(*) AS jumlah_aktif
            FROM tb_transaksi
            WHERE status = 'masuk'
              AND waktu_keluar IS NULL
              AND id_area IS NOT NULL
            GROUP BY id_area
        ) t
            ON t.id_area = a.id_area
        SET a.terisi = COALESCE(t.jumlah_aktif, 0)
    ";

    return $conn->query($sql);
}


/*
|--------------------------------------------------------------------------
| SINKRONKAN TERISI
|--------------------------------------------------------------------------
*/
sinkronisasiSemuaArea($conn);


/*
|--------------------------------------------------------------------------
| PESAN
|--------------------------------------------------------------------------
*/
$pesan = "";
$tipePesan = "";


/*
|--------------------------------------------------------------------------
| SUBMIT ULASAN
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_ulasan'])
) {

    $id_area = (int) ($_POST['id_area'] ?? 0);
    $rating = (float) ($_POST['rating'] ?? 5);
    $komentar = trim($_POST['komentar'] ?? '');

    /*
    |----------------------------------------------------------------------
    | NAMA PENGULAS
    |----------------------------------------------------------------------
    */
    if (
        isset($_SESSION['nama_lengkap'])
        && $_SESSION['nama_lengkap'] !== ''
    ) {

        $nama_pengulas = trim($_SESSION['nama_lengkap']);

    } elseif (
        isset($_SESSION['nama'])
        && $_SESSION['nama'] !== ''
    ) {

        $nama_pengulas = trim($_SESSION['nama']);

    } else {

        $nama_pengulas = trim(
            $_POST['nama_pengulas'] ?? 'Pengguna'
        );
    }

    if ($nama_pengulas === '') {
        $nama_pengulas = 'Pengguna';
    }

    /*
    |----------------------------------------------------------------------
    | ID USER
    |----------------------------------------------------------------------
    */
    $id_user = isset($_SESSION['id_user'])
        ? (int) $_SESSION['id_user']
        : null;


    /*
    |----------------------------------------------------------------------
    | VALIDASI
    |----------------------------------------------------------------------
    */
    if ($id_area <= 0) {

        $pesan = "Area parkir tidak valid.";
        $tipePesan = "danger";

    } elseif ($rating < 1 || $rating > 5) {

        $pesan = "Rating harus antara 1 sampai 5.";
        $tipePesan = "danger";

    } elseif ($komentar === '') {

        $pesan = "Komentar tidak boleh kosong.";
        $tipePesan = "danger";

    } elseif (mb_strlen($nama_pengulas) > 100) {

        $pesan = "Nama pengulas maksimal 100 karakter.";
        $tipePesan = "danger";

    } else {

        /*
        |------------------------------------------------------------------
        | CEK AREA
        |------------------------------------------------------------------
        */
        $stmtArea = $conn->prepare("
            SELECT id_area
            FROM tb_area_parkir
            WHERE id_area = ?
            LIMIT 1
        ");

        if (!$stmtArea) {

            $pesan = "Gagal memproses area.";
            $tipePesan = "danger";

        } else {

            $stmtArea->bind_param("i", $id_area);
            $stmtArea->execute();

            $areaResult = $stmtArea->get_result();

            if ($areaResult->num_rows === 0) {

                $pesan = "Area parkir tidak ditemukan.";
                $tipePesan = "danger";

            } else {

                /*
                |----------------------------------------------------------
                | SIMPAN ULASAN
                |----------------------------------------------------------
                */
                if ($id_user === null) {

                    $stmt = $conn->prepare("
                        INSERT INTO tb_ulasan
                        (
                            id_area,
                            id_user,
                            nama_pengulas,
                            rating,
                            komentar
                        )
                        VALUES (?, NULL, ?, ?, ?)
                    ");

                    if ($stmt) {

                        $stmt->bind_param(
                            "isds",
                            $id_area,
                            $nama_pengulas,
                            $rating,
                            $komentar
                        );
                    }

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO tb_ulasan
                        (
                            id_area,
                            id_user,
                            nama_pengulas,
                            rating,
                            komentar
                        )
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    if ($stmt) {

                        $stmt->bind_param(
                            "iisis",
                            $id_area,
                            $id_user,
                            $nama_pengulas,
                            $rating,
                            $komentar
                        );
                    }
                }

                if (!isset($stmt) || !$stmt) {

                    $pesan = "Gagal menyiapkan penyimpanan ulasan.";
                    $tipePesan = "danger";

                } elseif ($stmt->execute()) {

                    /*
                    |------------------------------------------------------
                    | HITUNG RATA-RATA RATING
                    |------------------------------------------------------
                    */
                    $stmtRating = $conn->prepare("
                        SELECT COALESCE(AVG(rating), 0)
                        FROM tb_ulasan
                        WHERE id_area = ?
                    ");

                    if ($stmtRating) {

                        $stmtRating->bind_param("i", $id_area);
                        $stmtRating->execute();

                        $ratingResult = $stmtRating->get_result();
                        $ratingData = $ratingResult->fetch_row();

                        $ratingRata = (float) ($ratingData[0] ?? 0);

                        /*
                        |--------------------------------------------------
                        | UPDATE RATING AREA
                        |--------------------------------------------------
                        */
                        $stmtUpdate = $conn->prepare("
                            UPDATE tb_area_parkir
                            SET rating = ?
                            WHERE id_area = ?
                        ");

                        if ($stmtUpdate) {

                            $stmtUpdate->bind_param(
                                "di",
                                $ratingRata,
                                $id_area
                            );

                            $stmtUpdate->execute();
                            $stmtUpdate->close();
                        }

                        $stmtRating->close();
                    }

                    $pesan = "Ulasan berhasil dikirim.";
                    $tipePesan = "success";

                } else {

                    $pesan = "Gagal menyimpan ulasan: " . $stmt->error;
                    $tipePesan = "danger";
                }

                if (isset($stmt) && $stmt) {
                    $stmt->close();
                }
            }

            $stmtArea->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA AREA
|--------------------------------------------------------------------------
| terisi dihitung langsung dari tb_transaksi.
|--------------------------------------------------------------------------
*/
$sqlArea = "
    SELECT
        a.id_area,
        a.nama_area,
        a.kapasitas,

        COUNT(t.id_parkir) AS terisi,

        COALESCE(a.rating, 0.0) AS rating

    FROM tb_area_parkir a

    LEFT JOIN tb_transaksi t
        ON t.id_area = a.id_area
        AND t.status = 'masuk'
        AND t.waktu_keluar IS NULL

    GROUP BY
        a.id_area,
        a.nama_area,
        a.kapasitas,
        a.rating

    ORDER BY a.nama_area ASC
";

$resultArea = $conn->query($sqlArea);

if (!$resultArea) {
    die("Gagal mengambil data area: " . $conn->error);
}


/*
|--------------------------------------------------------------------------
| TOTAL DATA
|--------------------------------------------------------------------------
*/
$totalKapasitas = 0;
$totalTerisi = 0;
$areas = [];

while ($area = $resultArea->fetch_assoc()) {

    $area['id_area'] = (int) $area['id_area'];
    $area['kapasitas'] = (int) $area['kapasitas'];
    $area['terisi'] = (int) $area['terisi'];
    $area['rating'] = (float) $area['rating'];

    $area['tersedia'] = max(
        $area['kapasitas'] - $area['terisi'],
        0
    );

    $totalKapasitas += $area['kapasitas'];
    $totalTerisi += $area['terisi'];

    $areas[] = $area;
}

$totalTersedia = max(
    $totalKapasitas - $totalTerisi,
    0
);


/*
|--------------------------------------------------------------------------
| AMBIL ULASAN TERBARU PER AREA
|--------------------------------------------------------------------------
*/
$ulasanPerArea = [];

foreach ($areas as $area) {

    $id_area = (int) $area['id_area'];

    $stmtUlasan = $conn->prepare("
        SELECT
            nama_pengulas,
            rating,
            komentar,
            created_at
        FROM tb_ulasan
        WHERE id_area = ?
        ORDER BY created_at DESC
        LIMIT 3
    ");

    if (!$stmtUlasan) {
        $ulasanPerArea[$id_area] = [];
        continue;
    }

    $stmtUlasan->bind_param("i", $id_area);
    $stmtUlasan->execute();

    $resultUlasan = $stmtUlasan->get_result();

    $ulasanPerArea[$id_area] = [];

    while ($ulasan = $resultUlasan->fetch_assoc()) {

        $ulasanPerArea[$id_area][] = $ulasan;
    }

    $stmtUlasan->close();
}


/*
|--------------------------------------------------------------------------
| STATUS AREA
|--------------------------------------------------------------------------
*/
function statusArea(int $kapasitas, int $terisi): array
{
    if ($kapasitas <= 0) {

        return [
            'text' => 'Tidak tersedia',
            'class' => 'danger',
            'icon' => 'bi-x-circle'
        ];
    }

    if ($terisi >= $kapasitas) {

        return [
            'text' => 'Penuh',
            'class' => 'danger',
            'icon' => 'bi-x-circle-fill'
        ];
    }

    $persen = ($terisi / $kapasitas) * 100;

    if ($persen >= 80) {

        return [
            'text' => 'Hampir penuh',
            'class' => 'warning',
            'icon' => 'bi-exclamation-triangle-fill'
        ];
    }

    return [
        'text' => 'Tersedia',
        'class' => 'success',
        'icon' => 'bi-check-circle-fill'
    ];
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

    <title>Status Area Parkir</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        .header {
            background: linear-gradient(
                135deg,
                #111827,
                #2563eb
            );

            color: white;
            padding: 35px 20px;
        }

        .header-title {
            min-width: 0;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .header-actions .btn {
            border-radius: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .summary-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .07);
            transition: .2s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, .10);
        }

        .area-card {
            border: 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .08);
            height: 100%;
            transition: .2s;
        }

        .area-card:hover {
            transform: translateY(-3px);
        }

        .area-header {
            padding: 20px;
            background: #111827;
            color: white;
        }

        .slot-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(45px, 1fr));

            gap: 8px;
        }

        .slot {
            height: 42px;
            border-radius: 9px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 12px;
            font-weight: bold;
        }

        .slot-empty {
            background: #dcfce7;
            color: #166534;
        }

        .slot-full {
            background: #fee2e2;
            color: #991b1b;
        }

        .review {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .stars {
            color: #f59e0b;
        }

        .stat-number {
            font-size: 30px;
            font-weight: 800;
        }

        .progress {
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width .5s ease;
        }

        .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .modal-header {
            background: #111827;
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        @media (max-width: 768px) {

            .header {
                padding: 28px 15px;
            }

            .header > .container > .d-flex {
                align-items: flex-start !important;
            }

            .header-actions {
                flex-direction: column;
            }

            .header-actions .btn {
                width: 100%;
            }

        }

        @media (max-width: 576px) {

            .header {
                padding: 25px 15px;
            }

            .header > .container > .d-flex {
                flex-direction: column;
            }

            .header-title {
                width: 100%;
            }

            .header-actions {
                width: 100%;
                flex-direction: row;
            }

            .header-actions .btn {
                flex: 1;
                font-size: 13px;
            }

            .stat-number {
                font-size: 24px;
            }

            .area-header {
                padding: 16px;
            }

        }

    </style>

</head>

<body>


<!-- =========================================================
     HEADER
========================================================= -->

<div class="header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center gap-3">

            <div class="header-title">

                <h2 class="fw-bold mb-1">

                    <i class="bi bi-p-square-fill"></i>

                    Status Area Parkir

                </h2>

                <p class="mb-0 opacity-75">

                    Informasi kapasitas parkir secara real-time

                </p>

            </div>


            <!-- =================================================
                 TOMBOL
            ================================================== -->

            <div class="header-actions">

                <!-- LANDING PAGE -->

                <a
                    href="<?= htmlspecialchars($landingPage) ?>"
                    class="btn btn-light"
                >

                    <i class="bi bi-house-door-fill"></i>

                    Landing Page

                </a>


                <!-- REFRESH -->

                <a
                    href="cek_area.php"
                    class="btn btn-light"
                >

                    <i class="bi bi-arrow-clockwise"></i>

                    Refresh

                </a>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     CONTENT
========================================================= -->

<div class="container py-4">


    <!-- =====================================================
         PESAN
    ====================================================== -->

    <?php if ($pesan !== ''): ?>

        <div
            class="alert alert-<?= htmlspecialchars($tipePesan) ?> alert-dismissible fade show"
            role="alert"
        >

            <?= htmlspecialchars($pesan) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL KAPASITAS -->

        <div class="col-md-4">

            <div class="card summary-card">

                <div class="card-body">

                    <small class="text-muted">

                        Total Kapasitas

                    </small>

                    <div class="stat-number">

                        <?= $totalKapasitas ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- SEDANG TERISI -->

        <div class="col-md-4">

            <div class="card summary-card">

                <div class="card-body">

                    <small class="text-muted">

                        Sedang Terisi

                    </small>

                    <div class="stat-number">

                        <?= $totalTerisi ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- TERSEDIA -->

        <div class="col-md-4">

            <div class="card summary-card">

                <div class="card-body">

                    <small class="text-muted">

                        Slot Tersedia

                    </small>

                    <div class="stat-number text-success">

                        <?= $totalTersedia ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         AREA
    ====================================================== -->

    <div class="row g-4">

        <?php foreach ($areas as $area): ?>

            <?php

            $status = statusArea(
                $area['kapasitas'],
                $area['terisi']
            );

            $persen = $area['kapasitas'] > 0

                ? min(
                    ($area['terisi'] / $area['kapasitas']) * 100,
                    100
                )

                : 0;

            ?>

            <div class="col-lg-6">

                <div class="card area-card">


                    <!-- =========================================
                         AREA HEADER
                    ========================================== -->

                    <div class="area-header">

                        <div class="d-flex justify-content-between align-items-center gap-3">

                            <div>

                                <h4 class="mb-1 fw-bold">

                                    <?= htmlspecialchars(
                                        $area['nama_area']
                                    ) ?>

                                </h4>

                                <small>

                                    Kapasitas
                                    <?= $area['kapasitas'] ?>
                                    kendaraan

                                </small>

                            </div>


                            <span
                                class="badge bg-<?= $status['class'] ?> p-2"
                            >

                                <i
                                    class="bi <?= $status['icon'] ?>"
                                ></i>

                                <?= $status['text'] ?>

                            </span>

                        </div>

                    </div>


                    <!-- =========================================
                         AREA BODY
                    ========================================== -->

                    <div class="card-body">


                        <!-- STATISTIK AREA -->

                        <div class="row text-center mb-4">

                            <div class="col-4">

                                <div class="fw-bold fs-4">

                                    <?= $area['kapasitas'] ?>

                                </div>

                                <small class="text-muted">

                                    Kapasitas

                                </small>

                            </div>


                            <div class="col-4">

                                <div class="fw-bold fs-4 text-danger">

                                    <?= $area['terisi'] ?>

                                </div>

                                <small class="text-muted">

                                    Terisi

                                </small>

                            </div>


                            <div class="col-4">

                                <div class="fw-bold fs-4 text-success">

                                    <?= $area['tersedia'] ?>

                                </div>

                                <small class="text-muted">

                                    Tersedia

                                </small>

                            </div>

                        </div>


                        <!-- PROGRESS -->

                        <div
                            class="progress mb-4"
                            style="height:10px;"
                        >

                            <div
                                class="progress-bar bg-<?= $status['class'] ?>"
                                style="width: <?= $persen ?>%"
                            ></div>

                        </div>


                        <!-- =====================================
                             SLOT
                        ====================================== -->

                        <?php

                        $jumlahSlot = min(
                            max($area['kapasitas'], 1),
                            40
                        );

                        ?>

                        <?php if ($area['kapasitas'] <= 40): ?>

                            <h6 class="fw-bold mb-3">

                                Status Slot

                            </h6>

                            <div class="slot-grid mb-4">

                                <?php for (
                                    $i = 1;
                                    $i <= $jumlahSlot;
                                    $i++
                                ): ?>

                                    <?php

                                    $terisiSlot =
                                        $i <= $area['terisi'];

                                    ?>

                                    <div
                                        class="slot <?= $terisiSlot
                                            ? 'slot-full'
                                            : 'slot-empty' ?>"
                                    >

                                        <?= $i ?>

                                    </div>

                                <?php endfor; ?>

                            </div>

                        <?php endif; ?>


                        <!-- =====================================
                             ULASAN
                        ====================================== -->

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <h6 class="fw-bold mb-0">

                                Ulasan

                            </h6>

                            <span class="stars">

                                <?php

                                $ratingBulat = (int) round(
                                    $area['rating']
                                );

                                for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ):

                                ?>

                                    <i
                                        class="bi <?= $i <= $ratingBulat
                                            ? 'bi-star-fill'
                                            : 'bi-star' ?>"
                                    ></i>

                                <?php endfor; ?>

                                <small class="text-muted ms-1">

                                    <?= number_format(
                                        $area['rating'],
                                        1
                                    ) ?>

                                </small>

                            </span>

                        </div>


                        <!-- ULASAN TERBARU -->

                        <?php if (
                            !empty(
                                $ulasanPerArea[$area['id_area']]
                            )
                        ): ?>

                            <?php foreach (
                                $ulasanPerArea[$area['id_area']]
                                as $ulasan
                            ): ?>

                                <div class="review">

                                    <div class="d-flex justify-content-between gap-2">

                                        <strong>

                                            <?= htmlspecialchars(
                                                $ulasan['nama_pengulas']
                                            ) ?>

                                        </strong>

                                        <span class="stars">

                                            <?= str_repeat(
                                                '★',
                                                (int) round(
                                                    $ulasan['rating']
                                                )
                                            ) ?>

                                        </span>

                                    </div>

                                    <div class="small text-muted mt-1">

                                        <?= htmlspecialchars(
                                            $ulasan['komentar']
                                        ) ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="text-muted small mb-3">

                                Belum ada ulasan.

                            </div>

                        <?php endif; ?>


                        <!-- TOMBOL ULASAN -->

                        <button
                            class="btn btn-primary w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#ulasanModal<?= $area['id_area'] ?>"
                        >

                            <i class="bi bi-chat-square-text"></i>

                            Beri Ulasan

                        </button>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 MODAL ULASAN
            ================================================== -->

            <div
                class="modal fade"
                id="ulasanModal<?= $area['id_area'] ?>"
                tabindex="-1"
                aria-hidden="true"
            >

                <div class="modal-dialog">

                    <div class="modal-content">

                        <form method="POST">

                            <div class="modal-header">

                                <h5 class="modal-title">

                                    Ulasan
                                    <?= htmlspecialchars(
                                        $area['nama_area']
                                    ) ?>

                                </h5>

                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                ></button>

                            </div>


                            <div class="modal-body">

                                <input
                                    type="hidden"
                                    name="id_area"
                                    value="<?= $area['id_area'] ?>"
                                >


                                <!-- NAMA JIKA BELUM LOGIN -->

                                <?php if (
                                    !isset($_SESSION['id_user'])
                                ): ?>

                                    <div class="mb-3">

                                        <label class="form-label">

                                            Nama

                                        </label>

                                        <input
                                            type="text"
                                            name="nama_pengulas"
                                            class="form-control"
                                            maxlength="100"
                                            required
                                        >

                                    </div>

                                <?php endif; ?>


                                <!-- RATING -->

                                <div class="mb-3">

                                    <label class="form-label">

                                        Rating

                                    </label>

                                    <select
                                        name="rating"
                                        class="form-select"
                                        required
                                    >

                                        <option value="5">

                                            ★★★★★ - Sangat Baik

                                        </option>

                                        <option value="4">

                                            ★★★★☆ - Baik

                                        </option>

                                        <option value="3">

                                            ★★★☆☆ - Cukup

                                        </option>

                                        <option value="2">

                                            ★★☆☆☆ - Kurang

                                        </option>

                                        <option value="1">

                                            ★☆☆☆☆ - Buruk

                                        </option>

                                    </select>

                                </div>


                                <!-- KOMENTAR -->

                                <div class="mb-3">

                                    <label class="form-label">

                                        Komentar

                                    </label>

                                    <textarea
                                        name="komentar"
                                        class="form-control"
                                        rows="4"
                                        maxlength="1000"
                                        required
                                    ></textarea>

                                </div>

                            </div>


                            <div class="modal-footer">

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    data-bs-dismiss="modal"
                                >

                                    Batal

                                </button>

                                <button
                                    type="submit"
                                    name="submit_ulasan"
                                    class="btn btn-primary"
                                >

                                    <i class="bi bi-send"></i>

                                    Kirim Ulasan

                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>