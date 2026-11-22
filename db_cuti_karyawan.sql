-- Database: db_cuti_karyawan
-- CATATAN: file ini menghapus tabel lama (users & pengajuan_cuti) lalu membuatnya ulang.
-- Jalankan hanya jika data lama boleh hilang.
CREATE DATABASE IF NOT EXISTS `db_cuti_karyawan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_cuti_karyawan`;

DROP TABLE IF EXISTS `pengajuan_cuti`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- Struktur tabel `users`
-- Kolom password menyimpan HASH bcrypt (60 karakter), bukan teks asli.
-- --------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
    `sisa_cuti` INT NOT NULL DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Struktur tabel `pengajuan_cuti`
-- Kolom bukti_surat menyimpan nama file surat dokter yang diunggah (boleh kosong).
-- --------------------------------------------------------
CREATE TABLE `pengajuan_cuti` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `jenis_cuti` VARCHAR(50) NOT NULL,
    `tanggal_mulai` DATE NOT NULL,
    `tanggal_selesai` DATE NOT NULL,
    `alasan` TEXT NOT NULL,
    `bukti_surat` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Pending',
    CONSTRAINT `fk_user_cuti` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Data bawaan tabel `users` (password sudah di-hash)
--   admin / admin123   (role: admin)
--   budi  / budi123    (role: karyawan)
-- --------------------------------------------------------
INSERT INTO `users` (`nama_lengkap`, `username`, `password`, `role`, `sisa_cuti`) VALUES
('Admin HRD', 'admin', '$2y$10$UkQ2qgOUMdWik9XVchmp6.WdvNOQSYq6sogOp/sWSwoisvqwSJ.yW', 'admin', 12),
('Budi Santoso', 'budi', '$2y$10$iKfiLCK32iT3tCmCgW3rEuwyHVuR9QRBOsTHDccFh6sJxvlupmPM.', 'karyawan', 12);
