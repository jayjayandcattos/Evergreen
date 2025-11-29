-- Archive table for closed/inactive customer accounts
-- Stores complete account history before deletion

CREATE TABLE IF NOT EXISTS archived_customer_accounts (
    archive_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    
    -- Original account data
    account_id INT(11) NOT NULL,
    customer_id INT(11) NOT NULL,
    account_number VARCHAR(30) NOT NULL,
    account_type_id INT(11) NOT NULL,
    interest_rate DECIMAL(5,2) DEFAULT NULL,
    last_interest_date DATE DEFAULT NULL,
    
    -- Account status info
    is_locked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL,
    created_by_employee_id INT(11) DEFAULT NULL,
    
    -- Balance and fees
    maintaining_balance_required DECIMAL(10,2) DEFAULT 500.00,
    monthly_service_fee DECIMAL(10,2) DEFAULT 100.00,
    final_balance DECIMAL(18,2) DEFAULT 0.00 COMMENT 'Balance at time of archiving',
    
    -- Timeline tracking
    below_maintaining_since DATE DEFAULT NULL,
    last_service_fee_date DATE DEFAULT NULL,
    closure_warning_date DATE DEFAULT NULL,
    flagged_for_removal_date DATE DEFAULT NULL,
    
    -- Archive metadata
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by_employee_id INT(11) DEFAULT NULL,
    archive_reason VARCHAR(255) DEFAULT 'Below maintaining balance for 6+ months',
    original_status ENUM('active','below_maintaining','flagged_for_removal','closed') DEFAULT 'closed',
    
    -- Indexes
    INDEX idx_original_account_id (account_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_account_number (account_number),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Archive of customer accounts closed due to prolonged inactivity';
