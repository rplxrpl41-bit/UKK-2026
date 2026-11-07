<?php
/*
 * USER AWAL GURU
 * Jalankan sekali: http://localhost/UKK-2026/buat_user_guru.php
 * Kalau akun sudah ada, password-nya di-reset ke guru123.
 */
include 'config/koneksi.php';
 
$name     = 'guru';
$email    = 'guru@gmail.com';
$password = 'guru123';
$role     = 'guru';
$hash     = password_hash($password, PASSWORD_DEFAULT);
 
$cek = mysqli_prepare($koneksi, "SELECT id FROM t_users WHERE email = ?");
mysqli_stmt_bind_param($cek, "s", $email);
mysqli_stmt_execute($cek);
$ada = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
 
if ($ada) {
    $stmt = mysqli_prepare($koneksi, "UPDATE t_users SET name = ?, password = ?, role = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $name, $hash, $role, $ada['id']);
    $aksi = "diperbarui (password di-reset)";
} else {
    // remember_token wajib diisi karena kolomnya NOT NULL tanpa default
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO t_users (name, email, password, remember_token, role) VALUES (?, ?, ?, '', ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);
    $aksi = "dibuat";
}
 
if (mysqli_stmt_execute($stmt)) {
    echo "User guru berhasil $aksi.";
    echo "<br>Email: $email";
    echo "<br>Password: $password";
    echo "<br><br><b style='color:red'>Hapus file ini setelah dipakai.</b>";
} else {
    echo "Gagal: " . htmlspecialchars(mysqli_stmt_error($stmt));
}