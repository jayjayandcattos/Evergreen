-- ========================================
-- TEMPORARY FIX: DISABLE THE TRIGGER
-- ========================================
-- This will disable the trigger temporarily so deposits/withdrawals work
-- You can re-enable it later after fixing the trigger properly

USE BankingDB;

-- Simply drop the trigger - deposits/withdrawals will work without journal entries
DROP TRIGGER IF EXISTS after_bank_transaction_insert;

SELECT 'Trigger disabled. Deposits and withdrawals should now work without errors.' as Status;
SELECT 'Note: Journal entries will not be automatically created until you fix and re-enable the trigger.' as Warning;

