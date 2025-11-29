-- ========================================
-- FIX MISSIONS AND TRANSACTIONS
-- ========================================

-- 1. Add missing transaction types for transfers
INSERT IGNORE INTO transaction_types (transaction_type_id, type_name, description) VALUES
(8, 'Transfer Out', 'Money sent to another account'),
(9, 'Transfer In', 'Money received from another account');

-- 2. Fix user_missions table to work with customer_id
-- First, check if we need to update the foreign key
ALTER TABLE user_missions DROP FOREIGN KEY IF EXISTS user_missions_ibfk_1;

-- Add a new column for customer_id if it doesn't exist
ALTER TABLE user_missions 
ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER user_id,
ADD INDEX IF NOT EXISTS idx_customer_id (customer_id);

-- Update existing records to set customer_id from bank_users
UPDATE user_missions um
INNER JOIN bank_users bu ON um.user_id = bu.id
INNER JOIN bank_customers bc ON bu.email = bc.email
SET um.customer_id = bc.customer_id
WHERE um.customer_id IS NULL;

-- 3. Make sure points_history uses correct user_id
-- Check if points_history exists and has correct structure
CREATE TABLE IF NOT EXISTS points_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'References bank_users.id',
    customer_id INT NULL COMMENT 'References bank_customers.customer_id',
    points DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    transaction_type ENUM('earn', 'redeem', 'referral', 'mission') DEFAULT 'earn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update points_history to have customer_id
ALTER TABLE points_history 
ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER user_id,
ADD INDEX IF NOT EXISTS idx_customer_id_history (customer_id);

-- Update existing points_history records
UPDATE points_history ph
INNER JOIN bank_users bu ON ph.user_id = bu.id
INNER JOIN bank_customers bc ON bu.email = bc.email
SET ph.customer_id = bc.customer_id
WHERE ph.customer_id IS NULL;

-- ========================================
-- DONE!
-- ========================================
SELECT 'Missions and transactions fixed!' as status;
