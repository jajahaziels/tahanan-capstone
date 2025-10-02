-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 02, 2025 at 02:04 AM
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
(2, 'private', 'Chat between samuel alcazar and sam aaa', '2025-09-30 07:44:18'),
(3, 'private', 'Chat between samuel landlord and sam aaa', '2025-09-30 15:05:45');

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
(3, 2, 'landlord', 5, '2025-09-30 07:44:18', NULL),
(4, 2, 'tenant', 4, '2025-09-30 07:44:18', NULL),
(5, 3, 'landlord', 2, '2025-09-30 15:05:45', NULL),
(6, 3, 'tenant', 4, '2025-09-30 15:05:45', NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlordtbl`
--

INSERT INTO `landlordtbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `phoneNum`, `verificationId`, `birthday`, `street`, `barangay`, `city`, `province`, `zipCode`, `country`, `gender`, `profilePic`, `dateJoin`, `status`, `created_at`) VALUES
(2, 'samuelll', 'samuel', 'landlord', NULL, 'salmuel.alcazar@cdsp.edu.ph', '$2y$10$43oeO3tDnPZzzFNT6i0Oxec5RkSIANMmBlFnDXD4/YHVd9oykYI3a', '09asdsadsda', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-30 06:55:30'),
(5, 'Justmnc30', 'samuel', 'alcazar', NULL, 'minceydicey@gmail.com', '$2y$10$2X0OxFNwX/WWVtZYqxvEoeS3/UGXhow/uFT7j55/k0F9JLKnCCK8W', '09421323121', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-27 01:43:33');

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
(1, 'BAHAY NI KUYA', 3332, '.........................', '[\"1758370391_68ce9a57ab50f_INFORMATION TECHNOLOGY SOCIETY.png\"]', 'JAN LANG SA KANTO', 'Calendola', 1, '2025-09-20', 'Low-rise apartment', NULL, 14.3653411, 121.0512023);

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
(2, 2, 4, 'Hello! I have a question about the property.', 'text', 'active', '2025-09-30 07:44:18', NULL, 0),
(3, 2, 5, 'sadasdsa', 'text', 'active', '2025-09-30 07:44:29', NULL, 0),
(4, 2, 4, 'adasda', 'text', 'active', '2025-09-30 07:44:38', NULL, 0),
(5, 2, 5, 'sadasda', 'text', 'active', '2025-09-30 07:44:40', NULL, 0),
(6, 2, 4, 'dasdas', 'text', 'active', '2025-09-30 07:44:42', NULL, 0),
(7, 2, 5, 'sadsda', 'text', 'active', '2025-09-30 07:44:44', NULL, 0),
(8, 2, 4, 'asdasdas', 'text', 'active', '2025-09-30 07:44:47', NULL, 0),
(9, 2, 5, 'adsadas', 'text', 'active', '2025-09-30 07:44:49', NULL, 0),
(10, 2, 4, 'asdasdad', 'text', 'active', '2025-09-30 07:44:52', NULL, 0),
(11, 2, 4, 'dsaasda', 'text', 'active', '2025-09-30 07:45:37', NULL, 0),
(12, 2, 5, 'asdasda', 'text', 'active', '2025-09-30 07:45:41', NULL, 0),
(13, 2, 5, 'adsadsa', 'text', 'active', '2025-09-30 07:45:44', NULL, 0),
(14, 2, 4, 'sadasd', 'text', 'active', '2025-09-30 07:45:46', NULL, 0),
(15, 2, 4, 'asdadsad', 'text', 'active', '2025-09-30 15:01:58', NULL, 0),
(16, 3, 4, 'Hello! I have a question about the property.', 'text', 'active', '2025-09-30 15:05:45', NULL, 0),
(17, 3, 2, 'adsada', 'text', 'active', '2025-09-30 15:06:04', NULL, 0),
(18, 2, 4, 'asdasda', 'text', 'active', '2025-09-30 15:06:09', NULL, 0),
(19, 3, 4, 'hi', 'text', 'active', '2025-09-30 15:06:16', NULL, 0),
(20, 3, 2, 'hello', 'text', 'active', '2025-09-30 15:06:20', NULL, 0),
(21, 3, 4, 'sadsa', 'text', 'active', '2025-10-01 13:04:19', NULL, 0),
(22, 3, 4, 'hello', 'text', 'active', '2025-10-01 13:04:23', NULL, 0),
(23, 3, 4, 'hi', 'text', 'active', '2025-10-01 13:04:25', NULL, 0),
(24, 3, 4, 'musa', 'text', 'active', '2025-10-01 13:04:28', NULL, 0),
(25, 2, 5, 'adsadas', 'text', 'active', '2025-10-01 13:06:19', NULL, 0),
(26, 2, 5, 'asdas', 'text', 'active', '2025-10-01 13:06:42', NULL, 0),
(27, 2, 5, 'hello', 'text', 'active', '2025-10-01 13:06:57', NULL, 0),
(28, 2, 5, 'hi', 'text', 'active', '2025-10-01 13:06:59', NULL, 0),
(29, 2, 5, 'sup', 'text', 'active', '2025-10-01 13:07:02', NULL, 0),
(30, 2, 4, 'hello', 'text', 'active', '2025-10-01 23:50:36', NULL, 0),
(31, 3, 4, 'hello', 'text', 'active', '2025-10-01 23:50:58', NULL, 0),
(32, 2, 4, 'hi', 'text', 'active', '2025-10-01 23:51:07', NULL, 0),
(33, 2, 4, 'hi', 'text', 'active', '2025-10-01 23:51:14', NULL, 0),
(34, 2, 5, 'hey', 'text', 'active', '2025-10-01 23:53:22', NULL, 0),
(35, 2, 4, 'asdas', 'text', 'active', '2025-10-01 23:54:26', NULL, 0),
(36, 3, 4, 'hi', 'text', 'active', '2025-10-01 23:54:33', NULL, 0),
(37, 3, 4, 'hello', 'text', 'active', '2025-10-01 23:54:49', NULL, 0);

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
  `datejoin` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `phoneNum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenanttbl`
--

INSERT INTO `tenanttbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `verificationId`, `birthday`, `gender`, `profilePic`, `datejoin`, `status`, `phoneNum`) VALUES
(1, 'Justmnc30', 'samuel', 'alcazar', NULL, 'psalmuelalcazar30@gmail.com', '$2y$10$6gL84WNCRqTQTQwe03KLTOQ/z7.5IMkifQJf4ZkKEyugRAnXAE9XW', NULL, NULL, NULL, NULL, NULL, NULL, 2147483647),
(3, 'Minceydicey2', 'samuel', 'alcazar', NULL, 'salmuel.alcazar@scha.edu.ph', '$2y$10$EijIIuuCfHA/.zOWvljC3.dX0UL7d2p2mCy6o76mGDetDtB5Itf1S', NULL, NULL, NULL, NULL, NULL, NULL, 13123123),
(4, 'samueltnt', 'sam', 'aaa', NULL, 'salmuel.alcazar@sa.edu.ph', '$2y$10$48Zm01eYRKFb43GqSkBqme5.NiYUTlGpSSpEKW7aUbEA5NtbCRl4S', NULL, NULL, NULL, NULL, NULL, NULL, 0);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conv_created` (`conversation_id`,`created_at`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `conversation_members`
--
ALTER TABLE `conversation_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversation_members`
--
ALTER TABLE `conversation_members`
  ADD CONSTRAINT `conversation_members_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD CONSTRAINT `fk_listing_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

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
