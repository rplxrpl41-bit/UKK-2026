
<?php
// =====================================================
// PROSES SIMPAN PELANGGARAN SISWA
// File: proses_simpan_pelanggaran.php
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/koneksi.php';

// =====================================================
// FUNGSI PESAN DAN REDIRECT
// =====================================================

function kembali($pesan, $jenis = 'error')
{
    $_SESSION['pesan'] = $pesan;
    $_SESSION['jenis_pesan'] = $jenis;

    header('Location: catat_pelanggaran.php');
    exit;
}

// =====================================================
// CEK LOGIN
// =====================================================

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// =====================================================
// CEK METODE REQUEST
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kembali('Akses tidak valid.');
}

// =====================================================
// AMBIL DATA DARI FORM
// =====================================================

$siswa_id = (int) ($_POST['siswa_id'] ?? 0);
$tahun_ajaran_id = (int) ($_POST['tahun_ajaran_id'] ?? 0);
$pelanggaran_id = (int) ($_POST['pelanggaran_id'] ?? 0);

$tanggal = trim($_POST['tanggal'] ?? '');
$status = trim($_POST['status'] ?? '');
$keterangan = trim($_POST['keterangan'] ?? '');

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$role = strtolower(trim($_SESSION['role'] ?? ''));

// =====================================================
// VALIDASI FORM
// =====================================================

if ($siswa_id <= 0) {
    kembali('Silakan pilih siswa terlebih dahulu.');
}

if ($tahun_ajaran_id <= 0) {
    kembali('Tahun ajaran belum dipilih.');
}

if ($pelanggaran_id <= 0) {
    kembali('Silakan pilih jenis pelanggaran.');
}

if ($tanggal === '') {
    kembali('Tanggal pelanggaran wajib diisi.');
}

if ($status === '') {
    kembali('Silakan pilih status pelanggaran.');
}

// =====================================================
// VALIDASI TANGGAL
// =====================================================

$tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal);

if (
    !$tanggal_obj ||
    $tanggal_obj->format('Y-m-d') !== $tanggal
) {
    kembali('Format tanggal tidak valid.');
}

// =====================================================
// VALIDASI STATUS
// =====================================================

$status_diizinkan = [
    'Ditindaklanjuti',
    'Proses',
    'Selesai'
];

if (!in_array($status, $status_diizinkan, true)) {
    kembali('Status pelanggaran tidak valid.');
}

// =====================================================
// CEK TAHUN AJARAN AKTIF
// =====================================================

$query_tahun = "
    SELECT id, nama
    FROM t_tahun_ajaran
    WHERE id = ?
      AND status_aktif = 1
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_tahun);

if (!$stmt) {
    kembali('Gagal memeriksa tahun ajaran.');
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $tahun_ajaran_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$tahun = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$tahun) {
    kembali('Tahun ajaran tidak aktif atau tidak ditemukan.');
}

// =====================================================
// CEK SISWA DAN PENEMPATAN KELAS AKTIF
// =====================================================

$query_siswa = "
    SELECT
        s.id AS siswa_id,
        s.nis,
        s.nama AS nama_siswa,
        ks.kelas_id,
        k.nama AS nama_kelas
    FROM t_siswa s
    INNER JOIN t_kelas_siswa ks
        ON ks.siswa_id = s.id
    INNER JOIN t_kelas k
        ON k.id = ks.kelas_id
    WHERE s.id = ?
      AND s.status_aktif = 1
      AND ks.tahun_ajaran_id = ?
      AND ks.status_aktif = 1
      AND k.status_aktif = 1
    ORDER BY ks.id DESC
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_siswa);

if (!$stmt) {
    kembali('Gagal memeriksa data penempatan siswa.');
}

mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $siswa_id,
    $tahun_ajaran_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_siswa = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_siswa) {
    kembali(
        'Siswa tidak ditemukan atau belum memiliki penempatan kelas aktif pada tahun ajaran ini.'
    );
}

$kelas_id = (int) $data_siswa['kelas_id'];

// =====================================================
// CEK JENIS PELANGGARAN
// =====================================================

$query_pelanggaran = "
    SELECT id, nama, poin
    FROM t_pelanggaran
    WHERE id = ?
      AND status_aktif = 1
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_pelanggaran);

if (!$stmt) {
    kembali('Gagal memeriksa jenis pelanggaran.');
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $pelanggaran_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_pelanggaran = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_pelanggaran) {
    kembali('Jenis pelanggaran tidak aktif atau tidak ditemukan.');
}

// =====================================================
// CEK AKUN GURU
// =====================================================

if ($role !== 'guru') {
    kembali('Akun ini tidak memiliki akses untuk mencatat pelanggaran.');
}

$query_guru = "
    SELECT id, nama
    FROM t_guru
    WHERE user_id = ?
      AND status_aktif = 1
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query_guru);

if (!$stmt) {
    kembali('Gagal memeriksa data guru.');
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data_guru = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data_guru) {
    kembali('Data guru tidak ditemukan atau belum aktif.');
}

$guru_id = (int) $data_guru['id'];

// =====================================================
// SIMPAN PELANGGARAN
// =====================================================

mysqli_begin_transaction($koneksi);

try {

    $query_insert = "
        INSERT INTO t_pelanggaran_siswa
        (
            tahun_ajaran_id,
            siswa_id,
            kelas_id,
            pelanggaran_id,
            guru_id,
            tanggal,
            status,
            keterangan
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = mysqli_prepare($koneksi, $query_insert);

    if (!$stmt) {
        throw new Exception(
            'Gagal menyiapkan query INSERT: ' .
            mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'iiiiisss',
        $tahun_ajaran_id,
        $siswa_id,
        $kelas_id,
        $pelanggaran_id,
        $guru_id,
        $tanggal,
        $status,
        $keterangan
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(
            'Gagal menyimpan data: ' .
            mysqli_stmt_error($stmt)
        );
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($koneksi);

    kembali(
        'Pelanggaran siswa berhasil dicatat.',
        'success'
    );

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    error_log(
        'ERROR SIMPAN PELANGGARAN: ' .
        $e->getMessage()
    );

    kembali(
        'Data gagal disimpan. Periksa struktur tabel t_pelanggaran_siswa dan log PHP.'
    );
}
