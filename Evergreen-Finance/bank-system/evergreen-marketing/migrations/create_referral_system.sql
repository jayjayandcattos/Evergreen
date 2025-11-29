-- ========================================
-- REFERRAL SYSTEM SETUP
-- ========================================

-- 1. Add referral_code column to bank_users if not exists
ALTER TABLE bank_users 
ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN IF NOT EXISTS total_points DECIMAL(10,2) DEFAULT 0.00,
ADD INDEX IF NOT EXISTS idx_referral_code (referral_code);

-- 2. Add referral_code column to bank_customers if not exists
ALTER TABLE bank_customers 
ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN IF NOT EXISTS total_points DECIMAL(10,2) DEFAULT 0.00,
ADD INDEX IF NOT EXISTS idx_referral_code_customers (referral_code);

-- 3. Create referrals table if not exists
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL COMMENT 'User who shared the code',
    referred_id INT NOT NULL COMMENT 'User who used the code',
    points_earned DECIMAL(10,2) DEFAULT 20.00 COMMENT 'Points earned by referrer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_referrer (referrer_id),
    INDEX idx_referred (referred_id),
    UNIQUE KEY unique_referral (referred_id) COMMENT 'Each user can only use one referral code'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create points_history table if not exists
CREATE TABLE IF NOT EXISTS points_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    transaction_type ENUM('earn', 'redeem', 'referral') DEFAULT 'earn',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Generate referral codes for existing users who don't have one
UPDATE bank_users 
SET referral_code = CONCAT(
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    FLOOR(RAND() * 10),
    FLOOR(RAND() * 10),
    FLOOR(RAND() * 10)
)
WHERE referral_code IS NULL OR referral_code = '';

-- 6. Generate referral codes for existing customers who don't have one
UPDATE bank_customers 
SET referral_code = CONCAT(
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    FLOOR(RAND() * 10),
    FLOOR(RAND() * 10),
    FLOOR(RAND() * 10)
)
WHERE referral_code IS NULL OR referral_code = '';

-- 7. Ensure total_points is not NULL
UPDATE bank_users SET total_points = 0.00 WHERE total_points IS NULL;
UPDATE bank_customers SET total_points = 0.00 WHERE total_points IS NULL;

-- ========================================
-- DONE! Referral system is ready
-- ========================================
