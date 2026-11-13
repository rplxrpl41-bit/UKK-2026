
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/helper_guru.php';

// =====================================
// FUNGSI
// =====================================

function e($nilai)
{
    return htmlspecialchars(
        (string)($nilai ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================
// VALIDASI ROLE
// =====================================

$role = strtolower(trim($_SESSION['role'] ?? ''));

if (!in_array($role, ['admin', 'guru'], true)) {
    http_response_code(403);
    exit('Akses ditolak!');
}

// =====================================
// CSRF TOKEN
// =====================================

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// =====================================
// IDENTITAS GURU
// =====================================

$guru_id = null;
$nama_guru = $_SESSION['name'] ?? 'Guru';

$guru = guru_login($koneksi);

if ($guru) {
    $guru_id = (int)($guru['id'] ?? 0);
    $nama_guru = $guru['nama'] ?? $nama_guru;
}

// =====================================
// PROSES SIMPAN CATATAN
// =====================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kembali = function ($tipe, $pesan) {
        $_SESSION['flash'] = [
            'tipe' => $tipe,
            'pesan' => $pesan
        ];

        header('Location: catatan_pelanggaran.php');
        exit;
    };

    // Validasi CSRF
    $csrf_post = $_POST['csrf'] ?? '';

    if (
        empty($_SESSION['csrf']) ||
        !hash_equals($_SESSION['csrf'], $csrf_post)
    ) {
        $kembali(
            'danger',
            'Token keamanan tidak valid. Silakan coba kembali.'
        );
    }

    // Validasi guru
    if (!$guru_id || $guru_id <= 0) {
        $kembali(
            'danger',
            'Data guru tidak ditemukan. Silakan hubungi administrator.'
        );
    }

    // Ambil data form
    $siswa_id = (int)($_POST['siswa_id'] ?? 0);
    $pelanggaran_id = (int)($_POST['pelanggaran_id'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tindakan = trim($_POST['tindakan'] ?? '');

    // =================================
    // VALIDASI TANGGAL
    // =================================

    $tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggal);

    $tanggalValid = (
        $tanggalObj instanceof DateTime &&
        $tanggalObj->format('Y-m-d') === $tanggal
    );

    if (!$tanggalValid) {
        $kembali('danger', 'Tanggal pelanggaran tidak valid.');
    }

    if ($tanggal > date('Y-m-d')) {
        $kembali(
            'danger',
            'Tanggal pelanggaran tidak boleh melebihi hari ini.'
        );
    }

    // =================================
    // VALIDASI INPUT
    // =================================

    if ($siswa_id <= 0) {
        $kembali('danger', 'Silakan pilih siswa terlebih dahulu.');
    }

    if ($pelanggaran_id <= 0) {
        $kembali('danger', 'Silakan pilih jenis pelanggaran.');
    }

    // =================================
    // AMBIL DATA SISWA
    // =================================

    $sqlSiswa = "
        SELECT
            s.nama,
            ks.kelas_id,
            ks.tahun_ajaran_id,
            k.nama AS nama_kelas
        FROM t_siswa s
        LEFT JOIN t_kelas_siswa ks ON ks.id = (
            SELECT MAX(x.id)
            FROM t_kelas_siswa x
            WHERE x.siswa_id = s.id
              AND x.status_aktif = 1
        )
        LEFT JOIN t_kelas k ON k.id = ks.kelas_id
        WHERE s.id = ?
          AND s.status_aktif = 1
        LIMIT 1
    ";

    $stmtSiswa = mysqli_prepare($koneksi, $sqlSiswa);

    if (!$stmtSiswa) {
        error_log(mysqli_error($koneksi));
        $kembali('danger', 'Gagal memeriksa data siswa.');
    }

    mysqli_stmt_bind_param($stmtSiswa, 'i', $siswa_id);

    if (!mysqli_stmt_execute($stmtSiswa)) {
        error_log(mysqli_stmt_error($stmtSiswa));
        mysqli_stmt_close($stmtSiswa);
        $kembali('danger', 'Gagal mengambil data siswa.');
    }

    $hasilSiswa = mysqli_stmt_get_result($stmtSiswa);
    $dataSiswa = mysqli_fetch_assoc($hasilSiswa);

    mysqli_stmt_close($stmtSiswa);

    if (!$dataSiswa) {
        $kembali(
            'danger',
            'Siswa tidak ditemukan atau sudah tidak aktif.'
        );
    }

    // =================================
    // AMBIL DATA PELANGGARAN
    // =================================

    $sqlPelanggaran = "
        SELECT
            id,
            nama,
            poin,
            pelanggaran_kategori_id
        FROM t_pelanggaran
        WHERE id = ?
          AND status_aktif = 1
        LIMIT 1
    ";

    $stmtPelanggaran = mysqli_prepare($koneksi, $sqlPelanggaran);

    if (!$stmtPelanggaran) {
        error_log(mysqli_error($koneksi));
        $kembali('danger', 'Gagal memeriksa jenis pelanggaran.');
    }

    mysqli_stmt_bind_param(
        $stmtPelanggaran,
        'i',
        $pelanggaran_id
    );

    if (!mysqli_stmt_execute($stmtPelanggaran)) {
        error_log(mysqli_stmt_error($stmtPelanggaran));
        mysqli_stmt_close($stmtPelanggaran);
        $kembali('danger', 'Gagal mengambil jenis pelanggaran.');
    }

    $hasilPelanggaran = mysqli_stmt_get_result($stmtPelanggaran);
    $dataPelanggaran = mysqli_fetch_assoc($hasilPelanggaran);

    mysqli_stmt_close($stmtPelanggaran);

    if (!$dataPelanggaran) {
        $kembali(
            'danger',
            'Jenis pelanggaran tidak ditemukan atau sudah tidak aktif.'
        );
    }

    // =================================
    // SIAPKAN DATA UNTUK DISIMPAN
    // =================================

    $tahun_ajaran_id = $dataSiswa['tahun_ajaran_id'] !== null
        ? (int)$dataSiswa['tahun_ajaran_id']
        : null;

    $kelas_id = $dataSiswa['kelas_id'] !== null
        ? (int)$dataSiswa['kelas_id']
        : null;

    $pelanggaran_kategori_id =
        $dataPelanggaran['pelanggaran_kategori_id'] !== null
            ? (int)$dataPelanggaran['pelanggaran_kategori_id']
            : null;

    $nama_siswa = $dataSiswa['nama'];
    $nama_kelas = $dataSiswa['nama_kelas'] ?? '';
    $nama_pelanggaran = $dataPelanggaran['nama'];
    $poin = (int)$dataPelanggaran['poin'];

    $status = 'tercatat';

    // =================================
    // SIMPAN KE DATABASE
    // =================================

    $sqlInsert = "
        INSERT INTO t_pelanggaran_siswa (
            tahun_ajaran_id,
            siswa_id,
            nama_siswa,
            kelas_id,
            nama_kelas,
            pelanggaran_id,
            nama_pelanggaran,
            pelanggaran_kategori_id,
            guru_id,
            nama_guru,
            tanggal,
            keterangan,
            poin,
            tindakan,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmtInsert = mysqli_prepare($koneksi, $sqlInsert);

    if (!$stmtInsert) {
        error_log(mysqli_error($koneksi));
        $kembali('danger', 'Gagal menyiapkan penyimpanan catatan.');
    }

    mysqli_stmt_bind_param(
        $stmtInsert,
        'iisisisiisssiss',
        $tahun_ajaran_id,
        $siswa_id,
        $nama_siswa,
        $kelas_id,
        $nama_kelas,
        $pelanggaran_id,
        $nama_pelanggaran,
        $pelanggaran_kategori_id,
        $guru_id,
        $nama_guru,
        $tanggal,
        $keterangan,
        $poin,
        $tindakan,
        $status
    );

    if (mysqli_stmt_execute($stmtInsert)) {
        mysqli_stmt_close($stmtInsert);

        $kembali(
            'success',
            'Catatan pelanggaran berhasil disimpan.'
        );
    }

    error_log(mysqli_stmt_error($stmtInsert));
    mysqli_stmt_close($stmtInsert);

    $kembali(
        'danger',
        'Catatan pelanggaran gagal disimpan. Silakan coba kembali.'
    );
}

// =====================================
// AMBIL PESAN NOTIFIKASI
// =====================================

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// =====================================
// DATA SISWA AKTIF
// =====================================

$sqlSiswa = "
    SELECT
        s.id,
        s.nis,
        s.nama,
        k.nama AS nama_kelas
    FROM t_siswa s
    LEFT JOIN t_kelas_siswa ks ON ks.id = (
        SELECT MAX(x.id)
        FROM t_kelas_siswa x
        WHERE x.siswa_id = s.id
          AND x.status_aktif = 1
    )
    LEFT JOIN t_kelas k ON k.id = ks.kelas_id
    WHERE s.status_aktif = 1
    ORDER BY s.nama ASC
";

$querySiswa = mysqli_query($koneksi, $sqlSiswa);

if (!$querySiswa) {
    error_log(mysqli_error($koneksi));
    die('Gagal mengambil daftar siswa.');
}

// =====================================
// DATA JENIS PELANGGARAN AKTIF
// =====================================

$sqlJenis = "
    SELECT
        p.id,
        p.kode,
        p.nama,
        p.poin,
        kt.nama AS kategori
    FROM t_pelanggaran p
    LEFT JOIN t_pelanggaran_kategori kt
        ON kt.id = p.pelanggaran_kategori_id
    WHERE p.status_aktif = 1
    ORDER BY kt.nama ASC, p.nama ASC
";

$queryJenis = mysqli_query($koneksi, $sqlJenis);

if (!$queryJenis) {
    error_log(mysqli_error($koneksi));
    die('Gagal mengambil daftar jenis pelanggaran.');
}

// =====================================
// 10 CATATAN TERBARU YANG BELUM SELESAI
// =====================================

$catatanTerbaru = [];

$sqlTerbaru = "
    SELECT
        ps.id,
        ps.tanggal,
        ps.nama_siswa,
        ps.nama_kelas,
        ps.nama_pelanggaran,
        ps.poin,
        ps.nama_guru,
        ps.status
    FROM t_pelanggaran_siswa ps
    WHERE ps.status <> 'selesai'
";

$tipe_batas = '';
$param_batas = [];

if ($role === 'guru') {

    if (!$guru_id || $guru_id <= 0) {
        die('Data guru yang sedang login tidak ditemukan.');
    }

    $sqlTerbaru .= "
        AND (
            ps.guru_id = ?
            OR ps.ditindak_oleh = ?
        )
    ";

    $tipe_batas = 'ii';
    $param_batas = [$guru_id, $guru_id];
}

$sqlTerbaru .= "
    ORDER BY ps.id DESC
    LIMIT 10
";

$stmtTerbaru = mysqli_prepare($koneksi, $sqlTerbaru);

if (!$stmtTerbaru) {
    error_log(
        'Query catatan terbaru gagal: ' .
        mysqli_error($koneksi) .
        ' | SQL: ' . $sqlTerbaru
    );

    die('Gagal menyiapkan data catatan terbaru. Periksa error log PHP.');
}

if ($tipe_batas !== '') {
    mysqli_stmt_bind_param(
        $stmtTerbaru,
        $tipe_batas,
        $param_batas[0],
        $param_batas[1]
    );
}

if (!mysqli_stmt_execute($stmtTerbaru)) {
    error_log(mysqli_stmt_error($stmtTerbaru));
    mysqli_stmt_close($stmtTerbaru);
    die('Gagal mengambil data catatan terbaru.');
}

$resultTerbaru = mysqli_stmt_get_result($stmtTerbaru);

if ($resultTerbaru) {
    while ($row = mysqli_fetch_assoc($resultTerbaru)) {
        $catatanTerbaru[] = $row;
    }
}

mysqli_stmt_close($stmtTerbaru);

?>
