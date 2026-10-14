<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

$query = mysqli_query($koneksi, "
    SELECT * FROM t_kelas
    ORDER BY id DESC
");

if (!$query) {
    die("Gagal mengambil data kelas: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<body>
<<<<<<< HEAD
    <div class="container-fluid">
=======
<div class="container-fluid">
>>>>>>> 385dba184835d1471028cfc31f21539087bdb38c
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white mb-4">
                My Website
            </h4>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
<<<<<<< HEAD
                    <a href="dashboard.php" class="nav-link active">
=======
                    <a href="dashboard.php"class="nav-link text-white">
>>>>>>> 385dba184835d1471028cfc31f21539087bdb38c
                        Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_siswa.php" class="nav-link text-white">
                        Data Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_guru.php" class="nav-link text-white">
                        Data Guru
                    </a>
                </li>
                <li class="nav-item mb-2">
<<<<<<< HEAD
                    <a href="kelola_kelas.php" class="nav-link text-white">
=======
                    <a href="kelola_kelas.php" class="nav-link active">
>>>>>>> 385dba184835d1471028cfc31f21539087bdb38c
                        Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="laporan.php" class="nav-link text-white">
                        Laporan
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="tentang_saya.php" class="nav-link text-white">
                        About Me
                    </a>
                </li>
                <hr class="text-secondary">
                <li class="nav-item">
                    <a href="login.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <h2>Kelola Kelas</h2>
    <p>Halaman untuk mengelola data kelas.</p>

    <a href="dashboard.php">Kembali ke Dashboard</a>
    |
    <a href="tambah_kelas.php">Tambah Kelas</a>

    <br><br>

    <table border="1" cellpadding="10" cellspacing="0">
        <head>
            <tr>
                <th>No</th>
                <th>Nama Kelas</th>
                <th>Tingkat</th>
                <th>Jurusan</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </head>

        <body>
            
            <?php
            $no = 1;

            if (mysqli_num_rows($query) > 0) {
                while ($kelas = mysqli_fetch_assoc($query)) {
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($kelas['nama']) ?></td>
                    <td><?= htmlspecialchars($kelas['tingkat']) ?></td>
                    <td><?= htmlspecialchars($kelas['jurusan']) ?></td>
                    <td>
                        <?= $kelas['status_aktif'] ? 'Aktif' : 'Tidak Aktif' ?>
                    </td>
                    <td>
                        <a href="edit_kelas.php?id=<?= $kelas['id'] ?>">  <button type="button" class="btn btn-primary">edit</button></a>
                        |
                        <a
                            href="hapus.php?id=<?= $kelas['id'] ?>"
                            onclick="return confirm('Yakin ingin menghapus kelas ini?')"
                        > <button type="button" class="btn btn-danger">hapus</button></a>
                    </td>
                </tr>
            <?php
                }
            } else {
            ?>
                <tr>
                    <td colspan="6">Belum ada data kelas.</td>
                </tr>
            <?php } ?>
        </body>
    </table>

</body>
</html>