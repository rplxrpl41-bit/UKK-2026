
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// Cek ID siswa
if (!isset($_GET['id'])) {
    die("ID siswa tidak ditemukan!");
}

$id = (int) $_GET['id'];

// Ambil data siswa
$query = mysqli_query($koneksi, "SELECT * FROM t_siswa WHERE id='$id'");

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}

$siswa = mysqli_fetch_assoc($query);

if (!$siswa) {
    die("Data siswa tidak ditemukan!");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Siswa</title>


</head>

<body>

<div class="container">

    <h2>Edit Data Siswa</h2>

    <form action="proses_edit_siswa.php" method="POST">

        <input type="hidden" name="id"
            value="<?= htmlspecialchars($siswa['id']) ?>">

        <label>NIS</label>
        <input type="text" name="nis"
            value="<?= htmlspecialchars($siswa['nis'] ?? '') ?>" required>

        <label>NISN</label>
        <input type="text" name="nisn"
            value="<?= htmlspecialchars($siswa['nisn'] ?? '') ?>" required>

        <label>Nama Siswa</label>
        <input type="text" name="nama"
            value="<?= htmlspecialchars($siswa['nama'] ?? '') ?>" required>

        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" required>
            <option value="L"
                <?= $siswa['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>
                Laki-laki
            </option>

            <option value="P"
                <?= $siswa['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>
                Perempuan
            </option>
        </select>

        <label>Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir"
            value="<?= htmlspecialchars($siswa['tanggal_lahir'] ?? '') ?>"
            required>

        <label>Alamat</label>
        <textarea name="alamat" required><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>

        <label>Status Aktif</label>
        <select name="status_aktif" required>
            <option value="1"
                <?= $siswa['status_aktif'] == 1 ? 'selected' : '' ?>>
                Aktif
            </option>

            <option value="0"
                <?= $siswa['status_aktif'] == 0 ? 'selected' : '' ?>>
                Tidak Aktif
            </option>
        </select>

        <button type="submit" name="update">
            Simpan Perubahan
        
        </button>

        <a href="menu1.php" class="batal">Batal</a>

    </form>

</div>

</body>
</html>

