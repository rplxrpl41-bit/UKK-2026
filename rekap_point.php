
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// =====================================
// AMBIL DATA REKAP POIN
// =====================================

$sql = "
    SELECT
        s.id,
        s.nis,
        s.nama,
        COUNT(ps.id) AS jumlah_pelanggaran,
        COALESCE(SUM(ps.poin), 0) AS total_poin
    FROM t_siswa s
    LEFT JOIN t_pelanggaran_siswa ps
        ON ps.siswa_id = s.id
    GROUP BY s.id, s.nis, s.nama
    ORDER BY total_poin DESC, s.nama ASC
";

$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die('Query gagal: ' . htmlspecialchars(
        mysqli_error($koneksi),
        ENT_QUOTES,
        'UTF-8'
    ));
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rekap Poin Pelanggaran Siswa</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        <?php include 'includes/crud_style.php'; ?>

        .content-card {
            max-width: none;
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
                background: white !important;
            }

            .content-card {
                box-shadow: none !important;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR KIRI -->
        <div class="col-md-3 col-lg-2 p-0">
            <?php
            $menu_aktif = 'Rekap Poin';
            include 'includes/crud_sidebar.php';
            ?>
        </div>

        <!-- KONTEN KANAN -->
        <main class="col-md-9 col-lg-10 content">

            <!-- JUDUL HALAMAN -->
            <h1 class="page-title">
                Rekap Poin Pelanggaran Siswa
            </h1>

            <p class="page-description">
                Jumlah pelanggaran dan total poin setiap siswa,
                diurutkan dari poin tertinggi.
            </p>

            <!-- TABEL REKAP POIN -->
            <div class="content-card">

                <div class="mb-3 no-print">
                    <button
                        type="button"
                        class="btn btn-outline-dark"
                        onclick="window.print()"
                    >
                        🖨 Cetak Laporan
                    </button>
                </div>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Jumlah Pelanggaran</th>
                                <th>Total Poin</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($query) > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($data = mysqli_fetch_assoc($query)): ?>

                                    <tr>
                                        <td>
                                            <?= $no++; ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $data['nis'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $data['nama'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        </td>

                                        <td>
                                            <?= (int) $data['jumlah_pelanggaran']; ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= (int) $data['total_poin']; ?>
                                            </strong>
                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada data rekap poin siswa.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

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
