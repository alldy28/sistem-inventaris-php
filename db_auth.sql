-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 06:12 AM
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
-- Database: `db_auth`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_permintaan`
--

CREATE TABLE `detail_permintaan` (
  `id` int(11) NOT NULL,
  `id_permintaan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_saat_minta` decimal(10,2) NOT NULL,
  `nilai_keluar_fifo` decimal(15,2) DEFAULT NULL COMMENT 'Nilai total biaya berdasarkan perhitungan FIFO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_permintaan`
--

INSERT INTO `detail_permintaan` (`id`, `id_permintaan`, `id_produk`, `jumlah`, `harga_saat_minta`, `nilai_keluar_fifo`) VALUES
(1, 1, 3, 13, 60000.00, 750000.00);

-- --------------------------------------------------------

--
-- Table structure for table `penerimaan`
--

CREATE TABLE `penerimaan` (
  `id` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `tanggal_penerimaan` datetime NOT NULL DEFAULT current_timestamp(),
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penerimaan`
--

INSERT INTO `penerimaan` (`id`, `id_produk`, `jumlah`, `harga_satuan`, `tanggal_penerimaan`, `catatan`) VALUES
(1, 3, 5, 70000.00, '2025-06-24 21:44:52', ''),
(2, 3, 5, 80000.00, '2025-06-24 21:56:21', '');

-- --------------------------------------------------------

--
-- Table structure for table `permintaan`
--

CREATE TABLE `permintaan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_permintaan` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
  `catatan_admin` text DEFAULT NULL,
  `tanggal_diproses` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permintaan`
--

INSERT INTO `permintaan` (`id`, `id_user`, `tanggal_permintaan`, `status`, `catatan_admin`, `tanggal_diproses`) VALUES
(1, 2, '2025-06-25 02:57:39', 'Disetujui', '', '2025-06-24 21:58:55');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nusp_id` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `satuan` varchar(30) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stok_awal` int(11) NOT NULL DEFAULT 0 COMMENT 'Stok pada awal periode',
  `harga_awal` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Harga satuan pada awal periode'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `nusp_id`, `nama_barang`, `satuan`, `stok`, `harga`, `stok_awal`, `harga_awal`) VALUES
(2, 'NUSP002', 'Keyboard Mekanikal', 'Pcs', 0, 9000000.00, 0, 9000000.00),
(3, 'NUSP003', 'Kertas A4 80gr', 'Rim', 2, 70000.00, 5, 60000.00),
(4, 'NUSP004', 'Tinta Printer Hitam', 'Botol', 0, 70000.00, 0, 70000.00),
(5, '1.1.7.01.03.02.001.0005', 'Kertas HVS Copy Paper F4 75 Gr', 'Rim', 0, 80000.00, 0, 80000.00),
(6, '1.1.7.01.03.02.001.13', 'Kertas HVS Copy Paper F2 70 Gr', 'Rim', 0, 70000.00, 0, 70000.00),
(7, '1.1.7.01.03.02.001.020', 'Kertas HVS Copy Paper F5 75 Gr', 'Rim', 0, 50000.00, 0, 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `stok_batch`
--

CREATE TABLE `stok_batch` (
  `id` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `id_penerimaan` int(11) NOT NULL,
  `jumlah_awal` int(11) NOT NULL,
  `sisa_stok` int(11) NOT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `tanggal_masuk` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_batch`
--

INSERT INTO `stok_batch` (`id`, `id_produk`, `id_penerimaan`, `jumlah_awal`, `sisa_stok`, `harga_beli`, `tanggal_masuk`) VALUES
(1, 3, 1, 5, 0, 70000.00, '2025-06-24 21:44:52'),
(2, 3, 2, 5, 0, 80000.00, '2025-06-24 21:56:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$hrMXEC3ySmhtgqaGTCs92equeBkNT7KWPV25NTQnLWhlqDIbgmtCW', 'Administrator Utama', 'admin', '2025-06-23 07:30:51'),
(2, 'budi', '$2y$10$k5nF4KBeABcug0WzRdLQjODIM1033poRQ1CFY89/v8rSuQSCVd7fO', 'Budi Santoso', 'user', '2025-06-23 07:30:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_permintaan`
--
ALTER TABLE `detail_permintaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_permintaan` (`id_permintaan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nusp_id` (`nusp_id`),
  ADD UNIQUE KEY `nusp_id_2` (`nusp_id`);

--
-- Indexes for table `stok_batch`
--
ALTER TABLE `stok_batch`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produk` (`id_produk`),
  ADD KEY `id_penerimaan` (`id_penerimaan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_permintaan`
--
ALTER TABLE `detail_permintaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permintaan`
--
ALTER TABLE `permintaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stok_batch`
--
ALTER TABLE `stok_batch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_permintaan`
--
ALTER TABLE `detail_permintaan`
  ADD CONSTRAINT `detail_permintaan_ibfk_1` FOREIGN KEY (`id_permintaan`) REFERENCES `permintaan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_permintaan_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD CONSTRAINT `penerimaan_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD CONSTRAINT `permintaan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stok_batch`
--
ALTER TABLE `stok_batch`
  ADD CONSTRAINT `stok_batch_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stok_batch_ibfk_2` FOREIGN KEY (`id_penerimaan`) REFERENCES `penerimaan` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
