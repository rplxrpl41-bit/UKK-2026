<?php
// Tindakan atas pelanggaran siswa.
// Guru mengisi / memperbarui tindakan dan status penanganan tiap catatan.
include 'includes/cek_session.php';
include 'config/koneksi.php';

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

const STATUS_VALID = ['tercatat', 'ditindak', 'selesai'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// ---------- PROSES SIMPAN TINDAKAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kembali = function ($tipe, $pesan) {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
        $balik = 'tindakan.php';
        if (!empty($_POST['balik_query']) && preg_match('/^[A-Za-z0-9_=&%.\-+]*$/', $_POST['balik_query'])) {
            $balik .= '?' . $_POST['balik_query'];
        }
        header('Location: ' . $balik);
        exit;
    };

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $kembali('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');
    }

    $id       = (int) ($_POST['id'] ?? 0);
    $tindakan = trim($_POST['tindakan'] ?? '');
    $status   = $_POST['status'] ?? '';

    if ($id <= 0 || !in_array($status, STATUS_VALID, true)) {
        $kembali('danger', 'Data tidak valid.');
    }
    if ($status !== 'tercatat' && $tindakan === '') {
        $kembali('danger', 'Isi tindakan terlebih dahulu sebelum mengubah status menjadi "' . $status . '".');
    }

    $stmt = mysqli_prepare($koneksi, "
        UPDATE t_pelanggaran_siswa
        SET tindakan = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "ssi", $tindakan, $status, $id);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        $kembali('danger', 'Gagal menyimpan tindakan: ' . $err);
    }
    mysqli_stmt_close($stmt);

    $kembali('success', 'Tindakan berhasil disimpan.');
}

// ---------- FILTER + PAGINATION ----------
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$status_filter = $_GET['status'] ?? 'belum_selesai';
$cari          = trim($_GET['q'] ?? '');
$per_halaman   = 15;
$halaman       = max(1, (int) ($_GET['hal'] ?? 1));

$where  = [];
$tipe   = '';
$param  = [];

if (in_array($status_filter, STATUS_VALID, true)) {
    $where[] = 'status = ?';
    $tipe   .= 's';
    $param[] = $status_filter;
} elseif ($status_filter === 'belum_selesai') {
    $where[] = "status <> 'selesai'";
} else {
    $status_filter = 'semua';
}

if ($cari !== '') {
    $where[] = '(nama_siswa LIKE ? OR nama_pelanggaran LIKE ? OR nama_kelas LIKE ?)';
    $tipe   .= 'sss';
    $like    = '%' . $cari . '%';
    array_push($param, $like, $like, $like);
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total FROM t_pelanggaran_siswa $sql_where");
if ($param) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$param);
}
mysqli_stmt_execute($stmt);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
mysqli_stmt_close($stmt);

$total_halaman = max(1, (int) ceil($total / $per_halaman));
$halaman       = min($halaman, $total_halaman);
$offset        = ($halaman - 1) * $per_halaman;

