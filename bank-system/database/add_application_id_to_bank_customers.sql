-- Migration: Add application_id to bank_customers table
-- This allows bank_customers to reference the application they came from

USE BankingDB;

-- Add application_id column to bank_customers
ALTER TABLE bank_customers 
ADD COLUMN application_id INT NULL AFTER customer_id,
ADD INDEX idx_application_id (application_id);

-- Add foreign key to link to account_applications (if needed)
-- Note: Commenting this out for now as it requires account_applications.application_id to be the primary key
-- ALTER TABLE bank_customers
-- ADD CONSTRAINT fk_bank_customers_application
-- FOREIGN KEY (application_id) REFERENCES account_applications(application_id)
-- ON DELETE SET NULL;

-- Also update account_applications to have customer_id for bidirectional linking
-- This was already done in a previous migration, but adding comment for clarity
-- ALTER TABLE account_applications ADD COLUMN customer_id INT NULL;
-- UPDATE account_applications aa 
-- INNER JOIN bank_customers bc ON aa.application_id = bc.application_id
-- SET aa.customer_id = bc.customer_id;
