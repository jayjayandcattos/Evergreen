-- ========================================
-- MIGRATION: Add barangay column
-- ========================================
-- Run this if you already have the account_applications table
-- and need to add the barangay column

USE BankingDB;

-- Add barangay column after street_address
ALTER TABLE account_applications 
ADD COLUMN barangay VARCHAR(150) NOT NULL DEFAULT '' 
AFTER street_address;

-- Verify the column was added
DESCRIBE account_applications;

-- Note: You may need to update existing records with barangay data
-- Example:
-- UPDATE account_applications SET barangay = 'Project 6' WHERE city = 'Quezon City';
