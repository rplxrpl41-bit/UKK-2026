
<?php

// =====================================
// MULAI SESSION
// =====================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================
// CEK LOGIN
// =====================================
if (
    !isset($_SESSION['login'])
    || $_SESSION['login'] !== true
) {
    header('Location: login.php');
    exit;
}

// =====================================
// AMBIL DATA ROLE
// =====================================
$role = $_SESSION['role'] ?? '';

$is_wali_kelas = (
    $role === 'guru'
    && !empty($_SESSION['is_wali_kelas'])
);

// =====================================
// HALAMAN YANG BOLEH DIAKSES GURU
// =====================================
$halaman_guru = [

    'dashboard.php',

    'catatan_pelanggaran.php',

    'tindakan.php',

    'riwayat_pelanggaran.php',

    'rekap_point.php',

    'tentang_saya.php',
];

// =====================================
// HALAMAN KHUSUS WALI KELAS
// =====================================
$halaman_wali_kelas = [

    'laporan.php',
];

// =====================================
// CEK HAK AKSES GURU
// =====================================
if ($role === 'guru') {

    $halaman_sekarang = basename($_SERVER['PHP_SELF']);

    // Default: halaman umum Guru
    $boleh_akses = in_array(
        $halaman_sekarang,
        $halaman_guru,
        true
    );

    // Tambahan akses khusus Wali Kelas
    if (
        $is_wali_kelas
        && in_array(
            $halaman_sekarang,
            $halaman_wali_kelas,
            true
        )
    ) {
        $boleh_akses = true;
    }

    // =====================================
    // TOLAK AKSES JIKA TIDAK DIIZINKAN
    // =====================================
    if (!$boleh_akses) {

        http_response_code(403);

        ?>
        <!DOCTYPE html>
        <html lang="id">

        <head>

            <meta charset="UTF-8">

            <meta name="viewport" content="width=device-width, initial-scale=1">

            <title>Akses Ditolak</title>

            <link
                href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
                rel="stylesheet"
            >

        </head>

        <body class="bg-light">

            <div class="container py-5">

                <div class="row justify-content-center">

                    <div class="col-md-7">

                        <div class="card shadow-sm border-0">

                            <div class="card-body text-center p-5">

                                <h1 class="text-danger mb-3">
                                    403
                                </h1>

                                <h4 class="mb-3">
                                    Akses Ditolak!
                                </h4>

                                <p class="text-muted">
                                    Maaf, akun Anda tidak memiliki
                                    izin untuk membuka halaman ini.
                                </p>

                                <a
                                    href="dashboard.php"
                                    class="btn btn-primary"
                                >
                                    Kembali ke Dashboard
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </body>

        </html>
        <?php

        exit;
    }
}

// =====================================
// ADMIN DAPAT MEMBUKA SEMUA HALAMAN
// =====================================

