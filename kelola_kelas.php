<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   TAMBAH KELAS
========================= */
if (isset($_POST['tambah'])) {

    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $tingkat = mysqli_real_escape_string($koneksi, $_POST['tingkat']);
    $jurusan = mysqli_real_escape_string($koneksi, $_POST['jurusan']);

    mysqli_query($koneksi, "
        INSERT INTO t_kelas
        (nama, tingkat, jurusan, status_aktif)
        VALUES
        ('$nama', '$tingkat', '$jurusan', 1)
    ");

    header("Location: kelola_kelas.php");
    exit;
}


/* =========================
   HAPUS
========================= */
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    mysqli_query(
        $koneksi,
        "DELETE FROM t_kelas WHERE id = $id"
    );

    header("Location: kelola_kelas.php");
    exit;
}


/* =========================
   DATA EDIT
========================= */
$edit = null;

if (isset($_GET['edit'])) {

    $id = (int) $_GET['edit'];

    $hasil = mysqli_query(
        $koneksi,
        "SELECT * FROM t_kelas WHERE id = $id"
    );

    if ($hasil && mysqli_num_rows($hasil) > 0) {
        $edit = mysqli_fetch_assoc($hasil);
    }
}


/* =========================
   UPDATE
========================= */
if (isset($_POST['update'])) {

    $id = (int) $_POST['id'];

    $nama = mysqli_real_escape_string(
        $koneksi,
        $_POST['nama']
    );

    $tingkat = mysqli_real_escape_string(
        $koneksi,
        $_POST['tingkat']
    );

    $jurusan = mysqli_real_escape_string(
        $koneksi,
        $_POST['jurusan']
    );

    $status = (int) $_POST['status_aktif'];

    mysqli_query($koneksi, "
        UPDATE t_kelas SET
            nama = '$nama',
            tingkat = '$tingkat',
            jurusan = '$jurusan',
            status_aktif = $status
        WHERE id = $id
    ");

    header("Location: kelola_kelas.php");
    exit;
}


/* =========================
   AMBIL DATA
========================= */
$data = mysqli_query(
    $koneksi,
    "
    SELECT
        id,
        nama,
        tingkat,
        jurusan,
        status_aktif
    FROM t_kelas
    ORDER BY id DESC
    "
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Kelola Kelas</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f5f7fb;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .judul {
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
        }

    </style>

</head>


<body>


<div class="container py-4">


    <!-- JUDUL -->

    <div class="mb-4">

        <h2 class="judul">
            Kelola Kelas
        </h2>

        <p class="text-muted">
            Pengelolaan data kelas.
        </p>

        <a
            href="dashboard.php"
            class="btn btn-secondary"
        >
            Kembali ke Dashboard
        </a>

    </div>



    <!-- FORM TAMBAH / EDIT -->

    <div class="card mb-4">

        <div class="card-body">

            <?php if ($edit) { ?>

                <h5 class="mb-3">
                    Ubah Data Kelas
                </h5>

            <?php } else { ?>

                <h5 class="mb-3">
                    Tambah Data Kelas
                </h5>

            <?php } ?>


            <form method="POST">


                <?php if ($edit) { ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo $edit['id']; ?>"
                    >

                <?php } ?>


                <div class="row">


                    <!-- NAMA -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Nama Kelas
                        </label>

                        <input
                            type="text"
                            name="nama"
                            class="form-control"
                            placeholder="Contoh: X RPL 1"
                            required
                            value="<?php

                            if ($edit) {
                                echo htmlspecialchars(
                                    $edit['nama']
                                );
                            }

                            ?>"
                        >

                    </div>


                    <!-- TINGKAT -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Tingkat
                        </label>

                        <select
                            name="tingkat"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Tingkat --
                            </option>

                            <option
                                value="X"
                                <?php
                                if (
                                    $edit &&
                                    $edit['tingkat'] == 'X'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                X
                            </option>

                            <option
                                value="XI"
                                <?php
                                if (
                                    $edit &&
                                    $edit['tingkat'] == 'XI'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                XI
                            </option>

                            <option
                                value="XII"
                                <?php
                                if (
                                    $edit &&
                                    $edit['tingkat'] == 'XII'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                XII
                            </option>

                        </select>

                    </div>


                    <!-- JURUSAN -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Jurusan
                        </label>

                        <select
                            name="jurusan"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Jurusan --
                            </option>

                            <option
                                value="RPL"
                                <?php
                                if (
                                    $edit &&
                                    $edit['jurusan'] == 'RPL'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                RPL
                            </option>

                            <option
                                value="TKJ"
                                <?php
                                if (
                                    $edit &&
                                    $edit['jurusan'] == 'TKJ'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                TKJ
                            </option>

                            <option
                                value="TSM"
                                <?php
                                if (
                                    $edit &&
                                    $edit['jurusan'] == 'TSM'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                TSM
                            </option>

                            <option
                                value="BDPM"
                                <?php
                                if (
                                    $edit &&
                                    $edit['jurusan'] == 'BDPM'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                BDPM
                            </option>

                            <option
                                value="TKR"
                                <?php
                                if (
                                    $edit &&
                                    $edit['jurusan'] == 'TKR'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                TKR
                            </option>

                        </select>

                    </div>


                </div>


                <?php if ($edit) { ?>

                    <!-- STATUS -->

                    <div class="mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status_aktif"
                            class="form-select"
                        >

                            <option
                                value="1"
                                <?php
                                if ($edit['status_aktif'] == 1) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                Aktif
                            </option>

                            <option
                                value="0"
                                <?php
                                if ($edit['status_aktif'] == 0) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                Tidak Aktif
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        name="update"
                        class="btn btn-warning"
                    >
                        Simpan Perubahan
                    </button>

                    <a
                        href="kelola_kelas.php"
                        class="btn btn-secondary"
                    >
                        Batal
                    </a>


                <?php } else { ?>


                    <button
                        type="submit"
                        name="tambah"
                        class="btn btn-primary"
                    >
                        + Tambah Kelas
                    </button>


                <?php } ?>


            </form>

        </div>

    </div>



    <!-- TABEL DATA -->

    <div class="card">

        <div class="card-body">

            <h5 class="mb-3">
                Data Kelas
            </h5>


            <div class="table-responsive">

                <table
                    class="table table-bordered table-hover align-middle"
                >

                    <thead class="table-dark">

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                ID
                            </th>

                            <th>
                                Nama
                            </th>

                            <th>
                                Tingkat
                            </th>

                            <th>
                                Jurusan
                            </th>

                            <th>
                                Status Aktif
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $no = 1;

                    if (
                        $data &&
                        mysqli_num_rows($data) > 0
                    ) {

                        while (
                            $row = mysqli_fetch_assoc($data)
                        ) {

                    ?>

                        <tr>

                            <!-- NO -->

                            <td>
                                <?php echo $no++; ?>
                            </td>


                            <!-- ID -->

                            <td>
                                <?php echo $row['id']; ?>
                            </td>


                            <!-- NAMA -->

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['nama']
                                );
                                ?>
                            </td>


                            <!-- TINGKAT -->

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['tingkat']
                                );
                                ?>
                            </td>


                            <!-- JURUSAN -->

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['jurusan']
                                );
                                ?>
                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if (
                                    $row['status_aktif'] == 1
                                ) { ?>

                                    <span
                                        class="badge bg-success"
                                    >
                                        Aktif
                                    </span>

                                <?php } else { ?>

                                    <span
                                        class="badge bg-danger"
                                    >
                                        Tidak Aktif
                                    </span>

                                <?php } ?>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <a
                                    href="kelola_kelas.php?edit=<?php echo $row['id']; ?>"
                                    class="btn btn-warning btn-sm"
                                >
                                    Ubah
                                </a>


                                <a
                                    href="kelola_kelas.php?hapus=<?php echo $row['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Yakin ingin menghapus kelas ini?')"
                                >
                                    Hapus
                                </a>

                            </td>

                        </tr>


                    <?php

                        }

                    } else {

                    ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center"
                            >
                                Belum ada data kelas.

                            </td>

                        </tr>

                    <?php

                    }

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


</body>
</html>