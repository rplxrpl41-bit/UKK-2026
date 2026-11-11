
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// =====================================
// FUNGSI
// =====================================

function e($nilai)
{
    return htmlspecialchars(
        (string) ($nilai ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Membantu binding parameter pencarian secara dinamis.
function bind_param_array($stmt, $tipe, &$param)
{
    if ($tipe === '' || empty($param)) {
        return true;
    }

    $bind = [$stmt, $tipe];

    foreach ($param as $key => &$nilai) {
        $bind[] = &$nilai;
    }

    unset($nilai);

    return call_user_func_array(
        'mysqli_stmt_bind_param',
        $bind
    );
}

const STATUS_VALID = [
    'tercatat',
    'ditindak',
    'selesai'
];

// =====================================
// TOKEN CSRF
// =====================================

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// =====================================
// PROSES SIMPAN TINDAKAN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $balik = $_POST['balik_query'] ?? '';

    if (
        !is_string($balik)
        || !preg_match('/^[A-Za-z0-9_=&%.\-+]*$/', $balik)
    ) {
        $balik = '';
    }

    $kembali = function ($tipe, $pesan) use ($balik) {

        $_SESSION['flash'] = [
            'tipe' => $tipe,
            'pesan' => $pesan
        ];

        header(
            'Location: tindakan.php' .
            ($balik !== '' ? '?' . $balik : '')
        );

        exit;
    };

    // CEK CSRF
    $csrf_post = $_POST['csrf'] ?? '';

    if (
        !is_string($csrf_post)
        || !hash_equals($_SESSION['csrf'], $csrf_post)
    ) {
        $kembali(
            'danger',
            'Sesi formulir tidak valid. Silakan coba lagi.'
        );
    }

    // AMBIL INPUT
    $id = (int) ($_POST['id'] ?? 0);

    $tindakan = $_POST['tindakan'] ?? '';

    if (!is_string($tindakan)) {
        $tindakan = '';
    }

    $tindakan = trim($tindakan);

    $status = $_POST['status'] ?? '';

    if (!is_string($status)) {
        $status = '';
    }

    // VALIDASI INPUT
    if (
        $id <= 0
        || !in_array($status, STATUS_VALID, true)
    ) {
        $kembali('danger', 'Data tidak valid.');
    }

    if (
        $status !== 'tercatat'
        && $tindakan === ''
    ) {
        $kembali(
            'danger',
            'Isi tindakan terlebih dahulu.'
        );
    }

    // =====================================
    // CEK DATA PELANGGARAN
    // =====================================

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id FROM t_pelanggaran_siswa WHERE id = ? LIMIT 1"
    );

    if (!$stmt) {
        $kembali(
            'danger',
            'Kesalahan database: ' . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $hasil_cek = mysqli_stmt_get_result($stmt);

    $data_ada = $hasil_cek
        ? mysqli_fetch_assoc($hasil_cek)
        : null;

    mysqli_stmt_close($stmt);

    if (!$data_ada) {
        $kembali(
            'danger',
            'Data pelanggaran tidak ditemukan.'
        );
    }

    // =====================================
    // SIMPAN TINDAKAN
    // =====================================

    $stmt = mysqli_prepare($koneksi, "
        UPDATE t_pelanggaran_siswa
        SET
            tindakan = ?,
            status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    if (!$stmt) {
        $kembali(
            'danger',
            'Kesalahan database: ' . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssi",
        $tindakan,
        $status,
        $id
    );

    if (!mysqli_stmt_execute($stmt)) {

        $error = mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);

        $kembali(
            'danger',
            'Gagal menyimpan tindakan: ' . $error
        );
    }

    mysqli_stmt_close($stmt);

    $kembali(
        'success',
        'Tindakan berhasil disimpan.'
    );
}

// =====================================
// FLASH MESSAGE
// =====================================

$flash = $_SESSION['flash'] ?? null;

unset($_SESSION['flash']);

// =====================================
// FILTER DAN PAGINATION
// =====================================

$status_filter = $_GET['status'] ?? 'belum_selesai';

if (!is_string($status_filter)) {
    $status_filter = 'belum_selesai';
}

$cari = $_GET['q'] ?? '';

if (!is_string($cari)) {
    $cari = '';
}

$cari = trim($cari);

$per_halaman = 15;

$halaman = max(
    1,
    (int) ($_GET['hal'] ?? 1)
);

$where = [];

$tipe = '';

$param = [];

// FILTER STATUS
if (in_array($status_filter, STATUS_VALID, true)) {

    $where[] = 'status = ?';

    $tipe .= 's';

    $param[] = $status_filter;

} elseif ($status_filter === 'belum_selesai') {

    $where[] = "status <> 'selesai'";

} else {

    $status_filter = 'semua';
}

// FILTER PENCARIAN
if ($cari !== '') {

    $where[] = "
        (
            nama_siswa LIKE ?
            OR nama_pelanggaran LIKE ?
            OR nama_kelas LIKE ?
        )
    ";

    $tipe .= 'sss';

    $like = '%' . $cari . '%';

    $param[] = $like;
    $param[] = $like;
    $param[] = $like;
}

$sql_where = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

// =====================================
// HITUNG TOTAL DATA
// =====================================

$stmt = mysqli_prepare($koneksi, "
    SELECT COUNT(*) AS total
    FROM t_pelanggaran_siswa
    $sql_where
");

if (!$stmt) {
    die(
        'Kesalahan database: ' .
        e(mysqli_error($koneksi))
    );
}

if ($param) {

    if (!bind_param_array($stmt, $tipe, $param)) {
        die('Gagal mengikat parameter pencarian.');
    }
}

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die('Gagal menghitung data: ' . e($error));
}

$hasil_total = mysqli_stmt_get_result($stmt);

$data_total = mysqli_fetch_assoc($hasil_total);

$total = (int) ($data_total['total'] ?? 0);

mysqli_stmt_close($stmt);

// PAGINATION
$total_halaman = max(
    1,
    (int) ceil($total / $per_halaman)
);

$halaman = min(
    $halaman,
    $total_halaman
);

$offset = ($halaman - 1) * $per_halaman;

// =====================================
// AMBIL DATA PELANGGARAN
// =====================================

$stmt = mysqli_prepare($koneksi, "
    SELECT
        id,
        tanggal,
        nama_siswa,
        nama_kelas,
        nama_pelanggaran,
        poin,
        keterangan,
        tindakan,
        status,
        nama_guru
    FROM t_pelanggaran_siswa
    $sql_where
    ORDER BY
        (status = 'selesai') ASC,
        tanggal DESC,
        id DESC
    LIMIT $per_halaman OFFSET $offset
");

if (!$stmt) {
    die(
        'Kesalahan database: ' .
        e(mysqli_error($koneksi))
    );
}

if ($param) {

    if (!bind_param_array($stmt, $tipe, $param)) {
        die('Gagal mengikat parameter pencarian.');
    }
}

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die('Gagal mengambil data: ' . e($error));
}

$hasil = mysqli_stmt_get_result($stmt);

// =====================================
// DATA UNTUK FORM TINDAKAN
// =====================================

$id_edit = (int) ($_GET['tindakan'] ?? 0);

$data_edit = null;

if ($id_edit > 0) {

    $stmt_edit = mysqli_prepare($koneksi, "
        SELECT
            id,
            tanggal,
            nama_siswa,
            nama_kelas,
            nama_pelanggaran,
            poin,
            keterangan,
            tindakan,
            status,
            nama_guru
        FROM t_pelanggaran_siswa
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt_edit) {
        die(
            'Kesalahan database: ' .
            e(mysqli_error($koneksi))
        );
    }

    mysqli_stmt_bind_param(
        $stmt_edit,
        "i",
        $id_edit
    );

    mysqli_stmt_execute($stmt_edit);

    $hasil_edit = mysqli_stmt_get_result($stmt_edit);

    $data_edit = $hasil_edit
        ? mysqli_fetch_assoc($hasil_edit)
        : null;

    mysqli_stmt_close($stmt_edit);

    if (!$data_edit) {

        mysqli_stmt_close($stmt);

        $_SESSION['flash'] = [
            'tipe' => 'danger',
            'pesan' => 'Data pelanggaran tidak ditemukan.'
        ];

        header('Location: tindakan.php');

        exit;
    }
}

// =====================================
// QUERY STRING
// =====================================

$query_string = http_build_query([
    'status' => $status_filter,
    'q' => $cari
]);

// =====================================
// WARNA STATUS
// =====================================

$warna = [
    'tercatat' => 'secondary',
    'ditindak' => 'warning',
    'selesai' => 'success'
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tindakan Pelanggaran - Sistem Informasi Pelanggaran Siswa</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        <?php include 'includes/crud_style.php'; ?>

        .content-card {
            max-width: none;
            background: #ffffff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .page-title {
            color: #172554;
            font-weight: 700;
        }

        .page-description {
            color: #64748b;
            margin-bottom: 25px;
        }

        .content-card h5 {
            color: #172554;
            font-weight: 700;
        }

        .form-tindakan {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .info-item {
            background: #f4f6f9;
            padding: 13px 15px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .info-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .info-value {
            font-weight: 600;
            color: #172554;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
        }

        .table thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody td {
            vertical-align: middle;
        }

        .btn-primary {
            background-color: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background-color: #2446b5;
            border-color: #2446b5;
        }

        @media (max-width: 767px) {

            .content {
                padding: 18px;
            }

            .form-tindakan {
                padding: 18px;
            }

            .content-card {
                padding: 15px;
            }
        }

        @media print {

            .sidebar,
            .no-print {
                display: none !important;
            }

            .content {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: #ffffff !important;
            }

            .content-card,
            .form-tindakan {
                box-shadow: none !important;
            }
        }
    </style>

</head>

<body>

<div class="container-fluid">

    <div class="row">

        <!-- =====================================
             SIDEBAR KIRI
        ====================================== -->

        <div class="col-md-3 col-lg-2 p-0">

            <?php
            $menu_aktif = 'Tindakan';
            include 'includes/crud_sidebar.php';
            ?>

        </div>

        <!-- =====================================
             KONTEN KANAN
        ====================================== -->

        <main class="col-md-9 col-lg-10 content">

            <!-- JUDUL HALAMAN -->

            <h1 class="page-title">
                Tindakan Pelanggaran
            </h1>

            <p class="page-description">
                Catat tindakan yang diberikan kepada siswa dan
                perbarui status penanganannya.
            </p>

            <!-- =====================================
                 NOTIFIKASI
            ====================================== -->

            <?php if ($flash): ?>

                <div class="alert alert-<?= e($flash['tipe']) ?> alert-dismissible fade show">

                    <?= e($flash['pesan']) ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                    ></button>

                </div>

            <?php endif; ?>

            <!-- =====================================
                 FORM TINDAKAN
            ====================================== -->

            <?php if ($data_edit): ?>

                <div class="form-tindakan">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                        <div>

                            <h4 class="mb-1">
                                Beri Tindakan
                            </h4>

                            <p class="text-muted mb-0">
                                Lengkapi formulir tindakan pelanggaran siswa.
                            </p>

                        </div>

                        <a
                            href="tindakan.php?<?= e($query_string) ?>&hal=<?= $halaman ?>"
                            class="btn btn-outline-secondary"
                        >
                            Kembali
                        </a>

                    </div>

                    <h5 class="mb-3">
                        Informasi Pelanggaran
                    </h5>

                    <div class="row">

                        <div class="col-md-6">

                            <div class="info-item">

                                <div class="info-label">
                                    Nama Siswa
                                </div>

                                <div class="info-value">
                                    <?= e($data_edit['nama_siswa']) ?>
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <div class="info-label">
                                    Kelas
                                </div>

                                <div class="info-value">
                                    <?= e($data_edit['nama_kelas']) ?>
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <div class="info-label">
                                    Tanggal Pelanggaran
                                </div>

                                <div class="info-value">
                                    <?= e($data_edit['tanggal']) ?>
                                </div>

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="info-item">

                                <div class="info-label">
                                    Poin
                                </div>

                                <div class="info-value">
                                    <?= (int) $data_edit['poin'] ?> poin
                                </div>

                            </div>

                        </div>

                        <div class="col-12">

                            <div class="info-item">

                                <div class="info-label">
                                    Jenis Pelanggaran
                                </div>

                                <div class="info-value">
                                    <?= e($data_edit['nama_pelanggaran']) ?>
                                </div>

                            </div>

                        </div>

                        <?php if (!empty($data_edit['keterangan'])): ?>

                            <div class="col-12">

                                <div class="info-item">

                                    <div class="info-label">
                                        Keterangan
                                    </div>

                                    <div class="info-value">
                                        <?= e($data_edit['keterangan']) ?>
                                    </div>

                                </div>

                            </div>

                        <?php endif; ?>

                        <div class="col-12">

                            <div class="info-item">

                                <div class="info-label">
                                    Guru Pencatat
                                </div>

                                <div class="info-value">
                                    <?= e($data_edit['nama_guru']) ?>
                                </div>

                            </div>

                        </div>

                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">
                        Formulir Tindakan
                    </h5>

                    <form method="POST" action="tindakan.php">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= e($_SESSION['csrf']) ?>"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $data_edit['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="balik_query"
                            value="<?= e($query_string . '&hal=' . $halaman) ?>"
                        >

                        <div class="mb-3">

                            <label for="tindakan" class="form-label">
                                Tindakan yang Diberikan
                            </label>

                            <textarea
                                name="tindakan"
                                id="tindakan"
                                rows="5"
                                class="form-control"
                                placeholder="Contoh: Teguran lisan, pemanggilan orang tua, pembinaan wali kelas..."
                            ><?= e($data_edit['tindakan']) ?></textarea>

                        </div>

                        <div class="mb-4">

                            <label for="status" class="form-label">
                                Status Penanganan
                            </label>

                            <select
                                name="status"
                                id="status"
                                class="form-select"
                                required
                            >

                                <option
                                    value="tercatat"
                                    <?= $data_edit['status'] === 'tercatat' ? 'selected' : '' ?>
                                >
                                    Tercatat (belum ditindak)
                                </option>

                                <option
                                    value="ditindak"
                                    <?= $data_edit['status'] === 'ditindak' ? 'selected' : '' ?>
                                >
                                    Sedang ditindak
                                </option>

                                <option
                                    value="selesai"
                                    <?= $data_edit['status'] === 'selesai' ? 'selected' : '' ?>
                                >
                                    Selesai
                                </option>

                            </select>

                        </div>

                        <div class="d-flex gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Tindakan
                            </button>

                            <a
                                href="tindakan.php?<?= e($query_string) ?>&hal=<?= $halaman ?>"
                                class="btn btn-outline-secondary"
                            >
                                Batal
                            </a>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

            <!-- =====================================
                 DAFTAR DATA PELANGGARAN
            ====================================== -->

            <div class="content-card">

                <h5 class="mb-3">
                    Daftar Pelanggaran Siswa
                </h5>

                <!-- FILTER PENCARIAN -->

                <form
                    method="GET"
                    action="tindakan.php"
                    class="row g-2 mb-3"
                >

                    <div class="col-md-3">

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option
                                value="belum_selesai"
                                <?= $status_filter === 'belum_selesai' ? 'selected' : '' ?>
                            >
                                Belum selesai
                            </option>

                            <option
                                value="tercatat"
                                <?= $status_filter === 'tercatat' ? 'selected' : '' ?>
                            >
                                Tercatat
                            </option>

                            <option
                                value="ditindak"
                                <?= $status_filter === 'ditindak' ? 'selected' : '' ?>
                            >
                                Sedang ditindak
                            </option>

                            <option
                                value="selesai"
                                <?= $status_filter === 'selesai' ? 'selected' : '' ?>
                            >
                                Selesai
                            </option>

                            <option
                                value="semua"
                                <?= $status_filter === 'semua' ? 'selected' : '' ?>
                            >
                                Semua status
                            </option>

                        </select>

                    </div>

                    <div class="col-md-5">

                        <input
                            type="text"
                            name="q"
                            value="<?= e($cari) ?>"
                            class="form-control"
                            placeholder="Cari nama siswa, kelas, atau pelanggaran..."
                        >

                    </div>

                    <div class="col-md-4 d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Tampilkan
                        </button>

                        <a
                            href="tindakan.php"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </a>

                    </div>

                </form>

                <!-- TABEL DATA -->

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

                                <tr>

                                    <td
                                        colspan="8"
                                        class="text-center text-muted py-4"
                                    >
                                        Tidak ada data.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php while ($r = mysqli_fetch_assoc($hasil)): ?>

                                    <tr>

                                        <td class="text-nowrap">
                                            <?= e($r['tanggal']) ?>
                                        </td>

                                        <td>
                                            <?= e($r['nama_siswa']) ?>
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

                                        <td>
                                            <?= (int) $r['poin'] ?> poin
                                        </td>

                                        <td>

                                            <?php if (!empty($r['tindakan'])): ?>

                                                <?= e($r['tindakan']) ?>

                                            <?php else: ?>

                                                <span class="text-muted">
                                                    Belum ada
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <span class="badge text-bg-<?= e($warna[$r['status']] ?? 'secondary') ?>">
                                                <?= e(ucfirst($r['status'] ?? 'Belum ada status')) ?>
                                            </span>

                                        </td>

                                        <td class="text-nowrap">

                                            <a
                                                href="tindakan.php?tindakan=<?= (int) $r['id'] ?>&status=<?= urlencode($status_filter) ?>&q=<?= urlencode($cari) ?>&hal=<?= $halaman ?>"
                                                class="btn btn-sm btn-primary"
                                            >
                                                Beri Tindakan
                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <!-- PAGINATION -->

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <small class="text-muted">
                        <?= number_format($total) ?> data —
                        halaman <?= $halaman ?> dari <?= $total_halaman ?>
                    </small>

                    <?php if ($total_halaman > 1): ?>

                        <nav>

                            <ul class="pagination pagination-sm mb-0">

                                <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?<?= e($query_string) ?>&hal=<?= max(1, $halaman - 1) ?>"
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
                                        href="?<?= e($query_string) ?>&hal=<?= min($total_halaman, $halaman + 1) ?>"
                                    >
                                        &raquo;
                                    </a>

                                </li>

                            </ul>

                        </nav>

                    <?php endif; ?>

                </div>

            </div>

            <!-- FOOTER -->

            <div class="text-center text-muted mt-4 small">
                © 2026 Sistem Informasi Pelanggaran Siswa
            </div>

        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
