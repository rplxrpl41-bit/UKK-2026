
<?php
// =====================================
// CATATAN PELANGGARAN SISWA
// =====================================

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

// =====================================
// TOKEN CSRF
// =====================================

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// =====================================
// IDENTITAS GURU LOGIN
// =====================================

$guru_id = null;
$nama_guru = $_SESSION['name'] ?? 'Guru';

$user_id = (int) ($_SESSION['user_id'] ?? 0);

if ($user_id > 0) {

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id, nama FROM t_guru WHERE user_id = ? LIMIT 1"
    );

    if (!$stmt) {
        die('Gagal mengambil data guru: ' . e(mysqli_error($koneksi)));
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);

    mysqli_stmt_execute($stmt);

    $guru = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($guru) {
        $guru_id = (int) $guru['id'];
        $nama_guru = $guru['nama'] ?: $nama_guru;
    }
}

// =====================================
// PROSES SIMPAN
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

    // =====================================
    // VALIDASI CSRF
    // =====================================

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

    // =====================================
    // CEK DATA GURU
    // =====================================

    if (!$guru_id) {
        $kembali(
            'danger',
            'Data guru untuk akun ini belum ditemukan.'
        );
    }

    // =====================================
    // AMBIL INPUT FORM
    // =====================================

    $siswa_id = (int) ($_POST['siswa_id'] ?? 0);

    $pelanggaran_id = (int) ($_POST['pelanggaran_id'] ?? 0);

    $tanggal = trim($_POST['tanggal'] ?? '');

    $keterangan = trim($_POST['keterangan'] ?? '');

    $tindakan = trim($_POST['tindakan'] ?? '');

    // =====================================
    // VALIDASI TANGGAL
    // =====================================

    $tgl = DateTime::createFromFormat('Y-m-d', $tanggal);

    if (!$tgl || $tgl->format('Y-m-d') !== $tanggal) {

        $kembali(
            'danger',
            'Tanggal tidak valid.'
        );
    }

    if ($tanggal > date('Y-m-d')) {

        $kembali(
            'danger',
            'Tanggal pelanggaran tidak boleh di masa depan.'
        );
    }

    // =====================================
    // VALIDASI PILIHAN
    // =====================================

    if ($siswa_id <= 0 || $pelanggaran_id <= 0) {

        $kembali(
            'danger',
            'Siswa dan jenis pelanggaran wajib dipilih.'
        );
    }

    // =====================================
    // AMBIL DATA SISWA
    // =====================================

    $stmt = mysqli_prepare($koneksi, "
        SELECT
            s.nama,
            ks.kelas_id,
            ks.tahun_ajaran_id,
            k.nama AS nama_kelas
        FROM t_siswa s
        LEFT JOIN t_kelas_siswa ks
            ON ks.id = (
                SELECT MAX(x.id)
                FROM t_kelas_siswa x
                WHERE x.siswa_id = s.id
                AND x.status_aktif = 1
            )
        LEFT JOIN t_kelas k
            ON k.id = ks.kelas_id
        WHERE s.id = ?
        AND s.status_aktif = 1
        LIMIT 1
    ");

    if (!$stmt) {
        $kembali(
            'danger',
            'Gagal mengambil data siswa: ' . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $siswa_id
    );

    mysqli_stmt_execute($stmt);

    $siswa = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$siswa) {

        $kembali(
            'danger',
            'Siswa tidak ditemukan atau tidak aktif.'
        );
    }

    // =====================================
    // AMBIL JENIS PELANGGARAN
    // =====================================

    $stmt = mysqli_prepare($koneksi, "
        SELECT
            nama,
            poin,
            pelanggaran_kategori_id
        FROM t_pelanggaran
        WHERE id = ?
        AND status_aktif = 1
        LIMIT 1
    ");

    if (!$stmt) {
        $kembali(
            'danger',
            'Gagal mengambil jenis pelanggaran: ' . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $pelanggaran_id
    );

    mysqli_stmt_execute($stmt);

    $jenis = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$jenis) {

        $kembali(
            'danger',
            'Jenis pelanggaran tidak ditemukan atau tidak aktif.'
        );
    }

    // =====================================
    // SIAPKAN DATA SIMPAN
    // =====================================

    $kelas_id = $siswa['kelas_id'] !== null
        ? (int) $siswa['kelas_id']
        : null;

    $tahun_ajaran_id = $siswa['tahun_ajaran_id'] !== null
        ? (int) $siswa['tahun_ajaran_id']
        : null;

    $nama_kelas = $siswa['nama_kelas'] ?? '-';

    $poin = (int) $jenis['poin'];

    $kategori_id = $jenis['pelanggaran_kategori_id'] !== null
        ? (int) $jenis['pelanggaran_kategori_id']
        : null;

    $status = 'tercatat';

    // =====================================
    // SIMPAN CATATAN PELANGGARAN
    // =====================================

    $stmt = mysqli_prepare($koneksi, "
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
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $kembali(
            'danger',
            'Gagal menyiapkan penyimpanan: ' . mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iisisisiisssiss",
        $tahun_ajaran_id,
        $siswa_id,
        $siswa['nama'],
        $kelas_id,
        $nama_kelas,
        $pelanggaran_id,
        $jenis['nama'],
        $kategori_id,
        $guru_id,
        $nama_guru,
        $tanggal,
        $keterangan,
        $poin,
        $tindakan,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        $kembali(
            'success',
            'Catatan pelanggaran untuk ' .
            $siswa['nama'] .
            ' (+' . $poin . ' poin) berhasil disimpan.'
        );
    }

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    $kembali(
        'danger',
        'Gagal menyimpan catatan: ' . $error
    );
}

// =====================================
// DATA UNTUK FORM
// =====================================

$flash = $_SESSION['flash'] ?? null;

unset($_SESSION['flash']);

// =====================================
// DATA SISWA AKTIF
// =====================================

$q_siswa = mysqli_query($koneksi, "
    SELECT
        s.id,
        s.nis,
        s.nama,
        k.nama AS nama_kelas
    FROM t_siswa s
    LEFT JOIN t_kelas_siswa ks
        ON ks.id = (
            SELECT MAX(x.id)
            FROM t_kelas_siswa x
            WHERE x.siswa_id = s.id
            AND x.status_aktif = 1
        )
    LEFT JOIN t_kelas k
        ON k.id = ks.kelas_id
    WHERE s.status_aktif = 1
    ORDER BY s.nama ASC
");

if (!$q_siswa) {
    die('Gagal mengambil data siswa: ' . e(mysqli_error($koneksi)));
}

// =====================================
// DATA JENIS PELANGGARAN AKTIF
// =====================================

$q_jenis = mysqli_query($koneksi, "
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
");

if (!$q_jenis) {
    die('Gagal mengambil jenis pelanggaran: ' . e(mysqli_error($koneksi)));
}

// =====================================
// 10 CATATAN TERAKHIR
// =====================================

$q_terakhir = mysqli_query($koneksi, "
    SELECT
        tanggal,
        nama_siswa,
        nama_kelas,
        nama_pelanggaran,
        poin,
        nama_guru,
        status
    FROM t_pelanggaran_siswa
    WHERE status <> 'selesai'
    ORDER BY id DESC
    LIMIT 10
");

if (!$q_terakhir) {
    die('Gagal mengambil catatan terakhir: ' . e(mysqli_error($koneksi)));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Catatan Pelanggaran - Sistem Informasi Pelanggaran Siswa</title>

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- SELECT2 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"
    >

    <style>

        <?php include 'includes/crud_style.php'; ?>

        /* =====================================
           KONTEN
        ====================================== */

        .content-card {
            max-width: none;
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

        .form-label {
            font-weight: 600;
            color: #334155;
        }

        /* =====================================
           SELECT2 PENCARIAN SISWA
        ====================================== */

        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: #ffffff;
        }

        .select2-container--default .select2-selection--single
        .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
            padding-right: 30px;
            color: #212529;
        }

        .select2-container--default .select2-selection--single
        .select2-selection__arrow {
            height: 36px;
            right: 5px;
        }

        .select2-container--default.select2-container--focus
        .select2-selection--single {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .select2-container--default .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }

        .select2-container--default .select2-search--dropdown {
            padding: 8px;
        }

        .select2-container--default .select2-search--dropdown
        .select2-search__field {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            outline: none;
        }

        .select2-container--default .select2-search--dropdown
        .select2-search__field:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .select2-container--default .select2-results__option {
            padding: 9px 12px;
            font-size: 14px;
        }

        .select2-container--default
        .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #3157d5;
        }

        /* =====================================
           TABEL
        ====================================== */

        .table thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody td {
            vertical-align: middle;
        }

        /* =====================================
           TOMBOL
        ====================================== */

        .btn-primary {
            background-color: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background-color: #2446b5;
            border-color: #2446b5;
        }

        /* =====================================
           RESPONSIVE
        ====================================== */

        @media (max-width: 767px) {

            .main-content {
                padding: 18px;
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
            $menu_aktif = 'Catatan Pelanggaran';
            include 'includes/crud_sidebar.php';
            ?>

        </div>

        <!-- =====================================
             KONTEN KANAN
        ====================================== -->

        <main class="col-md-9 col-lg-10 main-content">

            <!-- JUDUL HALAMAN -->

            <h1 class="page-title">
                Catatan Pelanggaran
            </h1>

            <p class="page-description">
                Catat pelanggaran siswa. Poin diambil otomatis
                dari jenis pelanggaran yang dipilih.
            </p>

            <!-- =====================================
                 PESAN NOTIFIKASI
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
                 FORM CATATAN PELANGGARAN
            ====================================== -->

            <div class="content-card">

                <h5 class="mb-3">
                    Tambah Catatan Pelanggaran
                </h5>

                <form
                    method="POST"
                    action="catatan_pelanggaran.php"
                >

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e($_SESSION['csrf']) ?>"
                    >

                    <div class="row g-3">

                        <!-- =====================================
                             SISWA DENGAN PENCARIAN
                        ====================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="siswa_id"
                            >
                                Siswa
                            </label>

                            <select
                                name="siswa_id"
                                id="siswa_id"
                                class="form-select"
                                required
                            >

                                <option value=""></option>

                                <?php while ($s = mysqli_fetch_assoc($q_siswa)): ?>

                                    <option
                                        value="<?= (int) $s['id'] ?>"
                                    >
                                        <?= e($s['nama']) ?>
                                        — <?= e($s['nis']) ?>
                                        (<?= e($s['nama_kelas'] ?? 'belum ada kelas') ?>)
                                    </option>

                                <?php endwhile; ?>

                            </select>

                            <small class="text-muted">
                                Ketik nama siswa, NIS, atau kelas untuk mencari.
                            </small>

                        </div>

                        <!-- =====================================
                             JENIS PELANGGARAN
                        ====================================== -->

                        <div class="col-md-6">

                            <label
                                class="form-label"
                                for="pelanggaran_id"
                            >
                                Jenis Pelanggaran
                            </label>

                            <select
                                name="pelanggaran_id"
                                id="pelanggaran_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Pilih Jenis Pelanggaran --
                                </option>

                                <?php while ($j = mysqli_fetch_assoc($q_jenis)): ?>

                                    <option
                                        value="<?= (int) $j['id'] ?>"
                                    >
                                        <?= e($j['kode']) ?>
                                        — <?= e($j['nama']) ?>
                                        (<?= (int) $j['poin'] ?> poin)
                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>

                        <!-- =====================================
                             TANGGAL
                        ====================================== -->

                        <div class="col-md-4">

                            <label
                                class="form-label"
                                for="tanggal"
                            >
                                Tanggal Kejadian
                            </label>

                            <input
                                type="date"
                                name="tanggal"
                                id="tanggal"
                                class="form-control"
                                value="<?= date('Y-m-d') ?>"
                                max="<?= date('Y-m-d') ?>"
                                required
                            >

                        </div>

                        <!-- =====================================
                             TINDAKAN AWAL
                        ====================================== -->

                        <div class="col-md-8">

                            <label
                                class="form-label"
                                for="tindakan"
                            >
                                Tindakan Awal

                                <span class="text-muted fw-normal">
                                    (opsional)
                                </span>
                            </label>

                            <input
                                type="text"
                                name="tindakan"
                                id="tindakan"
                                class="form-control"
                                maxlength="255"
                                placeholder="Contoh: Teguran lisan"
                            >

                        </div>

                        <!-- =====================================
                             KETERANGAN
                        ====================================== -->

                        <div class="col-12">

                            <label
                                class="form-label"
                                for="keterangan"
                            >
                                Keterangan

                                <span class="text-muted fw-normal">
                                    (opsional)
                                </span>
                            </label>

                            <textarea
                                name="keterangan"
                                id="keterangan"
                                rows="3"
                                class="form-control"
                                placeholder="Kronologi singkat kejadian"
                            ></textarea>

                        </div>

                    </div>

                    <!-- =====================================
                         TOMBOL
                    ====================================== -->

                    <div class="mt-4 d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan Catatan
                        </button>

                        <button
                            type="reset"
                            class="btn btn-outline-secondary"
                        >
                            Reset
                        </button>

                    </div>

                </form>

            </div>

            <!-- =====================================
                 TABEL CATATAN TERAKHIR
            ====================================== -->

            <div class="content-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                    <h5 class="mb-0">
                        10 Catatan Terakhir (Belum Selesai)
                    </h5>

                    <a
                        href="riwayat_pelanggaran.php"
                        class="btn btn-sm btn-outline-primary"
                    >
                        Lihat Semua Riwayat
                    </a>

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
                                <th>Dicatat Oleh</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($q_terakhir) === 0): ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="text-center text-muted py-4"
                                    >
                                        Tidak ada catatan yang masih berjalan.
                                        Catatan yang sudah selesai ada di Riwayat Pelanggaran.
                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php while ($r = mysqli_fetch_assoc($q_terakhir)): ?>

                                    <tr>

                                        <td>
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
                                        </td>

                                        <td>

                                            <span class="badge text-bg-primary">
                                                <?= (int) $r['poin'] ?> poin
                                            </span>

                                        </td>

                                        <td>
                                            <?= e($r['nama_guru']) ?>
                                        </td>

                                        <td>

                                            <span class="badge text-bg-secondary">
                                                <?= e(ucfirst($r['status'])) ?>
                                            </span>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- FOOTER -->

            <p class="text-muted small mt-4">
                Sistem Informasi Pelanggaran Siswa &copy; 2026
            </p>

        </main>

    </div>

</div>

<!-- =====================================
     JAVASCRIPT
====================================== -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- JQUERY -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {

    // =====================================
    // PENCARIAN SISWA
    // =====================================

    $('#siswa_id').select2({

        placeholder: '-- Pilih Siswa --',

        allowClear: false,

        minimumResultsForSearch: 0,

        width: '100%',

        language: {
            noResults: function () {
                return 'Siswa tidak ditemukan';
            },

            searching: function () {
                return 'Sedang mencari...';
            }
        }

    });

});
</script>

</body>
</html>
