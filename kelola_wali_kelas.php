
<?php
require_once 'cek_akses.php';
require_once 'koneksi.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
$namaUser = $_SESSION['name'] ?? 'Pengguna';

if ($role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Koneksi database PDO $pdo tidak ditemukan. Periksa koneksi.php.');
}

$pesan = '';
$error = '';
$cari = trim($_GET['cari'] ?? '');
$editData = null;
$data = [];
$total = 0;
$tahunAjaran = [];
$daftarKelas = [];
$daftarGuru = [];

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function kolomTabel(PDO $pdo, string $tabel): array
{
    $hasil = $pdo->query("SHOW COLUMNS FROM `$tabel`")->fetchAll(PDO::FETCH_ASSOC);
    return array_column($hasil, 'Field');
}

function pilihKolom(array $kolom, array $pilihan): ?string
{
    foreach ($pilihan as $nama) {
        if (in_array($nama, $kolom, true)) {
            return $nama;
        }
    }

    return null;
}

function ambilPilihan(
    PDO $pdo,
    string $tabel,
    array $pilihanLabel
): array {
    $kolom = kolomTabel($pdo, $tabel);
    $kolomId = pilihKolom($kolom, ['id']);
    $kolomLabel = pilihKolom($kolom, $pilihanLabel);

    if (!$kolomId || !$kolomLabel) {
        throw new RuntimeException(
            "Kolom id atau kolom nama pada tabel $tabel tidak ditemukan."
        );
    }

    $sql = "SELECT `$kolomId` AS id, `$kolomLabel` AS label
            FROM `$tabel`
            ORDER BY `$kolomLabel`";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

try {
    // Ambil pilihan dari database.
    $tahunAjaran = ambilPilihan(
        $pdo,
        't_tahun_ajaran',
        ['label', 'tahun_ajaran', 'nama_tahun_ajaran', 'nama', 'tahun']
    );

    $daftarKelas = ambilPilihan(
        $pdo,
        't_kelas',
        ['nama_kelas', 'nama', 'label', 'kelas']
    );

    $daftarGuru = ambilPilihan(
        $pdo,
        't_guru',
        ['nama_guru', 'nama', 'name']
    );

    // TAMBAH DATA
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
        $tahunId = (int)($_POST['tahun_ajaran_id'] ?? 0);
        $kelasId = (int)($_POST['kelas_id'] ?? 0);
        $guruId = (int)($_POST['guru_id'] ?? 0);
        $mulai = $_POST['tanggal_mulai'] ?? '';
        $selesai = $_POST['tanggal_selesai'] ?? '';
        $aktif = isset($_POST['status_aktif']) ? 1 : 0;

        if (!$tahunId || !$kelasId || !$guruId || !$mulai) {
            throw new RuntimeException('Tahun ajaran, kelas, guru, dan tanggal mulai wajib diisi.');
        }

        if ($selesai !== '' && $selesai < $mulai) {
            throw new RuntimeException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO t_wali_kelas
            (tahun_ajaran_id, kelas_id, guru_id, tanggal_mulai, tanggal_selesai, status_aktif)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $tahunId,
            $kelasId,
            $guruId,
            $mulai,
            $selesai !== '' ? $selesai : null,
            $aktif
        ]);

        header('Location: kelola_wali_kelas.php?pesan=tambah');
        exit;
    }

    // EDIT DATA
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
        $id = (int)($_POST['id'] ?? 0);
        $tahunId = (int)($_POST['tahun_ajaran_id'] ?? 0);
        $kelasId = (int)($_POST['kelas_id'] ?? 0);
        $guruId = (int)($_POST['guru_id'] ?? 0);
        $mulai = $_POST['tanggal_mulai'] ?? '';
        $selesai = $_POST['tanggal_selesai'] ?? '';
        $aktif = isset($_POST['status_aktif']) ? 1 : 0;

        if (!$id || !$tahunId || !$kelasId || !$guruId || !$mulai) {
            throw new RuntimeException('Data wajib belum lengkap.');
        }

        if ($selesai !== '' && $selesai < $mulai) {
            throw new RuntimeException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        $stmt = $pdo->prepare(
            "UPDATE t_wali_kelas
             SET tahun_ajaran_id = ?,
                 kelas_id = ?,
                 guru_id = ?,
                 tanggal_mulai = ?,
                 tanggal_selesai = ?,
                 status_aktif = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $tahunId,
            $kelasId,
            $guruId,
            $mulai,
            $selesai !== '' ? $selesai : null,
            $aktif,
            $id
        ]);

        header('Location: kelola_wali_kelas.php?pesan=edit');
        exit;
    }

    // HAPUS DATA
    if (isset($_GET['hapus'])) {
        $id = (int)$_GET['hapus'];

        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM t_wali_kelas WHERE id = ?');
            $stmt->execute([$id]);
        }

        header('Location: kelola_wali_kelas.php?pesan=hapus');
        exit;
    }

    // AMBIL DATA UNTUK FORM EDIT
    if (isset($_GET['edit'])) {
        $id = (int)$_GET['edit'];

        $stmt = $pdo->prepare(
            'SELECT * FROM t_wali_kelas WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // CARI DAN TAMPILKAN DATA
    $kolomTahun = kolomTabel($pdo, 't_tahun_ajaran');
    $kolomKelas = kolomTabel($pdo, 't_kelas');
    $kolomGuru = kolomTabel($pdo, 't_guru');

    $namaTahun = pilihKolom(
        $kolomTahun,
        ['label', 'tahun_ajaran', 'nama_tahun_ajaran', 'nama', 'tahun']
    );

    $namaKelas = pilihKolom(
        $kolomKelas,
        ['nama_kelas', 'nama', 'label', 'kelas']
    );

    $namaGuru = pilihKolom(
        $kolomGuru,
        ['nama_guru', 'nama', 'name']
    );

    if (!$namaTahun || !$namaKelas || !$namaGuru) {
        throw new RuntimeException('Kolom nama tahun ajaran, kelas, atau guru tidak ditemukan.');
    }

    $sql = "SELECT
                w.id,
                w.tahun_ajaran_id,
                w.kelas_id,
                w.guru_id,
                w.tanggal_mulai,
                w.tanggal_selesai,
                w.status_aktif,
                ta.`$namaTahun` AS nama_tahun,
                k.`$namaKelas` AS nama_kelas,
                g.`$namaGuru` AS nama_guru
            FROM t_wali_kelas w
            LEFT JOIN t_tahun_ajaran ta ON ta.id = w.tahun_ajaran_id
            LEFT JOIN t_kelas k ON k.id = w.kelas_id
            LEFT JOIN t_guru g ON g.id = w.guru_id";

    $params = [];

    if ($cari !== '') {
        $sql .= " WHERE CAST(ta.`$namaTahun` AS CHAR) LIKE ?
                  OR CAST(k.`$namaKelas` AS CHAR) LIKE ?
                  OR CAST(g.`$namaGuru` AS CHAR) LIKE ?";
        $kata = "%$cari%";
        $params = [$kata, $kata, $kata];
    }

    $sql .= ' ORDER BY w.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($data);

} catch (Throwable $ex) {
    $error = $ex->getMessage();
}

$pesan = $_GET['pesan'] ?? $pesan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Wali Kelas | UKK 2026</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--primary:#3157d5;--sidebar:#172554;--text:#1e293b;--muted:#64748b;--border:#e2e8f0;--background:#f4f7fb}
body{font-family:"Segoe UI",Arial,sans-serif;background:var(--background);color:var(--text);font-size:14px}
.sidebar{position:fixed;inset:0 auto 0 0;width:250px;padding:25px 16px;background:var(--sidebar);color:white;overflow-y:auto;z-index:100}
.brand{display:flex;align-items:center;gap:12px;padding:0 8px 25px;border-bottom:1px solid #ffffff26}
.brand-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:12px;background:var(--primary);font-size:22px;font-weight:bold}
.brand h2{font-size:19px}.brand p{margin-top:4px;color:#cbd5e1;font-size:12px}
.menu-label{margin:25px 10px 10px;color:#94a3b8;font-size:11px;font-weight:bold;letter-spacing:1.4px}
.nav-menu{display:flex;flex-direction:column;gap:5px}
.nav-menu a{display:flex;align-items:center;gap:11px;padding:11px 12px;border-radius:9px;color:#dbeafe;text-decoration:none;font-size:13px;transition:.2s}
.nav-menu a:hover{background:#ffffff1a;color:white}
.nav-menu a.active{background:var(--primary);color:white;font-weight:600}
.nav-icon{width:21px;text-align:center;font-size:16px}
.nav-menu .logout-link{margin-top:15px;padding-top:17px;border-top:1px solid #ffffff26}
.main{margin-left:250px;padding:28px}
.topbar{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:28px}
.topbar h1{margin-bottom:6px;font-size:25px}.topbar p{color:var(--muted)}
.user-info{display:flex;align-items:center;gap:11px;padding:10px 14px;background:white;border:1px solid var(--border);border-radius:12px}
.avatar{width:40px;height:40px;display:grid;place-items:center;border-radius:50%;background:#e8edff;color:var(--primary);font-weight:bold}
.user-info strong,.user-info small{display:block}.user-info small{margin-top:3px;color:var(--muted)}
.breadcrumb{margin-bottom:20px;color:var(--muted)}.breadcrumb span{color:var(--primary);font-weight:600}
.welcome{padding:26px;margin-bottom:23px;border-radius:16px;color:white;background:linear-gradient(120deg,#3157d5,#5278ec);box-shadow:0 8px 24px #3157d524}
.welcome h2{margin-bottom:9px;font-size:24px}.welcome p{color:#e4eaff;line-height:1.7}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:23px}
.stat-card{padding:21px;background:white;border:1px solid var(--border);border-radius:14px}
.stat-card span{display:block;margin-bottom:10px;color:var(--muted)}.stat-card strong{color:var(--primary);font-size:27px}
.card{padding:24px;margin-bottom:23px;background:white;border:1px solid var(--border);border-radius:14px;box-shadow:0 3px 12px #0f172a06}
.card-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:21px;padding-bottom:15px;border-bottom:1px solid var(--border)}
.card-title h3{font-size:18px}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.form-group{margin-bottom:17px}.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#334155}
.form-group input,.form-group select{width:100%;padding:12px 13px;border:1px solid #cbd5e1;border-radius:8px;background:white;font:inherit;outline:none}
.form-group input:focus,.form-group select:focus{border-color:var(--primary);box-shadow:0 0 0 3px #3157d51a}
.checkbox{display:flex;align-items:center;gap:9px;margin:4px 0 20px}.checkbox input{width:17px;height:17px;accent-color:var(--primary)}
.btn{display:inline-flex;justify-content:center;align-items:center;padding:10px 14px;border:0;border-radius:7px;font:inherit;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer}
.btn-primary{background:var(--primary);color:white}.btn-primary:hover{background:#2445b3}
.btn-secondary{background:#e2e8f0;color:#334155}.btn-edit{background:#dbeafe;color:#1d4ed8}.btn-delete{background:#fee2e2;color:#b91c1c}
.search-form{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.search-form input{flex:1;min-width:220px;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;outline:none}
.table-wrapper{width:100%;overflow-x:auto;border:1px solid var(--border);border-radius:9px}
table{width:100%;min-width:950px;border-collapse:collapse;background:white}
th{padding:15px;text-align:left;white-space:nowrap;background:#eff6ff;color:#1e40af;border-bottom:1px solid #dbeafe}
td{padding:14px 15px;color:#475569;border-bottom:1px solid var(--border);vertical-align:middle}
tbody tr:hover{background:#f8fafc}.action-buttons{display:flex;gap:7px}
.badge{display:inline-block;padding:6px 10px;border-radius:20px;background:#dcfce7;color:#166534;font-size:12px;font-weight:600}
.badge-off{background:#fee2e2;color:#b91c1c}
.alert{margin-bottom:20px;padding:13px 16px;border-radius:8px}
.alert-success{background:#dcfce7;color:#166534}.alert-error{background:#fee2e2;color:#991b1b}
.empty-data{padding:30px;text-align:center;color:var(--muted)}
.footer{padding:25px;text-align:center;color:#94a3b8}
@media(max-width:1100px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.form-grid{grid-template-columns:1fr;gap:0}}
@media(max-width:800px){.sidebar{width:215px}.main{margin-left:215px;padding:20px}.topbar{align-items:flex-start;flex-direction:column}}
@media(max-width:650px){.sidebar{position:relative;width:100%;height:auto}.nav-menu{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.main{margin-left:0;padding:15px}.stats{grid-template-columns:1fr}.welcome,.card{padding:18px}.search-form{flex-direction:column}.search-form input{width:100%}}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">U</div>
        <div><h2>UKK 2026</h2><p>Sistem Pelanggaran Siswa</p></div>
    </div>
    <div class="menu-label">MENU NAVIGASI</div>
    <nav class="nav-menu">
        <?php
        $sidebarMenus = [
            ['Dashboard','dashboard.php','▦'],
            ['Kelola Siswa','menu1.php','♙'],
            ['Kelola Guru','menu2.php','♟'],
            ['Kelola Jenis Pelanggaran','menu3.php','⚑'],
            ['Admin','menu4.php','⚙'],
            ['Kelola Kelas','kelola_kelas.php','▤'],
            ['Kelola Tahun Ajaran','kelola_tahun_ajaran.php','▣'],
            ['Penempatan Siswa','penempatan_siwa.php','⇄'],
            ['Kelola Wali Kelas','kelola_wali_kelas.php','♧'],
            ['Kelola Kategori Pelanggaran','kelola_kategori_pelanggaran.php','☷'],
            ['Laporan','laporan.php','▥'],
            ['Logout','logout.php','⇥']
        ];

        foreach ($sidebarMenus as [$label,$url,$icon]):
            if ($role !== 'admin' && !in_array($url, [
                'dashboard.php','menu1.php','menu3.php',
                'kelola_kategori_pelanggaran.php','laporan.php','logout.php'
            ], true)) {
                continue;
            }
        ?>
        <a href="<?= e($url) ?>"
           class="<?= $url === 'kelola_wali_kelas.php' ? 'active' : '' ?> <?= $url === 'logout.php' ? 'logout-link' : '' ?>"
           <?php if ($url === 'logout.php'): ?>onclick="return confirm('Yakin ingin logout?')"<?php endif; ?>>
            <span class="nav-icon"><?= e($icon) ?></span><?= e($label) ?>
        </a>
        <?php endforeach; ?>
    </nav>
</aside>

<main class="main">
    <header class="topbar">
        <div>
            <h1>Kelola Wali Kelas</h1>
            <p>Kelola penugasan wali kelas di sistem UKK 2026.</p>
        </div>
        <div class="user-info">
            <div class="avatar"><?= e(strtoupper(substr($namaUser, 0, 1))) ?></div>
            <div>
                <strong><?= e($namaUser) ?></strong>
                <small><?= e(ucfirst($role)) ?> · UKK 2026</small>
            </div>
        </div>
    </header>

    <div class="breadcrumb">Dashboard &nbsp; / &nbsp; <span>Kelola Wali Kelas</span></div>

    <section class="welcome">
        <h2>Kelola Wali Kelas</h2>
        <p>Atur penugasan guru sebagai wali kelas berdasarkan tahun ajaran, kelas, tanggal mulai, dan status penugasan.</p>
    </section>

    <?php if ($pesan !== ''): ?>
        <div class="alert alert-success">
            <?php
            if ($pesan === 'tambah') echo 'Data wali kelas berhasil ditambahkan.';
            elseif ($pesan === 'edit') echo 'Data wali kelas berhasil diperbarui.';
            elseif ($pesan === 'hapus') echo 'Data wali kelas berhasil dihapus.';
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card"><span>Jumlah data ditemukan</span><strong><?= (int)$total ?></strong></div>
        <div class="stat-card"><span>Status pencarian</span><strong><?= $cari !== '' ? 'Cari' : 'Siap' ?></strong></div>
        <div class="stat-card"><span>Hak akses</span><strong><?= e(ucfirst($role)) ?></strong></div>
    </section>

    <section class="card">
        <div class="card-title">
            <h3><?= $editData ? 'Edit Penugasan Wali Kelas' : 'Tambah Wali Kelas' ?></h3>
        </div>

        <form method="POST" action="kelola_wali_kelas.php">
            <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tahun_ajaran_id">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" id="tahun_ajaran_id" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <?php foreach ($tahunAjaran as $tahun): ?>
                            <option value="<?= (int)$tahun['id'] ?>"
                                <?= $editData && (int)$editData['tahun_ajaran_id'] === (int)$tahun['id'] ? 'selected' : '' ?>>
                                <?= e($tahun['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="kelas_id">Kelas</label>
                    <select name="kelas_id" id="kelas_id" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($daftarKelas as $kelas): ?>
                            <option value="<?= (int)$kelas['id'] ?>"
                                <?= $editData && (int)$editData['kelas_id'] === (int)$kelas['id'] ? 'selected' : '' ?>>
                                <?= e($kelas['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="guru_id">Guru Wali Kelas</label>
                    <select name="guru_id" id="guru_id" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($daftarGuru as $guru): ?>
                            <option value="<?= (int)$guru['id'] ?>"
                                <?= $editData && (int)$editData['guru_id'] === (int)$guru['id'] ? 'selected' : '' ?>>
                                <?= e($guru['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                        value="<?= e($editData['tanggal_mulai'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                        value="<?= e(($editData['tanggal_selesai'] ?? '') === '0000-00-00' ? '' : ($editData['tanggal_selesai'] ?? '')) ?>">
                </div>
            </div>

            <label class="checkbox">
                <input type="checkbox" name="status_aktif" value="1"
                    <?= !$editData || (int)($editData['status_aktif'] ?? 0) === 1 ? 'checked' : '' ?>>
                Status penugasan aktif
            </label>

            <?php if ($editData): ?>
                <button type="submit" name="edit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="kelola_wali_kelas.php" class="btn btn-secondary">Batal</a>
            <?php else: ?>
                <button type="submit" name="tambah" class="btn btn-primary">+ Tambah Wali Kelas</button>
            <?php endif; ?>
        </form>
    </section>

    <section class="card">
        <div class="card-title">
            <h3>Daftar Wali Kelas</h3>
            <span class="badge"><?= (int)$total ?> data</span>
        </div>

        <form method="GET" class="search-form">
            <input type="text" name="cari" placeholder="Cari tahun ajaran, kelas, atau nama guru..."
                   value="<?= e($cari) ?>">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="kelola_wali_kelas.php" class="btn btn-secondary">Reset</a>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th><th>Tahun Ajaran</th><th>Kelas</th>
                        <th>Guru Wali Kelas</th><th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total > 0): ?>
                    <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($row['nama_tahun']) ?></td>
                            <td><?= e($row['nama_kelas']) ?></td>
                            <td><?= e($row['nama_guru']) ?></td>
                            <td><?= e($row['tanggal_mulai']) ?></td>
                            <td><?= empty($row['tanggal_selesai']) || $row['tanggal_selesai'] === '0000-00-00' ? '—' : e($row['tanggal_selesai']) ?></td>
                            <td>
                                <?php if ((int)$row['status_aktif'] === 1): ?>
                                    <span class="badge">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-off">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a class="btn btn-edit" href="kelola_wali_kelas.php?edit=<?= (int)$row['id'] ?>">Edit</a>
                                    <a class="btn btn-delete" href="kelola_wali_kelas.php?hapus=<?= (int)$row['id'] ?>"
                                       onclick="return confirm('Yakin ingin menghapus data wali kelas ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="empty-data">Belum ada data wali kelas yang ditemukan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">&copy; <?= date('Y') ?> UKK 2026 — Sistem Pelanggaran Siswa</footer>
</main>
</body>
</html>