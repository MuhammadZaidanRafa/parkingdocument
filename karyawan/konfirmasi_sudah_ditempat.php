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
   CEK KONEKSI
   ========================================================= */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak tersedia.");
}

/* =========================================================
   CEK LOGIN
   ========================================================= */
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

/* =========================================================
   AMBIL ID BOOKING
   ========================================================= */
$id_booking = 0;

if (isset($_GET['id_booking'])) {
    $id_booking = (int) $_GET['id_booking'];
}

if (isset($_POST['id_booking'])) {
    $id_booking = (int) $_POST['id_booking'];
}

if ($id_booking <= 0) {
    $_SESSION['pesan_error'] = "Booking tidak ditemukan.";
    header("Location: riwayat.php");
    exit;
}

/* =========================================================
   SINKRONISASI AREA KARYAWAN
   ========================================================= */
function sinkronisasiAreaKaryawan(
    mysqli $conn,
    int $id_area_karyawan
): void {

    $sql = "
        UPDATE tb_area_parkir_karyawan a
        SET
            a.terisi = (
                SELECT COUNT(*)
                FROM tb_transaksi t
                WHERE t.id_area_karyawan = a.id_area_karyawan
                  AND t.status = 'masuk'
                  AND t.waktu_keluar IS NULL
            ),
            a.status = CASE

                WHEN a.terisi >= a.kapasitas
                    THEN 'penuh'

                ELSE 'tersedia'

            END

        WHERE a.id_area_karyawan = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            "Gagal sinkronisasi area karyawan: " .
            $conn->error
        );
    }

    $stmt->bind_param(
        "i",
        $id_area_karyawan
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new Exception(
            "Gagal memperbarui kapasitas area karyawan: " .
            $error
        );
    }

    $stmt->close();
}

/* =========================================================
   AMBIL DATA BOOKING KARYAWAN
   ========================================================= */
$sql_booking = "

    SELECT

        b.id_booking,
        b.id_kendaraan,
        b.id_area,
        b.id_area_karyawan,
        b.id_user,
        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        b.status,

        k.plat_nomor,
        k.jenis_kendaraan,
        k.merk,
        k.warna,
        k.pemilik,

        a.nama_area,
        a.kapasitas,
        a.terisi,
        a.rating,
        a.status AS status_area

    FROM tb_booking b

    INNER JOIN tb_kendaraan k
        ON k.id_kendaraan = b.id_kendaraan

    INNER JOIN tb_area_parkir_karyawan a
        ON a.id_area_karyawan = b.id_area_karyawan

    WHERE b.id_booking = ?
      AND b.id_user = ?

    LIMIT 1
";

$stmt_booking = $conn->prepare($sql_booking);

if (!$stmt_booking) {
    die(
        "Gagal mengambil data booking karyawan: " .
        $conn->error
    );
}

$stmt_booking->bind_param(
    "ii",
    $id_booking,
    $id_user
);

$stmt_booking->execute();

$result_booking = $stmt_booking->get_result();

$booking = $result_booking->fetch_assoc();

$stmt_booking->close();

/* =========================================================
   BOOKING TIDAK DITEMUKAN
   ========================================================= */
if (!$booking) {

    $_SESSION['pesan_error'] =
        "Data booking karyawan tidak ditemukan.";

    header("Location: riwayat.php");
    exit;
}

