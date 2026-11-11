<?php
session_start();

include 'config/koneksi.php';

// ==========================================
// CEK AKSES ADMIN
// ==========================================
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    http_response_code(403);
    exit('Akses ditolak! Hanya admin yang dapat membuat akun guru.');
}

// ==========================================
// PENGATURAN AKUN
// ==========================================
$password_awal = 'walikelas123';
$password_hash = password_hash($password_awal, PASSWORD_DEFAULT);

// ==========================================
// DAFTAR 55 GURU
// ==========================================
$daftar_guru = [
    'Wawan Kurniawan, S.Pd.I',
    'Ai Nuryaidah, S.Pd',
    'Maya Permata Endah, S.Pd',
    'Dody Jaenudin, S.Pd',
    'Dedi Kardiman, S.Pd',
    'Uca Musa Yusenda, S.Pd',
    'Drs. Ahmad Nur Mukhlis',
    'Drs. Heri',
    'Asep Bastaman',
    'H. Encep Maemun, S.T., M.Pd',
    'Opik',
    'Ida Farida, S.Pd',
    'Idah Mahmudah, S.Pd',
    'Drs. Muhamad Ali',
    'Helmi Ali Akbar, S.Pd.I',
    'Syeni Suswarini, S.Pd',
    'Wiwid Yulianti, S.Pd',
    'Ansor Muhajir, S.T',
    'Ogi Gaosun Gias, S.Pd.I',
    'Sandi Mulyana, S.Pd',
    'Erna Suryani, S.Pd',
    'Ai Wahidah, S.Ag',
    'Chandra Aditya Yusup, S.Pd',
    'Arif Kusmawan, S.Pd',
    'Asep Taufiq Hidayat',
    'Rendy Ichsan Kusumah, S.T',
    'Feri Rahmawan, S.Pd',
    'Ari Sandriana, S.Kom',
    'Yaneu Nurdian, S.Pd, M.Pd',
    'Ucu Muhammad Khoer N, S.Pd',
    'Mira Dwi Rahmawati, S.Ag',
    'Aldi Maulana, S.Pd',
    'Eli Rosliah, S.Pd',
    'Isni Nur Fitriani, S.Pd',
    'Aanisah Putri Nur Aini, S.Pd',
    'Yusi Luthfiani, S.Sos.I',
    'Lilis Rustandy Mawardi, S.Pd., M.Pd',
    'Yusrin Noor Sabrina, S.Pd',
    'Senny Indayani, S.Pd',
    'Ilma Amalia, S.Pd',
    'Alvira Sofwatunnisa, S.Pd',
    'Risna Andriyani, S.Pd',
    'Dra. Tintin Noerhayati',
    'Rizkia Fahira, S.Pd',
    'Frasetio',
    'Ratnaningsih Hati Aeni, S.Pd',
    'Dheny Darajatul Hasani, S.Pd',
    'Fahrullah, S.Pd',
    'Aan Andriana, S.T',
    'Fitri Fauziah, S.T',
    'Nida Aulia, S.Pd',
    'Sri Nurrizki Putri Krisnadi, S.Pd',
    'Riesy Putri Navia, S.Kom',
    'Rurry Febriani Maryadi, S.Pd',
    'Irvan Rizal'
];

// ==========================================
// MEMBUAT NAMA DEPAN UNTUK EMAIL
// ==========================================
function namaDepanEmail($nama)
{
    $nama = trim($nama);

    // Hilangkan gelar depan
    $nama = preg_replace(
        '/^(Drs\.?|Dra\.?|H\.?)\s*/i',
        '',
        $nama
    );

    $bagian = preg_split('/\s+/', $nama);
    $depan = strtolower($bagian[0] ?? 'guru');

    $depan = preg_replace('/[^a-z0-9]/', '', $depan);

    return $depan ?: 'guru';
}

