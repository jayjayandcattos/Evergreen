-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2025 at 07:58 PM
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
-- Database: `evergreen_bank`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_customers`
--

CREATE TABLE `bank_customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city_province` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `birthday` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(100) DEFAULT NULL,
  `bank_id` varchar(50) DEFAULT NULL,
  `total_points` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified` tinyint(4) NOT NULL,
  `referral_code` varchar(6) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_points_earned` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_customers`
--

INSERT INTO `bank_customers` (`customer_id`, `first_name`, `middle_name`, `last_name`, `address`, `city_province`, `email`, `contact_number`, `birthday`, `password`, `verification_code`, `bank_id`, `total_points`, `created_at`, `is_verified`, `referral_code`, `referred_by`, `referral_points_earned`) VALUES
(13, 'Aaron', 'Cadorna', 'Pagente', '#42 COA KATABI NI MARCOS', 'Metro Manigga', 'aaronpagente19@gmail.com', '09611021573', '2005-02-21', '$2y$10$NAdL0TfJdjaMDjsdUtc2sunroa8tR7JPTYXJ6YyX2mMu84LQj8t.C', '279537', '6734', 10.00, '2025-10-26 09:51:16', 1, '5E9508', NULL, 0.00),
(27, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'karmaajoshh@gmail.com', '09611021573', '2010-10-10', '$2y$10$dyQYfe68PyEwqmwHf1YPcuFuddF6vkluuqjRUvRW8hb1WTqGFm6Iu', '130315', '5697', 2821.60, '2025-11-02 17:16:35', 1, 'AAB5DC', NULL, 0.00),
(37, 'George', 'Wong', 'Wang', '#66 Pasong Tamo QC', 'Metro Manila', 'wonggeorge062@gmail.com', '09611021573', '2004-10-10', '$2y$10$P6/7atb0WwR7XfjLa8wGU.kh8cQxM3UhmuZjuw2Tfex5gXwwlt2wi', '713732', '4025', 30.00, '2025-11-12 13:01:42', 1, 'DFF881', NULL, 0.00),
(39, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'karmaajosh47@gmail.com', '09611021573', '2004-10-10', '$2y$10$2frBaf8kl.J6j8VbxKQH8eWxy9o6jmljLvNpgQ44bzorIYzCwa6JK', '073406', '5551', 60.00, '2025-11-15 15:21:45', 1, 'MCA580', NULL, 0.00),
(41, 'tite', 'tite', 'tite', '#41 KATABINIPINTO', 'Metro Manigga', 'flores.augosteras.gapasin@gmail.com', '09611021573', '2025-11-12', '$2y$10$ok/qNK1Zs9foFv/9cV/H7Op1sQHttF4TY.cVbZcqfreEUl3EKjvpy', '585547', '4821', 30.00, '2025-11-19 06:21:02', 1, 'JSS407', NULL, 0.00),
(42, 'Christian Jay', 'Gazzingan', 'Mabbayad', '#75 - 1 Assistant St. Barangay Bahay Toro', 'Metro Manila', 'cjheyy1210@gmail.com', '09625898446', '2004-12-10', '$2y$10$DlLx2omLgudMy.0u8C9QZul9DAUHPESR.PSEgl2PQT98P.4RN/uj.', '321594', '2605', 10000.00, '2025-11-23 06:11:08', 1, 'GMP219', NULL, 0.00),
(43, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'nambio.johsua.agustin@gmail.com', '09611021573', '2005-10-10', '$2y$10$ijyFXvEogiQ5tM1TptdzoO8A2xZ8O9NYtY9c.SKRN00gNQUhpMAOC', '952490', '7666', 9200.00, '2025-11-24 03:37:00', 1, 'FNT628', NULL, 0.00),
(44, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Quezon City, Metro Manila', 'lilarew800@izeao.com', '09611021573', '2005-10-10', '$2y$10$BVGe9bjE2ti3lcrG6SKBpungLXvWs28EJd15f349NBzBwiG0fliOu', '053047', '2745', 30.00, '2025-11-24 12:23:02', 1, 'TTJ242', NULL, 0.00),
(45, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Quezon City, Metro Manila', 'frodfried4@gmail.com', '09611021573', '2005-10-10', '$2y$10$pR4Ej8dhwZbSmHcxV/WHIO8STwWn/iZnnULuzyVtncP.RXUbdZVym', '029805', '6292', 30.00, '2025-11-24 16:54:53', 1, 'FML752', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `missions`
--

CREATE TABLE `missions` (
  `customer_id` int(11) NOT NULL,
  `mission_text` varchar(255) NOT NULL,
  `points_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `missions`
--

INSERT INTO `missions` (`customer_id`, `mission_text`, `points_value`, `created_at`) VALUES
(1, 'Refer your first friend to EVERGREEN', 50.00, '2025-11-12 06:24:53'),
(2, 'Successfully refer 3 friends', 150.00, '2025-11-12 06:24:53'),
(3, 'Reach 5 successful referrals', 250.00, '2025-11-12 06:24:53'),
(4, 'Refer 10 friends and unlock premium rewards', 500.00, '2025-11-12 06:24:53'),
(5, 'Achieve 15 referrals milestone', 750.00, '2025-11-12 06:24:53'),
(6, 'Become a referral champion with 20 friends', 1000.00, '2025-11-12 06:24:53'),
(7, 'Share your referral code on social media', 30.00, '2025-11-12 06:24:53'),
(8, 'Have 3 friends use your referral code in one week', 200.00, '2025-11-12 06:24:53'),
(9, 'Reach 25 total referrals - Elite status', 1500.00, '2025-11-12 06:24:53'),
(10, 'Ultimate referrer - 50 successful referrals', 3000.00, '2025-11-12 06:24:53'),
(11, 'Refer a friend and earn bonus points', 20.00, '2025-11-11 15:50:20'),
(12, 'Use a referral code to get started', 10.00, '2025-11-11 15:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `points_history`
--

CREATE TABLE `points_history` (
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `transaction_type` enum('mission','redemption','referral','bonus') DEFAULT 'mission',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `points_history`
--

INSERT INTO `points_history` (`customer_id`, `user_id`, `points`, `description`, `transaction_type`, `created_at`) VALUES
(1, 27, -100.00, 'Redeemed: Mobile Load Bonus', 'redemption', '2025-11-12 21:05:06'),
(2, 38, 30.00, 'Share your referral code on social media', 'mission', '2025-11-12 21:35:02'),
(3, 27, -500.00, 'Redeemed: Home-credit discount', 'redemption', '2025-11-15 15:19:06'),
(4, 39, 30.00, 'Share your referral code on social media', 'mission', '2025-11-15 16:10:57'),
(5, 37, 50.00, 'Refer your first friend to EVERGREEN', 'mission', '2025-11-15 16:12:43'),
(6, 37, -100.00, 'Redeemed: Mobile Load Bonus', 'redemption', '2025-11-15 16:14:21'),
(7, 23, 50.00, 'Refer your first friend to EVERGREEN', 'mission', '2025-11-17 03:07:24'),
(8, 23, 30.00, 'Share your referral code on social media', 'mission', '2025-11-17 03:07:31'),
(9, 23, -100.00, 'Redeemed: Mobile Load Bonus', 'redemption', '2025-11-17 03:07:54'),
(10, 40, 30.00, 'Share your referral code on social media', 'mission', '2025-11-19 05:45:50'),
(11, 41, 30.00, 'Share your referral code on social media', 'mission', '2025-11-19 06:46:38'),
(12, 42, 30.00, 'Share your referral code on social media', 'mission', '2025-11-23 06:35:12'),
(13, 27, -500.00, 'Redeemed: Home-credit discount', 'redemption', '2025-11-23 10:04:21'),
(14, 27, -300.00, 'Redeemed: Gift Voucher', 'redemption', '2025-11-23 10:04:26'),
(15, 27, -300.00, 'Redeemed: Gift Voucher', 'redemption', '2025-11-24 02:43:38'),
(16, 27, -150.00, 'Redeemed: Fuel Discount', 'redemption', '2025-11-24 02:43:43'),
(17, 27, -200.00, 'Redeemed: Food Treat', 'redemption', '2025-11-24 02:56:09'),
(18, 27, -300.00, 'Redeemed: Gift Voucher', 'redemption', '2025-11-24 02:56:15'),
(19, 43, 30.00, 'Share your referral code on social media', 'mission', '2025-11-24 03:53:13'),
(20, 43, -500.00, 'Redeemed: Home-credit discount', 'redemption', '2025-11-24 03:54:31'),
(21, 43, -300.00, 'Redeemed: Gift Voucher', 'redemption', '2025-11-24 03:54:34'),
(22, 44, 30.00, 'Share your referral code on social media', 'mission', '2025-11-24 12:23:47'),
(23, 45, 30.00, 'Share your referral code on social media', 'mission', '2025-11-24 16:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `customer_id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `points_earned` decimal(10,2) DEFAULT 20.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`customer_id`, `referrer_id`, `referred_id`, `points_earned`, `created_at`) VALUES
(1, 23, 27, 20.00, '2025-11-11 15:56:37'),
(16, 27, 33, 20.00, '2025-11-11 16:54:46'),
(17, 27, 34, 50.00, '2025-11-11 17:00:31'),
(18, 27, 35, 50.00, '2025-11-11 17:02:50'),
(19, 27, 36, 20.00, '2025-11-11 17:04:48'),
(20, 27, 37, 20.00, '2025-11-12 13:24:33'),
(21, 27, 38, 20.00, '2025-11-12 21:34:46'),
(27, 37, 39, 20.00, '2025-11-15 15:57:34'),
(28, 37, 23, 20.00, '2025-11-17 03:53:34'),
(29, 39, 40, 20.00, '2025-11-19 07:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_missions`
--

CREATE TABLE `user_missions` (
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `points_earned` decimal(10,2) NOT NULL,
  `status` enum('pending','available','collected') DEFAULT 'pending',
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_missions`
--

INSERT INTO `user_missions` (`customer_id`, `user_id`, `mission_id`, `points_earned`, `status`, `completed_at`) VALUES
(21, 27, 11, 20.00, 'pending', '2025-11-11 16:54:46'),
(29, 37, 7, 30.00, 'collected', '2025-11-12 13:24:00'),
(31, 37, 12, 10.00, 'pending', '2025-11-12 13:24:33'),
(32, 27, 1, 50.00, 'collected', '2025-11-12 20:10:51'),
(33, 27, 2, 150.00, 'collected', '2025-11-12 20:10:56'),
(34, 27, 3, 250.00, 'collected', '2025-11-12 20:10:58'),
(35, 27, 7, 30.00, 'collected', '2025-11-12 20:11:03'),
(39, 37, 11, 20.00, 'pending', '2025-11-15 15:57:34'),
(40, 39, 12, 10.00, 'pending', '2025-11-15 15:57:34'),
(41, 39, 7, 30.00, 'collected', '2025-11-15 16:10:57'),
(42, 37, 1, 50.00, 'collected', '2025-11-15 16:12:43'),
(48, 41, 7, 30.00, 'collected', '2025-11-19 06:46:38'),
(49, 39, 11, 20.00, 'pending', '2025-11-19 07:14:38'),
(51, 42, 7, 30.00, 'collected', '2025-11-23 06:35:12'),
(52, 43, 7, 30.00, 'collected', '2025-11-24 03:53:13'),
(53, 44, 7, 30.00, 'collected', '2025-11-24 12:23:47'),
(55, 45, 7, 30.00, 'collected', '2025-11-24 16:55:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_customers`
--
ALTER TABLE `bank_customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `referral_code` (`referral_code`),
  ADD KEY `idx_referred_by` (`referred_by`),
  ADD KEY `idx_referral_code` (`referral_code`);

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `points_history`
--
ALTER TABLE `points_history`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `unique_referral` (`referred_id`);

--
-- Indexes for table `user_missions`
--
ALTER TABLE `user_missions`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `unique_user_mission` (`user_id`,`mission_id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_customers`
--
ALTER TABLE `bank_customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `points_history`
--
ALTER TABLE `points_history`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_missions`
--
ALTER TABLE `user_missions`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_missions`
--
ALTER TABLE `user_missions`
  ADD CONSTRAINT `user_missions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `bank_customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_missions_ibfk_2` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
