
<?php

session_start();

require_once 'db.php';

/*
|--------------------------------------------------------------------------
| Jika sudah login sebagai karyawan
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['id_user']) &&
    ($_SESSION['role'] ?? '') === 'karyawan'
) {
    header("Location: dashboard_karyawan.php");
    exit;
}

$pesan = '';
$tipe_pesan = '';

/*
|--------------------------------------------------------------------------
| PROSES LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $pesan = 'Username dan password wajib diisi.';
        $tipe_pesan = 'danger';

    } else {

        $stmt = $conn->prepare("
            SELECT
                id_user,
                nama_lengkap,
                username,
                password,
                role,
                status_aktif
            FROM tb_user
            WHERE username = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $user = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | CEK ROLE
                |--------------------------------------------------------------------------
                */

                if ($user['role'] !== 'karyawan') {

                    $pesan = 'Akun ini bukan akun karyawan.';
                    $tipe_pesan = 'danger';

                /*
                |--------------------------------------------------------------------------
                | CEK STATUS
                |--------------------------------------------------------------------------
                */

                } elseif ((int)$user['status_aktif'] !== 1) {

                    $pesan = 'Akun karyawan Anda sedang tidak aktif.';
                    $tipe_pesan = 'danger';

                /*
                |--------------------------------------------------------------------------
                | CEK PASSWORD
                |--------------------------------------------------------------------------
                */

                } elseif (
                    password_verify($password, $user['password']) ||
                    $password === $user['password']
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    $_SESSION['id_user'] = $user['id_user'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'karyawan';

                    header("Location: dashboard_karyawan.php");
                    exit;

                } else {

                    $pesan = 'Username atau password salah.';
                    $tipe_pesan = 'danger';
                }

            } else {

                $pesan = 'Username atau password salah.';
                $tipe_pesan = 'danger';
            }

            $stmt->close();

        } else {

            $pesan = 'Terjadi kesalahan pada sistem database.';
            $tipe_pesan = 'danger';
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

    <title>Login Karyawan | E-Parkir</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <!-- Google Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background:
                linear-gradient(
                    135deg,
                    rgba(16, 29, 51, 0.94),
                    rgba(30, 60, 114, 0.88)
                ),
                url('rsjbg.jpeg');

            background-size: cover;
            background-position: center;
            background-attachment: fixed;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 25px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            padding: 40px;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.30);
        }

        .logo {
            width: 75px;
            height: 75px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: linear-gradient(
                135deg,
                #f7b733,
                #e0a52a
            );

            color: #101d33;

            font-size: 32px;

            box-shadow:
                0 10px 25px rgba(247, 183, 51, 0.30);
        }

        .login-title {
            color: #101d33;
            font-weight: 800;
        }

        .login-subtitle {
            color: #6c757d;
            font-size: 14px;
        }

        .form-label {
            font-weight: 600;
            color: #343a40;
        }

        .form-control {
            min-height: 52px;
            border-radius: 12px;
            padding-left: 45px;
        }

        .form-control:focus {
            border-color: #1e3c72;
            box-shadow:
                0 0 0 0.2rem rgba(30, 60, 114, 0.15);
        }

        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #1e3c72;
            z-index: 5;
        }

        .btn-login {
            width: 100%;
            min-height: 52px;

            border: none;
            border-radius: 12px;

            background: linear-gradient(
                135deg,
                #1e3c72,
                #2a5298
            );

            color: white;
            font-weight: 700;

            transition: 0.25s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);

            box-shadow:
                0 10px 20px rgba(30, 60, 114, 0.25);

            color: white;
        }

        .back-link {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            color: #f0a900;
        }

        .employee-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            padding: 7px 14px;

            border-radius: 30px;

            background: #fff3cd;
            color: #856404;

            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 576px) {

            body {
                padding: 15px;
            }

            .login-card {
                padding: 30px 22px;
                border-radius: 20px;
            }

        }

    </style>

</head>

<body>

<div class="login-wrapper">

    <div class="login-card">

        <!-- LOGO -->

        <div class="text-center">

            <div class="logo">

                <i class="fa-solid fa-id-badge"></i>

            </div>

            <div class="employee-badge mb-3">

                <i class="fa-solid fa-hospital"></i>

                Karyawan Rumah Sakit

            </div>

            <h2 class="login-title mb-2">

                Login Karyawan

            </h2>

            <p class="login-subtitle mb-4">

                Masuk ke sistem E-Parkir untuk mengelola
                kendaraan dan booking parkir Anda.

            </p>

        </div>


        <!-- PESAN -->

        <?php if ($pesan !== ''): ?>

            <div
                class="alert alert-<?= htmlspecialchars($tipe_pesan) ?> alert-dismissible fade show"
                role="alert"
            >

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?= htmlspecialchars($pesan) ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            method="POST"
            action=""
        >

            <!-- USERNAME -->

            <div class="mb-3">

                <label
                    for="username"
                    class="form-label"
                >
                    Username
                </label>

                <div class="input-group-custom">

                    <i class="fa-solid fa-user input-icon"></i>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="mb-4">

                <label
                    for="password"
                    class="form-label"
                >
                    Password
                </label>

                <div class="input-group-custom">

                    <i class="fa-solid fa-lock input-icon"></i>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                </div>

            </div>


            <!-- LOGIN -->

            <button
                type="submit"
                class="btn btn-login"
            >

                <i class="fa-solid fa-right-to-bracket me-2"></i>

                Masuk sebagai Karyawan

            </button>

        </form>


        <!-- BACK -->

        <div class="text-center mt-4">

            <a
                href="index.php"
                class="back-link"
            >

                <i class="fa-solid fa-arrow-left me-1"></i>

                Kembali ke Beranda

            </a>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>

