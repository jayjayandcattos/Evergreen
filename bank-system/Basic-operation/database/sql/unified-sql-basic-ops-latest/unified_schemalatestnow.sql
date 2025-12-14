-- ========================================
-- UNIFIED DATABASE SCHEMA
-- ========================================
-- This file contains the complete unified database schema
-- Merged from: schema.sql, hris_system.sql, evergreen_bank.sql, basic-operation.sql, bank_loan.sql
-- 
-- Database Name: BankingDB
-- Professional database for comprehensive banking and financial management
--
-- ========================================
-- DATABASE CREATION
-- ========================================

DROP DATABASE IF EXISTS BankingDB;
CREATE DATABASE BankingDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE BankingDB;

-- ========================================
-- CORE USERS AND AUTHENTICATION
-- ========================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    attempt_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT 0,
    failure_reason VARCHAR(255) DEFAULT NULL,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time)
);

CREATE TABLE user_account (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    username VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    role VARCHAR(20) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    UNIQUE KEY username (username),
    INDEX idx_employee_id (employee_id)
);

-- ========================================
-- HRIS MODULE
-- ========================================

CREATE TABLE department (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE `position` (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    position_title VARCHAR(100) NOT NULL,
    job_description VARCHAR(255) DEFAULT NULL,
    salary_grade INT DEFAULT NULL
);

CREATE TABLE employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) DEFAULT NULL,
    last_name VARCHAR(50) DEFAULT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    gender VARCHAR(10) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    house_number VARCHAR(50) DEFAULT NULL,
    street VARCHAR(100) DEFAULT NULL,
    barangay VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    province VARCHAR(100) DEFAULT NULL,
    secondary_email VARCHAR(100) DEFAULT NULL,
    secondary_contact_number VARCHAR(20) DEFAULT NULL,
    hire_date DATE DEFAULT NULL,
    department_id INT DEFAULT NULL,
    position_id INT DEFAULT NULL,
    contract_id INT DEFAULT NULL,
    employment_status VARCHAR(20) DEFAULT NULL,
    INDEX idx_department_id (department_id),
    INDEX idx_position_id (position_id),
    INDEX idx_employment_status (employment_status),
    FOREIGN KEY (department_id) REFERENCES department(department_id),
    FOREIGN KEY (position_id) REFERENCES `position`(position_id)
);

CREATE TABLE employee_refs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_employee_no VARCHAR(100) NOT NULL,
    name VARCHAR(200),
    department VARCHAR(100),
    position VARCHAR(100),
    base_monthly_salary DECIMAL(12,2) DEFAULT 0.00,
    employment_type ENUM('regular','contract','part-time') DEFAULT 'regular',
    external_source VARCHAR(100) DEFAULT 'HRIS',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (external_employee_no, external_source),
    INDEX idx_external_no (external_employee_no)
);

CREATE TABLE employee_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_external_no VARCHAR(100) NOT NULL,
    attendance_date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    status ENUM('present','absent','late','leave','half_day') DEFAULT 'present',
    hours_worked DECIMAL(4,2) DEFAULT 0.00,
    overtime_hours DECIMAL(4,2) DEFAULT 0.00,
    late_minutes INT DEFAULT 0,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_external_no, attendance_date)
);

CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    date DATE DEFAULT NULL,
    time_in DATETIME DEFAULT NULL,
    time_out DATETIME DEFAULT NULL,
    total_hours DECIMAL(5,2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    INDEX idx_date (date),
    INDEX idx_employee_date (employee_id, date),
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE
);

