-- Migration: Add customer_id column to account_applications table
-- Date: 2025-12-07
-- Description: Adds customer_id to track which customer is opening the account

-- Add customer_id column
ALTER TABLE account_applications 
ADD COLUMN customer_id INT DEFAULT NULL COMMENT 'Reference to existing customer opening the account' 
AFTER application_status;

-- Add index for better performance
ALTER TABLE account_applications 
ADD INDEX idx_customer_id (customer_id);

-- Add foreign key constraint (if bank_customers table exists)
-- ALTER TABLE account_applications 
-- ADD CONSTRAINT fk_account_applications_customer 
-- FOREIGN KEY (customer_id) REFERENCES bank_customers(customer_id) 
-- ON DELETE SET NULL;

-- Note: The foreign key constraint is commented out by default
-- Uncomment it if you want to enforce referential integrity
