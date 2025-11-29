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
    INDEX idx_recruitment_id (recruitment_id),
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

CREATE TABLE missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_text VARCHAR(255) NOT NULL,
    points_value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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
    total_points DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN NOT NULL,
    UNIQUE KEY email (email),
    INDEX idx_bank_id (bank_id)
);

CREATE TABLE user_missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    points_earned DECIMAL(10,2) NOT NULL,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_mission (user_id, mission_id),
    INDEX idx_mission_id (mission_id),
    FOREIGN KEY (user_id) REFERENCES bank_users(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
);

CREATE TABLE genders (
    gender_id INT AUTO_INCREMENT PRIMARY KEY,
    gender_name VARCHAR(50) NOT NULL,
    UNIQUE KEY gender_name (gender_name)
);

CREATE TABLE provinces (
    province_id INT AUTO_INCREMENT PRIMARY KEY,
    province_name VARCHAR(100) NOT NULL,
    country VARCHAR(100) DEFAULT 'Philippines'
);

CREATE TABLE bank_customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by_employee_id INT DEFAULT NULL,
    INDEX idx_created_by_employee_id (created_by_employee_id)
);

CREATE TABLE bank_employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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
    city VARCHAR(100) DEFAULT NULL,
    province_id INT DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    address_type VARCHAR(20) DEFAULT 'home',
    is_primary BOOLEAN DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_province_id (province_id),
    FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id),
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
    loan_type_id INT DEFAULT NULL,
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

ALTER TABLE bank_customers 
ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN total_points DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN referred_by_customer_id INT NULL,
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by_customer_id);

ALTER TABLE bank_customers 
ADD CONSTRAINT fk_referred_by 
FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id) ON DELETE SET NULL;

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



