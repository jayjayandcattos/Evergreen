-- ========================================
-- POINTS & REWARDS SYSTEM MIGRATION
-- From evergreen_bank to BankingDB
-- ========================================

USE BankingDB;

-- ========================================
-- 1. ADD MISSING REFERRALS TABLE
-- ========================================

CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL COMMENT 'Customer who referred (bank_customers.customer_id)',
    referred_id INT NOT NULL COMMENT 'Customer who was referred (bank_customers.customer_id)',
    points_earned DECIMAL(10,2) DEFAULT 20.00 COMMENT 'Points earned by referrer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_referral (referred_id),
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id),
    FOREIGN KEY (referrer_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES bank_customers(customer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracks customer referrals for points system';

-- ========================================
-- 2. MIGRATE DATA FROM evergreen_bank
-- ========================================

-- Copy referrals data (if evergreen_bank exists)
INSERT IGNORE INTO BankingDB.referrals (id, referrer_id, referred_id, points_earned, created_at)
SELECT id, referrer_id, referred_id, points_earned, created_at
FROM evergreen_bank.referrals
WHERE EXISTS (SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'evergreen_bank');

-- Copy missions data (if not already present)
INSERT IGNORE INTO BankingDB.missions (id, mission_text, points_value, created_at)
SELECT customer_id as id, mission_text, points_value, created_at
FROM evergreen_bank.missions
WHERE EXISTS (SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'evergreen_bank');

-- Copy user_missions data
INSERT IGNORE INTO BankingDB.user_missions (id, user_id, mission_id, points_earned, completed_at)
SELECT customer_id as id, user_id, mission_id, points_earned, completed_at
FROM evergreen_bank.user_missions
WHERE EXISTS (SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'evergreen_bank');

-- Copy points_history data
INSERT IGNORE INTO BankingDB.points_history (id, user_id, points, description, transaction_type, created_at)
SELECT customer_id as id, user_id, points, description, transaction_type, created_at
FROM evergreen_bank.points_history
WHERE EXISTS (SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'evergreen_bank');

-- ========================================
-- 3. SAMPLE MISSIONS DATA (if missions table is empty)
-- ========================================

INSERT IGNORE INTO missions (id, mission_text, points_value, created_at) VALUES
(1, 'Refer your first friend to EVERGREEN', 50.00, CURRENT_TIMESTAMP),
(2, 'Successfully refer 3 friends', 150.00, CURRENT_TIMESTAMP),
(3, 'Reach 5 successful referrals', 250.00, CURRENT_TIMESTAMP),
(4, 'Refer 10 friends and unlock premium rewards', 500.00, CURRENT_TIMESTAMP),
(5, 'Achieve 15 referrals milestone', 750.00, CURRENT_TIMESTAMP),
(6, 'Become a referral champion with 20 friends', 1000.00, CURRENT_TIMESTAMP),
(7, 'Share your referral code on social media', 30.00, CURRENT_TIMESTAMP),
(8, 'Have 3 friends use your referral code in one week', 200.00, CURRENT_TIMESTAMP),
(9, 'Reach 25 total referrals - Elite status', 1500.00, CURRENT_TIMESTAMP),
(10, 'Ultimate referrer - 50 successful referrals', 3000.00, CURRENT_TIMESTAMP),
(11, 'Refer a friend and earn bonus points', 20.00, CURRENT_TIMESTAMP),
(12, 'Use a referral code to get started', 10.00, CURRENT_TIMESTAMP);

-- ========================================
-- 4. VERIFICATION QUERIES
-- ========================================

-- Check if data was migrated successfully
SELECT 'Missions Count' as Check_Type, COUNT(*) as Count FROM missions
UNION ALL
SELECT 'User Missions Count', COUNT(*) FROM user_missions
UNION ALL
SELECT 'Points History Count', COUNT(*) FROM points_history
UNION ALL
SELECT 'Referrals Count', COUNT(*) FROM referrals
UNION ALL
SELECT 'Customers with Points', COUNT(*) FROM bank_customers WHERE total_points > 0;

-- Show sample data
SELECT 'Sample Missions' as Data_Type;
SELECT id, mission_text, points_value FROM missions LIMIT 5;

SELECT 'Sample User Missions' as Data_Type;
SELECT id, user_id, mission_id, points_earned, completed_at FROM user_missions LIMIT 5;

SELECT 'Sample Points History' as Data_Type;
SELECT id, user_id, points, description, transaction_type, created_at FROM points_history LIMIT 5;

SELECT 'Sample Referrals' as Data_Type;
SELECT id, referrer_id, referred_id, points_earned, created_at FROM referrals LIMIT 5;

-- ========================================
-- 5. CLEANUP (OPTIONAL - ONLY IF MIGRATION IS SUCCESSFUL)
-- ========================================

-- Uncomment these lines ONLY after verifying the migration was successful
-- and you no longer need the old evergreen_bank database

-- DROP DATABASE IF EXISTS evergreen_bank;

SELECT '=== MIGRATION COMPLETE ===' as Status;
SELECT 'Please update db_connect.php to use BankingDB instead of evergreen_bank' as Next_Step;
