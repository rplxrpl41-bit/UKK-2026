
<?php
// Riwayat Pelanggaran Siswa (hanya lihat, dengan filter)
include 'includes/cek_session.php';
include 'config/koneksi.php';

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// Identitas guru yang login (untuk filter catatan saya)
$guru_id = null;

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id FROM t_guru WHERE user_id = ? LIMIT 1"
);

if ($stmt) {
    $user_id = (int) ($_SESSION['user_id'] ?? 0);

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $g = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);

    if ($g) {
        $guru_id = (int) $g['id'];
    }
}

// ---------- FILTER ----------
$cari     = trim($_GET['q'] ?? '');
$kelas_id = (int) ($_GET['kelas_id'] ?? 0);
$dari     = trim($_GET['dari'] ?? '');
$sampai   = trim($_GET['sampai'] ?? '');
$status   = $_GET['status'] ?? '';
$oleh     = $_GET['oleh'] ?? 'semua';

// Validasi tanggal
$tgl_valid = function ($t) {
    $d = DateTime::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
};

if ($dari !== '' && !$tgl_valid($dari)) {
    $dari = '';
}

if ($sampai !== '' && !$tgl_valid($sampai)) {
    $sampai = '';
}

if (!in_array($status, ['tercatat', 'ditindak', 'selesai'], true)) {
    $status = '';
}

// ---------- SUSUN WHERE ----------
$where = [];
$tipe  = '';
$param = [];

if ($cari !== '') {
    $where[] = '(ps.nama_siswa LIKE ? OR s.nis LIKE ? OR ps.nama_pelanggaran LIKE ?)';

    $tipe .= 'sss';

    $like = '%' . $cari . '%';

    array_push($param, $like, $like, $like);
}

if ($kelas_id > 0) {
    $where[] = 'ps.kelas_id = ?';
    $tipe .= 'i';
    $param[] = $kelas_id;
}

if ($dari !== '') {
    $where[] = 'ps.tanggal >= ?';
    $tipe .= 's';
    $param[] = $dari;
}

if ($sampai !== '') {
    $where[] = 'ps.tanggal <= ?';
    $tipe .= 's';
    $param[] = $sampai;
}

if ($status !== '') {
    $where[] = 'ps.status = ?';
    $tipe .= 's';
    $param[] = $status;
}

if ($oleh === 'saya') {
    if ($guru_id !== null) {
        $where[] = 'ps.guru_id = ?';
        $tipe .= 'i';
        $param[] = $guru_id;
    } else {
        $where[] = 'ps.nama_guru = ?';
        $tipe .= 's';
        $param[] = $_SESSION['name'] ?? '';
    }
} else {
    $oleh = 'semua';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------- PAGINATION ----------
$per_halaman = 20;
$halaman = max(1, (int) ($_GET['hal'] ?? 1));

$dasar = "
    FROM t_pelanggaran_siswa ps
    LEFT JOIN t_siswa s ON s.id = ps.siswa_id
    $sql_where
";

// ---------- HITUNG TOTAL ----------
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(ps.poin), 0) AS total_poin
     $dasar"
);

if ($stmt) {
    if ($param) {
        mysqli_stmt_bind_param($stmt, $tipe, ...$param);
    }

    mysqli_stmt_execute($stmt);

    $ringkas = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);
} else {
    die("Gagal menghitung riwayat: " . e(mysqli_error($koneksi)));
}

$total = (int) ($ringkas['total'] ?? 0);
$total_poin = (int) ($ringkas['total_poin'] ?? 0);

$total_halaman = max(1, (int) ceil($total / $per_halaman));

$halaman = min($halaman, $total_halaman);

$offset = ($halaman - 1) * $per_halaman;

// ---------- AMBIL DATA RIWAYAT ----------
$stmt = mysqli_prepare(
    $koneksi,
    "
    SELECT
        ps.id,
        ps.tanggal,
        ps.nama_siswa,
        s.nis,
        ps.nama_kelas,
        ps.nama_pelanggaran,
        ps.poin,
        ps.keterangan,
        ps.tindakan,
        ps.status,
        ps.nama_guru
    $dasar
    ORDER BY ps.tanggal DESC, ps.id DESC
    LIMIT $per_halaman OFFSET $offset
    "
);

if (!$stmt) {
    die("Gagal mengambil riwayat: " . e(mysqli_error($koneksi)));
}

if ($param) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$param);
}

mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);

// ---------- DATA KELAS ----------
$q_kelas = mysqli_query(
    $koneksi,
    "SELECT id, nama FROM t_kelas ORDER BY tingkat, jurusan, nama"
);

// ---------- QUERY PAGINATION ----------
$query_string = http_build_query([
    'q'        => $cari,
    'kelas_id' => $kelas_id,
    'dari'     => $dari,
    'sampai'   => $sampai,
    'status'   => $status,
    'oleh'     => $oleh,
]);

