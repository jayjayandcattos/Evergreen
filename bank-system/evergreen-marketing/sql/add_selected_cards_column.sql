-- ========================================
-- MIGRATION: Add selected_cards column
-- ========================================
-- Run this if you already have the account_applications table
-- and need to add the selected_cards column

USE BankingDB;

-- Add selected_cards column if it doesn't exist
ALTER TABLE account_applications 
ADD COLUMN IF NOT EXISTS selected_cards TEXT COMMENT 'Comma-separated: debit, credit, prepaid' 
AFTER account_type;

-- Verify the column was added
DESCRIBE account_applications;
