<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Wajib login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header('Location: login.php');
    exit;
}

// Halaman yang boleh dibuka oleh role GURU.
// Admin boleh membuka semua halaman; guru hanya yang ada di daftar ini.
$halaman_guru = [
    'dashboard.php',
    'catatan_pelanggaran.php',
    'tindakan.php',
    'riwayat_pelanggaran.php',
    'laporan.php',
    'rekap_point.php',
    'tentang_saya.php',
];

if (($_SESSION['role'] ?? '') === 'guru') {
    $halaman_sekarang = basename($_SERVER['PHP_SELF']);

    if (!in_array($halaman_sekarang, $halaman_guru, true)) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Akses Ditolak</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container py-5 text-center">
                <h1 class="text-danger">Akses Ditolak</h1>
                <p class="text-muted">Akun guru tidak memiliki izin membuka halaman ini.</p>
                <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
