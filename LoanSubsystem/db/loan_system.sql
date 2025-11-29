-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 02:52 AM
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
-- Database: `loan_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `loan_type_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `job` varchar(255) DEFAULT NULL,
  `monthly_salary` decimal(10,2) DEFAULT NULL,
  `user_email` varchar(255) NOT NULL,
  `loan_terms` varchar(50) DEFAULT NULL,
  `loan_amount` decimal(12,2) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `monthly_payment` decimal(10,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `next_payment_due` date DEFAULT NULL,
  `rejected_by` varchar(255) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_remarks` text DEFAULT NULL,
  `proof_of_income` varchar(255) DEFAULT NULL,
  `coe_document` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `pdf_approved` varchar(255) DEFAULT NULL,
  `pdf_active` varchar(255) DEFAULT NULL,
  `pdf_rejected` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `loan_type_id`, `full_name`, `account_number`, `contact_number`, `email`, `job`, `monthly_salary`, `user_email`, `loan_terms`, `loan_amount`, `purpose`, `monthly_payment`, `due_date`, `status`, `remarks`, `file_name`, `created_at`, `approved_by`, `approved_at`, `next_payment_due`, `rejected_by`, `rejected_at`, `rejection_remarks`, `proof_of_income`, `coe_document`, `pdf_path`, `pdf_approved`, `pdf_active`, `pdf_rejected`) VALUES
(61, 2, 'Mike Beringuela', '1004567890', '09456789012', 'mikeberinguela@gmail.com', 'Project Manager', 70000.00, '', '12 Months', 7000.00, 'For purposes only', 648.44, '2026-11-29', 'Active', 'Dear Mike Beringuela,\n\nYour loan is now ACTIVE!\n\nPayment Details:\n- Monthly Payment: ₱648.44\n- First Payment Due: December 29, 2025\n- Final Payment: November 29, 2026\n\nActivated by: Jerome Malunes\nDate: 2025-11-29 09:45:59', 'uploads/692a5017799e3_loan_rejected_60_1764379593.pdf', '2025-11-29 01:44:55', 'Jerome Malunes', '2025-11-29 09:45:25', '2025-12-29', NULL, NULL, NULL, 'uploads/692a5017799e7_loan_active_58_1764379377.pdf', 'uploads/692a5017799e8_SIA_DOCU_Final.pdf', NULL, 'uploads/loan_approved_61_1764380731.pdf', 'uploads/loan_active_61_1764380782.pdf', NULL),
(62, 3, 'Mike Beringuela', '1004567890', '09456789012', 'mikeberinguela@gmail.com', 'Project Manager', 70000.00, '', '12 Months', 9000.00, 'For purposes only', 833.71, '2026-11-29', 'Pending', NULL, 'uploads/692a50d714171_Gemini_Generated_Image_ija02cija02cija0.png', '2025-11-29 01:48:07', NULL, NULL, '2025-12-29', NULL, NULL, NULL, 'uploads/692a50d714176_Gemini_Generated_Image_ija02cija02cija0.png', 'uploads/692a50d714178_loan_notification_approved_53_20251129010635.pdf', NULL, NULL, NULL, NULL),
(63, 4, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', '6 Months', 9000.00, 'For', 1588.71, '2026-05-29', 'Approved', 'Dear Kurt Realisan,\n\nCongratulations! Your loan application for ₱9,000.00 has been APPROVED.\n\nPlease visit our bank within 30 days to claim your loan.\n\nLoan Details:\n- Amount: ₱9,000.00\n- Term: 6 Months\n- Monthly Payment: ₱1,588.71\n\nApproved by: Jerome Malunes\nDate: 2025-11-29 09:50:14', 'uploads/692a51462fa0b_loan_active_61_1764380782.pdf', '2025-11-29 01:49:58', 'Jerome Malunes', '2025-11-29 09:50:14', '2025-12-29', NULL, NULL, NULL, 'uploads/692a51462fa11_loan_approved_61_1764380731.pdf', 'uploads/692a51462fa13_loan_active_58_1764379377.pdf', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_types`
--

CREATE TABLE `loan_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_types`
--

INSERT INTO `loan_types` (`id`, `name`) VALUES
(2, 'Car Loan'),
(3, 'Home Loan'),
(4, 'Multi-Purpose Loan'),
(1, 'Personal Loan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_types`
--
ALTER TABLE `loan_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `loan_types`
--
ALTER TABLE `loan_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
