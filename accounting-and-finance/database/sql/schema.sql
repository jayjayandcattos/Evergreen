-- ========================================
-- DATABASE CREATION
-- ========================================

DROP DATABASE IF EXISTS accounting_finance;
CREATE DATABASE accounting_finance;
USE accounting_finance;

-- ========================================
-- USERS AND AUTHENTICATION
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
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- ========================================
-- HRIS EMPLOYEE REFERENCE
-- ========================================

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
  UNIQUE KEY (external_employee_no, external_source)
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

-- ========================================
-- CORE ACCOUNTING
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
    FOREIGN KEY (closed_by) REFERENCES users(id)
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
    FOREIGN KEY (created_by) REFERENCES users(id)
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

-- ========================================
-- JOURNAL ENTRIES
-- ========================================

CREATE TABLE journal_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(50) NOT NULL,
    auto_reversing BOOLEAN DEFAULT FALSE,
    description TEXT
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
    FOREIGN KEY (created_by) REFERENCES users(id)
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
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- ========================================
-- PAYROLL
-- ========================================

CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    frequency ENUM('monthly','semimonthly','weekly') DEFAULT 'semimonthly',
    status ENUM('open','processing','posted','paid') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (period_start, period_end)
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
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
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
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id)
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- BANKING AND PAYMENTS
-- ========================================

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
    UNIQUE KEY (bank_name, account_number)
);

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
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ========================================
-- LOANS
-- ========================================

CREATE TABLE loan_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    max_amount DECIMAL(18,2),
    max_term_months INT,
    interest_rate DECIMAL(6,4) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
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
    status ENUM('pending','active','paid','defaulted','cancelled') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
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
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
);

-- ========================================
-- EXPENSES
-- ========================================

CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    account_id INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
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
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id)
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
    FOREIGN KEY (generated_by) REFERENCES users(id)
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
    FOREIGN KEY (user_id) REFERENCES users(id)
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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