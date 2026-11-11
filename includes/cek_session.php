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

// Login terbaru menggunakan user_id.
// login=true tetap didukung untuk kompatibilitas
// dengan sistem login versi sebelumnya.

$sudah_login = (
    !empty($_SESSION['user_id'])
    || (
        isset($_SESSION['login'])
        && $_SESSION['login'] === true
    )
);

if (!$sudah_login) {
    header('Location: login.php');
    exit;
}

// =====================================
// AMBIL DATA PENGGUNA
// =====================================

$role = strtolower(trim($_SESSION['role'] ?? ''));

$email = strtolower(
    trim($_SESSION['email'] ?? '')
);

// =====================================
// CEK WALI KELAS BERDASARKAN EMAIL
// =====================================

$email_wali_kelas = [
    'ari@gmail.com',
    'aldi@gmail.com',
    'anshor@gmail.com'
];

$is_wali_kelas = (
    $role === 'guru'
    && (
        !empty($_SESSION['is_wali_kelas'])
        || in_array($email, $email_wali_kelas, true)
    )
);

// =====================================
// HALAMAN YANG BOLEH DIAKSES GURU
// =====================================

$halaman_guru = [

    'dashboard.php',

    'catatan_pelanggaran.php',

    'tindakan.php',

    'riwayat_pelanggaran.php',

    'rekap_poin.php',

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

    // Halaman umum Guru
    $boleh_akses = in_array(
        $halaman_sekarang,
        $halaman_guru,
        true
    );

    // Wali Kelas mendapatkan akses tambahan
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
    // JIKA AKSES TIDAK DIIZINKAN
    // =====================================

    if (!$boleh_akses) {

        http_response_code(403);

        exit(
            'Akses ditolak! Akun Anda tidak memiliki izin untuk membuka halaman ini.'
        );
    }
}

// =====================================
// ADMIN DAPAT MEMBUKA SEMUA HALAMAN
// =====================================

