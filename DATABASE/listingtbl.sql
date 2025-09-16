-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 06:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tahanandb`
--

-- --------------------------------------------------------

--
-- Table structure for table `listingtbl`
--

CREATE TABLE `listingtbl` (
  `ID` int(11) NOT NULL,
  `listingName` varchar(255) DEFAULT NULL,
  `price` int(50) NOT NULL,
  `listingDesc` varchar(255) NOT NULL,
  `images` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `rooms` int(11) DEFAULT NULL,
  `listingDate` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `landlord_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listingtbl`
--

INSERT INTO `listingtbl` (`ID`, `listingName`, `price`, `listingDesc`, `images`, `address`, `barangay`, `rooms`, `listingDate`, `category`, `landlord_id`) VALUES
(1, 'BAHAY NI KUYA', 7999, 'AUTO EVICTION', '1758038514_402934371_315858804559983_8804292395181792774_n.jpg', 'JAN LANG SA KANTO', 'Calendola', 1, '0000-00-00', 'Condominium', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_listing_landlord` (`landlord_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD CONSTRAINT `fk_listing_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
