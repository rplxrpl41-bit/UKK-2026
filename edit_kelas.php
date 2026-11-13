<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak!");
}

function e($nilai)
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['pesan'] = "ID kelas tidak valid!";
    header("Location: kelola_kelas.php");
    exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM t_kelas WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['pesan'] = "Data kelas tidak ditemukan!";
    header("Location: kelola_kelas.php");
    exit;
}

$daftar_tingkat = ['X', 'XI', 'XII'];
$daftar_jurusan = ['RPL', 'TKJ', 'TKR', 'BD', 'TSM'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tingkat = trim($_POST['tingkat'] ?? '');
    $jurusan = trim($_POST['jurusan'] ?? '');
    $status_aktif = (string) ($_POST['status_aktif'] ?? '1');

    if (!in_array($tingkat, $daftar_tingkat, true) || !in_array($jurusan, $daftar_jurusan, true)) {
        $error = "Tingkat atau jurusan tidak valid.";
    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = "Status kelas tidak valid.";
    } else {
        $nama = $jurusan; // nama kelas dibuat otomatis, sama seperti Tambah Kelas

        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_kelas WHERE nama = ? AND tingkat = ? AND jurusan = ? AND id <> ? LIMIT 1"
        );
        mysqli_stmt_bind_param($cek, "sssi", $nama, $tingkat, $jurusan, $id);
        mysqli_stmt_execute($cek);

        if (mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0) {
            $error = "Kelas tersebut sudah terdaftar.";
        }
        mysqli_stmt_close($cek);

        if ($error === '') {
            $status = (int) $status_aktif;

            $up = mysqli_prepare(
                $koneksi,
                "UPDATE t_kelas SET nama = ?, tingkat = ?, jurusan = ?, status_aktif = ?, updated_at = NOW() WHERE id = ?"
            );
            mysqli_stmt_bind_param($up, "sssii", $nama, $tingkat, $jurusan, $status, $id);

            if (mysqli_stmt_execute($up)) {
                mysqli_stmt_close($up);
                $_SESSION['pesan'] = "Data kelas berhasil diperbarui!";
                header("Location: kelola_kelas.php");
                exit;
            }

            $error = "Gagal memperbarui kelas: " . mysqli_stmt_error($up);
            mysqli_stmt_close($up);
        }
    }

    $data['tingkat'] = $tingkat;
    $data['jurusan'] = $jurusan;
    $data['status_aktif'] = $status_aktif;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Kelas</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        <?php include 'includes/crud_style.php'; ?>
        <?php include 'includes/edit_style.php'; ?>
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 p-0">
            <?php $menu_aktif = 'Data Kelas'; include 'includes/crud_sidebar.php'; ?>
        </div>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 content">

            <div class="mb-4">
                <h2 class="page-title">Edit Data Kelas</h2>
                <p class="text-muted">Perbarui tingkat, jurusan, dan status kelas.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card card-main">

                <div class="card-header">
                    Form Edit Data Kelas
                </div>

                <div class="card-body p-4">

                    <form method="POST" action="edit_kelas.php?id=<?= $id ?>">

                        <input type="hidden" name="id" value="<?= $id ?>">

                        <h5 class="section-title">Informasi Kelas</h5>

                        <div class="form-group">
                            <label for="tingkat">Tingkat</label>
                            <select name="tingkat" id="tingkat" class="form-control" required>
                                <?php foreach ($daftar_tingkat as $t): ?>
                                    <option value="<?= e($t) ?>" <?= $data['tingkat'] === $t ? 'selected' : '' ?>>
                                        <?= e($t) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jurusan">Jurusan</label>
                            <select name="jurusan" id="jurusan" class="form-control" required>
                                <?php foreach ($daftar_jurusan as $j): ?>
                                    <option value="<?= e($j) ?>" <?= $data['jurusan'] === $j ? 'selected' : '' ?>>
                                        <?= e($j) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status_aktif">Status Aktif</label>
                            <select name="status_aktif" id="status_aktif" class="form-control" required>
                                <option value="1" <?= (string) $data['status_aktif'] === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= (string) $data['status_aktif'] === '0' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>

                        <a href="kelola_kelas.php" class="btn btn-secondary">
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
