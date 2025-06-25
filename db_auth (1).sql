-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 02:15 PM
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
  `nilai_keluar_fifo` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_permintaan`
--

INSERT INTO `detail_permintaan` (`id`, `id_permintaan`, `id_produk`, `jumlah`, `harga_saat_minta`, `nilai_keluar_fifo`) VALUES
(1, 1, 1, 1, 64158.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_produk`
--

CREATE TABLE `kategori_produk` (
  `id` int(11) NOT NULL,
  `nusp_id` varchar(50) NOT NULL,
  `nama_kategori` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_produk`
--

INSERT INTO `kategori_produk` (`id`, `nusp_id`, `nama_kategori`) VALUES
(1, '1.1.7.01.03.02.001.0005', 'Kertas HVS'),
(2, '1.1.7.01.03.02.002.0015', 'Berbagai Kertas');

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
  `bentuk_kontrak` varchar(255) DEFAULT NULL,
  `nama_penyedia` varchar(255) DEFAULT NULL,
  `nomor_faktur` varchar(100) DEFAULT NULL,
  `sumber_anggaran` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penerimaan`
--

INSERT INTO `penerimaan` (`id`, `id_produk`, `jumlah`, `harga_satuan`, `tanggal_penerimaan`, `bentuk_kontrak`, `nama_penyedia`, `nomor_faktur`, `sumber_anggaran`, `catatan`) VALUES
(1, 2, 3, 80000.00, '2025-06-25 10:50:28', 'Kerja Sama', 'PT Sumber Jaya', '122345679', 'APBN', ''),
(2, 2, 5, 90000.00, '2025-06-25 11:35:22', 'Kerja Sama', 'PT Sumber Mandiri', '12234567', 'APBN', '');

-- --------------------------------------------------------

--
-- Table structure for table `perbaikan_aset`
--

CREATE TABLE `perbaikan_aset` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nama_aset` varchar(255) NOT NULL,
  `komponen_rusak` varchar(255) NOT NULL,
  `deskripsi_kerusakan` text NOT NULL,
  `tanggal_laporan` datetime NOT NULL DEFAULT current_timestamp(),
  `status_perbaikan` enum('Baru','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Baru',
  `catatan_admin` text DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perbaikan_aset`
--

INSERT INTO `perbaikan_aset` (`id`, `id_user`, `nama_aset`, `komponen_rusak`, `deskripsi_kerusakan`, `tanggal_laporan`, `status_perbaikan`, `catatan_admin`, `tanggal_selesai`) VALUES
(1, 2, 'Komputer Ruangan B', 'Power Suplay', 'jadi sudah tidak bisanaya ketika dicolok ke power', '2025-06-25 17:38:33', 'Diproses', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permintaan`
--

CREATE TABLE `permintaan` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_perbaikan_aset` int(11) DEFAULT NULL,
  `tanggal_permintaan` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Disetujui','Ditolak') NOT NULL DEFAULT 'Pending',
  `catatan_admin` text DEFAULT NULL,
  `tanggal_diproses` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permintaan`
--

INSERT INTO `permintaan` (`id`, `id_user`, `id_perbaikan_aset`, `tanggal_permintaan`, `status`, `catatan_admin`, `tanggal_diproses`) VALUES
(1, 2, NULL, '2025-06-25 14:27:38', 'Disetujui', '', '2025-06-25 09:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `spesifikasi` varchar(255) NOT NULL,
  `satuan` varchar(50) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stok_awal` int(11) NOT NULL DEFAULT 0,
  `harga_awal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`id`, `id_kategori`, `spesifikasi`, `satuan`, `stok`, `harga`, `stok_awal`, `harga_awal`) VALUES
(1, 1, 'Kertas HVS Copy Paper F4 80 Gr', 'Rim', 0, 64158.00, 1, 64158.00),
(2, 1, 'Kertas HVS Copy Paper F4 75 Gr', 'Rim', 10, 90000.00, 2, 70000.00);

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
(1, 2, 1, 3, 3, 80000.00, '2025-06-25 10:50:28'),
(2, 2, 2, 5, 5, 90000.00, '2025-06-25 11:35:22');

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
-- Indexes for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nusp_id` (`nusp_id`);

--
-- Indexes for table `penerimaan`
--
ALTER TABLE `penerimaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indexes for table `perbaikan_aset`
--
ALTER TABLE `perbaikan_aset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_perbaikan_aset` (`id_perbaikan_aset`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kategori` (`id_kategori`);

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
-- AUTO_INCREMENT for table `kategori_produk`
--
ALTER TABLE `kategori_produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penerimaan`
--
ALTER TABLE `penerimaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `perbaikan_aset`
--
ALTER TABLE `perbaikan_aset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permintaan`
--
ALTER TABLE `permintaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `perbaikan_aset`
--
ALTER TABLE `perbaikan_aset`
  ADD CONSTRAINT `perbaikan_aset_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD CONSTRAINT `permintaan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `produk_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
