-- ========================================
-- CREATE ACCOUNTING ADMIN ACCOUNT
-- ========================================
-- This creates a professional Accounting Admin account
-- Email: finance.admin@evergreen.com
-- Username: finance.admin
-- Password: Finance2025
-- Role: Accounting Admin
-- ========================================

USE BankingDB;

-- Step 1: Create user in the users table (for authentication)
INSERT INTO users (username, password_hash, email, full_name, is_active, created_at) 
VALUES (
    'finance.admin', -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password hash for 'Finance2025'
    'finance.admin@evergreen.com', -- email
    'Finance Administrator', -- full name
    TRUE, -- is_active
    NOW() -- created_at
) ON DUPLICATE KEY UPDATE 
    username = VALUES(username),
    password_hash = VALUES(password_hash),
    email = VALUES(email),
    full_name = VALUES(full_name),
    is_active = VALUES(is_active);

-- Step 2: Get the user_id from users table
SET @finance_user_id = (SELECT id FROM users WHERE username = 'finance.admin' LIMIT 1);

-- Step 3: Create Accounting Admin role if it doesn't exist
INSERT INTO roles (name, description) 
VALUES (
    'Accounting Admin',
    'Full administrative access to accounting and finance modules'
)
ON DUPLICATE KEY UPDATE 
    description = VALUES(description);

-- Step 4: Get the role_id
SET @accounting_admin_role_id = (SELECT id FROM roles WHERE name = 'Accounting Admin' LIMIT 1);

-- Step 5: Assign role to user
INSERT INTO user_roles (user_id, role_id) 
VALUES (@finance_user_id, @accounting_admin_role_id) 
ON DUPLICATE KEY UPDATE 
    user_id = VALUES(user_id);

-- Step 6: Create user_account entry (for backward compatibility)
INSERT INTO user_account (user_id, employee_id, username, password_hash, role, last_login)
VALUES (
    @finance_user_id, -- user_id from users table
    2, -- employee_id (linked to Maria Elena Rodriguez - CFO)
    'finance.admin', -- username
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password hash for 'Finance2025'
    'Accounting Admin', -- role (must be exactly 'Accounting Admin')
    NULL -- last_login will be set automatically on first login
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = VALUES(role);

-- ========================================
-- VERIFICATION
-- ========================================

SELECT '========================================' AS '';
SELECT 'ACCOUNTING ADMIN ACCOUNT CREATED SUCCESSFULLY!' AS status;
SELECT '========================================' AS '';

-- Display account details
SELECT 
    'Account Details' AS info_type,
    u.id as user_id,
    u.username,
    u.email,
    u.full_name,
    u.is_active,
    r.name as role
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN roles r ON ur.role_id = r.id
WHERE u.username = 'finance.admin';

-- Display user_account details
SELECT 
    'User Account Details' AS info_type,
    ua.user_id,
    ua.employee_id,
    ua.username,
    ua.role
FROM user_account ua
WHERE ua.username = 'finance.admin';

SELECT '========================================' AS '';
SELECT 'LOGIN CREDENTIALS:' AS '';
SELECT 'Email: finance.admin@evergreen.com' AS credential;
SELECT 'Username: finance.admin' AS credential;
SELECT 'Password: Finance2025' AS credential;
SELECT '========================================' AS '';
