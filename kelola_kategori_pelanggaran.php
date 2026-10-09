
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';

if ($role !== 'admin') {
    http_response_code(403);
    exit("Akses ditolak. Halaman ini hanya untuk admin.");
}

require 'koneksi.php';

// Pastikan koneksi.php menyediakan variabel PDO bernama $pdo.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit("Koneksi database belum benar. Periksa koneksi.php.");
}

$pesan = '';
$error = '';

/* =========================
   TAMBAH DATA
========================= */
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '' || $deskripsi === '') {
        $error = "Nama dan deskripsi wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO t_pelanggaran_kategori (nama, deskripsi)
                VALUES (:nama, :deskripsi)
            ");

            $stmt->execute([
                'nama' => $nama,
                'deskripsi' => $deskripsi
            ]);

            header("Location: kelola_kategori_pelanggaran.php?pesan=tambah");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal menambahkan kategori. Periksa struktur tabel database.";
        }
    }
}

/* =========================
   EDIT DATA
========================= */
if (isset($_POST['edit'])) {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($id <= 0 || $nama === '' || $deskripsi === '') {
        $error = "Data edit tidak lengkap.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE t_pelanggaran_kategori
                SET nama = :nama, deskripsi = :deskripsi
                WHERE id = :id
            ");

            $stmt->execute([
                'nama' => $nama,
                'deskripsi' => $deskripsi,
                'id' => $id
            ]);

            header("Location: kelola_kategori_pelanggaran.php?pesan=edit");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal mengubah kategori pelanggaran.";
        }
    }
}

/* =========================
   HAPUS DATA
========================= */
if (isset($_POST['hapus'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("
                DELETE FROM t_pelanggaran_kategori
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);

            header("Location: kelola_kategori_pelanggaran.php?pesan=hapus");
            exit;
        } catch (PDOException $e) {
            $error = "Kategori tidak dapat dihapus. Periksa apakah data tersebut sedang digunakan.";
        }
    }
}

/* =========================
   PESAN NOTIFIKASI
========================= */
if (isset($_GET['pesan'])) {
    $daftarPesan = [
        'tambah' => 'Kategori berhasil ditambahkan.',
        'edit' => 'Kategori berhasil diperbarui.',
        'hapus' => 'Kategori berhasil dihapus.'
    ];

    $pesan = $daftarPesan[$_GET['pesan']] ?? '';
}

/* =========================
   AMBIL DATA EDIT
========================= */
$dataEdit = null;

