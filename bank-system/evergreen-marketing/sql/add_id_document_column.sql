-- ========================================
-- Migration: Add ID Document Path Column
-- ========================================
-- Run this script to add the id_document_path column to existing databases
-- This column stores the path to uploaded ID documents for verification

USE BankingDB;

-- Add id_document_path column if it doesn't exist
SET @column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'account_applications' 
    AND COLUMN_NAME = 'id_document_path'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE account_applications ADD COLUMN id_document_path VARCHAR(255) DEFAULT NULL COMMENT "Path to uploaded ID document" AFTER id_number',
    'SELECT "Column id_document_path already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update ssn column comment to reflect TIN usage
ALTER TABLE account_applications MODIFY COLUMN ssn VARCHAR(50) NOT NULL COMMENT 'TIN (Tax Identification Number)';

-- Update id_type column comment for Philippine IDs
ALTER TABLE account_applications MODIFY COLUMN id_type VARCHAR(50) NOT NULL COMMENT 'Philippine Government ID Type';

SELECT 'Migration completed successfully!' AS status;
