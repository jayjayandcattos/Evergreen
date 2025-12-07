-- Migration: Update bank_customers table to match provided schema
-- Adds all missing fields from the user's schema specification

USE BankingDB;

-- Add personal information fields
ALTER TABLE bank_customers 
ADD COLUMN last_name VARCHAR(100) NULL AFTER application_id,
ADD COLUMN first_name VARCHAR(100) NULL AFTER last_name,
ADD COLUMN middle_name VARCHAR(100) NULL AFTER first_name;

-- Add contact and location fields
ALTER TABLE bank_customers 
ADD COLUMN address TEXT NULL AFTER middle_name,
ADD COLUMN city_province VARCHAR(200) NULL AFTER address,
ADD COLUMN contact_number VARCHAR(20) NULL AFTER email;

-- Add birthday field
ALTER TABLE bank_customers 
ADD COLUMN birthday DATE NULL AFTER contact_number;

-- Add verification and bank fields
ALTER TABLE bank_customers 
ADD COLUMN verification_code VARCHAR(10) NULL AFTER password_hash,
ADD COLUMN bank_id INT NULL AFTER verification_code;

-- Add referral system fields
ALTER TABLE bank_customers 
ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL AFTER bank_id,
ADD COLUMN referred_by_customer_id INT NULL AFTER referral_code,
ADD COLUMN total_points INT DEFAULT 0 AFTER referred_by_customer_id;

-- Add indexes for performance
ALTER TABLE bank_customers 
ADD INDEX idx_last_name (last_name),
ADD INDEX idx_first_name (first_name),
ADD INDEX idx_contact_number (contact_number),
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by_customer_id);

-- Add foreign key for referral system (self-referencing)
ALTER TABLE bank_customers
ADD CONSTRAINT fk_referred_by_customer
FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id)
ON DELETE SET NULL;

-- Add comment to table
ALTER TABLE bank_customers COMMENT = 'Complete customer profile with personal info, contact details, and referral system';
