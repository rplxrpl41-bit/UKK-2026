<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// Ambil ID dari URL
$id = $_GET['id'] ?? null;

// Validasi ID
if ($id === null || !is_numeric($id)) {
    die("ID siswa tidak ditemukan atau tidak valid!");
}

$id = (int) $id;

// Proses hapus data
$sql = "DELETE FROM t_siswa WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die("Query gagal disiapkan: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {
    die("Gagal menghapus data: " . mysqli_stmt_error($stmt));
}

// Periksa apakah data benar-benar terhapus
if (mysqli_stmt_affected_rows($stmt) > 0) {
    header("Location: menu1.php");
    exit;
} else {
    die("Data dengan ID $id tidak ditemukan di tabel t_siswa!");
}
?>