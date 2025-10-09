-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2025 at 11:22 AM
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
-- Database: `tahanandb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admintbl`
--

CREATE TABLE `admintbl` (
  `ID` int(11) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `phoneNum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('private','group') NOT NULL DEFAULT 'private',
  `title` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `type`, `title`, `created_at`) VALUES
(1, 'private', 'Chat between allen mina and oliber olivera', '2025-10-02 09:13:58');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_members`
--

CREATE TABLE `conversation_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` enum('landlord','tenant') NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_message_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `conversation_members`
--

INSERT INTO `conversation_members` (`id`, `conversation_id`, `user_type`, `user_id`, `joined_at`, `last_read_message_id`) VALUES
(1, 1, 'landlord', 1, '2025-10-02 09:13:58', NULL),
(2, 1, 'tenant', 4, '2025-10-02 09:13:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `landlordtbl`
--

CREATE TABLE `landlordtbl` (
  `ID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
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
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
  `ID_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlordtbl`
--

INSERT INTO `landlordtbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `phoneNum`, `verificationId`, `birthday`, `street`, `barangay`, `city`, `province`, `zipCode`, `country`, `gender`, `profilePic`, `dateJoin`, `status`, `created_at`, `verification_status`, `ID_image`) VALUES
(1, 'allen', 'allen', 'mina', NULL, 'allen@gmail.com', '$2y$10$GkbWeG2n1owen.YATCsyQ.qUCDxAVnRI1BZzHxE88eeUhYqXxx906', '45346323', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-02 03:27:32', 'verified', '../LANDLORD/uploads/ids1759375774_468223193_122120882294401719_4910812637509051064_n.jpg');

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
(1, 'BAHAY NI KENJI', 2523, '.......................', '[\"1759375816_68ddf1c848252_468223193_122120882294401719_4910812637509051064_n.jpg\"]', 'DOON LAGN', 'Bagong Silang', 4, '2025-10-02', 'Townhouse', 1, 14.3551935, 121.0526181),
(2, 'BAHAY NI CARLO', 432423, '......................', '[\"1759379452_68ddfffc7b50f_1758977990_68d7dfc6f135a_382486546_6612322138887282_4039537186339714220_n.jpg\"]', 'DONN', 'Bagong Silang', 3, '2025-10-02', 'Townhouse', 1, 14.3451736, 121.0560083),
(3, 'BAHAY NI BERHEL', 1324, '132................', '[\"1759382927_68de0d8f89d16_1758205268_68cc1554bd4f0_INFORMATION TECHNOLOGY SOCIETY.png\",\"1759382927_68de0d8f8a03a_1758205593_68cc1699aa447_Gemini_Generated_Image_8b6dsy8b6dsy8b6d.png\"]', 'AsdASDA', 'Calendola', 2, '2025-10-02', 'Single-family home', 1, 14.3506106, 121.0541439),
(4, 'bahay ni oliiber', 5232, '..............................', '[\"1759387936_68de2120cf9bb_1759050360_68d8fa787f1e7_340328863_137665385938079_6705222439846490113_n.jpg\"]', '........................', 'Bagong Silang', 2, '2025-10-02', 'Single-family home', 1, 14.3516134, 121.0526610),
(5, 'KJAKD', 342, 'asdasdasdassssssssss', '[\"1759389277_68de265daff21_1758988260_68d807e45a387_381687360_298767639568410_6505632902293883757_n.jpg\"]', 'asjdajh', 'Bagong Silang', 2, '2025-10-02', 'Single-family home', 1, 14.3566902, 121.0560512);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `content_type` enum('text','image','file') NOT NULL DEFAULT 'text',
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `content_type`, `status`, `created_at`, `updated_at`, `is_read`) VALUES
(1, 1, 4, 'Hello! I have a question about the property.', 'text', 'active', '2025-10-02 09:13:58', NULL, 0),
(2, 1, 4, 'asjdaksdasdha', 'text', 'active', '2025-10-02 09:14:27', NULL, 0),
(3, 1, 1, 'ihihi', 'text', 'active', '2025-10-02 09:14:34', NULL, 0),
(4, 1, 4, 'okii', 'text', 'active', '2025-10-02 09:16:14', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `renttbl`
--

CREATE TABLE `renttbl` (
  `ID` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `landlord_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renttbl`
--

INSERT INTO `renttbl` (`ID`, `date`, `status`, `landlord_id`, `tenant_id`, `listing_id`) VALUES
(1, '2025-10-02', 'approved', 1, 1, 1),
(2, '2025-10-02', 'pending', NULL, 3, NULL),
(3, '2025-10-02', 'pending', NULL, 3, NULL),
(4, '2025-10-02', 'pending', NULL, 3, NULL),
(5, '2025-10-02', 'pending', NULL, 3, NULL),
(6, '2025-10-02', 'pending', NULL, 3, NULL),
(7, '2025-10-02', 'rejected', NULL, 3, 1),
(8, '2025-10-02', 'rejected', NULL, 3, 4),
(9, '2025-10-02', 'approved', NULL, 4, 4);

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
  `ID` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `middleName` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verificationId` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `profilePic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `phoneNum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenanttbl`
--

INSERT INTO `tenanttbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `verificationId`, `birthday`, `gender`, `profilePic`, `created_at`, `status`, `phoneNum`) VALUES
(1, 'mina', 'mina', 'mina', NULL, 'mina@gmail.com', '$2y$10$WcyN9bKppu1gXv5plvKZtO/hNT59Kw/mD.9fFfxSdMK365rQDtqVC', NULL, NULL, NULL, NULL, NULL, NULL, 2147483647),
(2, 'sam', 'sam', 'hayop', NULL, 'sam@gmail.com', '$2y$10$47U13TSs7UJGmULauosR1e2MOuBGJ.2KS6YPpW63IEp8TlxKdnMr6', NULL, NULL, NULL, NULL, NULL, NULL, 7825428),
(3, 'berhel', 'berhel', 'abella', NULL, 'berhel@gmail.com', '$2y$10$np9lY.f4ngKnhsG58VqXOe0j5jzpMwtauuLkysmS3RnBLRlT49lIW', NULL, NULL, NULL, NULL, NULL, NULL, 254265347),
(4, 'oliber', 'oliber', 'olivera', NULL, 'oliber@gmail.com', '$2y$10$uiU9Va54jhFfEXYTcos1p.AHxonYfp/KaWoKGSm0RacDypGi28C7G', NULL, NULL, NULL, NULL, NULL, NULL, 5644566);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `conversation_members`
--
ALTER TABLE `conversation_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_listing_landlord` (`landlord_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `conversation_members`
--
ALTER TABLE `conversation_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `renttbl`
--
ALTER TABLE `renttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `requesttbl`
--
ALTER TABLE `requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
