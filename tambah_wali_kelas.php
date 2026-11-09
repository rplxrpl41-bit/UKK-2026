<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

// Hanya admin yang boleh menambahkan wali kelas
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Akses ditolak!');
}

$error = '';

$tahun_ajaran_id = '';
$kelas_id = '';
$guru_id = '';
$tanggal_mulai = '';
$tanggal_selesai = '';
$status_aktif = '1';

// Mengambil pilihan dari database
$query_tahun = mysqli_query(
    $koneksi,
    "SELECT id, nama
     FROM t_tahun_ajaran
     ORDER BY id DESC"
);

$query_kelas = mysqli_query(
    $koneksi,
    "SELECT id, nama, tingkat, jurusan
     FROM t_kelas
     ORDER BY tingkat, jurusan, nama"
);

$query_guru = mysqli_query(
    $koneksi,
    "SELECT id, nama
     FROM t_guru
     ORDER BY nama ASC"
);

if (!$query_tahun || !$query_kelas || !$query_guru) {
    die(
        'Gagal mengambil data pilihan. Periksa struktur tabel ' .
        'tahun ajaran, kelas, dan guru.'
    );
}

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tahun_ajaran_id = (int) ($_POST['tahun_ajaran_id'] ?? 0);
    $kelas_id = (int) ($_POST['kelas_id'] ?? 0);
    $guru_id = (int) ($_POST['guru_id'] ?? 0);
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $status_aktif = (string) ($_POST['status_aktif'] ?? '1');

    // Validasi
    if (
        $tahun_ajaran_id <= 0 ||
        $kelas_id <= 0 ||
        $guru_id <= 0 ||
        $tanggal_mulai === '' ||
        $tanggal_selesai === ''
    ) {
        $error = 'Semua kolom wajib diisi.';

    } elseif (
        !in_array($status_aktif, ['0', '1'], true)
    ) {
        $error = 'Status yang dipilih tidak valid.';

    } elseif ($tanggal_mulai > $tanggal_selesai) {
        $error = 'Tanggal mulai tidak boleh melewati tanggal selesai.';

    } else {
        // Periksa apakah kelas sudah memiliki wali kelas
        // pada tahun ajaran yang dipilih.
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id
             FROM t_wali_kelas
             WHERE tahun_ajaran_id = ? AND kelas_id = ?
             LIMIT 1"
        );

        if (!$cek) {
            $error = 'Gagal memeriksa data wali kelas.';
        } else {
            mysqli_stmt_bind_param(
                $cek,
                "ii",
                $tahun_ajaran_id,
                $kelas_id
            );

            mysqli_stmt_execute($cek);
            $hasil_cek = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($hasil_cek) > 0) {
                $error = 'Kelas tersebut sudah memiliki data wali kelas pada tahun ajaran yang dipilih.';
            }

            mysqli_stmt_close($cek);
        }

        if ($error === '') {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO t_wali_kelas
                (
                    tahun_ajaran_id,
                    kelas_id,
                    guru_id,
                    tanggal_mulai,
                    tanggal_selesai,
                    status_aktif
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                $status = (int) $status_aktif;

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiissi",
                    $tahun_ajaran_id,
                    $kelas_id,
                    $guru_id,
                    $tanggal_mulai,
                    $tanggal_selesai,
                    $status
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);

                    header('Location: kelola_wali_kelas.php?pesan=sukses');
                    exit;
                } else {
                    $error = 'Gagal menyimpan data wali kelas.';
                }

                mysqli_stmt_close($stmt);
            } else {
                $error = 'Terjadi kesalahan saat menyiapkan query.';
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

    <title>Tambah Wali Kelas - My Website</title>

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
                    <a href="kelola_wali_kelas.php" class="nav-link active">
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

            <h2 class="page-title">Tambah Wali Kelas</h2>

            <p class="page-description">
                Tambahkan penugasan guru sebagai wali kelas berdasarkan
                tahun ajaran dan kelas.
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

                <h5>Formulir Data Wali Kelas</h5>

                <p class="subtitle">
                    Lengkapi informasi penugasan wali kelas.
                    Kolom bertanda
                    <span class="required">*</span>
                    wajib diisi.
                </p>

                <form method="POST" action="">

                    <!-- TAHUN AJARAN -->
                    <div class="form-group">
                        <label for="tahun_ajaran_id">
                            Tahun Ajaran
                            <span class="required">*</span>
                        </label>

                        <select
                            name="tahun_ajaran_id"
                            id="tahun_ajaran_id"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Tahun Ajaran --</option>

                            <?php while ($ta = mysqli_fetch_assoc($query_tahun)) { ?>
                                <option
                                    value="<?= (int) $ta['id'] ?>"
                                    <?= (string) $tahun_ajaran_id === (string) $ta['id']
                                        ? 'selected' : '' ?>>
                                    <?= e($ta['nama']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- KELAS -->
                    <div class="form-group">
                        <label for="kelas_id">
                            Kelas
                            <span class="required">*</span>
                        </label>

                        <select
                            name="kelas_id"
                            id="kelas_id"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Kelas --</option>

                            <?php while ($kelas = mysqli_fetch_assoc($query_kelas)) { ?>
                                <option
                                    value="<?= (int) $kelas['id'] ?>"
                                    <?= (string) $kelas_id === (string) $kelas['id']
                                        ? 'selected' : '' ?>>
                                    <?= e(
                                        $kelas['nama'] . ' - ' .
                                        $kelas['tingkat'] . ' - ' .
                                        $kelas['jurusan']
                                    ) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- GURU -->
                    <div class="form-group">
                        <label for="guru_id">
                            Guru Wali Kelas
                            <span class="required">*</span>
                        </label>

                        <select
                            name="guru_id"
                            id="guru_id"
                            class="form-control"
                            required
                        >
                            <option value="">-- Pilih Guru --</option>

                            <?php while ($guru = mysqli_fetch_assoc($query_guru)) { ?>
                                <option
                                    value="<?= (int) $guru['id'] ?>"
                                    <?= (string) $guru_id === (string) $guru['id']
                                        ? 'selected' : '' ?>>
                                    <?= e($guru['nama']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- TANGGAL MULAI -->
                    <div class="form-group">
                        <label for="tanggal_mulai">
                            Tanggal Mulai
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            id="tanggal_mulai"
                            class="form-control"
                            value="<?= e($tanggal_mulai) ?>"
                            required
                        >
                    </div>

                    <!-- TANGGAL SELESAI -->
                    <div class="form-group">
                        <label for="tanggal_selesai">
                            Tanggal Selesai
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_selesai"
                            id="tanggal_selesai"
                            class="form-control"
                            value="<?= e($tanggal_selesai) ?>"
                            min="<?= e($tanggal_mulai) ?>"
                            required
                        >
                    </div>

                    <!-- STATUS -->
                    <div class="form-group">
                        <label for="status_aktif">
                            Status Penugasan
                        </label>

                        <select
                            name="status_aktif"
                            id="status_aktif"
                            class="form-control"
                            required
                        >
                            <option
                                value="1"
                                <?= $status_aktif === '1' ? 'selected' : '' ?>>
                                Aktif
                            </option>

                            <option
                                value="0"
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
                            href="kelola_wali_kelas.php"
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

<script>
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');

    tanggalMulai.addEventListener('change', function () {
        tanggalSelesai.min = this.value;

        if (
            tanggalSelesai.value !== '' &&
            tanggalSelesai.value < this.value
        ) {
            tanggalSelesai.value = '';
        }
    });
</script>

</body>
</html>