$warna = [
    'tercatat' => 'secondary',
    'ditindak' => 'warning',
    'selesai'  => 'success'
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pelanggaran</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        <?php include 'includes/crud_style.php'; ?>

        .content-card {
            max-width: none;
        }

        .filter-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card .form-label {
            font-weight: 600;
            color: #374151;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            min-height: 40px;
            border-radius: 7px;
        }

        .summary-text {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .table {
            font-size: 14px;
            vertical-align: middle;
        }

        .table thead th {
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .badge {
            padding: 7px 10px;
            font-weight: 500;
        }

        .pagination {
            margin-top: 18px;
        }

        @media print {
            .sidebar,
            .filter-card,
            .pagination,
            .btn,
            .page-description {
                display: none !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .content-card {
                border: none !important;
                box-shadow: none !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 p-0">
            <?php
            $menu_aktif = 'Riwayat Pelanggaran';
            include 'includes/crud_sidebar.php';
            ?>
        </div>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Riwayat Pelanggaran</h1>

            <p class="page-description">
                Seluruh catatan pelanggaran siswa yang pernah tersimpan.
            </p>

            <div class="content-card">

                <!-- FILTER DATA -->
                <div class="filter-card">

                    <form method="GET" action="riwayat_pelanggaran.php" class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>

                            <input
                                type="text"
                                name="q"
                                value="<?= e($cari) ?>"
                                class="form-control"
                                placeholder="Cari nama, NIS, atau pelanggaran..."
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>

                            <select name="kelas_id" class="form-select">
                                <option value="0">Semua kelas</option>

                                <?php while ($k = mysqli_fetch_assoc($q_kelas)): ?>
                                    <option
                                        value="<?= (int) $k['id'] ?>"
                                        <?= $kelas_id === (int) $k['id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($k['nama']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>

                            <select name="status" class="form-select">
                                <option value="">Semua status</option>

                                <option value="tercatat" <?= $status === 'tercatat' ? 'selected' : '' ?>>
                                    Tercatat
                                </option>

                                <option value="ditindak" <?= $status === 'ditindak' ? 'selected' : '' ?>>
                                    Ditindak
                                </option>

                                <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>
                                    Selesai
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Dicatat Oleh</label>

                            <select name="oleh" class="form-select">
                                <option value="semua" <?= $oleh === 'semua' ? 'selected' : '' ?>>
                                    Dicatat siapa saja
                                </option>

                                <option value="saya" <?= $oleh === 'saya' ? 'selected' : '' ?>>
                                    Catatan saya saja
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>

                            <input
                                type="date"
                                name="dari"
                                value="<?= e($dari) ?>"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>

                            <input
                                type="date"
                                name="sampai"
                                value="<?= e($sampai) ?>"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-6 d-flex gap-2 align-items-end">

                            <button type="submit" class="btn btn-primary">
                                Tampilkan
                            </button>

                            <a href="riwayat_pelanggaran.php" class="btn btn-outline-secondary">
                                Reset
                            </a>

                            <button
                                type="button"
                                class="btn btn-outline-dark ms-auto"
                                onclick="window.print()"
                            >
                                Cetak
                            </button>

                        </div>

                    </form>

                </div>

                <!-- RINGKASAN -->
                <p class="summary-text">
                    Menampilkan <strong><?= $total ?></strong> catatan
                    &middot;
                    Total <strong><?= $total_poin ?></strong> poin
                </p>

                <!-- TABEL RIWAYAT -->
                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th>Tindakan</th>
                                <th>Status</th>
                                <th>Dicatat Oleh</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($total === 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Tidak ada riwayat yang cocok.
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php
                            $no = $offset + 1;

                            while ($r = mysqli_fetch_assoc($hasil)):
                            ?>

                                <tr>

                                    <td class="text-center">
                                        <?= $no++ ?>
                                    </td>

                                    <td class="text-nowrap">
                                        <?= e($r['tanggal']) ?>
                                    </td>

                                    <td>
                                        <strong><?= e($r['nama_siswa']) ?></strong>

                                        <div class="small text-muted">
                                            NIS: <?= e($r['nis']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($r['nama_kelas']) ?>
                                    </td>

                                    <td>
                                        <?= e($r['nama_pelanggaran']) ?>

                                        <?php if (!empty($r['keterangan'])): ?>
                                            <div class="small text-muted mt-1">
                                                <?= e($r['keterangan']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <strong><?= (int) $r['poin'] ?></strong>
                                    </td>

                                    <td>
                                        <?php if (!empty($r['tindakan'])): ?>
                                            <?= e($r['tindakan']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge text-bg-<?= $warna[$r['status']] ?? 'secondary' ?>">
                                            <?= e(ucfirst($r['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($r['nama_guru']) ?>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

                <!-- PAGINATION -->
                <?php if ($total_halaman > 1): ?>

                    <nav aria-label="Navigasi halaman">

                        <ul class="pagination pagination-sm mb-0">

                            <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?<?= e($query_string) ?>&hal=<?= $halaman - 1 ?>"
                                >
                                    &laquo;
                                </a>

                            </li>

                            <li class="page-item disabled">

                                <span class="page-link">
                                    <?= $halaman ?> / <?= $total_halaman ?>
                                </span>

                            </li>

                            <li class="page-item <?= $halaman >= $total_halaman ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?<?= e($query_string) ?>&hal=<?= $halaman + 1 ?>"
                                >
                                    &raquo;
                                </a>

                            </li>

                        </ul>

                    </nav>

                <?php endif; ?>

            </div>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
