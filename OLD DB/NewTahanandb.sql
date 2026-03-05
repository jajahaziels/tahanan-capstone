-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 25, 2025 at 08:52 AM
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
  `password` varchar(255) DEFAULT NULL,
  `phoneNum` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admintbl`
--

INSERT INTO `admintbl` (`ID`, `firstName`, `lastName`, `middleName`, `email`, `password`, `phoneNum`) VALUES
(1, 'Tahanan', 'Admin', NULL, 'tahanan@gmail.com', '$2y$10$hWiI.qZdoJmwUqN8a0Fhiu0pZ9rM//HZYYO5uJNSsq8iqJL.LzTnS', 2147483647);

-- --------------------------------------------------------

--
-- Table structure for table `cancel_requesttbl`
--

CREATE TABLE `cancel_requesttbl` (
  `ID` int(11) NOT NULL,
  `rent_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `extension_requesttbl`
--

CREATE TABLE `extension_requesttbl` (
  `ID` int(11) NOT NULL,
  `rent_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `listing_id` int(11) NOT NULL,
  `new_end_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landlordtbl`
--

CREATE TABLE `landlordtbl` (
  `ID` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
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
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_status` enum('not_submitted','pending','verified','rejected') DEFAULT 'not_submitted',
  `ID_image` varchar(255) DEFAULT NULL
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
  `longitude` decimal(10,7) DEFAULT NULL,
  `availability` enum('available','occupied') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `content_type` enum('text','image','file') NOT NULL DEFAULT 'text',
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paymenttbl`
--

CREATE TABLE `paymenttbl` (
  `ID` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renttbl`
--

CREATE TABLE `renttbl` (
  `ID` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `landlord_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `tenant_request` enum('none','extend','cancel') DEFAULT 'none',
  `request_status` enum('pending','approved','denied') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requesttbl`
--

CREATE TABLE `requesttbl` (
  `ID` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reset_password`
--

CREATE TABLE `reset_password` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
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

-- --------------------------------------------------------

--
-- Table structure for table `trusted_devices`
--

CREATE TABLE `trusted_devices` (
  `ID` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_hash` varchar(255) NOT NULL,
  `last_ip` varchar(45) NOT NULL,
  `last_used` datetime NOT NULL,
  `role` enum('tenant','landlord') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admintbl`
--
ALTER TABLE `admintbl`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cancel_requesttbl`
--
ALTER TABLE `cancel_requesttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_cancel_rent` (`rent_id`),
  ADD KEY `fk_cancel_tenant` (`tenant_id`),
  ADD KEY `fk_cancel_landlord` (`landlord_id`),
  ADD KEY `fk_cancel_listing` (`listing_id`);

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
-- Indexes for table `extension_requesttbl`
--
ALTER TABLE `extension_requesttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_rent` (`rent_id`),
  ADD KEY `fk_tenant` (`tenant_id`),
  ADD KEY `fk_landlord` (`landlord_id`),
  ADD KEY `fk_listing` (`listing_id`);

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
-- Indexes for table `paymenttbl`
--
ALTER TABLE `paymenttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `rental_id` (`rental_id`);

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
-- Indexes for table `reset_password`
--
ALTER TABLE `reset_password`
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`);

--
-- Indexes for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admintbl`
--
ALTER TABLE `admintbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cancel_requesttbl`
--
ALTER TABLE `cancel_requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversation_members`
--
ALTER TABLE `conversation_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extension_requesttbl`
--
ALTER TABLE `extension_requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paymenttbl`
--
ALTER TABLE `paymenttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cancel_requesttbl`
--
ALTER TABLE `cancel_requesttbl`
  ADD CONSTRAINT `fk_cancel_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cancel_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cancel_rent` FOREIGN KEY (`rent_id`) REFERENCES `renttbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cancel_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `extension_requesttbl`
--
ALTER TABLE `extension_requesttbl`
  ADD CONSTRAINT `fk_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rent` FOREIGN KEY (`rent_id`) REFERENCES `renttbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD CONSTRAINT `fk_listing_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `paymenttbl`
--
ALTER TABLE `paymenttbl`
  ADD CONSTRAINT `paymenttbl_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `renttbl` (`ID`);

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
