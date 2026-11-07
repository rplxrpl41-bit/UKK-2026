<?php
session_start();

include 'config/koneksi.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email dan password wajib diisi!";
    } else {

        $sql = "SELECT id, name, email, password, role
                FROM t_users
                WHERE email = ?
                LIMIT 1";

        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {

            // Hanya admin dan guru yang boleh login
            if (!in_array($user['role'], ['admin', 'guru'], true)) {
                $error = "Role pengguna tidak diizinkan!";
            } else {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit;
            }

        } else {
            $error = "Email atau password salah!";
        }

        mysqli_stmt_close($stmt);
    }
}
?>