if (isset($_GET['edit'])) {
    $idEdit = (int)$_GET['edit'];

    $stmt = $pdo->prepare("
        SELECT id, nama, deskripsi
        FROM t_pelanggaran_kategori
        WHERE id = :id
    ");
    $stmt->execute(['id' => $idEdit]);
    $dataEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   PENCARIAN DAN DAFTAR DATA
========================= */
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $stmt = $pdo->prepare("
        SELECT id, nama, deskripsi
        FROM t_pelanggaran_kategori
        WHERE nama LIKE :cari1
           OR deskripsi LIKE :cari2
        ORDER BY id ASC
    ");

    $keyword = '%' . $cari . '%';
    $stmt->execute([
        'cari1' => $keyword,
        'cari2' => $keyword
    ]);
} else {
    $stmt = $pdo->query("
        SELECT id, nama, deskripsi
        FROM t_pelanggaran_kategori
        ORDER BY id ASC
    ");
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalKategori = count($data);

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori Pelanggaran | UKK 2026</title>

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
            --white: #ffffff;
            --green: #15803d;
            --red: #dc2626;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 250px;
            background: var(--sidebar);
            color: white;
            padding: 25px 16px;
            overflow-y: auto;
            z-index: 10;
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
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--primary);
            font-size: 21px;
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

        .logout-link {
            margin-top: 18px;
            border-top: 1px solid rgba(255,255,255,.15);
            padding-top: 18px !important;
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
            font-size: 25px;
            margin-bottom: 6px;
        }

        .topbar p {
            color: var(--muted);
            font-size: 13px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 11px;
            background: white;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .avatar {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: var(--primary);
            background: #e8edff;
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
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .breadcrumb span {
            color: var(--primary);
            font-weight: 600;
        }

        .welcome {
            padding: 25px;
            border-radius: 16px;
            color: white;
            background: linear-gradient(120deg, #3157d5, #5278ec);
            margin-bottom: 23px;
            box-shadow: 0 8px 24px rgba(49,87,213,.14);
        }

        .welcome h2 {
            font-size: 23px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #e4eaff;
            line-height: 1.6;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 23px;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #e8edff;
            color: var(--primary);
            font-size: 23px;
        }

        .stat-card h3 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        .stat-card p {
            font-size: 12px;
            color: var(--muted);
        }

        .panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 23px;
            margin-bottom: 22px;
            box-shadow: 0 3px 12px rgba(15,23,42,.025);
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .panel-heading h3 {
            font-size: 17px;
            margin-bottom: 5px;
        }

        .panel-heading p {
            color: var(--muted);
            font-size: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 18px;
        }

        .field {
            margin-bottom: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .field input,
        .field textarea,
        .search-input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d7deea;
            border-radius: 9px;
            background: white;
            color: var(--text);
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: .2s;
        }

        .field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .field input:focus,
        .field textarea:focus,
        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(49,87,213,.1);
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            border: 0;
            border-radius: 8px;
            padding: 11px 15px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: .2s;
        }

        .btn-primary {
            color: white;
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-light {
            background: #eef2f7;
            color: #334155;
        }

        .btn-light:hover {
            background: #e2e8f0;
        }

        .btn-edit {
            background: #fff7db;
            color: #946200;
            padding: 7px 10px;
        }

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
            padding: 7px 10px;
        }

        .notice {
            border-radius: 9px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .notice-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }

        .notice-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        .table-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .search-form {
            display: flex;
            align-items: center;
            gap: 8px;
            width: min(100%, 430px);
        }

        .search-input {
            min-width: 0;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        thead {
            background: #f5f7fc;
        }

        th {
            text-align: left;
            padding: 14px 13px;
            color: #64748b;
            font-size: 11px;
            letter-spacing: .4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid #edf0f5;
            font-size: 13px;
            line-height: 1.6;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #fafbff;
        }

        .id-badge {
            display: inline-block;
            background: #eef2ff;
            color: #3730a3;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .category-name {
            font-weight: 600;
            color: #1e293b;
        }

        .description {
            color: #64748b;
            max-width: 350px;
            overflow-wrap: anywhere;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .actions form {
            display: inline;
        }

        .empty-state {
            padding: 35px 15px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state .empty-icon {
            font-size: 34px;
            margin-bottom: 10px;
        }

        .empty-state strong {
            display: block;
            color: var(--text);
            margin-bottom: 6px;
        }

        .footer {
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            padding: 8px 0 15px;
        }

        @media (max-width: 1000px) {
            .sidebar {
                width: 215px;
            }

            .main {
                margin-left: 215px;
                padding: 22px;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                max-height: none;
            }

            .nav-menu {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .main {
                margin-left: 0;
                padding: 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar h1 {
                font-size: 22px;
            }

            .stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .welcome {
                padding: 20px;
            }

            .panel {
                padding: 16px;
            }

            .table-tools {
                align-items: stretch;
                flex-direction: column;
            }

            .search-form {
                width: 100%;
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

    <div class="menu-label">MENU UTAMA</div>

    <nav class="nav-menu">
        <a href="dashboard.php">
            <span class="nav-icon">▦</span> Dashboard
        </a>
        <a href="menu1.php">
            <span class="nav-icon">♙</span> Kelola Siswa
        </a>
        <a href="menu2.php">
            <span class="nav-icon">♟</span> Kelola Guru
        </a>
        <a href="menu3.php">
            <span class="nav-icon">⚑</span> Jenis Pelanggaran
        </a>
        <a href="menu4.php">
            <span class="nav-icon">⚙</span> Admin
        </a>

        <a href="kelola_kelas.php">
            <span class="nav-icon">▤</span> Kelola Kelas
        </a>
        <a href="kelola_tahun_ajaran.php">
            <span class="nav-icon">▣</span> Tahun Ajaran
        </a>
        <a href="penempatan_siwa.php">
            <span class="nav-icon">⇄</span> Penempatan Siswa
        </a>
        <a href="kelola_wali_kelas.php">
            <span class="nav-icon">♧</span> Wali Kelas
        </a>
        <a class="active" href="kelola_kategori_pelanggaran.php">
            <span class="nav-icon">☷</span> Kategori Pelanggaran
        </a>
        <a href="laporan.php">
            <span class="nav-icon">▥</span> Laporan
        </a>

        <a class="logout-link" href="logout.php"
           onclick="return confirm('Yakin ingin keluar dari aplikasi?')">
            <span class="nav-icon">⇥</span> Logout
        </a>
    </nav>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Kelola Kategori Pelanggaran</h1>
            <p>Kelola kategori pelanggaran siswa dengan mudah.</p>
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
        Dashboard &nbsp; / &nbsp; Pengaturan Data &nbsp; /
        <span>Kategori Pelanggaran</span>
    </div>

    <section class="welcome">
        <h2>Kelola Kategori Pelanggaran</h2>
        <p>
            Tambahkan, perbarui, cari, dan kelola kategori pelanggaran
            yang digunakan dalam pencatatan pelanggaran siswa.
        </p>
    </section>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-icon">☷</div>
            <div>
                <h3><?= $totalKategori ?></h3>
                <p><?= $cari !== '' ? 'Hasil Pencarian' : 'Kategori Ditampilkan' ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⌕</div>
            <div>
                <h3><?= $cari !== '' ? 'Aktif' : 'Siap' ?></h3>
                <p>Status halaman</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✓</div>
            <div>
                <h3>Admin</h3>
                <p>Hak akses pengelolaan</p>
            </div>
        </div>
    </section>

    <?php if ($pesan !== ''): ?>
        <div class="notice notice-success">
            ✓ <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice notice-error">
            ⚠ <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h3><?= $dataEdit ? 'Edit Kategori Pelanggaran' : 'Tambah Kategori Pelanggaran' ?></h3>
                <p>
                    <?= $dataEdit
                        ? 'Perbarui informasi kategori yang dipilih.'
                        : 'Isi nama dan deskripsi kategori baru.' ?>
                </p>
            </div>
        </div>

        <form method="POST" action="kelola_kategori_pelanggaran.php">
            <?php if ($dataEdit): ?>
                <input type="hidden" name="id" value="<?= e($dataEdit['id']) ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label for="nama">Nama Pelanggaran</label>
                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        maxlength="150"
                        placeholder="Contoh: Terlambat Masuk"
                        value="<?= e($dataEdit['nama'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="deskripsi">Deskripsi Pelanggaran</label>
                    <textarea
                        id="deskripsi"
                        name="deskripsi"
                        placeholder="Jelaskan kategori pelanggaran..."
                        required
                    ><?= e($dataEdit['deskripsi'] ?? '') ?></textarea>
                </div>
            </div>

            <button class="btn btn-primary" type="submit"
                    name="<?= $dataEdit ? 'edit' : 'tambah' ?>">
                <?= $dataEdit ? '✓ Simpan Perubahan' : '+ Tambah Kategori' ?>
            </button>

            <?php if ($dataEdit): ?>
                <a class="btn btn-light" href="kelola_kategori_pelanggaran.php">
                    Batal
                </a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h3>Daftar Kategori Pelanggaran</h3>
                <p>Data kategori yang tersimpan dalam database.</p>
            </div>
        </div>

        <div class="table-tools">
            <form class="search-form" method="GET"
                  action="kelola_kategori_pelanggaran.php">
                <input
                    class="search-input"
                    type="search"
                    name="cari"
                    value="<?= e($cari) ?>"
                    placeholder="Cari nama atau deskripsi..."
                >
                <button class="btn btn-primary" type="submit">Cari</button>

                <?php if ($cari !== ''): ?>
                    <a class="btn btn-light"
                       href="kelola_kategori_pelanggaran.php">Reset</a>
                <?php endif; ?>
            </form>

            <span style="font-size:12px;color:#64748b">
                Total: <strong><?= $totalKategori ?></strong> data
            </span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Pelanggaran</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($totalKategori > 0): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td>
                                <span class="id-badge">
                                    #<?= e($row['id']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="category-name">
                                    <?= e($row['nama']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="description">
                                    <?= nl2br(e($row['deskripsi'])) ?>
                                </div>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-edit"
                                       href="kelola_kategori_pelanggaran.php?edit=<?= (int)$row['id'] ?>">
                                        ✎ Edit
                                    </a>

                                    <form method="POST"
                                          action="kelola_kategori_pelanggaran.php"
                                          onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                        <input type="hidden" name="id"
                                               value="<?= (int)$row['id'] ?>">
                                        <button class="btn btn-delete"
                                                type="submit" name="hapus">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">▤</div>
                                <strong>Belum ada data kategori</strong>
                                <?= $cari !== ''
                                    ? 'Coba kata kunci pencarian yang berbeda.'
                                    : 'Tambahkan kategori melalui formulir di atas.' ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">
        © 2026 UKK — Sistem Informasi Pelanggaran Siswa
    </footer>

</main>
</body>
</html>