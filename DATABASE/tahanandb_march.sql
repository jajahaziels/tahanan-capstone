-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 04:22 PM
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
-- Table structure for table `account_suspensions`
--

CREATE TABLE `account_suspensions` (
  `ID` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `suspension_type` enum('temporary','permanent') NOT NULL,
  `reason` text NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL COMMENT 'NULL for permanent suspensions',
  `suspended_by` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `lifted_by` varchar(100) DEFAULT NULL,
  `lifted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL,
  `admin_username` varchar(100) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT NULL,
  `target_landlord_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `action_timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`id`, `admin_username`, `action_type`, `target_landlord_id`, `notes`, `action_timestamp`) VALUES
(0, 'Tahanan', 'verified', 2, NULL, '2026-02-09 00:32:26'),
(1, 'Tahanan Admin', 'verified', 3, NULL, '2026-02-05 21:03:54');

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

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `type`, `title`, `created_at`) VALUES
(13, 'private', 'Chat about STUDIO TYPE APARTMENT', '2026-02-08 20:12:06'),
(14, 'private', 'Chat about Magandang bahay hehe', '2026-02-08 20:12:14'),
(15, 'private', 'Chat about Bahay sa san pedro', '2026-03-26 10:51:25'),
(16, 'private', 'Chat about STUDIO TYPE APARTMENT', '2026-03-27 10:52:23'),
(17, 'private', 'Chat about STUDION TYPE APARTMENT', '2026-03-27 11:00:03');

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
(1, 1, 'tenant', 1, '2025-11-08 01:55:17', NULL),
(2, 1, 'landlord', 1, '2025-11-08 01:55:17', NULL),
(3, 2, 'tenant', 7, '2026-02-08 16:33:02', NULL),
(4, 2, 'landlord', 2, '2026-02-08 16:33:02', NULL),
(5, 3, 'tenant', 7, '2026-02-08 16:34:32', NULL),
(6, 3, 'landlord', 1, '2026-02-08 16:34:32', NULL),
(9, 5, 'tenant', 7, '2026-02-08 19:47:58', NULL),
(10, 5, 'landlord', 2, '2026-02-08 19:47:58', NULL),
(11, 6, 'tenant', 7, '2026-02-08 19:48:39', NULL),
(12, 6, 'landlord', 2, '2026-02-08 19:48:39', NULL),
(13, 7, 'tenant', 7, '2026-02-08 19:48:50', NULL),
(14, 7, 'landlord', 2, '2026-02-08 19:48:50', NULL),
(15, 8, 'tenant', 7, '2026-02-08 19:52:57', NULL),
(16, 8, 'landlord', 2, '2026-02-08 19:52:57', NULL),
(17, 9, 'tenant', 7, '2026-02-08 19:54:15', NULL),
(18, 9, 'landlord', 2, '2026-02-08 19:54:15', NULL),
(19, 10, 'tenant', 2, '2026-02-08 19:58:43', NULL),
(20, 10, 'landlord', 2, '2026-02-08 19:58:43', NULL),
(21, 11, 'tenant', 2, '2026-02-08 20:04:02', NULL),
(22, 11, 'landlord', 2, '2026-02-08 20:04:02', NULL),
(23, 12, 'tenant', 2, '2026-02-08 20:04:45', NULL),
(24, 12, 'landlord', 1, '2026-02-08 20:04:45', NULL),
(25, 13, 'tenant', 2, '2026-02-08 20:12:06', NULL),
(26, 13, 'landlord', 1, '2026-02-08 20:12:06', NULL),
(27, 14, 'tenant', 2, '2026-02-08 20:12:14', NULL),
(28, 14, 'landlord', 2, '2026-02-08 20:12:14', NULL),
(29, 15, 'tenant', 3, '2026-03-26 10:51:25', NULL),
(30, 15, 'landlord', 2, '2026-03-26 10:51:25', NULL),
(31, 16, 'tenant', 1, '2026-03-27 10:52:23', NULL),
(32, 16, 'landlord', 1, '2026-03-27 10:52:23', NULL),
(33, 17, 'tenant', 3, '2026-03-27 11:00:03', NULL),
(34, 17, 'landlord', 1, '2026-03-27 11:00:03', NULL);

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
  `lastName` varchar(50) DEFAULT NULL,
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
  `ID_image` varchar(255) DEFAULT NULL,
  `valid_id` varchar(255) DEFAULT NULL,
  `proof_of_ownership` varchar(255) DEFAULT NULL,
  `landlord_insurance` varchar(255) DEFAULT NULL,
  `gas_safety_cert` varchar(255) DEFAULT NULL,
  `electric_safety_cert` varchar(255) DEFAULT NULL,
  `lease_agreement` varchar(255) DEFAULT NULL,
  `admin_rejection_reason` text DEFAULT NULL,
  `submission_date` datetime DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlordtbl`
--

INSERT INTO `landlordtbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `phoneNum`, `verificationId`, `birthday`, `street`, `barangay`, `city`, `province`, `zipCode`, `country`, `gender`, `profilePic`, `status`, `created_at`, `verification_status`, `ID_image`, `valid_id`, `proof_of_ownership`, `landlord_insurance`, `gas_safety_cert`, `electric_safety_cert`, `lease_agreement`, `admin_rejection_reason`, `submission_date`, `verified_date`) VALUES
(1, NULL, 'Jahaziel', 'Sison', 'Bautista', 'jajasison07@gmail.com', '$2y$10$yeBWZM7FROJnfP6aYivI1.nrDbtaSR5MqSf3molFn6Y1aADSfmiia', '09932273303', NULL, '2004-08-07', 'Blk 2 Lot 6 Phase 1B, Sta. Ana St.', 'Pacita 1', 'San Pedro', 'Laguna', 4023, NULL, 'Female', '1772716430_profile_ll1.png', 'active', '2025-11-08 00:52:24', 'verified', 'uploads/ids/1762563197_stock-vector-driver-license-with-male-photo-identification-or-id-card-template-vector-illustration-1227173818.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'sam landlord', 'samuel', 'alcazar', '', 'psalmuelalcazar30@gmail.com', '$2y$10$DVIKRPqbvwyhl5FGA6f1PuR7bhe.hBAXoJbVk9NAg83D18BDPvHuW', '', NULL, '0000-00-00', '', '', '', '', 0, NULL, 'Male', '1770581747_profile_be7cff0e3f0046dc8d36d24201af422cH3000W3000_464_464.jpg', 'pending', '2026-02-08 16:23:50', 'verified', NULL, 'uploads/verification/2_valid_id_1770568334.jpg', 'uploads/verification/2_proof_of_ownership_1770568334.jpg', 'uploads/verification/2_landlord_insurance_1770568334.jpg', 'uploads/verification/2_gas_safety_cert_1770568334.jpg', 'uploads/verification/2_electric_safety_cert_1770568334.jpg', 'uploads/verification/2_lease_agreement_1770568334.docx', NULL, '2026-02-09 00:32:14', '2026-02-09 00:32:26'),
(10, 'Jaja', '', NULL, NULL, 'sisonja07@gmail.com', '$2y$10$lNiMPFNzel/NdDd0RnJ73uUda.ChHSoVAq3ZURwJswrJ2VdMidwHy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-03-31 14:18:56', 'not_submitted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `landlord_rating_summary`
-- (See below for the actual view)
--
CREATE TABLE `landlord_rating_summary` (
`landlord_id` int(11)
,`firstName` varchar(50)
,`lastName` varchar(50)
,`total_reviews` bigint(21)
,`average_rating` decimal(7,4)
,`five_star_count` decimal(22,0)
,`four_star_count` decimal(22,0)
,`three_star_count` decimal(22,0)
,`two_star_count` decimal(22,0)
,`one_star_count` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `landlord_warnings`
--

CREATE TABLE `landlord_warnings` (
  `ID` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `warning_level` enum('first','second','final') NOT NULL,
  `reason` text NOT NULL,
  `issued_by` varchar(100) NOT NULL,
  `expires_at` datetime DEFAULT NULL COMMENT 'Warnings expire after 6 months',
  `is_active` tinyint(1) DEFAULT 1,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leasetbl`
--

CREATE TABLE `leasetbl` (
  `ID` int(11) NOT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `landlord_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `rent` decimal(10,2) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','expired','terminated','renewed') NOT NULL DEFAULT 'pending',
  `pdf_path` varchar(255) DEFAULT NULL,
  `tenant_response` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `lease_status` enum('active','terminated','renewed') DEFAULT 'active',
  `visible_to_tenant` tinyint(1) NOT NULL DEFAULT 1,
  `rent_due_day` int(11) NOT NULL,
  `request_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leasetbl`
--

INSERT INTO `leasetbl` (`ID`, `listing_id`, `tenant_id`, `landlord_id`, `start_date`, `end_date`, `rent`, `deposit`, `terms`, `created_at`, `status`, `pdf_path`, `tenant_response`, `lease_status`, `visible_to_tenant`, `rent_due_day`, `request_id`) VALUES
(206, 15, 1, 1, '2026-04-01', '2026-07-07', 20000.00, 3000.00, '[\"Tenant pays 1 month advance rent and 1 month security deposit.\",\"Security deposit refundable upon move-out minus damages.\",\"Rent must be paid on or before the due date.\",\"No subleasing without landlord approval.\"]', '2026-03-31 12:44:03', 'active', '../LANDLORD/leases/lease_206.pdf', 'accepted', 'active', 1, 6, NULL),
(207, 4, 3, 1, '2026-04-01', '2026-08-18', 1500.00, 3000.00, '[\"Tenant pays 1 month advance rent and 1 month security deposit.\",\"Security deposit refundable upon move-out minus damages.\",\"Rent must be paid on or before the due date.\",\"No subleasing without landlord approval.\"]', '2026-04-01 13:59:13', 'active', '../LANDLORD/leases/lease_207.pdf', 'accepted', 'active', 1, 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lease_renewaltbl`
--

CREATE TABLE `lease_renewaltbl` (
  `ID` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `requested_date` date NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `landlord_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `landlord_response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lease_terminationstbl`
--

CREATE TABLE `lease_terminationstbl` (
  `ID` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `terminated_by` enum('tenant','landlord') NOT NULL,
  `reason` text DEFAULT NULL,
  `terminated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `landlord_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `landlord_response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listingtbl`
--

CREATE TABLE `listingtbl` (
  `ID` int(11) NOT NULL,
  `listingName` varchar(255) DEFAULT NULL,
  `price` int(50) NOT NULL,
  `listingDesc` longtext DEFAULT NULL,
  `images` longtext NOT NULL,
  `address` varchar(255) NOT NULL,
  `barangay` varchar(50) NOT NULL,
  `rooms` int(11) DEFAULT NULL,
  `listingDate` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `landlord_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `availability` enum('available','occupied') DEFAULT 'available',
  `verification_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_visit_scheduled` datetime DEFAULT NULL,
  `admin_visit_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listingtbl`
--

INSERT INTO `listingtbl` (`ID`, `listingName`, `price`, `listingDesc`, `images`, `address`, `barangay`, `rooms`, `listingDate`, `category`, `landlord_id`, `latitude`, `longitude`, `availability`, `verification_status`, `verification_notes`, `verified_by`, `verified_date`, `rejection_reason`, `admin_visit_scheduled`, `admin_visit_completed`) VALUES
(2, 'Single-Family House for Rent – 1 Bedroom', 4500, 'Cozy single-family house available for rent, perfect for individuals or small families looking for a private and peaceful home.\r\n\r\n1 Bedroom\r\nLiving area\r\nKitchen space\r\nPrivate bathroom\r\n\r\nFeatures:\r\nQuiet and family-friendly environment\r\nIdeal for 1–2 occupants\r\nEasy to maintain', '[\"1774640161_69c6dc2190d85_FB_IMG_1774531689693.jpg\",\"1774640161_69c6dc2194367_FB_IMG_1774531692164.jpg\",\"1774640161_69c6dc2196264_FB_IMG_1774531695541.jpg\",\"1774640161_69c6dc2198164_FB_IMG_1774531697993.jpg\",\"1774640161_69c6dc219a140_FB_IMG_1774531700387.jpg\",\"1774640161_69c6dc219bffc_FB_IMG_1774531702688.jpg\",\"1774640161_69c6dc219da80_FB_IMG_1774531705000.jpg\"]', '123 Rizal St., San Vicente, San Pedro, Laguna', 'San Vicente', 1, '2025-11-08', 'Single-family home', 1, 14.3464895, 121.0263443, 'available', 'approved', '', 1, '2026-03-26 13:57:08', NULL, NULL, 0),
(4, 'STUDION TYPE APARTMENT', 1500, 'House type: Apartment\r\n₱ 3500/month\r\nDescription\r\nMale Bed space for Rent at Magsaysay, S.P.L\r\n\r\n\r\nAddress:\r\nPurok #6, 1669 National Highway\r\nMagsaysay, San Pedro, Laguna\r\nAlong highway, Near SM', '[\"1769874773_697e25559e2bd_617003593_1309577377576796_5793339414094449268_n.jpg\",\"1769874773_697e25559f5d5_619661220_1309577387576795_8602374495345039408_n.jpg\",\"1769874773_697e2555a07c7_619694282_1309577434243457_5342415326550166753_n.jpg\",\"1769874773_697e2555a1cdf_620008726_1309577430910124_4106903204621638308_n.jpg\",\"1769874773_697e2555a2b6f_621606639_1309577540910113_3689565198174649248_n.jpg\",\"1769874773_697e2555a381c_623365942_1309577364243464_1553094445443937069_n.jpg\",\"1769874773_697e2555a4580_623503654_1309577357576798_6462543165614109993_n.jpg\"]', 'Blk 6 Lot 8, P. Burgos Street, Magsaysay, S.P.L', 'Magsaysay', 4, '2025-11-08', 'Studio Unit', 1, 14.3682090, 121.0469066, 'available', 'approved', '', 1, '2026-03-26 13:57:28', NULL, NULL, 0),
(5, 'Townhouse for Rent Comfortable Family Living', 10000, 'Property Features:\r\n\r\n2 Bedrooms\r\n1 Bathroom\r\nLiving area\r\nKitchen and dining space\r\nParking space\r\n\r\n📍 Why Choose a Townhouse:\r\n\r\nMore affordable than detached houses\r\nLocated in organized communities or subdivisions\r\nShared walls but still offers good privacy\r\nEfficient use of space\r\n\r\nPerfect For:\r\nSmall families, couples, or professionals looking for a secure and budget-friendly home with a modern layout.', '[\"1774504890_69c4cbba543b2_174C22~1.WEB\",\"1774504890_69c4cbba5647e_1769874773_697e25559e2bd_617003593_1309577377576796_5793339414094449268_n.jpg\",\"1774504890_69c4cbba58fc4_1769874773_697e25559f5d5_619661220_1309577387576795_8602374495345039408_n.jpg\",\"1774504890_69c4cbba5ba02_177277~2.WEB\",\"1774504890_69c4cbba5cba8_175BB4~1.WEB\"]', '12 Palm Grove, San Pedro, Laguna', 'Pacita 2', 2, '2025-11-08', 'Townhouse', 1, 14.3482859, 121.0483027, 'available', 'approved', '', 1, '2026-03-26 14:11:23', NULL, NULL, 0),
(7, 'Cozy Loft Condo', 20000, 'Perfect for young professionals, with amenities.', '[\"1774504948_69c4cbf45234d_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJkZjRiLTRkMzktNzFiZC1iOGFmLWE1ZDhiZDhlMzQ0Zi8wMTliZGY1Zi0wY2ZmLTcyODUtOWM0ZS0wYjFiYWEzNzQwYmMuanBnIiwi - Copy.webp\",\"1774504948_69c4cbf456f67_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJkZjRiLTRkMzktNzFiZC1iOGFmLWE1ZDhiZDhlMzQ0Zi8wMTliZGY1Zi0wYjk0LTcyZjgtYTVjOC1hZjUxNmIwMWQyMGEuanBnIiwi - Copy.webp\",\"1774504948_69c4cbf45868f_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJkZjRiLTRkMzktNzFiZC1iOGFmLWE1ZDhiZDhlMzQ0Zi8wMTliZGY1Zi0wYmU5LTcxYTMtYjEwYy1mZDg3NzllNTQzYzIuanBnIiwiY.webp\",\"1774504948_69c4cbf45a0b5_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJkZjRiLTRkMzktNzFiZC1iOGFmLWE1ZDhiZDhlMzQ0Zi8wMTliZGY1Zi0wYTI5LTcyMDctYThkZS1lY2U1ZGY1NTg4ODguanBnIiwiY.webp\",\"1774504948_69c4cbf45b9d2_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJkZjRiLTRkMzktNzFiZC1iOGFmLWE1ZDhiZDhlMzQ0Zi8wMTliZGY1Zi0wYzM2LTcwYWYtOTcyNi0xNjVlMzFkMWQyYjEuanBnIiwi - Copy.webp\"]', '9 Horizon St., San Pedro, Laguna', 'Pacita 1', 4, '2025-11-08', 'High-rise apartment', 1, 14.3617548, 121.0560799, 'available', 'approved', '', 1, '2026-03-26 17:43:15', NULL, NULL, 0),
(8, 'Laguna Hills Townhouse', 34000, 'Townhouse with modern interiors and parking.', '[\"1774504739_69c4cb2322a8f_1769874411_697e23ebb3f8d_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzk1YmQzM2YwLTZlZWUtMTFlZC05ZDczLWI3OWViZDRhYjc3Ny8wMTliYmFmYi00MTc2LTcxMTgtYmQ2MS0 - Copy.webp\",\"1774504739_69c4cb2324bbf_176987~1.WEB\",\"1774504739_69c4cb2326b7c_1769874411_697e23ebb051f_623503654_1309577357576798_6462543165614109993_n.jpg\"]', '33 Del Pilar St., San Pedro, Laguna', 'Maharlika', 5, '2025-11-08', 'Condominium', 1, 14.3556710, 121.0537624, 'available', 'approved', 'all g', 1, '2026-03-26 13:59:36', NULL, NULL, 0),
(9, 'Gardenview Condo', 32000, 'Condo with garden view and gym access.', '[\"1774504982_69c4cc16d0ca7_590269974_837360282358204_5073854959907903242_n.jpg\",\"1774504982_69c4cc16d368f_590412492_818810960979420_6540923996156771945_n.jpg\",\"1774504982_69c4cc16d93eb_590953653_2107270670043159_7098658162033819033_n.jpg\",\"1774504982_69c4cc16dc0a6_591916959_2320551888410801_4076471974660158441_n.jpg\",\"1774504982_69c4cc16df157_592133209_696054726908885_5177470230404901730_n.jpg\",\"1774504982_69c4cc16e1be9_592952375_1924518878134274_7257499523061647961_n.jpg\"]', '10 Banay-Banay Rd., San Pedro, Laguna', 'Calendola', 3, '2025-11-08', 'Condominium', 1, 14.3641522, 121.0592556, 'available', 'pending', NULL, NULL, NULL, NULL, NULL, 0),
(10, 'Studio Type for Rent – Landayan, San Pedro, Laguna', 3500, 'Affordable studio-type unit available in Landayan, San Pedro, Laguna, perfect for solo renters or students looking for a budget-friendly place.\r\n\r\nDetails:\r\n\r\nOpen space (bedroom + living area)\r\nSmall kitchen area\r\nPrivate bathroom\r\n\r\n📍 Location: Near stores, public transport, and ROB GAL\r\n\r\nRent: ₱3,000/month\r\n\r\nBest for: Students, workers, or individuals looking for a simple and low-cost space.', '[\"1774505031_69c4cc47cd9ee_1769874773_697e2555a1cdf_620008726_1309577430910124_4106903204621638308_n.jpg\",\"1774505031_69c4cc47d041e_1769874773_697e2555a2b6f_621606639_1309577540910113_3689565198174649248_n.jpg\",\"1774505031_69c4cc47d2e9a_1769874773_697e2555a07c7_619694282_1309577434243457_5342415326550166753_n.jpg\"]', 'Purok 8, South Fairway, Landayan 1, S.P.L', 'Landayan', 1, '2025-11-08', 'Studio Unit', 1, 14.3519496, 121.0725838, 'available', 'approved', '', 1, '2026-03-26 20:43:07', NULL, NULL, 0),
(11, 'STUDIO TYPE APARTMENT', 20000, 'House type: Apartment\r\nOffer type: For Rent\r\nCar parks: 2\r\nContract duration: 1 Years\r\nUsable area: 250 sqm\r\nProperty Floor: 2\r\n6 Jan 2026 - Published by DPI Properties\r\n₱ 40,000/month\r\nDescription\r\nFOR RENT: Furnished 2 storey house and lot at Concorde Village Parañaque.\r\n\r\nLot area: 260sqm\r\nFloor area: 250sqm\r\n\r\n- 6 Bedrooms\r\n- 1 Office room\r\n- Washing machine included\r\n- Air conditioned all rooms\r\n- Dining table and chairs included\r\n- Rooms with cabinets\r\n- Water heater\r\n- Wifi included\r\n\r\nRental rate: ₱40,000/month\r\n2 months advance, 2 months security deposit.', '[\"1769875585_697e2881da010_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zY2EyLTcyZWUtYjRmOS05MDkzNTA0OGRkNjAuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881dacb7_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zY2U0LTcxNzAtYmE3Yi1kZTI3ZGI3NTJlNGQuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881db828_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYmE0LTczODktYTkyZi02NWExYjk0ODk1YWIuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881de246_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYmYzLTcxNmEtOTM3My1jOWRiZTFiZDExOWIuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881deba7_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYTZlLTcxODItOWEzMi1kY2UyZmFiMTNjNGUuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881df47c_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYzJjLTcwYmYtYmE0ZC1iMTMxMjYyNmZlNTQuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881dfd81_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYzYzLTcwNWItYjM1MC1lYzQwN2NlMjlmN2QuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881e0765_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zZDA5LTcwOGMtOWY4OS1iZjQwYTRhMWRhNzAuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881e1472_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zZTQ0LTczYTktODQxYy0xNGExYWRmMjNjYzEuanBnIiwiYnJhbmQiOiJsYW.webp\"]', '123 Rizal St., San Pedro, Laguna', 'San Roque', 4, '2026-01-31', 'Single-family home', 1, 14.3670574, 121.0606474, 'available', 'approved', '', 1, '2026-03-26 13:57:45', NULL, NULL, 0),
(12, 'Magandang bahay hehe', 213131, '123', '[\"1770568366_6988baaedae6a_ab67616d0000b27301cb2e736602194466522135.jfif\"]', '12312312', 'Bagong Silang', 0, '2026-02-08', 'Apartment complex', 2, 14.3640809, 121.0385227, 'available', 'approved', '', 1, '2026-03-28 12:32:28', NULL, NULL, 0),
(15, 'Isang buong bahat', 20000, 'Experience comfort, privacy, and convenience in this spacious whole house located in Pacita 1, San Pedro, Laguna, ideal for families looking for a peaceful yet accessible place to call home.\r\n\r\nThis property offers a complete living space, giving you the freedom and privacy that apartments cannot provide. The house features a well-designed layout with a cozy living area, a functional kitchen, and comfortable bedrooms perfect for family living.\r\n\r\nProperty Features:\r\n\r\n3 Bedrooms (ideal for small to medium-sized families)\r\n2 Bathrooms\r\nSpacious Living Area\r\nDining Area & Kitchen\r\nParking Space Available\r\nSmall outdoor space / yard \r\n\r\n📍 Prime Location:\r\nSituated in Pacita 1, San Pedro, Laguna, the house is located in a safe and established residential community. It is conveniently close to:\r\n\r\nSupermarkets and local markets\r\nSchools and learning centers\r\nHospitals and clinics\r\nPublic transportation (easy access to Manila and nearby cities)\r\nMalls such as Pacita Complex\r\n\r\nWhy You’ll Love It:\r\n\r\nFull house rental – no shared spaces\r\nPeaceful and family-friendly neighborhood\r\nAccessible location for work, school, and daily needs\r\nIdeal for long-term stay\r\n\r\nPerfect For:\r\nFamilies, working professionals, or anyone looking for a comfortable and secure home environment in Laguna.', '[\"1774637801_69c6d2e9e151c_FB_IMG_1774532022280.jpg\",\"1774637801_69c6d2e9e7aa9_FB_IMG_1774532027136.jpg\",\"1774637801_69c6d2e9eaf92_FB_IMG_1774532031832.jpg\",\"1774637801_69c6d2e9eee8a_FB_IMG_1774532036920.jpg\",\"1774637801_69c6d2e9f36b2_FB_IMG_1774532041586.jpg\",\"1774637802_69c6d2ea02184_FB_IMG_1774532045997.jpg\",\"1774637802_69c6d2ea043f2_FB_IMG_1774532051187.jpg\",\"1774637802_69c6d2ea06654_FB_IMG_1774532055432.jpg\",\"1774637802_69c6d2ea085c9_FB_IMG_1774532060447.jpg\",\"1774637802_69c6d2ea0b4ba_FB_IMG_1774532065088.jpg\",\"1774637802_69c6d2ea0d221_FB_IMG_1774532068931.jpg\",\"1774637802_69c6d2ea0fd26_FB_IMG_1774532073428.jpg\"]', 'Blk 2 Lot 6 Phase 1B, Sta. Ana St. Pacita 1, S.P.L', 'Pacita 1', 3, '2026-03-27', 'Single-family home', 1, 14.3482226, 121.0612699, 'available', 'approved', '', 1, '2026-03-28 02:57:56', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_attachmentstbl`
--

CREATE TABLE `maintenance_attachmentstbl` (
  `id` int(11) NOT NULL,
  `maintenance_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requeststbl`
--

CREATE TABLE `maintenance_requeststbl` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `category` enum('Plumbing','Electrical','Appliances','Structural','Pest Control','Cleaning','Other') DEFAULT 'Other',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Pending','Approved','In Progress','Completed','Rejected') DEFAULT 'Pending',
  `requested_date` date DEFAULT curdate(),
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `landlord_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `landlord_response` text DEFAULT NULL,
  `response_date` datetime DEFAULT NULL,
  `maintenance_status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_requeststbl`
--

INSERT INTO `maintenance_requeststbl` (`id`, `lease_id`, `tenant_id`, `landlord_id`, `title`, `description`, `photo_path`, `category`, `priority`, `status`, `requested_date`, `scheduled_date`, `completed_date`, `landlord_remarks`, `created_at`, `updated_at`, `landlord_response`, `response_date`, `maintenance_status`) VALUES
(19, 206, 1, 1, 'yuuu', 'yow', NULL, 'Appliances', 'Urgent', 'Completed', '2026-03-31', NULL, '2026-03-31', NULL, '2026-03-31 12:50:23', '2026-03-31 12:51:30', 'okiii na', NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `sender_type` varchar(20) DEFAULT NULL,
  `content` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `content_type` enum('text','image','file') NOT NULL DEFAULT 'text',
  `status` enum('active','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `sender_type`, `content`, `file_path`, `file_type`, `file_size`, `content_type`, `status`, `created_at`, `read_at`, `updated_at`, `is_read`) VALUES
(16, 13, 2, 'tenant', 'Hi! I\'m interested in your property: STUDIO TYPE APARTMENT.', NULL, NULL, NULL, 'text', 'active', '2026-02-08 20:12:06', NULL, NULL, 0),
(17, 14, 2, 'tenant', 'Hi! I\'m interested in your property: Magandang bahay hehe.', NULL, NULL, NULL, 'text', 'active', '2026-02-08 20:12:14', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(18, 14, 2, 'tenant', 'hey', NULL, NULL, NULL, 'text', 'active', '2026-02-08 20:12:18', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(19, 14, 2, 'landlord', 'yo', NULL, NULL, NULL, 'text', 'active', '2026-02-08 20:12:28', NULL, NULL, 0),
(20, 14, 2, 'landlord', '123', NULL, NULL, NULL, 'text', 'active', '2026-03-05 14:48:27', NULL, NULL, 0),
(21, 14, 2, 'tenant', '123213', NULL, NULL, NULL, 'text', 'active', '2026-03-25 16:05:54', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(22, 14, 2, 'tenant', 'yo', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:17:47', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(23, 14, 2, 'tenant', 'hey', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:20:31', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(24, 14, 2, 'landlord', 'yo', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:20:47', NULL, NULL, 0),
(25, 14, 2, 'landlord', 'sup', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:20:54', NULL, NULL, 0),
(26, 14, 2, 'tenant', 'yo', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:21:02', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(27, 14, 2, 'tenant', 'sup', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:35:25', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(28, 14, 2, 'landlord', 'hey', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:35:28', NULL, NULL, 0),
(29, 14, 2, 'tenant', 'yo', NULL, NULL, NULL, 'text', 'active', '2026-03-25 17:47:06', '2026-03-28 11:11:00', '2026-03-28 03:11:00', 0),
(30, 14, 2, 'landlord', 'hoy', NULL, NULL, NULL, 'text', 'active', '2026-03-26 02:51:11', NULL, NULL, 0),
(31, 15, 3, 'tenant', 'Hi! I\'m interested in your property: Bahay sa san pedro.', NULL, NULL, NULL, 'text', 'active', '2026-03-26 10:51:25', '2026-03-28 11:10:52', '2026-03-28 03:10:52', 0),
(32, 15, 3, 'tenant', 'hi', NULL, NULL, NULL, 'text', 'active', '2026-03-26 11:26:13', '2026-03-28 11:10:52', '2026-03-28 03:10:52', 0),
(33, 16, 1, 'tenant', 'Hi! I\'m interested in your property: STUDIO TYPE APARTMENT.', NULL, NULL, NULL, 'text', 'active', '2026-03-27 10:52:23', '2026-03-27 18:59:07', '2026-03-27 10:59:07', 0),
(34, 15, 3, 'tenant', 'hey', NULL, NULL, NULL, 'text', 'active', '2026-03-27 10:58:38', '2026-03-28 11:10:52', '2026-03-28 03:10:52', 0),
(35, 17, 3, 'tenant', 'Hi! I\'m interested in your property: STUDION TYPE APARTMENT.', NULL, NULL, NULL, 'text', 'active', '2026-03-27 11:00:03', '2026-03-27 19:00:49', '2026-03-27 11:00:49', 0),
(36, 17, 1, 'landlord', 'oki', NULL, NULL, NULL, 'text', 'active', '2026-03-27 11:01:04', '2026-03-27 19:01:28', '2026-03-27 11:01:28', 0),
(37, 16, 1, 'tenant', 'more details', NULL, NULL, NULL, 'text', 'active', '2026-03-27 21:03:37', '2026-03-28 05:03:58', '2026-03-27 21:03:58', 0),
(38, 16, 1, 'landlord', 'okay', NULL, NULL, NULL, 'text', 'active', '2026-03-27 21:04:23', '2026-03-28 09:06:08', '2026-03-28 01:06:08', 0),
(39, 16, 1, 'tenant', 'hi', NULL, NULL, NULL, 'text', 'active', '2026-03-28 01:06:30', '2026-03-28 12:30:19', '2026-03-28 04:30:19', 0),
(40, 14, 2, 'landlord', '.', NULL, NULL, NULL, 'text', 'active', '2026-03-28 03:12:16', NULL, NULL, 0),
(41, 14, 2, 'landlord', '.', NULL, NULL, NULL, 'text', 'active', '2026-03-28 03:12:21', NULL, NULL, 0),
(42, 14, 2, 'landlord', '.', NULL, NULL, NULL, 'text', 'active', '2026-03-28 03:12:26', NULL, NULL, 0),
(43, 16, 1, 'tenant', 'hey, i have issue', NULL, NULL, NULL, 'text', 'active', '2026-03-28 04:30:02', '2026-03-28 12:30:19', '2026-03-28 04:30:19', 0);

-- --------------------------------------------------------

--
-- Table structure for table `paymentstbl`
--

CREATE TABLE `paymentstbl` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `payment_type` enum('rent','deposit','penalty','Security Deposit','Advance Rent','Utility','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_method` enum('Cash','Bank Transfer','GCash','PayMaya','Cheque','Other') DEFAULT NULL,
  `status` enum('Pending','Paid','Overdue','Cancelled','pending_verification','rejected','partial') DEFAULT 'Pending',
  `reference_no` varchar(100) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `paymentstbl`
--

INSERT INTO `paymentstbl` (`id`, `lease_id`, `tenant_id`, `landlord_id`, `payment_type`, `amount`, `due_date`, `paid_date`, `payment_method`, `status`, `reference_no`, `proof_path`, `remarks`, `created_at`, `updated_at`) VALUES
(56, 206, 1, 1, 'rent', 3000.00, '2026-03-06', '2026-03-31', 'Cash', 'Paid', '', NULL, '', '2026-03-31 12:54:20', '2026-03-31 12:56:26'),
(57, 207, 3, 1, 'rent', 1500.00, '2026-04-04', '2026-04-09', 'Cash', 'Paid', '', NULL, 'EFRFDR', '2026-04-09 14:21:50', '2026-04-09 14:22:05');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `property_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_agreements`
--

CREATE TABLE `rental_agreements` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `status` enum('active','expired','terminated') DEFAULT 'active',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renttbl`
--

CREATE TABLE `renttbl` (
  `ID` int(11) NOT NULL,
  `lease_id` int(11) DEFAULT NULL,
  `date` date NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `landlord_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `tenant_request` enum('none','extend','cancel') DEFAULT 'none',
  `request_status` enum('pending','approved','denied') DEFAULT 'pending',
  `tenant_removed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renttbl`
--

INSERT INTO `renttbl` (`ID`, `lease_id`, `date`, `status`, `landlord_id`, `tenant_id`, `listing_id`, `start_date`, `end_date`, `tenant_request`, `request_status`, `tenant_removed`) VALUES
(66, NULL, '0000-00-00', '', 2, 2, 12, '0000-00-00', '0000-00-00', 'none', 'pending', 0),
(91, 206, '0000-00-00', 'approved', 1, 1, 15, '2026-04-01', '2026-07-07', 'none', 'pending', 0),
(92, 207, '0000-00-00', 'approved', 1, 3, 4, '2026-04-01', '2026-08-18', 'none', 'pending', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reportstbl`
--

CREATE TABLE `reportstbl` (
  `ID` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `landlord_id` int(11) NOT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `incident_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `evidence_files` text DEFAULT NULL COMMENT 'JSON array of file paths',
  `status` enum('pending','investigating','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `admin_action` varchar(255) DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `resolution_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reportstbl`
--

INSERT INTO `reportstbl` (`ID`, `tenant_id`, `is_anonymous`, `landlord_id`, `listing_id`, `category`, `subject`, `description`, `incident_date`, `priority`, `evidence_files`, `status`, `admin_notes`, `admin_action`, `reviewed_by`, `reviewed_at`, `resolution_details`, `created_at`, `updated_at`) VALUES
(1, 2, 0, 2, NULL, 'Scamming/Fraud', 'skamer', '123123123133121232213213122132112321123323213212332132323131321321312', '2026-01-22', 'medium', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-03-25 16:55:52', '2026-03-25 16:55:52'),
(2, 3, 1, 1, NULL, 'Illegal Activities', 'ggg', 'plklljkjkhjhplplplplplplplplplplplplplplplplplplpo', '2026-03-03', 'high', NULL, 'pending', NULL, NULL, NULL, NULL, NULL, '2026-03-28 04:44:06', '2026-03-28 04:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `report_actions_log`
--

CREATE TABLE `report_actions_log` (
  `ID` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `action_type` varchar(100) NOT NULL COMMENT 'warning, suspend, dismiss, investigate, etc.',
  `action_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report_actions_log`
--

INSERT INTO `report_actions_log` (`ID`, `report_id`, `admin_username`, `action_type`, `action_details`, `created_at`) VALUES
(1, 1, 'SYSTEM', 'report_created', 'Report submitted by tenant', '2026-03-25 16:55:52'),
(2, 2, 'SYSTEM', 'report_created', 'Report submitted by tenant', '2026-03-28 04:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `requesttbl`
--

CREATE TABLE `requesttbl` (
  `ID` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `listing_id` int(11) DEFAULT NULL,
  `status` enum('pending','waiting_tenant','approved','rejected') NOT NULL DEFAULT 'pending',
  `lease_id` int(11) DEFAULT NULL,
  `is_removed` tinyint(1) DEFAULT 0,
  `tenant_action` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requesttbl`
--

INSERT INTO `requesttbl` (`ID`, `date`, `tenant_id`, `listing_id`, `status`, `lease_id`, `is_removed`, `tenant_action`) VALUES
(126, '2026-03-31', 1, 8, 'pending', NULL, 0, NULL),
(128, '2026-03-31', 1, 15, 'approved', NULL, 0, NULL),
(129, '2026-04-01', 3, 4, 'approved', NULL, 0, NULL);

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

--
-- Dumping data for table `reset_password`
--

INSERT INTO `reset_password` (`id`, `email`, `token`, `expires_at`) VALUES
(0, 'jajasison07@gmail.com', '803761', '2026-03-28 21:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `landlord_id`, `tenant_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(1, 1, 7, 5, '676767676767676767sixseven', '2026-02-05 08:35:23', '2026-02-05 08:35:36'),
(0, 1, 1, 5, 'DSDFDFSDFSDGDGSDGFGDFGDFGDF', '2026-02-06 04:59:11', NULL),
(0, 1, 3, 5, 'ang galing neto pramis hehehehehe', '2026-03-28 03:10:01', NULL);

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `prevent_duplicate_reviews` BEFORE INSERT ON `reviews` FOR EACH ROW BEGIN
    DECLARE review_count INT;
    
    SELECT COUNT(*) INTO review_count
    FROM reviews
    WHERE landlord_id = NEW.landlord_id 
    AND tenant_id = NEW.tenant_id
    AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    IF review_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot submit multiple reviews within 30 days';
    END IF;
END
$$
DELIMITER ;

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
(1, 'Jaja', 'Jahaziel', 'Sison', 'Bautusta', 'jahaziel.sison@cdsp.edu.ph', '$2y$10$9gP2NL8makGt36JSUa3p2e/YFG/VdTC1oMzOcfBMA/WoCYs9rj/ua', NULL, '2004-08-07', 'Female', '1770343112_profile_551246401_1344915943899367_5892129137320406716_n.jpg', '2025-11-08 00:54:25', 'active', 2147483647),
(2, 'sam tenant', 'sam', 'tenant', '', 'minceydicey@gmail.com', '$2y$10$mTxwsCZP.OBGEHALwVh/HOt7US7RYoFOU8CzDP2r1CLOFla/qT80m', NULL, '0000-00-00', 'Male', '1770568510_profile_12902a631d6c4afd9f75b0b432158954.gif', '2026-02-08 16:24:07', 'pending', 0),
(3, 'jo-sison', 'Joyce Diane', 'Sison', '', 'sisonjoycediane29@gmail.com', '$2y$10$OCh2X7FHE4psYM4Vhl3riufVU9zSl3JN2UgQYe7163759EtFVntse', NULL, '2000-09-21', 'Female', '1774610670_profile_b7fc43d3-a8cb-4f56-8e54-70d67615dd70_0f474745.webp', '2026-03-05 13:08:57', 'active', 99322789);

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
-- Dumping data for table `trusted_devices`
--

INSERT INTO `trusted_devices` (`ID`, `user_id`, `device_hash`, `last_ip`, `last_used`, `role`) VALUES
(1, 1, '4d18388e047e300d3ba3a84c4f37faa3d1747ad346e1d8ba11e7333a9089ac32', '::1', '2025-11-10 15:36:58', 'landlord'),
(2, 1, '4d18388e047e300d3ba3a84c4f37faa3d1747ad346e1d8ba11e7333a9089ac32', '::1', '2025-12-13 22:07:02', 'tenant'),
(3, 2, '9d37f950beb0e85a86b77094a2e09cef7957813df5b7e8305a84629b2ae77231', '::1', '2026-02-09 00:25:27', 'landlord'),
(4, 2, '9d37f950beb0e85a86b77094a2e09cef7957813df5b7e8305a84629b2ae77231', '::1', '2026-02-09 00:26:07', 'tenant'),
(5, 3, 'ae19a203e0cfff11e538a46111a5ca11ec04ce8b6c32637852772ad9ac5b5078', '::1', '2026-03-05 21:40:22', 'landlord'),
(6, 2, 'bdb869440c8a2109a4697a93e1e98705d766e3a6695c7c6f89ed62327a1126d6', '::1', '2026-03-25 23:58:23', 'landlord'),
(7, 2, 'bdb869440c8a2109a4697a93e1e98705d766e3a6695c7c6f89ed62327a1126d6', '::1', '2026-03-26 00:05:32', 'tenant'),
(8, 1, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-26 13:41:27', 'landlord'),
(9, 2, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-26 20:06:45', 'landlord'),
(10, 3, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-27 19:19:34', 'tenant'),
(11, 1, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-29 02:58:33', 'tenant'),
(12, 9, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-31 22:16:39', 'landlord'),
(13, 10, '5b43de8f7990ebd39b7f81d2a7febbc144fc308ede43c5371ed5a7d30cdf5eba', '::1', '2026-03-31 22:19:47', 'landlord');

-- --------------------------------------------------------

--
-- Structure for view `landlord_rating_summary`
--
DROP TABLE IF EXISTS `landlord_rating_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `landlord_rating_summary`  AS SELECT `l`.`ID` AS `landlord_id`, `l`.`firstName` AS `firstName`, `l`.`lastName` AS `lastName`, count(`r`.`id`) AS `total_reviews`, avg(`r`.`rating`) AS `average_rating`, sum(case when `r`.`rating` = 5 then 1 else 0 end) AS `five_star_count`, sum(case when `r`.`rating` = 4 then 1 else 0 end) AS `four_star_count`, sum(case when `r`.`rating` = 3 then 1 else 0 end) AS `three_star_count`, sum(case when `r`.`rating` = 2 then 1 else 0 end) AS `two_star_count`, sum(case when `r`.`rating` = 1 then 1 else 0 end) AS `one_star_count` FROM (`landlordtbl` `l` left join `reviews` `r` on(`l`.`ID` = `r`.`landlord_id`)) GROUP BY `l`.`ID`, `l`.`firstName`, `l`.`lastName` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_suspensions`
--
ALTER TABLE `account_suspensions`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_landlord` (`landlord_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `admintbl`
--
ALTER TABLE `admintbl`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_username`),
  ADD KEY `idx_landlord` (`target_landlord_id`),
  ADD KEY `idx_timestamp` (`action_timestamp`);

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
-- Indexes for table `landlord_warnings`
--
ALTER TABLE `landlord_warnings`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_landlord` (`landlord_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `leasetbl`
--
ALTER TABLE `leasetbl`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `lease_renewaltbl`
--
ALTER TABLE `lease_renewaltbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `lease_id` (`lease_id`);

--
-- Indexes for table `lease_terminationstbl`
--
ALTER TABLE `lease_terminationstbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `lease_id` (`lease_id`);

--
-- Indexes for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_listing_landlord` (`landlord_id`),
  ADD KEY `idx_verification_status` (`verification_status`),
  ADD KEY `idx_landlord_verification` (`landlord_id`,`verification_status`);

--
-- Indexes for table `maintenance_attachmentstbl`
--
ALTER TABLE `maintenance_attachmentstbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenance_id` (`maintenance_id`);

--
-- Indexes for table `maintenance_requeststbl`
--
ALTER TABLE `maintenance_requeststbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lease_id` (`lease_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_read_at` (`conversation_id`,`read_at`);

--
-- Indexes for table `paymentstbl`
--
ALTER TABLE `paymentstbl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lease_id` (`lease_id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Indexes for table `renttbl`
--
ALTER TABLE `renttbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_rent_landlord` (`landlord_id`),
  ADD KEY `fk_rent_tenant` (`tenant_id`),
  ADD KEY `fk_rent_listing` (`listing_id`),
  ADD KEY `fk_lease` (`lease_id`);

--
-- Indexes for table `reportstbl`
--
ALTER TABLE `reportstbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_tenant` (`tenant_id`),
  ADD KEY `idx_landlord` (`landlord_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `report_actions_log`
--
ALTER TABLE `report_actions_log`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `idx_report` (`report_id`),
  ADD KEY `idx_admin` (`admin_username`);

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
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `email_3` (`email`),
  ADD UNIQUE KEY `email_4` (`email`),
  ADD UNIQUE KEY `email_5` (`email`),
  ADD UNIQUE KEY `email_6` (`email`),
  ADD UNIQUE KEY `email_7` (`email`),
  ADD UNIQUE KEY `email_8` (`email`),
  ADD UNIQUE KEY `email_9` (`email`),
  ADD UNIQUE KEY `email_10` (`email`),
  ADD UNIQUE KEY `email_11` (`email`),
  ADD UNIQUE KEY `email_12` (`email`),
  ADD UNIQUE KEY `email_13` (`email`),
  ADD UNIQUE KEY `email_14` (`email`),
  ADD UNIQUE KEY `email_15` (`email`),
  ADD UNIQUE KEY `email_16` (`email`),
  ADD UNIQUE KEY `email_17` (`email`),
  ADD UNIQUE KEY `email_18` (`email`),
  ADD UNIQUE KEY `email_19` (`email`),
  ADD UNIQUE KEY `email_20` (`email`),
  ADD UNIQUE KEY `email_21` (`email`);

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
-- AUTO_INCREMENT for table `account_suspensions`
--
ALTER TABLE `account_suspensions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `conversation_members`
--
ALTER TABLE `conversation_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `extension_requesttbl`
--
ALTER TABLE `extension_requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `landlord_warnings`
--
ALTER TABLE `landlord_warnings`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leasetbl`
--
ALTER TABLE `leasetbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `lease_renewaltbl`
--
ALTER TABLE `lease_renewaltbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lease_terminationstbl`
--
ALTER TABLE `lease_terminationstbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `maintenance_attachmentstbl`
--
ALTER TABLE `maintenance_attachmentstbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_requeststbl`
--
ALTER TABLE `maintenance_requeststbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `paymentstbl`
--
ALTER TABLE `paymentstbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `renttbl`
--
ALTER TABLE `renttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `reportstbl`
--
ALTER TABLE `reportstbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `report_actions_log`
--
ALTER TABLE `report_actions_log`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `requesttbl`
--
ALTER TABLE `requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_suspensions`
--
ALTER TABLE `account_suspensions`
  ADD CONSTRAINT `account_suspensions_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_suspensions_ibfk_2` FOREIGN KEY (`report_id`) REFERENCES `reportstbl` (`ID`) ON DELETE CASCADE;

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
-- Constraints for table `landlord_warnings`
--
ALTER TABLE `landlord_warnings`
  ADD CONSTRAINT `landlord_warnings_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `landlord_warnings_ibfk_2` FOREIGN KEY (`report_id`) REFERENCES `reportstbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `lease_renewaltbl`
--
ALTER TABLE `lease_renewaltbl`
  ADD CONSTRAINT `lease_renewaltbl_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leasetbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `lease_terminationstbl`
--
ALTER TABLE `lease_terminationstbl`
  ADD CONSTRAINT `lease_terminationstbl_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leasetbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD CONSTRAINT `fk_listing_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maintenance_attachmentstbl`
--
ALTER TABLE `maintenance_attachmentstbl`
  ADD CONSTRAINT `maintenance_attachmentstbl_ibfk_1` FOREIGN KEY (`maintenance_id`) REFERENCES `maintenance_requeststbl` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_requeststbl`
--
ALTER TABLE `maintenance_requeststbl`
  ADD CONSTRAINT `maintenance_requeststbl_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leasetbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_requeststbl_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_requeststbl_ibfk_3` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `paymentstbl`
--
ALTER TABLE `paymentstbl`
  ADD CONSTRAINT `paymentstbl_ibfk_1` FOREIGN KEY (`lease_id`) REFERENCES `leasetbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `paymentstbl_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `paymentstbl_ibfk_3` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `renttbl`
--
ALTER TABLE `renttbl`
  ADD CONSTRAINT `fk_lease` FOREIGN KEY (`lease_id`) REFERENCES `leasetbl` (`ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rent_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rent_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rent_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reportstbl`
--
ALTER TABLE `reportstbl`
  ADD CONSTRAINT `reportstbl_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportstbl_ibfk_2` FOREIGN KEY (`landlord_id`) REFERENCES `landlordtbl` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `report_actions_log`
--
ALTER TABLE `report_actions_log`
  ADD CONSTRAINT `report_actions_log_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reportstbl` (`ID`) ON DELETE CASCADE;

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
