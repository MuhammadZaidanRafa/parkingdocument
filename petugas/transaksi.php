<?php
/* =========================================================
   SESSION
   ========================================================= */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../db.php';

/* =========================================================
   CEK KONEKSI
   ========================================================= */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak ditemukan.");
}

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
if ($role !== 'petugas') {
    header("Location: ../dashboard.php");
    exit;
}

/* =========================================================
   FUNGSI SINKRONISASI AREA REGULER
   ========================================================= */
function sinkronisasiAreaReguler($conn, $id_area = null)
{
    if ($id_area !== null) {
        $id_area = (int) $id_area;
        $stmt = $conn->prepare("
            UPDATE tb_area_parkir a
            SET a.terisi = (
                SELECT COUNT(*)
                FROM tb_transaksi t
                WHERE t.id_area = a.id_area
                  AND t.status = 'masuk'
                  AND t.waktu_keluar IS NULL
            )
            WHERE a.id_area = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id_area);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $conn->query("
            UPDATE tb_area_parkir a
            SET a.terisi = (
                SELECT COUNT(*)
                FROM tb_transaksi t
                WHERE t.id_area = a.id_area
                  AND t.status = 'masuk'
                  AND t.waktu_keluar IS NULL
            )
        ");
    }
}

/* =========================================================
   FUNGSI SINKRONISASI AREA KARYAWAN
   ========================================================= */
function sinkronisasiAreaKaryawan($conn, $id_area_karyawan = null)
{
    if ($id_area_karyawan !== null) {
        $id_area_karyawan = (int) $id_area_karyawan;
        $stmt = $conn->prepare("
            UPDATE tb_area_parkir_karyawan a
            SET a.terisi = (
                SELECT COUNT(*)
                FROM tb_transaksi t
                WHERE t.id_area_karyawan = a.id_area_karyawan
                  AND t.status = 'masuk'
                  AND t.waktu_keluar IS NULL
            )
            WHERE a.id_area_karyawan = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id_area_karyawan);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $conn->query("
            UPDATE tb_area_parkir_karyawan a
            SET a.terisi = (
                SELECT COUNT(*)
                FROM tb_transaksi t
                WHERE t.id_area_karyawan = a.id_area_karyawan
                  AND t.status = 'masuk'
                  AND t.waktu_keluar IS NULL
            )
        ");
    }

    /* status area karyawan menyesuaikan otomatis, kecuali sedang dinonaktifkan manual */
    $conn->query("
        UPDATE tb_area_parkir_karyawan
        SET status = CASE
            WHEN status = 'nonaktif' THEN 'nonaktif'
            WHEN terisi >= kapasitas THEN 'penuh'
            ELSE 'tersedia'
        END
    ");
}

/* =========================================================
   SINKRONISASI SEMUA AREA
   ========================================================= */
sinkronisasiAreaReguler($conn);
sinkronisasiAreaKaryawan($conn);

/* =========================================================
   CHECK-IN DARI BOOKING
   ========================================================= */
if (isset($_POST['checkin_booking'])) {
    $id_booking = (int) ($_POST['id_booking'] ?? 0);
    if ($id_booking <= 0) {
        header("Location: transaksi.php?error=1");
        exit;
    }
    $conn->begin_transaction();
    try {
        /* -------------------------------------------------
           AMBIL BOOKING
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            SELECT
                b.id_booking,
                b.id_kendaraan,
                b.id_area,
                b.id_area_karyawan,
                b.id_user,
                k.plat_nomor,
                k.jenis_kendaraan,
                t.id_tarif,
                t.tarif_per_jam
            FROM tb_booking b
            INNER JOIN tb_kendaraan k
                ON k.id_kendaraan = b.id_kendaraan
            INNER JOIN tb_tarif t
                ON LOWER(TRIM(t.jenis_kendaraan))
                 = LOWER(TRIM(k.jenis_kendaraan))
            WHERE b.id_booking = ?
              AND b.status = 'booking'
            LIMIT 1
            FOR UPDATE
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("i", $id_booking);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("booking");
        }

        $id_kendaraan    = (int) $booking['id_kendaraan'];
        $id_user_booking = (int) $booking['id_user'];
        $id_tarif        = (int) $booking['id_tarif'];

        $id_area          = $booking['id_area'] !== null ? (int) $booking['id_area'] : null;
        $id_area_karyawan = $booking['id_area_karyawan'] !== null ? (int) $booking['id_area_karyawan'] : null;

        if (!empty($id_area)) {
            $tipe_area = 'reguler';
        } elseif (!empty($id_area_karyawan)) {
            $tipe_area = 'karyawan';
        } else {
            throw new Exception("area_tidak_ditemukan");
        }

        /* -------------------------------------------------
           KUNCI AREA
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            $stmt = $conn->prepare("
                SELECT
                    id_area,
                    nama_area,
                    kapasitas,
                    terisi
                FROM tb_area_parkir
                WHERE id_area = ?
                LIMIT 1
                FOR UPDATE
            ");
            if (!$stmt) {
                throw new Exception("database");
            }
            $stmt->bind_param("i", $id_area);
        } else {
            $stmt = $conn->prepare("
                SELECT
                    id_area_karyawan,
                    nama_area,
                    kapasitas,
                    terisi
                FROM tb_area_parkir_karyawan
                WHERE id_area_karyawan = ?
                LIMIT 1
                FOR UPDATE
            ");
            if (!$stmt) {
                throw new Exception("database");
            }
            $stmt->bind_param("i", $id_area_karyawan);
        }
        $stmt->execute();
        $area = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$area) {
            throw new Exception("area_tidak_ditemukan");
        }

        /* -------------------------------------------------
           HITUNG KENDARAAN AKTIF AKTUAL
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM tb_transaksi
                WHERE id_area = ?
                  AND status = 'masuk'
                  AND waktu_keluar IS NULL
            ");
            $stmt->bind_param("i", $id_area);
        } else {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM tb_transaksi
                WHERE id_area_karyawan = ?
                  AND status = 'masuk'
                  AND waktu_keluar IS NULL
            ");
            $stmt->bind_param("i", $id_area_karyawan);
        }
        $stmt->execute();
        $aktif_area = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $terisi_aktual = (int) ($aktif_area['total'] ?? 0);

        /* -------------------------------------------------
           CEK KAPASITAS
           ------------------------------------------------- */
        if ($terisi_aktual >= (int) $area['kapasitas']) {
            throw new Exception("area_penuh");
        }

        /* -------------------------------------------------
           CEK KENDARAAN SUDAH PARKIR
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            SELECT id_parkir
            FROM tb_transaksi
            WHERE id_kendaraan = ?
              AND status = 'masuk'
              AND waktu_keluar IS NULL
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id_kendaraan);
        $stmt->execute();
        $sudah_parkir = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sudah_parkir) {
            throw new Exception("sudah_parkir");
        }

        /* -------------------------------------------------
           INSERT TRANSAKSI PARKIR
           ------------------------------------------------- */
        $stmt = $conn->prepare("
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
                ?,
                ?,
                ?
            )
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param(
            "iiiiii",
            $id_kendaraan,
            $id_tarif,
            $id_user_booking,
            $id_area,
            $id_area_karyawan,
            $id_booking
        );
        if (!$stmt->execute()) {
            throw new Exception("database");
        }
        $stmt->close();

        /* -------------------------------------------------
           UPDATE STATUS BOOKING
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            UPDATE tb_booking
            SET status = 'aktif'
            WHERE id_booking = ?
              AND status = 'booking'
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("i", $id_booking);
        if (!$stmt->execute()) {
            throw new Exception("database");
        }
        $stmt->close();

        /* -------------------------------------------------
           SINKRONISASI AREA
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            sinkronisasiAreaReguler($conn, $id_area);
        } else {
            sinkronisasiAreaKaryawan($conn, $id_area_karyawan);
        }

        /* -------------------------------------------------
           COMMIT
           ------------------------------------------------- */
        $conn->commit();
        header("Location: transaksi.php?sukses=checkin");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $allowed_errors = [
            'booking',
            'area_tidak_ditemukan',
            'area_penuh',
            'sudah_parkir'
        ];
        if (in_array($error, $allowed_errors, true)) {
            header("Location: transaksi.php?error=" . urlencode($error));
        } else {
            header("Location: transaksi.php?error=database");
        }
        exit;
    }
}

/* =========================================================
   BATALKAN BOOKING
   ========================================================= */
if (isset($_POST['batalkan_booking'])) {
    $id_booking = (int) ($_POST['id_booking'] ?? 0);
    if ($id_booking > 0) {
        $stmt = $conn->prepare("
            UPDATE tb_booking
            SET status = 'batal'
            WHERE id_booking = ?
              AND status = 'booking'
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id_booking);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: transaksi.php?sukses=batal_booking");
    exit;
}

/* =========================================================
   PARKIR MASUK LANGSUNG
   ========================================================= */
if (isset($_POST['parkir_masuk'])) {
    $id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);
    $tipe_area    = (($_POST['tipe_area'] ?? '') === 'karyawan') ? 'karyawan' : 'reguler';

    if ($tipe_area === 'karyawan') {
        $id_area_pilih = (int) ($_POST['id_area_karyawan'] ?? 0);
    } else {
        $id_area_pilih = (int) ($_POST['id_area_reguler'] ?? 0);
    }

    if ($id_kendaraan <= 0 || $id_area_pilih <= 0) {
        header("Location: transaksi.php?error=data_masuk");
        exit;
    }

    $conn->begin_transaction();
    try {
        /* -------------------------------------------------
           KUNCI KENDARAAN
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            SELECT
                id_kendaraan,
                jenis_kendaraan
            FROM tb_kendaraan
            WHERE id_kendaraan = ?
            LIMIT 1
            FOR UPDATE
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("i", $id_kendaraan);
        $stmt->execute();
        $kendaraan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$kendaraan) {
            throw new Exception("kendaraan_tidak_ditemukan");
        }

        /* -------------------------------------------------
           CEK KENDARAAN SEDANG PARKIR
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            SELECT id_parkir
            FROM tb_transaksi
            WHERE id_kendaraan = ?
              AND status = 'masuk'
              AND waktu_keluar IS NULL
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id_kendaraan);
        $stmt->execute();
        $cek_parkir = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cek_parkir) {
            throw new Exception("sudah_parkir");
        }

        /* -------------------------------------------------
           AMBIL TARIF
           ------------------------------------------------- */
        $jenis_kendaraan = $kendaraan['jenis_kendaraan'];
        $stmt = $conn->prepare("
            SELECT
                id_tarif,
                tarif_per_jam
            FROM tb_tarif
            WHERE LOWER(TRIM(jenis_kendaraan))
                = LOWER(TRIM(?))
            LIMIT 1
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("s", $jenis_kendaraan);
        $stmt->execute();
        $tarif_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$tarif_data) {
            throw new Exception("tarif_tidak_ditemukan");
        }

        $id_tarif = (int) $tarif_data['id_tarif'];

        /* -------------------------------------------------
           KUNCI AREA
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            $stmt = $conn->prepare("
                SELECT
                    id_area,
                    nama_area,
                    kapasitas
                FROM tb_area_parkir
                WHERE id_area = ?
                LIMIT 1
                FOR UPDATE
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT
                    id_area_karyawan,
                    nama_area,
                    kapasitas,
                    status
                FROM tb_area_parkir_karyawan
                WHERE id_area_karyawan = ?
                LIMIT 1
                FOR UPDATE
            ");
        }
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("i", $id_area_pilih);
        $stmt->execute();
        $area = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$area) {
            throw new Exception("area_tidak_ditemukan");
        }

        if ($tipe_area === 'karyawan' && $area['status'] === 'nonaktif') {
            throw new Exception("area_tidak_ditemukan");
        }

        /* -------------------------------------------------
           HITUNG AREA
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM tb_transaksi
                WHERE id_area = ?
                  AND status = 'masuk'
                  AND waktu_keluar IS NULL
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM tb_transaksi
                WHERE id_area_karyawan = ?
                  AND status = 'masuk'
                  AND waktu_keluar IS NULL
            ");
        }
        $stmt->bind_param("i", $id_area_pilih);
        $stmt->execute();
        $aktif_area = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $terisi_aktual = (int) ($aktif_area['total'] ?? 0);

        /* -------------------------------------------------
           CEK KAPASITAS
           ------------------------------------------------- */
        if ($terisi_aktual >= (int) $area['kapasitas']) {
            throw new Exception("area_penuh");
        }

        /* -------------------------------------------------
           INSERT TRANSAKSI
           ------------------------------------------------- */
        $insert_id_area          = ($tipe_area === 'reguler')  ? $id_area_pilih : null;
        $insert_id_area_karyawan = ($tipe_area === 'karyawan') ? $id_area_pilih : null;

        $stmt = $conn->prepare("
            INSERT INTO tb_transaksi
            (
                id_kendaraan,
                waktu_masuk,
                id_tarif,
                status,
                id_user,
                id_area,
                id_area_karyawan
            )
            VALUES
            (
                ?,
                NOW(),
                ?,
                'masuk',
                ?,
                ?,
                ?
            )
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param(
            "iiiii",
            $id_kendaraan,
            $id_tarif,
            $id_user,
            $insert_id_area,
            $insert_id_area_karyawan
        );
        if (!$stmt->execute()) {
            throw new Exception("database");
        }
        $stmt->close();

        /* -------------------------------------------------
           SINKRONISASI AREA
           ------------------------------------------------- */
        if ($tipe_area === 'reguler') {
            sinkronisasiAreaReguler($conn, $insert_id_area);
        } else {
            sinkronisasiAreaKaryawan($conn, $insert_id_area_karyawan);
        }

        $conn->commit();
        header("Location: transaksi.php?sukses=masuk");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $allowed_errors = [
            'kendaraan_tidak_ditemukan',
            'sudah_parkir',
            'tarif_tidak_ditemukan',
            'area_tidak_ditemukan',
            'area_penuh'
        ];
        if (in_array($error, $allowed_errors, true)) {
            header("Location: transaksi.php?error=" . urlencode($error));
        } else {
            header("Location: transaksi.php?error=database");
        }
        exit;
    }
}

/* =========================================================
   KOMPATIBILITAS LINK LAMA (transaksi.php?keluar=ID)
   Proses keluar sekarang lewat halaman scan_qr.php,
   jadi link lama langsung diarahkan ke sana.
   ========================================================= */
if (isset($_GET['keluar'])) {
    $id_keluar = (int) $_GET['keluar'];
    header("Location: scan_qr.php" . ($id_keluar > 0 ? "?id_parkir=" . $id_keluar : ""));
    exit;
}

/* =========================================================
   PARKIR KELUAR + PEMBAYARAN (CASH / QRIS)
   Pembayaran Cash / QRIS tetap dipertahankan di halaman ini
   (modal "Bayar Manual"). scan_qr.php juga boleh mem-POST ke
   transaksi.php dengan field: proses_keluar, id_parkir,
   metode_pembayaran.
   ========================================================= */
if (isset($_POST['proses_keluar'])) {

    $id_parkir = (int) ($_POST['id_parkir'] ?? 0);
    $metode    = strtolower(trim($_POST['metode_pembayaran'] ?? ''));

    if ($id_parkir <= 0) {
        header("Location: transaksi.php?error=transaksi_tidak_ditemukan");
        exit;
    }

    /* metode wajib valid, jangan percaya input dari form */
    if (!in_array($metode, ['cash', 'qris'], true)) {
        header("Location: transaksi.php?error=metode_pembayaran");
        exit;
    }

    $conn->begin_transaction();
    try {
        /* -------------------------------------------------
           AMBIL TRANSAKSI AKTIF
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            SELECT
                t.*,
                tr.tarif_per_jam
            FROM tb_transaksi t
            INNER JOIN tb_tarif tr
                ON tr.id_tarif = t.id_tarif
            WHERE t.id_parkir = ?
              AND t.status = 'masuk'
              AND t.waktu_keluar IS NULL
            LIMIT 1
            FOR UPDATE
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("i", $id_parkir);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$data) {
            throw new Exception("transaksi_tidak_ditemukan");
        }

        /* -------------------------------------------------
           AREA
           ------------------------------------------------- */
        $id_area_keluar = !empty($data['id_area'])
            ? (int) $data['id_area']
            : 0;

        $id_area_karyawan_keluar = !empty($data['id_area_karyawan'])
            ? (int) $data['id_area_karyawan']
            : 0;

        /* -------------------------------------------------
           HITUNG DURASI
           ------------------------------------------------- */
        $masuk   = strtotime($data['waktu_masuk']);
        $keluar  = time();
        $selisih = max(0, $keluar - $masuk);
        $jam     = max(1, (int) ceil($selisih / 3600));

        /* -------------------------------------------------
           HITUNG BIAYA
           ------------------------------------------------- */
        $tarif_per_jam = (float) $data['tarif_per_jam'];
        $total = $jam * $tarif_per_jam;

        /* -------------------------------------------------
           UPDATE TRANSAKSI KELUAR + METODE PEMBAYARAN
           ------------------------------------------------- */
        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET
                waktu_keluar      = NOW(),
                durasi_jam        = ?,
                biaya_total       = ?,
                metode_pembayaran = ?,
                status            = 'keluar'
            WHERE id_parkir = ?
              AND status = 'masuk'
              AND waktu_keluar IS NULL
        ");
        if (!$stmt) {
            throw new Exception("database");
        }
        $stmt->bind_param("idsi", $jam, $total, $metode, $id_parkir);
        if (!$stmt->execute()) {
            throw new Exception("database");
        }
        $berhasil_keluar = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$berhasil_keluar) {
            throw new Exception("transaksi_tidak_ditemukan");
        }

        /* -------------------------------------------------
           UPDATE BOOKING MENJADI SELESAI
           ------------------------------------------------- */
        if (!empty($data['id_booking'])) {
            $id_booking_selesai = (int) $data['id_booking'];

            $stmt = $conn->prepare("
                UPDATE tb_booking
                SET status = 'selesai'
                WHERE id_booking = ?
            ");
            if (!$stmt) {
                throw new Exception("database");
            }
            $stmt->bind_param("i", $id_booking_selesai);
            if (!$stmt->execute()) {
                throw new Exception("database");
            }
            $stmt->close();
        }

        /* -------------------------------------------------
           SINKRONISASI AREA
           ------------------------------------------------- */
        if ($id_area_keluar > 0) {
            sinkronisasiAreaReguler($conn, $id_area_keluar);
        } elseif ($id_area_karyawan_keluar > 0) {
            sinkronisasiAreaKaryawan($conn, $id_area_karyawan_keluar);
        }

        $conn->commit();
        header("Location: transaksi.php?sukses=keluar_" . $metode);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        if ($error === 'transaksi_tidak_ditemukan') {
            header("Location: transaksi.php?error=transaksi_tidak_ditemukan");
        } else {
            header("Location: transaksi.php?error=database");
        }
        exit;
    }
}

/* =========================================================
   EDIT DURASI PARKIR
   ========================================================= */
if (isset($_POST['update_durasi'])) {
    $id_parkir   = (int) ($_POST['id_parkir'] ?? 0);
    $durasi_baru = (int) ($_POST['durasi_jam'] ?? 1);

    if ($durasi_baru < 1) {
        $durasi_baru = 1;
    }

    $stmt = $conn->prepare("
        SELECT
            tr.tarif_per_jam
        FROM tb_transaksi t
        INNER JOIN tb_tarif tr
            ON tr.id_tarif = t.id_tarif
        WHERE t.id_parkir = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $id_parkir);
        $stmt->execute();
        $data_durasi = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($data_durasi) {
            $tarif      = (float) $data_durasi['tarif_per_jam'];
            $total_baru = $durasi_baru * $tarif;

            $stmt = $conn->prepare("
                UPDATE tb_transaksi
                SET
                    durasi_jam = ?,
                    biaya_total = ?
                WHERE id_parkir = ?
            ");
            if ($stmt) {
                $stmt->bind_param("idi", $durasi_baru, $total_baru, $id_parkir);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: transaksi.php?sukses=edit_durasi");
    exit;
}

/* =========================================================
   UBAH METODE PEMBAYARAN (KOREKSI PETUGAS)
   ========================================================= */
if (isset($_POST['update_metode'])) {
    $id_parkir = (int) ($_POST['id_parkir'] ?? 0);
    $metode    = strtolower(trim($_POST['metode_pembayaran'] ?? ''));

    if ($id_parkir > 0 && in_array($metode, ['cash', 'qris'], true)) {
        $stmt = $conn->prepare("
            UPDATE tb_transaksi
            SET metode_pembayaran = ?
            WHERE id_parkir = ?
              AND status = 'keluar'
        ");
        if ($stmt) {
            $stmt->bind_param("si", $metode, $id_parkir);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: transaksi.php?sukses=edit_metode");
        exit;
    }

    header("Location: transaksi.php?error=metode_pembayaran");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transaksi Parkir - E-Parkir</title>

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

.sidebar .brand {
    padding: 20px;
    font-size: 20px;
    font-weight: bold;
    background: #0f172a;
    border-bottom: 1px solid #334155;
    text-align: center;
}

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
    margin-bottom: 5px;
}

.nav-links {
    list-style: none;
    padding: 15px 0;
    flex-grow: 1;
    overflow-y: auto;
}

.nav-links li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 14px;
    transition: .2s;
}

.nav-links li a:hover,
.nav-links li a.active {
    background: #007bff;
    color: #fff;
}

.logout-container {
    padding: 15px 20px;
    border-top: 1px solid #334155;
}

.logout-btn {
    display: block;
    width: 100%;
    padding: 10px;
    background: #dc3545;
    color: white;
    text-decoration: none;
    text-align: center;
    border-radius: 7px;
    font-weight: bold;
    transition: .2s;
}

.logout-btn:hover {
    background: #bd2130;
}

/* =========================================================
   MOBILE
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

.overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1500;
}

/* =========================================================
   MAIN
   ========================================================= */
.main-content {
    margin-left: 250px;
    padding: 30px;
    min-height: 100vh;
}

.container {
    max-width: 1400px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,.08);
}

.page-title {
    margin-bottom: 25px;
    color: #212529;
    font-size: 25px;
}

h3 {
    margin-top: 25px;
    margin-bottom: 15px;
    color: #343a40;
}

hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 30px 0;
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
    margin-top: 10px;
}

table th,
table td {
    padding: 11px;
    border: 1px solid #ddd;
    text-align: left;
    white-space: nowrap;
}

table th {
    background: #007bff;
    color: white;
}

table tbody tr:nth-child(even) {
    background: #f8f9fa;
}

table tbody tr:hover {
    background: #eef5ff;
}

/* =========================================================
   FORM
   ========================================================= */
.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

input,
select {
    width: 100%;
    padding: 11px;
    border: 1px solid #ced4da;
    border-radius: 7px;
    outline: none;
    font-size: 14px;
    background: #fff;
}

input:focus,
select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,.1);
}

button {
    background: #007bff;
    color: white;
    padding: 10px 18px;
    border: none;
    cursor: pointer;
    border-radius: 7px;
    font-size: 14px;
}

button:hover {
    background: #0056b3;
}

/* =========================================================
   TIPE AREA TOGGLE
   ========================================================= */
.tipe-area-toggle {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.tipe-area-toggle label {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px;
    border: 1px solid #ced4da;
    border-radius: 7px;
    font-weight: normal;
    cursor: pointer;
    margin-bottom: 0;
    transition: .2s;
}

.tipe-area-toggle input[type="radio"] {
    width: auto;
}

.tipe-area-toggle label.selected {
    border-color: #007bff;
    background: #eff6ff;
}

/* =========================================================
   BUTTON
   ========================================================= */
.btn {
    padding: 8px 13px;
    background: #198754;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    display: inline-block;
    border: none;
    cursor: pointer;
    font-size: 13px;
    margin: 2px;
}

.btn:hover {
    background: #146c43;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-danger {
    background: #dc3545;
}

.btn-danger:hover {
    background: #b02a37;
}

.btn-back {
    background: #6c757d;
}

.btn-back:hover {
    background: #545b62;
}

/* tombol scan QR (keluar) di header kartu kendaraan aktif */
.btn-scan {
    background: #2563eb;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    border-radius: 10px;
}

.btn-scan:hover {
    background: #1d4ed8;
}

/* =========================================================
   ALERT
   ========================================================= */
.alert-success {
    padding: 12px 15px;
    background: #d1e7dd;
    color: #0f5132;
    border-radius: 7px;
    margin-bottom: 15px;
}

.alert-error {
    padding: 12px 15px;
    background: #f8d7da;
    color: #842029;
    border-radius: 7px;
    margin-bottom: 15px;
}

/* =========================================================
   ACTIVE PARKING
   ========================================================= */
.parking-active-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 5px 18px rgba(15,23,42,.07);
}

.parking-active-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.parking-active-header h4 {
    font-size: 18px;
    color: #0f172a;
    margin-bottom: 5px;
}

.parking-active-header p {
    color: #64748b;
    font-size: 13px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.active-counter {
    min-width: 95px;
    padding: 12px 16px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 12px;
    text-align: center;
    font-size: 22px;
    font-weight: 800;
}

.active-counter span {
    display: block;
    margin-top: 2px;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
}

/* =========================================================
   VEHICLE
   ========================================================= */
.vehicle-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 150px;
}

.vehicle-icon-active {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eff6ff;
    border-radius: 10px;
    font-size: 21px;
    flex-shrink: 0;
}

.vehicle-info strong {
    display: block;
    color: #0f172a;
    font-size: 13px;
}

.vehicle-info small {
    display: block;
    color: #64748b;
    font-size: 11px;
    margin-top: 3px;
}

/* =========================================================
   PLAT
   ========================================================= */
.plate-number {
    display: inline-block;
    padding: 7px 10px;
    background: #111827;
    color: #fff;
    border-radius: 6px;
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .5px;
}

/* =========================================================
   AREA
   ========================================================= */
.area-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 10px;
    background: #f0fdf4;
    color: #166534;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
}

.area-badge.karyawan {
    background: #fef3c7;
    color: #92400e;
}

/* =========================================================
   TIME
   ========================================================= */
.time-info strong {
    display: block;
    color: #0f172a;
    font-size: 14px;
}

.time-info small {
    display: block;
    color: #64748b;
    font-size: 11px;
    margin-top: 3px;
}

/* =========================================================
   DURATION
   ========================================================= */
.duration-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 10px;
    background: #fff7ed;
    color: #c2410c;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* =========================================================
   SOURCE
   ========================================================= */
.source-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.source-badge.booking {
    background: #ede9fe;
    color: #6d28d9;
}

.source-badge.langsung {
    background: #ecfeff;
    color: #0e7490;
}

/* =========================================================
   METODE PEMBAYARAN (BADGE)
   ========================================================= */
.pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.pay-badge.cash {
    background: #dcfce7;
    color: #166534;
}

.pay-badge.qris {
    background: #dbeafe;
    color: #1d4ed8;
}

.pay-badge.none {
    background: #f1f5f9;
    color: #94a3b8;
}

/* =========================================================
   EMPTY
   ========================================================= */
.empty-active {
    text-align: center !important;
    padding: 50px 20px !important;
    color: #64748b;
}

.empty-active-icon {
    font-size: 42px;
    margin-bottom: 10px;
}

.empty-active strong {
    display: block;
    color: #334155;
    font-size: 15px;
}

.empty-active small {
    display: block;
    margin-top: 5px;
    color: #94a3b8;
}

/* =========================================================
   MODAL (EDIT DURASI & UBAH METODE)
   ========================================================= */
.modal {
    display: none;
    position: fixed;
    z-index: 5000;
    inset: 0;
    background: rgba(0, 0, 0, .55);
    padding: 20px;
    overflow-y: auto;
}

.modal-content {
    background: #fff;
    width: 400px;
    max-width: 100%;
    margin: 30px auto;
    padding: 22px;
    border-radius: 14px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, .25);
    max-height: calc(100vh - 60px);
    overflow-y: auto;
}

.modal-content h3 {
    margin-top: 0;
    margin-bottom: 18px;
    padding-right: 30px;
    font-size: 20px;
    color: #0f172a;
}

.close {
    color: #94a3b8;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.close:hover {
    color: #111827;
}

/* toggle Cash / QRIS (modal ubah metode) */
.metode-toggle {
    display: flex;
    gap: 10px;
    width: 100%;
    margin-top: 8px;
    margin-bottom: 14px;
}

.metode-toggle label {
    flex: 1;
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 9px;
    font-weight: 600;
    cursor: pointer;
    margin-bottom: 0;
    text-align: center;
    transition: all .2s ease;
    background: #fff;
}

.metode-toggle label:hover {
    border-color: #007bff;
    background: #f8fbff;
}

.metode-toggle input[type="radio"] {
    width: auto;
    margin: 0;
    flex-shrink: 0;
}

.metode-toggle label.selected {
    border-color: #007bff;
    background: #eff6ff;
    color: #007bff;
}

/* =========================================================
   RESPONSIVE (TABLET / HP)
   ========================================================= */
@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform .25s ease;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .overlay.show {
        display: block;
    }

    .mobile-menu-btn {
        display: block;
    }

    .main-content {
        margin-left: 0;
        padding: 70px 15px 20px;
    }

    .container {
        padding: 18px;
    }

    .parking-active-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-actions {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 600px) {
    .modal {
        padding: 12px;
    }

    .modal-content {
        width: 100%;
        margin: 15px auto;
        padding: 18px;
        max-height: calc(100vh - 30px);
    }

    .modal-content h3 {
        font-size: 18px;
        margin-bottom: 15px;
    }

    .metode-toggle {
        gap: 8px;
    }

    .metode-toggle label {
        min-height: 46px;
        padding: 9px 8px;
        font-size: 13px;
    }
}

@media (max-height: 650px) {
    .modal-content {
        margin: 10px auto;
        max-height: calc(100vh - 20px);
    }
}

/* =========================================================
   TOMBOL KUITANSI
   ========================================================= */
.btn-info {
    background: #0891b2;
}

.btn-info:hover {
    background: #0e7490;
}

/* =========================================================
   RINCIAN PEMBAYARAN (MODAL) + QRIS
   ========================================================= */
.bayar-info {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 18px;
}

.bayar-info > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    padding: 5px 0;
}

.bayar-info span {
    color: #64748b;
}

.bayar-info b {
    color: #0f172a;
}

.bayar-total {
    margin-top: 6px;
    padding-top: 10px !important;
    border-top: 1px dashed #cbd5e1;
}

.bayar-total b {
    font-size: 19px;
    color: #16a34a !important;
}

.bayar-note {
    display: block;
    margin: -8px 0 14px;
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.5;
}

.qris-box {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 12px;
    margin-top: 4px;
    margin-bottom: 15px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    text-align: center;
}

.qris-box img {
    display: block;
    width: 180px;
    height: 180px;
    max-width: 100%;
    object-fit: contain;
    margin: 0 auto 10px;
    border-radius: 6px;
}

.qris-box small {
    display: block;
    max-width: 300px;
    color: #64748b;
    font-size: 11px;
    line-height: 1.5;
    margin: 0 auto;
}

.btn-block {
    width: 100%;
    padding: 12px;
    background: #198754;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
}

.btn-block:hover {
    background: #146c43;
}

@media (max-width: 600px) {
    .qris-box img {
        width: 170px;
        height: 170px;
    }
}

@media (max-height: 650px) {
    .qris-box img {
        width: 150px;
        height: 150px;
    }
}
</style>
</head>

<body>

<!-- =========================================================
     MOBILE MENU
     ========================================================= -->
<button type="button" class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- =========================================================
     SIDEBAR
     ========================================================= -->
<aside class="sidebar" id="sidebar">
    <div class="brand">🅿️ E-Parkir Petugas</div>

    <div class="user-info">
        Role: <b><?= htmlspecialchars(strtoupper($role)); ?></b>
        User: <b><?= htmlspecialchars($nama); ?></b>
    </div>

    <ul class="nav-links">
        <li><a href="../dashboard.php"><span>🏠</span> Dashboard</a></li>
        <li><a href="transaksi.php" class="active"><span>🎫</span> Transaksi Parkir</a></li>
        <li><a href="scan_qr.php"><span>📷</span> Scan QR Masuk/Keluar</a></li>
        <li><a href="area.php"><span>🅿️</span> Area Parkir</a></li>
        <li><a href="kendaraan.php"><span>🚗</span> Kelola Kendaraan</a></li>
        <li><a href="log.php"><span>📋</span> Log Aktivitas</a></li>
    </ul>

    <div class="logout-container">
        <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?');">🚪 Logout</a>
    </div>
</aside>

<!-- =========================================================
     MAIN
     ========================================================= -->
<main class="main-content">
<div class="container">

<h2 class="page-title">🎫 Transaksi Parkir</h2>

<?php
/* =========================================================
   PESAN SUKSES
   ========================================================= */
if (isset($_GET['sukses'])) {
    $pesan_sukses_map = [
        'masuk'         => 'Parkir masuk berhasil ditambahkan.',
        'keluar'        => 'Parkir keluar berhasil diproses.',
        'keluar_cash'   => 'Parkir keluar berhasil diproses. Pembayaran: CASH 💵',
        'keluar_qris'   => 'Parkir keluar berhasil diproses. Pembayaran: QRIS 📱',
        'edit_durasi'   => 'Durasi parkir dan biaya berhasil diperbarui.',
        'edit_metode'   => 'Metode pembayaran berhasil diperbarui.',
        'checkin'       => 'Booking berhasil dikonfirmasi masuk (check-in).',
        'batal_booking' => 'Booking berhasil dibatalkan.'
    ];

    if (isset($pesan_sukses_map[$_GET['sukses']])) {
        echo '<div class="alert-success">' .
            htmlspecialchars($pesan_sukses_map[$_GET['sukses']]) .
            '</div>';
    }
}

/* =========================================================
   PESAN ERROR
   ========================================================= */
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $pesan_error = [
        '1'                         => 'Booking tidak ditemukan atau sudah diproses.',
        'booking'                   => 'Booking tidak ditemukan atau sudah diproses.',
        'sudah_parkir'              => 'Kendaraan tersebut masih tercatat sedang parkir.',
        'tarif_tidak_ditemukan'     => 'Tarif untuk jenis kendaraan tersebut belum tersedia.',
        'area_penuh'                => 'Area parkir yang dipilih sudah penuh.',
        'area_tidak_ditemukan'      => 'Area parkir tidak ditemukan.',
        'data_masuk'                => 'Kendaraan dan area parkir wajib dipilih.',
        'kendaraan_tidak_ditemukan' => 'Kendaraan tidak ditemukan.',
        'metode_pembayaran'         => 'Metode pembayaran wajib dipilih (Cash atau QRIS).',
        'transaksi_tidak_ditemukan' => 'Transaksi parkir tidak ditemukan atau sudah selesai.',
        'database'                  => 'Terjadi kesalahan database. Silakan periksa koneksi atau struktur tabel.'
    ];

    if (isset($pesan_error[$error])) {
        echo '<div class="alert-error">' .
            htmlspecialchars($pesan_error[$error]) .
            '</div>';
    }
}
?>

