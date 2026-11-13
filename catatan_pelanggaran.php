
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/helper_guru.php';

// =====================================
// FUNGSI
// =====================================

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

// =====================================
// CSRF TOKEN
// =====================================

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// =====================================
// IDENTITAS GURU
// =====================================

$guru_id = null;
$nama_guru = $_SESSION['name'] ?? 'Guru';

$guru = guru_login($koneksi);

if ($guru) {
    $guru_id = (int)$guru['id'];
    $nama_guru = $guru['nama'] ?: $nama_guru;
}

// =====================================
// PROSES SIMPAN CATATAN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kembali = function ($tipe, $pesan) {
        $_SESSION['flash'] = [
            'tipe' => $tipe,
            'pesan' => $pesan
        ];

        header('Location: catatan_pelanggaran.php');
        exit;
    };

    // Validasi CSRF
    $csrf_post = $_POST['csrf'] ?? '';

    if (
        empty($_SESSION['csrf']) ||
        !hash_equals($_SESSION['csrf'], $csrf_post)
    ) {
        $kembali('danger', 'Token keamanan tidak valid. Silakan coba kembali.');
    }

    // Validasi guru
    if (!$guru_id) {
        $kembali('danger', 'Data guru tidak ditemukan. Silakan hubungi administrator.');
    }

    // Ambil data form
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $pelanggaran_id = (int)($_POST['pelanggaran_id'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tindakan = trim($_POST['tindakan'] ?? '');

    // =================================
    // VALIDASI TANGGAL
    // =================================

    $tanggalObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    $tanggalValid = (
        $tanggalObj &&
        $tanggalObj->format('Y-m-d') === $tanggal
    );

    if (!$tanggalValid) {
        $kembali('danger', 'Tanggal pelanggaran tidak valid.');
    }

    if ($tanggal > date('Y-m-d')) {
        $kembali('danger', 'Tanggal pelanggaran tidak boleh melebihi hari ini.');
    }

    // =================================
    // VALIDASI INPUT
    // =================================

    if ($siswa_id <= 0) {
        $kembali('danger', 'Silakan pilih siswa terlebih dahulu.');
    }

    if ($pelanggaran_id <= 0) {
        $kembali('danger', 'Silakan pilih jenis pelanggaran.');
    }

    // =================================
    // AMBIL DATA SISWA
    // =================================

    $sqlSiswa = "
        SELECT
            s.nama,
            ks.kelas_id,
            ks.tahun_ajaran_id,
            k.nama AS nama_kelas
        FROM t_siswa s
        LEFT JOIN t_kelas_siswa ks ON ks.id = (
            SELECT MAX(x.id)
            FROM t_kelas_siswa x
            WHERE x.siswa_id = s.id
              AND x.status_aktif = 1
        )
        LEFT JOIN t_kelas k ON k.id = ks.kelas_id
        WHERE s.id = ?
          AND s.status_aktif = 1
        LIMIT 1
    ";

    $stmtSiswa = mysqli_prepare($koneksi, $sqlSiswa);

    if (!$stmtSiswa) {
        $kembali('danger', 'Terjadi kesalahan saat memeriksa data siswa.');
    }

    mysqli_stmt_bind_param($stmtSiswa, 'i', $siswa_id);
    mysqli_stmt_execute($stmtSiswa);

    $hasilSiswa = mysqli_stmt_get_result($stmtSiswa);
    $dataSiswa = mysqli_fetch_assoc($hasilSiswa);

    mysqli_stmt_close($stmtSiswa);

    if (!$dataSiswa) {
        $kembali('danger', 'Siswa tidak ditemukan atau sudah tidak aktif.');
    }

    // =================================
    // AMBIL DATA PELANGGARAN
    // =================================

    $sqlPelanggaran = "
        SELECT
            id,
            nama,
            poin,
            pelanggaran_kategori_id
        FROM t_pelanggaran
        WHERE id = ?
          AND status_aktif = 1
        LIMIT 1
    ";

    $stmtPelanggaran = mysqli_prepare($koneksi, $sqlPelanggaran);

    if (!$stmtPelanggaran) {
        $kembali('danger', 'Terjadi kesalahan saat memeriksa jenis pelanggaran.');
    }

    mysqli_stmt_bind_param($stmtPelanggaran, 'i', $pelanggaran_id);
    mysqli_stmt_execute($stmtPelanggaran);

    $hasilPelanggaran = mysqli_stmt_get_result($stmtPelanggaran);
    $dataPelanggaran = mysqli_fetch_assoc($hasilPelanggaran);

    mysqli_stmt_close($stmtPelanggaran);

    if (!$dataPelanggaran) {
        $kembali('danger', 'Jenis pelanggaran tidak ditemukan atau sudah tidak aktif.');
    }

    // =================================
    // SIAPKAN DATA UNTUK DISIMPAN
    // =================================

    $tahun_ajaran_id = $dataSiswa['tahun_ajaran_id'] !== null
        ? (int)$dataSiswa['tahun_ajaran_id']
        : null;

    $kelas_id = $dataSiswa['kelas_id'] !== null
        ? (int)$dataSiswa['kelas_id']
        : null;

    $pelanggaran_kategori_id = $dataPelanggaran['pelanggaran_kategori_id'] !== null
        ? (int)$dataPelanggaran['pelanggaran_kategori_id']
        : null;

    $nama_siswa = $dataSiswa['nama'];
    $nama_kelas = $dataSiswa['nama_kelas'] ?? '';
    $nama_pelanggaran = $dataPelanggaran['nama'];
    $poin = (int)$dataPelanggaran['poin'];

    // Status awal catatan
    $status = 'tercatat';

    // =================================
    // SIMPAN KE DATABASE
    // =================================

    $sqlInsert = "
        INSERT INTO t_pelanggaran_siswa (
            tahun_ajaran_id,
            siswa_id,
            nama_siswa,
            kelas_id,
            nama_kelas,
            pelanggaran_id,
            nama_pelanggaran,
            pelanggaran_kategori_id,
            guru_id,
            nama_guru,
            tanggal,
            keterangan,
            poin,
            tindakan,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);

    if (!$stmtInsert) {
        $kembali('danger', 'Gagal menyiapkan penyimpanan data pelanggaran.');
    }

    mysqli_stmt_bind_param(
        $stmtInsert,
        'iisisisiisssiss',
        $tahun_ajaran_id,
        $siswa_id,
        $nama_siswa,
        $kelas_id,
        $nama_kelas,
        $pelanggaran_id,
        $nama_pelanggaran,
        $pelanggaran_kategori_id,
        $guru_id,
        $nama_guru,
        $tanggal,
        $keterangan,
        $poin,
        $tindakan,
        $status
    );

    if (mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);
        $kembali('success', 'Catatan pelanggaran berhasil disimpan.');
    }

    mysqli_stmt_close($stmtInsert);
    $kembali('danger', 'Catatan pelanggaran gagal disimpan. Silakan coba kembali.');
}

