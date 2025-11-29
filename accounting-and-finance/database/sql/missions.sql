-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 07:19 AM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
