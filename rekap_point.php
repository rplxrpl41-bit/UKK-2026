<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

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
    die('Query gagal: ' . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Poin Pelanggaran Siswa</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style><?php include 'includes/crud_style.php'; ?></style>
    <style>
        .content-card { max-width: none; }
        @media print {
            .sidebar, .no-print { display: none !important; }
            .main-content { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <?php $menu_aktif = 'Rekap Poin'; include 'includes/crud_sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Rekap Poin Pelanggaran Siswa</h1>
            <p class="page-description">Jumlah pelanggaran dan total poin setiap siswa, diurutkan dari poin tertinggi.</p>

            <div class="content-card">

                <div class="mb-3 no-print">
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()">Cetak Laporan</button>
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
                            <?php
                            $no = 1;
                            while ($data = mysqli_fetch_assoc($query)) {
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($data['nis'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($data['nama'] ?? ''); ?></td>
                                    <td><?= (int) $data['jumlah_pelanggaran']; ?></td>
                                    <td><strong><?= (int) $data['total_poin']; ?></strong></td>
                                </tr>
                            <?php } ?>
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
