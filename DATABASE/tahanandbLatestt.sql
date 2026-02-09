-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 08, 2026 at 02:08 AM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetLandlordRatingStats` (IN `p_landlord_id` INT)   BEGIN
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as average_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews
    WHERE landlord_id = p_landlord_id;
END$$

DELIMITER ;

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
(1, 'private', 'Chat about Sunny Studio Apartment', '2025-11-08 01:55:17');

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
(2, 1, 'landlord', 1, '2025-11-08 01:55:17', NULL);

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
  `ID_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `landlordtbl`
--

INSERT INTO `landlordtbl` (`ID`, `username`, `firstName`, `lastName`, `middleName`, `email`, `password`, `phoneNum`, `verificationId`, `birthday`, `street`, `barangay`, `city`, `province`, `zipCode`, `country`, `gender`, `profilePic`, `status`, `created_at`, `verification_status`, `ID_image`) VALUES
(1, NULL, 'Jahaziel', NULL, NULL, 'jajasison07@gmail.com', '$2y$10$yeBWZM7FROJnfP6aYivI1.nrDbtaSR5MqSf3molFn6Y1aADSfmiia', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2025-11-08 00:52:24', 'verified', 'uploads/ids/1762563197_stock-vector-driver-license-with-male-photo-identification-or-id-card-template-vector-illustration-1227173818.jpg');

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
  `visible_to_tenant` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leasetbl`
--

INSERT INTO `leasetbl` (`ID`, `listing_id`, `tenant_id`, `landlord_id`, `start_date`, `end_date`, `rent`, `deposit`, `terms`, `created_at`, `status`, `pdf_path`, `tenant_response`, `lease_status`, `visible_to_tenant`) VALUES
(168, 4, 1, 1, '2026-02-13', '2026-06-02', 1500.00, 40000.00, '[\"Tenant pays 1 month advance rent and 1 month security deposit.\",\"Security deposit refundable upon move-out minus damages.\",\"Rent must be paid on or before the due date.\",\"No subleasing without landlord approval.\",\"No karaoke at past 10 in the midnight\",\"No parking\"]', '2026-02-07 03:46:00', 'active', '../LANDLORD/leases/lease_168.pdf', 'accepted', 'active', 1);

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
  `availability` enum('available','occupied') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `listingtbl`
--

INSERT INTO `listingtbl` (`ID`, `listingName`, `price`, `listingDesc`, `images`, `address`, `barangay`, `rooms`, `listingDate`, `category`, `landlord_id`, `latitude`, `longitude`, `availability`) VALUES
(2, 'Sunny Studio Apartment', 12000, 'Description\r\nArguably the best accommodation in Santa Rosa Laguna. Stay with us and be part of our family. We provide more than the basics. We have free Wi-Fi, a lounge area, a kitchen and a big backyard for you to hang out with and get to know your fellow boarders. We accept male and female tenants.\r\n\r\nP1, 950/month per head all-in\r\nGood for 8-people\r\n*RATES ARE SUBJECT TO CHANGE WITHOUT PRIOR NOTICE*\r\nSpacious room with own cabinet and tables. Toilet separate from bath\r\n\r\nWe are located along the Old National Highway, walkable from Balibago Complex and 1 ride to most offices.\r\n\r\nAmenities:\r\n24 hour security\r\nCCTV cameras\r\nFire alarm system\r\nPrivate toilet & bath per room\r\nToilet separate from shower\r\nCooking area\r\nLaundry cages', '[\"1769830724_697d79445232b_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJhMTQ1LTVlMGItNzEyOC04ZjQ3LWI1ZWE2MDA3NjQwMC8wMTliYTE0Ny1hMTZlLTcxNGMtOGVhNS0zODkyMTdjN2Q4YjAuanBnIiwiYnJhbmQiOiJsYW11Z.webp\",\"1769830724_697d794454cd5_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJhMTQ1LTVlMGItNzEyOC04ZjQ3LWI1ZWE2MDA3NjQwMC8wMTliYTE0Ny1hMmFlLTcyNTgtYjAxNi0xOGJjNGYwNDhkMGIuanBnIiwiYnJhbmQiOiJsYW11Z.webp\",\"1769830724_697d794456724_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWJhMTQ1LTVlMGItNzEyOC04ZjQ3LWI1ZWE2MDA3NjQwMC8wMTliYTE0Ny1hMzgzLTcyM2YtOThlMi1jZWM5MGFhZTIwMDAuanBnIiwiYnJhbmQiOiJsYW11Z.webp\"]', '123 Rizal St., San Pedro, Laguna', 'San Antonio', 3, '2025-11-08', 'Apartment complex', 1, 14.3450160, 121.0178471, 'available'),
(4, 'STUDION TYPE APARTMENT', 1500, 'House type: Apartment\r\nOffer type: For Rent\r\nUsable area: 25 sqm\r\n21 Mar 2025 - Published by Casita Santa Rosa\r\n₱ 1,950/month\r\nDescription\r\nMale Bed space for Rent at Sta. Rosa, Laguna\r\nEnjoy a clean environment, spacious room, and a comfortable set-up for only P1,950 a month inclusive of water and electricity.\r\n\r\n\r\nRate includes:\r\n- Free Wi-Fi\r\n- 24/7 security guard\r\n- cctv Protected\r\n- With\r\n-Furnished -Bed with mattress, built-in cabinet with lock, study desk with chairs, wall fans. Spacious room for eight people\r\nRoom has its own separate toilet and bathroom\r\n-Fire Detection & Alarm System\r\n-Lounge with LCD cable TV\r\n-Pantry with stove & microwave free of used\r\n-Laundry cage and Parking space at the back\r\n\r\nFor more information, you can text or call View Phone Whatsapp / WhatsApp.\r\n\r\nAddress:\r\nPurok #6, 1669 National Highway\r\nDila, Sta. Rosa Laguna\r\nAlong highway, Near Complex Balibago', '[\"1769874773_697e25559e2bd_617003593_1309577377576796_5793339414094449268_n.jpg\",\"1769874773_697e25559f5d5_619661220_1309577387576795_8602374495345039408_n.jpg\",\"1769874773_697e2555a07c7_619694282_1309577434243457_5342415326550166753_n.jpg\",\"1769874773_697e2555a1cdf_620008726_1309577430910124_4106903204621638308_n.jpg\",\"1769874773_697e2555a2b6f_621606639_1309577540910113_3689565198174649248_n.jpg\",\"1769874773_697e2555a381c_623365942_1309577364243464_1553094445443937069_n.jpg\",\"1769874773_697e2555a4580_623503654_1309577357576798_6462543165614109993_n.jpg\"]', 'Blk 6 Lot 8, P. Burgos Street, gsis 1, S.P.L', 'Poblacion', 4, '2025-11-08', 'Low-rise apartment', 1, 14.3496102, 121.0407162, 'available'),
(5, 'Serene Villa', 15000, 'Private villa with garden and pool.', '[\"1762565688_690e9e38088d2_11.jpg\",\"1762565688_690e9e380eb7a_12.jpg\",\"1762565688_690e9e3815073_13.jpg\"]', '12 Palm Grove, San Pedro, Laguna', 'Maharlika', 4, '2025-11-08', 'Apartment complex', 1, 14.3584866, 121.0443687, 'available'),
(7, 'Cozy Loft Condo', 20000, 'Perfect for young professionals, with amenities.', '[\"1762565795_690e9ea325202_2.jpg\",\"1762565795_690e9ea32835d_3.jpg\",\"1762565795_690e9ea32c3c8_4.jpg\"]', '9 Horizon St., San Pedro, Laguna', 'Nueva', 4, '2025-11-08', 'High-rise apartment', 1, 14.3617548, 121.0560799, 'available'),
(8, 'Laguna Hills Townhouse', 34000, 'Townhouse with modern interiors and parking.', '[\"1762566235_690ea05bb74ab_1.jpg\",\"1762566235_690ea05bbd78f_2.jpg\",\"1762566235_690ea05bc1c06_3.jpg\"]', '33 Del Pilar St., San Pedro, Laguna', 'Fatima', 5, '2025-11-08', 'Condominium', 1, 14.3556710, 121.0537624, 'available'),
(9, 'Gardenview Condo', 32000, 'Condo with garden view and gym access.', '[\"1762566350_690ea0ceeb5ca_3.jpg\",\"1762566350_690ea0ceef840_4.jpg\",\"1762566350_690ea0cef317e_5.jpg\"]', '10 Banay-Banay Rd., San Pedro, Laguna', 'Poblacion', 3, '2025-11-08', 'Condominium', 1, 14.3641522, 121.0592556, 'available'),
(10, 'APARTMENT27', 4500, 'EXAMPLE', '[\"1762570786_690eb2221b2f7_1.jpg\",\"1762570786_690eb22222349_2.jpg\",\"1762570786_690eb2222910f_3.jpg\"]', 'Blk 6 Lot 8, P. Burgos Street, Pacita 1, S.P.L', 'Pacita 1', 1, '2025-11-08', 'Low-rise apartment', 1, 14.3437149, 121.0577178, 'available'),
(11, 'STUDIO TYPE APARTMENT', 40000, 'House type: Apartment\r\nOffer type: For Rent\r\nCar parks: 2\r\nContract duration: 1 Years\r\nUsable area: 250 sqm\r\nProperty Floor: 2\r\n6 Jan 2026 - Published by DPI Properties\r\n₱ 40,000/month\r\nDescription\r\nFOR RENT: Furnished 2 storey house and lot at Concorde Village Parañaque.\r\n\r\nLot area: 260sqm\r\nFloor area: 250sqm\r\n\r\n- 6 Bedrooms\r\n- 1 Office room\r\n- Washing machine included\r\n- Air conditioned all rooms\r\n- Dining table and chairs included\r\n- Rooms with cabinets\r\n- Water heater\r\n- Wifi included\r\n\r\nRental rate: ₱40,000/month\r\n2 months advance, 2 months security deposit.\r\n\r\nDirect tenants only.\r\nFor inquiries call: View Phone Whatsapp Viber DPI PROPERTIES\r\nDetails\r\nGarage\r\nAir conditioning\r\nAlarm\r\nElectricity\r\nFully fenced\r\nEntertainment room\r\nInternet\r\nBalcony\r\nBuilt-in wardrobe\r\nCellar\r\nWifi\r\nView more\r\nCommunity characteristics\r\nSecurity\r\nGrill\r\nGreen area\r\n24 hours security\r\nGarden\r\nGuardhouse\r\nBasketball court', '[\"1769875585_697e2881da010_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zY2EyLTcyZWUtYjRmOS05MDkzNTA0OGRkNjAuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881dacb7_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zY2U0LTcxNzAtYmE3Yi1kZTI3ZGI3NTJlNGQuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881db828_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYmE0LTczODktYTkyZi02NWExYjk0ODk1YWIuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881de246_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYmYzLTcxNmEtOTM3My1jOWRiZTFiZDExOWIuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881deba7_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYTZlLTcxODItOWEzMi1kY2UyZmFiMTNjNGUuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881df47c_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYzJjLTcwYmYtYmE0ZC1iMTMxMjYyNmZlNTQuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881dfd81_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zYzYzLTcwNWItYjM1MC1lYzQwN2NlMjlmN2QuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881e0765_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zZDA5LTcwOGMtOWY4OS1iZjQwYTRhMWRhNzAuanBnIiwiYnJhbmQiOiJsYW.webp\",\"1769875585_697e2881e1472_eyJidWNrZXQiOiJwcmQtbGlmdWxsY29ubmVjdC1iYWNrZW5kLWIyYi1pbWFnZXMiLCJrZXkiOiJwcm9wZXJ0aWVzLzAxOWI5MWRjLTUzYzYtNzFiYS1iNTk4LTJhYjc1OGQ1NjE4Yy8wMTliOTFlMC0zZTQ0LTczYTktODQxYy0xNGExYWRmMjNjYzEuanBnIiwiYnJhbmQiOiJsYW.webp\"]', '123 Rizal St., San Pedro, Laguna', 'Pacita 2', 4, '2026-01-31', 'Single-family home', 1, 14.3670574, 121.0606474, 'available');

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
  `category` enum('Plumbing','Electrical','Appliances','Structural','Pest Control','Cleaning','Other') DEFAULT 'Other',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Pending','Approved','In Progress','Completed','Rejected') DEFAULT 'Pending',
  `requested_date` date DEFAULT curdate(),
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `landlord_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `content`, `file_path`, `file_type`, `file_size`, `content_type`, `status`, `created_at`, `updated_at`, `is_read`) VALUES
(1, 1, 1, 'Hi! I\'m interested in your property: Sunny Studio Apartment.', NULL, NULL, NULL, 'text', 'active', '2025-11-08 01:55:17', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `paymentstbl`
--

CREATE TABLE `paymentstbl` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `payment_type` enum('Rent','Security Deposit','Advance Rent','Utility','Penalty','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_method` enum('Cash','Bank Transfer','GCash','PayMaya','Cheque','Other') DEFAULT NULL,
  `status` enum('Pending','Paid','Overdue','Cancelled') DEFAULT 'Pending',
  `reference_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(65, 168, '0000-00-00', 'approved', 1, 1, 4, '2026-02-13', '2026-06-02', 'none', 'pending', 0);

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
  `lease_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requesttbl`
--

INSERT INTO `requesttbl` (`ID`, `date`, `tenant_id`, `listing_id`, `status`, `lease_id`) VALUES
(62, '2026-02-07', 1, 4, 'approved', NULL);

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
(0, 1, 1, 5, 'DSDFDFSDFSDGDGSDGFGDFGDFGDF', '2026-02-06 04:59:11', NULL);

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
(1, 'Jaja', 'Jahaziel', 'Sison', 'Bautusta', 'jahaziel.sison@cdsp.edu.ph', '$2y$10$PaxpUKQNdWKjJJXnBWfK1OonMOtoguy1iNaouAototdAtrxtXmqhK', NULL, '2004-08-07', 'Female', '1770343112_profile_551246401_1344915943899367_5892129137320406716_n.jpg', '2025-11-08 00:54:25', 'active', 2147483647);

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
(2, 1, '4d18388e047e300d3ba3a84c4f37faa3d1747ad346e1d8ba11e7333a9089ac32', '::1', '2025-12-13 22:07:02', 'tenant');

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
-- Indexes for table `leasetbl`
--
ALTER TABLE `leasetbl`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `listingtbl`
--
ALTER TABLE `listingtbl`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_listing_landlord` (`landlord_id`);

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
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `email_6` (`email`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `conversation_members`
--
ALTER TABLE `conversation_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `extension_requesttbl`
--
ALTER TABLE `extension_requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landlordtbl`
--
ALTER TABLE `landlordtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leasetbl`
--
ALTER TABLE `leasetbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `listingtbl`
--
ALTER TABLE `listingtbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `maintenance_attachmentstbl`
--
ALTER TABLE `maintenance_attachmentstbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_requeststbl`
--
ALTER TABLE `maintenance_requeststbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `paymentstbl`
--
ALTER TABLE `paymentstbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `renttbl`
--
ALTER TABLE `renttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `requesttbl`
--
ALTER TABLE `requesttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tenanttbl`
--
ALTER TABLE `tenanttbl`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `trusted_devices`
--
ALTER TABLE `trusted_devices`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `requesttbl`
--
ALTER TABLE `requesttbl`
  ADD CONSTRAINT `fk_request_listing` FOREIGN KEY (`listing_id`) REFERENCES `listingtbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenanttbl` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
