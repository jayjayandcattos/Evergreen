-- ========================================
-- MIGRATION: evergreen_bank to BankingDB
-- ========================================
-- This script helps migrate data from the old evergreen_bank database
-- to the new unified BankingDB database

-- ========================================
-- STEP 1: Check if old database exists
-- ========================================

SELECT 'Checking if evergreen_bank exists...' AS status;

SELECT SCHEMA_NAME 
FROM INFORMATION_SCHEMA.SCHEMATA 
WHERE SCHEMA_NAME = 'evergreen_bank';

-- ========================================
-- STEP 2: Migrate bank_customers data
-- ========================================

-- If you have existing customers in evergreen_bank, migrate them to BankingDB

USE BankingDB;

-- Insert customers from old database (if it exists)
-- Uncomment and run if you have data to migrate:

/*
INSERT INTO bank_customers (
    customer_id, last_name, first_name, middle_name, email, password_hash,
    referral_code, total_points, created_at, created_by_employee_id
)
SELECT 
    customer_id, last_name, first_name, middle_name, email, password,
    referral_code, total_points, created_at, NULL
FROM evergreen_bank.bank_customers
WHERE NOT EXISTS (
    SELECT 1 FROM BankingDB.bank_customers 
    WHERE BankingDB.bank_customers.email = evergreen_bank.bank_customers.email
);
*/

-- ========================================
-- STEP 3: Migrate bank_users data (if different from bank_customers)
-- ========================================

/*
INSERT INTO bank_users (
    id, first_name, middle_name, last_name, address, city_province,
    email, contact_number, birthday, password, verification_code,
    bank_id, total_points, created_at, is_verified
)
SELECT 
    id, first_name, middle_name, last_name, address, city_province,
    email, contact_number, birthday, password, verification_code,
    bank_id, total_points, created_at, is_verified
FROM evergreen_bank.bank_users
WHERE NOT EXISTS (
    SELECT 1 FROM BankingDB.bank_users 
    WHERE BankingDB.bank_users.email = evergreen_bank.bank_users.email
);
*/

-- ========================================
-- STEP 4: Verify migration
-- ========================================

SELECT 'Verifying data in BankingDB...' AS status;

-- Check bank_customers count
SELECT 'bank_customers' AS table_name, COUNT(*) AS record_count 
FROM BankingDB.bank_customers;

-- Check bank_users count (if table exists)
-- SELECT 'bank_users' AS table_name, COUNT(*) AS record_count 
-- FROM BankingDB.bank_users;

-- ========================================
-- STEP 5: Backup old database (OPTIONAL)
-- ========================================

-- Before dropping the old database, create a backup:
-- mysqldump -u root -p evergreen_bank > evergreen_bank_backup.sql

-- ========================================
-- STEP 6: Drop old database (OPTIONAL - BE CAREFUL!)
-- ========================================

-- Only run this after confirming all data is migrated successfully
-- and you have a backup!

-- DROP DATABASE IF EXISTS evergreen_bank;

--