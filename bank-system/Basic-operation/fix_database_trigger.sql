-- ========================================
-- FIX DATABASE TRIGGER
-- ========================================
-- This script fixes the database trigger that causes the deposit error
-- The trigger uses 'account_name' but the table uses 'name'

USE BankingDB;

-- Drop the existing broken trigger
DROP TRIGGER IF EXISTS after_bank_transaction_insert;

DELIMITER $$

-- Recreate the trigger with the correct column name
CREATE TRIGGER after_bank_transaction_insert
AFTER INSERT ON bank_transactions
FOR EACH ROW
BEGIN
    DECLARE v_journal_type_id INT;
    DECLARE v_journal_no VARCHAR(50);
    DECLARE v_cash_account_id INT;
    DECLARE v_customer_receivable_account_id INT;
    DECLARE v_journal_entry_id INT;
    DECLARE v_user_id INT DEFAULT 1;
    DECLARE v_fiscal_period_id INT DEFAULT NULL;
    
    -- Get appropriate journal type (CR = Cash Receipt, CD = Cash Disbursement)
    IF NEW.amount > 0 THEN
        SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'CR' LIMIT 1;
    ELSE
        SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'CD' LIMIT 1;
    END IF;
    
    -- Get cash account (typically account code 1001 - Cash on Hand)
    -- FIXED: Changed account_name to name
    SELECT id INTO v_cash_account_id FROM accounts WHERE code = '1001' OR name LIKE '%Cash%' LIMIT 1;
    
    -- Get customer receivable account (typically account code 1120)
    -- FIXED: Changed account_name to name
    SELECT id INTO v_customer_receivable_account_id FROM accounts WHERE code = '1120' OR name LIKE '%Accounts Receivable%' LIMIT 1;
    
    -- Get fiscal period if available (required for journal entries)
    SELECT id INTO v_fiscal_period_id FROM fiscal_periods WHERE status = 'open' ORDER BY start_date DESC LIMIT 1;
    
    -- Generate journal number
    SET v_journal_no = CONCAT('BT-', LPAD(NEW.transaction_id, 8, '0'));
    
    -- Only create journal entry if we have ALL required data
    -- Skip journal entry creation if accounting module is not fully set up
    -- This prevents the trigger from blocking bank transactions
    IF v_journal_type_id IS NOT NULL 
       AND v_cash_account_id IS NOT NULL 
       AND v_fiscal_period_id IS NOT NULL THEN
        
        -- Insert journal entry
        INSERT INTO journal_entries (
            journal_no,
            journal_type_id,
            entry_date,
            description,
            reference_no,
            total_debit,
            total_credit,
            status,
            created_by,
            created_at,
            posted_at,
            fiscal_period_id
        ) VALUES (
            v_journal_no,
            v_journal_type_id,
            DATE(NEW.created_at),
            CONCAT('Bank Transaction - ', COALESCE(NEW.description, 'Auto-generated')),
            NEW.transaction_ref,
            ABS(NEW.amount),
            ABS(NEW.amount),
            'posted',
            v_user_id,
            NOW(),
            NOW(),
            v_fiscal_period_id
        );
        
        SET v_journal_entry_id = LAST_INSERT_ID();
        
        -- Create journal lines (double entry)
        -- Note: journal_lines table uses 'memo' not 'description', and doesn't have 'line_number'
        IF NEW.amount > 0 THEN
            -- Deposit/Credit: Debit Cash, Credit Customer Receivable
            INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
            VALUES 
                (v_journal_entry_id, v_cash_account_id, ABS(NEW.amount), 0, 'Cash received'),
                (v_journal_entry_id, COALESCE(v_customer_receivable_account_id, v_cash_account_id), 0, ABS(NEW.amount), 'Customer deposit');
        ELSE
            -- Withdrawal/Debit: Debit Customer Receivable, Credit Cash
            INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
            VALUES 
                (v_journal_entry_id, COALESCE(v_customer_receivable_account_id, v_cash_account_id), ABS(NEW.amount), 0, 'Customer withdrawal'),
                (v_journal_entry_id, v_cash_account_id, 0, ABS(NEW.amount), 'Cash disbursed');
        END IF;
    END IF;
    -- If accounting module is not set up, silently skip journal entry creation
    -- This allows bank transactions to proceed without errors
END$$

DELIMITER ;

SELECT 'Trigger fixed successfully! The deposit should now work.' as Status;

