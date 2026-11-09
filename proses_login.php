<?php

session_start();

include 'config/koneksi.php';

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = mysqli_prepare($koneksi, "SELECT * FROM t_users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($hasil) > 0) {

        $user = mysqli_fetch_assoc($hasil);

        if (password_verify($password, $user['password'])) {

            // Simpan data user ke session
            $_SESSION['login']=true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['user_id'] = $user['id']; // dipakai halaman lama (menu1-4, cek_akses.php)
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Masuk ke dashboard
            header("Location: dashboard.php");
            exit;

        } else {

            $_SESSION['pesan_error'] = "Password salah!";
            header("Location: login.php");
            exit;

        }

    } else {

        $_SESSION['pesan_error'] = "Email tidak ditemukan!";
        header("Location: login.php");
        exit;

    }

} else {

    header("Location: login.php");
    exit;

}

?>