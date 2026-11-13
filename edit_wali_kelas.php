<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// Halaman ini hanya boleh dibuka oleh admin.
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak! Halaman ini hanya untuk admin.');
}

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['pesan'] = 'ID wali kelas tidak valid.';
    header('Location: kelola_wali_kelas.php');
    exit;
}

// Ambil data wali kelas berdasarkan ID.
$stmt = mysqli_prepare($koneksi, "SELECT id, tahun_ajaran_id, kelas_id, guru_id, tanggal_mulai, tanggal_selesai, status_aktif FROM t_wali_kelas WHERE id = ? LIMIT 1");
if (!$stmt) {
    die('Gagal menyiapkan query data wali kelas: ' . e(mysqli_error($koneksi)));
}
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data) {
    $_SESSION['pesan'] = 'Data wali kelas tidak ditemukan.';
    header('Location: kelola_wali_kelas.php');
    exit;
}

$error = '';
$tahun_ajaran_id = (string)$data['tahun_ajaran_id'];
$kelas_id = (string)$data['kelas_id'];
$guru_id = (string)$data['guru_id'];
$tanggal_mulai = $data['tanggal_mulai'];
$tanggal_selesai = $data['tanggal_selesai'];
$status_aktif = (string)$data['status_aktif'];

// Nilai 0000-00-00 dan 9999-12-31 dianggap belum memiliki tanggal selesai.
if ($tanggal_selesai === '0000-00-00' || $tanggal_selesai === '9999-12-31') {
    $tanggal_selesai = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun_ajaran_id = (string)(int)($_POST['tahun_ajaran_id'] ?? 0);
    $kelas_id = (string)(int)($_POST['kelas_id'] ?? 0);
    $guru_id = (string)(int)($_POST['guru_id'] ?? 0);
    $tanggal_mulai = trim($_POST['tanggal_mulai'] ?? '');
    $tanggal_selesai = trim($_POST['tanggal_selesai'] ?? '');
    $status_aktif = (string)($_POST['status_aktif'] ?? '');

    if ((int)$tahun_ajaran_id <= 0 || (int)$kelas_id <= 0 || (int)$guru_id <= 0 || $tanggal_mulai === '') {
        $error = 'Tahun ajaran, kelas, guru, dan tanggal mulai wajib diisi.';
    } elseif (!in_array($status_aktif, ['0', '1'], true)) {
        $error = 'Status wali kelas tidak valid.';
    } elseif ($tanggal_selesai !== '' && $tanggal_mulai > $tanggal_selesai) {
        $error = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
    } else {
        // Pastikan kombinasi kelas dan tahun ajaran tidak dipakai data lain.
        $cek = mysqli_prepare($koneksi, "SELECT id FROM t_wali_kelas WHERE tahun_ajaran_id = ? AND kelas_id = ? AND id <> ? LIMIT 1");
        if (!$cek) {
            $error = 'Gagal memeriksa duplikasi data wali kelas.';
        } else {
            $tahun_int = (int)$tahun_ajaran_id;
            $kelas_int = (int)$kelas_id;
            mysqli_stmt_bind_param($cek, 'iii', $tahun_int, $kelas_int, $id);
            mysqli_stmt_execute($cek);
            $cek_result = mysqli_stmt_get_result($cek);
            if ($cek_result && mysqli_num_rows($cek_result) > 0) {
                $error = 'Kelas tersebut sudah memiliki wali kelas pada tahun ajaran yang dipilih.';
            }
            mysqli_stmt_close($cek);
        }

        if ($error === '') {
            // Kolom tanggal_selesai pada database NOT NULL.
            $tanggal_selesai_db = ($tanggal_selesai === '') ? '9999-12-31' : $tanggal_selesai;
            $tahun_int = (int)$tahun_ajaran_id;
            $kelas_int = (int)$kelas_id;
            $guru_int = (int)$guru_id;
            $status_int = (int)$status_aktif;

            $update = mysqli_prepare($koneksi, "UPDATE t_wali_kelas SET tahun_ajaran_id = ?, kelas_id = ?, guru_id = ?, tanggal_mulai = ?, tanggal_selesai = ?, status_aktif = ?, updated_at = NOW() WHERE id = ?");
            if (!$update) {
                $error = 'Gagal menyiapkan proses perubahan: ' . mysqli_error($koneksi);
            } else {
                mysqli_stmt_bind_param($update, 'iiissii', $tahun_int, $kelas_int, $guru_int, $tanggal_mulai, $tanggal_selesai_db, $status_int, $id);
                if (mysqli_stmt_execute($update)) {
                    mysqli_stmt_close($update);
                    $_SESSION['pesan'] = 'Data wali kelas berhasil diperbarui.';
                    header('Location: kelola_wali_kelas.php');
                    exit;
                }
                $error = 'Gagal memperbarui data wali kelas: ' . mysqli_stmt_error($update);
                mysqli_stmt_close($update);
            }
        }
    }
}

