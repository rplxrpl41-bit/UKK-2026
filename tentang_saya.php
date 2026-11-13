
<?php
include 'includes/cek_session.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Me | MUHAMMAD ZULFAN NURALFANSYAH</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        <?php include 'includes/crud_style.php'; ?>

        body {
            background: #f4f6f9;
            font-family: Arial, sans-serif;
            color: #1e293b;
            margin: 0;
        }

        /* KONTEN UTAMA */
        .content {
            padding: 28px;
            min-height: 100vh;
        }

        /* KARTU PROFIL */
        .profile-card {
            background: white;
            border-radius: 25px;
            padding: 40px;
            max-width: 750px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        /* IKON PROFIL */
        .profile-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            font-weight: bold;
            color: white;
            margin: 0 auto 20px;
        }

        /* SUBTITLE */
        .subtitle {
            color: #2563eb;
            font-weight: bold;
            letter-spacing: 3px;
            font-size: 13px;
        }

        /* INFORMASI PROFIL */
        .info-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }

        /* TOMBOL */
        .btn-custom {
            background: #2563eb;
            color: white;
            border-radius: 10px;
            padding: 12px 25px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-custom:hover {
            background: #1d4ed8;
            color: white;
        }

        /* RESPONSIVE */
        @media (max-width: 767px) {
            .content {
                padding: 18px;
            }

            .profile-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR KIRI -->
        <div class="col-md-3 col-lg-2 p-0">
            <?php
            $menu_aktif = 'About Me';
            include 'includes/crud_sidebar.php';
            ?>
        </div>

        <!-- KONTEN KANAN -->
        <main class="col-md-9 col-lg-10 content">

            <div class="profile-card text-center">

                <div class="profile-icon">
                    Z
                </div>

                <p class="subtitle mb-2">
                    ABOUT ME
                </p>

                <h1 class="fw-bold">
                    Muhammad Zulfan Nuralfansyah
                </h1>

                <p class="text-secondary">
                    Student | XII RPL 1
                </p>

                <hr class="my-4">

                <h4 class="fw-bold text-start mb-3">
                    👋 Perkenalkan, Saya!
                </h4>

                <p
                    class="text-secondary text-start"
                    style="line-height: 1.9;"
                >
                    Halo semuanya! Perkenalkan, nama saya
                    <b>Muhammad Zulfan Nuralfansyah</b>,
                    siswa kelas XII RPL 1. Saya merupakan siswa
                    Rekayasa Perangkat Lunak (RPL) yang tertarik
                    dengan dunia teknologi, komputer, dan pembuatan
                    website.

                    <br><br>

                    Melalui pembelajaran di sekolah, saya terus
                    berusaha mengembangkan kemampuan dalam bidang
                    pemrograman, desain web, serta pengembangan
                    aplikasi. Saya percaya bahwa kerja keras,
                    kreativitas, dan kemauan untuk terus belajar
                    merupakan kunci untuk meraih masa depan yang
                    lebih baik.
                </p>

                <!-- INFORMASI PROFIL -->
                <div class="text-start mt-4">

                    <div class="info-box">
                        <strong>
                            👤 Nama Lengkap
                        </strong>

                        <div class="text-secondary">
                            Muhammad Zulfan Nuralfansyah
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>
                            🎓 Kelas
                        </strong>

                        <div class="text-secondary">
                            XII RPL 1
                        </div>
                    </div>

                    <div class="info-box">
                        <strong>
                            💻 Bidang yang Dipelajari
                        </strong>

                        <div class="text-secondary">
                            Pemrograman, Web Development, dan Teknologi
                        </div>
                    </div>

                </div>

                <!-- MOTIVASI -->
                <p class="fst-italic text-secondary mt-4">
                    "Terus belajar, berani mencoba, dan jangan pernah
                    berhenti berkembang."
                </p>

                <!-- TOMBOL KEMBALI -->
                <a
                    href="dashboard.php"
                    class="btn-custom mt-2"
                >
                    ← Kembali ke Dashboard
                </a>

                <!-- FOOTER -->
                <p class="text-secondary small mt-4 mb-0">
                    &copy; 2026 Muhammad Zulfan Nuralfansyah
                </p>

            </div>

        </main>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
