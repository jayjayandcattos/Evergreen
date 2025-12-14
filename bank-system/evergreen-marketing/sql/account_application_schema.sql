-- ========================================
-- ACCOUNT APPLICATION MODULE
-- ========================================
-- Tables for handling account applications from evergreen_form.php
-- This should be added to the BankingDB database

USE BankingDB;

-- ========================================
-- CARD TYPES TABLE
-- ========================================
-- Stores available card types that customers can apply for

CREATE TABLE IF NOT EXISTS card_types (
    card_type_id INT AUTO_INCREMENT PRIMARY KEY,
    card_code VARCHAR(50) UNIQUE NOT NULL,
    card_name VARCHAR(100) NOT NULL,
    card_description TEXT,
    annual_fee DECIMAL(10,2) DEFAULT 0.00,
    credit_limit_min DECIMAL(15,2) DEFAULT 0.00,
    credit_limit_max DECIMAL(15,2) DEFAULT 0.00,
    interest_rate DECIMAL(5,2) DEFAULT 0.00,
    rewards_program VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_card_code (card_code),
    INDEX idx_is_active (is_active)
);

-- Insert default card types
INSERT INTO card_types (card_code, card_name, card_description, annual_fee, credit_limit_min, credit_limit_max, interest_rate, rewards_program) VALUES
('DEBIT_STANDARD', 'Standard Debit Card', 'Basic debit card for everyday banking', 0.00, 0.00, 0.00, 0.00, 'Points Rewards'),
('DEBIT_GOLD', 'Gold Debit Card', 'Premium debit card with enhanced benefits', 25.00, 0.00, 0.00, 0.00, 'Gold Rewards'),
('CREDIT_CLASSIC', 'Classic Credit Card', 'Entry-level credit card', 50.00, 1000.00, 5000.00, 18.99, 'Basic Cashback'),
('CREDIT_PLATINUM', 'Platinum Credit Card', 'Premium credit card with travel benefits', 150.00, 5000.00, 25000.00, 15.99, 'Platinum Travel Rewards'),
('CREDIT_BUSINESS', 'Business Credit Card', 'Credit card for business expenses', 100.00, 10000.00, 50000.00, 16.99, 'Business Rewards');

-- ========================================
-- ACCOUNT APPLICATIONS TABLE
-- ========================================
-- Stores all account applications submitted through evergreen_form.php

CREATE TABLE IF NOT EXISTS account_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Application Reference
    application_number VARCHAR(50) UNIQUE NOT NULL,
    application_status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    
    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    
    -- Address Information
    street_address VARCHAR(255) NOT NULL,
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
    
    -- Additional Services (JSON array of selected services)
    additional_services JSON COMMENT 'Array of selected services: debit, online, mobile, overdraft',
    
    -- Terms Agreement
    terms_accepted BOOLEAN DEFAULT FALSE,
    privacy_acknowledged BOOLEAN DEFAULT FALSE,
    marketing_consent BOOLEAN DEFAULT FALSE,
    
    -- Application Tracking
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME,
    reviewed_by INT,
    decision_at DATETIME,
    decision_by INT,
    rejection_reason TEXT,
    
    -- Customer Link (set when approved and customer account created)
    customer_id INT,
    account_id INT,
    
    -- IP and User Agent for security
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_status (application_status),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_customer_id (customer_id),
    
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (decision_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- APPLICATION CARD REQUESTS TABLE
-- ========================================
-- Stores which cards the applicant wants to apply for

CREATE TABLE IF NOT EXISTS application_card_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    card_type_id INT NOT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    card_number VARCHAR(20),
    issued_at DATETIME,
    
    INDEX idx_application_id (application_id),
    INDEX idx_card_type_id (card_type_id),
    INDEX idx_status (status),
    
    FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (card_type_id) REFERENCES card_types(card_type_id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_app_card (application_id, card_type_id)
);

-- ========================================
-- APPLICATION DOCUMENTS TABLE
-- ========================================
-- Stores uploaded documents for applications

CREATE TABLE IF NOT EXISTS application_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL COMMENT 'id_front, id_back, proof_of_income, proof_of_address',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_application_id (application_id),
    INDEX idx_document_type (document_type),
    
    FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE
);

-- ========================================
-- APPLICATION NOTES TABLE
-- ========================================
-- Stores internal notes and comments on applications

