<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';

/* Hanya Admin yang boleh mengelola siswa */
if ($role != 'admin') {
    die("Akses ditolak");
}

require 'koneksi.php';


/* =========================
   TAMBAH SISWA
========================= */

if (isset($_POST['tambah'])) {

    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $jenis_kelamin = trim($_POST['jenis_kelamin']);
    $kelas = trim($_POST['kelas']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);

    if ($nis == '' || $nama == '' || $jenis_kelamin == '' || $kelas == '') {

        echo "<script>
                alert('NIS, nama, jenis kelamin, dan kelas wajib diisi!');
                window.location='menu1.php';
              </script>";
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            INSERT INTO t_siswa
            (nis, nama, jenis_kelamin, kelas, alamat, no_hp)
            VALUES
            (:nis, :nama, :jenis_kelamin, :kelas, :alamat, :no_hp)
        ");

        $stmt->execute([
            ':nis' => $nis,
            ':nama' => $nama,
            ':jenis_kelamin' => $jenis_kelamin,
            ':kelas' => $kelas,
            ':alamat' => $alamat,
            ':no_hp' => $no_hp
        ]);

        echo "<script>
                alert('Data siswa berhasil ditambahkan!');
                window.location='menu1.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Gagal menambahkan siswa: " . addslashes($e->getMessage()) . "');
                window.location='menu1.php';
              </script>";
        exit;
    }
}


/* =========================
   EDIT SISWA
========================= */

if (isset($_POST['edit'])) {

    $id = $_POST['id'];
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $jenis_kelamin = trim($_POST['jenis_kelamin']);
    $kelas = trim($_POST['kelas']);
    $alamat = trim($_POST['alamat']);
    $no_hp = trim($_POST['no_hp']);

    try {

        $stmt = $pdo->prepare("
            UPDATE t_siswa
            SET
                nis = :nis,
                nama = :nama,
                jenis_kelamin = :jenis_kelamin,
                kelas = :kelas,
                alamat = :alamat,
                no_hp = :no_hp
            WHERE id = :id
        ");

        $stmt->execute([
            ':nis' => $nis,
            ':nama' => $nama,
            ':jenis_kelamin' => $jenis_kelamin,
            ':kelas' => $kelas,
            ':alamat' => $alamat,
            ':no_hp' => $no_hp,
            ':id' => $id
        ]);

        echo "<script>
                alert('Data siswa berhasil diperbarui!');
                window.location='menu1.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Gagal memperbarui siswa: " . addslashes($e->getMessage()) . "');
                window.location='menu1.php';
              </script>";
        exit;
    }
}


/* =========================
   HAPUS SISWA
========================= */

if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM t_siswa
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        echo "<script>
                alert('Data siswa berhasil dihapus!');
                window.location='menu1.php';
              </script>";
        exit;

    } catch (PDOException $e) {

        echo "<script>
                alert('Data siswa tidak dapat dihapus karena masih digunakan!');
                window.location='menu1.php';
              </script>";
        exit;
    }
}


/* =========================
   DATA UNTUK EDIT
========================= */

$data_edit = null;

if (isset($_GET['edit'])) {

    $id = $_GET['edit'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM t_siswa
        WHERE id = :id
    ");

    $stmt->execute([
        ':id' => $id
    ]);

    $data_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}


/* =========================
   PENCARIAN
========================= */

$cari = $_GET['cari'] ?? '';

if ($cari != '') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM t_siswa
        WHERE nis LIKE :cari
        OR nama LIKE :cari
        OR kelas LIKE :cari
        ORDER BY id DESC
    ");

    $stmt->execute([
        ':cari' => "%$cari%"
    ]);

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM t_siswa
        ORDER BY id DESC
    ");
}

$data_siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Kelola Siswa</title>

</head>

<body>

<h1>Kelola Siswa</h1>

<p>
    Selamat datang,
    <b><?= htmlspecialchars($name) ?></b>
    (<?= htmlspecialchars($role) ?>)
</p>

<hr>


<!-- =========================
     KEMBALI KE DASHBOARD
========================= -->

<p>
    <a href="dashboard.php">
        ← Kembali ke Dashboard
    </a>
