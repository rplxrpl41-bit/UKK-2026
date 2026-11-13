<?php
// =====================================================
// CETAK LAPORAN PELANGGARAN SISWA (format dokumen sekolah)
// =====================================================

include 'includes/cek_session.php';
include 'config/koneksi.php';
include 'includes/helper_guru.php';
include 'includes/dokumen_cetak.php';

$adalah_admin = (($_SESSION['role'] ?? '') === 'admin');

// ---------- FILTER ----------
$kelas_id = (int) ($_GET['kelas_id'] ?? 0);
$dari     = trim($_GET['dari'] ?? '');
$sampai   = trim($_GET['sampai'] ?? '');

$tgl_valid = function ($t) {
    $d = DateTime::createFromFormat('Y-m-d', $t);
    return $d && $d->format('Y-m-d') === $t;
};

if ($dari !== '' && !$tgl_valid($dari)) {
    $dari = '';
}

if ($sampai !== '' && !$tgl_valid($sampai)) {
    $sampai = '';
}

// ---------- QUERY ----------
// Guru / wali kelas hanya mencetak catatan miliknya sendiri.
list($sql_batas, $tipe_batas, $param_batas) = batas_data_guru($koneksi, 'ps');

$where = [];
$tipe  = '';
$param = [];

if ($sql_batas !== '') {
    $where[] = $sql_batas;
    $tipe .= $tipe_batas;
    foreach ($param_batas as $v) {
        $param[] = $v;
    }
}

if ($kelas_id > 0) {
    $where[] = 'ps.kelas_id = ?';
    $tipe .= 'i';
    $param[] = $kelas_id;
}

if ($dari !== '') {
    $where[] = 'ps.tanggal >= ?';
    $tipe .= 's';
    $param[] = $dari;
}

if ($sampai !== '') {
    $where[] = 'ps.tanggal <= ?';
    $tipe .= 's';
    $param[] = $sampai;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        ps.id,
        ps.tanggal,
        ps.nama_siswa,
        s.nis,
        k.tingkat,
        k.jurusan,
        ps.nama_kelas,
        ps.nama_pelanggaran,
        ps.poin,
        ps.tindakan,
        ps.status,
        ps.nama_guru
    FROM t_pelanggaran_siswa ps
    LEFT JOIN t_siswa s ON s.id = ps.siswa_id
    LEFT JOIN t_kelas k ON k.id = ps.kelas_id
    $sql_where
    ORDER BY ps.tanggal ASC, ps.id ASC
";

$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    die('Gagal mengambil data laporan: ' . dk_e(mysqli_error($koneksi)));
}

if ($param) {
    mysqli_stmt_bind_param($stmt, $tipe, ...$param);
}

mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$baris = [];
$total_poin = 0;
$siswa_unik = [];

while ($r = mysqli_fetch_assoc($hasil)) {
    $baris[] = $r;
    $total_poin += (int) $r['poin'];
    $siswa_unik[$r['nama_siswa']] = true;
}

mysqli_stmt_close($stmt);

// ---------- PILIHAN KELAS ----------
$q_kelas = mysqli_query(
    $koneksi,
    "SELECT id, nama, tingkat, jurusan FROM t_kelas
     WHERE status_aktif = 1 ORDER BY tingkat, jurusan, nama"
);

$daftar_kelas = [];
$nama_kelas_dipilih = 'Semua Kelas';

while ($q_kelas && ($k = mysqli_fetch_assoc($q_kelas))) {
    $daftar_kelas[] = $k;
    if ((int) $k['id'] === $kelas_id) {
        $nama_kelas_dipilih = $k['tingkat'] . ' ' . $k['jurusan'] . ' (' . $k['nama'] . ')';
    }
}

// ---------- PERIODE ----------
if ($dari !== '' && $sampai !== '') {
    $periode = dk_tanggal($dari) . ' s.d. ' . dk_tanggal($sampai);
} elseif ($dari !== '') {
    $periode = 'Mulai ' . dk_tanggal($dari);
} elseif ($sampai !== '') {
    $periode = 'Sampai ' . dk_tanggal($sampai);
} elseif ($baris) {
    $periode = dk_tanggal($baris[0]['tanggal']) . ' s.d. ' . dk_tanggal($baris[count($baris) - 1]['tanggal']);
} else {
    $periode = '-';
}

// ---------- PEMBUAT DOKUMEN ----------
$guru = guru_login($koneksi);
$nama_pencetak = $adalah_admin
    ? ($_SESSION['name'] ?? 'Administrator')
    : ($guru['nama'] ?? ($_SESSION['name'] ?? 'Guru'));