// =====================================
// AMBIL PESAN NOTIFIKASI
// =====================================

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// =====================================
// DATA SISWA AKTIF
// =====================================

$sqlSiswa = "
    SELECT
        s.id,
        s.nis,
        s.nama,
        k.nama AS nama_kelas
    FROM t_siswa s
    LEFT JOIN t_kelas_siswa ks ON ks.id = (
        SELECT MAX(x.id)
        FROM t_kelas_siswa x
        WHERE x.siswa_id = s.id
          AND x.status_aktif = 1
    )
    LEFT JOIN t_kelas k ON k.id = ks.kelas_id
    WHERE s.status_aktif = 1
    ORDER BY s.nama ASC
";

$querySiswa = mysqli_query($koneksi, $sqlSiswa);

// =====================================
// DATA JENIS PELANGGARAN AKTIF
// =====================================

$sqlJenis = "
    SELECT
        p.id,
        p.kode,
        p.nama,
        p.poin,
        kt.nama AS kategori
    FROM t_pelanggaran p
    LEFT JOIN t_pelanggaran_kategori kt
        ON kt.id = p.pelanggaran_kategori_id
    WHERE p.status_aktif = 1
    ORDER BY kt.nama ASC, p.nama ASC
";

$queryJenis = mysqli_query($koneksi, $sqlJenis);

// =====================================
// 10 CATATAN TERBARU YANG BELUM SELESAI
// =====================================

