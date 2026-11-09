<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

$tahun_ajaran_id = (int) ($_GET['tahun_ajaran_id'] ?? 0);
$kelas_id = (int) ($_GET['kelas_id'] ?? 0);

$query_tahun = mysqli_query(
    $koneksi,
    "SELECT id, nama FROM t_tahun_ajaran ORDER BY id DESC"
);

$query_kelas = mysqli_query(
    $koneksi,
    "SELECT id, nama, tingkat, jurusan
     FROM t_kelas
     ORDER BY tingkat, jurusan, nama"
);

$sql = "
    SELECT
        s.nis,
        s.nama AS nama_siswa,
        k.nama AS nama_kelas,
        k.tingkat,
        k.jurusan,
        ta.nama AS tahun_ajaran
    FROM t_kelas_siswa ks
    JOIN t_siswa s ON ks.siswa_id = s.id
    JOIN t_kelas k ON ks.kelas_id = k.id
    JOIN t_tahun_ajaran ta ON ks.tahun_ajaran_id = ta.id
    WHERE 1 = 1
";

if ($tahun_ajaran_id > 0) {
    $sql .= " AND ks.tahun_ajaran_id = $tahun_ajaran_id";
}

if ($kelas_id > 0) {
    $sql .= " AND ks.kelas_id = $kelas_id";
}

$sql .= " ORDER BY k.nama ASC, s.nama ASC";

$query_laporan = mysqli_query($koneksi, $sql);

if (!$query_laporan) {
    die("Gagal mengambil laporan: " . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penempatan Siswa</title>

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

        <?php $menu_aktif = 'Laporan'; include 'includes/crud_sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Laporan Penempatan Siswa</h1>
            <p class="page-description">Laporan data siswa berdasarkan kelas dan tahun ajaran.</p>

            <div class="content-card">

                <form method="GET" action="laporan.php" class="row g-2 mb-3 no-print">
                    <div class="col-md-4">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" class="form-select">
                            <option value="0">Semua Tahun Ajaran</option>
                            <?php while ($ta = mysqli_fetch_assoc($query_tahun)) { ?>
                                <option value="<?= (int) $ta['id'] ?>"
                                    <?= $tahun_ajaran_id == $ta['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ta['nama']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Kelas</label>
                        <select name="kelas_id" class="form-select">
                            <option value="0">Semua Kelas</option>
                            <?php while ($kelas = mysqli_fetch_assoc($query_kelas)) { ?>
                                <option value="<?= (int) $kelas['id'] ?>"
                                    <?= $kelas_id == $kelas['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kelas['nama'] . ' - ' . $kelas['tingkat'] . ' - ' . $kelas['jurusan']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                        <button type="button" class="btn btn-outline-dark" onclick="window.print()">Cetak Laporan</button>
                    </div>
                </form>

                <h5 class="mb-3">Data Penempatan Siswa</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Tingkat</th>
                                <th>Jurusan</th>
                                <th>Tahun Ajaran</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no = 1;

                            if (mysqli_num_rows($query_laporan) > 0) {
                                while ($row = mysqli_fetch_assoc($query_laporan)) {
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nis']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars($row['tingkat']) ?></td>
                                    <td><?= htmlspecialchars($row['jurusan']) ?></td>
                                    <td><?= htmlspecialchars($row['tahun_ajaran']) ?></td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada data penempatan siswa.</td>
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
