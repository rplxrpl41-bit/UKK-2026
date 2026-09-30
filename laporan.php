<?php require 'cek_akses.php'; require 'koneksi.php';
$rows = $pdo->query('SELECT id,nama_siswa,nama_kelas,nama_pelanggaran,nama_guru,tanggal,poin,status FROM t_pelanggaran_siswa ORDER BY id DESC LIMIT 100')->fetchAll(); ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Laporan</title></head><body>
<h1>Laporan Pelanggaran Siswa</h1><table border="1" cellpadding="5"><tr><th>ID</th><th>Siswa</th><th>Kelas</th><th>Pelanggaran</th><th>Guru</th><th>Tanggal</th><th>Poin</th><th>Status</th></tr>
<?php foreach ($rows as $r): ?><tr><?php foreach ($r as $v): ?><td><?= htmlspecialchars((string)$v) ?></td><?php endforeach; ?></tr><?php endforeach; ?></table>
<p><a href="dashboard.php">Kembali ke Dashboard</a></p></body></html>
