<?php require 'cek_akses.php'; require 'koneksi.php';
$rows = $pdo->query('SELECT * FROM t_pelanggaran ORDER BY id LIMIT 100')->fetchAll(); ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Kelola Jenis Pelanggaran</title></head><body>
<h1>Kelola Jenis Pelanggaran</h1>
<table border="1" cellpadding="5"><tr><th>ID</th><th>Kode</th><th>Nama Pelanggaran</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['id'] ?? '') ?></td><td><?= htmlspecialchars($r['kode'] ?? '') ?></td><td><?= htmlspecialchars($r['nama'] ?? $r['nama_pelanggaran'] ?? '') ?></td></tr><?php endforeach; ?></table>
<p><a href="dashboard.php">Kembali ke Dashboard</a></p></body></html>
