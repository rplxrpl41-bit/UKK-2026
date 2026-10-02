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
| Menu 3 hanya untuk Admin dan Guru
|--------------------------------------------------------------------------
*/
if (!in_array($role, ['admin', 'guru'])) {
    die("Akses ditolak");
}

require 'koneksi.php';

/*
|--------------------------------------------------------------------------
| TAMBAH DATA
|--------------------------------------------------------------------------
*/
if (isset($_POST['tambah'])) {

    $kode = trim($_POST['kode']);
    $nama_pelanggaran = trim($_POST['nama_pelanggaran']);

    if ($kode == '' || $nama_pelanggaran == '') {
        echo "<script>
                alert('Kode dan nama pelanggaran wajib diisi!');
                window.location='menu3.php';
              </script>";
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO t_pelanggaran
            (kode, nama_pelanggaran)
            VALUES
            (:kode, :nama_pelanggaran)
        ");

        $stmt->execute([
            ':kode' => $kode,
            ':nama_pelanggaran' => $nama_pelanggaran
        ]);

        echo "<script>
                alert('Jenis pelanggaran berhasil ditambahkan!');
                window.location='menu3.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Gagal menambahkan data: " . addslashes($e->getMessage()) . "');
                window.location='menu3.php';
              </script>";
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE DATA
|--------------------------------------------------------------------------
*/
if (isset($_POST['edit'])) {

    $id = $_POST['id'];
    $kode = trim($_POST['kode']);
    $nama_pelanggaran = trim($_POST['nama_pelanggaran']);

    if ($kode == '' || $nama_pelanggaran == '') {
        echo "<script>
                alert('Kode dan nama pelanggaran wajib diisi!');
                window.location='menu3.php';
              </script>";
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE t_pelanggaran
            SET
                kode = :kode,
                nama_pelanggaran = :nama_pelanggaran
            WHERE id = :id
        ");

        $stmt->execute([
            ':kode' => $kode,
            ':nama_pelanggaran' => $nama_pelanggaran,
            ':id' => $id
        ]);

        echo "<script>
                alert('Jenis pelanggaran berhasil diperbarui!');
                window.location='menu3.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Gagal memperbarui data: " . addslashes($e->getMessage()) . "');
                window.location='menu3.php';
              </script>";
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS DATA
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM t_pelanggaran
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        echo "<script>
                alert('Jenis pelanggaran berhasil dihapus!');
                window.location='menu3.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Data tidak dapat dihapus. Data mungkin masih digunakan.');
                window.location='menu3.php';
              </script>";
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/
$data_edit = null;

if (isset($_GET['edit'])) {

    $id = $_GET['edit'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM t_pelanggaran
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $data_edit = $stmt->fetch(PDO::FETCH_ASSOC);
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
        FROM t_pelanggaran
        WHERE kode LIKE :cari
        OR nama_pelanggaran LIKE :cari
        ORDER BY id DESC
    ");

    $stmt->execute([
        ':cari' => "%$cari%"
    ]);

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM t_pelanggaran
        ORDER BY id DESC
    ");
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Kelola Jenis Pelanggaran</title>

</head>

<body>

<h1>Kelola Jenis Pelanggaran</h1>

<p>
    Selamat datang, <b><?= htmlspecialchars($name) ?></b>
    | Role: <b><?= htmlspecialchars($role) ?></b>
</p>

<hr>

<!-- MENU -->

<a href="dashboard.php">Dashboard</a> |

<?php if ($role == 'admin'): ?>

    <a href="menu1.php">Kelola Siswa</a> |
    <a href="menu2.php">Kelola Guru</a> |

<?php endif; ?>

<a href="menu3.php">Kelola Jenis Pelanggaran</a> |
<a href="menu4.php">Admin</a> |
<a href="kelola_kelas.php">Kelola Kelas</a> |
<a href="kelola_tahun_ajaran.php">Kelola Tahun Ajaran</a> |
<a href="penempatan_siwa.php">Penempatan Siswa</a> |
<a href="kelola_wali_kelas.php">Kelola Wali Kelas</a> |
<a href="kelola_kategori_pelanggaran.php">Kelola Kategori Pelanggaran</a> |
<a href="laporan.php">Laporan</a> |
<a href="logout.php">Logout</a>

<hr>

<h2>
    <?= $data_edit ? 'Edit Jenis Pelanggaran' : 'Tambah Jenis Pelanggaran' ?>
</h2>

<form method="POST">

    <?php if ($data_edit): ?>

        <input
            type="hidden"
            name="id"
            value="<?= htmlspecialchars($data_edit['id']) ?>"
        >

    <?php endif; ?>

    <p>
        <label>Kode Pelanggaran</label><br>

        <input
            type="text"
            name="kode"
            placeholder="Contoh: PG-001"
            value="<?= $data_edit ? htmlspecialchars($data_edit['kode']) : '' ?>"
            required
        >
    </p>

    <p>
        <label>Nama Pelanggaran</label><br>

        <input
            type="text"
            name="nama_pelanggaran"
            placeholder="Contoh: Terlambat datang ke sekolah"
            value="<?= $data_edit ? htmlspecialchars($data_edit['nama_pelanggaran']) : '' ?>"
            required
        >
    </p>

    <?php if ($data_edit): ?>

        <button type="submit" name="edit">
            Update
        </button>

        <a href="menu3.php">
            Batal
        </a>

    <?php else: ?>

        <button type="submit" name="tambah">
            Simpan
        </button>

    <?php endif; ?>

</form>

<hr>

<h2>Data Jenis Pelanggaran</h2>

<!-- PENCARIAN -->

<form method="GET">

    <input
        type="text"
        name="cari"
        placeholder="Cari kode atau nama pelanggaran..."
        value="<?= htmlspecialchars($cari) ?>"
    >

    <button type="submit">
        Cari
    </button>

    <a href="menu3.php">
        Reset
    </a>

</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">

    <tr>

        <th>No</th>
        <th>ID</th>
        <th>Kode</th>
        <th>Nama Pelanggaran</th>
        <th>Aksi</th>

    </tr>

    <?php if (count($data) > 0): ?>

        <?php $no = 1; ?>

        <?php foreach ($data as $row): ?>

            <tr>

                <td>
                    <?= $no++ ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['id']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['kode']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($row['nama_pelanggaran']) ?>
                </td>

                <td>

                    <a href="menu3.php?edit=<?= $row['id'] ?>">
                        Edit
                    </a>

                    |

                    <a
                        href="menu3.php?hapus=<?= $row['id'] ?>"
                        onclick="return confirm('Yakin ingin menghapus jenis pelanggaran ini?')"
                    >
                        Hapus
                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>

            <td colspan="5">
                Belum ada data jenis pelanggaran.
            </td>

        </tr>

    <?php endif; ?>

</table>

</body>
</html>