$stmt = mysqli_prepare($koneksi, "
    SELECT id, tanggal, nama_siswa, nama_kelas, nama_pelanggaran, poin,
           keterangan, tindakan, status, nama_guru
    FROM t_pelanggaran_siswa
    $sql_where
    ORDER BY (status = 'selesai') ASC, tanggal DESC, id DESC
    LIMIT $per_halaman OFFSET $offset
");
if ($param) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$param);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$query_string = http_build_query(['status' => $status_filter, 'q' => $cari]);
$warna = ['tercatat' => 'secondary', 'ditindak' => 'warning', 'selesai' => 'success'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tindakan Pelanggaran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style><?php include 'includes/crud_style.php'; ?></style>
    <style>.content-card { max-width: none; }</style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <?php $menu_aktif = 'Tindakan'; include 'includes/crud_sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Tindakan</h1>
            <p class="page-description">
                Catat tindakan yang diberikan kepada siswa dan perbarui status penanganannya.
            </p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['tipe']) ?> alert-dismissible fade show">
                    <?= e($flash['pesan']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-card">

                <form method="GET" action="tindakan.php" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="belum_selesai" <?= $status_filter === 'belum_selesai' ? 'selected' : '' ?>>Belum selesai</option>
                            <option value="tercatat" <?= $status_filter === 'tercatat' ? 'selected' : '' ?>>Tercatat (belum ditindak)</option>
                            <option value="ditindak" <?= $status_filter === 'ditindak' ? 'selected' : '' ?>>Sedang ditindak</option>
                            <option value="selesai" <?= $status_filter === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="semua" <?= $status_filter === 'semua' ? 'selected' : '' ?>>Semua status</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="q" value="<?= e($cari) ?>" class="form-control"
                               placeholder="Cari nama siswa, kelas, atau pelanggaran...">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                        <a href="tindakan.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th>Tindakan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total === 0): ?>
                                <tr><td colspan="8" class="text-center text-muted">Tidak ada data.</td></tr>
                            <?php endif; ?>

                            <?php while ($r = mysqli_fetch_assoc($hasil)): ?>
                                <tr>
                                    <td class="text-nowrap"><?= e($r['tanggal']) ?></td>
                                    <td><?= e($r['nama_siswa']) ?></td>
                                    <td><?= e($r['nama_kelas']) ?></td>
                                    <td>
                                        <?= e($r['nama_pelanggaran']) ?>
                                        <?php if ($r['keterangan'] !== ''): ?>
                                            <div class="small text-muted"><?= e($r['keterangan']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int) $r['poin'] ?></td>
                                    <td><?= $r['tindakan'] !== '' ? e($r['tindakan']) : '<span class="text-muted">Belum ada</span>' ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $warna[$r['status']] ?? 'secondary' ?>">
                                            <?= e(ucfirst($r['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-sm btn-primary btn-tindakan"
                                                data-bs-toggle="modal" data-bs-target="#modalTindakan"
                                                data-id="<?= (int) $r['id'] ?>"
                                                data-siswa="<?= e($r['nama_siswa']) ?>"
                                                data-pelanggaran="<?= e($r['nama_pelanggaran']) ?>"
                                                data-tindakan="<?= e($r['tindakan']) ?>"
                                                data-status="<?= e($r['status']) ?>">
                                            Beri Tindakan
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted"><?= $total ?> data — halaman <?= $halaman ?> dari <?= $total_halaman ?></small>

                    <?php if ($total_halaman > 1): ?>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= e($query_string) ?>&hal=<?= $halaman - 1 ?>">&laquo;</a>
                                </li>
                                <li class="page-item disabled"><span class="page-link"><?= $halaman ?> / <?= $total_halaman ?></span></li>
                                <li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?<?= e($query_string) ?>&hal=<?= $halaman + 1 ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- MODAL BERI TINDAKAN -->
<div class="modal fade" id="modalTindakan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="tindakan.php" class="modal-content">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="id" id="modal_id">
            <input type="hidden" name="balik_query" value="<?= e($query_string . '&hal=' . $halaman) ?>">

            <div class="modal-header">
                <h5 class="modal-title">Beri Tindakan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    <strong id="modal_siswa"></strong><br>
                    <span class="text-muted" id="modal_pelanggaran"></span>
                </p>

                <div class="mb-3">
                    <label class="form-label" for="modal_tindakan">Tindakan</label>
                    <textarea name="tindakan" id="modal_tindakan" rows="3" class="form-control"
                              placeholder="Contoh: Teguran lisan, pemanggilan orang tua, pembinaan wali kelas"></textarea>
                </div>

                <div>
                    <label class="form-label" for="modal_status">Status</label>
                    <select name="status" id="modal_status" class="form-select" required>
                        <option value="tercatat">Tercatat (belum ditindak)</option>
                        <option value="ditindak">Sedang ditindak</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-tindakan').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
        document.getElementById('modal_id').value = tombol.dataset.id;
        document.getElementById('modal_siswa').textContent = tombol.dataset.siswa;
        document.getElementById('modal_pelanggaran').textContent = tombol.dataset.pelanggaran;
        document.getElementById('modal_tindakan').value = tombol.dataset.tindakan;
        document.getElementById('modal_status').value = tombol.dataset.status;
    });
});
</script>
</body>
</html>