CREATE TABLE IF NOT EXISTS application_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    note_text TEXT NOT NULL,
    note_type ENUM('general', 'review', 'decision', 'follow_up') DEFAULT 'general',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_application_id (application_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ========================================
-- APPLICATION STATUS HISTORY TABLE
-- ========================================
-- Tracks all status changes for audit trail

CREATE TABLE IF NOT EXISTS application_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    change_reason TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_application_id (application_id),
    INDEX idx_changed_at (changed_at),
    
    FOREIGN KEY (application_id) REFERENCES account_applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ========================================
-- VIEWS FOR REPORTING
-- ========================================

-- View for pending applications
CREATE OR REPLACE VIEW v_pending_applications AS
SELECT 
    aa.application_id,
    aa.application_number,
    aa.first_name,
    aa.last_name,
    aa.email,
    aa.phone_number,
    aa.account_type,
    aa.annual_income,
    aa.submitted_at,
    DATEDIFF(NOW(), aa.submitted_at) as days_pending,
    GROUP_CONCAT(ct.card_name SEPARATOR ', ') as requested_cards
FROM account_applications aa
LEFT JOIN application_card_requests acr ON aa.application_id = acr.application_id
LEFT JOIN card_types ct ON acr.card_type_id = ct.card_type_id
WHERE aa.application_status = 'pending'
GROUP BY aa.application_id
ORDER BY aa.submitted_at DESC;

-- View for application statistics
CREATE OR REPLACE VIEW v_application_statistics AS
SELECT 
    DATE(submitted_at) as application_date,
    COUNT(*) as total_applications,
    SUM(CASE WHEN application_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN application_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN application_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
    AVG(annual_income) as avg_income
FROM account_applications
GROUP BY DATE(submitted_at)
ORDER BY application_date DESC;

-- ========================================
-- STORED PROCEDURES
-- ========================================

DELIMITER $$

-- Procedure to approve an application
CREATE PROCEDURE sp_approve_application(
    IN p_application_id INT,
    IN p_approved_by INT,
    OUT p_customer_id INT,
    OUT p_account_id INT
)
BEGIN
    DECLARE v_email VARCHAR(150);
    DECLARE v_first_name VARCHAR(100);
    DECLARE v_last_name VARCHAR(100);
    DECLARE v_account_number VARCHAR(30);
    DECLARE v_account_type_id INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get application details
    SELECT email, first_name, last_name, account_type
    INTO v_email, v_first_name, v_last_name, v_account_type_id
    FROM account_applications
    WHERE application_id = p_application_id;
    
    -- Create customer account (simplified - you may need to adjust based on your schema)
    -- This is a placeholder - adjust according to your actual bank_customers table structure
    
    -- Update application status
    UPDATE account_applications
    SET application_status = 'approved',
        decision_at = NOW(),
        decision_by = p_approved_by,
        customer_id = p_customer_id,
        account_id = p_account_id
    WHERE application_id = p_application_id;
    
    -- Log status change
    INSERT INTO application_status_history (application_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_application_id, 'pending', 'approved', p_approved_by, 'Application approved');
    
    COMMIT;
END$$

-- Procedure to reject an application
CREATE PROCEDURE sp_reject_application(
    IN p_application_id INT,
    IN p_rejected_by INT,
    IN p_rejection_reason TEXT
)
BEGIN
    START TRANSACTION;
    
    UPDATE account_applications
    SET application_status = 'rejected',
        decision_at = NOW(),
        decision_by = p_rejected_by,
        rejection_reason = p_rejection_reason
    WHERE application_id = p_application_id;
    
    INSERT INTO application_status_history (application_id, old_status, new_status, changed_by, change_reason)
    VALUES (p_application_id, 'pending', 'rejected', p_rejected_by, p_rejection_reason);
    
    COMMIT;
END$$

DELIMITER ;

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Insert a sample application (for testing)
INSERT INTO account_applications (
    application_number, first_name, last_name, email, phone_number, date_of_birth,
    street_address, city, state, zip_code, ssn, id_type, id_number,
    employment_status, employer_name, job_title, annual_income, account_type,
    additional_services, terms_accepted, privacy_acknowledged, marketing_consent
) VALUES (
    'APP-2025-00001', 'John', 'Doe', 'john.doe@example.com', '(555) 123-4567', '1990-01-15',
    '123 Main Street', 'New York', 'NY', '10001', '123-45-6789', 'Driver\'s License', 'DL123456',
    'Employed', 'Tech Corp', 'Software Engineer', 75000.00, 'acct-both',
    '["debit", "online", "mobile"]', TRUE, TRUE, FALSE
);

-- Link sample application to card requests
INSERT INTO application_card_requests (application_id, card_type_id)
SELECT 1, card_type_id FROM card_types WHERE card_code IN ('DEBIT_STANDARD', 'CREDIT_CLASSIC');

-- ========================================
-- END OF ACCOUNT APPLICATION SCHEMA
-- ========================================
