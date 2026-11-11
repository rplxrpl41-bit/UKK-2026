<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// =====================================
// IDENTITAS DAN HAK AKSES
// =====================================

$namaUser = $_SESSION['name']
    ?? $_SESSION['nama_user']
    ?? 'Pengguna';

$role = $_SESSION['role'] ?? '';
$email = strtolower(trim($_SESSION['email'] ?? ''));

$isAdmin = ($role === 'admin');

$emailWaliKelas = [
    'ari@gmail.com',
    'aldi@gmail.com',
    'anshor@gmail.com'
];

$isWaliKelas = (
    $role === 'guru'
    && (
        !empty($_SESSION['is_wali_kelas'])
        || in_array($email, $emailWaliKelas, true)
    )
);

$isGuruBiasa = (
    $role === 'guru'
    && !$isWaliKelas
);

// =====================================
// FUNGSI HITUNG DATA
// =====================================

function hitungData($koneksi, $tabel)
{
    $daftarTabel = [
        't_siswa',
        't_guru',
        't_kelas',
        't_pelanggaran_siswa'
    ];

    if (!in_array($tabel, $daftarTabel, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS total FROM `$tabel`";
    $hasil = mysqli_query($koneksi, $sql);

    if (!$hasil) {
        return 0;
    }

    $data = mysqli_fetch_assoc($hasil);
    mysqli_free_result($hasil);

    return (int) $data['total'];
}

$totalSiswa = hitungData($koneksi, 't_siswa');
$totalGuru = hitungData($koneksi, 't_guru');
$totalKelas = hitungData($koneksi, 't_kelas');
$totalPelanggaran = hitungData($koneksi, 't_pelanggaran_siswa');

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard - Sistem Pelanggaran Siswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* SIDEBAR */
        .sidebar { position: sticky; top: 0; height: 100vh; align-self: flex-start; overflow-y: auto;
            background: #172554;
            color: white;
            min-height: 100vh;
            padding: 22px 15px;
        }

        .sidebar h4 {
            font-weight: bold;
            margin-bottom: 30px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: white;
        }

        .sidebar .nav-link {
            color: #e2e8f0;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .sidebar .nav-link:hover {
            background: #263b70;
            color: white;
        }

        .sidebar .nav-link.active {
            background: #3157d5;
            color: white;
        }

        .sidebar .logout {
            color: #fca5a5;
        }

        .content {
            padding: 28px;
            min-width: 0;
        }

        .page-title {
            color: #172554;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .page-description {
            color: #64748b;
            margin-bottom: 25px;
        }

        /* KARTU SELAMAT DATANG */
        .welcome-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            border-left: 5px solid #3157d5;
        }

        .welcome-card h4 {
            color: #172554;
            font-weight: bold;
        }

        /* KARTU STATISTIK */
        .stat-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            height: 100%;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card .card-body {
            padding: 24px;
        }

        .stat-blue {
            background: #3157d5;
            color: white;
        }

        .stat-yellow {
            background: #facc15;
            color: #172554;
        }

        .stat-navy {
            background: #172554;
            color: white;
        }

        .stat-light {
            background: white;
            color: #172554;
            border-bottom: 4px solid #3157d5;
        }

        .stat-label {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 34px;
            font-weight: bold;
            margin: 0;
        }

        .stat-icon {
            font-size: 28px;
            opacity: 0.9;
        }

        /* MENU CEPAT */
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-top: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            color: #172554;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .quick-link {
            display: block;
            height: 100%;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            text-decoration: none;
            color: #172554;
            transition: 0.2s;
        }

        .quick-link:hover {
            background: #eff6ff;
            border-color: #3157d5;
            color: #3157d5;
            transform: translateY(-2px);
        }

        .quick-icon {
            font-size: 25px;
            margin-bottom: 10px;
        }

        .quick-link h6 {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .quick-link p {
            color: #64748b;
            font-size: 13px;
            margin: 0;
        }

        .footer-text {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 25px;
        }

        .role-label {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 8px;
        }

        .role-admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .role-guru {
            background: #dcfce7;
            color: #166534;
        }

        .role-wali {
            background: #fef3c7;
            color: #92400e;
        }

        @media (max-width: 767px) {
            .sidebar { position: static; height: auto; overflow-y: visible; align-self: auto;
                min-height: auto;
            }

            .content {
                padding: 18px;
            }

            .welcome-card,
            .section-card {
                padding: 18px;
            }

            .page-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- =====================================
             SIDEBAR ADMIN
        ====================================== -->

        <?php if ($isAdmin): ?>

            <aside class="col-md-3 col-lg-2 sidebar">

                <h4 class="sidebar-title">My Website</h4>

                <nav class="nav flex-column">

                    <a href="dashboard.php" class="nav-link active">
                        ⌂ Dashboard
                    </a>

                    <a href="kelola_siswa.php" class="nav-link">
                        ♟ Data Siswa
                    </a>

                    <a href="kelola_guru.php" class="nav-link">
                        ♙ Data Guru
                    </a>

                    <a href="kelola_kelas.php" class="nav-link">
                        ▦ Data Kelas
                    </a>

                    <a href="kelola_tahun_ajaran.php" class="nav-link">
                        ◷ Kelola Tahun Ajaran
                    </a>

                    <a href="kelola_wali_kelas.php" class="nav-link">
                        ♙ Kelola Wali Kelas
                    </a>

                    <a href="kelola_penempatan_siswa.php" class="nav-link">
                        ⇄ Penempatan Siswa
                    </a>

                    <a href="kelola_kategori_pelanggaran.php" class="nav-link">
                        ☷ Kategori Pelanggaran
                    </a>

                    <a href="tentang_saya.php" class="nav-link">
                        ℹ About Me
                    </a>

                    <hr>

                    <a href="logout.php"
                       class="nav-link logout"
                       onclick="return confirm('Yakin ingin logout?')">
                        ⇥ Logout
                    </a>

                </nav>
            </aside>

        <?php else: ?>

            <!-- SIDEBAR GURU DAN WALI KELAS -->

            <aside class="col-md-3 col-lg-2 sidebar">

                <h4 class="sidebar-title">Panel Guru</h4>

                <nav class="nav flex-column">

                    <a href="dashboard.php" class="nav-link active">
                        ⌂ Dashboard
                    </a>

                    <a href="catatan_pelanggaran.php" class="nav-link">
                        ✎ Catatan Pelanggaran
                    </a>

                    <a href="tindakan.php" class="nav-link">
                        ⚑ Tindakan
                    </a>

                    <a href="riwayat_pelanggaran.php" class="nav-link">
                        ☰ Riwayat Pelanggaran
                    </a>

                    <?php if ($isWaliKelas): ?>

                        <a href="laporan.php" class="nav-link">
                            ▤ Laporan
                        </a>

                    <?php endif; ?>

                    <a href="rekap_point.php" class="nav-link">
                        ★ Rekap Poin
                    </a>

                    <a href="tentang_saya.php" class="nav-link">
                        ℹ About Me
                    </a>

                    <hr>

                    <a href="logout.php"
                       class="nav-link logout"
                       onclick="return confirm('Yakin ingin logout?')">
                        ⇥ Logout
                    </a>

                </nav>
            </aside>

        <?php endif; ?>


        <!-- =====================================
             KONTEN DASHBOARD
        ====================================== -->

        <main class="col-md-9 col-lg-10 content">

            <h1 class="page-title">Dashboard</h1>

            <p class="page-description">
                Ringkasan informasi Sistem Pelanggaran Siswa.
            </p>


            <!-- UCAPAN SELAMAT DATANG -->

            <div class="welcome-card">

                <h4>
                    Selamat datang,
                    <?= htmlspecialchars($namaUser, ENT_QUOTES, 'UTF-8'); ?>!
                </h4>

                <?php if ($isAdmin): ?>

                    <span class="role-label role-admin">
                        ADMINISTRATOR
                    </span>

                    <p class="text-muted mt-3 mb-0">
                        Anda login sebagai Administrator dan memiliki
                        akses untuk mengelola seluruh sistem.
                    </p>

                <?php elseif ($isWaliKelas): ?>

                    <span class="role-label role-wali">
                        WALI KELAS
                    </span>

                    <p class="text-muted mt-3 mb-0">
                        Anda login sebagai Wali Kelas. Silakan gunakan
                        fitur Guru dan fitur tambahan Wali Kelas.
                    </p>

                <?php else: ?>

                    <span class="role-label role-guru">
                        GURU
                    </span>

                    <p class="text-muted mt-3 mb-0">
                        Anda login sebagai Guru. Silakan gunakan fitur
                        pencatatan dan pemantauan pelanggaran siswa.
                    </p>

                <?php endif; ?>

            </div>


            <!-- KARTU STATISTIK -->

            <div class="row g-3">

                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-blue">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label">Data Siswa</div>
                                    <h2 class="stat-number">
                                        <?= $totalSiswa; ?>
                                    </h2>
                                </div>
                                <div class="stat-icon">♟</div>
                            </div>
                            <small>Total siswa terdaftar</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-yellow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label">Data Guru</div>
                                    <h2 class="stat-number">
                                        <?= $totalGuru; ?>
                                    </h2>
                                </div>
                                <div class="stat-icon">♙</div>
                            </div>
                            <small>Total guru terdaftar</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-navy">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label">Data Kelas</div>
                                    <h2 class="stat-number">
                                        <?= $totalKelas; ?>
                                    </h2>
                                </div>
                                <div class="stat-icon">▦</div>
                            </div>
                            <small>Total kelas tersedia</small>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card stat-light">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-label">Catatan Pelanggaran</div>
                                    <h2 class="stat-number">
                                        <?= $totalPelanggaran; ?>
                                    </h2>
                                </div>
                                <div class="stat-icon">⚑</div>
                            </div>
                            <small>Total catatan tersimpan</small>
                        </div>
                    </div>
                </div>

            </div>


            <!-- =====================================
                 MENU ADMIN
            ====================================== -->

            <?php if ($isAdmin): ?>

                <div class="section-card">

                    <h5 class="section-title">
                        Menu Pengelolaan Admin
                    </h5>

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
                            <a href="kelola_penempatan_siswa.php" class="quick-link">
                                <div class="quick-icon">⇄</div>
                                <h6>Penempatan Siswa</h6>
                                <p>Atur penempatan siswa ke kelas.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="kelola_kategori_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">☷</div>
                                <h6>Kategori Pelanggaran</h6>
                                <p>Kelola kategori pelanggaran siswa.</p>
                            </a>
                        </div>

                    </div>
                </div>


            <!-- =====================================
                 MENU GURU BIASA
            ====================================== -->

            <?php elseif ($isGuruBiasa): ?>

                <div class="section-card">

                    <h5 class="section-title">Menu Guru</h5>

                    <div class="row g-3">

                        <div class="col-md-6 col-xl-4">
                            <a href="catatan_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">✎</div>
                                <h6>Catatan Pelanggaran</h6>
                                <p>Catat pelanggaran yang dilakukan siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="tindakan.php" class="quick-link">
                                <div class="quick-icon">⚑</div>
                                <h6>Tindakan</h6>
                                <p>Isi tindakan dan ubah status penanganan.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="riwayat_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">☰</div>
                                <h6>Riwayat Pelanggaran</h6>
                                <p>Lihat riwayat pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="rekap_point.php" class="quick-link">
                                <div class="quick-icon">★</div>
                                <h6>Rekap Poin</h6>
                                <p>Lihat total poin pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="tentang_saya.php" class="quick-link">
                                <div class="quick-icon">ℹ</div>
                                <h6>About Me</h6>
                                <p>Informasi tentang pembuat aplikasi.</p>
                            </a>
                        </div>

                    </div>
                </div>


            <!-- =====================================
                 MENU WALI KELAS
            ====================================== -->

            <?php elseif ($isWaliKelas): ?>

                <div class="section-card">

                    <h5 class="section-title">Menu Wali Kelas</h5>

                    <div class="row g-3">

                        <div class="col-md-6 col-xl-4">
                            <a href="catatan_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">✎</div>
                                <h6>Catatan Pelanggaran</h6>
                                <p>Catat pelanggaran yang dilakukan siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="tindakan.php" class="quick-link">
                                <div class="quick-icon">⚑</div>
                                <h6>Tindakan</h6>
                                <p>Pantau tindakan penanganan pelanggaran.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="riwayat_pelanggaran.php" class="quick-link">
                                <div class="quick-icon">☰</div>
                                <h6>Riwayat Pelanggaran</h6>
                                <p>Pantau riwayat pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="laporan.php" class="quick-link">
                                <div class="quick-icon">▤</div>
                                <h6>Laporan</h6>
                                <p>Lihat laporan pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="rekap_point.php" class="quick-link">
                                <div class="quick-icon">★</div>
                                <h6>Rekap Poin</h6>
                                <p>Pantau poin pelanggaran siswa.</p>
                            </a>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <a href="tentang_saya.php" class="quick-link">
                                <div class="quick-icon">ℹ</div>
                                <h6>About Me</h6>
                                <p>Informasi tentang pembuat aplikasi.</p>
                            </a>
                        </div>

                    </div>
                </div>

            <?php endif; ?>


            <p class="footer-text">
                Sistem Informasi Pelanggaran Siswa &copy; 2026
            </p>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
