<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'koneksi.php';

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';

$stmt = $pdo->query("
    SELECT
        id,
        nama_siswa,
        nama_kelas,
        nama_pelanggaran,
        nama_guru,
        tanggal,
        poin,
        status
    FROM t_pelanggaran_siswa
    ORDER BY id DESC
");

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Eksport Laporan</title>

</head>

<body>

<h1>Eksport Laporan Pelanggaran Siswa</h1>

<p>
    Selamat datang,
    <b><?= htmlspecialchars($name) ?></b>
    (<?= htmlspecialchars($role) ?>)
</p>

<hr>

<!-- MENU -->

<a href="dashboard.php">Dashboard</a><br><br>

<?php if ($role == 'admin'): ?>

    <a href="menu1.php">Kelola Siswa</a><br><br>

    <a href="menu2.php">Kelola Guru</a><br><br>

    <a href="menu3.php">Kelola Jenis Pelanggaran</a><br><br>

    <a href="menu4.php">Admin</a><br><br>

    <a href="kelola_kelas.php">Kelola Kelas</a><br><br>

    <a href="kelola_tahun_ajaran.php">
        Kelola Tahun Ajaran
    </a><br><br>

    <a href="penempatan_siwa.php">
        Penempatan Siwa
    </a><br><br>

    <a href="kelola_wali_kelas.php">
        Kelola Wali Kelas
    </a><br><br>

    <a href="kelola_kategori_pelanggaran.php">
        Kelola Kategori Pelanggaran
    </a><br><br>

<?php elseif ($role == 'guru'): ?>

    <a href="menu3.php">
        Kelola Jenis Pelanggaran
    </a><br><br>

    <a href="menu4.php">
        Admin
    </a><br><br>

<?php endif; ?>

<a href="laporan.php">Laporan</a><br><br>

<a href="eksport.php">Eksport</a><br><br>

<a href="logout.php">Logout</a>

<hr>

<h2>Data Laporan Pelanggaran</h2>

<p>
    Berikut adalah data yang tersedia untuk laporan.
</p>

<!-- FITUR -->

<p>

    <button type="button" onclick="window.print()">
        Cetak Laporan
    </button>

    &nbsp;&nbsp;

    <a href="laporan.php">
        Kembali ke Laporan
    </a>

</p>

<hr>

<!-- TABEL -->

<table border="1" cellpadding="8" cellspacing="0">

    <thead>

        <tr>
            <th>ID</th>
            <th>Siswa</th>
            <th>Kelas</th>
            <th>Pelanggaran</th>
            <th>Guru</th>
            <th>Tanggal</th>
            <th>Poin</th>
            <th>Status</th>
        </tr>

    </thead>

    <tbody>

        <?php if (!empty($data)): ?>

            <?php foreach ($data as $row): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($row['id']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['nama_siswa']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['nama_kelas']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['nama_pelanggaran']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['nama_guru']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['tanggal']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['poin']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['status']) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>
                <td colspan="8">
                    Belum ada data laporan.
                </td>
            </tr>

        <?php endif; ?>

    </tbody>

</table>

<hr>

<p>
    <button type="button" onclick="window.print()">
        Cetak Laporan
    </button>
</p>

</body>

</html>