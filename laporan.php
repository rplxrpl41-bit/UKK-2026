
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/helper_guru.php';

// =====================================
// PENGATURAN HALAMAN
// =====================================

$pageTitle = 'Laporan Pelanggaran';

// =====================================
// HELPER HTML
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
// AMBIL DATA LAPORAN
// =====================================

// Wali kelas / guru hanya melihat laporan yang dia catat atau dia tindak.
// Admin melihat semua laporan.
list($sql_batas, $tipe_batas, $param_batas) = batas_data_guru($koneksi, 'ps');

$sql = "
    SELECT
        ps.id,
        ps.nama_siswa,
        ps.nama_kelas,
        ps.nama_pelanggaran,
        ps.nama_guru,
        ps.tanggal,
        ps.poin,
        ps.tindakan,
        ps.status
    FROM t_pelanggaran_siswa ps
    " . ($sql_batas !== '' ? "WHERE $sql_batas" : '') . "
    ORDER BY ps.tanggal DESC, ps.id DESC
";

$stmt_laporan = mysqli_prepare($koneksi, $sql);

if ($stmt_laporan && $param_batas) {
    mysqli_stmt_bind_param($stmt_laporan, $tipe_batas, ...$param_batas);
}

$query = ($stmt_laporan && mysqli_stmt_execute($stmt_laporan))
    ? mysqli_stmt_get_result($stmt_laporan)
    : false;

if (!$query) {
    die(
        'Gagal mengambil data laporan: ' .
        e(mysqli_error($koneksi))
    );
}

$data_laporan = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data_laporan[] = $row;
}

// =====================================
// HITUNG STATISTIK
// =====================================

$total_laporan = count($data_laporan);
$total_poin = 0;
$total_siswa = [];

foreach ($data_laporan as $data) {
    $total_poin += (int) $data['poin'];

    $nama = $data['nama_siswa'];
    $total_siswa[$nama] = true;
}

$total_siswa_tercatat = count($total_siswa);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> - Sistem Informasi Pelanggaran Siswa</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        <?php include 'includes/crud_style.php'; ?>

        .stat-card {
            background: #ffffff;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: bold;
            color: #172554;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            background: #e8efff;
        }

        .content-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.05);
            margin-top: 25px;
            max-width: none;
        }

        .content-card h5 {
            font-weight: bold;
            color: #172554;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f1f5f9;
            color: #334155;
            font-size: 13px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table tbody td {
            font-size: 13px;
            vertical-align: middle;
        }

        .badge-status {
            background: #dcfce7;
            color: #166534;
            padding: 6px 10px;
            border-radius: 20px;
            display: inline-block;
            white-space: nowrap;
        }

        .badge-poin {
            background: #fff1c2;
            color: #92400e;
            padding: 6px 10px;
            border-radius: 20px;
            display: inline-block;
            white-space: nowrap;
        }

        .search-input {
            width: 240px;
        }

        @media (max-width: 768px) {
            .search-input {
                width: 100%;
            }
        }

        @media print {
            .sidebar,
            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                background: white !important;
            }

            .content-card,
            .stat-card {
                box-shadow: none !important;
                border: 1px solid #ddd;
            }

            .table {
                font-size: 11px;
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
            $menu_aktif = 'Laporan';
            include 'includes/crud_sidebar.php';
            ?>
        </div>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 main-content">

            <!-- JUDUL HALAMAN -->
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">

                <div>
                    <h1 class="page-title">
                        Laporan Pelanggaran Siswa
                    </h1>

                    <p class="page-description">
                        Rekap data pelanggaran siswa yang tercatat dalam sistem.
                    </p>
                </div>

                <div class="no-print">
                    <a
                        href="cetak_laporan.php"
                        target="_blank"
                        class="btn btn-primary"
                    >
                        🖨 Cetak Laporan
                    </a>
                </div>

            </div>

            <!-- STATISTIK -->
            <div class="row g-3">

                <div class="col-md-4">
                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="stat-label">
                                    Total Pelanggaran
                                </div>

                                <div class="stat-value">
                                    <?= number_format($total_laporan) ?>
                                </div>
                            </div>

                            <div class="stat-icon">
                                📋
                            </div>

                        </div>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="stat-label">
                                    Siswa Tercatat
                                </div>

                                <div class="stat-value">
                                    <?= number_format($total_siswa_tercatat) ?>
                                </div>
                            </div>

                            <div class="stat-icon">
                                👨‍🎓
                            </div>

                        </div>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-card">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>
                                <div class="stat-label">
                                    Total Poin
                                </div>

                                <div class="stat-value">
                                    <?= number_format($total_poin) ?>
                                </div>
                            </div>

                            <div class="stat-icon">
                                ⭐
                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <!-- TABEL LAPORAN -->
            <div class="content-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

                    <div>
                        <h5 class="mb-1">
                            Data Laporan Pelanggaran
                        </h5>

                        <small class="text-muted">
                            Daftar seluruh catatan pelanggaran siswa.
                        </small>
                    </div>

                    <div class="no-print mt-2">
                        <input
                            type="text"
                            id="cariLaporan"
                            class="form-control search-input"
                            placeholder="Cari laporan..."
                        >
                    </div>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-bordered table-hover"
                        id="tabelLaporan"
                    >

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jenis Pelanggaran</th>
                                <th>Guru</th>
                                <th>Tanggal</th>
                                <th>Poin</th>
                                <th>Tindakan</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($data_laporan) > 0): ?>

                                <?php $no = 1; ?>

                                <?php foreach ($data_laporan as $data): ?>

                                    <tr>

                                        <td>
                                            <?= $no++ ?>
                                        </td>

                                        <td>
                                            <?= e($data['nama_siswa']) ?>
                                        </td>

                                        <td>
                                            <?= e($data['nama_kelas']) ?>
                                        </td>

                                        <td>
                                            <?= e($data['nama_pelanggaran']) ?>
                                        </td>

                                        <td>
                                            <?= e($data['nama_guru']) ?>
                                        </td>

                                        <td class="text-nowrap">
                                            <?php
                                            if (!empty($data['tanggal'])) {
                                                echo e(date(
                                                    'd-m-Y',
                                                    strtotime($data['tanggal'])
                                                ));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <span class="badge-poin">
                                                <?= (int) $data['poin'] ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= !empty($data['tindakan'])
                                                ? e($data['tindakan'])
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <span class="badge-status">
                                                <?= !empty($data['status'])
                                                    ? e(ucfirst($data['status']))
                                                    : 'Belum ada status' ?>
                                            </span>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Belum ada data laporan pelanggaran.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="text-muted small mt-3">
                    Total <?= number_format($total_laporan) ?> data laporan.
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

<script>
// =====================================
// PENCARIAN DATA LAPORAN
// =====================================

const inputCari = document.getElementById('cariLaporan');

inputCari.addEventListener('input', function () {

    const keyword = this.value.toLowerCase();

    const rows = document.querySelectorAll(
        '#tabelLaporan tbody tr'
    );

    rows.forEach(function (row) {

        const text = row.innerText.toLowerCase();

        row.style.display = text.includes(keyword) ? '' : 'none';

    });

});
</script>

</body>
</html>
