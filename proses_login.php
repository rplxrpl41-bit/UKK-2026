
<?php

// =====================================
// MULAI SESSION
// =====================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================
// HUBUNGKAN DATABASE
// =====================================

require_once 'config/koneksi.php';

// =====================================
// FUNGSI REDIRECT DENGAN PESAN
// =====================================

function gagal_login($pesan)
{
    $_SESSION['pesan_error'] = $pesan;

    header('Location: login.php');
    exit;
}

// =====================================
// CEK METODE REQUEST
// =====================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// =====================================
// AMBIL INPUT LOGIN
// =====================================

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!is_string($email) || !is_string($password)) {
    gagal_login('Email atau password tidak valid.');
}

$email = strtolower($email);

// =====================================
// VALIDASI INPUT
// =====================================

if ($email === '' || $password === '') {
    gagal_login('Email dan password wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    gagal_login('Format email tidak valid.');
}

// =====================================
// CEK KONEKSI DATABASE
// =====================================

if (!isset($koneksi) || !$koneksi) {
    gagal_login('Koneksi database gagal. Silakan hubungi administrator.');
}

// =====================================
// CARI AKUN BERDASARKAN EMAIL
// =====================================

$sql = "
    SELECT
        id,
        name,
        email,
        password,
        role
    FROM t_users
    WHERE email = ?
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    gagal_login('Terjadi kesalahan pada sistem login.');
}

mysqli_stmt_bind_param($stmt, "s", $email);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    gagal_login('Terjadi kesalahan saat memeriksa akun.');
}

// =====================================
// AMBIL DATA PENGGUNA
// =====================================

mysqli_stmt_bind_result(
    $stmt,
    $user_id,
    $nama,
    $email_db,
    $password_db,
    $role_db
);

$akun_ditemukan = mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);

// =====================================
// CEK AKUN
// =====================================

if (!$akun_ditemukan) {
    gagal_login('Email atau password salah!');
}

// =====================================
// VERIFIKASI PASSWORD
// =====================================

if (!password_verify($password, $password_db)) {
    gagal_login('Email atau password salah!');
}

// =====================================
// CEK ROLE PENGGUNA
// =====================================

$role = strtolower(trim($role_db));

if (!in_array($role, ['admin', 'guru'], true)) {
    gagal_login('Role akun tidak memiliki izin untuk login.');
}

// =====================================
// LOGIN BERHASIL
// =====================================

// Perbarui ID session untuk keamanan.
session_regenerate_id(true);

// Hapus data session login lama.
unset(
    $_SESSION['csrf'],
    $_SESSION['flash'],
    $_SESSION['pesan_error']
);

// Simpan data pengguna ke session.
$_SESSION['login'] = true;

$_SESSION['user_id'] = (int) $user_id;

$_SESSION['name'] = $nama;

$_SESSION['email'] = strtolower(trim($email_db));

$_SESSION['role'] = $role;

// =====================================
// ARAHKAN KE DASHBOARD
// =====================================

header('Location: dashboard.php');
exit;

?>
