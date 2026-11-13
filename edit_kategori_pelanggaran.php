
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
// AMBIL ID KATEGORI
// =====================================
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['pesan'] = "ID kategori tidak valid!";
    header("Location: kelola_kategori_pelanggaran.php");
    exit;
}

// =====================================
// AMBIL DATA KATEGORI
// =====================================
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM t_pelanggaran_kategori WHERE id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['pesan'] = "Data kategori tidak ditemukan!";
    header("Location: kelola_kategori_pelanggaran.php");
    exit;
}

// =====================================
// PROSES UPDATE
// =====================================
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status_aktif = (string)($_POST['status_aktif'] ?? '1');

    if ($nama === '') {
        $error = "Nama kategori wajib diisi.";

    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = "Status kategori tidak valid.";

    } else {

        // Cek nama kategori duplikat
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id
             FROM t_pelanggaran_kategori
             WHERE nama = ? AND id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($cek, "si", $nama, $id);
        mysqli_stmt_execute($cek);

        $hasil_cek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil_cek) > 0) {
            $error = "Nama kategori tersebut sudah digunakan.";
        }

        mysqli_stmt_close($cek);
    }

    if ($error === '') {

        $status = (int)$status_aktif;

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE t_pelanggaran_kategori SET
                nama = ?,
                deskripsi = ?,
                status_aktif = ?,
                updated_at = NOW()
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssii",
            $nama,
            $deskripsi,
            $status,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            $_SESSION['pesan'] = "Data kategori berhasil diperbarui!";

            header("Location: kelola_kategori_pelanggaran.php?pesan=edit_berhasil");
            exit;

        } else {

            $error = "Gagal memperbarui kategori: " . mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);
        }
    }

    // Pertahankan isian jika terjadi kesalahan
    $data['nama'] = $nama;
    $data['deskripsi'] = $deskripsi;
    $data['status_aktif'] = $status_aktif;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Kategori Pelanggaran</title>

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
                    <a href="kelola_penempatan_siswa.php" class="nav-link">
                        &#8644; Penempatan Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kategori_pelanggaran.php" class="nav-link active">
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
                <h2 class="page-title">Edit Kategori Pelanggaran</h2>

                <p class="text-muted">
                    Perbarui nama, deskripsi, dan status kategori pelanggaran.
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="card card-main">

                <div class="card-header">
                    Form Edit Kategori Pelanggaran
                </div>

                <div class="card-body p-4">

                    <form method="POST"
                          action="edit_kategori_pelanggaran.php?id=<?= $id ?>">

                        <input type="hidden" name="id" value="<?= $id ?>">

                        <h5 class="section-title">
                            Informasi Kategori
                        </h5>

                        <div class="form-group">
                            <label for="nama">Nama Kategori</label>

                            <input
                                type="text"
                                name="nama"
                                id="nama"
                                class="form-control"
                                value="<?= e($data['nama']) ?>"
                                placeholder="Masukkan nama kategori"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>

                            <textarea
                                name="deskripsi"
                                id="deskripsi"
                                rows="4"
                                class="form-control"
                                placeholder="Masukkan deskripsi kategori"
                            ><?= e($data['deskripsi']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status_aktif">Status Aktif</label>

                            <select
                                name="status_aktif"
                                id="status_aktif"
                                class="form-control"
                                required
                            >
                                <option value="1"
                                    <?= (string)$data['status_aktif'] === '1' ? 'selected' : '' ?>>
                                    Aktif
                                </option>

                                <option value="0"
                                    <?= (string)$data['status_aktif'] === '0' ? 'selected' : '' ?>>
                                    Tidak Aktif
                                </option>
                            </select>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>

                        <a href="kelola_kategori_pelanggaran.php"
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
