-- ========================================
-- INTEGRATION TRIGGERS FOR AUTOMATIC JOURNAL ENTRIES
-- Connects Bank System, HRIS, and Loan Subsystem to Accounting
-- ========================================

USE BankingDB;

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS after_bank_transaction_insert;
DROP TRIGGER IF EXISTS after_loan_disbursement;
DROP TRIGGER IF EXISTS after_loan_payment;
DROP TRIGGER IF EXISTS after_payroll_run_insert;

DELIMITER $$

-- ========================================
-- 1. BANK TRANSACTION TRIGGER
-- Automatically creates journal entry when bank transaction occurs
-- ========================================
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
    
    -- Get appropriate journal type (CR = Cash Receipt, CD = Cash Disbursement)
    IF NEW.amount > 0 THEN
        SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'CR' LIMIT 1;
    ELSE
        SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'CD' LIMIT 1;
    END IF;
    
    -- Get cash account (typically account code 1001 - Cash on Hand)
    SELECT id INTO v_cash_account_id FROM accounts WHERE code = '1001' OR account_name LIKE '%Cash%' LIMIT 1;
    
    -- Get customer receivable account (typically account code 1120)
    SELECT id INTO v_customer_receivable_account_id FROM accounts WHERE code = '1120' OR account_name LIKE '%Accounts Receivable%' LIMIT 1;
    
    -- Generate journal number
    SET v_journal_no = CONCAT('BT-', LPAD(NEW.transaction_id, 8, '0'));
    
    -- Only create journal entry if we have the required accounts
    IF v_journal_type_id IS NOT NULL AND v_cash_account_id IS NOT NULL THEN
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
            posted_at
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
            NOW()
        );
        
        SET v_journal_entry_id = LAST_INSERT_ID();
        
        -- Create journal lines (double entry)
        IF NEW.amount > 0 THEN
            -- Deposit/Credit: Debit Cash, Credit Customer Receivable
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES 
                (v_journal_entry_id, 1, v_cash_account_id, ABS(NEW.amount), 0, 'Cash received'),
                (v_journal_entry_id, 2, v_customer_receivable_account_id, 0, ABS(NEW.amount), 'Customer deposit');
        ELSE
            -- Withdrawal/Debit: Debit Customer Receivable, Credit Cash
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES 
                (v_journal_entry_id, 1, v_customer_receivable_account_id, ABS(NEW.amount), 0, 'Customer withdrawal'),
                (v_journal_entry_id, 2, v_cash_account_id, 0, ABS(NEW.amount), 'Cash disbursed');
        END IF;
    END IF;
END$$

-- ========================================
-- 2. LOAN DISBURSEMENT TRIGGER
-- Automatically creates journal entry when loan is disbursed
-- ========================================
CREATE TRIGGER after_loan_disbursement
AFTER UPDATE ON loans
FOR EACH ROW
BEGIN
    DECLARE v_journal_type_id INT;
    DECLARE v_journal_no VARCHAR(50);
    DECLARE v_loan_receivable_account_id INT;
    DECLARE v_cash_account_id INT;
    DECLARE v_interest_income_account_id INT;
    DECLARE v_journal_entry_id INT;
    DECLARE v_user_id INT DEFAULT 1;
    
    -- Only trigger when loan status changes to 'disbursed'
    IF OLD.status != 'disbursed' AND NEW.status = 'disbursed' THEN
        
        -- Get journal type for loan disbursement
        SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'GJ' OR name LIKE '%General%' LIMIT 1;
        
        -- Get loan receivable account (typically 1130)
        SELECT id INTO v_loan_receivable_account_id FROM accounts WHERE code = '1130' OR account_name LIKE '%Loan%Receivable%' LIMIT 1;
        
        -- Get cash account
        SELECT id INTO v_cash_account_id FROM accounts WHERE code = '1001' OR account_name LIKE '%Cash%' LIMIT 1;
        
        -- Generate journal number
        SET v_journal_no = CONCAT('LD-', LPAD(NEW.id, 8, '0'));
        
        IF v_journal_type_id IS NOT NULL AND v_loan_receivable_account_id IS NOT NULL AND v_cash_account_id IS NOT NULL THEN
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
                posted_at
            ) VALUES (
                v_journal_no,
                v_journal_type_id,
                DATE(NEW.start_date),
                CONCAT('Loan Disbursement - ', NEW.loan_no),
                NEW.loan_no,
                NEW.principal_amount,
                NEW.principal_amount,
                'posted',
                v_user_id,
                NOW(),
                NOW()
            );
            
            SET v_journal_entry_id = LAST_INSERT_ID();
            
            -- Create journal lines: Debit Loan Receivable, Credit Cash
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES 
                (v_journal_entry_id, 1, v_loan_receivable_account_id, NEW.principal_amount, 0, 'Loan disbursed to customer'),
                (v_journal_entry_id, 2, v_cash_account_id, 0, NEW.principal_amount, 'Cash paid out');
        END IF;
    END IF;
END$$

