
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// =====================================
// CEK HAK AKSES
// =====================================
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak!");
}

// =====================================
// FUNGSI
// =====================================
function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// =====================================
// AMBIL ID PENEMPATAN
// =====================================
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['pesan'] = "ID penempatan tidak valid!";
    header("Location: kelola_penempatan_siswa.php");
    exit;
}

// =====================================
// AMBIL DATA PENEMPATAN
// =====================================
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM t_kelas_siswa WHERE id = ? LIMIT 1"
);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['pesan'] = "Data penempatan tidak ditemukan!";
    header("Location: kelola_penempatan_siswa.php");
    exit;
}

// =====================================
// AMBIL DATA SISWA
// =====================================
$querySiswa = mysqli_query(
    $koneksi,
    "SELECT id, nis, nama
     FROM t_siswa
     ORDER BY nama ASC"
);

// =====================================
// AMBIL DATA KELAS
// =====================================
$kelasId = (int)$data['kelas_id'];

$queryKelas = mysqli_query(
    $koneksi,
    "SELECT id, nama, tingkat, jurusan
     FROM t_kelas
     WHERE status_aktif = 1 OR id = $kelasId
     ORDER BY FIELD(tingkat, 'X', 'XI', 'XII'), jurusan, nama"
);

// =====================================
// AMBIL DATA TAHUN AJARAN
// =====================================
$queryTahun = mysqli_query(
    $koneksi,
    "SELECT id, nama
     FROM t_tahun_ajaran
     ORDER BY id DESC"
);

if (!$querySiswa || !$queryKelas || !$queryTahun) {
    die("Gagal mengambil data pilihan: " . mysqli_error($koneksi));
}

// =====================================
// FORMAT TANGGAL SELESAI
// =====================================
$tanggalSelesai = $data['tanggal_selesai'] ?? '';

if (
    $tanggalSelesai === '0000-00-00' ||
    $tanggalSelesai === '9999-12-31'
) {
    $tanggalSelesai = '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Penempatan Siswa</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

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

        .card-header {
            background: white;
            color: #172554;
            font-weight: bold;
            padding: 18px 22px;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 12px 12px 0 0 !important;
        }

        .section-title {
            color: #172554;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            border-radius: 6px;
            padding: 10px 12px;
        }

        .form-control:focus {
            border-color: #3157d5;
            box-shadow: 0 0 0 0.15rem rgba(49, 87, 213, 0.15);
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

        /* FOOTER */
        .footer {
            color: #64748b;
            font-size: 13px;
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
                    <a href="kelola_wali_kelas.php" class="nav-link">
                        &#9817; Kelola Wali Kelas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_penempatan_siswa.php" class="nav-link active">
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

            <div class="mb-4">
                <h2 class="page-title">Edit Penempatan Siswa</h2>

                <p class="text-muted">
                    Perbarui kelas, tahun ajaran, dan masa penempatan siswa.
                </p>
            </div>

            <?php if (!empty($_SESSION['pesan'])): ?>
                <div class="alert alert-danger">
                    <?= e($_SESSION['pesan']) ?>
                </div>
                <?php unset($_SESSION['pesan']); ?>
            <?php endif; ?>

            <div class="card card-main">

                <div class="card-header">
                    Form Edit Penempatan Siswa
                </div>

                <div class="card-body p-4">

                    <form action="proses_edit_kelas.php" method="POST">

                        <input type="hidden"
                               name="id"
                               value="<?= (int)$data['id'] ?>">

                        <input type="hidden"
                               name="redirect"
                               value="kelola_penempatan_siswa.php">

                        <h5 class="section-title">
                            Informasi Penempatan
                        </h5>

                        <!-- SISWA -->
                        <div class="form-group">
                            <label for="siswa_id">Nama Siswa</label>

                            <select name="siswa_id"
                                    id="siswa_id"
                                    class="form-control"
                                    required>

                                <option value="">-- Pilih Siswa --</option>

                                <?php while ($siswa = mysqli_fetch_assoc($querySiswa)): ?>

                                    <option
                                        value="<?= (int)$siswa['id'] ?>"
                                        <?= (int)$data['siswa_id'] === (int)$siswa['id'] ? 'selected' : '' ?>>

                                        <?= e(trim(($siswa['nis'] ?? '') . ' - ' . ($siswa['nama'] ?? ''), ' -')) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                        <!-- KELAS -->
                        <div class="form-group">
                            <label for="kelas_id">Kelas</label>

                            <select name="kelas_id"
                                    id="kelas_id"
                                    class="form-control"
                                    required>

                                <option value="">-- Pilih Kelas --</option>

                                <?php while ($kelas = mysqli_fetch_assoc($queryKelas)): ?>

                                    <option
                                        value="<?= (int)$kelas['id'] ?>"
                                        <?= (int)$data['kelas_id'] === (int)$kelas['id'] ? 'selected' : '' ?>>

                                        <?= e($kelas['tingkat']) ?>
                                        <?= e($kelas['jurusan']) ?>
                                        (<?= e($kelas['nama']) ?>)

                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                        <!-- TAHUN AJARAN -->
                        <div class="form-group">
                            <label for="tahun_ajaran_id">Tahun Ajaran</label>

                            <select name="tahun_ajaran_id"
                                    id="tahun_ajaran_id"
                                    class="form-control"
                                    required>

                                <option value="">-- Pilih Tahun Ajaran --</option>

                                <?php while ($tahun = mysqli_fetch_assoc($queryTahun)): ?>

                                    <option
                                        value="<?= (int)$tahun['id'] ?>"
                                        <?= (int)$data['tahun_ajaran_id'] === (int)$tahun['id'] ? 'selected' : '' ?>>

                                        <?= e($tahun['nama']) ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>
                        </div>

                        <hr>

                        <h5 class="section-title">
                            Masa Penempatan
                        </h5>

                        <div class="form-row">

                            <div class="form-group col-md-6">
                                <label for="tanggal_mulai">
                                    Tanggal Mulai
                                </label>

                                <input
                                    type="date"
                                    name="tanggal_mulai"
                                    id="tanggal_mulai"
                                    class="form-control"
                                    value="<?= e($data['tanggal_mulai']) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group col-md-6">
                                <label for="tanggal_selesai">
                                    Tanggal Selesai
                                </label>

                                <input
                                    type="date"
                                    name="tanggal_selesai"
                                    id="tanggal_selesai"
                                    class="form-control"
                                    value="<?= e($tanggalSelesai) ?>"
                                >

                                <small class="text-muted">
                                    Kosongkan jika masih berlangsung.
                                </small>
                            </div>

                        </div>

                        <!-- STATUS -->
                        <div class="form-group">
                            <label for="status_aktif">
                                Status Aktif
                            </label>

                            <select name="status_aktif"
                                    id="status_aktif"
                                    class="form-control"
                                    required>

                                <option value="1"
                                    <?= (int)$data['status_aktif'] === 1 ? 'selected' : '' ?>>
                                    Aktif
                                </option>

                                <option value="0"
                                    <?= (int)$data['status_aktif'] === 0 ? 'selected' : '' ?>>
                                    Tidak Aktif
                                </option>

                            </select>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>

                        <a href="kelola_penempatan_siswa.php"
                           class="btn btn-secondary">
                            Batal
                        </a>

                    </form>

                </div>
            </div>

            <p class="footer text-center mt-4">
                &copy; <?= date('Y') ?> Sistem Informasi Pelanggaran Siswa
            </p>

        </main>

    </div>
</div>

</body>
</html>
