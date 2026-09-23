<?php

session_start();

require_once __DIR__ . '/../db.php';

/* ============================================================
   CEK LOGIN
   ============================================================ */

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_pengguna.php");
    exit;
}

/* ============================================================
   CEK ROLE
   ============================================================ */

if (($_SESSION['role'] ?? '') !== 'pengguna') {
    die("Akses ditolak.");
}

/* ============================================================
   AMBIL ID KENDARAAN
   ============================================================ */
/*
   kendaraan_saya.php mengirim:
   hapus_kendaraan.php?id=ID
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID kendaraan tidak valid.");
}

$id_kendaraan = (int) $_GET['id'];
$id_user = (int) $_SESSION['id_user'];

/* ============================================================
   CEK KENDARAAN MILIK USER
   ============================================================ */

$stmt = $conn->prepare("
    SELECT
        id_kendaraan,
        plat_nomor,
        jenis_kendaraan,
        merk,
        warna,
        pemilik
    FROM tb_kendaraan
    WHERE id_kendaraan = ?
      AND id_user = ?
      AND is_dihapus = 0
    LIMIT 1
");

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}

$stmt->bind_param("ii", $id_kendaraan, $id_user);
$stmt->execute();

$result = $stmt->get_result();
$kendaraan = $result->fetch_assoc();

$stmt->close();

/* ============================================================
   JIKA TIDAK DITEMUKAN
   ============================================================ */

if (!$kendaraan) {
    die("Kendaraan tidak ditemukan atau bukan milik Anda.");
}

/* ============================================================
   CEK TRANSAKSI AKTIF
   ============================================================ */

$stmt = $conn->prepare("
    SELECT id_parkir
    FROM tb_transaksi
    WHERE id_kendaraan = ?
      AND status = 'masuk'
    LIMIT 1
");

if (!$stmt) {
    die("Query transaksi gagal: " . $conn->error);
}

$stmt->bind_param("i", $id_kendaraan);
$stmt->execute();

$result = $stmt->get_result();
$transaksi = $result->fetch_assoc();

$stmt->close();

if ($transaksi) {
    die(
        "Kendaraan dengan plat " .
        htmlspecialchars($kendaraan['plat_nomor']) .
        " masih sedang parkir dan tidak dapat dihapus."
    );
}

/* ============================================================
   HAPUS KENDARAAN (SOFT DELETE)
   ============================================================ */

$stmt = $conn->prepare("
    UPDATE tb_kendaraan
    SET is_dihapus = 1
    WHERE id_kendaraan = ?
      AND id_user = ?
      AND is_dihapus = 0
    LIMIT 1
");

if (!$stmt) {
    die("Query UPDATE gagal: " . $conn->error);
}

$stmt->bind_param("ii", $id_kendaraan, $id_user);

try {

    $stmt->execute();

    if ($stmt->affected_rows > 0) {

        $plat = htmlspecialchars($kendaraan['plat_nomor']);

        $stmt->close();

        echo "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Kendaraan Dihapus</title>

            <style>
                body {
                    margin: 0;
                    font-family: Arial, sans-serif;
                    background: #f4f6f9;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                }

                .box {
                    background: white;
                    padding: 35px;
                    border-radius: 12px;
                    text-align: center;
                    box-shadow: 0 5px 20px rgba(0,0,0,.1);
                    width: 90%;
                    max-width: 450px;
                }

                .icon {
                    font-size: 55px;
                    margin-bottom: 15px;
                }

                h2 {
                    color: #28a745;
                    margin-bottom: 10px;
                }

                p {
                    color: #555;
                }

                a {
                    display: inline-block;
                    margin-top: 20px;
                    padding: 11px 20px;
                    background: #007bff;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                }

                a:hover {
                    background: #0056b3;
                }
            </style>
        </head>

        <body>

            <div class='box'>

                <div class='icon'>✅</div>

                <h2>Kendaraan Berhasil Dihapus</h2>

                <p>
                    Kendaraan dengan plat
                    <strong>{$plat}</strong>
                    berhasil dihapus dari daftar kendaraan Anda.
                </p>

                <a href='kendaraan_saya.php'>
                    Kembali ke Kendaraan Saya
                </a>

            </div>

        </body>
        </html>
        ";

        exit;

    } else {

        $stmt->close();

        die("Kendaraan gagal dihapus (mungkin sudah dihapus sebelumnya).");
    }

} catch (mysqli_sql_exception $e) {

    $stmt->close();

    die("Terjadi kesalahan saat menghapus kendaraan. Silakan coba lagi.");
}

?>