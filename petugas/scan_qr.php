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

$id_user = (int) $_SESSION['id_user'];
$nama    = $_SESSION['nama_lengkap'] ?? 'Petugas';
$role    = $_SESSION['role'] ?? '';

/* =========================================================
   CEK ROLE
   ========================================================= */

if (!in_array($role, ['petugas', 'karyawan', 'admin'])) {
    die("Akses ditolak.");
}

$pesan = '';
$tipe_pesan = '';

/* =========================================================
   FUNGSI BACA QR
   Format utama:

   PARKIR|MASUK|123          (123 = id_booking)
   PARKIR|KELUAR|123         (123 = id_booking)
   PARKIR|TRANSAKSI|55       (55  = id_parkir, untuk keluar)

   Input manual:
   123

   Tetap mendukung format lama:
   BOOKING:123
   ID_BOOKING:123
   URL?id_booking=123
   ========================================================= */

function bacaQR($qr_data)
{
    $qr_data = trim($qr_data);

    $hasil = [
        'action'     => null,
        'id_booking' => null,
        'id_parkir'  => null
    ];

    if ($qr_data === '') {
        return $hasil;
    }

    /*
     * FORMAT TRANSAKSI (keluar berdasarkan id_parkir):
     * PARKIR|TRANSAKSI|55
     *
     * Dipakai untuk parkir langsung atau booking yang
     * statusnya belum 'aktif'.
     */
    if (preg_match('/^PARKIR\|TRANSAKSI\|(\d+)$/i', $qr_data, $match)) {
        $hasil['action']    = 'KELUAR';
        $hasil['id_parkir'] = (int) $match[1];
        return $hasil;
    }

    /*
     * FORMAT RESMI:
     * PARKIR|MASUK|123
     * PARKIR|KELUAR|123
     */
    if (preg_match('/^PARKIR\|([A-Z]+)\|(\d+)$/i', $qr_data, $match)) {

        $action = strtoupper(trim($match[1]));
        $id_booking = (int) $match[2];

        if (in_array($action, ['MASUK', 'KELUAR'])) {
            $hasil['action'] = $action;
            $hasil['id_booking'] = $id_booking;
            return $hasil;
        }
    }

    /*
     * FORMAT:
     * BOOKING:123
     * ID_BOOKING:123
     * ID_BOOKING=123
     */
    if (
        preg_match(
            '/^(?:BOOKING|ID_BOOKING)[=:\/\s-]*(\d+)$/i',
            $qr_data,
            $match
        )
    ) {
        $hasil['id_booking'] = (int) $match[1];
        return $hasil;
    }

    /*
     * FORMAT URL:
     * ?id_booking=123
     */
    if (
        preg_match(
            '/(?:[?&]|^)(?:id_booking|booking)[=](\d+)/i',
            $qr_data,
            $match
        )
    ) {
        $hasil['id_booking'] = (int) $match[1];
        return $hasil;
    }

    /*
     * FORMAT URL UMUM YANG MENGANDUNG:
     * id_booking=123
     */
    if (
        preg_match(
            '/(?:id_booking|booking)[=:\s\/-]*(\d+)/i',
            $qr_data,
            $match
        )
    ) {
        $hasil['id_booking'] = (int) $match[1];
        return $hasil;
    }

    /*
     * FORMAT PALING SEDERHANA:
     * 123
     *
     * Jika manual hanya angka,
     * action akan ditentukan berdasarkan status booking.
     */
    if (preg_match('/^\d+$/', $qr_data)) {
        $hasil['id_booking'] = (int) $qr_data;
        return $hasil;
    }

    return $hasil;
}

