
<?php
require 'cek_akses.php';

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? 'guru';

$menuAdmin = [
    ['Dashboard', 'dashboard.php', '▦'],
    ['Kelola Siswa', 'menu1.php', '♙'],
    ['Kelola Guru', 'menu2.php', '♟'],
    ['Kelola Jenis Pelanggaran', 'menu3.php', '⚑'],
    ['Admin', 'menu4.php', '⚙'],
    ['Kelola Kelas', 'kelola_kelas.php', '▤'],
    ['Kelola Tahun Ajaran', 'kelola_tahun_ajaran.php', '▣'],
    ['Penempatan Siswa', 'penempatan_siwa.php', '⇄'],
    ['Kelola Wali Kelas', 'kelola_wali_kelas.php', '♧'],
    ['Kelola Kategori Pelanggaran', 'kelola_kategori_pelanggaran.php', '☷'],
    ['Laporan', 'laporan.php', '▥'],
    ['Logout', 'logout.php', '⇥'],
];

$menuGuru = [
    ['Dashboard', 'dashboard.php', '▦'],
    ['Kelola Siswa', 'menu1.php', '♙'],
    ['Kelola Jenis Pelanggaran', 'menu3.php', '⚑'],
    ['Kelola Kategori Pelanggaran', 'kelola_kategori_pelanggaran.php', '☷'],
    ['Laporan', 'laporan.php', '▥'],
    ['Logout', 'logout.php', '⇥'],
];

$menus = $role === 'admin' ? $menuAdmin : $menuGuru;

// Hitung menu yang dapat diakses oleh pengguna.
$totalMenu = count($menus);

