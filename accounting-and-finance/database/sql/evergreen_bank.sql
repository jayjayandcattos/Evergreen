-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2025 at 01:55 PM
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
  `id` int(11) NOT NULL,
  `mission_text` varchar(255) NOT NULL,
  `points_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `missions`
--

INSERT INTO `missions` (`id`, `mission_text`, `points_value`, `created_at`) VALUES
(1, 'Spend ₱200 with your EVERGREEN Card and earn 10 reward points.', 10.00, '2025-11-02 12:35:47'),
(2, 'Use your card five times this week and get 50 bonus points.', 1.60, '2025-11-02 12:35:47'),
(3, 'Make a purchase of ₱500 or more in a single transaction.', 25.00, '2025-11-02 12:35:47'),
(4, 'Refer a friend and earn points when they make their first purchase.', 15.00, '2025-11-02 12:35:47'),
(5, 'Complete your profile information and verify your email address.', 30.00, '2025-11-02 12:35:47'),
(6, 'Shop at any partner store this weekend for bonus rewards.', 20.00, '2025-11-02 12:35:47'),
(7, 'Reach ₱1,000 in total spending this month for a special bonus.', 50.00, '2025-11-02 12:35:47'),
(8, 'Download and use the EVERGREEN mobile app for the first time.', 12.00, '2025-11-02 12:35:47'),
(9, 'Leave a review for any product you\'ve purchased this month.', 18.00, '2025-11-02 12:35:47'),
(10, 'Celebrate your membership anniversary - special loyalty bonus!', 40.00, '2025-11-02 12:35:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `is_verified` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `address`, `city_province`, `email`, `contact_number`, `birthday`, `password`, `verification_code`, `bank_id`, `total_points`, `created_at`, `is_verified`) VALUES
(13, 'Aaron', 'Cadorna', 'Pagente', '#42 COA KATABI NI MARCOS', 'Metro Manigga', 'aaronpagente19@gmail.com', '09611021573', '2005-02-21', '$2y$10$NAdL0TfJdjaMDjsdUtc2sunroa8tR7JPTYXJ6YyX2mMu84LQj8t.C', '279537', '6734', 10.00, '2025-10-26 09:51:16', 1),
(23, 'Johsua', 'Agustin', 'Nambio', '#42 TAGADYANLANG', 'Metro Manigga', 'nambio.johsua.agustin@gmail.com', '09611021573', '2004-10-01', '$2y$10$1DudcF0MoLamUZ/aIPqcyuQoAsChBZ0mGZSqDsxe1wrcC7NA8bKOS', '068214', '6968', 0.00, '2025-11-02 17:05:37', 1),
(27, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'karmaajoshh@gmail.com', '09611021573', '2010-10-10', '$2y$10$3lsDV5LkaNsXnn9lr.8kv.02F520vRYV8bS.bR3IUf/aCTFRWKC4y', '130315', '5697', 50.00, '2025-11-02 17:16:35', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_missions`
--

CREATE TABLE `user_missions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `points_earned` decimal(10,2) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_missions`
--

INSERT INTO `user_missions` (`id`, `user_id`, `mission_id`, `points_earned`, `completed_at`) VALUES
(6, 13, 1, 10.00, '2025-11-02 13:03:21'),
(7, 27, 1, 10.00, '2025-11-03 05:07:51'),
(8, 27, 3, 25.00, '2025-11-03 05:09:10'),
(9, 27, 4, 15.00, '2025-11-03 06:19:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_missions`
--
ALTER TABLE `user_missions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_mission` (`user_id`,`mission_id`),
  ADD KEY `mission_id` (`mission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user_missions`
--
ALTER TABLE `user_missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_missions`
--
ALTER TABLE `user_missions`
  ADD CONSTRAINT `user_missions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_missions_ibfk_2` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
