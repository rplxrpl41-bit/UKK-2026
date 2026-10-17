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
            g.id,
            g.nip,
            g.nama,
            g.email,
            g.status_aktif,
            g.created_at,
            g.updated_at
        FROM t_guru g
        JOIN t_users u ON g.user_id = u.id
        WHERE u.role = 'guru'
        ORDER BY g.id DESC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    die("Gagal mengambil data guru: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Guru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 min-vh-100 p-3"
             style="background-color: #000;">

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
                    <a href="kelola_wali_kelas.php" class="nav-link active">
                        Kelola Walikelas
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <a href="penempatan_siwa.php" class="nav-link text-white">
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

        <!-- KONTEN DATA GURU -->
        <main class="col-md-9 col-lg-10 p-4">

            <h1>Kelola Guru</h1>

            <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">
                Kembali ke Dashboard
            </a>

            <a href="tambah_guru.php" class="btn btn-primary btn-sm mb-3">
                Tambah Guru
            </a>

            <hr>

            <h4>Data Guru</h4>

            <div class="table-responsive">
              <table class="table table-sm table-dark table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>No</th>
                            <th>NIP</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php
                    $no = 1;

                    while ($guru = mysqli_fetch_assoc($hasil)) {
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>

                            <td>
                                <?php echo htmlspecialchars($guru['nip'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($guru['nama'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($guru['email'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php if ($guru['status_aktif'] == 1) { ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                <?php } ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($guru['created_at'] ?? '-'); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($guru['updated_at'] ?? '-'); ?>
                            </td>

                            <td>
                                <a href="edit_guru.php?id=<?php echo $guru['id']; ?>"
                                   class="btn btn-warning btn-sm">
                                    Edit
                                </a>

                                <a href="hapus_guru.php?id=<?php echo $guru['id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin ingin menghapus guru ini?')">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                    <?php } ?>

                    <?php if (mysqli_num_rows($hasil) == 0) { ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                Belum ada data guru.
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