/* =========================================================
   PROSES KONFIRMASI
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $conn->begin_transaction();

    try {

        /* =================================================
           1. LOCK BOOKING
           ================================================= */
        $sql_lock_booking = "

            SELECT

                id_booking,
                id_kendaraan,
                id_area,
                id_area_karyawan,
                id_user,
                status,
                estimasi_jam

            FROM tb_booking

            WHERE id_booking = ?
              AND id_user = ?

            LIMIT 1

            FOR UPDATE
        ";

        $stmt_lock_booking =
            $conn->prepare($sql_lock_booking);

        if (!$stmt_lock_booking) {
            throw new Exception(
                "Gagal mengunci booking karyawan."
            );
        }

        $stmt_lock_booking->bind_param(
            "ii",
            $id_booking,
            $id_user
        );

        $stmt_lock_booking->execute();

        $result_lock_booking =
            $stmt_lock_booking->get_result();

        $booking_lock =
            $result_lock_booking->fetch_assoc();

        $stmt_lock_booking->close();

        if (!$booking_lock) {
            throw new Exception(
                "Booking karyawan tidak ditemukan."
            );
        }

        /* =================================================
           2. CEK STATUS BOOKING
           ================================================= */
        if ($booking_lock['status'] !== 'booking') {

            throw new Exception(
                "Booking ini sudah dikonfirmasi " .
                "atau sudah tidak dapat digunakan."
            );
        }

        $id_kendaraan =
            (int) $booking_lock['id_kendaraan'];

        $id_area_karyawan =
            (int) $booking_lock['id_area_karyawan'];

        $booking_user =
            (int) $booking_lock['id_user'];

        if ($id_area_karyawan <= 0) {
            throw new Exception(
                "Area parkir karyawan belum ditentukan."
            );
        }

        /* =================================================
           3. LOCK KENDARAAN
           ================================================= */
        $sql_vehicle = "

            SELECT

                id_kendaraan,
                plat_nomor,
                jenis_kendaraan,
                merk,
                warna,
                pemilik

            FROM tb_kendaraan

            WHERE id_kendaraan = ?

            LIMIT 1

            FOR UPDATE
        ";

        $stmt_vehicle =
            $conn->prepare($sql_vehicle);

        if (!$stmt_vehicle) {
            throw new Exception(
                "Gagal mengambil kendaraan."
            );
        }

        $stmt_vehicle->bind_param(
            "i",
            $id_kendaraan
        );

        $stmt_vehicle->execute();

        $result_vehicle =
            $stmt_vehicle->get_result();

        $kendaraan =
            $result_vehicle->fetch_assoc();

        $stmt_vehicle->close();

        if (!$kendaraan) {
            throw new Exception(
                "Data kendaraan tidak ditemukan."
            );
        }

        /* =================================================
           4. LOCK AREA KARYAWAN
           ================================================= */
        $sql_area = "

            SELECT

                id_area_karyawan,
                nama_area,
                kapasitas,
                terisi,
                rating,
                status

            FROM tb_area_parkir_karyawan

            WHERE id_area_karyawan = ?

            LIMIT 1

            FOR UPDATE
        ";

        $stmt_area =
            $conn->prepare($sql_area);

        if (!$stmt_area) {
            throw new Exception(
                "Gagal mengambil area parkir karyawan."
            );
        }

        $stmt_area->bind_param(
            "i",
            $id_area_karyawan
        );

        $stmt_area->execute();

        $result_area =
            $stmt_area->get_result();

        $area =
            $result_area->fetch_assoc();

        $stmt_area->close();

        if (!$area) {
            throw new Exception(
                "Area parkir karyawan tidak ditemukan."
            );
        }

        /* =================================================
           5. CEK STATUS AREA
           ================================================= */
        if ($area['status'] === 'nonaktif') {

            throw new Exception(
                "Area " .
                $area['nama_area'] .
                " sedang nonaktif."
            );
        }

        $kapasitas =
            (int) $area['kapasitas'];

        /* =================================================
           6. HITUNG PARKIR AKTIF KARYAWAN
           ================================================= */
        $sql_count = "

            SELECT COUNT(*) AS total_aktif

            FROM tb_transaksi

            WHERE id_area_karyawan = ?

              AND status = 'masuk'

              AND waktu_keluar IS NULL
        ";

        $stmt_count =
            $conn->prepare($sql_count);

        if (!$stmt_count) {
            throw new Exception(
                "Gagal menghitung kendaraan karyawan."
            );
        }

        $stmt_count->bind_param(
            "i",
            $id_area_karyawan
        );

        $stmt_count->execute();

        $result_count =
            $stmt_count->get_result();

        $data_count =
            $result_count->fetch_assoc();

        $stmt_count->close();

        $total_aktif =
            (int) ($data_count['total_aktif'] ?? 0);

        /* =================================================
           7. CEK KAPASITAS
           ================================================= */
        if ($kapasitas <= 0) {

            throw new Exception(
                "Kapasitas area " .
                $area['nama_area'] .
                " belum diatur."
            );
        }

        if ($total_aktif >= $kapasitas) {

            throw new Exception(
                "Area karyawan " .
                $area['nama_area'] .
                " sudah penuh (" .
                $total_aktif .
                "/" .
                $kapasitas .
                ")."
            );
        }

        /* =================================================
           8. CEK KENDARAAN SUDAH PARKIR
           ================================================= */
        $sql_existing = "

            SELECT id_parkir

            FROM tb_transaksi

            WHERE id_kendaraan = ?

              AND status = 'masuk'

              AND waktu_keluar IS NULL

            LIMIT 1
        ";

        $stmt_existing =
            $conn->prepare($sql_existing);

        if (!$stmt_existing) {
            throw new Exception(
                "Gagal memeriksa kendaraan aktif."
            );
        }

        $stmt_existing->bind_param(
            "i",
            $id_kendaraan
        );

        $stmt_existing->execute();

        $result_existing =
            $stmt_existing->get_result();

        $existing =
            $result_existing->fetch_assoc();

        $stmt_existing->close();

        if ($existing) {

            throw new Exception(
                "Kendaraan dengan plat " .
                $kendaraan['plat_nomor'] .
                " masih tercatat sedang parkir."
            );
        }

        /* =================================================
           9. NORMALISASI JENIS KENDARAAN
           ================================================= */
        $jenis = strtolower(
            trim(
                $kendaraan['jenis_kendaraan']
            )
        );

        if (
            $jenis === 'motor' ||
            $jenis === 'sepeda motor' ||
            $jenis === 'roda 2' ||
            $jenis === 'roda dua'
        ) {

            $jenis_tarif = 'motor';

        } elseif (
            $jenis === 'mobil' ||
            $jenis === 'roda 4' ||
            $jenis === 'roda empat'
        ) {

            $jenis_tarif = 'mobil';

        } elseif (
            $jenis === 'truk' ||
            $jenis === 'truck'
        ) {

            $jenis_tarif = 'truk';

        } elseif (
            $jenis === 'bus'
        ) {

            $jenis_tarif = 'bus';

        } else {

            $jenis_tarif = $jenis;
        }

        /* =================================================
           10. AMBIL TARIF
           ================================================= */
        $sql_tarif = "

            SELECT

                id_tarif,
                jenis_kendaraan,
                tarif_per_jam

            FROM tb_tarif

            WHERE LOWER(
                TRIM(jenis_kendaraan)
            ) = ?

            LIMIT 1
        ";

        $stmt_tarif =
            $conn->prepare($sql_tarif);

        if (!$stmt_tarif) {
            throw new Exception(
                "Gagal mengambil tarif."
            );
        }

        $stmt_tarif->bind_param(
            "s",
            $jenis_tarif
        );

        $stmt_tarif->execute();

        $result_tarif =
            $stmt_tarif->get_result();

        $tarif =
            $result_tarif->fetch_assoc();

        $stmt_tarif->close();

        /* =================================================
           FALLBACK JENIS ASLI
           ================================================= */
        if (!$tarif) {

            $jenis_asli = strtolower(
                trim(
                    $kendaraan['jenis_kendaraan']
                )
            );

            $stmt_tarif2 =
                $conn->prepare($sql_tarif);

            if (!$stmt_tarif2) {
                throw new Exception(
                    "Gagal mengambil tarif kendaraan."
                );
            }

            $stmt_tarif2->bind_param(
                "s",
                $jenis_asli
            );

            $stmt_tarif2->execute();

            $result_tarif2 =
                $stmt_tarif2->get_result();

            $tarif =
                $result_tarif2->fetch_assoc();

            $stmt_tarif2->close();
        }

        if (!$tarif) {

            throw new Exception(
                "Tarif untuk jenis kendaraan '" .
                $kendaraan['jenis_kendaraan'] .
                "' belum tersedia."
            );
        }

        $id_tarif =
            (int) $tarif['id_tarif'];

        /* =================================================
           11. BUAT TRANSAKSI PARKIR KARYAWAN
           ================================================= */
        $sql_insert = "

            INSERT INTO tb_transaksi
            (
                id_kendaraan,
                waktu_masuk,
                id_tarif,
                status,
                id_user,
                id_area,
                id_area_karyawan,
                id_booking
            )

            VALUES
            (
                ?,
                NOW(),
                ?,
                'masuk',
                ?,
                NULL,
                ?,
                ?
            )
        ";

        $stmt_insert =
            $conn->prepare($sql_insert);

        if (!$stmt_insert) {
            throw new Exception(
                "Gagal membuat transaksi parkir karyawan: " .
                $conn->error
            );
        }

        $stmt_insert->bind_param(
            "iiiii",
            $id_kendaraan,
            $id_tarif,
            $booking_user,
            $id_area_karyawan,
            $id_booking
        );

        if (!$stmt_insert->execute()) {

            $error_insert =
                $stmt_insert->error;

            $stmt_insert->close();

            throw new Exception(
                "Gagal membuat transaksi parkir karyawan: " .
                $error_insert
            );
        }

        $stmt_insert->close();

        /* =================================================
           12. UPDATE BOOKING
           ================================================= */
        $tanggal_sekarang =
            date('Y-m-d');

        $jam_sekarang =
            date('H:i:s');

        $sql_update_booking = "

            UPDATE tb_booking

            SET

                status = 'aktif',
                tanggal = ?,
                jam_masuk = ?,
                id_area = NULL,
                id_area_karyawan = ?

            WHERE id_booking = ?
              AND id_user = ?
              AND status = 'booking'
        ";

        $stmt_update_booking =
            $conn->prepare(
                $sql_update_booking
            );

        if (!$stmt_update_booking) {
            throw new Exception(
                "Gagal memperbarui booking karyawan."
            );
        }

        $stmt_update_booking->bind_param(
            "ssiii",
            $tanggal_sekarang,
            $jam_sekarang,
            $id_area_karyawan,
            $id_booking,
            $id_user
        );

        if (!$stmt_update_booking->execute()) {

            $error_update =
                $stmt_update_booking->error;

            $stmt_update_booking->close();

            throw new Exception(
                "Gagal mengaktifkan booking karyawan: " .
                $error_update
            );
        }

        $affected =
            $stmt_update_booking->affected_rows;

        $stmt_update_booking->close();

        if ($affected <= 0) {

            throw new Exception(
                "Booking karyawan sudah diproses."
            );
        }

        /* =================================================
           13. SINKRONISASI AREA KARYAWAN
           ================================================= */
        sinkronisasiAreaKaryawan(
            $conn,
            $id_area_karyawan
        );

        /* =================================================
           14. COMMIT
           ================================================= */
        $conn->commit();

        /* =================================================
           15. REDIRECT RIWAYAT
           ================================================= */
        $_SESSION['pesan_sukses'] =
            "Kedatangan karyawan berhasil dikonfirmasi. " .
            "Kendaraan " .
            $kendaraan['plat_nomor'] .
            " sekarang tercatat sedang parkir di area " .
            $area['nama_area'] .
            ".";

        header("Location: riwayat.php");
        exit;

    } catch (Throwable $e) {

        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
        }

        $_SESSION['pesan_error'] =
            $e->getMessage();

        header("Location: riwayat.php");
        exit;
    }
}

