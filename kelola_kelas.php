<?php
session_start();

include 'includes/cek_session.php';
include 'config/koneksi.php';

// Hanya admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak!');
}

// Ambil hanya kelas XII RPL yang aktif
$sql = "SELECT
            k.id,
            k.nama,
            k.tingkat,
            k.jurusan,
            k.status_aktif,
            (
                SELECT COUNT(DISTINCT ks.siswa_id)
                FROM t_kelas_siswa ks
                WHERE ks.kelas_id = k.id
                  AND ks.status_aktif = 1
            ) AS jumlah_siswa
        FROM t_kelas k
        WHERE k.nama = 'RPL'
          AND k.tingkat = 'XII'
          AND k.jurusan = 'RPL'
          AND k.status_aktif = 1
        ORDER BY k.id ASC";

$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die('Query gagal: ' . mysqli_error($koneksi));
}

function e($nilai)
{
    return htmlspecialchars((string)($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

$jumlah_kelas = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Kelas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }

        .sidebar { position: sticky; top: 0; height: 100vh; align-self: flex-start; overflow-y: auto;
            background: #172554;
            color: white;
            min-height: 100vh;
            padding: 22px 15px;
        }

        .sidebar-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            padding: 0 10px;
            line-height: 1.5;
        }

        .sidebar .nav-link {
            color: #e2e8f0;
            padding: 12px 14px;
            margin-bottom: 6px;
            border-radius: 6px;
        }

        .sidebar .nav-link:hover {
            background: #263b70;
            color: white;
        }

        .sidebar .nav-link.active {
            background: #3157d5;
            color: white;
        }

        .sidebar .nav-link.logout {
            color: #fca5a5;
        }

        .sidebar hr {
            border-color: #64748b;
        }

        .main-content {
            padding: 28px;
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

        .content-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            color: #172554;
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background: #2546b8;
            border-color: #2546b8;
        }

        #tabelKelas thead th {
            background: #172554;
            color: white;
            vertical-align: middle;
            white-space: nowrap;
        }

        #tabelKelas tbody td {
            vertical-align: middle;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #dcfce7;
            color: #166534;
        }

        .footer-text {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 25px;
        }

        @media (max-width: 767px) {
            .sidebar { position: static; height: auto; overflow-y: visible; align-self: auto;
                min-height: auto;
            }

            .main-content {
                padding: 18px;
            }

            .content-card {
                padding: 15px;
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

        <aside class="col-md-3 col-lg-2 sidebar">
            <div class="sidebar-title">Sistem Pelanggaran</div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link">⌂ Dashboard</a>
                <a href="kelola_siswa.php" class="nav-link">♟ Data Siswa</a>
                <a href="kelola_guru.php" class="nav-link">♟ Data Guru</a>
                <a href="kelola_kelas.php" class="nav-link active">▦ Data Kelas</a>
                <a href="kelola_tahun_ajaran.php" class="nav-link">◷ Kelola Tahun Ajaran</a>
                <a href="kelola_wali_kelas.php" class="nav-link">♙ Kelola Wali Kelas</a>
                <a href="kelola_penempatan_siswa.php" class="nav-link">⇄ Penempatan Siswa</a>
                <a href="kelola_kategori_pelanggaran.php" class="nav-link">☷ Kategori Pelanggaran</a>
                <a href="tentang_saya.php" class="nav-link">ℹ About Me</a>

                <hr>

                <a href="logout.php"
                   class="nav-link logout"
                   onclick="return confirm('Yakin ingin logout?')">
                    ⇥ Logout
                </a>
            </nav>
        </aside>

        <main class="col-md-9 col-lg-10 main-content">

            <h1 class="page-title">Kelola Data Kelas</h1>
            <p class="page-description">
                Kelola data kelas XII RPL yang aktif.
            </p>

            <div class="content-card">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="section-title">Data Kelas</h5>
                        <small class="text-muted">
                            Daftar kelas aktif XII RPL
                        </small>
                    </div>

                    <a href="tambah_kelas.php" class="btn btn-primary">
                        + Tambah Kelas
                    </a>
                </div>

                <?php if (isset($_SESSION['pesan'])): ?>
                    <div class="alert alert-info mt-3">
                        <?= e($_SESSION['pesan']); ?>
                    </div>
                    <?php unset($_SESSION['pesan']); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table id="tabelKelas"
                           class="table table-bordered table-striped table-hover"
                           style="width:100%">

                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kelas</th>
                                <th>Tingkat</th>
                                <th>Jurusan</th>
                                <th>Jumlah Siswa</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $no = 1;

                            while ($data = mysqli_fetch_assoc($query)):
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <strong>
                                            <?= e($data['tingkat'] . ' ' . $data['jurusan']); ?>
                                        </strong>
                                    </td>
                                    <td><?= e($data['tingkat']); ?></td>
                                    <td><?= e($data['jurusan']); ?></td>
                                    <td>
                                        <strong><?= (int)$data['jumlah_siswa']; ?></strong>
                                        Siswa
                                    </td>
                                    <td>
                                        <span class="status-badge">Aktif</span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>

                    </table>
                </div>

            </div>

            <p class="footer-text">
                Sistem Informasi Pelanggaran Siswa &copy; 2026
            </p>

        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    $('#tabelKelas').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        scrollX: true,
        language: {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data kelas',
            zeroRecords: 'Kelas tidak ditemukan',
            emptyTable: 'Belum ada data kelas aktif',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Berikutnya',
                previous: 'Sebelumnya'
            }
        }
    });
});
</script>

</body>
</html>

<?php
mysqli_free_result($query);
?>