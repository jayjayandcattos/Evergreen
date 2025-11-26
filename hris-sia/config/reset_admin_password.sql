-- ========================================
-- RESET ADMIN PASSWORD
-- ========================================
-- This script resets the admin account password
-- Use this if you cannot log in to the admin account

USE BankingDB;

-- Update admin password to 'password'
UPDATE user_account 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    role = 'Admin'
WHERE username = 'admin';

-- If the admin account doesn't exist, create it
INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    1, -- Links to employee_id 1 (Juan Santos - HR Manager)
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'Admin',
    NULL
)
ON DUPLICATE KEY UPDATE 
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    role = 'Admin';

-- Verify the admin account
SELECT 
    user_id,
    employee_id,
    username,
    role,
    last_login,
    'Password is: password' as note
FROM user_account 
WHERE username = 'admin';

-- ========================================
-- CREDENTIALS
-- ========================================
-- Username: admin
-- Password: password
-- ========================================
