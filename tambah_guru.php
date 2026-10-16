
<?php
include 'includes/cek_session.php';
include 'config/koneksi.php';

// Hanya admin yang boleh mengelola guru
if (($_SESSION['role'] ?? '') !== 'admin') {
    die('Akses ditolak!');
}

$pesan = '';
$tipe_pesan = 'danger';

// Ambil akun yang dapat dihubungkan dengan data guru
$users = mysqli_query(
    $koneksi,
    "SELECT id, name, email, role
     FROM t_users
     ORDER BY name ASC"
);

if (!$users) {
    die('Gagal mengambil akun: ' . mysqli_error($koneksi));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = isset($_POST['status_aktif'])
        ? (int) $_POST['status_aktif'] : -1;
    $user_id = (int) ($_POST['user_id'] ?? 0);

    if ($nip === '' || $nama === '' || $email === '') {
        $pesan = 'NIP, nama, dan email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = 'Format email tidak valid.';
    } elseif (!in_array($status, [0, 1], true)) {
        $pesan = 'Status guru tidak valid.';
    } elseif ($user_id <= 0) {
        $pesan = 'Silakan pilih akun pengguna.';
    } else {
        // Pastikan akun pengguna benar-benar tersedia
        $cek_user = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_users WHERE id = ?"
        );

        mysqli_stmt_bind_param($cek_user, 'i', $user_id);
        mysqli_stmt_execute($cek_user);
        mysqli_stmt_store_result($cek_user);

        $user_ada = mysqli_stmt_num_rows($cek_user) > 0;
        mysqli_stmt_close($cek_user);

        if (!$user_ada) {
            $pesan = 'Akun pengguna tidak ditemukan.';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO t_guru
                 (nip, nama, email, status_aktif, user_id)
                 VALUES (?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $pesan = 'Gagal menyiapkan query: '
                    . mysqli_error($koneksi);
            } else {
                mysqli_stmt_bind_param(
                    $stmt,
                    'sssii',
                    $nip,
                    $nama,
                    $email,
                    $status,
                    $user_id
                );

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header('Location: kelola_guru.php?pesan=sukses');
                    exit;
                }

                if (mysqli_stmt_errno($stmt) === 1062) {
                    $pesan = 'NIP, email, atau akun guru sudah digunakan.';
                } else {
                    $pesan = 'Gagal menyimpan data: '
                        . mysqli_stmt_error($stmt);
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Guru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>

<body class="bg-light">

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-3 col-lg-2 bg-dark min-vh-100 p-3">
            <h4 class="text-white mb-4">My Website</h4>

            <ul class="nav nav-pills flex-column">
                <li class="nav-item mb-2">
                    <a href="dashboard.php" class="nav-link text-white">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_siswa.php" class="nav-link text-white">
                        Data Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_guru.php" class="nav-link active">
                        Data Guru
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="Kelola_kelas.php" class="nav-link text-white">
                        Data Kelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_tahun_ajaran.php" class="nav-link text-white">
                        Kelola Tahun Ajaran
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_wali_kelas.php" class="nav-link text-white">
                        Kelola Walikelas
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="penempatan_siwa.php" class="nav-link text-white">
                        Penempatan Siswa
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="kelola_kategori_pelanggaran.php"
                       class="nav-link text-white">
                        Pelanggaran Kategori
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="tentang_saya.php" class="nav-link text-white">
                        About Me
                    </a>
                </li>

                <hr class="text-secondary">

                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- KONTEN -->
        <main class="col-md-9 col-lg-10 p-4">

            <div class="mb-4">
                <h2 class="fw-bold">Tambah Guru</h2>
                <p class="text-secondary">
                    Tambahkan data guru baru ke dalam sistem.
                </p>
            </div>

            <a href="kelola_guru.php"
               class="btn btn-outline-secondary btn-sm mb-3">
                &larr; Kembali ke Data Guru
            </a>

            <?php if ($pesan !== '') { ?>
                <div class="alert alert-<?= $tipe_pesan ?>">
                    <?= htmlspecialchars($pesan) ?>
                </div>
            <?php } ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Formulir Data Guru</h5>
                </div>

                <div class="card-body p-4">

                    <form method="POST" action="">

                        <div class="mb-3">
                            <label for="nip" class="form-label">
                                NIP <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nip" id="nip"
                                   class="form-control"
                                   maxlength="30"
                                   value="<?= htmlspecialchars($_POST['nip'] ?? '') ?>"
                                   placeholder="Masukkan NIP guru"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="nama" class="form-label">
                                Nama Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" id="nama"
                                   class="form-control"
                                   maxlength="150"
                                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                   placeholder="Masukkan nama lengkap guru"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" id="email"
                                   class="form-control"
                                   maxlength="150"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="contoh@email.com"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="user_id" class="form-label">
                                Akun Pengguna
                                <span class="text-danger">*</span>
                            </label>

                            <select name="user_id" id="user_id"
                                    class="form-select" required>
                                <option value="">-- Pilih akun pengguna --</option>

                                <?php while ($user = mysqli_fetch_assoc($users)) { ?>
                                    <option value="<?= (int)$user['id'] ?>"
                                        <?= (($_POST['user_id'] ?? '') == $user['id'])
                                            ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?>
                                        (<?= htmlspecialchars($user['email'] ?? '-') ?>)
                                        - <?= htmlspecialchars($user['role']) ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <small class="text-secondary">
                                Pilih akun yang memang terkait dengan guru tersebut.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label for="status_aktif" class="form-label">
                                Status Guru
                            </label>

                            <select name="status_aktif" id="status_aktif"
                                    class="form-select">
                                <option value="1"
                                    <?= (string)($_POST['status_aktif'] ?? '1') === '1'
                                        ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="0"
                                    <?= (string)($_POST['status_aktif'] ?? '') === '0'
                                        ? 'selected' : '' ?>>
                                    Tidak Aktif
                                </option>
                            </select>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="kelola_guru.php"
                               class="btn btn-secondary">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-primary">
                                Simpan Data Guru
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>