<?php
require 'cek_akses.php';
$name = $_SESSION['name'];
$role = $_SESSION['role'];

$menuAdmin = [
    ['Dashboard','dashboard.php'],
    ['Kelola Siswa','menu1.php'],
    ['Kelola Guru','menu2.php'],
    ['Kelola Jenis Pelanggaran','menu3.php'],
    ['Admin','menu4.php'],
    ['Kelola Kelas','kelola_kelas.php'],
    ['Kelola Tahun Ajaran','kelola_tahun_ajaran.php'],
    ['Penempatan Siwa','penempatan_siwa.php'],
    ['Kelola Wali Kelas','kelola_wali_kelas.php'],
    ['Kelola Kategori Pelanggaran','kelola_kategori_pelanggaran.php'],
    ['Laporan','laporan.php'],
    ['Eksport','eksport.php'],
    ['Logout','logout.php'],
];

$menuGuru = [
    ['Dashboard','dashboard.php'],
    ['Kelola Siswa','menu1.php'],
    ['Kelola Jenis Pelanggaran','menu3.php'],
    ['Kelola Kategori Pelanggaran','kelola_kategori_pelanggaran.php'],
    ['Laporan','laporan.php'],
    ['Logout','logout.php'],
];

$menus = $role === 'admin' ? $menuAdmin : $menuGuru;
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><title>Dashboard</title></head>
<body>
<h1>Dashboard</h1>
<p>Selamat datang, <b><?= htmlspecialchars($name) ?></b> (<?= htmlspecialchars($role) ?>)</p>
<ul>
<?php foreach ($menus as [$label, $url]): ?>
    <li><a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a></li>
<?php endforeach; ?>
</ul>
</body>
</html>
