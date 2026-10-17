
<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

$sql = "SELECT
            ks.*,
            s.nis,
            s.nama AS nama_siswa,
            k.nama AS nama_kelas,
            k.tingkat,
            k.jurusan,
            ta.nama AS tahun_ajaran
        FROM t_kelas_siswa ks
        LEFT JOIN t_siswa s ON ks.siswa_id = s.id
        LEFT JOIN t_kelas k ON ks.kelas_id = k.id
        LEFT JOIN t_tahun_ajaran ta ON ks.tahun_ajaran_id = ta.id
        ORDER BY ks.id DESC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    die("Gagal mengambil data: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penempatan Siswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 min-vh-100 p-3"
             style="background-color: #000;">

            <h4 class="text-white mb-4">
                My Website
            </h4>

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
                    <a href="kelola_penempatan_siswa.php" class="nav-link active">
                        Penempatan Siswa
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="kelola_kategori_pelanggaran.php" class="nav-link text-white">
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

        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">

            <h1>Penempatan Siswa</h1>

            <a href="dashboard.php" class="btn btn-secondary btn-sm">
                Kembali ke Dashboard
            </a>

            <a href="tambah_penempatan.php" class="btn btn-primary btn-sm">
                Tambah Penempatan Siswa
            </a>

            <hr>

            <!-- TABEL HITAM -->
            <div class="table-responsive">
                <table class="table table-sm table-dark table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
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

                    if (mysqli_num_rows($hasil) > 0) {
                        while ($data = mysqli_fetch_assoc($hasil)) {
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?= htmlspecialchars($data['nis'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['nama_siswa'] ?? '-') ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    ($data['nama_kelas'] ?? '-') . ' - ' .
                                    ($data['tingkat'] ?? '-') . ' ' .
                                    ($data['jurusan'] ?? '')
                                );
                                ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['tahun_ajaran'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['tanggal_mulai'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($data['tanggal_selesai'] ?? '-') ?>
                            </td>

                            <td>
                                <?php if (($data['status_aktif'] ?? 0) == 1) { ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                <?php } ?>
                            </td>

                            <td>
                                <a href="edit_penempatan.php?id=<?= (int) $data['id'] ?>"
                                   class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <a href="hapus_penempatan.php?id=<?= (int) $data['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin ingin menghapus data ini?')">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                Belum ada data penempatan siswa.
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
