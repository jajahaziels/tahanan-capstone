-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 04:02 PM
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
-- Table structure for table `admintbl`
--

CREATE TABLE `admintbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phoneNum` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landlordtbl`
--

CREATE TABLE `landlordtbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phoneNum` varchar(11) DEFAULT NULL,
  `verificationId` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `street` varchar(50) DEFAULT NULL,
  `barangay` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipCode` int(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `profilePic` varchar(255) DEFAULT NULL,
  `dateJoin` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listingtbl`
--

CREATE TABLE `listingtbl` (
  `ID` int(11) NOT NULL,
  `listingName` varchar(255) DEFAULT NULL,
  `price` int(50) NOT NULL,
  `listingDesc` varchar(255) NOT NULL,
  `images` longtext NOT NULL,
  `address` varchar(255) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `rooms` int(11) DEFAULT NULL,
  `listingDate` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `landlord_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listingtbl`
--

INSERT INTO `listingtbl` (`ID`, `listingName`, `price`, `listingDesc`, `images`, `address`, `barangay`, `rooms`, `listingDate`, `category`, `landlord_id`, `latitude`, `longitude`) VALUES
(1, 'BAHAY NI KUYA', 2222, 'sssssssssssssssssssss', '[\"1758084896_68ca3f207fa6f_381687360_298767639568410_6505632902293883757_n.jpg\"]', 'JAN LANG SA KANTO', 'Bagong Silang', 3, '2025-09-17', 'Condominium', NULL, 14.3643373, 121.0502529),
(2, 'BAHAY NI KUYA', 2222, 'sssssssssssssssssssss', '[\"1758084990_68ca3f7e2b3da_381687360_298767639568410_6505632902293883757_n.jpg\"]', 'JAN LANG SA KANTO', 'Bagong Silang', 3, '2025-09-17', 'Condominium', NULL, 14.3643373, 121.0502529),
(3, 'APARTMENT2', 998, 'jjjjjjjjjjjjjjjjjj', '[\"1758085013_68ca3f9516cd0_381687360_298767639568410_6505632902293883757_n.jpg\"]', 'JAN LANG SA KANTOs', 'Calendola', 2, '2025-09-17', 'Single-family home', NULL, 14.3662913, 121.0493088),
(4, 'APARTMENT2', 998, 'jjjjjjjjjjjjjjjjjj', '[\"1758085590_68ca41d652f77_381687360_298767639568410_6505632902293883757_n.jpg\"]', 'JAN LANG SA KANTOs', 'Calendola', 2, '2025-09-17', 'Single-family home', NULL, 14.3662913, 121.0493088),
(5, 'BAHAY NI ATEEs', 2222, '22222222222', '[\"1758085629_68ca41fd95098_382486546_6612322138887282_4039537186339714220_n.jpg\"]', '2222222222', 'Bagong Silang', 1, '2025-09-17', 'Condominium', NULL, 14.3645868, 121.0526991),
(6, 'BAHAY NI ATEEs', 2222, '22222222222', '[\"1758085662_68ca421eb779d_382486546_6612322138887282_4039537186339714220_n.jpg\"]', '2222222222', 'Bagong Silang', 1, '2025-09-17', 'Condominium', NULL, 14.3645868, 121.0526991),
(7, 'BAHAY NI KUYA', 2222222, '22222222222222222', '[\"1758086066_68ca43b2c2643_home.png\"]', '2222222222222', 'Bagong Silang', 2, '2025-09-17', 'Apartment complex', NULL, 14.3642314, 121.0530746),
(8, 'APARTMENT3', 22222, '222222222222', '[\"1758086252_68ca446ccb325_home.png\"]', '222222222222', 'Bagong Silang', 2, '2025-09-17', 'Apartment complex', NULL, 14.3634227, 121.0521412);

-- --------------------------------------------------------

--
-- Table structure for table `renttbl`
--

CREATE TABLE `renttbl` (
  `ID` int(11) NOT NULL,
  `date` date NOT NULL,
  `landlord_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requesttbl`
--

CREATE TABLE `requesttbl` (
  `ID` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenanttbl`
--

CREATE TABLE `tenanttbl` (
  `ID` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verificationId` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `profilePic` varchar(255) DEFAULT NULL,
  `datejoin` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `phoneNum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_listing_landlord` (`landlord_id`);

--
-- Indexes for table `renttbl`
--
ALTER TABLE `renttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_rent_landlord` (`landlord_id`),
  ADD KEY `fk_rent_tenant` (`tenant_id`),
  ADD KEY `fk_rent_listing` (`listing_id`);

--
-- Indexes for table `requesttbl`
--
ALTER TABLE `requesttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_request_tenant` (`tenant_id`),
  ADD KEY `fk_request_listing` (`listing_id`);

--
-- Indexes for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `renttbl`
--
ALTER TABLE `renttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requesttbl`
--
ALTER TABLE `requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD CONSTRAINT `fk_listing_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `renttbl`
--
ALTER TABLE `renttbl`
  ADD CONSTRAINT `fk_rent_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rent_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rent_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requesttbl`
--
ALTER TABLE `requesttbl`
  ADD CONSTRAINT `fk_request_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
