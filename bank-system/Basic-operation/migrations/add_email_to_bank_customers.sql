-- Migration: Add email column to bank_customers table
-- This allows customers to login with email directly
-- Run this in phpMyAdmin after running unified_schema.sql

USE BankingDB;

-- Step 1: Add email column to bank_customers (if not exists)
ALTER TABLE bank_customers 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER middle_name,
ADD INDEX IF NOT EXISTS idx_email (email);

-- Step 2: Update existing bank_customers with emails from emails table
UPDATE bank_customers bc
INNER JOIN emails e ON bc.customer_id = e.customer_id AND e.is_primary = 1
SET bc.email = e.email
WHERE bc.email IS NULL OR bc.email = '';

-- Step 3: Make email column NOT NULL and UNIQUE after data migration
ALTER TABLE bank_customers 
MODIFY COLUMN email VARCHAR(255) NOT NULL UNIQUE;

-- Verification query
SELECT 
    bc.customer_id,
    bc.first_name,
    bc.last_name,
    bc.email,
    e.email AS email_from_emails_table
FROM bank_customers bc
LEFT JOIN emails e ON bc.customer_id = e.customer_id AND e.is_primary = 1
LIMIT 10;