$query_tahun = mysqli_query($koneksi, "SELECT id, nama, status_aktif FROM t_tahun_ajaran ORDER BY id DESC");

// Semua kelas aktif (+ kelas yang sedang dipakai data ini, walau sudah nonaktif)
$stmt_kelas = mysqli_prepare($koneksi, "SELECT id, nama, tingkat, jurusan FROM t_kelas WHERE status_aktif = 1 OR id = ? ORDER BY FIELD(tingkat, 'X', 'XI', 'XII'), jurusan, nama");
$kelas_pilih_id = (int) $data['kelas_id'];
mysqli_stmt_bind_param($stmt_kelas, 'i', $kelas_pilih_id);
mysqli_stmt_execute($stmt_kelas);
$query_kelas = mysqli_stmt_get_result($stmt_kelas);

$query_guru = mysqli_query($koneksi, "SELECT id, nama, nip FROM t_guru ORDER BY nama ASC");

if (!$query_tahun || !$query_kelas || !$query_guru) {
    die('Gagal mengambil pilihan tahun ajaran, kelas, atau guru: ' . e(mysqli_error($koneksi)));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Wali Kelas</title>

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
            <?php $menu_aktif = 'Wali Kelas'; include 'includes/crud_sidebar.php'; ?>
        </div>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 content">

            <div class="mb-4">
                <h2 class="page-title">Edit Wali Kelas</h2>
                <p class="text-muted">
                    Ubah informasi guru, kelas, tahun ajaran, dan masa tugas wali kelas.
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="card card-main">

                <div class="card-header">
                    Form Edit Wali Kelas
                </div>

                <div class="card-body p-4">

                    <form method="POST" action="edit_wali_kelas.php?id=<?= $id ?>">

                        <input type="hidden" name="id" value="<?= $id ?>">

                        <h5 class="section-title">Informasi Wali Kelas</h5>

                        <div class="form-group">
                            <label for="tahun_ajaran_id">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-control" required>
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php while ($ta = mysqli_fetch_assoc($query_tahun)): ?>
                                    <option value="<?= (int) $ta['id'] ?>" <?= (int) $tahun_ajaran_id === (int) $ta['id'] ? 'selected' : '' ?>>
                                        <?= e($ta['nama']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="kelas_id">Kelas</label>
                            <select name="kelas_id" id="kelas_id" class="form-control" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php while ($kelas = mysqli_fetch_assoc($query_kelas)): ?>
                                    <option value="<?= (int) $kelas['id'] ?>" <?= (int) $kelas_id === (int) $kelas['id'] ? 'selected' : '' ?>>
                                        <?= e($kelas['tingkat']) ?> <?= e($kelas['jurusan']) ?> (<?= e($kelas['nama']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="guru_id">Guru / Wali Kelas</label>
                            <select name="guru_id" id="guru_id" class="form-control" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php while ($guru = mysqli_fetch_assoc($query_guru)): ?>
                                    <option value="<?= (int) $guru['id'] ?>" <?= (int) $guru_id === (int) $guru['id'] ? 'selected' : '' ?>>
                                        <?= e($guru['nama']) ?><?= !empty($guru['nip']) ? ' - ' . e($guru['nip']) : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <hr>

                        <h5 class="section-title">Masa Tugas</h5>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="tanggal_mulai">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                       class="form-control" value="<?= e($tanggal_mulai) ?>" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="tanggal_selesai">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                       class="form-control" value="<?= e($tanggal_selesai) ?>">
                                <small class="text-muted">Kosongkan jika masa tugas masih berlangsung.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status_aktif">Status Aktif</label>
                            <select name="status_aktif" id="status_aktif" class="form-control" required>
                                <option value="1" <?= $status_aktif === '1' ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= $status_aktif === '0' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary">
                            Simpan Perubahan
                        </button>

                        <a href="kelola_wali_kelas.php" class="btn btn-secondary">
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
