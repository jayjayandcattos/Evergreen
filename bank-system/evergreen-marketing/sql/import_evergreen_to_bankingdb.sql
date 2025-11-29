-- ========================================
-- IMPORT EVERGREEN_BANK DATA TO BankingDB
-- ========================================
-- This script imports all tables and data from evergreen_bank to BankingDB

USE BankingDB;

-- ========================================
-- TABLE: bank_customers
-- ========================================

CREATE TABLE IF NOT EXISTS `bank_customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `referral_code` varchar(6) DEFAULT NULL,
  `referred_by` int(11) DEFAULT NULL,
  `referral_points_earned` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `idx_referred_by` (`referred_by`),
  KEY `idx_bank_id` (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Import existing customer data
INSERT INTO `bank_customers` (`customer_id`, `first_name`, `middle_name`, `last_name`, `address`, `city_province`, `email`, `contact_number`, `birthday`, `password`, `verification_code`, `bank_id`, `total_points`, `created_at`, `is_verified`, `referral_code`, `referred_by`, `referral_points_earned`) VALUES
(13, 'Aaron', 'Cadorna', 'Pagente', '#42 COA KATABI NI MARCOS', 'Metro Manigga', 'aaronpagente19@gmail.com', '09611021573', '2005-02-21', '$2y$10$NAdL0TfJdjaMDjsdUtc2sunroa8tR7JPTYXJ6YyX2mMu84LQj8t.C', '279537', '6734', 10.00, '2025-10-26 09:51:16', 1, '5E9508', NULL, 0.00),
(27, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'karmaajoshh@gmail.com', '09611021573', '2010-10-10', '$2y$10$dyQYfe68PyEwqmwHf1YPcuFuddF6vkluuqjRUvRW8hb1WTqGFm6Iu', '130315', '5697', 4571.60, '2025-11-02 17:16:35', 1, 'AAB5DC', NULL, 0.00),
(37, 'George', 'Wong', 'Wang', '#66 Pasong Tamo QC', 'Metro Manila', 'wonggeorge062@gmail.com', '09611021573', '2004-10-10', '$2y$10$P6/7atb0WwR7XfjLa8wGU.kh8cQxM3UhmuZjuw2Tfex5gXwwlt2wi', '713732', '4025', 30.00, '2025-11-12 13:01:42', 1, 'DFF881', NULL, 0.00),
(39, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'karmaajosh47@gmail.com', '09611021573', '2004-10-10', '$2y$10$2frBaf8kl.J6j8VbxKQH8eWxy9o6jmljLvNpgQ44bzorIYzCwa6JK', '073406', '5551', 60.00, '2025-11-15 15:21:45', 1, 'MCA580', NULL, 0.00),
(40, 'Johsua', 'Agustin', 'Nambio', '#66 Pasong Tamo QC', 'Metro Manila', 'nambio.johsua.agustin@gmail.com', '09611021573', '2004-10-10', '$2y$10$eQnx9LLvYPMe0eUMLR9jUOiuhj7WbZkVaMHgUEPlVxB6iWL8bjgg6', '032247', '9057', 40.00, '2025-11-19 05:44:16', 1, 'WNH743', NULL, 0.00),
(41, 'tite', 'tite', 'tite', '#41 KATABINIPINTO', 'Metro Manigga', 'flores.augosteras.gapasin@gmail.com', '09611021573', '2025-11-12', '$2y$10$ok/qNK1Zs9foFv/9cV/H7Op1sQHttF4TY.cVbZcqfreEUl3EKjvpy', '585547', '4821', 30.00, '2025-11-19 06:21:02', 1, 'JSS407', NULL, 0.00);

-- Set AUTO_INCREMENT
ALTER TABLE `bank_customers` AUTO_INCREMENT = 42;

-- ========================================
-- TABLE: missions
-- ========================================

CREATE TABLE IF NOT EXISTS `missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mission_text` varchar(255) NOT NULL,
  `points_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Import missions data
INSERT INTO `missions` (`id`, `mission_text`, `points_value`, `created_at`) VALUES
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

ALTER TABLE `missions` AUTO_INCREMENT = 13;

-- ========================================
-- TABLE: points_history
-- ========================================

CREATE TABLE IF NOT EXISTS `points_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `transaction_type` enum('mission','redemption','referral','bonus') DEFAULT 'mission',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Import points history
INSERT INTO `points_history` (`id`, `user_id`, `points`, `description`, `transaction_type`, `created_at`) VALUES
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
(11, 41, 30.00, 'Share your referral code on social media', 'mission', '2025-11-19 06:46:38');

ALTER TABLE `points_history` AUTO_INCREMENT = 12;

-- ========================================
-- TABLE: referrals
-- ========================================

CREATE TABLE IF NOT EXISTS `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` int(11) NOT NULL,
  `referred_id` int(11) NOT NULL,
  `points_earned` decimal(10,2) DEFAULT 20.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_referral` (`referred_id`),
  KEY `idx_referrer_id` (`referrer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Import referrals
INSERT INTO `referrals` (`id`, `referrer_id`, `referred_id`, `points_earned`, `created_at`) VALUES
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

ALTER TABLE `referrals` AUTO_INCREMENT = 30;

-- ========================================
-- TABLE: user_missions
-- ========================================

CREATE TABLE IF NOT EXISTS `user_missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `points_earned` decimal(10,2) NOT NULL,
  `status` enum('pending','available','collected') DEFAULT 'pending',
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_mission` (`user_id`,`mission_id`),
  KEY `mission_id` (`mission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Import user missions
INSERT INTO `user_missions` (`id`, `user_id`, `mission_id`, `points_earned`, `status`, `completed_at`) VALUES
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
(47, 40, 7, 30.00, 'collected', '2025-11-19 05:45:50'),
(48, 41, 7, 30.00, 'collected', '2025-11-19 06:46:38'),
(49, 39, 11, 20.00, 'pending', '2025-11-19 07:14:38'),
(50, 40, 12, 10.00, 'pending', '2025-11-19 07:14:38');

ALTER TABLE `user_missions` AUTO_INCREMENT = 51;

-- ========================================
-- VERIFICATION
-- ========================================

SELECT 'Migration completed successfully!' AS status;

-- Verify data
SELECT 'bank_customers' AS table_name, COUNT(*) AS record_count FROM bank_customers;
SELECT 'missions' AS table_name, COUNT(*) AS record_count FROM missions;
SELECT 'points_history' AS table_name, COUNT(*) AS record_count FROM points_history;
SELECT 'referrals' AS table_name, COUNT(*) AS record_count FROM referrals;
SELECT 'user_missions' AS table_name, COUNT(*) AS record_count FROM user_missions;

SELECT '✓ All your login data has been imported to BankingDB!' AS message;
