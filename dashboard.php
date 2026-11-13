<?php
// Memeriksa session login
include "includes/cek_session.php";

// Mengambil data pengguna
$nama = $_SESSION['nama'] ?? $_SESSION['nama_lengkap'] ?? $_SESSION['name'] ?? 'Pengguna';
$email = $_SESSION['email'] ?? '-';
$role = $_SESSION['role'] ?? '-';

// Menghubungkan database
include "config/koneksi.php";

// Menghitung jumlah data dari database
function hitungData($koneksi, $tabel)
{
    $tabelDiizinkan = ['t_siswa', 't_guru', 't_kelas'];

    if (!in_array($tabel, $tabelDiizinkan, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$tabel`";
    $hasil = mysqli_query($koneksi, $sql);

    if (!$hasil) {
        die(
            "Gagal menghitung data pada tabel " .
            htmlspecialchars($tabel) .
            ". Periksa nama tabel di database. Error: " .
            htmlspecialchars(mysqli_error($koneksi))
        );
    }

    $data = mysqli_fetch_assoc($hasil);

    return (int) $data['total'];
}

// Jumlah data otomatis
$total_siswa = hitungData($koneksi, 't_siswa');
$total_guru = hitungData($koneksi, 't_guru');
$total_kelas = hitungData($koneksi, 't_kelas');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - Sistem Pelanggaran Siswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="asset/zulfan.css" rel="stylesheet">
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <?php include "layout/sidebar.php"; ?>

        <!-- KONTEN DASHBOARD -->
        <main class="col-md-9 col-lg-10 content">

            <h1 class="page-title">Dashboard</h1>

            <p class="page-description">
                Ringkasan informasi Sistem Pelanggaran Siswa.
            </p>

            <!-- UCAPAN SELAMAT DATANG -->
            <div class="welcome-card">

                <h4>
                    Selamat datang,
                    <?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'); ?>!
                </h4>

                <?php if ($role == 'admin') { ?>

                    <span class="role-label role-admin">ADMINISTRATOR</span>

                    <p class="text-muted mt-3 mb-0">
                        Anda login sebagai Administrator dan memiliki
                        akses untuk mengelola seluruh sistem.
                    </p>

                <?php } elseif (in_array($role, ['guru', 'walikelas'], true)) { ?>

                    <span class="role-label role-guru"><?= $role === 'walikelas' ? 'WALI KELAS' : 'GURU'; ?></span>

                    <p class="text-muted mt-3 mb-0">
                        Anda login sebagai <?= $role === 'walikelas' ? 'Wali Kelas' : 'Guru'; ?>. Silakan gunakan fitur
                        pencatatan dan pemantauan pelanggaran siswa.
                    </p>

                <?php } ?>

            </div>

            <?php if ($role == 'admin') { ?>

                <!-- KARTU STATISTIK ADMIN -->
                <div class="row g-3">

                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card stat-blue">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Data Siswa</div>
                                        <h2 class="stat-number"><?php echo $total_siswa; ?></h2>
                                    </div>
                                    <div class="stat-icon">♟</div>
                                </div>
                                <small>Total siswa terdaftar</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card stat-yellow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Data Guru</div>
                                        <h2 class="stat-number"><?php echo $total_guru; ?></h2>
                                    </div>
                                    <div class="stat-icon">♙</div>
                                </div>
                                <small>Total guru terdaftar</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-xl-4">
                        <div class="card stat-card stat-navy">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">Data Kelas</div>
                                        <h2 class="stat-number"><?php echo $total_kelas; ?></h2>
                                    </div>
                                    <div class="stat-icon">▦</div>
                                </div>
                                <small>Total kelas tersedia</small>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- MENU PENGELOLAAN ADMIN -->
                <div class="section-card">

                    <h5 class="section-title">Menu Pengelolaan Admin</h5>

                    <div class="row g-3">

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_siswa.php" class="quick-link">
                                <div class="quick-icon">♟</div>
                                <h6>Kelola Siswa</h6>
                                <p>Lihat dan kelola data siswa sekolah.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_guru.php" class="quick-link">
                                <div class="quick-icon">♙</div>
                                <h6>Kelola Guru</h6>
                                <p>Lihat dan kelola data guru sekolah.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_kelas.php" class="quick-link">
                                <div class="quick-icon">▦</div>
                                <h6>Kelola Kelas</h6>
                                <p>Atur data kelas yang tersedia.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_tahun_ajaran.php" class="quick-link">
                                <div class="quick-icon">◷</div>
                                <h6>Tahun Ajaran</h6>
                                <p>Kelola periode tahun ajaran sekolah.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_wali_kelas.php" class="quick-link">
                                <div class="quick-icon">♙</div>
                                <h6>Kelola Wali Kelas</h6>
                                <p>Kelola penugasan wali kelas.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="penempatan_siswa.php" class="quick-link">
                                <div class="quick-icon">⇄</div>
                                <h6>Penempatan Siswa</h6>
                                <p>Atur penempatan siswa ke kelas.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_pelanggaran_kategori.php" class="quick-link">
                                <div class="quick-icon">☷</div>
                                <h6>Kategori Pelanggaran</h6>
                                <p>Kelola kategori pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_jenis_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">☰</div>
                                <h6>Jenis Pelanggaran</h6>
                                <p>Kelola jenis dan poin pelanggaran.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="laporan.php" class="quick-link">
                                <div class="quick-icon">▤</div>
                                <h6>Laporan</h6>
                                <p>Lihat dan cetak laporan pelanggaran.</p>
                            </a>
                        </div>

                    </div>
                </div>

            <?php } elseif (in_array($role, ['guru', 'walikelas'], true)) { ?>

                <!-- MENU GURU / WALI KELAS -->
                <div class="section-card mt-0">

                    <h5 class="section-title"><?= $role === 'walikelas' ? 'Menu Wali Kelas' : 'Menu Guru'; ?></h5>

                    <div class="row g-3">

                        <div class="col-md-6 col-xl-4">
                            <a href="catat_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">✎</div>
                                <h6>Catat Pelanggaran</h6>
                                <p>Mencatat pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="riwayat.php" class="quick-link">
                                <div class="quick-icon">☰</div>
                                <h6>Riwayat Pelanggaran</h6>
                                <p>Melihat riwayat pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="rekap_poin.php" class="quick-link">
                                <div class="quick-icon">★</div>
                                <h6>Rekap Poin</h6>
                                <p>Melihat jumlah poin pelanggaran.</p>
                            </a>
                        </div>

                    </div>
                </div>

            <?php } else { ?>

                <div class="alert alert-danger">
                    Role pengguna tidak dikenali.
                </div>

            <?php } ?>

            <p class="footer-text">
                &copy; <?= date('Y'); ?> Sistem Informasi Pelanggaran Siswa
            </p>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
