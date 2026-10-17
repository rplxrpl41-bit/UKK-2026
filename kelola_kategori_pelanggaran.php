
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

$sql = "SELECT * FROM t_pelanggaran_kategori ORDER BY id DESC";
$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori Pelanggaran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white mb-4">My Website</h4>

            <ul class="nav nav-pills flex-column">

                <li class="nav-item mb-2">
                    <a href="dashboard.php" class="nav-link text-white">
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
                    <a href="kelola_kelas.php" class="nav-link text-white">
                        Data Kelas
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="kelola_tahun_ajaran.php" class="nav-link text-white">
                        Kelola Tahun Ajaran
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="kelola_wali_kelas.php" class="nav-link text-white">
                        Kelola Walikelas
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="kelola_penempatan_siswa.php" class="nav-link text-white">
                        Penempatan Siswa
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="kelola_kategori_pelanggaran.php"class="nav-link active">
                        Pelanggaran Kategori
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="tentang_saya.php" class="nav-link text-white">
                        About Me
                    </a>
                </li>

                <hr class="text-secondary">

                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>

            </ul>
        </div>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center
                        flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="fw-bold">Kelola Kategori Pelanggaran</h2>
                    <p class="text-secondary mb-0">
                        Kelola data kategori pelanggaran siswa.
                    </p>
                </div>

                <a href="tambah_kategori_pelanggaran.php"
                   class="btn btn-primary">
                    + Tambah Kategori
                </a>
            </div>

            <a href="dashboard.php"
               class="btn btn-outline-secondary btn-sm mb-3">
                &larr; Kembali ke Dashboard
            </a>

            <div class="card shadow-sm border-0">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-bordered align-middle">

                            <thead class="table-dark">
                                <tr>
                                    <th width="60">No</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Status Aktif</th>
                                    <th width="160">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $no = 1;

                                if (mysqli_num_rows($query) > 0) {
                                    while ($data = mysqli_fetch_assoc($query)) {
                                ?>
                                    <tr>
                                        <td><?= $no++; ?></td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $data['nama'] ?? ''
                                            ); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $data['deskripsi'] ?? ''
                                            ); ?>
                                        </td>

                                        <td>
                                            <?php if ($data['status_aktif']) { ?>
                                                <span class="badge bg-success">
                                                    Aktif
                                                </span>
                                            <?php } else { ?>
                                                <span class="badge bg-secondary">
                                                    Tidak Aktif
                                                </span>
                                            <?php } ?>
                                        </td>

                                        <td>
                                            <a href="edit_kategori_pelanggaran.php?id=<?= (int)$data['id']; ?>"
                                               class="btn btn-warning btn-sm">
                                                Edit
                                            </a>

                                            <a href="hapus_kategori_pelanggaran.php?id=<?= (int)$data['id']; ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="5"
                                            class="text-center text-secondary py-4">
                                            Belum ada data kategori pelanggaran.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </main>

    </div>
</div>

</body>
</html>
