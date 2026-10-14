<?php

session_start();

include 'config/koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

// CEK ROLE
if ($_SESSION['role'] != 'admin') {
    echo "Akses ditolak!";
    exit;
}

// AMBIL DATA GURU
$sql = "SELECT
            g.id,
            g.nip,
            g.nama,
            g.email,
            g.status_aktif,
            g.created_at,
            g.updated_at
        FROM t_guru g
        JOIN t_users u
            ON g.user_id = u.id
        WHERE u.role = 'guru'
        ORDER BY g.id DESC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    die("Gagal mengambil data guru: " . mysqli_error($koneksi));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <meta charset="UTF-8">
    <title>Kelola Guru</title>
</head>

<body>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white mb-4">
                My Website
            </h4>
            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a href="dashboard.php" class="nav-link text-white">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_siswa.php" class="nav-link text-white">
                        Data Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_guru.php" class="nav-link active">
                        Data Guru
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="Kelola_kelas.php" class="nav-link text-white">
                        Data Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_tahun_ajaran.php" class="nav-link text-white">
                        Kelola Tahun Ajaran
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_walikelas.php" class="nav-link text-white">
                        Kelola Walikelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="penempatan_siswa.php" class="nav-link text-white">
                        Penempatan Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_pelanggaran_kategori.php" class="nav-link text-white">
                        Pelanggaran Kategori
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="#" class="nav-link text-white">
                        About Me
                    </a>
                </li>
                
                <hr class="text-secondary">
                <li class="nav-item">
                    <a href="logoout.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">

<h1>Kelola Guru</h1>


<a href="dashboard.php">
    Kembali ke Dashboard
</a>

<br><br>

<a href="tambah_guru.php">
    Tambah Guru
</a>

<hr>

<h2>Data Guru</h2>

<table class="table table-sm table-dark">

    <tr>
        <th>No</th>
        <th>NIP</th>
        <th>Nama</th>
        <th>Email</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Updated At</th>
        <th>Aksi</th>
    </tr>

    <?php

    $no = 1;

    while ($guru = mysqli_fetch_assoc($hasil)) {

    ?>

    <tr>

        <td>
            <?php echo $no++; ?>
        </td>

        <td>
            <?php echo $guru['nip']; ?>
        </td>

        <td>
            <?php echo $guru['nama']; ?>
        </td>

        <td>
            <?php echo $guru['email']; ?>
        </td>

        <td>

            <?php

            if ($guru['status_aktif'] == 1) {
                echo "Aktif";
            } else {
                echo "Tidak Aktif";
            }

            ?>

        </td>

        <td>
            <?php echo $guru['created_at']; ?>
        </td>

        <td>
            <?php echo $guru['updated_at']; ?>
        </td>

        <td>

            <a href="edit_guru.php?id=<?php echo $guru['id']; ?>">
                <button type="button" class="btn btn-warning">Edit</button>
            </a>

            |

            <a
                href="hapus_guru.php?id=<?php echo $guru['id']; ?>"
                onclick="return confirm('Yakin ingin menghapus guru ini?')"
            >
                <button type="button" class="btn btn-danger">Hapus</button>
            </a>

        </td>

    </tr>

    <?php

    }

    ?>

</table>

</body>
</html>