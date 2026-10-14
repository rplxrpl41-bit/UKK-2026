<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

$query = mysqli_query($koneksi, "
    SELECT * FROM t_tahun_ajaran
    ORDER BY id DESC
");

if (!$query) {
    die("Gagal mengambil data: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tahun Ajaran</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<body>

    <h2>Kelola Tahun Ajaran</h2>
    <p>Halaman pengelolaan data tahun ajaran sekolah.</p>

    <a href="dashboard.php">Kembali ke Dashboard</a>
    |
    <a href="tambah_tahun_ajaran.php">Tambah Tahun Ajaran</a>

    <br><br>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Tahun Ajaran</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $no = 1;

            if (mysqli_num_rows($query) > 0) {
                while ($row = mysqli_fetch_assoc($query)) {
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_mulai']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal_selesai']) ?></td>
                    <td>
                        <?= $row['status_aktif'] == 1
                            ? 'Aktif'
                            : 'Tidak Aktif' ?>
                    </td>
                    <td>
                        <a href="edit_tahun_ajaran.php?id=<?= (int) $row['id'] ?>">
                            Edit
                        </a>
                        |
                        <a
                            href="hapus_tahun_ajaran.php?id=<?= (int) $row['id'] ?>"
                            onclick="return confirm('Yakin ingin menghapus tahun ajaran ini?')"
                        >
                            Hapus
                        </a>
                    </td>
                </tr>
            <?php
                }
            } else {
            ?>
                <tr>
                    <td colspan="6">Belum ada data tahun ajaran.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

</body>
</html>