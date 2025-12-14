-- Add ID Image Columns to account_applications table
-- This migration adds columns to store front and back images of valid IDs

USE BankingDB;

-- Add columns for ID images if they don't exist
ALTER TABLE account_applications
ADD COLUMN IF NOT EXISTS id_front_image VARCHAR(255) DEFAULT NULL COMMENT 'Path to front image of valid ID',
ADD COLUMN IF NOT EXISTS id_back_image VARCHAR(255) DEFAULT NULL COMMENT 'Path to back image of valid ID',
ADD COLUMN IF NOT EXISTS id_uploaded_at DATETIME DEFAULT NULL COMMENT 'Timestamp when ID images were uploaded';

-- Add index for faster queries
ALTER TABLE account_applications
ADD INDEX IF NOT EXISTS idx_id_uploaded (id_uploaded_at);

-- Display result
SELECT 'ID image columns added successfully to account_applications table' AS status;
