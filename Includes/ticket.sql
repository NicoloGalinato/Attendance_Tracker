-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2025 at 10:44 PM
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
-- Table structure for table `ticket`
--

CREATE TABLE `ticket` (
  `id` int(15) NOT NULL,
  `Timestamp` text DEFAULT NULL,
  `Email_Address` text DEFAULT NULL,
  `Department` text DEFAULT NULL,
  `Site` text DEFAULT NULL,
  `Affected_employee` text DEFAULT NULL,
  `EID` text DEFAULT NULL,
  `Issues_Concerning` text DEFAULT NULL,
  `Station_Number` text DEFAULT NULL,
  `TIME_RECEIVED` text DEFAULT NULL,
  `TIME_RESOLVED` text DEFAULT NULL,
  `SLT_on_DUTY` text DEFAULT NULL,
  `Week_Beginning` text DEFAULT NULL,
  `LOB` text DEFAULT NULL,
  `OM` text DEFAULT NULL,
  `Employee_name` text DEFAULT NULL,
  `Work_Number` text DEFAULT NULL,
  `Status` text DEFAULT NULL,
  `Urgency` text NOT NULL,
  `Issue_Details` text DEFAULT NULL,
  `resolution` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket`
--

INSERT INTO `ticket` (`id`, `Timestamp`, `Email_Address`, `Department`, `Site`, `Affected_employee`, `EID`, `Issues_Concerning`, `Station_Number`, `TIME_RECEIVED`, `TIME_RESOLVED`, `SLT_on_DUTY`, `Week_Beginning`, `LOB`, `OM`, `Employee_name`, `Work_Number`, `Status`, `Urgency`, `Issue_Details`, `resolution`) VALUES
(1, '8/17/2025 09:35:55', 'july4lorena@gmail.com', 'ALE', 'KAWIT', 'Individual', 'CXI11625', 'Keyboard', 'STN0001', '9:35 AM', '2025-08-17 09:40:33', 'PENDING', '08/11/2025', 'ADMIN', 'Benedict Mendoza', 'DAROY, LORENA', 'SLT0000', 'RESOLVED', 'Low', 'sample', 'asdasd');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ticket`
--
ALTER TABLE `ticket`
  MODIFY `id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