$jabatan = $adalah_admin ? 'Administrator' : 'Wali Kelas';

$tahun_ajaran = dk_tahun_ajaran($koneksi);

$label_status = [
    'tercatat' => 'Tercatat',
    'ditindak' => 'Ditindak',
    'selesai'  => 'Selesai',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pelanggaran Siswa</title>
    <?php dk_css('landscape'); ?>
</head>
<body>

<div class="toolbar">
    <form method="GET" action="cetak_laporan.php">
        <label>Kelas:</label>
        <select name="kelas_id">
            <option value="0">Semua kelas</option>
            <?php foreach ($daftar_kelas as $k): ?>
                <option value="<?= (int) $k['id'] ?>" <?= $kelas_id === (int) $k['id'] ? 'selected' : '' ?>>
                    <?= dk_e($k['tingkat'] . ' ' . $k['jurusan'] . ' (' . $k['nama'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Dari:</label>
        <input type="date" name="dari" value="<?= dk_e($dari) ?>">

        <label>Sampai:</label>
        <input type="date" name="sampai" value="<?= dk_e($sampai) ?>">

        <button type="submit">Terapkan</button>
    </form>

    <span class="spasi"></span>
    <a href="laporan.php" class="abu">&larr; Kembali</a>
    <button type="button" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
</div>

<div class="kertas">

    <?php dk_kop(); ?>

    <?php dk_judul('Laporan Pelanggaran Siswa', 'Tahun Ajaran ' . $tahun_ajaran); ?>

    <table class="identitas">
        <tr><td>Kelas</td><td>:</td><td><?= dk_e($nama_kelas_dipilih) ?></td></tr>
        <tr><td>Periode</td><td>:</td><td><?= dk_e($periode) ?></td></tr>
        <tr><td>Tanggal Cetak</td><td>:</td><td><?= dk_e(dk_tanggal(date('Y-m-d'))) ?></td></tr>
        <?php if (!$adalah_admin): ?>
            <tr><td>Dicatat Oleh</td><td>:</td><td><?= dk_e($nama_pencetak) ?></td></tr>
        <?php endif; ?>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:9mm">No</th>
                <th style="width:24mm">Tanggal</th>
                <th style="width:22mm">NIS</th>
                <th>Nama Siswa</th>
                <th style="width:20mm">Kelas</th>
                <th>Jenis Pelanggaran</th>
                <th style="width:13mm">Poin</th>
                <th>Tindakan</th>
                <th style="width:20mm">Status</th>
                <?php if ($adalah_admin): ?>
                    <th style="width:38mm">Pencatat</th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>
        <?php if ($baris): ?>
            <?php $no = 1; foreach ($baris as $r): ?>
                <tr>
                    <td class="tengah"><?= $no++ ?></td>
                    <td class="tengah"><?= dk_e(date('d-m-Y', strtotime($r['tanggal']))) ?></td>
                    <td class="tengah"><?= dk_e($r['nis'] ?? '-') ?></td>
                    <td><?= dk_e($r['nama_siswa']) ?></td>
                    <td class="tengah"><?= dk_e(trim(($r['tingkat'] ?? '') . ' ' . ($r['jurusan'] ?? '')) ?: $r['nama_kelas']) ?></td>
                    <td><?= dk_e($r['nama_pelanggaran']) ?></td>
                    <td class="tengah"><?= (int) $r['poin'] ?></td>
                    <td><?= $r['tindakan'] !== '' && $r['tindakan'] !== null ? dk_e($r['tindakan']) : '-' ?></td>
                    <td class="tengah"><?= dk_e($label_status[$r['status']] ?? ($r['status'] ?: '-')) ?></td>
                    <?php if ($adalah_admin): ?>
                        <td><?= dk_e($r['nama_guru']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= $adalah_admin ? 10 : 9 ?>" class="kosong">
                    Tidak ada data pelanggaran pada periode ini.
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <?php if ($baris): ?>
        <tfoot>
            <tr>
                <td colspan="6" class="kanan">JUMLAH POIN</td>
                <td class="tengah"><?= $total_poin ?></td>
                <td colspan="<?= $adalah_admin ? 3 : 2 ?>"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="catatan">
        Rekapitulasi: <?= count($baris) ?> catatan pelanggaran dari
        <?= count($siswa_unik) ?> siswa, dengan total <?= $total_poin ?> poin.
    </div>

    <?php dk_tanda_tangan($jabatan, $nama_pencetak, $adalah_admin ? '' : 'Wali Kelas'); ?>

</div>

</body>
</html>
