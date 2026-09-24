-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2026 at 05:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parkir`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_area_parkir`
--

CREATE TABLE `tb_area_parkir` (
  `id_area` int(11) NOT NULL,
  `nama_area` varchar(50) NOT NULL,
  `kapasitas` int(5) NOT NULL,
  `terisi` int(5) DEFAULT 0,
  `rating` decimal(2,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_area_parkir`
--

INSERT INTO `tb_area_parkir` (`id_area`, `nama_area`, `kapasitas`, `terisi`, `rating`) VALUES
(1, 'Area A', 3, 2, 5.0),
(2, 'Area B', 30, 2, 5.0),
(3, 'Area C', 2, 1, 0.0);

-- --------------------------------------------------------

--
-- Table structure for table `tb_area_parkir_karyawan`
--

CREATE TABLE `tb_area_parkir_karyawan` (
  `id_area_karyawan` int(11) NOT NULL,
  `nama_area` varchar(100) NOT NULL,
  `kapasitas` int(5) NOT NULL DEFAULT 0,
  `terisi` int(5) NOT NULL DEFAULT 0,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `status` enum('tersedia','penuh','nonaktif') NOT NULL DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_area_parkir_karyawan`
--

INSERT INTO `tb_area_parkir_karyawan` (`id_area_karyawan`, `nama_area`, `kapasitas`, `terisi`, `rating`, `status`) VALUES
(1, 'Parkir Karyawan Gedung Utama', 50, 0, 4.5, 'tersedia'),
(2, 'Parkir Karyawan IGD', 30, 0, 4.3, 'tersedia'),
(3, 'Parkir Karyawan Poliklinik', 40, 0, 4.4, 'tersedia'),
(4, 'Parkir Karyawan Belakang', 35, 0, 4.2, 'tersedia'),
(5, 'Parkir Karyawan Zona Timur', 25, 0, 4.1, 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `tb_booking`
--

CREATE TABLE `tb_booking` (
  `id_booking` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_kendaraan` int(11) NOT NULL,
  `id_area` int(11) DEFAULT NULL,
  `id_area_karyawan` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time NOT NULL,
  `estimasi_jam` int(11) NOT NULL,
  `status` enum('booking','aktif','selesai','batal') DEFAULT 'booking',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_booking`
--

INSERT INTO `tb_booking` (`id_booking`, `id_user`, `id_kendaraan`, `id_area`, `id_area_karyawan`, `tanggal`, `jam_masuk`, `estimasi_jam`, `status`, `created_at`) VALUES
(1, 5, 3, 1, NULL, '2026-08-10', '12:15:22', 1, 'aktif', '2026-08-05 02:09:29'),
(2, 5, 3, 1, NULL, '2026-08-10', '12:22:52', 20, 'aktif', '2026-08-10 03:42:19'),
(3, 5, 3, 2, NULL, '2026-08-11', '11:46:00', 1, 'selesai', '2026-08-11 04:47:53'),
(4, 5, 3, 3, NULL, '2026-08-18', '14:10:00', 2, 'selesai', '2026-08-18 07:10:33'),
(5, 5, 3, 1, NULL, '2026-09-06', '18:34:32', 2, 'aktif', '2026-09-06 11:33:02'),
(6, 5, 3, 2, NULL, '2026-09-06', '18:49:54', 24, 'selesai', '2026-09-06 11:49:38'),
(8, 9, 4, NULL, 4, '2026-09-08', '09:38:03', 2, 'selesai', '2026-09-07 06:59:03'),
(9, 5, 3, 3, NULL, '2026-09-07', '13:59:00', 2, 'batal', '2026-09-07 06:59:56'),
(10, 5, 3, 1, NULL, '2026-09-16', '08:26:00', 24, 'batal', '2026-09-16 01:26:59'),
(11, 5, 7, 1, NULL, '2026-09-16', '09:01:16', 2, 'selesai', '2026-09-16 02:01:07'),
(12, 5, 8, 2, NULL, '2026-09-16', '09:35:08', 3, 'selesai', '2026-09-16 02:34:52'),
(13, 11, 9, 1, NULL, '2026-09-16', '14:14:05', 1, 'selesai', '2026-09-16 07:13:49'),
(14, 5, 8, 1, NULL, '2026-09-18', '21:13:00', 2, 'selesai', '2026-09-18 14:13:06'),
(15, 5, 8, 1, NULL, '2026-09-19', '09:18:00', 1, 'selesai', '2026-09-19 02:19:07'),
(16, 5, 8, 2, NULL, '2026-09-19', '18:47:00', 4, 'batal', '2026-09-19 11:47:48'),
(17, 5, 8, 2, NULL, '2026-09-21', '16:05:00', 24, 'selesai', '2026-09-21 05:02:14'),
(18, 5, 8, 2, NULL, '2026-09-21', '20:11:00', 2, 'aktif', '2026-09-21 12:12:02'),
(19, 12, 13, 3, NULL, '2026-09-21', '19:18:00', 1, 'batal', '2026-09-21 12:16:51'),
(20, 5, 8, 1, NULL, '2026-09-21', '19:34:00', 2, 'batal', '2026-09-21 12:34:25'),
(21, 5, 8, 1, NULL, '2026-09-22', '14:09:00', 1, 'aktif', '2026-09-22 07:09:18'),
(22, 5, 8, 2, NULL, '2026-09-22', '19:24:00', 2, 'booking', '2026-09-22 12:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kendaraan`
--

CREATE TABLE `tb_kendaraan` (
  `id_kendaraan` int(11) NOT NULL,
  `plat_nomor` varchar(15) NOT NULL,
  `jenis_kendaraan` varchar(20) NOT NULL,
  `merk` varchar(50) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `pemilik` varchar(100) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `is_dihapus` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kendaraan`
--

INSERT INTO `tb_kendaraan` (`id_kendaraan`, `plat_nomor`, `jenis_kendaraan`, `merk`, `warna`, `pemilik`, `id_user`, `is_dihapus`) VALUES
(3, 'AB 1213', 'Mobil', 'supra', 'Hitam', 'Rafael', 5, 1),
(4, 'RAFA2008', 'Mobil', NULL, 'Putih', 'Faizal', 9, 0),
(7, 'RAFA200828DR6TH', 'Mobil', NULL, 'Putih', 'Faizal', 5, 1),
(8, 'AB 12132', 'Mobil', NULL, 'Hitam', 'rafa', 5, 0),
(9, 'AB 12132222', 'Mobil', NULL, 'Hitam', 'isal', 11, 0),
(10, '121212121212', 'mobil', NULL, 'hitam', 'isal', 7, 0),
(11, 'AB 1213', 'motor', NULL, 'Hitam', 'rafa', NULL, 0),
(12, '121212121212', 'mobil', NULL, 'hitam', 'Rafa2', NULL, 0),
(13, 'RAFA200822', 'Mobil', NULL, 'Hitam', 'rafa', 12, 0),
(14, '1212121212122', 'mobil', NULL, 'hitam', 'bu salsa', NULL, 0),
(15, '121212121212111', 'mobil', NULL, 'putih', 'Rafa3', NULL, 0),
(16, '121212121212111', 'mobil', NULL, 'putih', 'Rafa22', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tb_log`
--

CREATE TABLE `tb_log` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `waktu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_log`
--

INSERT INTO `tb_log` (`id_log`, `id_user`, `aktivitas`, `waktu`) VALUES
(1, 2, 'Login sebagai petugas', '2026-09-05 20:38:10');

-- --------------------------------------------------------

--
-- Table structure for table `tb_log_aktivitas`
--

CREATE TABLE `tb_log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `aktivitas` varchar(100) NOT NULL,
  `waktu_aktivitas` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_tarif`
--

CREATE TABLE `tb_tarif` (
  `id_tarif` int(11) NOT NULL,
  `jenis_kendaraan` enum('motor','mobil','lainnya') NOT NULL,
  `tarif_per_jam` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_tarif`
--

INSERT INTO `tb_tarif` (`id_tarif`, `jenis_kendaraan`, `tarif_per_jam`) VALUES
(2, 'mobil', 5000),
(3, 'motor', 5000),
(5, 'lainnya', 15000);

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_parkir` int(11) NOT NULL,
  `id_kendaraan` int(11) NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `id_tarif` int(11) NOT NULL,
  `durasi_jam` int(11) DEFAULT 0,
  `biaya_total` decimal(10,0) DEFAULT 0,
  `metode_pembayaran` enum('cash','qris') DEFAULT NULL,
  `status` enum('masuk','keluar') DEFAULT 'masuk',
  `id_user` int(11) NOT NULL,
  `id_area` int(11) DEFAULT NULL,
  `id_area_karyawan` int(11) DEFAULT NULL,
  `id_booking` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_parkir`, `id_kendaraan`, `waktu_masuk`, `waktu_keluar`, `id_tarif`, `durasi_jam`, `biaya_total`, `metode_pembayaran`, `status`, `id_user`, `id_area`, `id_area_karyawan`, `id_booking`) VALUES
(3, 3, '2026-08-18 13:44:08', '2026-08-18 13:44:12', 2, 1, 5000, NULL, 'keluar', 5, 2, NULL, 3),
(4, 3, '2026-08-18 14:11:19', '2026-08-18 14:11:21', 2, 1, 5000, NULL, 'keluar', 5, 3, NULL, 4),
(5, 3, '2026-09-06 18:49:54', '2026-09-12 17:00:51', 2, 143, 715000, NULL, 'keluar', 5, 2, NULL, 6),
(6, 4, '2026-09-08 09:38:03', '2026-09-12 17:00:46', 2, 104, 520000, NULL, 'keluar', 9, NULL, 4, 8),
(7, 7, '2026-09-16 09:01:16', '2026-09-16 09:03:02', 2, 1, 5000, NULL, 'keluar', 5, 1, NULL, 11),
(8, 8, '2026-09-16 09:35:08', '2026-09-16 14:16:07', 2, 5, 25000, NULL, 'keluar', 5, 2, NULL, 12),
(9, 9, '2026-09-16 14:14:05', '2026-09-16 14:16:33', 2, 1, 5000, NULL, 'keluar', 11, 1, NULL, 13),
(10, 10, '2026-09-16 14:24:48', '2026-09-17 07:59:28', 2, 18, 90000, NULL, 'keluar', 2, 1, NULL, NULL),
(11, 8, '2026-09-16 14:29:50', '2026-09-17 07:59:33', 2, 18, 90000, NULL, 'keluar', 1, 1, NULL, NULL),
(12, 9, '2026-09-16 14:30:43', '2026-09-17 07:59:40', 2, 18, 90000, NULL, 'keluar', 1, 1, NULL, NULL),
(13, 8, '2026-09-17 08:21:46', '2026-09-17 08:22:33', 2, 1, 5000, NULL, 'keluar', 2, 1, NULL, NULL),
(14, 11, '2026-09-17 08:22:44', '2026-09-17 08:57:29', 3, 1, 5000, 'qris', 'keluar', 2, 1, NULL, NULL),
(15, 11, '2026-09-17 09:28:27', '2026-09-17 09:44:35', 3, 1, 5000, 'cash', 'keluar', 2, 1, NULL, NULL),
(16, 10, '2026-09-17 09:44:47', '2026-09-18 09:17:59', 2, 24, 120000, 'cash', 'keluar', 2, 1, NULL, NULL),
(17, 8, '2026-09-17 10:00:30', '2026-09-18 21:11:33', 2, 36, 180000, 'cash', 'keluar', 2, 1, NULL, NULL),
(18, 4, '2026-09-17 10:01:17', '2026-09-19 17:14:23', 2, 56, 280000, 'qris', 'keluar', 2, 2, NULL, NULL),
(19, 7, '2026-09-18 09:18:15', '2026-09-19 17:13:18', 2, 32, 160000, 'qris', 'keluar', 2, 3, NULL, NULL),
(20, 8, '2026-09-18 21:28:14', '2026-09-19 17:17:12', 2, 20, 100000, 'qris', 'keluar', 2, 1, NULL, 14),
(21, 8, '2026-09-19 18:45:24', '2026-09-19 18:46:54', 2, 1, 5000, 'qris', 'keluar', 5, 1, NULL, 15),
(22, 8, '2026-09-21 12:04:01', '2026-09-21 12:09:40', 2, 1, 5000, 'qris', 'keluar', 2, 2, NULL, 17),
(23, 12, '2026-09-21 18:53:04', '2026-09-21 18:54:14', 2, 1, 5000, 'qris', 'keluar', 2, 2, NULL, NULL),
(24, 3, '2026-09-21 19:13:57', '2026-09-22 19:29:05', 2, 25, 125000, 'cash', 'keluar', 2, 3, NULL, NULL),
(25, 8, '2026-09-21 19:14:22', NULL, 2, 0, 0, NULL, 'masuk', 5, 2, NULL, 18),
(26, 4, '2026-09-21 19:17:31', NULL, 2, 0, 0, NULL, 'masuk', 2, 3, NULL, NULL),
(27, 14, '2026-09-22 14:06:20', '2026-09-22 14:07:27', 2, 1, 5000, 'cash', 'keluar', 2, 1, NULL, NULL),
(28, 8, '2026-09-22 14:10:16', NULL, 2, 0, 0, NULL, 'masuk', 2, 1, NULL, 21),
(29, 15, '2026-09-22 19:18:53', NULL, 2, 0, 0, NULL, 'masuk', 2, 2, NULL, NULL),
(30, 16, '2026-09-23 08:06:07', NULL, 2, 0, 0, NULL, 'masuk', 2, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_ulasan`
--

CREATE TABLE `tb_ulasan` (
  `id_ulasan` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nama_pengulas` varchar(50) NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 5.0,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ulasan`
--

INSERT INTO `tb_ulasan` (`id_ulasan`, `id_area`, `id_user`, `nama_pengulas`, `rating`, `komentar`, `created_at`) VALUES
(1, 1, 5, 'Rafael', 5.0, 'bagius', '2026-08-11 04:45:27'),
(2, 2, 5, 'Rafael', 5.0, 'tes', '2026-09-04 05:24:32');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','petugas','owner','pengguna','karyawan') NOT NULL,
  `status_aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `status_aktif`) VALUES
(1, 'Administrator', 'admin', 'admin123', 'admin', 1),
(2, 'Petugas Parkir', 'petugas', 'petugas123', 'petugas', 1),
(3, 'Owner Parkir', 'owner', 'owner123', 'owner', 1),
(5, 'Rafael', 'Rafa', '$2y$10$.uiRJsRaDXtGb8n1.qFFcOCAAjaL0LMJyiNgWTYIHZW639WYX6lmO', 'pengguna', 1),
(6, 'Faisal', 'faisal', '$2y$10$IQVnrx2ZWudQUI0GrLa8mu1ZFVWnreh26Bftf7oEOrtZyRQlujiLm', 'admin', 1),
(7, 'Faisal2', 'faisal2', '123456', 'admin', 1),
(8, 'Rafael22', 'Rafael22', '$2y$10$W2N9ZWwKIYoaK5xOMJ7kweVq1w5JjOE2xnPsL54VNaigOM1ZIgHYi', 'pengguna', 1),
(9, 'Faizal', 'Faizal', '$2y$10$HKOM3FLstnPzQYSpYJqZ/uaUdyDHLZxmWI7DiWc0RtkvMZp/.RsIO', 'karyawan', 1),
(10, 'fajar', 'fajar b', '$2y$10$vT.wyYfe9tdqQOJ9u8UpCOPwXi.aknbVDetEROSVbkR8A4xeV7iFq', 'karyawan', 1),
(11, 'faizal2', 'faizal2', '$2y$10$n.cU47ZzIzu.jrgWwKBfUeqF8YFVmK8ToMY.35/LgNykJI8CRpIHK', 'pengguna', 1),
(12, 'Rafa2', 'Rafa2', '$2y$10$CD6Mm9LUxwO3iroMxM7vB.lor/xybK0mvyE4ahw/.t/OEfqHh63CS', 'pengguna', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  ADD PRIMARY KEY (`id_area`);

--
-- Indexes for table `tb_area_parkir_karyawan`
--
ALTER TABLE `tb_area_parkir_karyawan`
  ADD PRIMARY KEY (`id_area_karyawan`);

--
-- Indexes for table `tb_booking`
--
ALTER TABLE `tb_booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_area` (`id_area`);

--
-- Indexes for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  ADD PRIMARY KEY (`id_kendaraan`),
  ADD KEY `fk_kendaraan_user` (`id_user`);

--
-- Indexes for table `tb_log`
--
ALTER TABLE `tb_log`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_log_user` (`id_user`);

--
-- Indexes for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  ADD PRIMARY KEY (`id_tarif`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_parkir`),
  ADD KEY `fk_transaksi_kendaraan` (`id_kendaraan`),
  ADD KEY `fk_transaksi_tarif` (`id_tarif`),
  ADD KEY `fk_transaksi_user` (`id_user`),
  ADD KEY `fk_transaksi_area` (`id_area`),
  ADD KEY `fk_transaksi_booking` (`id_booking`);

--
-- Indexes for table `tb_ulasan`
--
ALTER TABLE `tb_ulasan`
  ADD PRIMARY KEY (`id_ulasan`),
  ADD KEY `id_area` (`id_area`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_area_parkir`
--
ALTER TABLE `tb_area_parkir`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tb_area_parkir_karyawan`
--
ALTER TABLE `tb_area_parkir_karyawan`
  MODIFY `id_area_karyawan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_booking`
--
ALTER TABLE `tb_booking`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  MODIFY `id_kendaraan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tb_log`
--
ALTER TABLE `tb_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_tarif`
--
ALTER TABLE `tb_tarif`
  MODIFY `id_tarif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_parkir` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tb_ulasan`
--
ALTER TABLE `tb_ulasan`
  MODIFY `id_ulasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_booking`
--
ALTER TABLE `tb_booking`
  ADD CONSTRAINT `tb_booking_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`),
  ADD CONSTRAINT `tb_booking_ibfk_2` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`),
  ADD CONSTRAINT `tb_booking_ibfk_3` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`);

--
-- Constraints for table `tb_kendaraan`
--
ALTER TABLE `tb_kendaraan`
  ADD CONSTRAINT `fk_kendaraan_user` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_log_aktivitas`
--
ALTER TABLE `tb_log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `fk_transaksi_area` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_booking` FOREIGN KEY (`id_booking`) REFERENCES `tb_booking` (`id_booking`),
  ADD CONSTRAINT `fk_transaksi_kendaraan` FOREIGN KEY (`id_kendaraan`) REFERENCES `tb_kendaraan` (`id_kendaraan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_tarif` FOREIGN KEY (`id_tarif`) REFERENCES `tb_tarif` (`id_tarif`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_ulasan`
--
ALTER TABLE `tb_ulasan`
  ADD CONSTRAINT `tb_ulasan_ibfk_1` FOREIGN KEY (`id_area`) REFERENCES `tb_area_parkir` (`id_area`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
