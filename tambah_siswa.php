<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

// Hanya admin yang boleh menambahkan siswa
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Akses ditolak!');
}

$error = '';

$nis = '';
$nisn = '';
$nama = '';
$jenis_kelamin = '';
$tanggal_lahir = '';
$alamat = '';
$status_aktif = '1';

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nis = trim($_POST['nis'] ?? '');
    $nisn = trim($_POST['nisn'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $alamat = trim($_POST['alamat'] ?? '');
    $status_aktif = (string) ($_POST['status_aktif'] ?? '1');

    // Validasi
    if (
        $nisn === '' ||
        $nama === '' ||
        $jenis_kelamin === '' ||
        $tanggal_lahir === '' ||
        $alamat === ''
    ) {
        $error = 'Semua kolom wajib diisi, kecuali NIS.';

    } elseif (!in_array($jenis_kelamin, ['Laki-laki', 'Perempuan'], true)) {
        $error = 'Jenis kelamin yang dipilih tidak valid.';

    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = 'Status siswa tidak valid.';

    } elseif ($tanggal_lahir > date('Y-m-d')) {
        $error = 'Tanggal lahir tidak boleh melebihi tanggal hari ini.';

    } else {

        // Periksa NISN
        $cek_nisn = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_siswa WHERE nisn = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($cek_nisn, 's', $nisn);
        mysqli_stmt_execute($cek_nisn);
        mysqli_stmt_store_result($cek_nisn);

        if (mysqli_stmt_num_rows($cek_nisn) > 0) {
            $error = 'NISN sudah terdaftar. Gunakan NISN yang berbeda.';
        }

        mysqli_stmt_close($cek_nisn);

        // Periksa NIS jika diisi
        if ($error === '' && $nis !== '') {
            $cek_nis = mysqli_prepare(
                $koneksi,
                "SELECT id FROM t_siswa WHERE nis = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($cek_nis, 's', $nis);
            mysqli_stmt_execute($cek_nis);
            mysqli_stmt_store_result($cek_nis);

            if (mysqli_stmt_num_rows($cek_nis) > 0) {
                $error = 'NIS sudah digunakan. Gunakan NIS yang berbeda.';
            }

            mysqli_stmt_close($cek_nis);
        }

        // Simpan data siswa
        if ($error === '') {

            $status = (int) $status_aktif;

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO t_siswa
                (nis, nisn, nama, jenis_kelamin, tanggal_lahir, alamat, status_aktif)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $error = 'Gagal menyiapkan data siswa.';
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssssssi',
                    $nis,
                    $nisn,
                    $nama,
                    $jenis_kelamin,
                    $tanggal_lahir,
                    $alamat,
                    $status
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $error = 'Gagal menyimpan data siswa.';
                } else {
                    header('Location: kelola_siswa.php?pesan=sukses');
                    exit;
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Fungsi keamanan output
function e($nilai)
{
    return htmlspecialchars(
        (string) $nilai,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Siswa - My Website</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
    >

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* SIDEBAR */
        .sidebar {
            background: #172554;
            color: white;
            min-height: 100vh;
            padding: 22px 15px;
        }

        .sidebar h4 {
            font-weight: bold;
            margin-bottom: 30px;
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

        /* KONTEN */
        .content {
            padding: 28px;
        }

        .page-title {
            color: #172554;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .page-description {
            color: #64748b;
            margin-bottom: 20px;
        }

        .card-main {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        /* FORM */
        .form-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            margin-top: 20px;
        }

        .form-card h5 {
            color: #172554;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .required {
            color: red;
        }

        .form-control {
            border-radius: 6px;
            min-height: 40px;
        }

        .form-actions {
            margin-top: 25px;
        }

        /* TOMBOL */
        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background: #2445b8;
            border-color: #2445b8;
        }

        footer {
            color: #64748b;
            font-size: 13px;
            margin-top: 30px;
        }

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
            }

            .content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-3 col-lg-2 sidebar">

            <h4>My Website</h4>

            <ul class="nav nav-pills flex-column">

                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        &#8962; Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_siswa.php" class="nav-link active">
                        ♟ Data Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_guru.php" class="nav-link">
                        ♟ Data Guru
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kelas.php" class="nav-link">
                        &#9638; Data Kelas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_tahun_ajaran.php" class="nav-link">
                        &#9719; Kelola Tahun Ajaran
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_wali_kelas.php" class="nav-link">
                        &#9817; Kelola Wali Kelas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_penempatan_siswa.php" class="nav-link">
                        &#8644; Penempatan Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kategori_pelanggaran.php" class="nav-link">
                        &#9776; Kategori Pelanggaran
                    </a>
                </li>

                <li class="nav-item">
                    <a href="tentang_saya.php" class="nav-link">
                        &#8505; About Me
                    </a>
                </li>

                <hr style="border-color: #475569;">

                <li class="nav-item">
                    <a href="logout.php"
                       class="nav-link logout"
                       onclick="return confirm('Yakin ingin logout?')">
                        &#10140; Logout
                    </a>
                </li>

            </ul>
        </aside>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 content">

            <h2 class="page-title">Tambah Siswa</h2>

            <p class="page-description">
                Tambahkan data siswa baru ke dalam sistem.
            </p>

            <!-- PESAN ERROR -->
            <?php if ($error !== '') { ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= e($error) ?>

                    <button
                        type="button"
                        class="close"
                        data-dismiss="alert"
                        aria-label="Tutup"
                    >
                        <span>&times;</span>
                    </button>
                </div>
            <?php } ?>

            <!-- FORM CARD -->
            <div class="form-card">

                <h5>Formulir Data Siswa</h5>

                <p class="subtitle">
                    Lengkapi informasi siswa pada kolom berikut.
                    Kolom bertanda
                    <span class="required">*</span>
                    wajib diisi.
                </p>

                <form method="POST" action="">

                    <!-- NIS -->
                    <div class="form-group">
                        <label for="nis">
                            NIS
                        </label>

                        <input
                            type="text"
                            name="nis"
                            id="nis"
                            class="form-control"
                            placeholder="Masukkan NIS siswa"
                            value="<?= e($nis) ?>"
                            maxlength="10"
                        >

                        <small class="form-text text-muted">
                            NIS boleh dikosongkan jika belum tersedia.
                        </small>
                    </div>

                    <!-- NISN -->
                    <div class="form-group">
                        <label for="nisn">
                            NISN <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="nisn"
                            id="nisn"
                            class="form-control"
                            placeholder="Masukkan NISN siswa"
                            value="<?= e($nisn) ?>"
                            maxlength="10"
                            required
                        >
                    </div>

                    <!-- NAMA -->
                    <div class="form-group">
                        <label for="nama">
                            Nama Lengkap <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama"
                            id="nama"
                            class="form-control"
                            placeholder="Masukkan nama lengkap siswa"
                            value="<?= e($nama) ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <!-- JENIS KELAMIN -->
                    <div class="form-group">
                        <label for="jenis_kelamin">
                            Jenis Kelamin <span class="required">*</span>
                        </label>

                        <select
                            name="jenis_kelamin"
                            id="jenis_kelamin"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Jenis Kelamin --</option>

                            <option value="Laki-laki"
                                <?= $jenis_kelamin === 'Laki-laki' ? 'selected' : '' ?>>
                                Laki-laki
                            </option>

                            <option value="Perempuan"
                                <?= $jenis_kelamin === 'Perempuan' ? 'selected' : '' ?>>
                                Perempuan
                            </option>
                        </select>
                    </div>

                    <!-- TANGGAL LAHIR -->
                    <div class="form-group">
                        <label for="tanggal_lahir">
                            Tanggal Lahir <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_lahir"
                            id="tanggal_lahir"
                            class="form-control"
                            value="<?= e($tanggal_lahir) ?>"
                            max="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <!-- ALAMAT -->
                    <div class="form-group">
                        <label for="alamat">
                            Alamat <span class="required">*</span>
                        </label>

                        <textarea
                            name="alamat"
                            id="alamat"
                            class="form-control"
                            placeholder="Masukkan alamat lengkap siswa"
                            required
                        ><?= e($alamat) ?></textarea>
                    </div>

                    <!-- STATUS -->
                    <div class="form-group">
                        <label for="status_aktif">Status Siswa</label>

                        <select
                            name="status_aktif"
                            id="status_aktif"
                            class="form-control"
                            required
                        >
                            <option value="1"
                                <?= $status_aktif === '1' ? 'selected' : '' ?>>
                                Aktif
                            </option>

                            <option value="0"
                                <?= $status_aktif === '0' ? 'selected' : '' ?>>
                                Tidak Aktif
                            </option>
                        </select>
                    </div>

                    <!-- TOMBOL -->
                    <div class="form-actions d-flex flex-wrap">

                        <button
                            type="submit"
                            class="btn btn-primary mr-2 mb-2"
                        >
                            &#10003; Simpan Data
                        </button>

                        <a
                            href="kelola_siswa.php"
                            class="btn btn-secondary mb-2"
                        >
                            Kembali
                        </a>

                    </div>

                </form>

            </div>

            <footer>
                &copy; <?= date('Y') ?> My Website. All rights reserved.
            </footer>

        </main>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>