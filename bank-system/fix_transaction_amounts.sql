-- Fix Transaction Amounts
-- This script converts negative amounts to positive amounts in bank_transactions table
-- The balance calculation logic applies the sign based on transaction type

-- Update Transfer Out transactions (type_id = 8) - convert negative to positive
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 8 AND amount < 0;

-- Update Service Charge transactions (type_id = 5) - convert negative to positive
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 5 AND amount < 0;

-- Update Withdrawal transactions (type_id = 3) - convert negative to positive
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 3 AND amount < 0;

-- Update Loan Payment transactions (type_id = 7) - convert negative to positive
UPDATE bank_transactions 
SET amount = ABS(amount) 
WHERE transaction_type_id = 7 AND amount < 0;

-- Verify the changes
SELECT 
    tt.type_name,
    COUNT(*) as transaction_count,
    MIN(t.amount) as min_amount,
    MAX(t.amount) as max_amount,
    SUM(t.amount) as total_amount
FROM bank_transactions t
INNER JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
GROUP BY tt.type_name
ORDER BY tt.type_name;