$catatanTerbaru = [];

list($sql_batas, $tipe_batas, $param_batas) =
    batas_data_guru($koneksi, 'ps');

$sqlTerbaru = "
    SELECT
        ps.id,
        ps.tanggal,
        ps.nama_siswa,
        ps.nama_kelas,
        ps.nama_pelanggaran,
        ps.poin,
        ps.nama_guru,
        ps.status
    FROM t_pelanggaran_siswa ps
    WHERE ps.status <> 'selesai'
    $sql_batas
    ORDER BY ps.id DESC
    LIMIT 10
";

$stmtTerbaru = mysqli_prepare($koneksi, $sqlTerbaru);
$stmtTerbaru = mysqli_prepare($koneksi, $sqlTerbaru);

if ($stmtTerbaru) {

    if (!empty($tipe_batas) && !empty($param_batas)) {
        $parameter = [$tipe_batas];

        foreach ($param_batas as $key => $value) {
            $parameter[] = &$param_batas[$key];
        }

        call_user_func_array(
            [$stmtTerbaru, 'bind_param'],
            $parameter
        );
    }

    mysqli_stmt_execute($stmtTerbaru);

    $hasilTerbaru = mysqli_stmt_get_result($stmtTerbaru);

    while ($row = mysqli_fetch_assoc($hasilTerbaru)) {
        $catatanTerbaru[] = $row;
    }

    mysqli_stmt_close($stmtTerbaru);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Catatan Pelanggaran - Panel Guru</title>

    <!-- BOOTSTRAP 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- SELECT2 -->
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* SIDEBAR */

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            align-self: flex-start;
            overflow-y: auto;
            background: #172554;
            color: white;
            min-height: 100vh;
            padding: 22px 15px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: white;
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
            min-width: 0;
        }

        .page-title {
            color: #172554;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .page-description {
            color: #64748b;
            margin-bottom: 25px;
        }

        /* CARD */

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .card-title {
            color: #172554;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-description {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 22px;
        }

        /* FORM */

        .form-label {
            color: #334155;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control,
        .form-select {
            border: 1px solid #dbe2ea;
            border-radius: 7px;
            padding: 10px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3157d5;
            box-shadow: 0 0 0 0.2rem rgba(49, 87, 213, 0.12);
        }

        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
            border-radius: 7px;
            padding: 10px 18px;
        }

        .btn-primary:hover {
            background: #2445b5;
            border-color: #2445b5;
        }

        .btn-secondary {
            border-radius: 7px;
            padding: 10px 18px;
        }

        /* SELECT2 */

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 43px;
            border: 1px solid #dbe2ea;
            border-radius: 7px;
            padding: 7px 10px;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__rendered {
            line-height: 27px;
            color: #334155;
        }

        .select2-container--default
        .select2-selection--single
        .select2-selection__arrow {
            height: 41px;
        }

        .select2-container--default.select2-container--focus
        .select2-selection--single {
            border-color: #3157d5;
        }

        /* TABEL */

        .table {
            vertical-align: middle;
            margin-bottom: 0;
        }

        .table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            padding: 13px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody td {
            padding: 13px;
            color: #475569;
            font-size: 14px;
        }

        .table-responsive {
            border-radius: 8px;
        }

        .badge-status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-tercatat {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-ditindak {
            background: #fef3c7;
            color: #92400e;
        }

        .status-selesai {
            background: #dcfce7;
            color: #166534;
        }

        .footer-text {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 25px;
        }

        @media (max-width: 767px) {
            .sidebar {
                position: static;
                height: auto;
                overflow-y: visible;
                align-self: auto;
                min-height: auto;
            }

            .content {
                padding: 18px;
            }

            .content-card {
                padding: 18px;
            }

            .page-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- =====================================
             SIDEBAR PANEL GURU
        ====================================== -->

        <aside class="col-md-3 col-lg-2 sidebar">

            <h4 class="sidebar-title">Panel Guru</h4>

            <nav class="nav flex-column">

                <a href="dashboard.php" class="nav-link">
                    ⌂ Dashboard
                </a>

                <a href="catatan_pelanggaran.php" class="nav-link active">
                    ✎ Catatan Pelanggaran
                </a>

                <a href="tindakan.php" class="nav-link">
                    ⚑ Tindakan
                </a>

                <a href="riwayat_pelanggaran.php" class="nav-link">
                    ☰ Riwayat Pelanggaran
                </a>

                <?php
                $emailLogin = strtolower(trim($_SESSION['email'] ?? ''));

                $emailWaliKelas = [
                    'ari@gmail.com',
                    'aldi@gmail.com',
                    'anshor@gmail.com'
                ];

                $isWaliKelas = (
                    !empty($_SESSION['is_wali_kelas']) ||
                    in_array($emailLogin, $emailWaliKelas, true)
                );
                ?>

                <?php if ($isWaliKelas): ?>
                    <a href="laporan.php" class="nav-link">
                        ▤ Laporan
                    </a>
                <?php endif; ?>

                <a href="rekap_point.php" class="nav-link">
                    ★ Rekap Poin
                </a>

                <a href="tentang_saya.php" class="nav-link">
                    ℹ About Me
                </a>

                <hr>

                <a
                    href="logout.php"
                    class="nav-link logout"
                    onclick="return confirm('Yakin ingin logout?')"
                >
                    ⇥ Logout
                </a>

            </nav>
        </aside>

        <!-- =====================================
             KONTEN UTAMA
        ====================================== -->

        <main class="col-md-9 col-lg-10 content">

            <h1 class="page-title">Catatan Pelanggaran</h1>

            <p class="page-description">
                Form pencatatan pelanggaran siswa dan daftar catatan
                yang masih memerlukan penanganan.
            </p>

            <!-- =================================
                 NOTIFIKASI
            ================================== -->

            <?php if ($flash): ?>

                <div
                    class="alert alert-<?= e($flash['tipe']); ?> alert-dismissible fade show"
                    role="alert"
                >
                    <?= e($flash['pesan']); ?>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="alert"
                        aria-label="Close"
                    ></button>
                </div>

            <?php endif; ?>

            <!-- =================================
                 FORM CATATAN PELANGGARAN
            ================================== -->

            <div class="content-card">

                <h5 class="card-title">
                    Form Catatan Pelanggaran
                </h5>

                <p class="card-description">
                    Lengkapi data berikut untuk mencatat pelanggaran siswa.
                </p>

                <?php if (!$guru_id): ?>

                    <div class="alert alert-warning mb-0">
                        Data akun guru tidak ditemukan. Silakan hubungi administrator.
                    </div>

                <?php else: ?>

                    <form method="POST" action="">

                        <input
                            type="hidden"
                            name="csrf"
                            value="<?= e($_SESSION['csrf']); ?>"
                        >

                        <div class="row g-3">

                            <!-- GURU -->

                            <div class="col-md-6">
                                <label class="form-label">
                                    Nama Guru
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= e($nama_guru); ?>"
                                    readonly
                                >
                            </div>

                            <!-- TANGGAL -->

                            <div class="col-md-6">
                                <label for="tanggal" class="form-label">
                                    Tanggal Pelanggaran
                                </label>

                                <input
                                    type="date"
                                    name="tanggal"
                                    id="tanggal"
                                    class="form-control"
                                    value="<?= date('Y-m-d'); ?>"
                                    max="<?= date('Y-m-d'); ?>"
                                    required
                                >
                            </div>

                            <!-- SISWA -->

                            <div class="col-md-6">
                                <label for="siswa_id" class="form-label">
                                    Pilih Siswa
                                </label>

                                <select
                                    name="siswa_id"
                                    id="siswa_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        -- Pilih Siswa --
                                    </option>

                                    <?php if ($querySiswa): ?>
                                        <?php while ($siswa = mysqli_fetch_assoc($querySiswa)): ?>

                                            <option value="<?= (int)$siswa['id']; ?>">
                                                <?= e($siswa['nis']); ?>
                                                -
                                                <?= e($siswa['nama']); ?>
                                                <?php if (!empty($siswa['nama_kelas'])): ?>
                                                    (<?= e($siswa['nama_kelas']); ?>)
                                                <?php endif; ?>
                                            </option>

                                        <?php endwhile; ?>
                                    <?php endif; ?>

                                </select>
                            </div>

                            <!-- JENIS PELANGGARAN -->

                            <div class="col-md-6">
                                <label for="pelanggaran_id" class="form-label">
                                    Jenis Pelanggaran
                                </label>

                                <select
                                    name="pelanggaran_id"
                                    id="pelanggaran_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        -- Pilih Pelanggaran --
                                    </option>

                                    <?php if ($queryJenis): ?>
                                        <?php while ($jenis = mysqli_fetch_assoc($queryJenis)): ?>

                                            <option value="<?= (int)$jenis['id']; ?>">
                                                <?= e($jenis['kode']); ?>
                                                -
                                                <?= e($jenis['nama']); ?>
                                                (<?= (int)$jenis['poin']; ?> poin)
                                            </option>

                                        <?php endwhile; ?>
                                    <?php endif; ?>

                                </select>
                            </div>

                            <!-- TINDAKAN AWAL -->

                            <div class="col-12">
                                <label for="tindakan" class="form-label">
                                    Tindakan Awal
                                    <small class="text-muted">(Opsional)</small>
                                </label>

                                <textarea
                                    name="tindakan"
                                    id="tindakan"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Masukkan tindakan awal jika ada..."
                                ></textarea>
                            </div>

                            <!-- KETERANGAN -->

                            <div class="col-12">
                                <label for="keterangan" class="form-label">
                                    Keterangan
                                    <small class="text-muted">(Opsional)</small>
                                </label>

                                <textarea
                                    name="keterangan"
                                    id="keterangan"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Masukkan keterangan tambahan..."
                                ></textarea>
                            </div>

                            <!-- TOMBOL -->

                            <div class="col-12 d-flex flex-wrap gap-2 mt-3">

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    <span class="me-1">✓</span>
                                    Simpan Catatan
                                </button>

                                <button
                                    type="reset"
                                    class="btn btn-secondary"
                                >
                                    Reset
                                </button>

                            </div>

                        </div>

                    </form>

                <?php endif; ?>

            </div>

            <!-- =================================
                 TABEL CATATAN TERBARU
            ================================== -->

            <div class="content-card">

                <h5 class="card-title">
                    Catatan Pelanggaran Terbaru
                </h5>

                <p class="card-description">
                    Menampilkan maksimal 10 catatan yang belum selesai
                    dan dapat ditangani oleh akun guru yang sedang login.
                </p>

                <div class="table-responsive">

                    <table class="table table-hover">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Pelanggaran</th>
                                <th>Poin</th>
                                <th>Dicatat Oleh</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($catatanTerbaru)): ?>

                                <?php $no = 1; ?>

                                <?php foreach ($catatanTerbaru as $catatan): ?>

                                    <?php
                                    $status = strtolower($catatan['status'] ?? '');

                                    $kelasStatus = 'status-tercatat';

                                    if ($status === 'ditindak') {
                                        $kelasStatus = 'status-ditindak';
                                    } elseif ($status === 'selesai') {
                                        $kelasStatus = 'status-selesai';
                                    }
                                    ?>

                                    <tr>

                                        <td><?= $no++; ?></td>

                                        <td>
                                            <?= !empty($catatan['tanggal'])
                                                ? date('d-m-Y', strtotime($catatan['tanggal']))
                                                : '-'; ?>
                                        </td>

                                        <td>
                                            <?= e($catatan['nama_siswa']); ?>
                                        </td>

                                        <td>
                                            <?= e($catatan['nama_kelas'] ?: '-'); ?>
                                        </td>

                                        <td>
                                            <?= e($catatan['nama_pelanggaran']); ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-primary">
                                                <?= (int)$catatan['poin']; ?> poin
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($catatan['nama_guru']); ?>
                                        </td>

                                        <td>
                                            <span class="badge-status <?= e($kelasStatus); ?>">
                                                <?= e($status); ?>
                                            </span>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Belum ada catatan pelanggaran yang perlu ditangani.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- FOOTER -->

            <p class="footer-text">
                Sistem Informasi Pelanggaran Siswa &copy; 2026
            </p>

        </main>

    </div>
</div>

<!-- JAVASCRIPT -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('#siswa_id').select2({
            placeholder: '-- Cari dan Pilih Siswa --',
            allowClear: true,
            width: '100%'
        });
    });
</script>

</body>
</html>