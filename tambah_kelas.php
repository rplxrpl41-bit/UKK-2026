
<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

$error = '';
$nama = '';
$tingkat = '';
$jurusan = '';
$status_aktif = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $tingkat = trim($_POST['tingkat'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $status_aktif = (int) ($_POST['status_aktif'] ?? 1);

    if ($nama === '' || $tingkat === '' || $jurusan === '') {
        $error = "Semua data wajib diisi.";
    } else {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO t_kelas
             (nama, tingkat, jurusan, status_aktif)
             VALUES (?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $nama,
                $tingkat,
                $jurusan,
                $status_aktif
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                header('Location: kelola_kelas.php');
                exit;
            } else {
                $error = "Gagal menyimpan data kelas.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Terjadi kesalahan saat menyiapkan query.";
        }
    }
}

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

    <title>Tambah Kelas - My Website</title>

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

        /* SIDEBAR - TETAP SEPERTI CONTOH */
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
            min-width: 0;
        }

        .page-title {
            color: #172554;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .page-description {
            color: #64748b;
        }

        /* KARTU */
        .card-main {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        /* TABEL */
        .table-responsive {
            overflow-x: auto;
        }

        .table thead th {
            background: #172554;
            color: white;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table td {
            vertical-align: middle;
        }

        /* STATUS */
        .badge-status {
            padding: 7px 10px;
            border-radius: 5px;
            display: inline-block;
            white-space: nowrap;
        }

        .status-aktif {
            background: #d1fae5;
            color: #065f46;
        }

        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
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

        .dataTables_wrapper {
            padding-top: 8px;
        }

        .dataTables_filter input,
        .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 5px 8px;
        }

        .dataTables_info {
            color: #64748b;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            white-space: nowrap;
        }

        @media (max-width: 767px) 
            .sidebar {
                min-height: auto;
            }

            .content {
                padding: 15px;
            }
        .main-content {
            padding: 28px;
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

        .quick-link .quick-icon {
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

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
            }

            .main-content {
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

      
        <aside class="col-md-3 col-lg-2 sidebar">

            <h4>My Website</h4>

            <ul class="nav nav-pills flex-column">

                <li>
                    <a href="dashboard.php" class="nav-link">
                        &#8962; Dashboard
                    </a>
                </li>

                <li>
                    <a href="kelola_siswa.php" class="nav-link">
                         ♟ Data Siswa
                    </a>
                </li>

                <li>
                    <a href="kelola_guru.php" class="nav-link">
                         ♟ Data Guru
                    </a>
                </li>

                <li>
                    <a href="kelola_kelas.php" class="nav-link active">
                        &#9638; Data Kelas
                    </a>
                </li>

                <li>
                    <a href="kelola_tahun_ajaran.php" class="nav-link">
                        &#9719; Kelola Tahun Ajaran
                    </a>
                </li>

                <li>
                    <a href="kelola_wali_kelas.php" class="nav-link">
                        &#9817; Kelola Wali Kelas
                    </a>
                </li>

                <li>
                    <a href="kelola_penempatan_siswa.php" class="nav-link">
                        &#8644; Penempatan Siswa
                    </a>
                </li>

                <li>
                    <a href="kelola_kategori_pelanggaran.php" class="nav-link">
                        &#9776; Kategori Pelanggaran
                    </a>
                </li>

                <li>
                    <a href="tentang_saya.php" class="nav-link">
                        &#8505; About Me
                    </a>
                </li>

                <hr style="border-color: #475569;">

                <li>
                    <a
                        href="logout.php"
                        class="nav-link logout"
                        onclick="return confirm('Yakin ingin logout?')"
                    >
                        &#10140; Logout
                    </a>
                </li>

            </ul>
        </aside>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 content">

            <h2 class="page-title">Tambah Kelas</h2>

            <p class="page-description">
                Tambahkan data kelas baru ke dalam sistem.
            </p>

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

            <div class="form-card">

                <h5>Formulir Data Kelas</h5>

                <p class="subtitle">
                    Lengkapi informasi kelas pada kolom berikut.
                    Kolom bertanda <span class="required">*</span> wajib diisi.
                </p>

                <form method="POST" action="">

                    <div class="form-group">
                        <label for="nama">
                            Nama Kelas <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama"
                            id="nama"
                            class="form-control"
                            placeholder="Contoh: XI RPL 1"
                            value="<?= e($nama) ?>"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="tingkat">
                            Tingkat <span class="required">*</span>
                        </label>

                        <select
                            name="tingkat"
                            id="tingkat"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Tingkat --</option>

                            <option value="X"
                                <?= $tingkat === 'X' ? 'selected' : '' ?>>
                                X
                            </option>

                            <option value="XI"
                                <?= $tingkat === 'XI' ? 'selected' : '' ?>>
                                XI
                            </option>

                            <option value="XII"
                                <?= $tingkat === 'XII' ? 'selected' : '' ?>>
                                XII
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="jurusan">
                            Jurusan <span class="required">*</span>
                        </label>

                        <select
                            name="jurusan"
                            id="jurusan"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Jurusan --</option>

                            <option value="RPL"
                                <?= $jurusan === 'RPL' ? 'selected' : '' ?>>
                                RPL
                            </option>

                            <option value="TKJ"
                                <?= $jurusan === 'TKJ' ? 'selected' : '' ?>>
                                TKJ
                            </option>

                            <option value="TKR"
                                <?= $jurusan === 'TKR' ? 'selected' : '' ?>>
                                TKR
                            </option>

                            <option value="BD"
                                <?= $jurusan === 'BD' ? 'selected' : '' ?>>
                                BD
                            </option>

                            <option value="TSM"
                                <?= $jurusan === 'TSM' ? 'selected' : '' ?>>
                                TSM
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_aktif">Status Kelas</label>

                        <select
                            name="status_aktif"
                            id="status_aktif"
                            class="form-control"
                            required
                        >
                            <option value="1"
                                <?= $status_aktif === 1 || $status_aktif === '1'
                                    ? 'selected' : '' ?>>
                                Aktif
                            </option>

                            <option value="0"
                                <?= $status_aktif === 0 || $status_aktif === '0'
                                    ? 'selected' : '' ?>>
                                Tidak Aktif
                            </option>
                        </select>
                    </div>

                    <div class="form-actions d-flex flex-wrap">

                        <button type="submit" class="btn btn-primary mr-2 mb-2">
                            &#10003; Simpan Data
                        </button>

                        <a
                            href="kelola_kelas.php"
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
