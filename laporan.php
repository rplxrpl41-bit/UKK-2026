<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';

$isAdmin = ($role == 'admin');
$isGuru  = ($role == 'guru');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pelanggaran Siswa</title>
</head>

<body>

<h1>Laporan Pelanggaran Siswa</h1>

<p>
    <b>Pengguna:</b>
    <?= htmlspecialchars($name) ?>
</p>

<p>
    <b>Role:</b>
    <?= htmlspecialchars($role) ?>
</p>

<hr>

<!-- MENU LAPORAN -->

<a href="dashboard.php">Kembali ke Dashboard</a>

&nbsp;&nbsp;

<a href="eksport.php">Ekspor Excel</a>

&nbsp;&nbsp;

<button onclick="window.print()">Cetak Laporan</button>

<hr>


<!-- TABEL LAPORAN -->

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

<?php

require 'koneksi.php';

try {

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

    if (count($data) > 0):

        foreach ($data as $row):

?>

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

<?php

        endforeach;

    else:

?>

        <tr>

            <td colspan="8">
                Belum ada data laporan.
            </td>

        </tr>

<?php

    endif;

} catch (PDOException $e) {

?>

    <tr>

        <td colspan="8">
            Gagal mengambil data laporan.
        </td>

    </tr>

<?php

}

?>

    </tbody>

</table>

<hr>

<h3>Fitur Laporan</h3>

<p>
    <button onclick="window.print()">
        Cetak Laporan
    </button>
</p>

<p>
    <a href="eksport.php">
        Ekspor ke Excel
    </a>
</p>

<p>
    <a href="dashboard.php">
        Kembali ke Dashboard
    </a>
</p>

</body>
</html>