<!-- =========================================================
     BOOKING MENUNGGU CHECK-IN
     ========================================================= -->
<h3>📅 Booking Menunggu Check-in</h3>

<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>No</th>
    <th>Plat</th>
    <th>Pemilik</th>
    <th>Jenis</th>
    <th>Tipe Area</th>
    <th>Area</th>
    <th>Tanggal</th>
    <th>Jam Booking</th>
    <th>Estimasi</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>
<?php
$booking_pending = $conn->query("
    SELECT
        b.id_booking,
        b.tanggal,
        b.jam_masuk,
        b.estimasi_jam,
        b.id_area,
        b.id_area_karyawan,
        k.plat_nomor,
        k.pemilik,
        k.jenis_kendaraan,
        COALESCE(a.nama_area, ak.nama_area) AS nama_area
    FROM tb_booking b
    INNER JOIN tb_kendaraan k
        ON k.id_kendaraan = b.id_kendaraan
    LEFT JOIN tb_area_parkir a
        ON a.id_area = b.id_area
    LEFT JOIN tb_area_parkir_karyawan ak
        ON ak.id_area_karyawan = b.id_area_karyawan
    WHERE b.status = 'booking'
    ORDER BY
        b.tanggal ASC,
        b.jam_masuk ASC
");

if (!$booking_pending) {
    echo '<tr><td colspan="10">Gagal mengambil data booking.</td></tr>';
} elseif ($booking_pending->num_rows === 0) {
    echo '<tr><td colspan="10">Tidak ada booking yang menunggu check-in.</td></tr>';
} else {
    $no_b = 1;

    while ($b = $booking_pending->fetch_assoc()) {
        $tipe_area_b = !empty($b['id_area']) ? 'reguler' : 'karyawan';
?>
<tr>
    <td><?= $no_b++; ?></td>
    <td><?= htmlspecialchars($b['plat_nomor']); ?></td>
    <td><?= htmlspecialchars($b['pemilik'] ?: '-'); ?></td>
    <td><?= htmlspecialchars(ucfirst($b['jenis_kendaraan'])); ?></td>
    <td>
        <?php if ($tipe_area_b === 'karyawan'): ?>
            <span class="source-badge booking">👔 Karyawan</span>
        <?php else: ?>
            <span class="source-badge langsung">🅿️ Reguler</span>
        <?php endif; ?>
    </td>
    <td>
        <span class="area-badge<?= $tipe_area_b === 'karyawan' ? ' karyawan' : ''; ?>">
            🅿️ <?= htmlspecialchars($b['nama_area'] ?: '-'); ?>
        </span>
    </td>
    <td><?= date('d-m-Y', strtotime($b['tanggal'])); ?></td>
    <td><?= date('H:i', strtotime($b['jam_masuk'])); ?></td>
    <td><?= (int) $b['estimasi_jam']; ?> Jam</td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id_booking" value="<?= (int) $b['id_booking']; ?>">
            <button type="submit" name="checkin_booking" class="btn">✅ Check-in</button>
        </form>

        <form method="POST" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?');">
            <input type="hidden" name="id_booking" value="<?= (int) $b['id_booking']; ?>">
            <button type="submit" name="batalkan_booking" class="btn btn-danger">❌ Batalkan</button>
        </form>
    </td>
</tr>
<?php
    }
}
?>
</tbody>
</table>
</div>

<hr>

<!-- =========================================================
     PARKIR MASUK LANGSUNG
     ========================================================= -->
<h3>🚗 Parkir Masuk Langsung</h3>

<form method="POST" id="formParkirMasuk">

<div class="form-group">
<label>Kendaraan</label>
<select name="id_kendaraan" required>
<option value="">Pilih Kendaraan</option>
<?php
$k = $conn->query("
    SELECT
        id_kendaraan,
        plat_nomor,
        jenis_kendaraan,
        merk,
        pemilik
    FROM tb_kendaraan
    ORDER BY plat_nomor ASC
");

while ($r = $k->fetch_assoc()) {
?>
<option value="<?= (int) $r['id_kendaraan']; ?>">
    <?= htmlspecialchars($r['plat_nomor']); ?> -
    <?= htmlspecialchars($r['jenis_kendaraan']); ?> -
    <?= htmlspecialchars($r['pemilik'] ?: '-'); ?>
</option>
<?php
}
?>
</select>
</div>

<div class="form-group">
<label>Tipe Area</label>
<div class="tipe-area-toggle">
    <label id="labelTipeReguler" class="selected">
        <input type="radio" name="tipe_area" value="reguler" checked onchange="toggleTipeArea()">
        🅿️ Area Reguler
    </label>
    <label id="labelTipeKaryawan">
        <input type="radio" name="tipe_area" value="karyawan" onchange="toggleTipeArea()">
        👔 Area Karyawan
    </label>
</div>
</div>

<div class="form-group" id="groupAreaReguler">
<label>Area Parkir Reguler</label>
<select name="id_area_reguler" id="selectAreaReguler" required>
<option value="">Pilih Area</option>
<?php
$a = $conn->query("
    SELECT
        a.id_area,
        a.nama_area,
        a.kapasitas,
        (
            SELECT COUNT(*)
            FROM tb_transaksi t
            WHERE t.id_area = a.id_area
              AND t.status = 'masuk'
              AND t.waktu_keluar IS NULL
        ) AS terisi
    FROM tb_area_parkir a
    ORDER BY a.nama_area ASC
");

while ($r = $a->fetch_assoc()) {
    $penuh = (int) $r['terisi'] >= (int) $r['kapasitas'];
?>
<option value="<?= (int) $r['id_area']; ?>" <?= $penuh ? 'disabled' : ''; ?>>
    <?= htmlspecialchars($r['nama_area']); ?>
    (<?= (int) $r['terisi']; ?>/<?= (int) $r['kapasitas']; ?>)
    <?= $penuh ? '- PENUH' : ''; ?>
</option>
<?php
}
?>
</select>
</div>

<div class="form-group" id="groupAreaKaryawan" style="display:none;">
<label>Area Parkir Karyawan</label>
<select name="id_area_karyawan" id="selectAreaKaryawan" disabled>
<option value="">Pilih Area</option>
<?php
$ak = $conn->query("
    SELECT
        id_area_karyawan,
        nama_area,
        kapasitas,
        terisi,
        status
    FROM tb_area_parkir_karyawan
    WHERE status != 'nonaktif'
    ORDER BY nama_area ASC
");

while ($r = $ak->fetch_assoc()) {
    $penuh = ($r['status'] === 'penuh') || ((int) $r['terisi'] >= (int) $r['kapasitas']);
?>
<option value="<?= (int) $r['id_area_karyawan']; ?>" <?= $penuh ? 'disabled' : ''; ?>>
    <?= htmlspecialchars($r['nama_area']); ?>
    (<?= (int) $r['terisi']; ?>/<?= (int) $r['kapasitas']; ?>)
    <?= $penuh ? '- PENUH' : ''; ?>
</option>
<?php
}
?>
</select>
</div>

<button type="submit" name="parkir_masuk">+ Simpan Parkir Masuk</button>

</form>

<hr>

<!-- =========================================================
     KENDARAAN MASIH PARKIR
     ========================================================= -->
<h3>🚙 Kendaraan Masih Parkir</h3>

<div class="parking-active-card">

<div class="parking-active-header">
<div>
<h4>Kendaraan Aktif di Area Parkir</h4>
<p>Daftar kendaraan yang saat ini masih berada di area parkir (reguler maupun karyawan). Proses keluar dilakukan lewat Scan QR.</p>
</div>

<div class="header-actions">
    <a href="scan_qr.php" class="btn btn-scan">📷 Scan QR Keluar</a>

    <div class="active-counter">
<?php
$count_active = $conn->query("
    SELECT COUNT(*) AS total
    FROM tb_transaksi
    WHERE status = 'masuk'
      AND waktu_keluar IS NULL
")->fetch_assoc();
?>
<?= (int) $count_active['total']; ?>
<span>Kendaraan</span>
    </div>
</div>
</div>

<div class="table-wrapper">

<table class="active-parking-table">

<thead>
<tr>
    <th>No</th>
    <th>Kendaraan</th>
    <th>Plat Nomor</th>
    <th>Pemilik</th>
    <th>Area</th>
    <th>Waktu Masuk</th>
    <th>Durasi</th>
    <th>Asal</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

<?php
$no_active = 1;

$data_active = $conn->query("
    SELECT
        t.id_parkir,
        t.id_kendaraan,
        t.id_booking,
        t.waktu_masuk,
        t.id_area,
        t.id_area_karyawan,
        k.plat_nomor,
        k.jenis_kendaraan,
        k.merk,
        k.warna,
        k.pemilik,
        COALESCE(tr.tarif_per_jam, 0) AS tarif_per_jam,
        COALESCE(a.nama_area, ak.nama_area) AS nama_area
    FROM tb_transaksi t
    INNER JOIN tb_kendaraan k
        ON k.id_kendaraan = t.id_kendaraan
    LEFT JOIN tb_tarif tr
        ON tr.id_tarif = t.id_tarif
    LEFT JOIN tb_area_parkir a
        ON a.id_area = t.id_area
    LEFT JOIN tb_area_parkir_karyawan ak
        ON ak.id_area_karyawan = t.id_area_karyawan
    WHERE t.status = 'masuk'
      AND t.waktu_keluar IS NULL
    ORDER BY t.waktu_masuk ASC
");

if (!$data_active) {
    echo '<tr><td colspan="9" class="empty-active">❌ Gagal mengambil data kendaraan aktif.</td></tr>';

} elseif ($data_active->num_rows === 0) {
    echo '
        <tr>
            <td colspan="9" class="empty-active">
                <div class="empty-active-icon">🅿️</div>
                <strong>Tidak ada kendaraan yang sedang parkir</strong>
                <small>Semua kendaraan sudah keluar dari area parkir.</small>
            </td>
        </tr>
    ';

} else {

    while ($d = $data_active->fetch_assoc()) {

        /* =================================================
           JENIS KENDARAAN
           ================================================= */
        $jenis = strtolower(trim($d['jenis_kendaraan']));
        $icon = '🚘';

        if ($jenis === 'motor' || $jenis === 'motorcycle') {
            $icon = '🏍️';
        } elseif ($jenis === 'mobil' || $jenis === 'car') {
            $icon = '🚗';
        } elseif ($jenis === 'bus') {
            $icon = '🚌';
        } elseif ($jenis === 'truk' || $jenis === 'truck') {
            $icon = '🚚';
        } elseif ($jenis === 'pickup') {
            $icon = '🛻';
        } elseif ($jenis === 'sepeda' || $jenis === 'bicycle') {
            $icon = '🚲';
        } elseif ($jenis === 'van' || $jenis === 'minibus') {
            $icon = '🚐';
        }

        /* =================================================
           TIPE AREA
           ================================================= */
        $tipe_area_d = !empty($d['id_area']) ? 'reguler' : 'karyawan';
        $area_badge_class = 'area-badge' . ($tipe_area_d === 'karyawan' ? ' karyawan' : '');
        $area_icon = $tipe_area_d === 'karyawan' ? '👔' : '🅿️';

        /* =================================================
           DURASI PARKIR
           ================================================= */
        $masuk_timestamp = strtotime($d['waktu_masuk']);
        $sekarang = time();
        $selisih = max(0, $sekarang - $masuk_timestamp);
        $jam = floor($selisih / 3600);
        $menit = floor(($selisih % 3600) / 60);

        if ($jam > 0) {
            $durasi_text = $jam . ' jam ' . $menit . ' menit';
        } else {
            $durasi_text = $menit . ' menit';
        }

        /* =================================================
           ASAL TRANSAKSI
           ================================================= */
        if (!empty($d['id_booking'])) {
            $asal = '<span class="source-badge booking">📅 Booking #' . (int) $d['id_booking'] . '</span>';
        } else {
            $asal = '<span class="source-badge langsung">⚡ Langsung</span>';
        }
?>
<tr>

<td><?= $no_active++; ?></td>

<td>
<div class="vehicle-info">
<div class="vehicle-icon-active"><?= $icon; ?></div>
<div>
<strong><?= htmlspecialchars(ucfirst($d['jenis_kendaraan'])); ?></strong>
<?php if (!empty($d['merk'])) { ?>
<small><?= htmlspecialchars($d['merk']); ?></small>
<?php } ?>
</div>
</div>
</td>

<td>
<span class="plate-number"><?= htmlspecialchars(strtoupper($d['plat_nomor'])); ?></span>
</td>

<td><?= htmlspecialchars($d['pemilik'] ?: '-'); ?></td>

<td>
<span class="<?= $area_badge_class; ?>">
    <?= $area_icon; ?> <?= htmlspecialchars($d['nama_area'] ?: '-'); ?>
</span>
</td>

<td>
<div class="time-info">
<strong><?= date('H:i', $masuk_timestamp); ?></strong>
<small><?= date('d/m/Y', $masuk_timestamp); ?></small>
</div>
</td>

<td>
<span class="duration-badge" data-masuk="<?= htmlspecialchars($d['waktu_masuk']); ?>">
    ⏱️ <?= htmlspecialchars($durasi_text); ?>
</span>
</td>

<td><?= $asal; ?></td>

<td>
<!-- Keluar: lewat halaman scan QR -->
<a
    href="scan_qr.php?id_parkir=<?= (int) $d['id_parkir']; ?>"
    class="btn btn-danger"
>
    📷 Scan QR Keluar
</a>

<!-- Struk untuk kendaraan yang masih parkir -->
<a
    href="struk.php?id=<?= (int) $d['id_parkir']; ?>"
    class="btn btn-info"
    target="_blank"
    rel="noopener"
>
    🧾 struk
</a>

<!-- Pembayaran Cash / QRIS manual (cadangan bila QR tidak bisa discan) -->
<button
    type="button"
    class="btn"
    onclick="openBayarModal(
        <?= (int) $d['id_parkir']; ?>,
        '<?= htmlspecialchars(strtoupper($d['plat_nomor']), ENT_QUOTES); ?>',
        '<?= htmlspecialchars($d['waktu_masuk'], ENT_QUOTES); ?>',
        <?= (float) $d['tarif_per_jam']; ?>
    )"
>
    💳 Bayar Manual
</button>
</td>

</tr>

<?php
    }
}
?>

</tbody>
</table>
</div>
</div>

<hr>

<!-- =========================================================
     RIWAYAT KENDARAAN KELUAR
     ========================================================= -->
<h3>📋 Riwayat Kendaraan Keluar</h3>

<div class="table-wrapper">
<table>
<thead>
<tr>
    <th>No</th>
    <th>Plat</th>
    <th>Jenis</th>
    <th>Masuk</th>
    <th>Keluar</th>
    <th>Durasi</th>
    <th>Total Biaya</th>
    <th>Bayar</th>
    <th>Asal</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>

<?php
$no_keluar = 1;

$data_keluar = $conn->query("
    SELECT
        t.*,
        k.plat_nomor,
        k.jenis_kendaraan
    FROM tb_transaksi t
    INNER JOIN tb_kendaraan k
        ON k.id_kendaraan = t.id_kendaraan
    WHERE t.status = 'keluar'
    ORDER BY t.waktu_keluar DESC
");

if (!$data_keluar) {
    echo '<tr><td colspan="10">Gagal mengambil riwayat kendaraan.</td></tr>';

} elseif ($data_keluar->num_rows === 0) {
    echo '<tr><td colspan="10">Belum ada riwayat kendaraan keluar.</td></tr>';

} else {

    while ($dk = $data_keluar->fetch_assoc()) {
        $metode_dk = strtolower($dk['metode_pembayaran'] ?? '');
?>
<tr>

<td><?= $no_keluar++; ?></td>

<td>
<span class="plate-number"><?= htmlspecialchars(strtoupper($dk['plat_nomor'])); ?></span>
</td>

<td><?= htmlspecialchars(ucfirst($dk['jenis_kendaraan'])); ?></td>

<td><?= htmlspecialchars($dk['waktu_masuk']); ?></td>

<td><?= htmlspecialchars($dk['waktu_keluar']); ?></td>

<td><?= (int) $dk['durasi_jam']; ?> Jam</td>

<td>Rp <?= number_format((float) $dk['biaya_total'], 0, ',', '.'); ?></td>

<td>
<?php if ($metode_dk === 'qris') { ?>
<span class="pay-badge qris">📱 QRIS</span>
<?php } elseif ($metode_dk === 'cash') { ?>
<span class="pay-badge cash">💵 Cash</span>
<?php } else { ?>
<span class="pay-badge none">— Belum dicatat</span>
<?php } ?>
</td>

<td>
<?php if (!empty($dk['id_booking'])) { ?>
<span class="source-badge booking">📅 Booking #<?= (int) $dk['id_booking']; ?></span>
<?php } else { ?>
<span class="source-badge langsung">⚡ Langsung</span>
<?php } ?>
</td>

<td>
<a href="struk.php?id=<?= (int) $dk['id_parkir']; ?>" class="btn" target="_blank" rel="noopener">🧾 Struk</a>

<button
    type="button"
    class="btn btn-warning"
    onclick="openEditModal(<?= (int) $dk['id_parkir']; ?>, <?= (int) $dk['durasi_jam']; ?>)"
>
    ✏️ Edit Durasi
</button>

<button
    type="button"
    class="btn btn-info"
    onclick="openMetodeModal(<?= (int) $dk['id_parkir']; ?>, '<?= htmlspecialchars($metode_dk, ENT_QUOTES); ?>')"
>
    💳 Ubah Bayar
</button>

</td>
</tr>

<?php
    }
}
?>

</tbody>
</table>
</div>

<br>

<a href="../dashboard.php" class="btn btn-back">← Kembali Dashboard</a>

</div>
</main>

<!-- =========================================================
     MODAL PEMBAYARAN (CASH / QRIS)
     ========================================================= -->
<div id="modalBayar" class="modal">

<div class="modal-content">

<span class="close" onclick="closeBayarModal()">&times;</span>

<h3>💳 Pembayaran Parkir</h3>

<div class="bayar-info">
    <div><span>Plat Nomor</span><b id="bayar_plat">-</b></div>
    <div><span>Durasi</span><b id="bayar_durasi">-</b></div>
    <div><span>Tarif / Jam</span><b id="bayar_tarif">-</b></div>
    <div class="bayar-total"><span>Total Bayar</span><b id="bayar_total">Rp 0</b></div>
</div>

<small class="bayar-note">
    Nilai di atas adalah estimasi. Total akhir dihitung ulang oleh sistem saat dikonfirmasi.
</small>

<form method="POST" onsubmit="return confirm('Konfirmasi pembayaran dan keluarkan kendaraan?');">

<input type="hidden" name="id_parkir" id="bayar_id_parkir">

<div class="form-group">
<label>Metode Pembayaran</label>
<div class="metode-toggle">
    <label id="labelMetodeCash" class="selected">
        <input type="radio" name="metode_pembayaran" value="cash" checked onchange="toggleMetode()">
        💵 Cash
    </label>
    <label id="labelMetodeQris">
        <input type="radio" name="metode_pembayaran" value="qris" onchange="toggleMetode()">
        📱 QRIS
    </label>
</div>
</div>

<div id="boxQris" class="qris-box" style="display:none;">
    <img src="qris.png" alt="Kode QRIS" onerror="this.style.display='none';">
    <small>Minta pelanggan scan QRIS di atas, lalu konfirmasi setelah pembayaran berhasil masuk.</small>
</div>

<button type="submit" name="proses_keluar" class="btn-block">
    ✅ Konfirmasi &amp; Proses Keluar
</button>

</form>
</div>
</div>

<!-- =========================================================
     MODAL EDIT DURASI
     ========================================================= -->
<div id="modalEdit" class="modal">

<div class="modal-content">

<span class="close" onclick="closeEditModal()">&times;</span>

<h3>Edit Durasi Parkir</h3>

<form method="POST">

<input type="hidden" name="id_parkir" id="edit_id_parkir">

<div class="form-group">
<label>Durasi (Jam)</label>
<input type="number" name="durasi_jam" id="edit_durasi_jam" min="1" required>
</div>

<button type="submit" name="update_durasi">Simpan Perubahan</button>

</form>
</div>
</div>

<!-- =========================================================
     MODAL UBAH METODE PEMBAYARAN
     ========================================================= -->
<div id="modalMetode" class="modal">

<div class="modal-content">

<span class="close" onclick="closeMetodeModal()">&times;</span>

<h3>💳 Ubah Metode Pembayaran</h3>

<form method="POST">

<input type="hidden" name="id_parkir" id="metode_id_parkir">

<div class="form-group">
<label>Metode Pembayaran</label>
<div class="metode-toggle">
    <label id="labelUbahCash" class="selected">
        <input type="radio" name="metode_pembayaran" value="cash" checked onchange="toggleUbahMetode()">
        💵 Cash
    </label>
    <label id="labelUbahQris">
        <input type="radio" name="metode_pembayaran" value="qris" onchange="toggleUbahMetode()">
        📱 QRIS
    </label>
</div>
</div>

<button type="submit" name="update_metode">Simpan Perubahan</button>

</form>
</div>
</div>

<script>
/* =========================================================
   SIDEBAR
   ========================================================= */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
}

/* =========================================================
   TOGGLE TIPE AREA (PARKIR MASUK LANGSUNG)
   ========================================================= */
function toggleTipeArea() {
    const checked = document.querySelector('input[name="tipe_area"]:checked');
    const tipeKaryawan = checked && checked.value === 'karyawan';

    const groupReguler = document.getElementById('groupAreaReguler');
    const groupKaryawan = document.getElementById('groupAreaKaryawan');
    const selectReguler = document.getElementById('selectAreaReguler');
    const selectKaryawan = document.getElementById('selectAreaKaryawan');
    const labelReguler = document.getElementById('labelTipeReguler');
    const labelKaryawan = document.getElementById('labelTipeKaryawan');

    if (tipeKaryawan) {
        groupReguler.style.display = 'none';
        groupKaryawan.style.display = 'block';
        selectReguler.disabled = true;
        selectReguler.required = false;
        selectKaryawan.disabled = false;
        selectKaryawan.required = true;
        labelReguler.classList.remove('selected');
        labelKaryawan.classList.add('selected');
    } else {
        groupReguler.style.display = 'block';
        groupKaryawan.style.display = 'none';
        selectReguler.disabled = false;
        selectReguler.required = true;
        selectKaryawan.disabled = true;
        selectKaryawan.required = false;
        labelReguler.classList.add('selected');
        labelKaryawan.classList.remove('selected');
    }
}

document.addEventListener('DOMContentLoaded', toggleTipeArea);

/* =========================================================
   MODAL PEMBAYARAN (CASH / QRIS)
   ========================================================= */
function formatRupiah(angka) {
    return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
}

function openBayarModal(idParkir, plat, waktuMasuk, tarif) {
    const masuk   = new Date(String(waktuMasuk).replace(' ', 'T'));
    const selisih = Math.max(0, (Date.now() - masuk.getTime()) / 1000);
    const jam     = Math.max(1, Math.ceil(selisih / 3600));
    const total   = jam * (parseFloat(tarif) || 0);

    document.getElementById('bayar_id_parkir').value    = idParkir;
    document.getElementById('bayar_plat').textContent   = plat;
    document.getElementById('bayar_durasi').textContent = jam + ' Jam';
    document.getElementById('bayar_tarif').textContent  = formatRupiah(tarif);
    document.getElementById('bayar_total').textContent  = formatRupiah(total);

    document.querySelector('#modalBayar input[value="cash"]').checked = true;
    toggleMetode();

    document.getElementById('modalBayar').style.display = 'block';
}

function closeBayarModal() {
    document.getElementById('modalBayar').style.display = 'none';
}

function toggleMetode() {
    const checked = document.querySelector('#modalBayar input[name="metode_pembayaran"]:checked');
    const isQris  = checked && checked.value === 'qris';

    document.getElementById('labelMetodeCash').classList.toggle('selected', !isQris);
    document.getElementById('labelMetodeQris').classList.toggle('selected', isQris);
    document.getElementById('boxQris').style.display = isQris ? 'flex' : 'none';
}

/* =========================================================
   MODAL EDIT DURASI
   ========================================================= */
function openEditModal(idParkir, durasiJam) {
    document.getElementById('edit_id_parkir').value = idParkir;
    document.getElementById('edit_durasi_jam').value = durasiJam;
    document.getElementById('modalEdit').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('modalEdit').style.display = 'none';
}

/* =========================================================
   MODAL UBAH METODE PEMBAYARAN
   ========================================================= */
function openMetodeModal(idParkir, metode) {
    document.getElementById('metode_id_parkir').value = idParkir;

    const target = (metode === 'qris') ? 'qris' : 'cash';
    document.querySelector('#modalMetode input[value="' + target + '"]').checked = true;
    toggleUbahMetode();

    document.getElementById('modalMetode').style.display = 'block';
}

function closeMetodeModal() {
    document.getElementById('modalMetode').style.display = 'none';
}

function toggleUbahMetode() {
    const checked = document.querySelector('#modalMetode input[name="metode_pembayaran"]:checked');
    const isQris  = checked && checked.value === 'qris';

    document.getElementById('labelUbahCash').classList.toggle('selected', !isQris);
    document.getElementById('labelUbahQris').classList.toggle('selected', isQris);
}

/* =========================================================
   PEMBARUAN DURASI PARKIR AKTIF (LIVE, TANPA REFRESH)
   Durasi dihitung ulang di browser tiap 30 detik dari
   atribut data-masuk, jadi selalu sinkron tanpa refresh.
   ========================================================= */
function formatDurasiAktif(totalDetik) {
    totalDetik = Math.max(0, Math.floor(totalDetik));
    const jam = Math.floor(totalDetik / 3600);
    const menit = Math.floor((totalDetik % 3600) / 60);
    return jam > 0 ? (jam + ' jam ' + menit + ' menit') : (menit + ' menit');
}

function updateDurasiAktif() {
    document.querySelectorAll('.duration-badge[data-masuk]').forEach(function (badge) {
        const waktuMasuk = badge.getAttribute('data-masuk');
        const masuk = new Date(String(waktuMasuk).replace(' ', 'T'));
        if (isNaN(masuk.getTime())) {
            return;
        }
        const selisihDetik = (Date.now() - masuk.getTime()) / 1000;
        badge.textContent = '⏱️ ' + formatDurasiAktif(selisihDetik);
    });
}

document.addEventListener('DOMContentLoaded', updateDurasiAktif);
setInterval(updateDurasiAktif, 30000);

/* =========================================================
   TUTUP SEMUA MODAL (klik di luar / tombol Esc)
   Tambahkan .modal baru di HTML dan otomatis tertutup di sini.
   ========================================================= */
window.addEventListener('click', function(event) {
    document.querySelectorAll('.modal').forEach(function(modal) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.style.display = 'none';
        });
    }
});

/* =========================================================
   TUTUP SIDEBAR DI HP
   ========================================================= */
document.querySelectorAll('.sidebar .nav-links a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
        }
    });
});
</script>
</body>
</html>