-- ========================================
-- 3. LOAN PAYMENT TRIGGER  
-- Automatically creates journal entry when loan payment is received
-- ========================================
CREATE TRIGGER after_loan_payment
AFTER INSERT ON loan_payments
FOR EACH ROW
BEGIN
    DECLARE v_journal_type_id INT;
    DECLARE v_journal_no VARCHAR(50);
    DECLARE v_cash_account_id INT;
    DECLARE v_loan_receivable_account_id INT;
    DECLARE v_interest_income_account_id INT;
    DECLARE v_journal_entry_id INT;
    DECLARE v_user_id INT DEFAULT 1;
    
    -- Get journal type for cash receipt
    SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'CR' LIMIT 1;
    
    -- Get accounts
    SELECT id INTO v_cash_account_id FROM accounts WHERE code = '1001' OR account_name LIKE '%Cash%' LIMIT 1;
    SELECT id INTO v_loan_receivable_account_id FROM accounts WHERE code = '1130' OR account_name LIKE '%Loan%Receivable%' LIMIT 1;
    SELECT id INTO v_interest_income_account_id FROM accounts WHERE code = '4100' OR account_name LIKE '%Interest%Income%' LIMIT 1;
    
    -- Generate journal number
    SET v_journal_no = CONCAT('LP-', LPAD(NEW.id, 8, '0'));
    
    IF v_journal_type_id IS NOT NULL AND v_cash_account_id IS NOT NULL AND v_loan_receivable_account_id IS NOT NULL THEN
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
            posted_at
        ) VALUES (
            v_journal_no,
            v_journal_type_id,
            DATE(NEW.payment_date),
                CONCAT('Loan Payment - ', COALESCE(NEW.payment_reference, '')),
            NEW.payment_reference,
            NEW.amount,
            NEW.amount,
            'posted',
            v_user_id,
            NOW(),
            NOW()
        );
        
        SET v_journal_entry_id = LAST_INSERT_ID();
        
        -- Create journal lines
        -- Debit: Cash
        INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
        VALUES (v_journal_entry_id, 1, v_cash_account_id, NEW.amount, 0, 'Loan payment received');
        
        -- Credit: Loan Receivable (principal portion)
        IF NEW.principal_amount > 0 THEN
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES (v_journal_entry_id, 2, v_loan_receivable_account_id, 0, NEW.principal_amount, 'Principal payment');
        END IF;
        
        -- Credit: Interest Income (interest portion)
        IF NEW.interest_amount > 0 AND v_interest_income_account_id IS NOT NULL THEN
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES (v_journal_entry_id, 3, v_interest_income_account_id, 0, NEW.interest_amount, 'Interest income');
        END IF;
    END IF;
END$$

-- ========================================
-- 4. PAYROLL RUN TRIGGER
-- Automatically creates journal entry when payroll is processed
-- ========================================
CREATE TRIGGER after_payroll_run_insert
AFTER INSERT ON payroll_runs
FOR EACH ROW
BEGIN
    DECLARE v_journal_type_id INT;
    DECLARE v_journal_no VARCHAR(50);
    DECLARE v_salaries_expense_account_id INT;
    DECLARE v_cash_account_id INT;
    DECLARE v_payable_account_id INT;
    DECLARE v_journal_entry_id INT;
    DECLARE v_user_id INT DEFAULT 1;
    
    -- Get journal type for payroll
    SELECT id INTO v_journal_type_id FROM journal_types WHERE code = 'PR' LIMIT 1;
    
    -- Get accounts
    SELECT id INTO v_salaries_expense_account_id FROM accounts WHERE code = '5100' OR account_name LIKE '%Salaries%Expense%' OR account_name LIKE '%Wages%' LIMIT 1;
    SELECT id INTO v_cash_account_id FROM accounts WHERE code = '1001' OR account_name LIKE '%Cash%' LIMIT 1;
    SELECT id INTO v_payable_account_id FROM accounts WHERE code = '2110' OR account_name LIKE '%Salaries%Payable%' OR account_name LIKE '%Wages%Payable%' LIMIT 1;
    
    -- Generate journal number
    SET v_journal_no = CONCAT('PR-', LPAD(NEW.id, 8, '0'));
    
    IF v_journal_type_id IS NOT NULL AND v_salaries_expense_account_id IS NOT NULL THEN
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
            posted_at
        ) VALUES (
            v_journal_no,
            v_journal_type_id,
            DATE(NEW.run_at),
            CONCAT('Payroll Run - ID ', NEW.id),
            CONCAT('PR-', NEW.id),
            NEW.total_gross,
            NEW.total_net + NEW.total_deductions,
            'posted',
            v_user_id,
            NOW(),
            NOW()
        );
        
        SET v_journal_entry_id = LAST_INSERT_ID();
        
        -- Create journal lines
        -- Debit: Salaries Expense (gross pay)
        INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
        VALUES (v_journal_entry_id, 1, v_salaries_expense_account_id, NEW.total_gross, 0, 'Payroll expense');
        
        -- Credit: Cash/Bank (net pay)
        IF v_cash_account_id IS NOT NULL THEN
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES (v_journal_entry_id, 2, v_cash_account_id, 0, NEW.total_net, 'Net pay disbursed');
        END IF;
        
        -- Credit: Various Payables (deductions)
        IF NEW.total_deductions > 0 AND v_payable_account_id IS NOT NULL THEN
            INSERT INTO journal_lines (journal_entry_id, line_number, account_id, debit, credit, description)
            VALUES (v_journal_entry_id, 3, v_payable_account_id, 0, NEW.total_deductions, 'Payroll deductions payable');
        END IF;
    END IF;
END$$

DELIMITER ;

-- ========================================
-- VERIFY TRIGGER CREATION
-- ========================================
SHOW TRIGGERS WHERE `Trigger` LIKE 'after_%';

SELECT 'Integration triggers created successfully!' as Status;

