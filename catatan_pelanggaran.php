<?php
// Catatan Pelanggaran Siswa
// Guru / admin mencatat pelanggaran yang dilakukan siswa.
include 'includes/cek_session.php';
include 'config/koneksi.php';

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// Token CSRF sederhana
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Identitas guru yang sedang login (t_guru.user_id = t_users.id)
$guru_id   = null;
$nama_guru = $_SESSION['name'] ?? 'Guru';

$stmt = mysqli_prepare($koneksi, "SELECT id, nama FROM t_guru WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($guru) {
    $guru_id   = (int) $guru['id'];
    $nama_guru = $guru['nama'] ?: $nama_guru;
}

// ---------- PROSES SIMPAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kembali = function ($tipe, $pesan) {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
        header('Location: catatan_pelanggaran.php');
        exit;
    };

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $kembali('danger', 'Sesi formulir tidak valid. Silakan coba lagi.');
    }

    $siswa_id       = (int) ($_POST['siswa_id'] ?? 0);
    $pelanggaran_id = (int) ($_POST['pelanggaran_id'] ?? 0);
    $tanggal        = trim($_POST['tanggal'] ?? '');
    $keterangan     = trim($_POST['keterangan'] ?? '');
    $tindakan       = trim($_POST['tindakan'] ?? '');

    $tgl = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$tgl || $tgl->format('Y-m-d') !== $tanggal) {
        $kembali('danger', 'Tanggal tidak valid.');
    }
    if ($tanggal > date('Y-m-d')) {
        $kembali('danger', 'Tanggal pelanggaran tidak boleh di masa depan.');
    }
    if ($siswa_id <= 0 || $pelanggaran_id <= 0) {
        $kembali('danger', 'Siswa dan jenis pelanggaran wajib dipilih.');
    }

    // Data siswa + kelas aktif terbaru
    $stmt = mysqli_prepare($koneksi, "
        SELECT s.nama, ks.kelas_id, ks.tahun_ajaran_id, k.nama AS nama_kelas
        FROM t_siswa s
        LEFT JOIN t_kelas_siswa ks
               ON ks.id = (SELECT MAX(x.id) FROM t_kelas_siswa x
                           WHERE x.siswa_id = s.id AND x.status_aktif = 1)
        LEFT JOIN t_kelas k ON k.id = ks.kelas_id
        WHERE s.id = ? AND s.status_aktif = 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $siswa_id);
    mysqli_stmt_execute($stmt);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$siswa) {
        $kembali('danger', 'Siswa tidak ditemukan atau tidak aktif.');
    }

    // Data jenis pelanggaran (harus aktif)
    $stmt = mysqli_prepare($koneksi, "
        SELECT nama, poin, pelanggaran_kategori_id
        FROM t_pelanggaran WHERE id = ? AND status_aktif = 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $pelanggaran_id);
    mysqli_stmt_execute($stmt);
    $jenis = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$jenis) {
        $kembali('danger', 'Jenis pelanggaran tidak ditemukan atau tidak aktif.');
    }

    $kelas_id        = $siswa['kelas_id'] !== null ? (int) $siswa['kelas_id'] : null;
    $tahun_ajaran_id = $siswa['tahun_ajaran_id'] !== null ? (int) $siswa['tahun_ajaran_id'] : null;
    $nama_kelas      = $siswa['nama_kelas'] ?? '-';
    $poin            = (int) $jenis['poin'];
    $kategori_id     = $jenis['pelanggaran_kategori_id'] !== null ? (int) $jenis['pelanggaran_kategori_id'] : null;
    $status          = 'tercatat';

    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO t_pelanggaran_siswa
            (tahun_ajaran_id, siswa_id, nama_siswa, kelas_id, nama_kelas,
             pelanggaran_id, nama_pelanggaran, pelanggaran_kategori_id,
             guru_id, nama_guru, tanggal, keterangan, poin, tindakan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "iisisisiisssiss",
        $tahun_ajaran_id, $siswa_id, $siswa['nama'], $kelas_id, $nama_kelas,
        $pelanggaran_id, $jenis['nama'], $kategori_id,
        $guru_id, $nama_guru, $tanggal, $keterangan, $poin, $tindakan, $status
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $kembali('success', 'Catatan pelanggaran untuk ' . $siswa['nama'] . ' (+' . $poin . ' poin) berhasil disimpan.');
    }

    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    $kembali('danger', 'Gagal menyimpan catatan: ' . $err);
}

