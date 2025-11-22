-- Migration: Add bank_id column to bank_customers table
-- Date: 2025-01-XX
-- Description: Adds bank_id column to support login authentication

-- Add bank_id column to bank_customers table if it doesn't exist
ALTER TABLE bank_customers 
ADD COLUMN IF NOT EXISTS bank_id VARCHAR(10) UNIQUE NULL,
ADD INDEX IF NOT EXISTS idx_bank_id (bank_id);

