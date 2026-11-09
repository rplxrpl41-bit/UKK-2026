<?php
$current_page = basename($_SERVER['PHP_SELF']);

$menu_aktif = $menu_aktif ?? $current_page;
$role_sidebar = $_SESSION['role'] ?? 'admin';

if ($role_sidebar === 'guru') {
    // Menu khusus GURU
    $judul_sidebar = 'Panel Guru';
    $menu_items = [
        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],
        ['catatan_pelanggaran.php', '✎ Catatan Pelanggaran', 'Catatan Pelanggaran'],
        ['tindakan.php', '⚑ Tindakan', 'Tindakan'],
        ['riwayat_pelanggaran.php', '☰ Riwayat Pelanggaran', 'Riwayat Pelanggaran'],
        ['laporan.php', '▤ Laporan', 'Laporan'],
        ['rekap_point.php', '★ Rekap Poin', 'Rekap Poin'],
        ['tentang_saya.php', 'ℹ About Me', 'About Me'],
    ];
} else {
    // Menu ADMIN
    $judul_sidebar = 'Sistem Pelanggaran';
    $menu_items = [
        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],
        ['kelola_siswa.php', '♟ Data Siswa', 'Data Siswa'],
        ['kelola_guru.php', '♟ Data Guru', 'Data Guru'],
        ['kelola_kelas.php', '▦ Data Kelas', 'Data Kelas'],
        ['kelola_tahun_ajaran.php', '◷ Kelola Tahun Ajaran', 'Tahun Ajaran'],
        ['kelola_wali_kelas.php', '♙ Kelola Wali Kelas', 'Wali Kelas'],
        ['kelola_penempatan_siswa.php', '⇄ Penempatan Siswa', 'Penempatan Siswa'],
        ['kelola_kategori_pelanggaran.php', '☷ Kategori Pelanggaran', 'Kategori Pelanggaran'],
        ['tentang_saya.php', 'ℹ About Me', 'About Me'],
    ];
}
?>
<aside class="col-md-3 col-lg-2 sidebar">
    <div class="sidebar-title"><?= htmlspecialchars($judul_sidebar) ?></div>

    <nav class="nav flex-column">
        <?php foreach ($menu_items as $item): ?>
            <a href="<?= htmlspecialchars($item[0]) ?>"
               class="nav-link <?= $menu_aktif === $item[2] ? 'active' : '' ?>">
                <?= htmlspecialchars($item[1]) ?>
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
