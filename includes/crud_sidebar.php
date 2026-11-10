
<?php

// =====================================
// PENGATURAN SIDEBAR
// =====================================
$current_page = basename($_SERVER['PHP_SELF']);

$menu_aktif = $menu_aktif ?? $current_page;

$role_sidebar = $_SESSION['role'] ?? '';

// =====================================
// CEK STATUS WALI KELAS
// =====================================
$is_wali_kelas_sidebar = (
    $role_sidebar === 'guru'
    && !empty($_SESSION['is_wali_kelas'])
);

// =====================================
// MENU WALI KELAS
// =====================================
if ($is_wali_kelas_sidebar) {

    $judul_sidebar = 'Panel Wali Kelas';

    $menu_items = [

        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],

        ['catatan_pelanggaran.php', '✎ Catatan Pelanggaran', 'Catatan Pelanggaran'],

        ['tindakan.php', '⚑ Tindakan', 'Tindakan'],

        ['riwayat_pelanggaran.php', '☰ Riwayat Pelanggaran', 'Riwayat Pelanggaran'],

        ['laporan.php', '▤ Laporan', 'Laporan'],

        ['rekap_point.php', '★ Rekap Poin', 'Rekap Poin'],

        ['tentang_saya.php', 'ℹ About Me', 'About Me'],
    ];

// =====================================
// MENU GURU BIASA
// =====================================
} elseif ($role_sidebar === 'guru') {

    $judul_sidebar = 'Panel Guru';

    $menu_items = [

        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],

        ['catatan_pelanggaran.php', '✎ Catatan Pelanggaran', 'Catatan Pelanggaran'],

        ['tindakan.php', '⚑ Tindakan', 'Tindakan'],

        ['riwayat_pelanggaran.php', '☰ Riwayat Pelanggaran', 'Riwayat Pelanggaran'],

        ['rekap_point.php', '★ Rekap Poin', 'Rekap Poin'],

        ['tentang_saya.php', 'ℹ About Me', 'About Me'],
    ];

// =====================================
// MENU ADMIN
// =====================================
} elseif ($role_sidebar === 'admin') {

    $judul_sidebar = 'My Website';

    $menu_items = [

        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],

        ['kelola_siswa.php', '♟ Data Siswa', 'Data Siswa'],

        ['kelola_guru.php', '♟ Data Guru', 'Data Guru'],

        ['kelola_kelas.php', '▦ Data Kelas', 'Data Kelas'],

        ['kelola_tahun_ajaran.php', '◷ Kelola Tahun Ajaran', 'Tahun Ajaran'],

        ['kelola_wali_kelas.php', '♙ Kelola Wali Kelas', 'Wali Kelas'],

        ['kelola_penempatan_siswa.php', '⇄ Penempatan Siswa', 'Penempatan Siswa'],

        ['kelola_kategori_pelanggaran.php', '☷ Kategori Pelanggaran', 'Kategori Pelanggaran'],

        ['laporan.php', '▤ Laporan', 'Laporan'],

        ['tentang_saya.php', 'ℹ About Me', 'About Me'],
    ];

} else {

    $judul_sidebar = 'Menu';

    $menu_items = [];
}

?>

<aside class="col-md-3 col-lg-2 sidebar">

    <!-- JUDUL SIDEBAR -->
    <div class="sidebar-title">
        <?= htmlspecialchars(
            $judul_sidebar,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

    <!-- MENU NAVIGASI -->
    <nav class="nav flex-column">

        <?php foreach ($menu_items as $item): ?>

            <a
                href="<?= htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') ?>"
                class="nav-link <?= $menu_aktif === $item[2] ? 'active' : '' ?>"
            >
                <?= htmlspecialchars(
                    $item[1],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </a>

        <?php endforeach; ?>

        <hr>

        <!-- LOGOUT -->
        <a
            href="logout.php"
            class="nav-link logout"
            onclick="return confirm('Yakin ingin logout?')"
        >
            ⇥ Logout
        </a>

    </nav>

</aside>
