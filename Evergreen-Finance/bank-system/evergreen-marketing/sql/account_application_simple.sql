-- ========================================
-- SIMPLE ACCOUNT APPLICATION TABLE
-- ========================================
-- Minimal table for storing account applications from evergreen_form.php

USE BankingDB;

-- ========================================
-- ACCOUNT APPLICATIONS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS account_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Application Reference
    application_number VARCHAR(50) UNIQUE NOT NULL,
    application_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    
    -- Address Information
    street_address VARCHAR(255) NOT NULL,
    barangay VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    
    -- Identity Verification
    ssn VARCHAR(50) NOT NULL,
    id_type VARCHAR(50) NOT NULL,
    id_number VARCHAR(100) NOT NULL,
    
    -- Employment Information
    employment_status VARCHAR(50) NOT NULL,
    employer_name VARCHAR(150),
    job_title VARCHAR(100),
    annual_income DECIMAL(15,2),
    
    -- Account Preferences
    account_type VARCHAR(50) NOT NULL COMMENT 'acct-checking, acct-savings, acct-both',
    
    -- Card Selection (stored as comma-separated values)
    selected_cards TEXT COMMENT 'Comma-separated: debit, credit, prepaid',
    
    -- Additional Services (stored as comma-separated values)
    additional_services TEXT COMMENT 'Comma-separated: online, mobile, overdraft, alerts',
    
    -- Terms Agreement
    terms_accepted BOOLEAN DEFAULT FALSE,
    privacy_acknowledged BOOLEAN DEFAULT FALSE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    
    -- Indexes for better performance
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_status (application_status),
    INDEX idx_submitted_at (submitted_at)
);

-- ========================================
-- SAMPLE DATA (Optional - for testing)
-- ========================================

INSERT INTO account_applications (
    application_number, first_name, last_name, email, phone_number, date_of_birth,
    street_address, barangay, city, state, zip_code, ssn, id_type, id_number,
    employment_status, employer_name, job_title, annual_income, account_type,
    selected_cards, additional_services, terms_accepted, privacy_acknowledged, marketing_consent
) VALUES (
    'APP-2025-00001', 'John', 'Doe', 'john.doe@example.com', '(555) 123-4567', '1990-01-15',
    '123 Main Street', 'Project 6', 'Quezon City', 'Metro Manila', '1100', '123-45-6789', 'Driver\'s License', 'DL123456',
    'Employed', 'Tech Corp', 'Software Engineer', 75000.00, 'acct-both',
    'debit,credit', 'online,mobile', TRUE, TRUE, FALSE
);

-- ========================================
-- END OF SCHEMA
-- ========================================