// ---------- DATA UNTUK FORM ----------
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$q_siswa = mysqli_query($koneksi, "
    SELECT s.id, s.nis, s.nama, k.nama AS nama_kelas
    FROM t_siswa s
    LEFT JOIN t_kelas_siswa ks
           ON ks.id = (SELECT MAX(x.id) FROM t_kelas_siswa x
                       WHERE x.siswa_id = s.id AND x.status_aktif = 1)
    LEFT JOIN t_kelas k ON k.id = ks.kelas_id
    WHERE s.status_aktif = 1
    ORDER BY s.nama ASC
");

$q_jenis = mysqli_query($koneksi, "
    SELECT p.id, p.kode, p.nama, p.poin, kt.nama AS kategori
    FROM t_pelanggaran p
    LEFT JOIN t_pelanggaran_kategori kt ON kt.id = p.pelanggaran_kategori_id
    WHERE p.status_aktif = 1
    ORDER BY kt.nama ASC, p.nama ASC
");

$q_terakhir = mysqli_query($koneksi, "
    SELECT tanggal, nama_siswa, nama_kelas, nama_pelanggaran, poin, nama_guru, status
    FROM t_pelanggaran_siswa
    ORDER BY id DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Pelanggaran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style><?php include 'includes/crud_style.php'; ?></style>
    <style>.content-card { max-width: none; margin-bottom: 24px; }</style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <?php $menu_aktif = 'Catatan Pelanggaran'; include 'includes/crud_sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Catatan Pelanggaran</h1>
            <p class="page-description">
                Catat pelanggaran siswa. Poin diambil otomatis dari jenis pelanggaran yang dipilih.
            </p>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['tipe']) ?> alert-dismissible fade show">
                    <?= e($flash['pesan']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <h5 class="mb-3">Tambah Catatan Pelanggaran</h5>

                <form method="POST" action="catatan_pelanggaran.php">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="siswa_id">Siswa</label>
                            <select name="siswa_id" id="siswa_id" class="form-select" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php while ($s = mysqli_fetch_assoc($q_siswa)): ?>
                                    <option value="<?= (int) $s['id'] ?>">
                                        <?= e($s['nama']) ?> — <?= e($s['nis']) ?>
                                        (<?= e($s['nama_kelas'] ?? 'belum ada kelas') ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="pelanggaran_id">Jenis Pelanggaran</label>
                            <select name="pelanggaran_id" id="pelanggaran_id" class="form-select" required>
                                <option value="">-- Pilih Jenis Pelanggaran --</option>
                                <?php while ($j = mysqli_fetch_assoc($q_jenis)): ?>
                                    <option value="<?= (int) $j['id'] ?>">
                                        <?= e($j['kode']) ?> — <?= e($j['nama']) ?> (<?= (int) $j['poin'] ?> poin)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="tanggal">Tanggal Kejadian</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control"
                                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="tindakan">Tindakan Awal <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" name="tindakan" id="tindakan" class="form-control"
                                   maxlength="255" placeholder="Contoh: Teguran lisan">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="keterangan">Keterangan <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea name="keterangan" id="keterangan" rows="3" class="form-control"
                                      placeholder="Kronologi singkat kejadian"></textarea>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Catatan</button>
                        <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </form>
            </div>

            <div class="content-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">10 Catatan Terakhir</h5>
                    <a href="riwayat_pelanggaran.php" class="btn btn-sm btn-outline-primary">Lihat semua riwayat</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th>Dicatat oleh</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($q_terakhir) === 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">Belum ada catatan pelanggaran.</td></tr>
                            <?php endif; ?>
                            <?php while ($r = mysqli_fetch_assoc($q_terakhir)): ?>
                                <tr>
                                    <td><?= e($r['tanggal']) ?></td>
                                    <td><?= e($r['nama_siswa']) ?></td>
                                    <td><?= e($r['nama_kelas']) ?></td>
                                    <td><?= e($r['nama_pelanggaran']) ?></td>
                                    <td><?= (int) $r['poin'] ?></td>
                                    <td><?= e($r['nama_guru']) ?></td>
                                    <td><span class="badge text-bg-secondary"><?= e(ucfirst($r['status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
