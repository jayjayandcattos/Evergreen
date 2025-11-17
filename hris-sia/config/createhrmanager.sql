-- ========================================
-- CREATE HR MANAGER ACCOUNT
-- ========================================
-- This file creates an HR Manager account
-- Username: hrmanager
-- Password: password
-- Role: HR Manager
--
-- ========================================

-- Create HR Manager account
INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    NULL, -- employee_id (can be set to a valid employee_id if needed)
    'hrmanager', -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password hash for 'password'
    'HR Manager', -- role (must be exactly 'HR Manager')
    NULL -- last_login will be set automatically on first login
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = VALUES(role);

-- ========================================
-- VERIFICATION
-- ========================================
-- Run this to verify the account was created:
-- SELECT user_id, username, role, last_login FROM user_account WHERE username = 'hrmanager';

