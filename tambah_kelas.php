
<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Akses ditolak!');
}

$error = '';
$tingkat = '';
$jurusan = '';
$status_aktif = '1';

function e($nilai) {
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tingkat = trim($_POST['tingkat'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $status_aktif = (string)($_POST['status_aktif'] ?? '1');

    $daftar_tingkat = ['X', 'XI', 'XII'];
    $daftar_jurusan = ['RPL', 'TKJ', 'TKR', 'BD', 'TSM'];

    if (!in_array($tingkat, $daftar_tingkat, true) ||
        !in_array($jurusan, $daftar_jurusan, true)) {
        $error = 'Tingkat atau jurusan tidak valid.';
    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = 'Status kelas tidak valid.';
    } else {
        // Nama kelas dibuat otomatis.
        $nama = $jurusan;
        $status = (int)$status_aktif;

        // Cek agar kelas yang sama tidak dibuat dua kali.
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_kelas
             WHERE nama = ? AND tingkat = ? AND jurusan = ?
             LIMIT 1"
        );

        if (!$cek) {
            $error = 'Gagal memeriksa data kelas.';
        } else {
            mysqli_stmt_bind_param($cek, 'sss', $nama, $tingkat, $jurusan);
            mysqli_stmt_execute($cek);
            $hasil_cek = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($hasil_cek) > 0) {
                $error = 'Kelas tersebut sudah terdaftar.';
            } else {
                $stmt = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO t_kelas
                     (nama, tingkat, jurusan, status_aktif)
                     VALUES (?, ?, ?, ?)"
                );

                if (!$stmt) {
                    $error = 'Gagal menyiapkan penyimpanan kelas.';
                } else {
                    mysqli_stmt_bind_param(
                        $stmt,
                        'sssi',
                        $nama,
                        $tingkat,
                        $jurusan,
                        $status
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        header('Location: kelola_kelas.php?pesan=sukses');
                        exit;
                    } else {
                        $error = 'Gagal menyimpan data kelas.';
                    }

                    mysqli_stmt_close($stmt);
                }
            }

            mysqli_stmt_close($cek);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelas - My Website</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
        }

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

        .form-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
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

        <aside class="col-md-3 col-lg-2 sidebar">
            <h4>My Website</h4>

            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        &#8962; Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_siswa.php" class="nav-link">
                        ♟ Data Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_guru.php" class="nav-link">
                        ♟ Data Guru
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kelas.php" class="nav-link active">
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
                    <a href="logout.php" class="nav-link logout"
                       onclick="return confirm('Yakin ingin logout?')">
                        &#10140; Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="col-md-9 col-lg-10 content">

            <h2 class="page-title">Tambah Kelas</h2>

            <p class="page-description">
                Tambahkan data kelas baru ke dalam sistem.
            </p>

            <?php if ($error !== '') { ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= e($error) ?>

                    <button type="button" class="close"
                            data-dismiss="alert" aria-label="Tutup">
                        <span>&times;</span>
                    </button>
                </div>
            <?php } ?>

            <div class="form-card">

                <h5>Formulir Data Kelas</h5>

                <p class="subtitle">
                    Lengkapi informasi kelas pada kolom berikut.
                    Kolom bertanda
                    <span class="required">*</span>
                    wajib diisi.
                </p>

                <form method="POST" action="">

                    <div class="form-group">
                        <label for="tingkat">
                            Tingkat <span class="required">*</span>
                        </label>

                        <select name="tingkat" id="tingkat"
                                class="form-control" required>
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

                        <select name="jurusan" id="jurusan"
                                class="form-control" required>
                            <option value="">-- Pilih Jurusan --</option>

                            <?php
                            $jurusan_list = ['RPL', 'TKJ', 'TKR', 'BD', 'TSM'];
                            foreach ($jurusan_list as $item) {
                            ?>
                                <option value="<?= e($item) ?>"
                                    <?= $jurusan === $item ? 'selected' : '' ?>>
                                    <?= e($item) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status_aktif">Status Kelas</label>

                        <select name="status_aktif" id="status_aktif"
                                class="form-control" required>
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

                    <div class="form-actions d-flex flex-wrap">
                        <button type="submit"
                                class="btn btn-primary mr-2 mb-2">
                            &#10003; Simpan Data
                        </button>

                        <a href="kelola_kelas.php"
                           class="btn btn-secondary mb-2">
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
