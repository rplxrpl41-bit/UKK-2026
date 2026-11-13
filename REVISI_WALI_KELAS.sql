-- MIGRASI REVISI UKK 2026
-- Jalankan satu kali melalui phpMyAdmin pada database db_ukk_2026.

START TRANSACTION;

-- Ubah role tiga akun dari guru menjadi wali kelas.
UPDATE t_users
SET role = 'walikelas', updated_at = NOW()
WHERE email IN ('ari@gmail.com', 'aldi@gmail.com', 'anshor@gmail.com');

-- Tambahkan kelas X dan XI RPL; kelas XII RPL sudah tersedia dengan ID 5.
INSERT INTO t_kelas (id, nama, tingkat, jurusan, status_aktif, created_at, updated_at)
SELECT 6, 'X RPL', 'X', 'RPL', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM t_kelas WHERE id = 6 OR (nama = 'X RPL' AND tingkat = 'X'));
INSERT INTO t_kelas (id, nama, tingkat, jurusan, status_aktif, created_at, updated_at)
SELECT 7, 'XI RPL', 'XI', 'RPL', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM t_kelas WHERE id = 7 OR (nama = 'XI RPL' AND tingkat = 'XI'));

UPDATE t_kelas SET nama = 'XII RPL', updated_at = NOW() WHERE id = 5 AND tingkat = 'XII';

-- Tetapkan tiga wali kelas pada tahun ajaran 2026/2027 (ID 25).
-- Ari: X RPL, Aldi: XI RPL, Ansor: XII RPL.
INSERT INTO t_wali_kelas
(tahun_ajaran_id, kelas_id, guru_id, tanggal_mulai, tanggal_selesai, status_aktif, created_at, updated_at)
SELECT 25, 6, 28, '2026-07-01', '2027-06-30', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM t_wali_kelas WHERE tahun_ajaran_id=25 AND kelas_id=6 AND guru_id=28);
INSERT INTO t_wali_kelas
(tahun_ajaran_id, kelas_id, guru_id, tanggal_mulai, tanggal_selesai, status_aktif, created_at, updated_at)
SELECT 25, 7, 32, '2026-07-01', '2027-06-30', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM t_wali_kelas WHERE tahun_ajaran_id=25 AND kelas_id=7 AND guru_id=32);
INSERT INTO t_wali_kelas
(tahun_ajaran_id, kelas_id, guru_id, tanggal_mulai, tanggal_selesai, status_aktif, created_at, updated_at)
SELECT 25, 5, 18, '2026-07-01', '2027-06-30', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM t_wali_kelas WHERE tahun_ajaran_id=25 AND kelas_id=5 AND guru_id=18);

-- Aktifkan tahun ajaran berjalan.
UPDATE t_tahun_ajaran SET status_aktif = 0 WHERE id <> 25;
UPDATE t_tahun_ajaran SET status_aktif = 1, updated_at = NOW() WHERE id = 25;

-- Sebarkan data siswa yang sudah tersedia ke tiga kelas wali kelas.
-- X RPL: siswa 1-8; XI RPL: siswa 9-16; XII RPL: siswa 17-21, 23, 24.
INSERT INTO t_kelas_siswa
(siswa_id, tahun_ajaran_id, kelas_id, tanggal_mulai, tanggal_selesai, status_aktif, created_at, updated_at)
SELECT s.id, 25,
       CASE WHEN s.id BETWEEN 1 AND 8 THEN 6
            WHEN s.id BETWEEN 9 AND 16 THEN 7
            ELSE 5 END,
       '2026-07-01', '2027-06-30', 1, NOW(), NOW()
FROM t_siswa s
WHERE (s.id BETWEEN 1 AND 21 OR s.id IN (23,24))
AND NOT EXISTS (
    SELECT 1 FROM t_kelas_siswa ks
    WHERE ks.siswa_id = s.id AND ks.tahun_ajaran_id = 25
);

COMMIT;
