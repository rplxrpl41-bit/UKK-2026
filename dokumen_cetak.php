<?php
// =====================================================
// KOMPONEN DOKUMEN CETAK STANDAR SEKOLAH
// (kop surat, judul, blok tanda tangan, gaya cetak A4)
// =====================================================

function dk_e($nilai)
{
    return htmlspecialchars((string) ($nilai ?? ''), ENT_QUOTES, 'UTF-8');
}

function dk_sekolah()
{
    static $data = null;

    if ($data === null) {
        $data = require __DIR__ . '/identitas_sekolah.php';
    }

    return $data;
}

/** 2026-11-13 => 13 November 2026 */
function dk_tanggal($tanggal)
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $waktu = strtotime((string) $tanggal);

    if (!$waktu) {
        return '-';
    }

    return date('j', $waktu) . ' ' . $bulan[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
}

/**
 * Tahun ajaran untuk judul dokumen, mis. 2026/2027.
 * Diambil dari tahun ajaran yang mencakup tanggal hari ini; jika tidak
 * ada, dihitung dari tanggal (Juli = awal tahun ajaran baru).
 */
function dk_tahun_ajaran(mysqli $koneksi)
{
    $q = mysqli_query(
        $koneksi,
        "SELECT nama FROM t_tahun_ajaran
         WHERE CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
         ORDER BY status_aktif DESC, id DESC LIMIT 1"
    );

    if ($q && ($r = mysqli_fetch_assoc($q)) && !empty($r['nama'])) {
        return $r['nama'];
    }

    $tahun = (int) date('Y');
    $mulai = ((int) date('n') >= 7) ? $tahun : $tahun - 1;

    return $mulai . '/' . ($mulai + 1);
}

/** Kop surat resmi: logo + instansi + nama sekolah + alamat + garis ganda */
function dk_kop()
{
    $s = dk_sekolah();
    ?>
    <div class="kop">
        <div class="kop-logo">
            <?php if (!empty($s['logo'])): ?>
                <img src="<?= dk_e($s['logo']) ?>" alt="Logo">
            <?php endif; ?>
        </div>

        <div class="kop-teks">
            <div class="kop-instansi"><?= dk_e($s['instansi_1']) ?></div>
            <div class="kop-instansi"><?= dk_e($s['instansi_2']) ?></div>
            <div class="kop-sekolah"><?= dk_e($s['nama_sekolah']) ?></div>
            <div class="kop-alamat"><?= dk_e($s['alamat']) ?></div>
            <div class="kop-alamat"><?= dk_e($s['kontak']) ?></div>
        </div>

        <div class="kop-logo"></div>
    </div>
    <div class="kop-garis"></div>
    <?php
}

/** Judul dokumen (tengah, huruf kapital, bergaris bawah) */
function dk_judul($judul, $subjudul = '')
{
    ?>
    <div class="dok-judul"><?= dk_e($judul) ?></div>
    <?php if ($subjudul !== ''): ?>
        <div class="dok-subjudul"><?= dk_e($subjudul) ?></div>
    <?php endif;
}

/**
 * Blok tanda tangan: kiri = Kepala Sekolah, kanan = pembuat dokumen.
 */
function dk_tanda_tangan($jabatan_kanan, $nama_kanan, $keterangan_kanan = '')
{
    $s = dk_sekolah();
    ?>
    <table class="ttd">
        <tr>
            <td class="ttd-kiri">
                <br>
                Mengetahui,<br>
                Kepala Sekolah
                <div class="ttd-ruang"></div>
                <strong><u><?= dk_e($s['kepala_sekolah']) ?></u></strong><br>
                <?= dk_e($s['nip_kepsek']) ?>
            </td>

            <td class="ttd-kanan">
                <?= dk_e($s['kota']) ?>, <?= dk_e(dk_tanggal(date('Y-m-d'))) ?><br>
                <?= dk_e($jabatan_kanan) ?>
                <div class="ttd-ruang"></div>
                <strong><u><?= dk_e($nama_kanan) ?></u></strong><br>
                <?= dk_e($keterangan_kanan) ?>
            </td>
        </tr>
    </table>
    <?php
}

/** CSS tampilan layar (pratinjau kertas) dan cetak (A4) */
function dk_css($orientasi = 'portrait')
{
    $lebar_layar = ($orientasi === 'landscape') ? '297mm' : '210mm';
    ?>
    <style>
        @page {
            size: A4 <?= $orientasi === 'landscape' ? 'landscape' : 'portrait' ?>;
            margin: 15mm 15mm 18mm 15mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
        }

        /* Toolbar layar (tidak ikut tercetak) */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #172554;
            color: #fff;
            padding: 10px 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .toolbar form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin: 0;
        }

        .toolbar select,
        .toolbar input[type=date] {
            padding: 5px 8px;
            border-radius: 5px;
            border: 0;
            font-size: 13px;
        }

        .toolbar a,
        .toolbar button {
            background: #3157d5;
            color: #fff;
            border: 0;
            padding: 7px 14px;
            border-radius: 5px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }

        .toolbar a.abu { background: #64748b; }
        .toolbar .spasi { flex: 1; }

        /* Lembar kertas */
        .kertas {
            width: <?= $lebar_layar ?>;
            max-width: 100%;
            margin: 18px auto;
            background: #fff;
            padding: 15mm;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .25);
        }

        /* Kop surat */
        .kop {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .kop-logo { width: 22mm; flex: none; }
        .kop-logo img { width: 22mm; height: auto; }
        .kop-teks { flex: 1; text-align: center; line-height: 1.25; }
        .kop-instansi { font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .kop-sekolah { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .kop-alamat { font-size: 10pt; }

        /* Garis ganda di bawah kop (tebal + tipis) */
        .kop-garis {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 5px;
            margin: 6px 0 14px;
        }

        /* Judul dokumen */
        .dok-judul {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .dok-subjudul {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 12px;
        }

        /* Identitas dokumen */
        table.identitas {
            font-size: 11pt;
            margin: 10px 0 10px;
        }

        table.identitas td { padding: 1px 4px 1px 0; vertical-align: top; }
        table.identitas td:first-child { width: 38mm; }
        table.identitas td:nth-child(2) { width: 4mm; }

        /* Tabel data */
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        table.data th,
        table.data td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
        }

        table.data th {
            background: #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }

        .tengah { text-align: center; }
        .kanan  { text-align: right; }

        table.data tfoot td {
            font-weight: bold;
            background: #f3f4f6;
        }

        .catatan { font-size: 10pt; margin-top: 8px; }

        /* Tanda tangan */
        table.ttd {
            width: 100%;
            margin-top: 22px;
            font-size: 11pt;
            page-break-inside: avoid;
        }

        table.ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd-ruang { height: 22mm; }

        .kosong {
            text-align: center;
            padding: 16px;
            font-style: italic;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }

            .kertas {
                width: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
    <?php
}
