
<?php
session_start();

require_once 'config/koneksi.php';

// =====================================
// CEK METODE REQUEST
// =====================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// =====================================
// AMBIL INPUT LOGIN
// =====================================
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['pesan_error'] = "Email dan password wajib diisi!";
    header("Location: login.php");
    exit;
}

// =====================================
// AMBIL DATA PENGGUNA DAN STATUS WALI KELAS
// =====================================
$query = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.password,
        u.role,
        g.id AS guru_id,

        CASE
            WHEN u.role = 'guru' AND EXISTS (
                SELECT 1
                FROM t_wali_kelas wk
                INNER JOIN t_tahun_ajaran ta
                    ON ta.id = wk.tahun_ajaran_id
                WHERE wk.guru_id = g.id
                  AND wk.status_aktif = 1
                  AND ta.status_aktif = 1

                  AND (
                      wk.tanggal_mulai IS NULL
                      OR wk.tanggal_mulai = '0000-00-00'
                      OR wk.tanggal_mulai <= CURDATE()
                  )

                  AND (
                      wk.tanggal_selesai IS NULL
                      OR wk.tanggal_selesai = '0000-00-00'
                      OR wk.tanggal_selesai = '9999-12-31'
                      OR wk.tanggal_selesai >= CURDATE()
                  )
            )
            THEN 1
            ELSE 0
        END AS is_wali_kelas

    FROM t_users u

    LEFT JOIN t_guru g
        ON g.user_id = u.id

    WHERE u.email = ?

    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    die("Kesalahan query login: " . mysqli_error($koneksi));
}

mysqli_stmt_bind_param($stmt, "s", $email);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// =====================================
// CEK EMAIL DAN PASSWORD
// =====================================
if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['pesan_error'] = "Email atau password salah!";
    header("Location: login.php");
    exit;
}

// =====================================
// CEK ROLE PENGGUNA
// =====================================
$role = strtolower(trim($user['role']));

if (!in_array($role, ['admin', 'guru'], true)) {
    $_SESSION['pesan_error'] = "Role pengguna tidak valid!";
    header("Location: login.php");
    exit;
}

// =====================================
// PERBARUI SESSION
// =====================================
session_regenerate_id(true);

// =====================================
// SIMPAN DATA SESSION
// =====================================
$_SESSION['login'] = true;

$_SESSION['id'] = (int) $user['id'];
$_SESSION['user_id'] = (int) $user['id'];

$_SESSION['name'] = $user['name'];
$_SESSION['nama_user'] = $user['name'];
$_SESSION['nama'] = $user['name'];

$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $role;

$_SESSION['guru_id'] = $user['guru_id'] !== null
    ? (int) $user['guru_id']
    : null;

// =====================================
// STATUS WALI KELAS
// =====================================
$_SESSION['is_wali_kelas'] = (
    $role === 'guru'
    && (int) $user['is_wali_kelas'] === 1
);

// =====================================
// REDIRECT KE DASHBOARD
// =====================================
header("Location: dashboard.php");
exit;
