

<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}

// Cek ID siswa
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['pesan'] = "ID siswa tidak valid!";
    header("Location: kelola_siswa.php");
    exit;
}

// Ambil data siswa
$stmt = mysqli_prepare($koneksi, "SELECT * FROM t_siswa WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

$siswa = mysqli_fetch_assoc($query);

if (!$siswa) {
    $_SESSION['pesan'] = "Data siswa tidak ditemukan!";
    header("Location: kelola_siswa.php");
    exit;
}

$namaUser = $_SESSION['name'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #172554;
            color: white;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .sidebar h4 {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
        }

        .user-info {
            text-align: center;
            font-size: 13px;
            color: #cbd5e1;
            margin-bottom: 25px;
        }

        .sidebar a {
            display: block;
            color: #e2e8f0;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 6px;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar a:hover {
            background: #263b70;
            color: white;
        }

        .sidebar a.active {
            background: #3157d5;
            color: white;
            font-weight: bold;
        }

        .sidebar a.logout {
            margin-top: 20px;
            background: #b91c1c;
            color: white;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h2 {
            color: #172554;
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .page-header p {
            color: #6b7280;
            margin: 0;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.06);
            max-width: 900px;
        }

        .form-card h5 {
            color: #172554;
            font-weight: bold;
            margin-bottom: 22px;
        }

        label {
            color: #172554;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ced4da;
            border-radius: 7px;
            min-height: 42px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-control:focus {
            border-color: #3157d5;
            box-shadow: 0 0 0 0.15rem rgba(49, 87, 213, 0.15);
        }

        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
            border-radius: 7px;
            padding: 9px 20px;
        }

        .btn-primary:hover {
            background: #2445b8;
            border-color: #2445b8;
        }

        .btn-secondary {
            border-radius: 7px;
            padding: 9px 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .content {
                margin-left: 0;
                padding: 20px;
            }

            .form-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">
    <h4>Sistem Pelanggaran</h4>

    <div class="user-info">
        <?= e($namaUser) ?> Â· <?= e(ucfirst($role)) ?>
    </div>

    <a href="dashboard.php">â–¦ Dashboard</a>
    <a href="kelola_siswa.php" class="active">â™™ Kelola Siswa</a>
    <a href="kelola_guru.php">â™™ Kelola Guru</a>
    <a href="kelola_kelas.php">â–£ Kelola Kelas</a>
    <a href="kelola_tahun_ajaran.php">â–¤ Tahun Ajaran</a>
    <a href="penempatan_siswa.php">â‡„ Penempatan Siswa</a>
    <a href="kelola_wali_kelas.php">â™§ Wali Kelas</a>
    <a href="kelola_kategori_pelanggaran.php">â–¤ Kategori Pelanggaran</a>
    <a href="menu3.php">âš  Jenis Pelanggaran</a>
    <a href="catatan.php">â˜· Catatan Pelanggaran</a>
    <a href="laporan.php">â–§ Laporan</a>

    <?php if ($role === 'admin'): ?>
        <a href="menu4.php">âš™ Pengguna/Admin</a>
    <?php endif; ?>

    <a href="logout.php" class="logout"
       onclick="return confirm('Yakin ingin keluar?')">â†ª Logout</a>
</div>

<div class="content">
    <div class="page-header">
        <h2>Edit Data Siswa</h2>
        <p>Perbarui informasi siswa pada sistem.</p>
    </div>

    <div class="form-card">
        <h5>Form Edit Siswa</h5>

        <form action="proses_edit_siswa.php" method="POST">
            <input type="hidden" name="id" value="<?= e($siswa['id']) ?>">

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="nis">NIS</label>
                    <input type="text" class="form-control"
                           id="nis" name="nis"
                           value="<?= e($siswa['nis'] ?? '') ?>" required>
                </div>

                <div class="form-group col-md-6">
                    <label for="nisn">NISN</label>
                    <input type="text" class="form-control"
                           id="nisn" name="nisn"
                           value="<?= e($siswa['nisn'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="nama">Nama Siswa</label>
                <input type="text" class="form-control"
                       id="nama" name="nama"
                       value="<?= e($siswa['nama'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select class="form-control" id="jenis_kelamin"
                            name="jenis_kelamin" required>
                        <option value="">-- Pilih Jenis Kelamin --</option>
                        <option value="L"
                            <?= ($siswa['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>
                            Laki-laki
                        </option>
                        <option value="P"
                            <?= ($siswa['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>
                            Perempuan
                        </option>
                    </select>
                </div>

                <div class="form-group col-md-6">
                    <label for="tanggal_lahir">Tanggal Lahir</label>
                    <input type="date" class="form-control"
                           id="tanggal_lahir" name="tanggal_lahir"
                           value="<?= e($siswa['tanggal_lahir'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea class="form-control" id="alamat"
                          name="alamat" required><?= e($siswa['alamat'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="status_aktif">Status Aktif</label>
                <select class="form-control" id="status_aktif"
                        name="status_aktif" required>
                    <option value="1"
                        <?= (string)($siswa['status_aktif'] ?? '') === '1' ? 'selected' : '' ?>>
                        Aktif
                    </option>
                    <option value="0"
                        <?= (string)($siswa['status_aktif'] ?? '') === '0' ? 'selected' : '' ?>>
                        Tidak Aktif
                    </option>
                </select>
            </div>

            <div class="mt-4">
                <button type="submit" name="update" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="kelola_siswa.php" class="btn btn-secondary ml-2">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>