/* =========================================================
   PROSES SCAN QR
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $qr_data = trim($_POST['qr_data'] ?? '');

    if ($qr_data === '') {

        $pesan = "QR Code tidak terbaca.";
        $tipe_pesan = "error";

    } else {

        /*
         * BACA DATA QR
         */
        $qr = bacaQR($qr_data);

        $id_booking   = $qr['id_booking'];
        $qr_action    = $qr['action'];
        $id_parkir_qr = $qr['id_parkir'];

        /* =====================================================
           QR TRANSAKSI → KELUAR (berdasarkan id_parkir)
           ===================================================== */

        if ($id_parkir_qr) {

            $stmt = $conn->prepare("
                SELECT id_parkir
                FROM tb_transaksi
                WHERE id_parkir = ?
                AND status = 'masuk'
                LIMIT 1
            ");

            if (!$stmt) {
                die("Prepare error: " . $conn->error);
            }

            $stmt->bind_param("i", $id_parkir_qr);
            $stmt->execute();

            $trx = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if ($trx) {

                header(
                    "Location: proses_keluar.php?id_parkir="
                    . (int) $trx['id_parkir']
                );

                exit;
            }

            $pesan =
                "Transaksi #{$id_parkir_qr} tidak ditemukan atau kendaraan sudah keluar.";

            $tipe_pesan = "error";

        } elseif (!$id_booking) {

            /*
             * Teks yang terbaca ikut ditampilkan (dipotong 60 karakter)
             * supaya mudah dicek kalau QR ditolak.
             */
            $pesan =
                "QR Code tidak valid. Pastikan QR berasal dari sistem Parking. "
                . "(Terbaca: " . substr($qr_data, 0, 60) . ")";

            $tipe_pesan = "error";

        } else {

            /* =====================================================
               AMBIL DATA BOOKING
               ===================================================== */

            $stmt = $conn->prepare("
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

                    k.plat_nomor,
                    k.jenis_kendaraan,
                    k.merk,
                    k.warna,
                    k.pemilik,

                    u.nama_lengkap,

                    a.nama_area

                FROM tb_booking b

                INNER JOIN tb_kendaraan k
                    ON b.id_kendaraan = k.id_kendaraan

                INNER JOIN tb_user u
                    ON b.id_user = u.id_user

                LEFT JOIN tb_area_parkir a
                    ON b.id_area = a.id_area

                WHERE b.id_booking = ?

                LIMIT 1
            ");

            if (!$stmt) {
                die("Prepare error: " . $conn->error);
            }

            $stmt->bind_param("i", $id_booking);
            $stmt->execute();

            $result = $stmt->get_result();
            $booking = $result->fetch_assoc();

            $stmt->close();

            /* =====================================================
               BOOKING TIDAK DITEMUKAN
               ===================================================== */

            if (!$booking) {

                $pesan = "Booking dengan ID #{$id_booking} tidak ditemukan.";
                $tipe_pesan = "error";

            } else {

                $status_booking = strtolower(trim($booking['status'] ?? ''));

                /* =================================================
                   CEK TRANSAKSI MASIH AKTIF
                   ================================================= */

                $stmt = $conn->prepare("
                    SELECT *
                    FROM tb_transaksi
                    WHERE id_booking = ?
                    AND status = 'masuk'
                    ORDER BY id_parkir DESC
                    LIMIT 1
                ");

                if (!$stmt) {
                    die("Prepare error: " . $conn->error);
                }

                $stmt->bind_param("i", $id_booking);
                $stmt->execute();

                $transaksi = $stmt->get_result()->fetch_assoc();

                $stmt->close();

                /* =================================================
                   TENTUKAN ACTION OTOMATIS
                   JIKA QR MANUAL HANYA ANGKA
                   ================================================= */

                if ($qr_action === null) {

                    if ($status_booking === 'booking' && !$transaksi) {

                        $qr_action = 'MASUK';

                    } elseif (
                        $status_booking === 'aktif'
                        && $transaksi
                    ) {

                        $qr_action = 'KELUAR';

                    } elseif ($transaksi) {

                        $qr_action = 'KELUAR';

                    }
                }

                /* =================================================
                   STATUS BOOKING
                   ================================================= */

                if ($status_booking === 'batal') {

                    $pesan =
                        "Booking #{$id_booking} sudah dibatalkan.";

                    $tipe_pesan = "error";

                } elseif ($status_booking === 'selesai') {

                    $pesan =
                        "Booking #{$id_booking} sudah selesai. QR tidak dapat digunakan lagi.";

                    $tipe_pesan = "error";

                }

                /* =================================================
                   MASUK
                   ================================================= */

                elseif ($status_booking === 'booking') {

                    /*
                     * Kalau sudah ada transaksi aktif,
                     * jangan membuat transaksi kedua.
                     */
                    if ($transaksi) {

                        $pesan =
                            "Kendaraan dari booking #{$id_booking} sudah masuk. Gunakan QR KELUAR.";

                        $tipe_pesan = "error";

                    }

                    /*
                     * QR KELUAR tidak boleh dipakai saat booking.
                     */
                    elseif ($qr_action === 'KELUAR') {

                        $pesan =
                            "QR KELUAR belum dapat digunakan. Kendaraan masih berstatus booking dan belum masuk.";

                        $tipe_pesan = "error";

                    }

                    /*
                     * QR bukan MASUK.
                     */
                    elseif ($qr_action !== 'MASUK') {

                        $pesan =
                            "QR tidak dikenali sebagai QR MASUK.";

                        $tipe_pesan = "error";

                    }

                    else {

                        /* =========================================
                           CARI TARIF
                           ========================================= */

                        $stmt = $conn->prepare("
                            SELECT id_tarif
                            FROM tb_tarif
                            WHERE jenis_kendaraan = ?
                            LIMIT 1
                        ");

                        if (!$stmt) {
                            die("Prepare error: " . $conn->error);
                        }

                        $stmt->bind_param(
                            "s",
                            $booking['jenis_kendaraan']
                        );

                        $stmt->execute();

                        $tarif_result = $stmt->get_result();
                        $tarif = $tarif_result->fetch_assoc();

                        $stmt->close();

                        if (!$tarif) {

                            $pesan =
                                "Tarif untuk kendaraan "
                                . htmlspecialchars(
                                    $booking['jenis_kendaraan']
                                )
                                . " belum tersedia.";

                            $tipe_pesan = "error";

                        } else {

                            $id_tarif = (int) $tarif['id_tarif'];

                            $id_kendaraan =
                                (int) $booking['id_kendaraan'];

                            /*
                             * Area utama
                             */
                            $id_area = !empty($booking['id_area'])
                                ? (int) $booking['id_area']
                                : null;

                            /*
                             * Area karyawan
                             */
                            $id_area_karyawan =
                                !empty($booking['id_area_karyawan'])
                                ? (int) $booking['id_area_karyawan']
                                : null;

                            $waktu_masuk =
                                date('Y-m-d H:i:s');

                            /* =====================================
                               INSERT TRANSAKSI MASUK

                               TOTAL PLACEHOLDER = 7
                               TYPE STRING = isiiiii
                               ===================================== */

                            $stmt = $conn->prepare("
                                INSERT INTO tb_transaksi
                                (
                                    id_kendaraan,
                                    waktu_masuk,
                                    waktu_keluar,
                                    id_tarif,
                                    durasi_jam,
                                    biaya_total,
                                    metode_pembayaran,
                                    status,
                                    id_user,
                                    id_area,
                                    id_area_karyawan,
                                    id_booking
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    NULL,
                                    ?,
                                    0,
                                    0,
                                    NULL,
                                    'masuk',
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )
                            ");

                            if (!$stmt) {
                                die(
                                    "Prepare transaksi error: "
                                    . $conn->error
                                );
                            }

                            /*
                             * 7 variabel:
                             *
                             * id_kendaraan      = i
                             * waktu_masuk       = s
                             * id_tarif          = i
                             * id_user           = i
                             * id_area           = i
                             * id_area_karyawan  = i
                             * id_booking        = i
                             *
                             * TOTAL: isiiiii
                             */

                            $stmt->bind_param(
                                "isiiiii",
                                $id_kendaraan,
                                $waktu_masuk,
                                $id_tarif,
                                $id_user,
                                $id_area,
                                $id_area_karyawan,
                                $id_booking
                            );

                            if ($stmt->execute()) {

                                $stmt->close();

                                /* =================================
                                   UPDATE BOOKING MENJADI AKTIF
                                   ================================= */

                                $stmt2 = $conn->prepare("
                                    UPDATE tb_booking
                                    SET status = 'aktif'
                                    WHERE id_booking = ?
                                ");

                                if (!$stmt2) {
                                    die(
                                        "Prepare update error: "
                                        . $conn->error
                                    );
                                }

                                $stmt2->bind_param(
                                    "i",
                                    $id_booking
                                );

                                $stmt2->execute();
                                $stmt2->close();

                                /*
                                 * BOOKING → MASUK → AKTIF
                                 *
                                 * QR MASUK otomatis tidak bisa
                                 * digunakan lagi.
                                 */

                                $pesan =
                                    "Kendaraan berhasil masuk. "
                                    . "Booking #{$id_booking} sekarang aktif. "
                                    . "QR berikutnya digunakan untuk keluar.";

                                $tipe_pesan = "success";

                            } else {

                                $pesan =
                                    "Gagal membuat transaksi masuk: "
                                    . $stmt->error;

                                $tipe_pesan = "error";

                                $stmt->close();
                            }
                        }
                    }
                }

                /* =================================================
                   AKTIF → KELUAR
                   ================================================= */

                elseif ($status_booking === 'aktif') {

                    /*
                     * Harus ada transaksi aktif.
                     */
                    if (!$transaksi) {

                        $pesan =
                            "Booking #{$id_booking} berstatus aktif, tetapi transaksi parkir aktif tidak ditemukan.";

                        $tipe_pesan = "error";

                    }

                    /*
                     * QR MASUK tidak boleh dipakai lagi.
                     */
                    elseif ($qr_action === 'MASUK') {

                        $pesan =
                            "Kendaraan sudah masuk. QR MASUK tidak dapat digunakan lagi. Gunakan QR KELUAR.";

                        $tipe_pesan = "error";

                    }

                    /*
                     * QR tidak dikenal.
                     */
                    elseif ($qr_action !== 'KELUAR') {

                        $pesan =
                            "QR tidak dikenali sebagai QR KELUAR.";

                        $tipe_pesan = "error";

                    }

                    else {

                        /*
                         * Ambil ID transaksi
                         */
                        $id_parkir =
                            (int) $transaksi['id_parkir'];

                        /*
                         * Jangan langsung mengubah status.
                         *
                         * Arahkan ke halaman pembayaran.
                         */
                        header(
                            "Location: proses_keluar.php?id_parkir="
                            . $id_parkir
                        );

                        exit;
                    }
                }

                /* =================================================
                   STATUS LAIN
                   ================================================= */

                else {

                    $pesan =
                        "Status booking tidak dapat diproses.";

                    $tipe_pesan = "error";
                }
            }
        }
    }
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

    <title>Scan QR - Parking</title>

    <!-- QR SCANNER -->
    <script src="https://unpkg.com/html5-qrcode"></script>

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
            max-width: 520px;
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
            margin-bottom: 22px;
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
            font-size: 25px;
            margin-bottom: 7px;
        }

        .subtitle {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }

        #reader {
            width: 100%;
            overflow: hidden;
            border-radius: 18px;
            background: #000;
            margin-top: 20px;
        }

        #reader video {
            border-radius: 18px !important;
        }

        .manual {
            margin-top: 20px;
        }

        .manual-title {
            font-size: 13px;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .input-row {
            display: flex;
            gap: 8px;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.25);
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #f59e0b;
        }

        button {
            border: none;
            padding: 13px 18px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s;
        }

        .btn-scan {
            width: 100%;
            margin-top: 14px;
            background: #f59e0b;
            color: #111827;
        }

        .btn-scan:hover {
            background: #fbbf24;
            transform: translateY(-1px);
        }

        .btn-submit {
            background: #f59e0b;
            color: #111827;
            white-space: nowrap;
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            border-radius: 12px;
            color: #cbd5e1;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .alert {
            padding: 14px;
            border-radius: 13px;
            margin-bottom: 18px;
            font-size: 14px;
            line-height: 1.5;
        }

        .success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }

        .error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .info {
            margin-top: 18px;
            padding: 14px;
            border-radius: 13px;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #bfdbfe;
            font-size: 13px;
            line-height: 1.6;
        }

        .info strong {
            color: #fff;
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
                font-size: 22px;
            }

            .input-row {
                flex-direction: column;
            }

            .btn-submit {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <div class="icon">
                📷
            </div>

            <h1>
                Scan QR Parking
            </h1>

            <div class="subtitle">

                Scan QR booking untuk proses
                kendaraan masuk atau keluar.

            </div>

        </div>

        <?php if ($pesan !== ''): ?>

            <div class="alert <?= htmlspecialchars($tipe_pesan) ?>">

                <?= htmlspecialchars($pesan) ?>

            </div>

        <?php endif; ?>


        <!-- QR READER -->

        <div id="reader"></div>


        <button
            type="button"
            class="btn-scan"
            onclick="mulaiScanner()"
        >

            📷 Mulai Scan Kamera

        </button>


        <!-- FORM MANUAL -->

        <div class="manual">

            <div class="manual-title">

                Atau masukkan ID Booking secara manual

            </div>

            <form
                method="POST"
                id="manualForm"
            >

                <div class="input-row">

                    <input
                        type="text"
                        name="qr_data"
                        id="qr_data"
                        placeholder="Contoh: 123"
                        autocomplete="off"
                        required
                    >

                    <button
                        type="submit"
                        class="btn-submit"
                    >

                        Proses

                    </button>

                </div>

            </form>

        </div>


        <!-- INFO ALUR -->

        <div class="info">

            <strong>Alur QR:</strong>
            <br><br>

            🟢 <strong>Booking</strong>
            → QR MASUK
            → kendaraan masuk
            <br>

            🔵 <strong>Aktif / Masuk</strong>
            → QR KELUAR
            → pembayaran
            <br>

            ⚫ <strong>Selesai</strong>
            → QR tidak dapat digunakan lagi.

            <br><br>

            Saat kendaraan keluar, petugas akan diarahkan
            ke <strong>proses_keluar.php</strong>
            untuk menyelesaikan pembayaran.

        </div>


        <a
            href="javascript:history.back()"
            class="btn-back"
        >

            ← Kembali

        </a>


        <div class="footer">

            Parking Management System

        </div>

    </div>

</div>


<script>

let scanner = null;

let sedangScan = false;

let sudahTerdeteksi = false;


/* =========================================================
   MULAI SCANNER
   ========================================================= */

function mulaiScanner() {

    if (sedangScan) {
        return;
    }

    sedangScan = true;

    sudahTerdeteksi = false;

    if (scanner === null) {

        scanner = new Html5Qrcode("reader");
    }

    const config = {

        fps: 10,

        qrbox: function(width, height) {

            const size = Math.min(
                width * 0.75,
                height * 0.75
            );

            return {
                width: size,
                height: size
            };
        },

        aspectRatio: 1.0
    };


    scanner.start(

        {
            facingMode: "environment"
        },

        config,

        function(decodedText) {

            /*
             * Cegah scanner membaca QR
             * berkali-kali sebelum redirect.
             */

            if (sudahTerdeteksi) {
                return;
            }

            sudahTerdeteksi = true;


            /*
             * Masukkan hasil QR ke input.
             */

            document.getElementById(
                "qr_data"
            ).value = decodedText;


            /*
             * Hentikan kamera terlebih dahulu.
             */

            scanner.stop().then(function() {

                sedangScan = false;

                /*
                 * Submit otomatis.
                 */

                document.getElementById(
                    "manualForm"
                ).submit();

            }).catch(function() {

                sedangScan = false;

                /*
                 * Tetap submit jika stop
                 * kamera gagal.
                 */

                document.getElementById(
                    "manualForm"
                ).submit();

            });

        },

        function(errorMessage) {

            /*
             * Error scan kecil tidak ditampilkan.
             */

        }

    ).catch(function(error) {

        sedangScan = false;

        sudahTerdeteksi = false;

        alert(
            "Kamera tidak dapat digunakan.\n\n" +
            "Pastikan browser memiliki izin kamera " +
            "dan halaman dibuka melalui HTTPS atau localhost."
        );

    });

}

</script>

</body>

</html>