/* =========================================================
   DATA TAMPILAN
   ========================================================= */
$plat_nomor = htmlspecialchars(
    $booking['plat_nomor'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$jenis_kendaraan = htmlspecialchars(
    $booking['jenis_kendaraan'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$merk = htmlspecialchars(
    $booking['merk'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$warna = htmlspecialchars(
    $booking['warna'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$nama_area = htmlspecialchars(
    $booking['nama_area'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$estimasi_jam =
    (int) ($booking['estimasi_jam'] ?? 1);

$tanggal = htmlspecialchars(
    $booking['tanggal'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$jam_masuk = htmlspecialchars(
    $booking['jam_masuk'] ?? '-',
    ENT_QUOTES,
    'UTF-8'
);

$kapasitas =
    (int) ($booking['kapasitas'] ?? 0);

$terisi =
    (int) ($booking['terisi'] ?? 0);

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
        Konfirmasi Kedatangan Karyawan - E-Parkir
    </title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #eef2ff,
                    #f8fafc
                );

            color: #1e293b;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;
        }

        .container {
            width: 100%;
            max-width: 700px;
        }

        .card {
            background: #fff;

            border-radius: 22px;

            padding: 32px;

            box-shadow:
                0 20px 50px
                rgba(15, 23, 42, .12);
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .icon {
            width: 70px;
            height: 70px;

            margin: 0 auto 15px;

            border-radius: 50%;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 34px;

            background: #dbeafe;
            color: #2563eb;
        }

        h1 {
            font-size: 26px;
            margin-bottom: 8px;
            color: #0f172a;
        }

        .subtitle {
            color: #64748b;
            line-height: 1.6;
        }

        .badge {
            display: inline-block;

            margin-top: 12px;

            padding: 7px 14px;

            border-radius: 999px;

            background: #dbeafe;

            color: #1d4ed8;

            font-size: 13px;

            font-weight: 700;
        }

        .info {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px;

            margin: 25px 0;
        }

        .item {
            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 14px;

            padding: 16px;
        }

        .label {
            display: block;

            font-size: 12px;

            color: #64748b;

            margin-bottom: 6px;

            text-transform: uppercase;

            letter-spacing: .5px;
        }

        .value {
            font-weight: 700;
            color: #0f172a;

            word-break: break-word;
        }

        .capacity {
            margin-bottom: 20px;

            padding: 15px;

            border-radius: 14px;

            background: #eff6ff;

            border: 1px solid #bfdbfe;

            color: #1e40af;

            line-height: 1.6;
        }

        .warning {
            background: #fff7ed;

            border:
                1px solid #fed7aa;

            color: #9a3412;

            padding: 15px;

            border-radius: 13px;

            line-height: 1.6;

            margin-bottom: 20px;
        }

        .actions {
            display: flex;
            gap: 12px;
        }

        button,
        .back {
            width: 100%;

            border: 0;

            border-radius: 12px;

            padding: 14px 18px;

            font-size: 15px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            text-align: center;

            transition: .2s;
        }

        .confirm {
            background: #2563eb;
            color: #fff;
        }

        .confirm:hover {
            background: #1d4ed8;

            transform:
                translateY(-1px);
        }

        .back {
            background: #e2e8f0;
            color: #334155;
        }

        .back:hover {
            background: #cbd5e1;
        }

        @media (max-width: 600px) {

            body {
                padding: 15px;
            }

            .card {
                padding: 22px;
            }

            h1 {
                font-size: 22px;
            }

            .info {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <div class="icon">
                ✓
            </div>

            <h1>
                Konfirmasi Kedatangan
            </h1>

            <p class="subtitle">
                Konfirmasi kedatangan kendaraan
                untuk <strong>area parkir karyawan</strong>.
            </p>

            <span class="badge">
                PARKIR KARYAWAN
            </span>

        </div>

        <div class="info">

            <div class="item">
                <span class="label">
                    Plat Nomor
                </span>

                <span class="value">
                    <?= $plat_nomor ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Kendaraan
                </span>

                <span class="value">
                    <?= $jenis_kendaraan ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Merk
                </span>

                <span class="value">
                    <?= $merk ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Warna
                </span>

                <span class="value">
                    <?= $warna ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Area Karyawan
                </span>

                <span class="value">
                    <?= $nama_area ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Estimasi Parkir
                </span>

                <span class="value">
                    <?= $estimasi_jam ?> jam
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Tanggal Booking
                </span>

                <span class="value">
                    <?= $tanggal ?>
                </span>
            </div>

            <div class="item">
                <span class="label">
                    Jam Booking
                </span>

                <span class="value">
                    <?= $jam_masuk ?>
                </span>
            </div>

        </div>

        <div class="capacity">

            <strong>Kapasitas Area</strong><br>

            Terisi:
            <strong><?= $terisi ?></strong>
            /
            <strong><?= $kapasitas ?></strong>
            kendaraan

        </div>

        <div class="warning">

            <strong>Perhatian:</strong><br>

            Setelah menekan
            <strong>Konfirmasi Kedatangan</strong>,
            kendaraan akan langsung dicatat sebagai
            <strong>sedang parkir di area karyawan</strong>.

            Waktu masuk parkir dimulai saat
            konfirmasi dilakukan.

        </div>

        <form
            method="POST"
            onsubmit="
                return confirm(
                    'Apakah Anda sudah berada di lokasi parkir karyawan dan ingin mengonfirmasi kedatangan?'
                );
            "
        >

            <input
                type="hidden"
                name="id_booking"
                value="<?= (int) $booking['id_booking'] ?>"
            >

            <div class="actions">

                <a
                    href="riwayat.php"
                    class="back"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="confirm"
                >
                    ✓ Konfirmasi Kedatangan
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>