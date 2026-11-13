<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak!");
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['pesan'] = "ID kelas tidak valid!";
    header("Location: kelola_kelas.php");
    exit;
}

// Kelas yang masih dipakai TIDAK boleh dihapus, karena relasi di database
// bersifat CASCADE (penempatan siswa, wali kelas, dan catatan pelanggaran
// ikut terhapus). Nonaktifkan kelas lewat menu Edit jika sudah tidak dipakai.
$cek = mysqli_prepare($koneksi, "
    SELECT
        (SELECT COUNT(*) FROM t_kelas_siswa WHERE kelas_id = ?) AS penempatan,
        (SELECT COUNT(*) FROM t_wali_kelas WHERE kelas_id = ?) AS wali,
        (SELECT COUNT(*) FROM t_pelanggaran_siswa WHERE kelas_id = ?) AS pelanggaran
");
mysqli_stmt_bind_param($cek, "iii", $id, $id, $id);
mysqli_stmt_execute($cek);
$pakai = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
mysqli_stmt_close($cek);

if ($pakai && ((int) $pakai['penempatan'] + (int) $pakai['wali'] + (int) $pakai['pelanggaran']) > 0) {
    $_SESSION['pesan'] = "Kelas tidak dapat dihapus karena masih dipakai "
        . "(penempatan siswa: " . (int) $pakai['penempatan']
        . ", wali kelas: " . (int) $pakai['wali']
        . ", catatan pelanggaran: " . (int) $pakai['pelanggaran']
        . "). Gunakan Edit untuk menonaktifkan kelas.";
    header("Location: kelola_kelas.php");
    exit;
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM t_kelas WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    $_SESSION['pesan'] = "Gagal menghapus kelas: " . $error;
    header("Location: kelola_kelas.php");
    exit;
}

$terhapus = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

$_SESSION['pesan'] = $terhapus > 0
    ? "Data kelas berhasil dihapus!"
    : "Data kelas tidak ditemukan.";

header("Location: kelola_kelas.php");
exit;
