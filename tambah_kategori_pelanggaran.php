```php
<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

$pesan = '';
$jenisPesan = '';

function e($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

$nama = '';
$deskripsi = '';
$status_aktif = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status_aktif = $_POST['status_aktif'] ?? '1';

    if ($nama === '') {
        $pesan = 'Nama kategori wajib diisi!';
        $jenisPesan = 'danger';
    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $pesan = 'Status kategori tidak valid!';
        $jenisPesan = 'danger';
    } else {
        // Cek apakah kategori sudah ada
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_kategori_pelanggaran WHERE nama = ? LIMIT 1"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $nama);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            $sudahAda = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($sudahAda) {
                $pesan = 'Kategori pelanggaran tersebut sudah ada!';
                $jenisPesan = 'danger';
            } else {
                $status = (int)$status_aktif;

                $stmt = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO t_kategori_pelanggaran
                    (nama, deskripsi, status_aktif)
                    VALUES (?, ?, ?)"
                );

                if ($stmt) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssi',
                        $nama,
                        $deskripsi,
                        $status
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        mysqli_stmt_close($stmt);
                        header('Location: kelola_kategori_pelanggaran.php?pesan=tambah_berhasil');
                        exit;
                    } else {
                        $pesan = 'Gagal menyimpan kategori. Periksa struktur tabel database.';
                        $jenisPesan = 'danger';
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $pesan = 'Query gagal disiapkan. Periksa nama kolom pada database.';
                    $jenisPesan = 'danger';
                }
            }
        } else {
            $pesan = 'Tabel atau kolom kategori tidak sesuai dengan kode.';
            $jenisPesan = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Kategori Pelanggaran</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: #172554;
            color: white;
            padding: 24px 15px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar h4 {
            text-align: center;
            font-weight: bold;
            margin-bottom: 30px;
        }

        .sidebar .nav-link {
            color: #dbeafe;
            padding: 11px 13px;
            margin-bottom: 5px;
            border-radius: 7px;
            font-size: 14px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: #3157d5;
            color: white;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .page-title {
            font-weight: bold;
            font-size: 25px;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        .form-card {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
            max-width: 850px;
        }

        .form-group label {
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            min-height: 43px;
            border-radius: 7px;
        }

        textarea.form-control {
            min-height: 120px;
        }

        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background: #2445b5;
            border-color: #2445b5;
        }

        .required {
            color: #dc2626;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }

            .content {
                margin-left: 0;
                padding: 18px;
            }

            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h4>My Website</h4>

    <ul class="nav nav-pills flex-column">
        <li class="nav-item">
            <a class="nav-link" href="dashboard.php">⌂ Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_siswa.php">♟ Data Siswa</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_guru.php">♟ Data Guru</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_kelas.php">▦ Data Kelas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_tahun_ajaran.php">◷ Kelola Tahun Ajaran</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_wali_kelas.php">♙ Kelola Wali Kelas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="kelola_penempatan_siswa.php">⇄ Penempatan Siswa</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="kelola_kategori_pelanggaran.php">
                ☷ Kategori Pelanggaran
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="tentang_saya.php">About Me</a>
        </li>

        <hr style="border-color: #475569;">

        <li class="nav-item">
            <a class="nav-link"
               href="logout.php"
               onclick="return confirm('Yakin ingin logout?')">
                ⇥ Logout
            </a>
        </li>
    </ul>
</aside>

<main class="content">
    <h1 class="page-title">Tambah Kategori Pelanggaran</h1>
    <p class="page-subtitle">
        Tambahkan kategori baru untuk mengelompokkan jenis pelanggaran siswa.
    </p>

    <div class="form-card">
        <h5 class="mb-4">Formulir Kategori Pelanggaran</h5>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-<?php echo e($jenisPesan); ?>">
                <?php echo e($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nama">
                    Nama Kategori <span class="required">*</span>
                </label>
                <input
                    type="text"
                    name="nama"
                    id="nama"
                    class="form-control"
                    placeholder="Contoh: Kedisiplinan"
                    maxlength="100"
                    value="<?php echo e($nama); ?>"
                    required
                >
                <small class="form-text text-muted">
                    Masukkan nama kategori pelanggaran.
                </small>
            </div>

            <div class="form-group">
                <label for="deskripsi">Deskripsi Kategori</label>
                <textarea
                    name="deskripsi"
                    id="deskripsi"
                    class="form-control"
                    placeholder="Tuliskan keterangan kategori (opsional)"
                ><?php echo e($deskripsi); ?></textarea>
            </div>

            <div class="form-group">
                <label for="status_aktif">Status Kategori</label>
                <select
                    name="status_aktif"
                    id="status_aktif"
                    class="form-control"
                    required
                >
                    <option value="1" <?php echo $status_aktif === '1' ? 'selected' : ''; ?>>
                        Aktif
                    </option>
                    <option value="0" <?php echo $status_aktif === '0' ? 'selected' : ''; ?>>
                        Tidak Aktif
                    </option>
                </select>
            </div>

            <hr>

            <div class="d-flex flex-wrap">
                <button type="submit" class="btn btn-primary mr-2 mb-2">
                    💾 Simpan Kategori
                </button>

                <a href="kelola_kategori_pelanggaran.php"
                   class="btn btn-secondary mb-2">
                    ↩ Kembali
                </a>
            </div>
        </form>
    </div>
</main>

</body>
</html>
```