```php
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

if (isset($_POST['update'])) {

    $id = $_POST['id'];
    $nis = mysqli_real_escape_string($koneksi, $_POST['nis']);
    $nisn = mysqli_real_escape_string($koneksi, $_POST['nisn']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jenis_kelamin = mysqli_real_escape_string($koneksi, $_POST['jenis_kelamin']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $status_aktif = $_POST['status_aktif'];

    $query = "UPDATE t_siswa SET
        nis='$nis',
        nisn='$nisn',
        nama='$nama',
        jenis_kelamin='$jenis_kelamin',
        tanggal_lahir='$tanggal_lahir',
        alamat='$alamat',
        status_aktif='$status_aktif',
        updated_at=NOW()
        WHERE id='$id'";

    if (mysqli_query($koneksi, $query)) {
        header("Location: menu1.php");
        exit;
    } else {
        die("Gagal mengupdate data: " . mysqli_error($koneksi));
    }

} else {
    header("Location: menu1.php");
    exit;
}
?>
```
