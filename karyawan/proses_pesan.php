
<?php

session_start();

require_once "../db.php";

/*
|--------------------------------------------------------------------------
| ERROR REPORTING
|--------------------------------------------------------------------------
| Aktifkan saat development agar error terlihat.
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['id_user'])) {
    header("Location: ../login_karyawan.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];


/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

if (($_SESSION['role'] ?? '') !== 'karyawan') {
    header("Location: ../dashboard.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| HANYA TERIMA POST DARI FORM BOOKING
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['booking'])) {
    header("Location: pesan_tempat.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

$id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);
$id_area_karyawan = (int) ($_POST['id_area_karyawan'] ?? 0);

$tanggal = trim($_POST['tanggal'] ?? '');
$jam_masuk = trim($_POST['jam_masuk'] ?? '');
$estimasi_jam = (int) ($_POST['estimasi_jam'] ?? 0);


/*
|--------------------------------------------------------------------------
| VALIDASI DASAR
|--------------------------------------------------------------------------
*/

$errors = [];


/*
| Validasi kendaraan
*/

if ($id_kendaraan <= 0) {
    $errors[] = "Kendaraan wajib dipilih.";
}


/*
| Validasi area
*/

if ($id_area_karyawan <= 0) {
    $errors[] = "Area parkir wajib dipilih.";
}


/*
| Validasi tanggal
*/

$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal);

if (
    !$tanggal_obj ||
    $tanggal_obj->format('Y-m-d') !== $tanggal
) {
    $errors[] = "Format tanggal tidak valid.";
} elseif ($tanggal < date('Y-m-d')) {
    $errors[] = "Tanggal booking tidak boleh sebelum hari ini.";
}


/*
| Validasi jam
*/

if (
    !preg_match(
        '/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/',
        $jam_masuk
    )
) {
    $errors[] = "Format jam masuk tidak valid.";
}


/*
| Validasi estimasi
*/

if ($estimasi_jam < 1 || $estimasi_jam > 24) {
    $errors[] = "Estimasi jam parkir harus antara 1 - 24 jam.";
}


/*
|--------------------------------------------------------------------------
| JIKA VALIDASI GAGAL
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {

    $_SESSION['pesan_error'] = implode(" ", $errors);

    header("Location: pesan_tempat.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFIKASI KENDARAAN MILIK KARYAWAN
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id_kendaraan
    FROM tb_kendaraan
    WHERE id_kendaraan = ?
      AND id_user = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $id_kendaraan,
    $id_user
);

$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {

    $stmt->close();

    $_SESSION['pesan_error'] =
        "Kendaraan tidak valid atau bukan milik Anda.";

    header("Location: pesan_tempat.php");
    exit;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| MULAI TRANSAKSI DATABASE
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | KUNCI AREA PARKIR KARYAWAN
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id_area_karyawan,
            nama_area,
            kapasitas,
            terisi,
            status
        FROM tb_area_parkir_karyawan
        WHERE id_area_karyawan = ?
        FOR UPDATE
    ");

    $stmt->bind_param(
        "i",
        $id_area_karyawan
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $area = $result->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | AREA TIDAK DITEMUKAN
    |--------------------------------------------------------------------------
    */

    if (!$area) {
        throw new Exception(
            "Area parkir karyawan tidak ditemukan."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CEK STATUS AREA
    |--------------------------------------------------------------------------
    */

    if ($area['status'] === 'nonaktif') {
        throw new Exception(
            "Area parkir tersebut sedang tidak aktif."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CEK KAPASITAS
    |--------------------------------------------------------------------------
    */

    if ((int) $area['terisi'] >= (int) $area['kapasitas']) {
        throw new Exception(
            "Mohon maaf, area parkir tersebut sedang penuh."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CEK BOOKING YANG SAMA
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id_booking
        FROM tb_booking
        WHERE id_user = ?
          AND id_kendaraan = ?
          AND tanggal = ?
          AND jam_masuk = ?
          AND status IN ('booking', 'aktif')
        LIMIT 1
    ");

    $stmt->bind_param(
        "iiss",
        $id_user,
        $id_kendaraan,
        $tanggal,
        $jam_masuk
    );

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt->close();

        throw new Exception(
            "Kendaraan tersebut sudah memiliki booking pada tanggal dan jam tersebut."
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | INSERT BOOKING KARYAWAN
    |--------------------------------------------------------------------------
    |
    | id_area = NULL
    | id_area_karyawan = area pilihan karyawan
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO tb_booking
        (
            id_user,
            id_kendaraan,
            id_area,
            id_area_karyawan,
            tanggal,
            jam_masuk,
            estimasi_jam,
            status
        )
        VALUES
        (
            ?,
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            'booking'
        )
    ");

    $stmt->bind_param(
        "iiissi",
        $id_user,
        $id_kendaraan,
        $id_area_karyawan,
        $tanggal,
        $jam_masuk,
        $estimasi_jam
    );

    $stmt->execute();

    $id_booking = $conn->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | UPDATE JUMLAH TERISI
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE tb_area_parkir_karyawan
        SET
            terisi = terisi + 1,
            status = CASE
                WHEN terisi + 1 >= kapasitas THEN 'penuh'
                ELSE 'tersedia'
            END
        WHERE id_area_karyawan = ?
    ");

    $stmt->bind_param(
        "i",
        $id_area_karyawan
    );

    $stmt->execute();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | PESAN SUKSES
    |--------------------------------------------------------------------------
    */

    $_SESSION['pesan_sukses'] =
        "Booking berhasil dibuat! Nomor booking #" .
        $id_booking .
        ". Silakan datang sesuai jadwal yang Anda pilih.";


    /*
    |--------------------------------------------------------------------------
    | REDIRECT RIWAYAT
    |--------------------------------------------------------------------------
    */

    header("Location: riwayat.php");
    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    /*
    |--------------------------------------------------------------------------
    | PESAN ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['pesan_error'] =
        "Booking gagal: " . $e->getMessage();


    /*
    |--------------------------------------------------------------------------
    | KEMBALI KE FORM
    |--------------------------------------------------------------------------
    */

    header("Location: pesan_tempat.php");
    exit;
}
