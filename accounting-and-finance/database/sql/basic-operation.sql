-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 03:37 AM
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
-- Database: `basic-operation`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_number` varchar(30) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`account_id`, `customer_id`, `account_number`, `account_type_id`, `interest_rate`, `last_interest_date`, `is_locked`, `created_at`, `created_by_employee_id`) VALUES
(1, 1, '1000000001', 1, 0.50, NULL, 0, '2025-10-23 11:22:37', 1),
(2, 2, '2000000001', 2, NULL, NULL, 0, '2025-10-23 11:22:37', 2),
(3, 3, '3000000001', 3, 2.50, '2023-01-01', 0, '2025-10-23 11:22:38', 2),
(4, 4, '4000000001', 1, 0.50, NULL, 0, '2025-10-23 11:22:38', 3),
(5, 1, '1000000002', 4, NULL, NULL, 1, '2025-10-23 11:22:38', 1),
(6, 5, '5000000001', 1, 2.50, NULL, 0, '2025-10-24 01:57:27', 3),
(8, 5, '5000000002', 2, NULL, NULL, 0, '2025-10-24 05:22:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `account_types`
--

CREATE TABLE `account_types` (
  `account_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_types`
--

INSERT INTO `account_types` (`account_type_id`, `type_name`, `description`) VALUES
(1, 'Savings', 'Standard savings account with interest.'),
(2, 'Checking', 'Account for daily transactions, no interest.'),
(3, 'Fixed Deposit', 'Time-locked deposit with higher interest.'),
(4, 'Loan', 'A credit facility provided by the bank.');

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_line` varchar(200) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `address_type` varchar(20) DEFAULT 'home',
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `customer_id`, `address_line`, `city`, `province_id`, `postal_code`, `address_type`, `is_primary`, `created_at`) VALUES
(1, 1, '123 Sampaguita St., Brgy. San Jose', 'Quezon City', 1, '1100', 'home', 1, '2025-10-23 11:22:37'),
(2, 2, '456 Narra Ave., Brgy. Poblacion', 'Cebu City', 2, '6000', 'home', 1, '2025-10-23 11:22:37'),
(3, 3, '789 Oakwood Dr', 'Los Angeles', 4, '90001', 'home', 1, '2025-10-23 11:22:37'),
(4, 4, '101 Molave St., Brgy. Centro', 'Davao City', 3, '8000', 'home', 1, '2025-10-23 11:22:37'),
(5, 5, '123 Main St', 'Manila', 1, '1000', 'home', 0, '2025-10-23 13:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `last_name`, `first_name`, `middle_name`, `password_hash`, `created_at`, `created_by_employee_id`) VALUES
(1, 'Dela Cruz', 'Juan', 'Santos', '$2b$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPjYf5z5z6', '2025-10-23 11:22:37', 1),
(2, 'Reyes', 'Maria', 'Garcia', '$2y$10$wE9L0Z6D8YvM5fN7j2R4O.k9F0l1M2t3.A4b5C6d7E8F9g0h1', '2025-10-23 11:22:37', 1),
(3, 'Lee', 'Kevin', NULL, '$2y$10$wE9L0Z6D8YvM5fN7j2R4O.k9F0l1M2t3.A4b5C6d7E8F9g0h1', '2025-10-23 11:22:37', 2),
(4, 'Chua', 'Sarah', 'Lim', '$2y$10$wE9L0Z6D8YvM5fN7j2R4O.k9F0l1M2t3.A4b5C6d7E8F9g0h1', '2025-10-23 11:22:37', 3),
(5, 'Santos', 'Diego', 'Reyes', '$2y$10$wtcGCO6Cze3SWb/sa0iwm.tw8f9ERXCcKwLRRfXHVgReqb0.uFVdK', '2025-10-23 13:19:32', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer_linked_accounts`
--

CREATE TABLE `customer_linked_accounts` (
  `link_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_linked_accounts`
--

INSERT INTO `customer_linked_accounts` (`link_id`, `customer_id`, `account_id`, `linked_at`, `is_active`) VALUES
(2, 5, 6, '2025-10-24 05:22:16', 1),
(3, 5, 8, '2025-10-24 05:48:41', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer_profiles`
--

CREATE TABLE `customer_profiles` (
  `profile_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed','other') DEFAULT 'single',
  `national_id` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `income_range` varchar(50) DEFAULT NULL,
  `preferred_language` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `loyalty_member` tinyint(1) DEFAULT 0,
  `profile_created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_profiles`
--

INSERT INTO `customer_profiles` (`profile_id`, `customer_id`, `gender_id`, `date_of_birth`, `marital_status`, `national_id`, `occupation`, `company`, `income_range`, `preferred_language`, `nationality`, `loyalty_member`, `profile_created_at`) VALUES
(1, 1, 1, '1990-05-15', 'single', 'PH123456789', 'Software Engineer', 'Tech Solutions Inc.', '50k-100k', 'English', 'Filipino', 1, '2025-10-23 11:22:38'),
(2, 2, 2, '1988-11-22', 'married', 'PH987654321', 'Marketing Manager', 'Global Brands', '100k-200k', 'English', 'Filipino', 1, '2025-10-23 11:22:38'),
(3, 3, 1, '1995-03-01', 'single', 'US543210987', 'Data Analyst', 'DataCorp', '50k-100k', 'English', 'American', 0, '2025-10-23 11:22:38'),
(4, 4, 2, '1992-08-10', 'married', 'PH112233445', 'Accountant', 'XYZ Accounting', '50k-100k', 'Filipino', 'Filipino', 1, '2025-10-23 11:22:38');

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `email_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emails`
--

INSERT INTO `emails` (`email_id`, `customer_id`, `email`, `is_primary`, `created_at`) VALUES
(1, 1, 'juan.delacruz@example.com', 1, '2025-10-23 11:22:37'),
(2, 1, 'juan.personal@mail.com', 0, '2025-10-23 11:22:37'),
(3, 2, 'maria.reyes@example.com', 1, '2025-10-23 11:22:37'),
(4, 3, 'kevin.lee@email.com', 1, '2025-10-23 11:22:37'),
(5, 4, 'sarah.chua@domain.net', 1, '2025-10-23 11:22:37'),
(6, 5, 'diego@example.com', 0, '2025-10-23 13:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `employee_name`, `created_at`) VALUES
(1, 'Alice Smith', '2025-10-23 11:22:37'),
(2, 'Bob Johnson', '2025-10-23 11:22:37'),
(3, 'Charlie Brown', '2025-10-23 11:22:37');

-- --------------------------------------------------------

--
-- Table structure for table `genders`
--

CREATE TABLE `genders` (
  `gender_id` int(11) NOT NULL,
  `gender_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `genders`
--

INSERT INTO `genders` (`gender_id`, `gender_name`) VALUES
(2, 'Female'),
(1, 'Male'),
(3, 'Non-binary'),
(4, 'Prefer not to say');

-- --------------------------------------------------------

--
-- Table structure for table `phones`
--

CREATE TABLE `phones` (
  `phone_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `phone_type` varchar(20) DEFAULT 'mobile',
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phones`
--

INSERT INTO `phones` (`phone_id`, `customer_id`, `phone_number`, `phone_type`, `is_primary`, `created_at`) VALUES
(1, 1, '09171234567', 'mobile', 1, '2025-10-23 11:22:37'),
(2, 1, '0281234567', 'landline', 0, '2025-10-23 11:22:37'),
(3, 2, '09209876543', 'mobile', 1, '2025-10-23 11:22:37'),
(4, 3, '+1-555-123-4567', 'mobile', 1, '2025-10-23 11:22:37'),
(5, 4, '09987654321', 'mobile', 1, '2025-10-23 11:22:37'),
(6, 5, '+63-912-345-6789', 'mobile', 0, '2025-10-23 13:19:32');

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `province_id` int(11) NOT NULL,
  `province_name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'Philippines'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`province_id`, `province_name`, `country`) VALUES
(1, 'Metro Manila', 'Philippines'),
(2, 'Cebu', 'Philippines'),
(3, 'Davao del Sur', 'Philippines'),
(4, 'California', 'USA'),
(5, 'Ontario', 'Canada');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_ref` varchar(50) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `related_account_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `transaction_ref`, `account_id`, `transaction_type_id`, `amount`, `related_account_id`, `description`, `employee_id`, `created_at`) VALUES
(1, '', 1, 1, 50000.00, NULL, 'Initial Deposit', 1, '2025-10-23 11:22:38'),
(2, '', 2, 1, 25000.00, NULL, 'Initial Deposit', 2, '2025-10-23 11:22:38'),
(3, '', 3, 1, 100000.00, NULL, 'Fixed Deposit Placement', 2, '2025-10-23 11:22:38'),
(4, '', 1, 2, 5000.00, NULL, 'ATM Withdrawal', NULL, '2025-10-23 11:22:38'),
(5, '', 2, 3, 2000.00, 1, 'Payment to Juan', 1, '2025-10-23 11:22:38'),
(6, '', 1, 4, 2000.00, 2, 'Received from Maria', 1, '2025-10-23 11:22:38'),
(7, '', 4, 1, 15000.00, NULL, 'Cash Deposit', 3, '2025-10-23 11:22:38'),
(8, '', 6, 1, 1000.00, NULL, 'Deposit Money', 1, '2025-10-24 03:20:39'),
(9, '', 6, 2, 100.00, NULL, 'Withdrawal', 1, '2025-10-24 04:39:51'),
(10, 'TXN-20251025190239-128310', 6, 3, 200.00, 8, '', NULL, '2025-10-25 17:02:39'),
(11, NULL, 6, 7, 15.00, NULL, 'Transaction Fee - TXN-20251025190239-128310', NULL, '2025-10-25 17:02:39'),
(12, 'TXN-20251025190239-128310', 8, 4, 200.00, 6, '', NULL, '2025-10-25 17:02:39'),
(13, 'TXN-20251025194321-1D9DF9', 6, 3, 1000.00, 8, 'BWAHAHAHA', NULL, '2025-10-25 17:43:21'),
(14, NULL, 6, 7, 15.00, NULL, 'Transaction Fee - TXN-20251025194321-1D9DF9', NULL, '2025-10-25 17:43:21'),
(15, 'TXN-20251025194321-1D9DF9', 8, 4, 1000.00, 6, 'BWAHAHAHA', NULL, '2025-10-25 17:43:21'),
(16, 'TXN-20251025194732-DBCF10', 8, 3, 600.00, 6, '', NULL, '2025-10-25 17:47:32'),
(17, NULL, 8, 7, 15.00, NULL, 'Transaction Fee - TXN-20251025194732-DBCF10', NULL, '2025-10-25 17:47:32'),
(18, 'TXN-20251025194732-DBCF10', 6, 4, 600.00, 8, '', NULL, '2025-10-25 17:47:32');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `transaction_type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`transaction_type_id`, `type_name`, `description`) VALUES
(1, 'Deposit', 'Adding funds to an account.'),
(2, 'Withdrawal', 'Removing funds from an account.'),
(3, 'Transfer Out', 'Sending funds to another account.'),
(4, 'Transfer In', 'Receiving funds from another account.'),
(5, 'Interest Payment', 'Interest credited to account.'),
(6, 'Loan Payment', 'Payment made towards a loan.'),
(7, 'Fee', 'Money deducted from an account for specific services, charges, or penalties');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `account_type_id` (`account_type_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `account_types`
--
ALTER TABLE `account_types`
  ADD PRIMARY KEY (`account_type_id`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `created_by_employee_id` (`created_by_employee_id`);

--
-- Indexes for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  ADD PRIMARY KEY (`link_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`account_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `gender_id` (`gender_id`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`email_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`email`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `genders`
--
ALTER TABLE `genders`
  ADD PRIMARY KEY (`gender_id`),
  ADD UNIQUE KEY `gender_name` (`gender_name`);

--
-- Indexes for table `phones`
--
ALTER TABLE `phones`
  ADD PRIMARY KEY (`phone_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`phone_number`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`province_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `related_account_id` (`related_account_id`),
  ADD KEY `transaction_type_id` (`transaction_type_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`transaction_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `account_types`
--
ALTER TABLE `account_types`
  MODIFY `account_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `genders`
--
ALTER TABLE `genders`
  MODIFY `gender_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `phones`
--
ALTER TABLE `phones`
  MODIFY `phone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `province_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `transaction_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `accounts_ibfk_2` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`account_type_id`),
  ADD CONSTRAINT `accounts_ibfk_3` FOREIGN KEY (`created_by_employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `addresses_ibfk_2` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by_employee_id`) REFERENCES `employees` (`employee_id`);

--
-- Constraints for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  ADD CONSTRAINT `customer_linked_accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `customer_linked_accounts_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`);

--
-- Constraints for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD CONSTRAINT `customer_profiles_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `customer_profiles_ibfk_2` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`gender_id`);

--
-- Constraints for table `emails`
--
ALTER TABLE `emails`
  ADD CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `phones`
--
ALTER TABLE `phones`
  ADD CONSTRAINT `phones_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`related_account_id`) REFERENCES `accounts` (`account_id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`transaction_type_id`) REFERENCES `transaction_types` (`transaction_type_id`),
  ADD CONSTRAINT `transactions_ibfk_4` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
