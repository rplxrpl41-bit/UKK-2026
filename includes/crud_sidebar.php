<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_sidebar = $_SESSION['role'] ?? '';
$email_sidebar = strtolower(trim($_SESSION['email'] ?? ''));
$menu_aktif = $menu_aktif ?? '';

$email_wali_kelas = [
    'ari@gmail.com',
    'aldi@gmail.com',
    'anshor@gmail.com'
];

$is_wali_kelas = (
    $role_sidebar === 'guru' && (
        !empty($_SESSION['is_wali_kelas']) ||
        in_array($email_sidebar, $email_wali_kelas, true)
    )
);

// =====================================
// MENU ADMIN
// =====================================
if ($role_sidebar === 'admin') {

    // Menu admin dibuat SAMA dengan sidebar di halaman admin lainnya
    // (dashboard, kelola_*), termasuk menu About Me.
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
        ['laporan.php', '▤ Laporan', 'Laporan'],
        ['tentang_saya.php', 'ℹ About Me', 'About Me']
    ];

// =====================================
// MENU WALI KELAS
// =====================================
} elseif ($is_wali_kelas) {

    $judul_sidebar = 'Panel Guru';

    $menu_items = [
        ['dashboard.php', '⌂ Dashboard', 'Dashboard'],
        ['catatan_pelanggaran.php', '✎ Catatan Pelanggaran', 'Catatan Pelanggaran'],
        ['tindakan.php', '⚑ Tindakan', 'Tindakan'],
        ['riwayat_pelanggaran.php', '☰ Riwayat Pelanggaran', 'Riwayat Pelanggaran'],
        ['laporan.php', '▤ Laporan', 'Laporan'],
        ['rekap_point.php', '★ Rekap Poin', 'Rekap Poin'],
        ['tentang_saya.php', 'ℹ About Me', 'About Me']
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
        ['tentang_saya.php', 'ℹ About Me', 'About Me']
    ];

} else {
    http_response_code(403);
    exit('Akses ditolak!');
}
?>

<div class="sidebar">

    <h4 class="sidebar-title">
        <?= htmlspecialchars($judul_sidebar, ENT_QUOTES, 'UTF-8'); ?>
    </h4>

    <nav class="nav flex-column">

        <?php foreach ($menu_items as $item): ?>

            <?php
            $url = $item[0];
            $label = $item[1];
            $nama_menu = $item[2];

            $aktif = ($menu_aktif === $nama_menu);
            ?>

            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
               class="nav-link <?= $aktif ? 'active' : ''; ?>">

                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>

            </a>

        <?php endforeach; ?>

    </nav>

    <hr>

    <a href="logout.php"
       class="nav-link logout"
       onclick="return confirm('Yakin ingin logout?')">
        ⇥ Logout
    </a>

</div>
