<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';

/*
|--------------------------------------------------------------------------
| HANYA ADMIN
|--------------------------------------------------------------------------
*/
if ($role != 'admin') {
    die("Akses ditolak");
}

require 'koneksi.php';

/*
|--------------------------------------------------------------------------
| TAMBAH DATA
|--------------------------------------------------------------------------
*/
if (isset($_POST['tambah'])) {

    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($nama == '' || $deskripsi == '') {

        echo "<script>
                alert('Nama dan deskripsi wajib diisi!');
                window.location='kelola_kategori_pelanggaran.php';
              </script>";
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO t_pelanggaran_kategori
        (nama, deskripsi)
        VALUES
        (:nama, :deskripsi)
    ");

    $stmt->execute([
        ':nama' => $nama,
        ':deskripsi' => $deskripsi
    ]);

    header("Location: kelola_kategori_pelanggaran.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| HAPUS DATA
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {

    $id = intval($_GET['hapus']);

    $stmt = $pdo->prepare("
        DELETE FROM t_pelanggaran_kategori
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    header("Location: kelola_kategori_pelanggaran.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/
if (isset($_POST['edit'])) {

    $id = intval($_POST['id']);
    $nama = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($nama == '' || $deskripsi == '') {

        echo "<script>
                alert('Nama dan deskripsi wajib diisi!');
                history.back();
              </script>";
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE t_pelanggaran_kategori
        SET
            nama = :nama,
            deskripsi = :deskripsi
        WHERE id = :id
    ");

    $stmt->execute([
        ':nama' => $nama,
        ':deskripsi' => $deskripsi,
        ':id' => $id
    ]);

    header("Location: kelola_kategori_pelanggaran.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA UNTUK EDIT
|--------------------------------------------------------------------------
*/
$dataEdit = null;

if (isset($_GET['edit'])) {

    $idEdit = intval($_GET['edit']);

    $stmt = $pdo->prepare("
        SELECT *
        FROM t_pelanggaran_kategori
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $idEdit
    ]);

    $dataEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| PENCARIAN
|--------------------------------------------------------------------------
*/
$cari = $_GET['cari'] ?? '';

if ($cari != '') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM t_pelanggaran_kategori
        WHERE nama LIKE :cari
        OR deskripsi LIKE :cari
        ORDER BY id ASC
    ");

    $stmt->execute([
        ':cari' => '%' . $cari . '%'
    ]);

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM t_pelanggaran_kategori
        ORDER BY id ASC
    ");

}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Kelola Kategori Pelanggaran</title>

</head>

<body>

<h1>Dashboard</h1>

<p>
    Selamat datang,
    <b><?= htmlspecialchars($name) ?></b>
    (<?= htmlspecialchars($role) ?>)
</p>

<hr>

<!-- =====================================================
     MENU
===================================================== -->

<a href="dashboard.php">Dashboard</a>
<br><br>

<a href="menu1.php">Kelola Siswa</a>
<br><br>

<a href="menu2.php">Kelola Guru</a>
<br><br>

<a href="menu3.php">Kelola Jenis Pelanggaran</a>
<br><br>

<a href="menu4.php">Admin</a>
<br><br>

<a href="kelola_kelas.php">Kelola Kelas</a>
<br><br>

<a href="kelola_tahun_ajaran.php">
    Kelola Tahun Ajaran
</a>
<br><br>

<a href="penempatan_siwa.php">
    Penempatan Siwa
</a>
<br><br>

<a href="kelola_wali_kelas.php">
    Kelola Wali Kelas
</a>
<br><br>

<a href="kelola_kategori_pelanggaran.php">
    Kelola Kategori Pelanggaran
</a>
<br><br>

<a href="laporan.php">
    Laporan
</a>
<br><br>

<a href="logout.php">
    Logout
</a>

<hr>

<!-- =====================================================
     JUDUL
===================================================== -->

<h2>Kelola Kategori Pelanggaran</h2>

<p>
    Halaman ini digunakan untuk mengelola kategori pelanggaran
    yang digunakan dalam sistem pelanggaran siswa.
</p>

<hr>

<!-- =====================================================
     FORM TAMBAH / EDIT
===================================================== -->

<?php if ($dataEdit): ?>

    <h3>Edit Kategori Pelanggaran</h3>

    <form method="POST">

        <input
            type="hidden"
            name="id"
            value="<?= htmlspecialchars($dataEdit['id']) ?>"
        >

        <p>

            <label>Nama Pelanggaran</label>
            <br>

            <input
                type="text"
                name="nama"
                value="<?= htmlspecialchars($dataEdit['nama']) ?>"
                required
            >

        </p>

        <p>

            <label>Deskripsi</label>
            <br>

            <textarea
                name="deskripsi"
                rows="5"
                cols="60"
                required
            ><?= htmlspecialchars($dataEdit['deskripsi']) ?></textarea>

        </p>

        <button type="submit" name="edit">
            Simpan Perubahan
        </button>

        <a href="kelola_kategori_pelanggaran.php">
            Batal
        </a>

    </form>

<?php else: ?>

    <h3>Tambah Kategori Pelanggaran</h3>

    <form method="POST">

        <p>

            <label>Nama Pelanggaran</label>
            <br>

            <input
                type="text"
                name="nama"
                placeholder="Contoh: Terlambat Masuk Sekolah"
                required
            >

        </p>

        <p>

            <label>Deskripsi</label>
            <br>

            <textarea
                name="deskripsi"
                rows="5"
                cols="60"
                placeholder="Jelaskan jenis pelanggaran..."
                required
            ></textarea>

        </p>

        <button type="submit" name="tambah">
            Tambah Kategori
        </button>

    </form>

<?php endif; ?>

<hr>

<!-- =====================================================
     PENCARIAN
===================================================== -->

<h3>Cari Kategori</h3>

<form method="GET">

    <input
        type="text"
        name="cari"
        value="<?= htmlspecialchars($cari) ?>"
        placeholder="Cari nama atau deskripsi..."
    >

    <button type="submit">
        Cari
    </button>

    <a href="kelola_kategori_pelanggaran.php">
        Reset
    </a>

</form>

<hr>

<!-- =====================================================
     TABEL DATA
===================================================== -->

<h3>Daftar Kategori Pelanggaran</h3>

<table border="1" cellpadding="8" cellspacing="0">

    <thead>

        <tr>

            <th>ID</th>

            <th>Nama Pelanggaran</th>

            <th>Deskripsi</th>

            <th>Aksi</th>

        </tr>

    </thead>

    <tbody>

        <?php if (count($data) > 0): ?>

            <?php foreach ($data as $row): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($row['id']) ?>
                    </td>

                    <td>
                        <b>
                            <?= htmlspecialchars($row['nama']) ?>
                        </b>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['deskripsi']) ?>
                    </td>

                    <td>

                        <a
                            href="kelola_kategori_pelanggaran.php?edit=<?= $row['id'] ?>"
                        >
                            Edit
                        </a>

                        &nbsp;|&nbsp;

                        <a
                            href="kelola_kategori_pelanggaran.php?hapus=<?= $row['id'] ?>"
                            onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                        >
                            Hapus
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php else: ?>

            <tr>

                <td colspan="4">
                    Belum ada data kategori pelanggaran.
                </td>

            </tr>

        <?php endif; ?>

    </tbody>

</table>

<hr>

<p>
    Jumlah kategori:
    <b><?= count($data) ?></b>
</p>

</body>

</html>