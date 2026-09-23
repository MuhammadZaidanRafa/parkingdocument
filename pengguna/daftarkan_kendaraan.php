<?php

session_start();

require '../db.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login_pengguna.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

$pesan = "";
$tipe_pesan = "";

/* =========================================================
   TAMBAH KENDARAAN
   ========================================================= */
if (isset($_POST['tambah'])) {

    $plat_nomor = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $pemilik = trim($_POST['pemilik'] ?? '');

    $warna = ($warna === '') ? null : $warna;
    $pemilik = ($pemilik === '') ? null : $pemilik;

    if ($plat_nomor === '' || $jenis_kendaraan === '') {

        $pesan = "Plat nomor dan jenis kendaraan wajib diisi.";
        $tipe_pesan = "danger";

    } else {

        /*
         * Cek apakah plat nomor sudah digunakan oleh kendaraan aktif.
         */
        $cek = $conn->prepare("
            SELECT id_kendaraan
            FROM tb_kendaraan
            WHERE plat_nomor = ?
              AND is_dihapus = 0
            LIMIT 1
        ");

        if ($cek === false) {
            die("Query error: " . $conn->error);
        }

        $cek->bind_param("s", $plat_nomor);
        $cek->execute();
        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = "Plat nomor tersebut sudah terdaftar.";
            $tipe_pesan = "danger";

            $cek->close();

        } else {

            $cek->close();

            $stmt = $conn->prepare("
                INSERT INTO tb_kendaraan
                (id_user, plat_nomor, jenis_kendaraan, warna, pemilik, is_dihapus)
                VALUES (?, ?, ?, ?, ?, 0)
            ");

            if ($stmt === false) {
                die("Query error: " . $conn->error);
            }

            $stmt->bind_param(
                "issss",
                $id_user,
                $plat_nomor,
                $jenis_kendaraan,
                $warna,
                $pemilik
            );

            if ($stmt->execute()) {

                $pesan = "Kendaraan berhasil ditambahkan!";
                $tipe_pesan = "success";

            } else {

                $pesan = "Gagal menambahkan kendaraan.";
                $tipe_pesan = "danger";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   EDIT KENDARAAN
   ========================================================= */
if (isset($_POST['edit'])) {

    $id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);

    $plat_nomor = strtoupper(trim($_POST['plat_nomor'] ?? ''));
    $jenis_kendaraan = trim($_POST['jenis_kendaraan'] ?? '');
    $warna = trim($_POST['warna'] ?? '');
    $pemilik = trim($_POST['pemilik'] ?? '');

    $warna = ($warna === '') ? null : $warna;
    $pemilik = ($pemilik === '') ? null : $pemilik;

    if ($id_kendaraan <= 0) {

        $pesan = "ID kendaraan tidak valid.";
        $tipe_pesan = "danger";

    } elseif ($plat_nomor === '' || $jenis_kendaraan === '') {

        $pesan = "Plat nomor dan jenis kendaraan wajib diisi.";
        $tipe_pesan = "danger";

    } else {

        /*
         * Cek plat nomor agar tidak bentrok dengan kendaraan aktif lain.
         */
        $cek = $conn->prepare("
            SELECT id_kendaraan
            FROM tb_kendaraan
            WHERE plat_nomor = ?
              AND id_kendaraan <> ?
              AND is_dihapus = 0
            LIMIT 1
        ");

        if ($cek === false) {
            die("Query error: " . $conn->error);
        }

        $cek->bind_param(
            "si",
            $plat_nomor,
            $id_kendaraan
        );

        $cek->execute();
        $hasil_cek = $cek->get_result();

        if ($hasil_cek->num_rows > 0) {

            $pesan = "Plat nomor tersebut sudah digunakan kendaraan lain.";
            $tipe_pesan = "danger";

            $cek->close();

        } else {

            $cek->close();

            /*
             * Hanya bisa mengedit kendaraan aktif milik user.
             */
            $stmt = $conn->prepare("
                UPDATE tb_kendaraan
                SET
                    plat_nomor = ?,
                    jenis_kendaraan = ?,
                    warna = ?,
                    pemilik = ?
                WHERE id_kendaraan = ?
                  AND id_user = ?
                  AND is_dihapus = 0
            ");

            if ($stmt === false) {
                die("Query error: " . $conn->error);
            }

            $stmt->bind_param(
                "ssssii",
                $plat_nomor,
                $jenis_kendaraan,
                $warna,
                $pemilik,
                $id_kendaraan,
                $id_user
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {

                    $pesan = "Kendaraan berhasil diperbarui!";
                    $tipe_pesan = "success";

                } else {

                    $pesan = "Tidak ada perubahan atau kendaraan tidak ditemukan.";
                    $tipe_pesan = "danger";
                }

            } else {

                $pesan = "Gagal memperbarui kendaraan.";
                $tipe_pesan = "danger";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   HAPUS KENDARAAN - SOFT DELETE
   ========================================================= */
if (isset($_POST['hapus'])) {

    $id_kendaraan = (int) ($_POST['id_kendaraan'] ?? 0);

    if ($id_kendaraan <= 0) {

        $pesan = "ID kendaraan tidak valid.";
        $tipe_pesan = "danger";

    } else {

        /*
         * JANGAN menggunakan DELETE.
         *
         * Karena tb_booking memiliki foreign key:
         *
         * tb_booking.id_kendaraan
         *        ↓
         * tb_kendaraan.id_kendaraan
         *
         * Kita hanya menandai kendaraan sebagai dihapus.
         */
        $stmt = $conn->prepare("
            UPDATE tb_kendaraan
            SET is_dihapus = 1
            WHERE id_kendaraan = ?
              AND id_user = ?
              AND is_dihapus = 0
        ");

        if ($stmt === false) {
            die("Query error: " . $conn->error);
        }

        $stmt->bind_param(
            "ii",
            $id_kendaraan,
            $id_user
        );

        if ($stmt->execute()) {

            if ($stmt->affected_rows > 0) {

                $pesan = "Kendaraan berhasil dihapus.";
                $tipe_pesan = "success";

            } else {

                $pesan = "Kendaraan tidak ditemukan atau sudah dihapus.";
                $tipe_pesan = "danger";
            }

        } else {

            $pesan = "Kendaraan gagal dihapus.";
            $tipe_pesan = "danger";
        }

        $stmt->close();
    }
}


/* =========================================================
   AMBIL DAFTAR KENDARAAN AKTIF MILIK USER
   ========================================================= */
$stmt = $conn->prepare("
    SELECT
        id_kendaraan,
        plat_nomor,
        jenis_kendaraan,
        merk,
        warna,
        pemilik
    FROM tb_kendaraan
    WHERE id_user = ?
      AND is_dihapus = 0
    ORDER BY id_kendaraan DESC
");

if ($stmt === false) {
    die("Query error: " . $conn->error);
}

$stmt->bind_param("i", $id_user);
$stmt->execute();

$daftar_kendaraan = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Kendaraan Saya</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #e3f2fd, #f4f6f9);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 30px 20px;
}

.container {
    width: 100%;
    max-width: 650px;
    background: #fff;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.top-bar a {
    color: #0d6efd;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.top-bar a:hover {
    text-decoration: underline;
}

h2 {
    color: #0d6efd;
    font-size: 26px;
}

h3.section-title {
    margin: 25px 0 12px;
    color: #333;
    font-size: 16px;
}

label {
    display: block;
    margin-top: 12px;
    margin-bottom: 6px;
    font-weight: 600;
    color: #444;
    font-size: 14px;
}

input,
select {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    transition: .3s;
    outline: none;
}

input:focus,
select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.2);
}

.btn {
    margin-top: 18px;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: #0d6efd;
    color: #fff;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: .3s;
}

.btn:hover {
    background: #0b5ed7;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 14px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
}

.alert-danger {
    background: #f8d7da;
    color: #842029;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}

th,
td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

th {
    color: #777;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.aksi {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 7px 12px;
    font-size: 13px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: 600;
}

.btn-edit {
    background: #e7f1ff;
    color: #0d6efd;
}

.btn-edit:hover {
    background: #d0e3ff;
}

.btn-hapus {
    background: #fde8e8;
    color: #dc3545;
}

.btn-hapus:hover {
    background: #fbd0d0;
}

.empty-state {
    text-align: center;
    color: #888;
    padding: 25px 0;
    font-size: 14px;
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-box {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    width: 100%;
    max-width: 400px;
}

.modal-box h3 {
    color: #0d6efd;
    margin-bottom: 5px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.modal-actions .btn {
    margin-top: 0;
    flex: 1;
}

.btn-batal {
    background: #e9ecef;
    color: #444;
}

.btn-batal:hover {
    background: #dee2e6;
}

@media (max-width: 600px) {

    body {
        padding: 15px;
    }

    .container {
        padding: 20px;
    }

    .top-bar {
        align-items: flex-start;
        flex-direction: column;
    }

    h2 {
        font-size: 23px;
    }

    table {
        min-width: 600px;
    }

}

</style>

</head>

<body>

<div class="container">


<div class="top-bar">

    <h2>Kendaraan Saya</h2>

    <a href="../dashboard.php">
        ← Kembali ke Dashboard
    </a>

</div>


<?php if (!empty($pesan)): ?>

    <div class="alert alert-<?= htmlspecialchars($tipe_pesan); ?>">
        <?= htmlspecialchars($pesan); ?>
    </div>

<?php endif; ?>


<!-- FORM TAMBAH KENDARAAN -->

<h3 class="section-title">
    Tambah Kendaraan Baru
</h3>

<form method="POST" action="">

    <label>Plat Nomor</label>

    <input
        type="text"
        name="plat_nomor"
        placeholder="Contoh: AB 1234 CD"
        maxlength="15"
        required
    >


    <label>Jenis Kendaraan</label>

    <select name="jenis_kendaraan" required>

        <option value="">
            -- Pilih Jenis --
        </option>

        <option value="Motor">
            Motor
        </option>

        <option value="Mobil">
            Mobil
        </option>

    </select>


    <label>
        Warna
        <span style="font-weight:400; color:#999;">
            (opsional)
        </span>
    </label>

    <input
        type="text"
        name="warna"
        placeholder="Contoh: Hitam"
        maxlength="20"
    >


    <label>
        Nama Pemilik
        <span style="font-weight:400; color:#999;">
            (opsional, jika berbeda dari akun ini)
        </span>
    </label>

    <input
        type="text"
        name="pemilik"
        placeholder="Contoh: Budi Santoso"
        maxlength="100"
    >


    <button
        type="submit"
        name="tambah"
        class="btn"
    >
        + Tambah Kendaraan
    </button>

</form>


<!-- DAFTAR KENDARAAN -->

<h3 class="section-title">
    Kendaraan Terdaftar
</h3>


<?php if (empty($daftar_kendaraan)): ?>

    <p class="empty-state">
        Anda belum memiliki kendaraan terdaftar.
    </p>

<?php else: ?>

    <div style="overflow-x:auto;">

        <table>

            <thead>

                <tr>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Warna</th>
                    <th>Pemilik</th>
                    <th>Aksi</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($daftar_kendaraan as $k): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($k['plat_nomor']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($k['jenis_kendaraan']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($k['warna'] ?? '-'); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($k['pemilik'] ?? '-'); ?>
                        </td>

                        <td>

                            <div class="aksi">

                                <button
                                    type="button"
                                    class="btn-sm btn-edit"
                                    onclick='openEditModal(
                                        <?= (int) $k["id_kendaraan"]; ?>,
                                        <?= json_encode($k["plat_nomor"]); ?>,
                                        <?= json_encode($k["jenis_kendaraan"]); ?>,
                                        <?= json_encode($k["warna"] ?? ""); ?>,
                                        <?= json_encode($k["pemilik"] ?? ""); ?>
                                    )'
                                >
                                    Edit
                                </button>


                                <form
                                    method="POST"
                                    action=""
                                    style="display:inline;"
                                    onsubmit="return confirm('Yakin ingin menghapus kendaraan ini? Kendaraan akan disembunyikan dari daftar tetapi riwayat booking tetap aman.');"
                                >

                                    <input
                                        type="hidden"
                                        name="id_kendaraan"
                                        value="<?= (int) $k['id_kendaraan']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="hapus"
                                        class="btn-sm btn-hapus"
                                    >
                                        Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

</div>

<!-- MODAL EDIT -->

<div id="editModal" class="modal-overlay">

<div class="modal-box">

    <h3>
        Edit Kendaraan
    </h3>

    <form method="POST" action="">

        <input
            type="hidden"
            name="id_kendaraan"
            id="edit_id_kendaraan"
        >


        <label>Plat Nomor</label>

        <input
            type="text"
            name="plat_nomor"
            id="edit_plat_nomor"
            maxlength="15"
            required
        >


        <label>Jenis Kendaraan</label>

        <select
            name="jenis_kendaraan"
            id="edit_jenis_kendaraan"
            required
        >

            <option value="Motor">
                Motor
            </option>

            <option value="Mobil">
                Mobil
            </option>

        </select>


        <label>
            Warna
            <span style="font-weight:400; color:#999;">
                (opsional)
            </span>
        </label>

        <input
            type="text"
            name="warna"
            id="edit_warna"
            maxlength="20"
        >


        <label>
            Nama Pemilik
            <span style="font-weight:400; color:#999;">
                (opsional)
            </span>
        </label>

        <input
            type="text"
            name="pemilik"
            id="edit_pemilik"
            maxlength="100"
        >


        <div class="modal-actions">

            <button
                type="submit"
                name="edit"
                class="btn"
            >
                Simpan
            </button>

            <button
                type="button"
                class="btn btn-batal"
                onclick="closeEditModal()"
            >
                Batal
            </button>

        </div>

    </form>

</div>

</div>

<script>

function openEditModal(id, plat, jenis, warna, pemilik) {

    document.getElementById('edit_id_kendaraan').value = id;

    document.getElementById('edit_plat_nomor').value = plat;

    document.getElementById('edit_jenis_kendaraan').value = jenis;

    document.getElementById('edit_warna').value = warna;

    document.getElementById('edit_pemilik').value = pemilik;

    document.getElementById('editModal').style.display = 'flex';
}


function closeEditModal() {

    document.getElementById('editModal').style.display = 'none';

}


document.getElementById('editModal').addEventListener('click', function(event) {

    if (event.target === this) {
        closeEditModal();
    }

});


document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        closeEditModal();
    }

});

</script>

</body>

</html>
