<?php
// about_me.php

include 'includes/cek_session.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Me | Kayila</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="asset/zulfan.css" rel="stylesheet">
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <?php include 'layout/sidebar.php'; ?>

        <!-- KONTEN KANAN -->
        <main class="col-md-9 col-lg-10 content">

            <div class="profile-card text-center">

                <div class="profile-icon">
                    K
                </div>

                <p class="profile-subtitle mb-2">
                    ABOUT ME
                </p>

                <h1 class="fw-bold">
                    Kayila Nur Fadilah
                </h1>

                <p class="text-secondary">
                    Student | XI RPL 1
                </p>

                <hr class="my-4">

                <h4 class="fw-bold text-start mb-3">
                    👋 Perkenalkan, Saya!
                </h4>

                <p class="text-secondary text-start" style="line-height: 1.9;">
                    Halo! Perkenalkan, nama saya
                    <b>Kayila Nur Fadilah</b>.
                    Saya merupakan siswa jurusan Rekayasa Perangkat
                    Lunak (RPL) yang tertarik mempelajari teknologi
                    dan pengembangan aplikasi berbasis web.

                    <br><br>

                    Aplikasi ini merupakan sistem pengelolaan pelanggaran
                    siswa yang dibuat untuk membantu proses pengelolaan
                    data kategori pelanggaran, jenis pelanggaran, dan
                    informasi terkait secara lebih terstruktur dan efisien.
                </p>

                <!-- INFORMASI PROFIL -->
                <div class="text-start mt-4">

                    <div class="info-box">
                        <strong>👤 Nama Lengkap</strong>
                        <div class="text-secondary">Kayila Nur Fadilah</div>
                    </div>

                    <div class="info-box">
                        <strong>🏫 Sekolah</strong>
                        <div class="text-secondary">SMK Muhammadiyah Kota Tasikmalaya</div>
                    </div>

                    <div class="info-box">
                        <strong>🎓 Kelas</strong>
                        <div class="text-secondary">XI RPL 1</div>
                    </div>

                    <div class="info-box">
                        <strong>💻 Jurusan</strong>
                        <div class="text-secondary">Rekayasa Perangkat Lunak</div>
                    </div>

                    <div class="info-box">
                        <strong>🛠 Keahlian</strong>
                        <div class="text-secondary">HTML, CSS, PHP, MySQL, Bootstrap</div>
                    </div>

                </div>

                <!-- TOMBOL KEMBALI -->
                <a href="dashboard.php" class="btn-custom mt-4">
                    ← Kembali ke Dashboard
                </a>

                <!-- FOOTER -->
                <p class="text-secondary small mt-4 mb-0">
                    &copy; <?= date('Y'); ?> Kayila Nur Fadilah
                </p>

            </div>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
