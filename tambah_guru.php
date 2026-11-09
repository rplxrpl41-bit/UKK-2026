<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

// Hanya admin yang boleh menambahkan guru
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Akses ditolak!');
}

$error = '';

$nip = '';
$nama = '';
$email = '';
$status_aktif = '1';

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    $status_aktif = (string) ($_POST['status_aktif'] ?? '1');

    // Validasi
    if (
        $nip === '' ||
        $nama === '' ||
        $email === '' ||
        $password === '' ||
        $konfirmasi_password === ''
    ) {
        $error = 'Semua kolom wajib diisi.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';

    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';

    } elseif ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak cocok.';

    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = 'Status guru tidak valid.';

    } else {

        // Periksa NIP
        $cek_nip = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_guru WHERE nip = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($cek_nip, 's', $nip);
        mysqli_stmt_execute($cek_nip);
        mysqli_stmt_store_result($cek_nip);

        if (mysqli_stmt_num_rows($cek_nip) > 0) {
            $error = 'NIP sudah digunakan.';
        }

        mysqli_stmt_close($cek_nip);

        // Periksa email di tabel users dan guru
        if ($error === '') {

            $cek_email = mysqli_prepare(
                $koneksi,
                "SELECT id FROM t_users WHERE email = ?
                 UNION
                 SELECT id FROM t_guru WHERE email = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $cek_email,
                'ss',
                $email,
                $email
            );

            mysqli_stmt_execute($cek_email);
            mysqli_stmt_store_result($cek_email);

            if (mysqli_stmt_num_rows($cek_email) > 0) {
                $error = 'Email sudah digunakan.';
            }

            mysqli_stmt_close($cek_email);
        }

        // Simpan akun dan data guru
        if ($error === '') {

            mysqli_begin_transaction($koneksi);

            try {

                // Enkripsi password
                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $status = (int) $status_aktif;

                // 1. Simpan akun ke t_users
                $stmt_user = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO t_users
                    (name, email, password, role)
                    VALUES (?, ?, ?, 'guru')"
                );

                if (!$stmt_user) {
                    throw new Exception('Gagal menyiapkan akun pengguna.');
                }

                mysqli_stmt_bind_param(
                    $stmt_user,
                    'sss',
                    $nama,
                    $email,
                    $password_hash
                );

                if (!mysqli_stmt_execute($stmt_user)) {
                    throw new Exception('Gagal membuat akun guru.');
                }

                // Ambil ID akun yang baru dibuat
                $user_id = mysqli_insert_id($koneksi);

                mysqli_stmt_close($stmt_user);

                // 2. Simpan data ke t_guru
                $stmt_guru = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO t_guru
                    (nip, nama, email, status_aktif, user_id)
                    VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmt_guru) {
                    throw new Exception('Gagal menyiapkan data guru.');
                }

                mysqli_stmt_bind_param(
                    $stmt_guru,
                    'sssii',
                    $nip,
                    $nama,
                    $email,
                    $status,
                    $user_id
                );

                if (!mysqli_stmt_execute($stmt_guru)) {
                    throw new Exception('Gagal menyimpan data guru.');
                }

                mysqli_stmt_close($stmt_guru);

                // Simpan transaksi
                mysqli_commit($koneksi);

                header('Location: kelola_guru.php?pesan=sukses');
                exit;

            } catch (Exception $e) {

                mysqli_rollback($koneksi);

                $error = $e->getMessage();
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

    <title>Tambah Guru - My Website</title>

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
                    <a href="kelola_siswa.php" class="nav-link">
                        ♟ Data Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_guru.php" class="nav-link active">
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

            <h2 class="page-title">Tambah Guru</h2>

            <p class="page-description">
                Tambahkan data guru baru ke dalam sistem.
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

                <h5>Formulir Data Guru</h5>

                <p class="subtitle">
                    Lengkapi informasi guru pada kolom berikut.
                    Kolom bertanda
                    <span class="required">*</span>
                    wajib diisi.
                </p>

                <form method="POST" action="">

                    <!-- NIP -->
                    <div class="form-group">
                        <label for="nip">
                            NIP <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="nip"
                            id="nip"
                            class="form-control"
                            placeholder="Masukkan NIP guru"
                            value="<?= e($nip) ?>"
                            maxlength="18"
                            required
                        >
                    </div>

                    <!-- NAMA -->
                    <div class="form-group">
                        <label for="nama">
                            Nama Lengkap
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama"
                            id="nama"
                            class="form-control"
                            placeholder="Masukkan nama lengkap guru"
                            value="<?= e($nama) ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <!-- EMAIL -->
                    <div class="form-group">
                        <label for="email">
                            Email <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            placeholder="contoh@email.com"
                            value="<?= e($email) ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <!-- PASSWORD -->
                    <div class="form-group">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Masukkan password minimal 8 karakter"
                            minlength="8"
                            required
                        >

                        <small class="form-text text-muted">
                            Password minimal 8 karakter.
                        </small>
                    </div>

                    <!-- KONFIRMASI PASSWORD -->
                    <div class="form-group">
                        <label for="konfirmasi_password">
                            Konfirmasi Password
                            <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            name="konfirmasi_password"
                            id="konfirmasi_password"
                            class="form-control"
                            placeholder="Masukkan ulang password"
                            minlength="8"
                            required
                        >
                    </div>

                    <!-- STATUS -->
                    <div class="form-group">
                        <label for="status_aktif">
                            Status Guru
                        </label>

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
                            href="kelola_guru.php"
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