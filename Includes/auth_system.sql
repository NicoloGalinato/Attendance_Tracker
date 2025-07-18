-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 09:28 PM
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
-- Database: `auth_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `sub_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `slt_email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `login_attempts` int(11) DEFAULT 0,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `sub_name`, `password`, `slt_email`, `created_at`, `login_attempts`, `last_failed_login`, `locked_until`, `role`) VALUES
(1, 'cxi11899', 'GALINATO, NICO', 'SLT NICO', '$2y$10$Nq1m.u1mc22CgjCYuRzHp.16IMLEc5PvKtFeZpPt0ej7V9xhIghc6', 'nicolo.galinato@communixinc.com', '2025-07-17 15:24:12', 0, NULL, NULL, 'admin'),
(37, 'cxi00525', 'JUAN TORRES', 'SLT JC', '$2y$10$fSnVChxO1EvhBhXFPkl/HOGC.uxik2kHay6cvlOKwkaIgXju.ASqS', 'juan.torres@communixinc.com', '2025-07-17 19:44:34', 0, NULL, NULL, 'admin'),
(38, 'cxi00730', 'RG DUTERTE', 'SLT RG', '$2y$10$4Tnf9CRtLqS4fvFM2Otjme8zCSX6bbtUDiSgNVQ8IGUgo/VBZ4gqe', 'rg.duterte@communixinc.com', '2025-07-17 19:45:03', 0, NULL, NULL, 'admin'),
(39, 'cxi11647', 'ALEXANDER RAY OLAES', 'SLT ALEX', '$2y$10$drhplWGrgo0Gz7nbnRXV.OcYwpieFAkaKROp/xrH2Hj5hNxnyEItW', 'a.olaes@communixinc.com', '2025-07-17 19:45:22', 0, NULL, NULL, 'admin'),
(40, 'cxi11652', 'ANGKIKO, MIGUEL JEAN', 'SLT MIGS', '$2y$10$QfoRP/1fsDr2jyaTZTSQfORiDix1fHi..f4KK9p.lzU38sk7oWv7S', 'miguel.angkiko@communixinc.com', '2025-07-17 19:45:49', 0, NULL, NULL, 'admin'),
(41, 'cxi11664', 'IVERSON LOMAT', 'SLT IVER', '$2y$10$ju/wgHSL1tQJeM4/weg5nuX3oGwKhBSOg3fcGjRZUIlzELPwtPeXi', 'iverson.lomat@communixinc.com', '2025-07-17 19:46:06', 0, NULL, NULL, 'admin'),
(42, 'cxi11812', 'CHRISTIAN MONTOYA', 'SLT CIAN', '$2y$10$jyCYyY.UBtHPSBlKONCrpeZ15N4u0VHQw20/LxV0q6fxvqpuAa2cy', 'c.montoya@communixinc.com', '2025-07-17 19:46:35', 0, NULL, NULL, 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fullname` (`fullname`) USING BTREE,
  ADD UNIQUE KEY `username` (`username`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