CREATE TABLE contract (
    contract_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    contract_type VARCHAR(50) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    salary DECIMAL(10,2) DEFAULT NULL,
    benefits VARCHAR(255) DEFAULT NULL,
    INDEX idx_employee_id (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE
);

CREATE TABLE leave_type (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_name VARCHAR(100) DEFAULT NULL,
    purpose VARCHAR(255) DEFAULT NULL,
    duration VARCHAR(50) DEFAULT NULL,
    paid_unpaid VARCHAR(20) DEFAULT NULL
);

CREATE TABLE leave_request (
    leave_request_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    leave_type_id INT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    total_days INT DEFAULT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT NULL,
    approver_id INT DEFAULT NULL,
    date_requested DATE DEFAULT NULL,
    date_approved DATE DEFAULT NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_leave_type_id (leave_type_id),
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_type(leave_type_id)
);

CREATE TABLE onboarding (
    onboarding_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    department_id INT DEFAULT NULL,
    completion_status VARCHAR(20) DEFAULT NULL,
    INDEX idx_employee_id (employee_id),
    INDEX idx_department_id (department_id),
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

CREATE TABLE recruitment (
    recruitment_id INT AUTO_INCREMENT PRIMARY KEY,
    job_title VARCHAR(100) DEFAULT NULL,
    department_id INT DEFAULT NULL,
    date_posted DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT NULL,
    posted_by INT DEFAULT NULL,
    INDEX idx_department_id (department_id),
    FOREIGN KEY (department_id) REFERENCES department(department_id)
);

CREATE TABLE applicant (
    applicant_id INT AUTO_INCREMENT PRIMARY KEY,
    recruitment_id INT DEFAULT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    resume_file VARCHAR(255) DEFAULT NULL,
    application_status VARCHAR(20) DEFAULT NULL,
    archived_at DATETIME DEFAULT NULL,
    offer_status ENUM('Pending', 'Accepted', 'Declined') DEFAULT 'Pending',
    offer_token VARCHAR(100) UNIQUE DEFAULT NULL,
    offer_sent_at DATETIME DEFAULT NULL,
    offer_acceptance_timestamp DATETIME DEFAULT NULL,
    offer_declined_at DATETIME DEFAULT NULL,
    INDEX idx_recruitment_id (recruitment_id),
     INDEX idx_offer_token (offer_token),
    FOREIGN KEY (recruitment_id) REFERENCES recruitment(recruitment_id)
);

CREATE TABLE interview (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT DEFAULT NULL,
    interviewer_id INT DEFAULT NULL,
    interview_date DATE DEFAULT NULL,
    interview_result VARCHAR(20) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    INDEX idx_applicant_id (applicant_id),
    INDEX idx_interviewer_id (interviewer_id),
    FOREIGN KEY (applicant_id) REFERENCES applicant(applicant_id),
    FOREIGN KEY (interviewer_id) REFERENCES employee(employee_id)
);

CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_summary TEXT DEFAULT NULL,
    recruitment_summary TEXT DEFAULT NULL,
    leave_summary TEXT DEFAULT NULL,
    payroll_summary TEXT DEFAULT NULL
);

CREATE TABLE system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    log_level ENUM('DEBUG','INFO','WARNING','ERROR','CRITICAL') NOT NULL,
    log_type VARCHAR(50) NOT NULL,
    user_id INT DEFAULT NULL,
    employee_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    request_data JSON DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_level (log_level),
    INDEX idx_log_type (log_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_employee_id (employee_id),
    FOREIGN KEY (user_id) REFERENCES user_account(user_id) ON DELETE SET NULL,
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE SET NULL
);

-- ========================================
-- BANKING MODULE
-- ========================================
CREATE TABLE account_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) NOT NULL,
    application_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    
    -- Personal Information (complete data stored here)
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(150) DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    civil_status VARCHAR(20) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    
    -- Contact Information (at least one required)
    email VARCHAR(150) DEFAULT NULL COMMENT 'At least one of email or phone_number must be provided',
    phone_number VARCHAR(20) DEFAULT NULL COMMENT 'At least one of email or phone_number must be provided',
    
    -- Address Information (complete)
    street_address VARCHAR(255) DEFAULT NULL,
    barangay_id INT DEFAULT NULL COMMENT 'FK to barangays table',
    city_id INT DEFAULT NULL COMMENT 'FK to cities table',
    province_id INT DEFAULT NULL COMMENT 'FK to provinces table',
    postal_code VARCHAR(20) DEFAULT NULL,
    
    -- Identification (from Step 2)
    id_type VARCHAR(50) DEFAULT NULL,
    id_number VARCHAR(100) DEFAULT NULL,
    
    -- Employment/Financial Information
    employment_status VARCHAR(50) DEFAULT NULL,
    employer_name VARCHAR(150) DEFAULT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    annual_income DECIMAL(15,2) DEFAULT NULL,
    source_of_funds VARCHAR(100) DEFAULT NULL,
    
    -- Account Preferences
    account_type VARCHAR(50) DEFAULT NULL COMMENT 'Savings, Checking',
    
    -- Application Metadata
    terms_accepted TINYINT(1) DEFAULT 0,
    privacy_acknowledged TINYINT(1) DEFAULT 0,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    reviewed_by_employee_id INT DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    
    -- Created by (for walk-in applications)
    created_by_employee_id INT DEFAULT NULL,
    
    UNIQUE KEY application_number (application_number),
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_status (application_status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_barangay_id (barangay_id),
    INDEX idx_city_id (city_id),
    INDEX idx_province_id (province_id),
    
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- APPLICATION DOCUMENTS TABLE
-- ========================================
-- Stores uploaded ID images and documents for account applications

CREATE TABLE application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL COMMENT 'id_front, id_back, proof_of_income, proof_of_address',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT COMMENT 'File size in bytes',
    mime_type VARCHAR(100) COMMENT 'image/jpeg, image/png, application/pdf',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_application_id (application_id),
    INDEX idx_document_type (document_type),
    
    FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_text VARCHAR(255) NOT NULL,
    points_value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    last_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city_province VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    birthday DATE NOT NULL,
    password VARCHAR(255) NOT NULL,
    verification_code VARCHAR(100) DEFAULT NULL,
    bank_id VARCHAR(50) DEFAULT NULL,
    referral_code VARCHAR(50) DEFAULT NULL,
    total_points DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN NOT NULL,
    UNIQUE KEY email (email),
    INDEX idx_bank_id (bank_id)
);

