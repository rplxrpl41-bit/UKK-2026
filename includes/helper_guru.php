<?php
// =====================================================
// HELPER IDENTITAS GURU YANG SEDANG LOGIN
// =====================================================
// Dipakai oleh: catatan_pelanggaran, tindakan, riwayat_pelanggaran,
//               laporan, rekap_point.
//
// Aturan akses data:
//   - admin : melihat semua data pelanggaran.
//   - guru / wali kelas : hanya melihat data yang DIA SENDIRI
//     catat atau tindak. Wali kelas lain tidak terlihat.
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Normalisasi nama supaya "Ari Sandriana, S.Kom." sama dengan
 * "Ari Sandriana, S.Kom" (titik, koma, spasi diabaikan).
 */
function guru_normalisasi_nama($nama)
{
    return preg_replace('/[^a-z0-9]/', '', strtolower((string) $nama));
}

/**
 * Cari data guru (id, nama) milik akun yang sedang login.
 * Mengembalikan null jika akun bukan guru / belum terdaftar di t_guru.
 */
function guru_login(mysqli $koneksi)
{
    static $cache = [];

    $user_id = (int) ($_SESSION['user_id'] ?? 0);

    if ($user_id <= 0) {
        return null;
    }

    if (array_key_exists($user_id, $cache)) {
        return $cache[$user_id];
    }

    $hasil = null;

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id, nama FROM t_guru WHERE user_id = ? ORDER BY id ASC"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);

        $baris = [];
        $res = mysqli_stmt_get_result($stmt);

        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $baris[] = $r;
        }

        mysqli_stmt_close($stmt);

        if (count($baris) === 1) {
            $hasil = $baris[0];
        } elseif (count($baris) > 1) {
            // Beberapa guru memakai satu akun yang sama:
            // pilih yang namanya cocok, kalau tidak ada ambil yang pertama.
            $nama_session = guru_normalisasi_nama($_SESSION['name'] ?? '');
            $hasil = $baris[0];

            foreach ($baris as $r) {
                if (
                    $nama_session !== ''
                    && guru_normalisasi_nama($r['nama']) === $nama_session
                ) {
                    $hasil = $r;
                    break;
                }
            }
        }
    }

    if ($hasil) {
        $hasil['id'] = (int) $hasil['id'];
    }

    return $cache[$user_id] = $hasil;
}

/**
 * Pastikan kolom t_pelanggaran_siswa.ditindak_oleh ada.
 * Kolom ini menyimpan guru yang memberi tindakan, supaya riwayat
 * bisa menampilkan data "mencatat ATAU menindak".
 * Aman dipanggil berulang kali.
 */
function pastikan_kolom_penindak(mysqli $koneksi)
{
    static $siap = null;

    if ($siap !== null) {
        return $siap;
    }

    try {
        $cek = mysqli_query(
            $koneksi,
            "SHOW COLUMNS FROM t_pelanggaran_siswa LIKE 'ditindak_oleh'"
        );

        if ($cek && mysqli_num_rows($cek) > 0) {
            return $siap = true;
        }

        $ok = mysqli_query(
            $koneksi,
            "ALTER TABLE t_pelanggaran_siswa
             ADD COLUMN ditindak_oleh INT(11) NULL DEFAULT NULL AFTER guru_id"
        );

        return $siap = (bool) $ok;
    } catch (Throwable $ex) {
        return $siap = false;
    }
}

/**
 * Potongan WHERE untuk membatasi data per guru.
 *
 * Mengembalikan [sql, tipe, parameter]:
 *   - admin  => ['', '', []]               (tanpa batas)
 *   - guru   => ["(ps.guru_id = ? OR ps.ditindak_oleh = ?)", 'ii', [id, id]]
 *   - guru yang belum terdaftar di t_guru => tidak ada data sama sekali.
 *
 * $alias = alias tabel t_pelanggaran_siswa pada query (mis. 'ps').
 */
function batas_data_guru(mysqli $koneksi, $alias = '')
{
    if (($_SESSION['role'] ?? '') === 'admin') {
        return ['', '', []];
    }

    $awalan = $alias !== '' ? $alias . '.' : '';
    $guru = guru_login($koneksi);

    if (!$guru) {
        return ['1 = 0', '', []];
    }

    if (pastikan_kolom_penindak($koneksi)) {
        return [
            '(' . $awalan . 'guru_id = ? OR ' . $awalan . 'ditindak_oleh = ?)',
            'ii',
            [$guru['id'], $guru['id']],
        ];
    }

    return ['(' . $awalan . 'guru_id = ?)', 'i', [$guru['id']]];
}
