<?php require 'cek_akses.php'; require 'koneksi.php';
$rows = $pdo->query('SELECT * FROM t_pelanggaran_kategori ORDER BY id LIMIT 100')->fetchAll(); ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Kelola Kategori Pelanggaran</title></head><body>
<h1>Kelola Kategori Pelanggaran</h1><table border="1" cellpadding="5"><tr><th>ID</th><th>Nama</th><th>Deskripsi</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['id'] ?? '') ?></td><td><?= htmlspecialchars($r['nama'] ?? $r['name'] ?? '') ?></td><td><?= htmlspecialchars($r['deskripsi'] ?? '') ?></td></tr><?php endforeach; ?></table>
<p><a href="dashboard.php">Kembali ke Dashboard</a></p></body></html>
