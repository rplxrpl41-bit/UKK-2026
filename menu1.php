<?php
require 'cek_akses.php';
require 'koneksi.php';
$rows = $pdo->query('SELECT * FROM t_siswa ORDER BY id LIMIT 100')->fetchAll();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Kelola Siswa</title></head><body>
<h1>Kelola Siswa</h1>
<p>Data siswa (maksimal 100 data ditampilkan).</p>
<table border="1" cellpadding="5"><tr><th>ID</th><th>Nama</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= htmlspecialchars($r['id'] ?? '') ?></td><td><?= htmlspecialchars($r['nama'] ?? $r['name'] ?? '') ?></td></tr><?php endforeach; ?>
</table><p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body></html>