// ==========================================
// MEMBUAT EMAIL YANG BELUM DIGUNAKAN
// ==========================================
function cariEmail($koneksi, $nama, $user_id = 0)
{
    $dasar = namaDepanEmail($nama);
    $nomor = 1;

    while (true) {

        if ($nomor === 1) {
            $email = $dasar . '@gmail.com';
        } else {
            $email = $dasar . $nomor . '@gmail.com';
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_users
             WHERE email = ?
             AND id <> ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $email,
            $user_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $dipakai = mysqli_stmt_num_rows($stmt) > 0;

        mysqli_stmt_close($stmt);

        if (!$dipakai) {
            return $email;
        }

        $nomor++;
    }
}

// ==========================================
// MEMBUAT NIP OTOMATIS JIKA GURU BARU
// ==========================================
function buatNIP($koneksi, $nomor)
{
    $urutan = $nomor;

    while (true) {

        $nip = 'GURU' . str_pad($urutan, 6, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id FROM t_guru WHERE nip = ? LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $nip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $ada = mysqli_stmt_num_rows($stmt) > 0;

        mysqli_stmt_close($stmt);

        if (!$ada) {
            return $nip;
        }

        $urutan++;
    }
}

// ==========================================
// PROSES MEMBUAT AKUN
// ==========================================
$hasil = [];
$jumlah_baru = 0;
$jumlah_diperbarui = 0;

mysqli_begin_transaction($koneksi);

try {

    foreach ($daftar_guru as $index => $nama) {

        // ----------------------------------
        // CARI DATA GURU BERDASARKAN NAMA
        // ----------------------------------
        $cari_guru = mysqli_prepare(
            $koneksi,
            "SELECT id, user_id
             FROM t_guru
             WHERE nama = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($cari_guru, "s", $nama);
        mysqli_stmt_execute($cari_guru);

        mysqli_stmt_bind_result(
            $cari_guru,
            $guru_id_lama,
            $user_id_lama
        );

        $guru_sudah_ada = mysqli_stmt_fetch($cari_guru);

        mysqli_stmt_close($cari_guru);

        $guru_id_lama = (int)($guru_id_lama ?? 0);
        $user_id_lama = (int)($user_id_lama ?? 0);

        // ----------------------------------
        // PERIKSA AKUN YANG SUDAH TERHUBUNG
        // ----------------------------------
        $user_id = 0;
        $akun_valid = false;

        if ($guru_sudah_ada && $user_id_lama > 0) {

            $cek_user = mysqli_prepare(
                $koneksi,
                "SELECT id, role
                 FROM t_users
                 WHERE id = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $cek_user,
                "i",
                $user_id_lama
            );

            mysqli_stmt_execute($cek_user);

            mysqli_stmt_bind_result(
                $cek_user,
                $id_user,
                $role_user
            );

            if (mysqli_stmt_fetch($cek_user)) {
                if ($role_user === 'guru') {
                    $akun_valid = true;
                    $user_id = (int)$id_user;
                }
            }

            mysqli_stmt_close($cek_user);
        }

        // ----------------------------------
        // CARI EMAIL UNIK
        // ----------------------------------
        $email = cariEmail(
            $koneksi,
            $nama,
            $akun_valid ? $user_id : 0
        );

        // ----------------------------------
        // UPDATE AKUN LAMA ATAU BUAT AKUN
        // ----------------------------------
        if ($akun_valid) {

            $update_user = mysqli_prepare(
                $koneksi,
                "UPDATE t_users
                 SET name = ?,
                     email = ?,
                     password = ?,
                     role = 'guru',
                     updated_at = NOW()
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update_user,
                "sssi",
                $nama,
                $email,
                $password_hash,
                $user_id
            );

            mysqli_stmt_execute($update_user);
            mysqli_stmt_close($update_user);

            $status = 'Akun diperbarui';
            $jumlah_diperbarui++;

        } else {

            $insert_user = mysqli_prepare(
                $koneksi,
                "INSERT INTO t_users
                 (name, email, password, remember_token, role)
                 VALUES (?, ?, ?, '', 'guru')"
            );

            mysqli_stmt_bind_param(
                $insert_user,
                "sss",
                $nama,
                $email,
                $password_hash
            );

            mysqli_stmt_execute($insert_user);

            $user_id = mysqli_insert_id($koneksi);

            mysqli_stmt_close($insert_user);

            $status = 'Akun dibuat';
            $jumlah_baru++;
        }

        // ----------------------------------
        // HUBUNGKAN DENGAN TABEL T_GURU
        // ----------------------------------
        if ($guru_sudah_ada) {

            $update_guru = mysqli_prepare(
                $koneksi,
                "UPDATE t_guru
                 SET user_id = ?,
                     email = ?,
                     status_aktif = 1
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update_guru,
                "isi",
                $user_id,
                $email,
                $guru_id_lama
            );

            mysqli_stmt_execute($update_guru);
            mysqli_stmt_close($update_guru);

        } else {

            $nip = buatNIP($koneksi, $index + 1);

            $insert_guru = mysqli_prepare(
                $koneksi,
                "INSERT INTO t_guru
                 (nip, nama, email, status_aktif, user_id)
                 VALUES (?, ?, ?, 1, ?)"
            );

            mysqli_stmt_bind_param(
                $insert_guru,
                "sssi",
                $nip,
                $nama,
                $email,
                $user_id
            );

            mysqli_stmt_execute($insert_guru);
            mysqli_stmt_close($insert_guru);
        }

        $hasil[] = [
            'nama' => $nama,
            'email' => $email,
            'password' => $password_awal,
            'status' => $status
        ];
    }

    mysqli_commit($koneksi);

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    die(
        'Terjadi kesalahan: ' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}

function e($nilai)
{
    return htmlspecialchars(
        (string)$nilai,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Akun Login Guru - SIPES</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1100px;
            margin-top: 40px;
            margin-bottom: 40px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
        }

        .judul {
            color: #172554;
            font-weight: 800;
        }

        .password-box {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 15px;
            border-radius: 9px;
            font-weight: bold;
        }

        table th {
            white-space: nowrap;
        }

        table td {
            vertical-align: middle;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card p-4">

        <h3 class="judul">Pembuatan Akun Guru SIPES</h3>

        <p class="text-secondary">
            Daftar akun login guru yang berhasil diproses.
        </p>

        <div class="password-box mb-3">
            Password awal seluruh guru: <?= e($password_awal); ?>
        </div>

        <div class="alert alert-success">
            <strong>Berhasil!</strong><br>
            Total guru diproses: <?= count($hasil); ?><br>
            Akun baru dibuat: <?= $jumlah_baru; ?><br>
            Akun lama diperbarui: <?= $jumlah_diperbarui; ?>
        </div>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Nama Guru</th>
                        <th>Email Login</th>
                        <th>Password Awal</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($hasil as $i => $data): ?>

                        <tr>
                            <td><?= $i + 1; ?></td>

                            <td><?= e($data['nama']); ?></td>

                            <td><?= e($data['email']); ?></td>

                            <td><?= e($data['password']); ?></td>

                            <td>
                                <span class="badge bg-primary">
                                    guru
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-success">
                                    <?= e($data['status']); ?>
                                </span>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <a href="dashboard.php" class="btn btn-primary mt-3">
            Kembali ke Dashboard
        </a>

    </div>

</div>

</body>
</html>
