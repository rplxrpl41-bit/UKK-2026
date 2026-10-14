
<?php

include 'includes/cek_session.php';
include 'config/koneksi.php';

// Ambil data guru beserta email akun user
$sql = "SELECT 
            t_guru.*,
            t_users.email AS email_user
        FROM t_guru
        LEFT JOIN t_users ON t_guru.user_id = t_users.id
        ORDER BY t_guru.id DESC";

$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Query data guru gagal: " . mysqli_error($koneksi));
}

// Ambil akun yang role-nya guru
$sql_user = "SELECT id, name, email
             FROM t_users
             WHERE role = 'guru'
             ORDER BY name ASC";

$query_user = mysqli_query($koneksi, $sql_user);

if (!$query_user) {
    die("Query akun guru gagal: " . mysqli_error($koneksi));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
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
                    <a href="kelola_kelas.php" class="nav-link text-white">
                        Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="laporan.php" class="nav-link text-white">
                        Laporan
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="tentang_saya.php" class="nav-link text-white">
                        About Me
                    </a>
                </li>
                <hr class="text-secondary">
                <li class="nav-item">
                    <a href="login.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Guru</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>

<body>
<<<<<<< HEAD
=======
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
                    <a href="kelola_kelas.php" class="nav-link text-white">
                        Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="laporan.php" class="nav-link text-white">
                        Laporan
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="tentang_saya.php" class="nav-link text-white">
                        About Me
                    </a>
                </li>
                <hr class="text-secondary">
                <li class="nav-item">
                    <a href="login.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">
>>>>>>> 385dba184835d1471028cfc31f21539087bdb38c
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
    <h1>Kelola Guru</h1>

    <?php if (isset($_SESSION['pesan'])) { ?>

        <p>
            <?= htmlspecialchars($_SESSION['pesan']); ?>
        </p>

        <?php unset($_SESSION['pesan']); ?>

    <?php } ?>

    <hr>

    <h2>Tambah Data Guru</h2>

    <form action="proses_guru.php" method="POST">

        <input type="hidden" name="aksi" value="tambah">

        <table class="table table-striped table-dark">

            <tr>
                <td>NIP</td>
                <td>:</td>
                <td>
                    <input type="text" name="nip" required>
                </td>
            </tr>

            <tr>
                <td>Nama Guru</td>
                <td>:</td>
                <td>
                    <input type="text" name="nama" required>
                </td>
            </tr>

            <tr>
                <td>Email</td>
                <td>:</td>
                <td>
                    <input type="email" name="email" required>
                </td>
            </tr>

            <tr>
                <td>Akun Guru</td>
                <td>:</td>
                <td>

                    <select name="user_id" required>

                        <option value="">
                            -- Pilih Akun Guru --
                        </option>

                        <?php while ($user = mysqli_fetch_assoc($query_user)) { ?>

                            <option value="<?= $user['id']; ?>">

                                <?= htmlspecialchars($user['name']); ?>
                                -
                                <?= htmlspecialchars($user['email']); ?>

                            </option>

                        <?php } ?>

                    </select>

                </td>
            </tr>

            <tr>
                <td>Status Aktif</td>
                <td>:</td>
                <td>

                    <select name="status_aktif" required>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>

                </td>
            </tr>

            <tr>
                <td colspan="3">
                    <button type="submit">
                        Tambah Guru
                    </button>
                </td>
            </tr>

        </table>
        <a href="dashboard.php">Kembali ke Dashboard</a>

    </form>

    <hr>

    <h2>Data Guru</h2>

    <table class="table table-striped table-dark">

        <thead>
            <tr>
                <th>ID</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Status</th>
                <th>User ID</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <body>

            <?php if (mysqli_num_rows($query) > 0) { ?>

                <?php while ($guru = mysqli_fetch_assoc($query)) { ?>

                    <tr>

                        <td>
                            <?= $guru['id']; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($guru['nip']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($guru['nama']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($guru['email_user'] ?? $guru['email'] ?? '-'); ?>
                        </td>

                        <td>

                            <?php if ($guru['status_aktif'] == 1) { ?>
                                Aktif
                            <?php } else { ?>
                                Tidak Aktif
                            <?php } ?>

                        </td>

                        <td>
                            <?= htmlspecialchars($guru['user_id'] ?? '-'); ?>
                        </td>

                        <td>

                            <a href="edit_guru.php?id=<?= $guru['id']; ?>">
                            <button type="button" class="btn btn-primary">edit</button>
                            </a>

                            |

                            <a href="proses_guru.php?aksi=hapus&id=<?= $guru['id']; ?>"
                               onclick="return confirm('Yakin ingin menghapus data guru ini?');">
                               <button type="button" class="btn btn-danger">hapus</button>
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="7">
                        Belum ada data guru.
                    </td>
                </tr>

            <?php } ?>

        </body>

    </table>

</body>
</html>
