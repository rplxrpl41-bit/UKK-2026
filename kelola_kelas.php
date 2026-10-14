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
            s.nama AS nama_siswa,
            k.nama AS nama_kelas,
            k.tingkat,
            k.jurusan,
            ta.nama AS tahun_ajaran
        FROM t_kelas_siswa ks
        LEFT JOIN t_siswa s ON ks.siswa_id = s.id
        LEFT JOIN t_kelas k ON ks.kelas_id = k.id
        LEFT JOIN t_tahun_ajaran ta
            ON ks.tahun_ajaran_id = ta.id
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
    <title>Kelola Kelas</title>
</head>
<body>


<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white mb-4">
                My Website
            </h4>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a href="dashboard.php" class="nav-link active">
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
                    <a href="Kelola_kelas.php" class="nav-link text-white">
                        Data Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_tahun_ajaran.php" class="nav-link text-white">
                        Kelola Tahun Ajaran
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white">
                        Kelola Walikelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white">
                        Penempatan Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white">
                        Pelanggaran Kategori
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white">
                        About Me
                    </a>
                </li>
                
                <hr class="text-secondary">
                <li class="nav-item">
                    <a href="logoout.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
          <main class="col-md-9 col-lg-10 p-4">
            
<h1>Kelola Kelas Siswa</h1>

<a href="dashboard.php">Kembali ke Dashboard</a>
<br><br>
<a href="tambah_kelas.php">Tambah Kelas Siswa</a>

<hr>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>No</th>
        <th>Nama Siswa</th>
        <th>Kelas</th>
        <th>Tahun Ajaran</th>
        <th>Tanggal Mulai</th>
        <th>Tanggal Selesai</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php
    $no = 1;

    while ($data = mysqli_fetch_assoc($hasil)) {
    ?>
    <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo htmlspecialchars($data['nama_siswa'] ?? '-'); ?></td>
        <td>
            <?php
            echo htmlspecialchars(
                ($data['nama_kelas'] ?? '-') . ' - ' .
                ($data['tingkat'] ?? '-') . ' ' .
                ($data['jurusan'] ?? '')
            );
            ?>
        </td>
        <td><?php echo htmlspecialchars($data['tahun_ajaran'] ?? '-'); ?></td>
        <td><?php echo $data['tanggal_mulai']; ?></td>
        <td><?php echo $data['tanggal_selesai']; ?></td>
        <td>
            <?php echo $data['status_aktif'] == 1 ? 'Aktif' : 'Tidak Aktif'; ?>
        </td>
        <td>
            <a href="edit_kelas.php?id=<?php echo $data['id']; ?>">Edit</a>
            |
            <a href="hapus_kelas.php?id=<?php echo $data['id']; ?>"
               onclick="return confirm('Yakin ingin menghapus data ini?')">
                Hapus
            </a>
        </td>
    </tr>
    <?php } ?>
</table>

</body>
</html>