
<?php
// =====================================================
// CATAT PELANGGARAN SISWA
// File: catat_pelanggaran.php
// =====================================================

include 'includes/cek_session.php';
include 'config/koneksi.php';

// =====================================================
// IDENTITAS LOGIN
// =====================================================

$role_login = strtolower(trim($_SESSION['role'] ?? ''));

$id_user = (int) (
    $_SESSION['user_id'] ??
    $_SESSION['id'] ??
    0
);

// =====================================================
// FUNGSI ESCAPE
// =====================================================

function e($nilai)
{
    return htmlspecialchars(
        (string) ($nilai ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================================
// TAHUN AJARAN AKTIF
// =====================================================

$tahun = mysqli_query(
    $koneksi,
    "SELECT id, nama
     FROM t_tahun_ajaran
     WHERE status_aktif = 1
     ORDER BY id DESC
     LIMIT 1"
);

$tahun_aktif = $tahun
    ? mysqli_fetch_assoc($tahun)
    : null;

$tahun_aktif_id = (int) ($tahun_aktif['id'] ?? 0);

// =====================================================
// DAFTAR SISWA DENGAN PENEMPATAN AKTIF
// =====================================================

$siswa = false;

if ($tahun_aktif_id > 0) {

    $stmt_siswa = mysqli_prepare(
        $koneksi,
        "SELECT
            s.id,
            s.nis,
            s.nama,
            ks.kelas_id,
            k.nama AS nama_kelas,
            k.tingkat,
            k.jurusan

         FROM t_siswa s

         INNER JOIN t_kelas_siswa ks
            ON ks.siswa_id = s.id
            AND ks.tahun_ajaran_id = ?
            AND ks.status_aktif = 1

         INNER JOIN t_kelas k
            ON k.id = ks.kelas_id
            AND k.status_aktif = 1

         WHERE s.status_aktif = 1

         ORDER BY s.nama ASC"
    );

    if ($stmt_siswa) {

        mysqli_stmt_bind_param(
            $stmt_siswa,
            "i",
            $tahun_aktif_id
        );

        mysqli_stmt_execute($stmt_siswa);

        $siswa = mysqli_stmt_get_result($stmt_siswa);
    }
}

// =====================================================
// DAFTAR PELANGGARAN AKTIF
// =====================================================

$pelanggaran = mysqli_query(
    $koneksi,
    "SELECT
        id,
        nama,
        poin,
        pelanggaran_kategori_id

     FROM t_pelanggaran

     WHERE status_aktif = 1

     ORDER BY nama ASC"
);

// =====================================================
// DATA GURU LOGIN
// =====================================================

$data_guru = null;
$guru_id_login = 0;

if ($role_login === 'guru') {

    $stmt_guru = mysqli_prepare(
        $koneksi,
        "SELECT id, nama
         FROM t_guru
         WHERE user_id = ?
           AND status_aktif = 1
         LIMIT 1"
    );

    if ($stmt_guru) {

        mysqli_stmt_bind_param(
            $stmt_guru,
            "i",
            $id_user
        );

        mysqli_stmt_execute($stmt_guru);

        $result_guru = mysqli_stmt_get_result($stmt_guru);

        $data_guru = mysqli_fetch_assoc($result_guru);

        $guru_id_login = (int) ($data_guru['id'] ?? 0);

        mysqli_stmt_close($stmt_guru);
    }

} elseif ($role_login === 'admin') {

    $data_guru = true;
}

// =====================================================
// RIWAYAT PELANGGARAN
// GURU HANYA MELIHAT CATATANNYA SENDIRI
// =====================================================

$sql = "SELECT
            ps.*,
            ta.nama AS tahun_ajaran,
            s.nama AS nama_siswa,
            k.nama AS nama_kelas,
            p.nama AS nama_pelanggaran,
            g.nama AS nama_guru

        FROM t_pelanggaran_siswa ps

        LEFT JOIN t_tahun_ajaran ta
            ON ps.tahun_ajaran_id = ta.id

        LEFT JOIN t_siswa s
            ON ps.siswa_id = s.id

        LEFT JOIN t_kelas k
            ON ps.kelas_id = k.id

        LEFT JOIN t_pelanggaran p
            ON ps.pelanggaran_id = p.id

        LEFT JOIN t_guru g
            ON ps.guru_id = g.id";

$hasil = false;

if ($role_login === 'guru') {

    if ($guru_id_login > 0) {

        $sql .= " WHERE ps.guru_id = ?
                  ORDER BY ps.id DESC";

        $stmt_riwayat = mysqli_prepare(
            $koneksi,
            $sql
        );

        if ($stmt_riwayat) {

            mysqli_stmt_bind_param(
                $stmt_riwayat,
                "i",
                $guru_id_login
            );

            mysqli_stmt_execute($stmt_riwayat);

            $hasil = mysqli_stmt_get_result($stmt_riwayat);
        }
    }

} else {

    $sql .= " ORDER BY ps.id DESC";

    $hasil = mysqli_query($koneksi, $sql);
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Catat Pelanggaran Siswa</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css"
        rel="stylesheet"
    >

    <link href="asset/zulfan.css" rel="stylesheet">

</head>

<body>

<div class="container-fluid">

    <div class="row">

        <?php include 'layout/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 content">

            <h2 class="page-title">
                Catat Pelanggaran Siswa
            </h2>

            <p class="page-description">
                Catat pelanggaran yang dilakukan siswa.
            </p>

            <!-- PESAN SUKSES -->

            <?php if (isset($_SESSION['pesan_sukses'])) { ?>

                <div class="alert alert-success">
                    <?= e($_SESSION['pesan_sukses']); ?>
                </div>

                <?php unset($_SESSION['pesan_sukses']); ?>

            <?php } ?>

            <!-- PESAN ERROR -->

            <?php if (isset($_SESSION['pesan_error'])) { ?>

                <div class="alert alert-danger">
                    <?= e($_SESSION['pesan_error']); ?>
                </div>

                <?php unset($_SESSION['pesan_error']); ?>

            <?php } ?>

            <!-- TAHUN AJARAN -->

            <?php if (!$tahun_aktif) { ?>

                <div class="alert alert-warning">
                    Belum ada tahun ajaran aktif.
                </div>

            <?php } ?>

            <!-- GURU -->

            <?php if ($role_login === 'guru' && !$data_guru) { ?>

                <div class="alert alert-danger">
                    Akun guru belum terhubung dengan data guru aktif.
                </div>

            <?php } ?>

            <!-- FORM -->

            <div class="card mb-4">

                <div class="card-header">
                    Form Catat Pelanggaran
                </div>

                <div class="card-body">

                    <form
                        action="proses_simpan_pelanggaran.php"
                        method="POST"
                        id="formPelanggaran"
                    >

                        <div class="row">

                            <!-- SISWA -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Siswa
                                </label>

                                <select
                                    name="siswa_id"
                                    id="siswa"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Siswa --
                                    </option>

                                    <?php if ($siswa) { ?>

                                        <?php while ($s = mysqli_fetch_assoc($siswa)) { ?>

                                            <?php
                                            $kelas_id_option = (int) $s['kelas_id'];

                                            $kelas_tampil =
                                                $s['nama_kelas'] . ' - ' .
                                                $s['tingkat'] . ' - ' .
                                                $s['jurusan'];
                                            ?>

                                            <option
                                                value="<?= (int) $s['id']; ?>"
                                                data-kelas-id="<?= $kelas_id_option; ?>"
                                            >
                                                <?= e(
                                                    $s['nis'] . ' - ' .
                                                    $s['nama'] . ' (' .
                                                    $kelas_tampil . ')'
                                                ); ?>
                                            </option>

                                        <?php } ?>

                                    <?php } ?>

                                </select>

                                <small class="text-muted">
                                    Kelas ditampilkan langsung pada pilihan siswa.
                                </small>

                            </div>

                            <!-- TAHUN AJARAN -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Tahun Ajaran
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?= e($tahun_aktif['nama'] ?? 'Belum aktif'); ?>"
                                    readonly
                                >

                                <input
                                    type="hidden"
                                    name="tahun_ajaran_id"
                                    value="<?= $tahun_aktif_id; ?>"
                                >

                            </div>

                            <!-- PELANGGARAN -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Jenis Pelanggaran
                                </label>

                                <select
                                    name="pelanggaran_id"
                                    id="pelanggaran"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Cari Jenis Pelanggaran --
                                    </option>

                                    <?php if ($pelanggaran) { ?>

                                        <?php while ($p = mysqli_fetch_assoc($pelanggaran)) { ?>

                                            <option
                                                value="<?= (int) $p['id']; ?>"
                                                data-kategori="<?= (int) $p['pelanggaran_kategori_id']; ?>"
                                            >
                                                <?= e($p['nama']); ?>
                                            </option>

                                        <?php } ?>

                                    <?php } ?>

                                </select>

                                <input
                                    type="hidden"
                                    name="pelanggaran_kategori_id"
                                    id="kategori"
                                >

                                <small class="text-muted">
                                    Ketik nama pelanggaran untuk mencari.
                                </small>

                            </div>

                            <!-- TANGGAL -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Tanggal
                                </label>

                                <input
                                    type="date"
                                    name="tanggal"
                                    class="form-control"
                                    value="<?= date('Y-m-d'); ?>"
                                    required
                                >

                            </div>

                            <!-- STATUS -->

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Status
                                </label>

                                <select
                                    name="status"
                                    class="form-select"
                                    required
                                >

                                    <option value="Ditindaklanjuti">
                                        Ditindaklanjuti
                                    </option>

                                    <option value="Proses">
                                        Proses
                                    </option>

                                    <option value="Selesai">
                                        Selesai
                                    </option>

                                </select>

                            </div>

                            <!-- KETERANGAN -->

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Keterangan
                                </label>

                                <textarea
                                    name="keterangan"
                                    class="form-control"
                                    rows="3"
                                ></textarea>

                            </div>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= (
                                !$tahun_aktif ||
                                !$data_guru ||
                                ($role_login === 'guru' && $guru_id_login <= 0)
                            ) ? 'disabled' : ''; ?>
                        >
                            Simpan Pelanggaran
                        </button>

                        <a
                            href="dashboard.php"
                            class="btn btn-secondary"
                        >
                            Kembali
                        </a>

                    </form>

                </div>

            </div>

            <!-- RIWAYAT -->

            <div class="card">

                <div class="card-header">
                    Data Catatan Pelanggaran
                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-dark">

                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Pelanggaran</th>
                                    <th>Guru</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php $no = 1; ?>

                                <?php if ($hasil) { ?>

                                    <?php while ($row = mysqli_fetch_assoc($hasil)) { ?>

                                        <tr>

                                            <td><?= $no++; ?></td>

                                            <td>
                                                <?= e($row['nama_siswa'] ?? '-'); ?>
                                            </td>

                                            <td>
                                                <?= e($row['nama_kelas'] ?? '-'); ?>
                                            </td>

                                            <td>
                                                <?= e($row['tahun_ajaran'] ?? '-'); ?>
                                            </td>

                                            <td>
                                                <?= e($row['nama_pelanggaran'] ?? '-'); ?>
                                            </td>

                                            <td>
                                                <?= e($row['nama_guru'] ?? '-'); ?>
                                            </td>

                                            <td>
                                                <?= e($row['tanggal'] ?? '-'); ?>
                                            </td>

                                            <td>

                                                <?php if (($row['status'] ?? '') === 'Selesai') { ?>

                                                    <span class="badge bg-success">
                                                        Selesai
                                                    </span>

                                                <?php } elseif (($row['status'] ?? '') === 'Proses') { ?>

                                                    <span class="badge bg-warning text-dark">
                                                        Proses
                                                    </span>

                                                <?php } else { ?>

                                                    <span class="badge bg-primary">
                                                        Ditindaklanjuti
                                                    </span>

                                                <?php } ?>

                                            </td>

                                            <td>

                                                <?php if (($row['status'] ?? '') !== 'Selesai') { ?>

                                                    <div class="d-flex gap-2">

                                                        <a
                                                            href="edit_catat_pelanggaran.php?id=<?= (int) $row['id']; ?>"
                                                            class="btn btn-warning btn-sm"
                                                        >
                                                            Edit
                                                        </a>

                                                        <a
                                                            href="hapus_catat_pelanggaran.php?id=<?= (int) $row['id']; ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Yakin ingin menghapus catatan pelanggaran ini?');"
                                                        >
                                                            Hapus
                                                        </a>

                                                    </div>

                                                <?php } else { ?>

                                                    <span class="text-muted">
                                                        Tidak tersedia
                                                    </span>

                                                <?php } ?>

                                            </td>

                                        </tr>

                                    <?php } ?>

                                <?php } ?>

                                <?php if (!$hasil || mysqli_num_rows($hasil) === 0) { ?>

                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            Belum ada catatan pelanggaran.
                                        </td>
                                    </tr>

                                <?php } ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>

<script>

// =====================================================
// TOM SELECT SISWA
// =====================================================

const pilihanSiswa = new TomSelect('#siswa', {
    create: false,
    placeholder: 'Cari nama atau NIS siswa...'
});

// =====================================================
// TOM SELECT PELANGGARAN
// =====================================================

const pilihanPelanggaran = new TomSelect('#pelanggaran', {

    create: false,

    placeholder: 'Cari jenis pelanggaran...',

    onChange: function(value) {

        const opsi = this.options[value];

        document.getElementById('kategori').value =
            opsi && opsi['data-kategori']
                ? opsi['data-kategori']
                : '';

    }

});

// =====================================================
// VALIDASI FORM
// =====================================================

document.getElementById('formPelanggaran')
.addEventListener('submit', function(event) {

    const siswaId = pilihanSiswa.getValue();

    const pelanggaranId = pilihanPelanggaran.getValue();

    if (!siswaId) {
        event.preventDefault();
        alert('Silakan pilih siswa terlebih dahulu!');
        return;
    }

    if (!pelanggaranId) {
        event.preventDefault();
        alert('Silakan pilih jenis pelanggaran!');
        return;
    }

});

</script>

</body>
</html>
