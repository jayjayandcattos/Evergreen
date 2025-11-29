-- Add Maintaining Balance System
-- This script adds support for maintaining balance requirements and account status tracking

-- 1. Add columns to customer_accounts table for maintaining balance tracking
ALTER TABLE customer_accounts
ADD COLUMN IF NOT EXISTS maintaining_balance_required DECIMAL(10,2) DEFAULT 500.00 COMMENT 'Minimum balance required to avoid service fee',
ADD COLUMN IF NOT EXISTS monthly_service_fee DECIMAL(10,2) DEFAULT 100.00 COMMENT 'Monthly service fee when below maintaining balance',
ADD COLUMN IF NOT EXISTS below_maintaining_since DATE NULL COMMENT 'Date when account first went below maintaining balance',
ADD COLUMN IF NOT EXISTS account_status ENUM('active', 'below_maintaining', 'flagged_for_removal', 'closed') DEFAULT 'active' COMMENT 'Current account status',
ADD COLUMN IF NOT EXISTS last_service_fee_date DATE NULL COMMENT 'Last date when service fee was charged',
ADD COLUMN IF NOT EXISTS closure_warning_date DATE NULL COMMENT 'Date when closure warning was issued';

-- 2. Create account_status_history table to track status changes
CREATE TABLE IF NOT EXISTS account_status_history (
    status_history_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    previous_status ENUM('active', 'below_maintaining', 'flagged_for_removal', 'closed'),
    new_status ENUM('active', 'below_maintaining', 'flagged_for_removal', 'closed') NOT NULL,
    balance_at_change DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255),
    changed_by INT NULL COMMENT 'Employee ID who triggered the change, NULL for system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(account_id),
    FOREIGN KEY (changed_by) REFERENCES bank_employees(employee_id) ON DELETE SET NULL,
    INDEX idx_account_status (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create table for service fee transactions
CREATE TABLE IF NOT EXISTS service_fee_charges (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    transaction_id INT NULL,
    fee_amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,
    charge_date DATE NOT NULL,
    fee_type ENUM('monthly_service_fee', 'below_maintaining_fee') DEFAULT 'monthly_service_fee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES customer_accounts(account_id),
    FOREIGN KEY (transaction_id) REFERENCES bank_transactions(transaction_id) ON DELETE SET NULL,
    INDEX idx_account_date (account_id, charge_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update existing accounts to have default values
UPDATE customer_accounts
SET maintaining_balance_required = 500.00,
    monthly_service_fee = 100.00,
    account_status = 'active'
WHERE maintaining_balance_required IS NULL;

-- 5. Add Service Charge transaction type if not exists
INSERT IGNORE INTO transaction_types (type_name, description)
VALUES ('Service Charge', 'Monthly service fee or below maintaining balance fee');

COMMIT;
