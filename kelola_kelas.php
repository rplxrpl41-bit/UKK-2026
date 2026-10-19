
<?php
session_start();
include 'config/koneksi.php';

// Cek login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// Cek hak akses admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    http_response_code(403);
    exit("Akses ditolak!");
}

// Ambil data penempatan siswa
$sql = "SELECT
            ks.*,
            s.nama AS nama_siswa,
            k.nama AS nama_kelas,
            k.tingkat,
            k.jurusan,
            ta.nama AS tahun_ajaran
        FROM t_kelas_siswa ks
        LEFT JOIN t_siswa s ON ks.siswa_id = s.id
        LEFT JOIN t_kelas k ON ks.kelas_id = k.id
        LEFT JOIN t_tahun_ajaran ta ON ks.tahun_ajaran_id = ta.id
        ORDER BY ks.id DESC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    die("Gagal mengambil data: " . mysqli_error($koneksi));
}

// Fungsi keamanan output HTML
function e($nilai) {
    return htmlspecialchars(
        (string)($nilai ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Kelola Kelas</title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- DataTables Bootstrap 4 -->
    <link rel="stylesheet"
          href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        /* SIDEBAR - TETAP SEPERTI CONTOH */
        .sidebar {
            background: #172554;
            color: white;
            min-height: 100vh;
            padding: 22px 15px;
        }

        .sidebar h4 {
            font-weight: bold;
            margin-bottom: 30px;
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
            font-weight: bold;
            margin-bottom: 8px;
        }

        .page-description {
            color: #64748b;
        }

        /* KARTU */
        .card-main {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        /* TABEL */
        .table-responsive {
            overflow-x: auto;
        }

        .table thead th {
            background: #172554;
            color: white;
            white-space: nowrap;
            vertical-align: middle;
        }

        .table td {
            vertical-align: middle;
        }

        /* STATUS */
        .badge-status {
            padding: 7px 10px;
            border-radius: 5px;
            display: inline-block;
            white-space: nowrap;
        }

        .status-aktif {
            background: #d1fae5;
            color: #065f46;
        }

        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }

        /* TOMBOL */
        .btn-primary {
            background: #3157d5;
            border-color: #3157d5;
        }

        .btn-primary:hover {
            background: #2445b8;
            border-color: #2445b8;
        }

        .dataTables_wrapper {
            padding-top: 8px;
        }

        .dataTables_filter input,
        .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 5px 8px;
        }

        .dataTables_info {
            color: #64748b;
            font-size: 13px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
            }

            .content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR: TIDAK DITAMBAH ATAU DIKURANGI -->
        <aside class="col-md-3 col-lg-2 sidebar">

            <h4>My Website</h4>

            <ul class="nav nav-pills flex-column">

                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        &#8962; Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_siswa.php" class="nav-link">
                        &#9813; Data Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_guru.php" class="nav-link">
                        &#9813; Data Guru
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kelas.php" class="nav-link active">
                        &#9638; Data Kelas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_tahun_ajaran.php" class="nav-link">
                        &#9719; Kelola Tahun Ajaran
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_wali_kelas.php" class="nav-link">
                        &#9817; Kelola Wali Kelas
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_penempatan_siswa.php" class="nav-link">
                        &#8644; Penempatan Siswa
                    </a>
                </li>

                <li class="nav-item">
                    <a href="kelola_kategori_pelanggaran.php" class="nav-link">
                        &#9776; Kategori Pelanggaran
                    </a>
                </li>

                <li class="nav-item">
                    <a href="tentang_saya.php" class="nav-link">
                        &#8505; About Me
                    </a>
                </li>

                <hr style="border-color: #475569;">

                <li class="nav-item">
                    <a href="logout.php"
                       class="nav-link logout"
                       onclick="return confirm('Yakin ingin logout?')">
                        &#10140; Logout
                    </a>
                </li>

            </ul>
        </aside>

        <!-- KONTEN UTAMA -->
        <main class="col-md-9 col-lg-10 content">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center mb-4">

                <div>
                    <h2 class="page-title">Kelola Kelas Siswa</h2>

                    <p class="page-description mb-0">
                        Kelola penempatan siswa berdasarkan kelas dan tahun ajaran.
                    </p>
                </div>

                <a href="tambah_kelas.php"
                   class="btn btn-primary mt-3 mt-md-0">
                    + Tambah Kelas Siswa
                </a>

            </div>

            <!-- TABEL PENEMPATAN SISWA -->
            <div class="card card-main">
                <div class="card-body">

                    <h5 class="font-weight-bold mb-3">
                        Daftar Penempatan Siswa
                    </h5>

                    <div class="table-responsive">

                        <table id="tabelKelas"
                               class="table table-bordered table-striped
                                      table-hover table-sm"
                               style="width:100%">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Tanggal Mulai</th>
                                    <th>Tanggal Selesai</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php
                            $no = 1;

                            while ($data = mysqli_fetch_assoc($hasil)) {
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <strong>
                                            <?= e($data['nama_siswa'] ?? '-') ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e($data['nama_kelas'] ?? '-') ?>

                                        <?php if (
                                            !empty($data['tingkat']) ||
                                            !empty($data['jurusan'])
                                        ) { ?>
                                            <br>
                                            <small class="text-muted">
                                                <?= e($data['tingkat'] ?? '') ?>
                                                <?= e($data['jurusan'] ?? '') ?>
                                            </small>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <?= e($data['tahun_ajaran'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?= e($data['tanggal_mulai'] ?? '-') ?>
                                    </td>

                                    <td>
                                        <?php
                                        $tanggalSelesai =
                                            $data['tanggal_selesai'] ?? '';

                                        if (
                                            $tanggalSelesai === '' ||
                                            $tanggalSelesai === '0000-00-00'
                                        ) {
                                            echo '-';
                                        } else {
                                            echo e($tanggalSelesai);
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            (int)($data['status_aktif'] ?? 0) === 1
                                        ) { ?>
                                            <span class="badge-status status-aktif">
                                                Aktif
                                            </span>
                                        <?php } else { ?>
                                            <span class="badge-status status-nonaktif">
                                                Tidak Aktif
                                            </span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <div class="action-buttons">

                                            <a
                                                href="edit_kelas.php?id=<?= (int)$data['id'] ?>"
                                                class="btn btn-warning btn-sm">
                                                Edit
                                            </a>

                                            <a
                                                href="hapus_kelas.php?id=<?= (int)$data['id'] ?>"
                                                class="btn btn-danger btn-sm"
                                                onclick="return confirm('Yakin ingin menghapus data ini?')">
                                                Hapus
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>

                        </table>

                    </div>

                    <a href="dashboard.php"
                       class="btn btn-secondary btn-sm mt-3">
                        Kembali ke Dashboard
                    </a>

                </div>
            </div>

            <p class="text-muted text-center mt-4">
                &copy; <?= date('Y') ?> Sistem Informasi Pelanggaran Siswa
            </p>

        </main>

    </div>
</div>

<!-- JQUERY -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- BOOTSTRAP 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DATATABLES -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function () {

    $('#tabelKelas').DataTable({
        processing: true,
        pageLength: 10,

        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'Semua']
        ],

        ordering: true,
        searching: true,
        autoWidth: false,

        columnDefs: [
            {
                targets: [0, 7],
                orderable: false,
                searchable: false
            }
        ],

        language: {
            search: 'Cari:',
            searchPlaceholder: 'Cari penempatan siswa...',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data penempatan siswa',
            infoFiltered: '(disaring dari _MAX_ seluruh data)',
            zeroRecords: 'Data yang dicari tidak ditemukan',
            emptyTable: 'Belum ada data penempatan siswa',
            processing: 'Memproses data...',
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
mysqli_free_result($hasil);
?>
