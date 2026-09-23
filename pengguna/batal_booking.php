<?php
session_start();
require '../db.php'; // sesuaikan: file koneksi yang menghasilkan $conn (mysqli)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ===== Pengaturan =====
$halaman_kembali = 'riwayat.php'; // halaman tujuan setelah proses selesai

// ===== Cek login =====
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
$id_user = (int) $_SESSION['id_user'];

// ===== Ambil id booking (POST atau GET, nama field id_booking atau id) =====
$id_booking = (int) ($_POST['id_booking'] ?? $_POST['id'] ?? $_GET['id_booking'] ?? $_GET['id'] ?? 0);

if ($id_booking <= 0) {
    $_SESSION['pesan'] = 'Permintaan tidak valid: id booking tidak terkirim.';
    header("Location: $halaman_kembali");
    exit;
}

$conn->begin_transaction();

try {
    // Ambil data booking + kunci barisnya supaya tidak bentrok dengan check-in petugas
    $stmt = $conn->prepare(
        "SELECT status FROM tb_booking WHERE id_booking = ? AND id_user = ? FOR UPDATE"
    );
    $stmt->bind_param('ii', $id_booking, $id_user);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        throw new Exception('Booking tidak ditemukan.');
    }

    // Hanya booking yang belum check-in yang boleh dibatalkan
    if ($booking['status'] !== 'booking') {
        $keterangan = [
            'aktif'   => 'Booking sudah check-in (kendaraan sedang parkir), tidak bisa dibatalkan.',
            'selesai' => 'Booking sudah selesai, tidak bisa dibatalkan.',
            'batal'   => 'Booking ini sudah dibatalkan sebelumnya.',
        ];
        throw new Exception($keterangan[$booking['status']] ?? 'Status booking tidak valid.');
    }

    // Batalkan
    $stmt = $conn->prepare(
        "UPDATE tb_booking SET status = 'batal'
         WHERE id_booking = ? AND id_user = ? AND status = 'booking'"
    );
    $stmt->bind_param('ii', $id_booking, $id_user);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception('Gagal membatalkan booking.');
    }
    $stmt->close();

    $conn->commit();
    $_SESSION['pesan'] = 'Booking berhasil dibatalkan.';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['pesan'] = $e->getMessage();
}

header("Location: $halaman_kembali");
exit;