CREATE TABLE genders (
    gender_id INT AUTO_INCREMENT PRIMARY KEY,
    gender_name VARCHAR(50) NOT NULL,
    UNIQUE KEY gender_name (gender_name)
);

CREATE TABLE provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
    region VARCHAR(100) DEFAULT NULL,
    INDEX idx_province_name (province_name),
    CHECK (country = 'Philippines')
);

CREATE TABLE cities (
    city_id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    province_id INT NOT NULL,
    city_type ENUM('city','municipality') DEFAULT 'city',
    zip_code VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_city_name (city_name),
    INDEX idx_province_id (province_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id) ON DELETE CASCADE
);

CREATE TABLE barangays (
    barangay_id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_name VARCHAR(100) NOT NULL,
    city_id INT NOT NULL,
    zip_code VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_barangay_name (barangay_name),
    INDEX idx_city_id (city_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id) ON DELETE CASCADE
);

CREATE TABLE bank_customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Used for login',
    password_hash VARCHAR(255) DEFAULT NULL COMMENT 'NULL until application approved and customer sets password',
    application_id INT DEFAULT NULL COMMENT 'Links to account_applications - set when application created',
    is_verified BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 0 COMMENT 'Set to 1 after application approved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_employee_id INT DEFAULT NULL COMMENT 'Employee who created walk-in application',
    last_login DATETIME DEFAULT NULL,
    UNIQUE KEY idx_email (email),
    INDEX idx_application_id (application_id),
    INDEX idx_created_by_employee_id (created_by_employee_id)
);

