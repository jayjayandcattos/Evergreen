-- ========================================
-- HR MANAGER ROLE SETUP
-- ========================================
-- This file sets up the HR Manager role functionality
-- Compatible with unified_schema.sql structure
-- 
-- Database: BankingDB
-- Purpose: Add HR Manager role support to existing HRIS system
--
-- ========================================
-- SCHEMA VALIDATION
-- ========================================

-- Ensure user_account table has role column with correct type
-- This is safe to run even if column already exists
SET @db_exists = (SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'BankingDB');

SET @table_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account'
);

SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

-- Add role column if it doesn't exist
SET @sql = IF(@column_exists = 0 AND @table_exists > 0,
    'ALTER TABLE user_account ADD COLUMN role VARCHAR(20) DEFAULT NULL AFTER password_hash',
    'SELECT "Role column already exists or table not found" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure role column is VARCHAR(20) if it exists with different type
SET @column_type = (
    SELECT DATA_TYPE 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

SET @column_length = (
    SELECT CHARACTER_MAXIMUM_LENGTH 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'user_account' 
    AND COLUMN_NAME = 'role'
);

-- Modify column if type or length is incorrect
SET @sql = IF(@column_exists > 0 AND (@column_type != 'varchar' OR @column_length != 20),
    'ALTER TABLE user_account MODIFY COLUMN role VARCHAR(20) DEFAULT NULL',
    'SELECT "Role column type is correct" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================
-- DATA MIGRATION
-- ========================================

-- Update existing user_account records with NULL role to 'Admin' for backward compatibility
-- This ensures all existing admin accounts are properly marked
UPDATE user_account 
SET role = 'Admin' 
WHERE role IS NULL 
AND username IS NOT NULL;

-- Ensure any existing admin accounts explicitly have 'Admin' role
-- (in case they were created with different role values)
UPDATE user_account 
SET role = 'Admin' 
WHERE username = 'admin' 
AND (role IS NULL OR role != 'Admin');

-- ========================================
-- SAMPLE DATA (OPTIONAL)
-- ========================================
-- Uncomment and modify the following section to create a sample HR Manager account
-- Replace the employee_id with an actual employee ID from your employee table
-- Replace the password hash with one generated using PHP: password_hash('your_password', PASSWORD_DEFAULT)

/*
-- Example: Create an HR Manager account
-- Password: 'password' (hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
-- IMPORTANT: Change the password hash before using in production!

INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    NULL, -- Replace with a valid employee_id from the employee table, or leave NULL
    'hrmanager', -- Username for HR Manager account
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password hash for 'password'
    'HR Manager', -- Role must be exactly 'HR Manager' (case-sensitive)
    NULL -- last_login will be set automatically on first login
);

-- To create a new password hash, use PHP:
-- <?php
-- echo password_hash('your_secure_password', PASSWORD_DEFAULT);
-- ?>
-- Then replace the password_hash value above with the generated hash.
*/

-- ========================================
-- VERIFICATION QUERIES
-- ========================================
-- Run these queries to verify the setup:

-- Check user_account table structure
-- SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_DEFAULT
-- FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = 'BankingDB' 
-- AND TABLE_NAME = 'user_account' 
-- AND COLUMN_NAME = 'role';

-- List all user accounts with their roles
-- SELECT user_id, username, role, last_login 
-- FROM user_account 
-- ORDER BY role, username;

-- Count users by role
-- SELECT role, COUNT(*) as user_count 
-- FROM user_account 
-- GROUP BY role;

-- ========================================
-- ROLLBACK INSTRUCTIONS (if needed)
-- ========================================
-- If you need to rollback this migration:

-- Remove HR Manager accounts (if any were created)
-- DELETE FROM user_account WHERE role = 'HR Manager';

-- Reset all roles to NULL (use with caution)
-- UPDATE user_account SET role = NULL;

-- Remove role column entirely (not recommended if other parts of system depend on it)
-- ALTER TABLE user_account DROP COLUMN role;

-- ========================================
-- NOTES
-- ========================================
-- 1. The role column supports two values: 'Admin' and 'HR Manager'
-- 2. Role names are case-sensitive - use exactly 'Admin' or 'HR Manager'
-- 3. HR Managers have view-only access to all pages
-- 4. Only Admins can add, edit, delete, or archive records
-- 5. All existing accounts without a role are automatically set to 'Admin'
-- 6. Always use password_hash() function in PHP to generate secure password hashes
-- 7. Never store plain text passwords in the database

