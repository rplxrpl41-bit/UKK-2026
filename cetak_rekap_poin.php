<?php
// =====================================================
// CETAK REKAP POIN PELANGGARAN SISWA (format dokumen sekolah)
// =====================================================

include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/helper_guru.php';
include 'includes/dokumen_cetak.php';

$adalah_admin = (($_SESSION['role'] ?? '') === 'admin');

// ---------- FILTER ----------
$kelas_id = (int) ($_GET['kelas_id'] ?? 0);
$semua    = (($_GET['semua'] ?? '') === '1');   // tampilkan siswa tanpa pelanggaran

// ---------- DATA KELAS UNTUK PILIHAN ----------
$q_kelas = mysqli_query(
    $koneksi,
    "SELECT id, nama, tingkat, jurusan FROM t_kelas
     WHERE status_aktif = 1 ORDER BY tingkat, jurusan, nama"
);

$nama_kelas_dipilih = 'Semua Kelas';

// ---------- QUERY REKAP ----------
// Guru / wali kelas hanya menghitung catatan miliknya sendiri.
list($sql_batas, $tipe_batas, $param_batas) = batas_data_guru($koneksi, 'ps');

$tipe  = '';
$param = [];

$on_batas = '';
if ($sql_batas !== '') {
    $on_batas = ' AND ' . $sql_batas;
    $tipe .= $tipe_batas;
    foreach ($param_batas as $v) {
        $param[] = $v;
    }
}

$where = ['s.status_aktif = 1'];

if ($kelas_id > 0) {
    $where[] = 'k.id = ?';
    $tipe .= 'i';
    $param[] = $kelas_id;
}

$having = $semua ? '' : 'HAVING jumlah_pelanggaran > 0';

$sql = "
    SELECT
        s.id,
        s.nis,
        s.nama,
        k.tingkat,
        k.jurusan,
        COUNT(ps.id) AS jumlah_pelanggaran,
        COALESCE(SUM(ps.poin), 0) AS total_poin
    FROM t_siswa s
    LEFT JOIN t_kelas_siswa ks
        ON ks.id = (
            SELECT MAX(x.id)
            FROM t_kelas_siswa x
            WHERE x.siswa_id = s.id AND x.status_aktif = 1
        )
    LEFT JOIN t_kelas k ON k.id = ks.kelas_id
    LEFT JOIN t_pelanggaran_siswa ps
        ON ps.siswa_id = s.id $on_batas
    WHERE " . implode(' AND ', $where) . "
    GROUP BY s.id, s.nis, s.nama, k.tingkat, k.jurusan
    $having
    ORDER BY total_poin DESC, s.nama ASC
";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die('Query gagal: ' . dk_e(mysqli_error($koneksi)));
}

if ($param) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$param);
}

mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$baris = [];
$total_pelanggaran = 0;
$total_poin = 0;

while ($r = mysqli_fetch_assoc($hasil)) {
    $baris[] = $r;
    $total_pelanggaran += (int) $r['jumlah_pelanggaran'];
    $total_poin += (int) $r['total_poin'];
}

mysqli_stmt_close($stmt);

// ---------- PILIHAN KELAS & NAMA KELAS ----------
$daftar_kelas = [];
while ($q_kelas && ($k = mysqli_fetch_assoc($q_kelas))) {
    $daftar_kelas[] = $k;
    if ((int) $k['id'] === $kelas_id) {
        $nama_kelas_dipilih = $k['tingkat'] . ' ' . $k['jurusan'] . ' (' . $k['nama'] . ')';
    }
}

// ---------- PEMBUAT DOKUMEN ----------
$guru = guru_login($koneksi);
$nama_pencetak = $adalah_admin
    ? ($_SESSION['name'] ?? 'Administrator')
    : ($guru['nama'] ?? ($_SESSION['name'] ?? 'Guru'));
$jabatan = $adalah_admin ? 'Administrator' : 'Guru / Wali Kelas';

$tahun_ajaran = dk_tahun_ajaran($koneksi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Poin Pelanggaran Siswa</title>
    <?php dk_css('portrait'); ?>
</head>
<body>

<div class="toolbar">
    <form method="GET" action="cetak_rekap_poin.php">
        <label>Kelas:</label>
        <select name="kelas_id" onchange="this.form.submit()">
            <option value="0">Semua kelas</option>
            <?php foreach ($daftar_kelas as $k): ?>
                <option value="<?= (int) $k['id'] ?>" <?= $kelas_id === (int) $k['id'] ? 'selected' : '' ?>>
                    <?= dk_e($k['tingkat'] . ' ' . $k['jurusan'] . ' (' . $k['nama'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            <input type="checkbox" name="semua" value="1"
                   <?= $semua ? 'checked' : '' ?> onchange="this.form.submit()">
            Sertakan siswa tanpa pelanggaran
        </label>
    </form>

    <span class="spasi"></span>
    <a href="rekap_point.php" class="abu">&larr; Kembali</a>
    <button type="button" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</div>

<div class="kertas">

    <?php dk_kop(); ?>

    <?php dk_judul('Rekapitulasi Poin Pelanggaran Siswa', 'Tahun Ajaran ' . $tahun_ajaran); ?>

    <table class="identitas">
        <tr><td>Kelas</td><td>:</td><td><?= dk_e($nama_kelas_dipilih) ?></td></tr>
        <tr><td>Tahun Ajaran</td><td>:</td><td><?= dk_e($tahun_ajaran) ?></td></tr>
        <tr><td>Tanggal Cetak</td><td>:</td><td><?= dk_e(dk_tanggal(date('Y-m-d'))) ?></td></tr>
        <?php if (!$adalah_admin): ?>
            <tr><td>Data Dicatat Oleh</td><td>:</td><td><?= dk_e($nama_pencetak) ?></td></tr>
        <?php endif; ?>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:9mm">No</th>
                <th style="width:24mm">NIS</th>
                <th>Nama Siswa</th>
                <th style="width:22mm">Kelas</th>
                <th style="width:24mm">Jumlah<br>Pelanggaran</th>
                <th style="width:20mm">Total<br>Poin</th>
            </tr>
        </thead>

        <tbody>
        <?php if ($baris): ?>
            <?php $no = 1; foreach ($baris as $r): ?>
                <tr>
                    <td class="tengah"><?= $no++ ?></td>
                    <td class="tengah"><?= dk_e($r['nis']) ?></td>
                    <td><?= dk_e($r['nama']) ?></td>
                    <td class="tengah"><?= dk_e(trim(($r['tingkat'] ?? '') . ' ' . ($r['jurusan'] ?? '')) ?: '-') ?></td>
                    <td class="tengah"><?= (int) $r['jumlah_pelanggaran'] ?></td>
                    <td class="tengah"><strong><?= (int) $r['total_poin'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" class="kosong">Tidak ada data pelanggaran.</td></tr>
        <?php endif; ?>
        </tbody>

        <?php if ($baris): ?>
        <tfoot>
            <tr>
                <td colspan="4" class="kanan">JUMLAH (<?= count($baris) ?> siswa)</td>
                <td class="tengah"><?= $total_pelanggaran ?></td>
                <td class="tengah"><?= $total_poin ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="catatan">
        Keterangan: poin dihitung dari seluruh catatan pelanggaran siswa pada sistem
        <?= $adalah_admin ? '' : '(hanya catatan yang dicatat/ditindak oleh pembuat dokumen)' ?>.
    </div>

    <?php dk_tanda_tangan($jabatan, $nama_pencetak, $adalah_admin ? '' : 'Guru / Wali Kelas'); ?>

</div>

</body>
</html>
