-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2025 at 12:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hris_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicant`
--

CREATE TABLE `applicant` (
  `applicant_id` int(11) NOT NULL,
  `recruitment_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `resume_file` varchar(255) DEFAULT NULL,
  `application_status` varchar(20) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract`
--

CREATE TABLE `contract` (
  `contract_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `contract_type` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `benefits` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`department_id`, `department_name`, `description`) VALUES
(1, 'IT', 'Information Technology'),
(2, 'HR', 'Human Resources'),
(3, 'Finance', 'Finance Department'),
(4, 'Marketing', 'Marketing Department'),
(5, 'Operations', 'Operations Department');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position_id` int(11) DEFAULT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `employment_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `first_name`, `last_name`, `middle_name`, `gender`, `birth_date`, `contact_number`, `email`, `address`, `hire_date`, `department_id`, `position_id`, `contract_id`, `employment_status`) VALUES
(1, 'Admin', 'User', '', 'Male', '1990-01-01', '09171234567', 'admin@hris.com', 'Admin Office', '2025-10-26', 1, 1, NULL, 'Active'),
(6, 'tite', 'tite', 'tite', 'Male', '2025-10-01', '432423', 'flores.augosteras.gapasin@gmail.com', 'tite ko malaki', '2025-10-30', 4, 2, NULL, 'Inactive'),
(7, 'Justin', 'Rivera', 'S.', 'Male', '2005-11-13', '09996830717', 'rivera.justin.santilla@gmail.com', 'Katipunan Ext.', '2025-10-26', 1, 1, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `interview`
--

CREATE TABLE `interview` (
  `interview_id` int(11) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `interview_date` date DEFAULT NULL,
  `interview_result` varchar(20) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_request`
--

CREATE TABLE `leave_request` (
  `leave_request_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_days` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `approver_id` int(11) DEFAULT NULL,
  `date_requested` date DEFAULT NULL,
  `date_approved` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_type`
--

CREATE TABLE `leave_type` (
  `leave_type_id` int(11) NOT NULL,
  `leave_name` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `paid_unpaid` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `failure_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `username`, `ip_address`, `attempt_time`, `success`, `failure_reason`) VALUES
(42, 'admin', '::1', '2025-10-26 00:16:42', 0, 'Invalid password'),
(43, 'admin', '::1', '2025-10-26 00:16:47', 0, 'Invalid password'),
(44, 'admin', '::1', '2025-10-26 00:18:17', 1, NULL),
(45, 'admin', '::1', '2025-10-26 00:19:34', 1, NULL),
(46, 'admin', '::1', '2025-10-26 00:28:38', 1, NULL),
(47, 'admin', '::1', '2025-10-26 01:13:07', 1, NULL),
(48, 'admin', '::1', '2025-10-26 14:02:57', 0, 'Invalid password'),
(49, 'admin', '::1', '2025-10-26 14:03:01', 0, 'Invalid password'),
(50, 'admin', '::1', '2025-10-26 14:08:48', 1, NULL),
(51, 'admin', '::1', '2025-10-26 14:12:18', 1, NULL),
(52, 'admin', '::1', '2025-10-26 14:25:02', 1, NULL),
(53, 'admin', '::1', '2025-10-26 14:32:06', 1, NULL),
(54, 'admin', '::1', '2025-10-26 14:36:56', 1, NULL),
(55, 'admin', '::1', '2025-10-26 14:40:08', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `onboarding`
--

CREATE TABLE `onboarding` (
  `onboarding_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `completion_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslip`
--

CREATE TABLE `payslip` (
  `payslip_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `pay_period_start` date DEFAULT NULL,
  `pay_period_end` date DEFAULT NULL,
  `gross_salary` decimal(10,2) DEFAULT NULL,
  `deduction` decimal(10,2) DEFAULT NULL,
  `net_pay` decimal(10,2) DEFAULT NULL,
  `release_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `position`
--

CREATE TABLE `position` (
  `position_id` int(11) NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `job_description` varchar(255) DEFAULT NULL,
  `salary_grade` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position`
--

INSERT INTO `position` (`position_id`, `position_title`, `job_description`, `salary_grade`) VALUES
(1, 'Software Developer', 'Develops software applications', 5),
(2, 'HR Manager', 'Manages human resources', 7),
(3, 'Accountant', 'Handles financial records', 6),
(4, 'Marketing Specialist', 'Marketing campaigns', 5),
(5, 'Operations Manager', 'Manages operations', 8);

-- --------------------------------------------------------

--
-- Table structure for table `recruitment`
--

CREATE TABLE `recruitment` (
  `recruitment_id` int(11) NOT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `date_posted` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recruitment`
--

INSERT INTO `recruitment` (`recruitment_id`, `job_title`, `department_id`, `date_posted`, `status`, `posted_by`) VALUES
(1, 'Software Developer', 1, '2025-01-15', 'Open', 1),
(2, 'HR Assistant', 2, '2025-01-20', 'Open', 1),
(3, 'Accountant', 3, '2025-02-01', 'Open', 1),
(4, 'Marketing Specialist', 4, '2025-02-10', 'Open', 1),
(5, 'Operations Manager', 5, '2025-02-15', 'Open', 1),
(6, 'Senior Software Engineer', 1, '2025-03-01', 'Open', 1),
(7, 'Sales Executive', 4, '2025-03-15', 'Open', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `attendance_summary` text DEFAULT NULL,
  `recruitment_summary` text DEFAULT NULL,
  `leave_summary` text DEFAULT NULL,
  `payroll_summary` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `log_level` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
  `log_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `log_level`, `log_type`, `user_id`, `employee_id`, `ip_address`, `user_agent`, `action`, `details`, `request_data`, `created_at`) VALUES
(1, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 00:18:36'),
(2, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 00:18:42'),
(3, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 00:19:34'),
(4, 'INFO', 'ATTENDANCE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Time-out recorded', 'Attendance ID: 8', NULL, '2025-10-26 00:19:47'),
(5, 'INFO', 'ATTENDANCE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Time-out recorded', 'Attendance ID: 7', NULL, '2025-10-26 00:19:52'),
(6, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 00:26:43'),
(7, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 00:27:39'),
(8, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 00:28:38'),
(9, 'INFO', 'EMPLOYEE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Employee archived', 'Employee ID: 2', NULL, '2025-10-26 00:28:47'),
(10, 'INFO', 'CALENDAR', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Event added', 'Event: tite', NULL, '2025-10-26 00:38:09'),
(11, 'INFO', 'CALENDAR', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Event deleted', 'ID: 1', NULL, '2025-10-26 00:38:23'),
(12, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant added', 'Name: tite', NULL, '2025-10-26 00:39:26'),
(13, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant status updated', 'ID: 1, Status: To Interview', NULL, '2025-10-26 00:39:40'),
(14, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant status updated', 'ID: 1, Status: To Interview', NULL, '2025-10-26 00:44:46'),
(15, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant status updated', 'ID: 1, Status: Rejected', NULL, '2025-10-26 00:44:56'),
(16, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant status updated', 'ID: 1, Status: Hired', NULL, '2025-10-26 01:00:11'),
(17, 'ERROR', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Failed to archive applicant', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'archived_at\' in \'field list\'', NULL, '2025-10-26 01:04:18'),
(18, 'ERROR', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Failed to archive applicant', 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'archived_at\' in \'field list\'', NULL, '2025-10-26 01:04:25'),
(19, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant archived', 'Applicant ID: 1', NULL, '2025-10-26 01:05:10'),
(20, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant unarchived', 'Applicant ID: 1', NULL, '2025-10-26 01:05:21'),
(21, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 01:05:32'),
(22, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 01:12:19'),
(23, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 01:13:07'),
(24, 'INFO', 'EMPLOYEE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Employee added', 'Name: tite tite', '{\"department_id\":\"4\",\"position_id\":\"2\"}', '2025-10-26 01:14:03'),
(25, 'INFO', 'EMPLOYEE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Employee archived', 'Employee ID: 6', NULL, '2025-10-26 01:14:14'),
(26, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant status updated', 'ID: 1, Status: To Interview', NULL, '2025-10-26 01:15:16'),
(27, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant archived', 'Applicant ID: 1', NULL, '2025-10-26 01:15:27'),
(28, 'INFO', 'RECRUITMENT', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Applicant deleted', 'Applicant ID: 1', NULL, '2025-10-26 01:15:35'),
(29, 'INFO', 'CALENDAR', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Event added', 'Event: tite', NULL, '2025-10-26 01:16:12'),
(30, 'INFO', 'CALENDAR', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'Event deleted', 'ID: 8', NULL, '2025-10-26 01:16:25'),
(31, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 01:16:31'),
(32, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:08:48'),
(33, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:09:40'),
(34, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:10:06'),
(35, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 14:10:14'),
(36, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:12:18'),
(37, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 14:12:48'),
(38, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:25:02'),
(39, 'INFO', 'EMPLOYEE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Employee added', 'Name: Justin Rivera', '{\"department_id\":\"1\",\"position_id\":\"1\"}', '2025-10-26 14:30:38'),
(40, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 14:30:43'),
(41, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:32:06'),
(42, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 14:32:13'),
(43, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:36:56'),
(44, 'INFO', 'AUTH', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'User logged out', 'Username: admin, IP: ::1', NULL, '2025-10-26 14:39:13'),
(45, 'DEBUG', 'PAGE', 1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'Dashboard accessed', 'User: admin', NULL, '2025-10-26 14:40:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_account`
--

CREATE TABLE `user_account` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_account`
--

INSERT INTO `user_account` (`user_id`, `employee_id`, `username`, `password_hash`, `role`, `last_login`) VALUES
(1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2025-10-26 22:40:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicant`
--
ALTER TABLE `applicant`
  ADD PRIMARY KEY (`applicant_id`),
  ADD KEY `recruitment_id` (`recruitment_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_employee_date` (`employee_id`,`date`);

--
-- Indexes for table `contract`
--
ALTER TABLE `contract`
  ADD PRIMARY KEY (`contract_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `idx_employment_status` (`employment_status`);

--
-- Indexes for table `interview`
--
ALTER TABLE `interview`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `interviewer_id` (`interviewer_id`);

--
-- Indexes for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD PRIMARY KEY (`leave_request_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_type`
--
ALTER TABLE `leave_type`
  ADD PRIMARY KEY (`leave_type_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempt_time` (`attempt_time`);

--
-- Indexes for table `onboarding`
--
ALTER TABLE `onboarding`
  ADD PRIMARY KEY (`onboarding_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `payslip`
--
ALTER TABLE `payslip`
  ADD PRIMARY KEY (`payslip_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `position`
--
ALTER TABLE `position`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `recruitment`
--
ALTER TABLE `recruitment`
  ADD PRIMARY KEY (`recruitment_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_log_level` (`log_level`),
  ADD KEY `idx_log_type` (`log_type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `user_account`
--
ALTER TABLE `user_account`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicant`
--
ALTER TABLE `applicant`
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract`
--
ALTER TABLE `contract`
  MODIFY `contract_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `interview`
--
ALTER TABLE `interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_request`
--
ALTER TABLE `leave_request`
  MODIFY `leave_request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_type`
--
ALTER TABLE `leave_type`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `onboarding`
--
ALTER TABLE `onboarding`
  MODIFY `onboarding_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslip`
--
ALTER TABLE `payslip`
  MODIFY `payslip_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `position`
--
ALTER TABLE `position`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `recruitment`
--
ALTER TABLE `recruitment`
  MODIFY `recruitment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `user_account`
--
ALTER TABLE `user_account`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant`
--
ALTER TABLE `applicant`
  ADD CONSTRAINT `applicant_ibfk_1` FOREIGN KEY (`recruitment_id`) REFERENCES `recruitment` (`recruitment_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `contract`
--
ALTER TABLE `contract`
  ADD CONSTRAINT `contract_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `employee_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`),
  ADD CONSTRAINT `employee_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `position` (`position_id`);

--
-- Constraints for table `interview`
--
ALTER TABLE `interview`
  ADD CONSTRAINT `interview_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicant` (`applicant_id`),
  ADD CONSTRAINT `interview_ibfk_2` FOREIGN KEY (`interviewer_id`) REFERENCES `employee` (`employee_id`);

--
-- Constraints for table `leave_request`
--
ALTER TABLE `leave_request`
  ADD CONSTRAINT `leave_request_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_request_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_type` (`leave_type_id`);

--
-- Constraints for table `onboarding`
--
ALTER TABLE `onboarding`
  ADD CONSTRAINT `onboarding_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `onboarding_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `payslip`
--
ALTER TABLE `payslip`
  ADD CONSTRAINT `payslip_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `recruitment`
--
ALTER TABLE `recruitment`
  ADD CONSTRAINT `recruitment_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_account` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `system_logs_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_account`
--
ALTER TABLE `user_account`
  ADD CONSTRAINT `user_account_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employee` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
