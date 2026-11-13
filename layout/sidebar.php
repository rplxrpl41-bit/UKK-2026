<?php
// layout/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);

// Halaman tambah/edit ikut menyalakan menu induknya
$menu_induk = [
    'tambah_siswa.php'               => 'kelola_siswa.php',
    'edit_siswa.php'                 => 'kelola_siswa.php',
    'tambah_guru.php'                => 'kelola_guru.php',
    'edit_guru.php'                  => 'kelola_guru.php',
    'tambah_kelas.php'               => 'kelola_kelas.php',
    'edit_kelas.php'                 => 'kelola_kelas.php',
    'tambah_tahun_ajaran.php'        => 'kelola_tahun_ajaran.php',
    'edit_tahun_ajaran.php'          => 'kelola_tahun_ajaran.php',
    'tambah_wali_kelas.php'          => 'kelola_wali_kelas.php',
    'edit_wali_kelas.php'            => 'kelola_wali_kelas.php',
    'tambah_pelanggaran_kategori.php'=> 'kelola_pelanggaran_kategori.php',
    'edit_pelanggaran_kategori.php'  => 'kelola_pelanggaran_kategori.php',
    'tambah_jenis_pelanggaran.php'   => 'kelola_jenis_pelanggaran.php',
    'edit_jenis_pelanggaran.php'     => 'kelola_jenis_pelanggaran.php',
    'edit_catat_pelanggaran.php'     => 'catat_pelanggaran.php',
    'cetak_laporan.php'              => 'laporan.php',
];
if (isset($menu_induk[$current_page])) {
    $current_page = $menu_induk[$current_page];
}

if ($_SESSION['role'] === "admin") {
    $judul_sidebar = 'Sistem Pelanggaran';
    $menu_items = [
        ['dashboard.php',                   '⌂ Dashboard'],
        ['kelola_siswa.php',                '♟ Data Siswa'],
        ['kelola_guru.php',                 '♙ Data Guru'],
        ['kelola_kelas.php',                '▦ Data Kelas'],
        ['kelola_tahun_ajaran.php',         '◷ Data Tahun Ajaran'],
        ['kelola_wali_kelas.php',           '♙ Data Wali Kelas'],
        ['penempatan_siswa.php',            '⇄ Penempatan Siswa'],
        ['kelola_pelanggaran_kategori.php', '☷ Kategori Pelanggaran'],
        ['kelola_jenis_pelanggaran.php',    '☰ Jenis Pelanggaran'],
        ['laporan.php',                     '▤ Laporan'],
        ['about_me.php',                    'ℹ About Me'],
    ];
} elseif (in_array($_SESSION['role'] ?? "", ["guru", "walikelas"], true)) {
    $judul_sidebar = ($_SESSION['role'] === 'walikelas') ? 'Panel Wali Kelas' : 'Panel Guru';
    $menu_items = [
        ['dashboard.php',         '⌂ Dashboard'],
        ['catat_pelanggaran.php', '✎ Catat Pelanggaran'],
        ['tindakan.php',          '⚑ Tindakan'],
        ['riwayat.php',           '☰ Riwayat'],
        ['rekap_poin.php',        '★ Rekap Poin'],
        ['laporan.php',           '▤ Laporan'],
    ];
} else {
    $menu_items = [];
    $judul_sidebar = 'Sistem Pelanggaran';
}
?>
<!-- SIDEBAR -->
<aside class="col-md-3 col-lg-2 sidebar">

    <h4 class="sidebar-title">
        <?= htmlspecialchars($judul_sidebar, ENT_QUOTES, 'UTF-8'); ?>
    </h4>

    <nav class="nav flex-column">

        <?php foreach ($menu_items as $item): ?>
            <a href="<?= htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8'); ?>"
               class="nav-link <?= ($current_page === $item[0]) ? 'active' : ''; ?>">
                <?= htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>

        <hr>

        <a href="logout.php"
           class="nav-link logout"
           onclick="return confirm('Yakin ingin logout?')">
            ⇥ Logout
        </a>

    </nav>
</aside>