CREATE TABLE `points_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `points` decimal(10,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `transaction_type` enum('mission','redemption','referral','bonus') DEFAULT 'mission',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  INDEX idx_user_id (user_id),
  INDEX idx_created_at (created_at),
  FOREIGN KEY (user_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE user_missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    points_earned DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mission (user_id, mission_id),
    INDEX idx_mission_id (mission_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_employees (
  employee_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username varchar(50) DEFAULT NULL,
  password_hash varchar(255) DEFAULT NULL,
  email varchar(100) DEFAULT NULL,
  first_name varchar(50) DEFAULT NULL,
  last_name varchar(50) DEFAULT NULL,
  role enum('admin','teller','manager') DEFAULT 'teller',
  is_active tinyint(1) DEFAULT 1,
  employee_name varchar(100) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY idx_username (username),
  UNIQUE KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bank_account_types (
    account_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    bank_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(64) NOT NULL,
    currency VARCHAR(10) DEFAULT 'PHP',
    current_balance DECIMAL(18,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (bank_name, account_number),
    INDEX idx_code (code)
);

CREATE TABLE employment_statuses (
    employment_status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Source of Funds Table
CREATE TABLE source_of_funds (
    source_id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    requires_proof TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    account_number VARCHAR(30) NOT NULL,
    account_type_id INT NOT NULL,
    interest_rate DECIMAL(5,2) DEFAULT NULL,
    last_interest_date DATE DEFAULT NULL,
    is_locked BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_employee_id INT DEFAULT NULL,
    maintaining_balance_required DECIMAL(10,2) DEFAULT 500.00,
    monthly_service_fee DECIMAL(10,2) DEFAULT 100.00,
    below_maintaining_since DATE NULL,
    account_status ENUM('active', 'below_maintaining', 'flagged_for_removal', 'closed'),
    last_service_fee_date DATE NULL,
    closure_warning_date DATE NULL,  
    UNIQUE KEY account_number (account_number),
    INDEX idx_customer_id (customer_id),
    INDEX idx_account_type_id (account_type_id),
    INDEX idx_created_by_employee_id (created_by_employee_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (account_type_id) REFERENCES bank_account_types(account_type_id),
    FOREIGN KEY (created_by_employee_id) REFERENCES bank_employees(employee_id)
);

CREATE TABLE customer_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    gender_id INT DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    marital_status ENUM('single','married','divorced','widowed','other') DEFAULT 'single',
    national_id VARCHAR(50) DEFAULT NULL,
    occupation VARCHAR(100) DEFAULT NULL,
    company VARCHAR(100) DEFAULT NULL,
    income_range VARCHAR(50) DEFAULT NULL,
    preferred_language VARCHAR(50) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT NULL,
    loyalty_member BOOLEAN DEFAULT 0,
    profile_created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_gender_id (gender_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (gender_id) REFERENCES genders(gender_id)
);

CREATE TABLE addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_line VARCHAR(200) NOT NULL,
    barangay_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,
    province_id INT DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    address_type VARCHAR(20) DEFAULT 'home',
    is_primary BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_barangay_id (barangay_id),
    INDEX idx_city_id (city_id),
    INDEX idx_province_id (province_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (barangay_id) REFERENCES barangays(barangay_id),
    FOREIGN KEY (city_id) REFERENCES cities(city_id),
    FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);

CREATE TABLE emails (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    is_primary BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (customer_id, email),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id)
);

CREATE TABLE phones (
    phone_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    phone_number VARCHAR(30) NOT NULL,
    phone_type VARCHAR(20) DEFAULT 'mobile',
    is_primary BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (customer_id, phone_number),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id)
);

CREATE TABLE customer_linked_accounts (
    link_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    account_id INT NOT NULL,
    linked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT 1,
    UNIQUE KEY (customer_id, account_id),
    INDEX idx_account_id (account_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
    FOREIGN KEY (account_id) REFERENCES customer_accounts(account_id)
);

CREATE TABLE transaction_types (
    transaction_type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL
);

CREATE TABLE bank_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_ref VARCHAR(50) DEFAULT NULL,
    account_id INT NOT NULL,
    transaction_type_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    related_account_id INT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    employee_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_id (account_id),
    INDEX idx_related_account_id (related_account_id),
    INDEX idx_transaction_type_id (transaction_type_id),
    INDEX idx_employee_id (employee_id),
    FOREIGN KEY (account_id) REFERENCES customer_accounts(account_id),
    FOREIGN KEY (related_account_id) REFERENCES customer_accounts(account_id),
    FOREIGN KEY (transaction_type_id) REFERENCES transaction_types(transaction_type_id),
    FOREIGN KEY (employee_id) REFERENCES bank_employees(employee_id)
);

-- ========================================
-- ACCOUNTING MODULE
-- ========================================

CREATE TABLE fiscal_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('open','closed','locked') DEFAULT 'open',
    closed_by INT,
    closed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (start_date, end_date),
    FOREIGN KEY (closed_by) REFERENCES users(id),
    INDEX idx_status (status)
);

CREATE TABLE account_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    category ENUM('asset','liability','equity','revenue','expense') NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    type_id INT NOT NULL,
    parent_account_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES account_types(id),
    FOREIGN KEY (parent_account_id) REFERENCES accounts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_code (code),
    INDEX idx_type_id (type_id)
);

CREATE TABLE account_balances (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    fiscal_period_id INT NOT NULL,
    opening_balance DECIMAL(18,2) DEFAULT 0.00,
    debit_movements DECIMAL(18,2) DEFAULT 0.00,
    credit_movements DECIMAL(18,2) DEFAULT 0.00,
    closing_balance DECIMAL(18,2) DEFAULT 0.00,
    last_updated DATETIME,
    UNIQUE KEY (account_id, fiscal_period_id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (fiscal_period_id) REFERENCES fiscal_periods(id)
);

CREATE TABLE journal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    auto_reversing BOOLEAN DEFAULT FALSE,
    description TEXT,
    INDEX idx_code (code)
);

CREATE TABLE journal_entries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    journal_no VARCHAR(50) UNIQUE NOT NULL,
    journal_type_id INT NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT,
    fiscal_period_id INT NOT NULL,
    reference_no VARCHAR(100),
    total_debit DECIMAL(18,2) DEFAULT 0.00,
    total_credit DECIMAL(18,2) DEFAULT 0.00,
    status ENUM('draft','posted','reversed','voided') DEFAULT 'draft',
    posted_by INT,
    posted_at DATETIME,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_type_id) REFERENCES journal_types(id),
    FOREIGN KEY (fiscal_period_id) REFERENCES fiscal_periods(id),
    FOREIGN KEY (posted_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_journal_no (journal_no),
    INDEX idx_status (status),
    INDEX idx_entry_date (entry_date)
);

CREATE TABLE journal_lines (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    journal_entry_id BIGINT NOT NULL,
    account_id INT NOT NULL,
    debit DECIMAL(18,2) DEFAULT 0.00,
    credit DECIMAL(18,2) DEFAULT 0.00,
    memo VARCHAR(255),
    cost_center_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_journal_entry_id (journal_entry_id)
);

-- ========================================
-- PAYROLL MODULE
-- ========================================

CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    frequency ENUM('monthly','semimonthly','weekly') DEFAULT 'semimonthly',
    status ENUM('open','processing','posted','paid') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (period_start, period_end),
    INDEX idx_status (status)
);

CREATE TABLE payroll_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    run_by_user_id INT NOT NULL,
    run_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_gross DECIMAL(18,2) DEFAULT 0.00,
    total_deductions DECIMAL(18,2) DEFAULT 0.00,
    total_net DECIMAL(18,2) DEFAULT 0.00,
    status ENUM('draft','finalized','exported','completed') DEFAULT 'draft',
    journal_entry_id BIGINT,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id),
    FOREIGN KEY (run_by_user_id) REFERENCES users(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_status (status)
);

CREATE TABLE payslips (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payroll_run_id INT NOT NULL,
    employee_external_no VARCHAR(100) NOT NULL,
    gross_pay DECIMAL(18,2) DEFAULT 0.00,
    total_deductions DECIMAL(18,2) DEFAULT 0.00,
    net_pay DECIMAL(18,2) DEFAULT 0.00,
    payslip_json JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id),
    INDEX idx_employee_external_no (employee_external_no)
);

CREATE TABLE payroll_payslips (
    payslip_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT DEFAULT NULL,
    pay_period_start DATE DEFAULT NULL,
    pay_period_end DATE DEFAULT NULL,
    gross_salary DECIMAL(10,2) DEFAULT NULL,
    deduction DECIMAL(10,2) DEFAULT NULL,
    net_pay DECIMAL(10,2) DEFAULT NULL,
    release_date DATE DEFAULT NULL,
    INDEX idx_employee_id (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE
);

CREATE TABLE salary_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('earning','deduction','tax','employer_contrib') NOT NULL,
    calculation_method ENUM('fixed','percent','per_hour','formula') DEFAULT 'fixed',
    value DECIMAL(15,4) DEFAULT 0.00,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type)
);

-- ========================================
-- PAYMENTS
-- ========================================

CREATE TABLE payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payment_no VARCHAR(50) UNIQUE NOT NULL,
    payment_date DATE NOT NULL,
    payment_type ENUM('cash','check','bank_transfer') NOT NULL,
    from_bank_account_id INT,
    payee_name VARCHAR(150) NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    reference_no VARCHAR(150),
    memo TEXT,
    status ENUM('pending','completed','failed','voided') DEFAULT 'pending',
    journal_entry_id BIGINT,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_payment_no (payment_no),
    INDEX idx_status (status)
);

-- ========================================
-- LOANS MODULE
-- ========================================

CREATE TABLE loan_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    max_amount DECIMAL(18,2),
    max_term_months INT,
    interest_rate DECIMAL(6,4) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_code (code)
);

CREATE TABLE loans (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    loan_no VARCHAR(50) UNIQUE NOT NULL,
    loan_type_id INT NOT NULL,
    borrower_external_no VARCHAR(100) NOT NULL,
    principal_amount DECIMAL(18,2) NOT NULL,
    interest_rate DECIMAL(6,4) NOT NULL,
    start_date DATE NOT NULL,
    term_months INT NOT NULL,
    monthly_payment DECIMAL(18,2) NOT NULL,
    current_balance DECIMAL(18,2) DEFAULT 0.00,
    next_payment_due DATE DEFAULT NULL,
    status ENUM('pending','active','paid','defaulted','cancelled') DEFAULT 'pending',
    application_id INT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_loan_no (loan_no),
    INDEX idx_status (status),
    INDEX idx_application_id (application_id)
);

CREATE TABLE loan_payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    principal_amount DECIMAL(18,2) NOT NULL,
    interest_amount DECIMAL(18,2) NOT NULL,
    payment_reference VARCHAR(100),
    journal_entry_id BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_loan_id (loan_id),
    INDEX idx_payment_date (payment_date)
);

CREATE TABLE loan_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_type_id int(11) DEFAULT NULL,
    -- Applicant information
    full_name VARCHAR(100) DEFAULT NULL,
    account_number VARCHAR(50) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    job VARCHAR(255) DEFAULT NULL,
    monthly_salary DECIMAL(10,2) DEFAULT NULL,
    user_email VARCHAR(255) NOT NULL,
    -- Requested loan details (transferred to loans table when approved)
    loan_type VARCHAR(50) DEFAULT NULL,
    loan_terms VARCHAR(50) DEFAULT NULL,
    loan_amount DECIMAL(12,2) DEFAULT NULL,
    purpose TEXT DEFAULT NULL,
    monthly_payment DECIMAL(10,2) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    -- Application workflow
    status VARCHAR(50) DEFAULT 'Pending',
    remarks TEXT DEFAULT NULL,
    file_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_by VARCHAR(100) DEFAULT NULL,
    approved_by_user_id INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    next_payment_due DATE DEFAULT NULL,
    rejected_by VARCHAR(255) DEFAULT NULL,
    rejected_by_user_id INT DEFAULT NULL,
    rejected_at DATETIME DEFAULT NULL,
    rejection_remarks TEXT DEFAULT NULL,
    -- Supporting documents (matching subsystem structure)
    proof_of_income VARCHAR(255) DEFAULT NULL,
    coe_document VARCHAR(255) DEFAULT NULL,
    pdf_path VARCHAR(255) DEFAULT NULL,
    pdf_approved VARCHAR(255) DEFAULT NULL,
    pdf_active VARCHAR(255) DEFAULT NULL,
    pdf_rejected VARCHAR(255) DEFAULT NULL,
    -- Link to approved loan (set when application is approved and loan created)
    loan_id BIGINT DEFAULT NULL,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rejected_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE SET NULL,
    INDEX idx_user_email (user_email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_loan_type_id (loan_type_id),
    INDEX idx_approved_by_user_id (approved_by_user_id),
    INDEX idx_rejected_by_user_id (rejected_by_user_id),
    INDEX idx_loan_id (loan_id)
);


ALTER TABLE loans 
ADD CONSTRAINT fk_loans_application_id 
FOREIGN KEY (application_id) REFERENCES loan_applications(id) ON DELETE SET NULL;

-- ========================================
-- EXPENSES MODULE
-- ========================================

CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    account_id INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    INDEX idx_code (code)
);

CREATE TABLE expense_claims (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    claim_no VARCHAR(50) UNIQUE NOT NULL,
    employee_external_no VARCHAR(100) NOT NULL,
    expense_date DATE NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    description TEXT,
    status ENUM('draft','submitted','approved','rejected','paid') DEFAULT 'draft',
    approved_by INT,
    approved_at DATETIME,
    payment_id BIGINT,
    journal_entry_id BIGINT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id),
    INDEX idx_claim_no (claim_no),
    INDEX idx_status (status)
);

-- ========================================
-- COMPLIANCE REPORTS
-- ========================================

CREATE TABLE compliance_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('gaap','sox','bir','ifrs') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    generated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NOT NULL,
    status ENUM('generating','completed','failed') DEFAULT 'generating',
    file_path VARCHAR(500),
    report_data JSON,
    compliance_score DECIMAL(5,2),
    issues_found TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_report_type (report_type),
    INDEX idx_status (status)
);

-- ========================================
-- AUDIT LOGGING
-- ========================================

CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    action VARCHAR(150) NOT NULL,
    object_type VARCHAR(100) NOT NULL,
    object_id VARCHAR(100) NOT NULL,
    old_values JSON,
    new_values JSON,
    additional_info JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_object_type (object_type),
    INDEX idx_created_at (created_at)
);

CREATE TABLE integration_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    source_system VARCHAR(100) NOT NULL,
    endpoint VARCHAR(200) NOT NULL,
    request_type VARCHAR(20) NOT NULL,
    payload JSON,
    response JSON,
    status ENUM('success','error','pending') NOT NULL,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);



-- ========================================
-- VIEWS
-- ========================================

CREATE VIEW v_account_balances AS
SELECT 
    a.code,
    a.name,
    at.category as account_type,
    ab.fiscal_period_id,
    fp.period_name,
    ab.opening_balance,
    ab.debit_movements,
    ab.credit_movements,
    ab.closing_balance
FROM accounts a
JOIN account_types at ON a.type_id = at.id
JOIN account_balances ab ON a.id = ab.account_id
JOIN fiscal_periods fp ON ab.fiscal_period_id = fp.id
WHERE a.is_active = 1;

CREATE VIEW v_journal_summary AS
SELECT 
    je.journal_no,
    je.entry_date,
    jt.name as journal_type,
    je.description,
    je.total_debit,
    je.total_credit,
    je.status,
    u.username as created_by,
    je.created_at
FROM journal_entries je
JOIN journal_types jt ON je.journal_type_id = jt.id
JOIN users u ON je.created_by = u.id;




-- ========================================
-- DATA FIXES AND MIGRATIONS
-- ========================================

-- Fix Transaction Amounts: Convert negative amounts to positive
-- The balance calculation logic applies the sign based on transaction type
-- All amounts should be stored as positive values in the database

-- Fix Transfer Out transactions (type_id = 8)
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 8 AND amount < 0;

-- Fix Service Charge transactions (type_id = 5)
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 5 AND amount < 0;

-- Fix Withdrawal transactions (type_id = 3)
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 3 AND amount < 0;

-- Fix Loan Payment transactions (type_id = 7)
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 7 AND amount < 0;

-- Verification: Check for any remaining negative amounts
-- This query should return 0 rows after the fix
SELECT 
    tt.type_name,
    COUNT(*) as negative_count,
    MIN(t.amount) as min_amount
FROM bank_transactions t
INNER JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
WHERE t.amount < 0
GROUP BY tt.type_name;

-- ========================================
-- USER ACCOUNT ROLE MANAGEMENT
-- ========================================

-- Ensure user_account table has role column with correct type
-- This is safe to run even if column already exists
SET @db_exists = (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'BankingDB');

SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account'
);

SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

-- Add role column if it doesn't exist
SET @sql = IF(@column_exists = 0 AND @table_exists > 0,
    'ALTER TABLE user_account ADD COLUMN role VARCHAR(20) DEFAULT NULL AFTER password_hash',
    'SELECT "Role column already exists or table not found" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure role column is VARCHAR(20) if it exists with different type
SET @column_type = (
    SELECT DATA_TYPE 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

SET @column_length = (
    SELECT CHARACTER_MAXIMUM_LENGTH 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

-- Modify column if type or length is incorrect
SET @sql = IF(@column_exists > 0 AND (@column_type != 'varchar' OR @column_length != 20),
    'ALTER TABLE user_account MODIFY COLUMN role VARCHAR(20) DEFAULT NULL',
    'SELECT "Role column type is correct" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- DATA MIGRATION - USER ROLES
-- ========================================

-- Update existing user_account records with NULL role to 'Admin' for backward compatibility
-- This ensures all existing admin accounts are properly marked
UPDATE user_account 
SET role = 'Admin' 
WHERE role IS NULL 
AND username IS NOT NULL;

-- Ensure any existing admin accounts explicitly have 'Admin' role
-- (in case they were created with different role values)
UPDATE user_account 
SET role = 'Admin' 
WHERE username = 'admin' 
AND (role IS NULL OR role != 'Admin');

-- ========================================
-- USER ACCOUNT MANAGEMENT
-- ========================================

-- ========================================
-- ADMIN ACCOUNT CREATION
-- ========================================
-- Create the main admin account for system access
-- Username: admin
-- Password: password
-- Role: Admin
-- IMPORTANT: Change the password in production!

INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    1, -- Links to employee_id 1 (Juan Santos - HR Manager in employee table)
    'admin', -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password hash for 'password'
    'Admin', -- role (must be exactly 'Admin')
    NULL -- last_login will be set automatically on first login
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = VALUES(role);

-- ========================================
-- HR MANAGER ACCOUNT CREATION
-- ========================================
-- Create HR Manager account
-- Username: hrmanager
-- Password: password
-- Role: HR Manager
-- IMPORTANT: Change the password hash before using in production!

INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    NULL, -- employee_id (can be set to a valid employee_id if needed)
    'hrmanager', -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password hash for 'password'
    'HR Manager', -- role (must be exactly 'HR Manager')
    NULL -- last_login will be set automatically on first login
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = VALUES(role);


-- ========================================
-- LEAVE REQUEST DATA FIXES
-- ========================================

-- Step 1: Normalize ALL leave_request status values to 'Approved' (consistent case)
-- This fixes both 'approved' (lowercase) and 'Approved' (capitalized) to be consistent
UPDATE leave_request 
SET status = 'Declined' 
WHERE UPPER(TRIM(status)) = 'REJECTED';

-- Step 2: Ensure employees 22 and 3 are Active
UPDATE employee 
SET employment_status = 'Active' 
WHERE employee_id IN (22, 3) 
AND (employment_status IS NULL OR employment_status != 'Active');

-- Step 3: Ensure ALL active employees have proper employment_status
UPDATE employee 
SET employment_status = 'Active' 
WHERE employment_status IS NULL 
AND employee_id IN (SELECT DISTINCT employee_id FROM leave_request WHERE UPPER(TRIM(status)) = 'APPROVED');

-- Step 4: Ensure date fields are proper DATE type (remove any time components)
UPDATE leave_request 
SET start_date = DATE(start_date),
    end_date = DATE(end_date)
WHERE start_date IS NOT NULL AND end_date IS NOT NULL;

-- Step 5: Fix specific leave requests mentioned by user
-- Employee 22 (Mariana) - Leave Request ID 10: Nov 17-19, 2025
UPDATE leave_request 
SET status = 'Approved',
    start_date = '2025-11-17',
    end_date = '2025-11-19',
    total_days = 3
WHERE leave_request_id = 10 
AND employee_id = 22;

-- Employee 3 (Jose) - Leave Request ID 2: Nov 15-16, 2025  
UPDATE leave_request 
SET status = 'Approved',
    start_date = '2025-11-15',
    end_date = '2025-11-16',
    total_days = 2
WHERE leave_request_id = 2 
AND employee_id = 3;

-- ========================================
-- ADDRESS FIELD MIGRATION
-- ========================================
-- Migrate existing address data to new atomic fields
-- This preserves existing data by attempting to parse or setting defaults

-- For existing records with address data, try to preserve it
-- If address exists but new fields are NULL, copy to city as fallback
UPDATE employee 
SET city = COALESCE(city, address)
WHERE address IS NOT NULL AND address != '' AND city IS NULL;

-- Set default province if not set
UPDATE employee 
SET province = COALESCE(province, 'Metro Manila')
WHERE province IS NULL;
-- Step 6: Add/update index for better query performance on leave_request
-- Check if index exists before dropping (safer approach)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'leave_request' 
    AND INDEX_NAME = 'idx_leave_status_date'
);

SET @sql = IF(@index_exists > 0,
    'DROP INDEX idx_leave_status_date ON leave_request',
    'SELECT "Index does not exist, will create new one" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create the index
CREATE INDEX idx_leave_status_date ON leave_request(employee_id, status, start_date, end_date);

-- Step 7: Verify the data
SELECT 
    'VERIFICATION' as check_type,
    lr.leave_request_id,
    lr.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    e.employment_status,
    lr.status as leave_status,
    UPPER(TRIM(lr.status)) as normalized_status,
    lr.start_date,
    lr.end_date,
    lt.leave_name,
    CASE 
        WHEN e.employment_status = 'Active' AND UPPER(TRIM(lr.status)) = 'APPROVED' THEN 'OK'
        ELSE 'NEEDS FIX'
    END as status_check
FROM leave_request lr
INNER JOIN employee e ON lr.employee_id = e.employee_id
LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
WHERE lr.employee_id IN (22, 3)
ORDER BY lr.leave_request_id;

-- Step 8: Test query for Nov 17, 2025
SELECT 
    'TEST QUERY FOR 2025-11-17' as test_name,
    lr.leave_request_id,
    lr.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    lr.start_date,
    lr.end_date,
    lr.status,
    lt.leave_name,
    CASE 
        WHEN CAST('2025-11-17' AS DATE) >= CAST(lr.start_date AS DATE) 
         AND CAST('2025-11-17' AS DATE) <= CAST(lr.end_date AS DATE) 
        THEN 'MATCHES'
        ELSE 'NO MATCH'
    END as date_match
FROM leave_request lr
INNER JOIN employee e ON lr.employee_id = e.employee_id
LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
WHERE lr.employee_id IN (22, 3)
AND e.employment_status = 'Active'
AND UPPER(TRIM(lr.status)) = 'APPROVED'
ORDER BY lr.leave_request_id;

SELECT '=== FIX COMPLETE ===' as status;

-- ========================================
-- END OF UNIFIED SCHEMA
-- ========================================