</p>

<hr>


<!-- =========================
     FORM SISWA
========================= -->

<h2>
<?php if ($data_edit): ?>
    Edit Data Siswa
<?php else: ?>
    Tambah Data Siswa
<?php endif; ?>
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

    <label>NIS</label><br>

    <input
        type="text"
        name="nis"
        placeholder="Contoh: 2026001"
        value="<?= $data_edit ? htmlspecialchars($data_edit['nis']) : '' ?>"
        required
    >

</p>


<p>

    <label>Nama Siswa</label><br>

    <input
        type="text"
        name="nama"
        placeholder="Masukkan nama siswa"
        value="<?= $data_edit ? htmlspecialchars($data_edit['nama']) : '' ?>"
        required
    >

</p>


<p>

    <label>Jenis Kelamin</label><br>

    <select name="jenis_kelamin" required>

        <option value="">-- Pilih --</option>

        <option
            value="Laki-laki"
            <?= ($data_edit && $data_edit['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>
        >
            Laki-laki
        </option>

        <option
            value="Perempuan"
            <?= ($data_edit && $data_edit['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>
        >
            Perempuan
        </option>

    </select>

</p>


<p>

    <label>Kelas</label><br>

    <input
        type="text"
        name="kelas"
        placeholder="Contoh: X RPL 1"
        value="<?= $data_edit ? htmlspecialchars($data_edit['kelas']) : '' ?>"
        required
    >

</p>


<p>

    <label>Alamat</label><br>

    <textarea
        name="alamat"
        rows="3"
        cols="40"
        placeholder="Masukkan alamat"
    ><?= $data_edit ? htmlspecialchars($data_edit['alamat']) : '' ?></textarea>

</p>


<p>

    <label>No. HP</label><br>

    <input
        type="text"
        name="no_hp"
        placeholder="Contoh: 08123456789"
        value="<?= $data_edit ? htmlspecialchars($data_edit['no_hp']) : '' ?>"
    >

</p>


<?php if ($data_edit): ?>

    <button type="submit" name="edit">
        Update Siswa
    </button>

    <a href="menu1.php">
        Batal
    </a>

<?php else: ?>

    <button type="submit" name="tambah">
        Tambah Siswa
    </button>

<?php endif; ?>

</form>

<hr>


<!-- =========================
     PENCARIAN
========================= -->

<h2>Data Siswa</h2>

<form method="GET">

    <input
        type="text"
        name="cari"
        placeholder="Cari NIS, nama, atau kelas..."
        value="<?= htmlspecialchars($cari) ?>"
    >

    <button type="submit">
        Cari
    </button>

    <a href="menu1.php">
        Reset
    </a>

</form>

<br>


<!-- =========================
     TABEL SISWA
========================= -->

<table border="1" cellpadding="8" cellspacing="0">

    <tr>

        <th>No</th>
        <th>ID</th>
        <th>NIS</th>
        <th>Nama Siswa</th>
        <th>Jenis Kelamin</th>
        <th>Kelas</th>
        <th>Alamat</th>
        <th>No. HP</th>
        <th>Aksi</th>

    </tr>


<?php if (count($data_siswa) > 0): ?>

    <?php $no = 1; ?>

    <?php foreach ($data_siswa as $row): ?>

        <tr>

            <td>
                <?= $no++ ?>
            </td>

            <td>
                <?= htmlspecialchars($row['id']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['nis']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['nama']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['jenis_kelamin']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['kelas']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['alamat']) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['no_hp']) ?>
            </td>

            <td>

                <a href="menu1.php?edit=<?= $row['id'] ?>">
                    Edit
                </a>

                |

                <a
                    href="menu1.php?hapus=<?= $row['id'] ?>"
                    onclick="return confirm('Yakin ingin menghapus siswa ini?')"
                >
                    Hapus
                </a>

            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>

        <td colspan="9">
            Belum ada data siswa.
        </td>

    </tr>

<?php endif; ?>

</table>

<br>

<p>
    Jumlah siswa:
    <b><?= count($data_siswa) ?></b>
</p>

</body>

</html>