// Statistik berikut adalah informasi menu, bukan jumlah data database.
$totalKelola = count(array_filter($menus, function ($menu) {
    return !in_array($menu[1], ['dashboard.php', 'logout.php']);
}));

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UKK 2026</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #3157d5;
            --primary-dark: #2445b3;
            --sidebar: #172554;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --background: #f4f7fb;
            --white: #fff;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            padding: 25px 16px;
            background: var(--sidebar);
            color: white;
            overflow-y: auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 8px 25px;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--primary);
            font-size: 22px;
            font-weight: bold;
        }

        .brand h2 {
            font-size: 19px;
        }

        .brand p {
            margin-top: 4px;
            color: #cbd5e1;
            font-size: 12px;
        }

        .menu-label {
            margin: 25px 10px 10px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.4px;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border-radius: 9px;
            text-decoration: none;
            color: #dbeafe;
            font-size: 13px;
            transition: .2s;
        }

        .nav-menu a:hover {
            background: rgba(255,255,255,.1);
            color: white;
        }

        .nav-menu a.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .nav-icon {
            width: 21px;
            text-align: center;
            font-size: 16px;
        }

        .nav-menu .logout-link {
            margin-top: 15px;
            padding-top: 17px;
            border-top: 1px solid rgba(255,255,255,.15);
            border-radius: 0 0 9px 9px;
        }

        .main {
            margin-left: 250px;
            padding: 28px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 28px;
        }

        .topbar h1 {
            margin-bottom: 6px;
            font-size: 25px;
        }

        .topbar p {
            color: var(--muted);
            font-size: 13px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 14px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8edff;
            color: var(--primary);
            font-weight: bold;
        }

        .user-info strong {
            display: block;
            font-size: 13px;
        }

        .user-info small {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: var(--muted);
            font-size: 13px;
        }

        .breadcrumb span {
            color: var(--primary);
            font-weight: 600;
        }

        .welcome {
            position: relative;
            overflow: hidden;
            padding: 28px;
            margin-bottom: 23px;
            border-radius: 16px;
            color: white;
            background: linear-gradient(120deg, #3157d5, #5278ec);
            box-shadow: 0 8px 24px rgba(49,87,213,.14);
        }

        .welcome h2 {
            margin-bottom: 10px;
            font-size: 25px;
        }

        .welcome p {
            max-width: 650px;
            color: #e4eaff;
            font-size: 14px;
            line-height: 1.7;
        }

        .welcome-tag {
            display: inline-block;
            margin-top: 17px;
            padding: 7px 12px;
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 20px;
            background: rgba(255,255,255,.12);
            font-size: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 12px;
            background: #e8edff;
            color: var(--primary);
            font-size: 23px;
        }

        .stat-card h3 {
            margin-bottom: 5px;
            font-size: 23px;
        }

        .stat-card p {
            color: var(--muted);
            font-size: 12px;
        }

        .section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 17px;
        }

        .section-heading h2 {
            margin-bottom: 5px;
            font-size: 19px;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 13px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
        }

        .menu-card {
            display: flex;
            flex-direction: column;
            min-height: 175px;
            padding: 21px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            text-decoration: none;
            color: var(--text);
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            border-color: #b8c7ff;
            box-shadow: 0 8px 22px rgba(15,23,42,.07);
        }

        .menu-card-icon {
            display: grid;
            place-items: center;
            width: 45px;
            height: 45px;
            margin-bottom: 17px;
            border-radius: 12px;
            background: #e8edff;
            color: var(--primary);
            font-size: 22px;
        }

        .menu-card h3 {
            margin-bottom: 8px;
            font-size: 15px;
        }

        .menu-card p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.6;
        }

        .menu-card .open-link {
            margin-top: auto;
            padding-top: 15px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
        }

        .footer {
            padding: 30px 0 10px;
            color: #94a3b8;
            text-align: center;
            font-size: 12px;
        }

        @media (max-width: 1150px) {
            .menu-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 850px) {
            .sidebar {
                width: 215px;
            }

            .main {
                margin-left: 215px;
                padding: 20px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 650px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .nav-menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .main {
                margin-left: 0;
                padding: 16px;
            }

            .menu-grid,
            .stats {
                grid-template-columns: 1fr;
            }

            .welcome {
                padding: 22px;
            }

            .welcome h2 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">U</div>
        <div>
            <h2>UKK 2026</h2>
            <p>Sistem Pelanggaran Siswa</p>
        </div>
    </div>

    <div class="menu-label">MENU NAVIGASI</div>

    <nav class="nav-menu">
        <?php foreach ($menus as [$label, $url, $icon]): ?>
            <a
                href="<?= e($url) ?>"
                class="<?= $url === 'dashboard.php' ? 'active' : '' ?> <?= $url === 'logout.php' ? 'logout-link' : '' ?>"
                <?php if ($url === 'logout.php'): ?>
                    onclick="return confirm('Yakin ingin keluar dari aplikasi?')"
                <?php endif; ?>
            >
                <span class="nav-icon"><?= e($icon) ?></span>
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Ringkasan dan navigasi Sistem Pelanggaran Siswa.</p>
        </div>

        <div class="user-info">
            <div class="avatar">
                <?= e(strtoupper(substr($name, 0, 1))) ?>
            </div>
            <div>
                <strong><?= e($name) ?></strong>
                <small><?= e(ucfirst($role)) ?> · UKK 2026</small>
            </div>
        </div>
    </header>

    <div class="breadcrumb">
        Halaman Utama &nbsp; / &nbsp; <span>Dashboard</span>
    </div>

    <section class="welcome">
        <h2>Selamat Datang, <?= e($name) ?>! 👋</h2>
        <p>
            Selamat datang di Sistem Informasi Pelanggaran Siswa UKK 2026.
            Gunakan menu yang tersedia untuk mengelola data dan mengakses
            informasi sesuai dengan hak akses akun kamu.
        </p>
        <div class="welcome-tag">
            Login sebagai <?= e(ucfirst($role)) ?>
        </div>
    </section>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-icon">☷</div>
            <div>
                <h3><?= $totalMenu ?></h3>
                <p>Total menu yang tersedia</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚙</div>
            <div>
                <h3><?= e(ucfirst($role)) ?></h3>
                <p>Hak akses pengguna</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✓</div>
            <div>
                <h3><?= $totalKelola ?></h3>
                <p>Menu selain Dashboard dan Logout</p>
            </div>
        </div>
    </section>

    <section>
        <div class="section-heading">
            <div>
                <h2>Menu Sistem</h2>
                <p>Pilih menu di bawah untuk membuka halaman yang kamu perlukan.</p>
            </div>
        </div>

        <div class="menu-grid">
            <?php foreach ($menus as [$label, $url, $icon]): ?>
                <?php if ($url !== 'dashboard.php' && $url !== 'logout.php'): ?>
                    <a class="menu-card" href="<?= e($url) ?>">
                        <div class="menu-card-icon"><?= e($icon) ?></div>
                        <h3><?= e($label) ?></h3>
                        <p>
                            Buka halaman <?= e(strtolower($label)) ?>
                            untuk melihat atau mengelola data sesuai hak akses.
                        </p>
                        <span class="open-link">Buka halaman →</span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="footer">
        © 2026 UKK — Sistem Informasi Pelanggaran Siswa
    </footer>

</main>

</body>
</html>