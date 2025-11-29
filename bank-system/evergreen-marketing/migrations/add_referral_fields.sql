-- Migration: Add referral code fields to bank_customers table
-- Date: 2025-01-XX
-- Description: Adds referral_code, total_points, and referred_by_customer_id to support referral system

-- Add referral fields to bank_customers table
ALTER TABLE bank_customers 
ADD COLUMN referral_code VARCHAR(20) UNIQUE NULL,
ADD COLUMN total_points DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN referred_by_customer_id INT NULL,
ADD INDEX idx_referral_code (referral_code),
ADD INDEX idx_referred_by (referred_by_customer_id);

-- Add foreign key constraint (if not already exists)
-- Note: This may fail if the constraint already exists, which is fine
ALTER TABLE bank_customers 
ADD CONSTRAINT fk_referred_by 
FOREIGN KEY (referred_by_customer_id) REFERENCES bank_customers(customer_id) ON DELETE SET NULL;

