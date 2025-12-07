-- Fix broken customer links
-- Link customer_id 6 to application_id 1 (or create proper application)

UPDATE bank_customers SET application_id = 1 WHERE customer_id = 6 AND application_id IS NULL;
UPDATE bank_customers SET application_id = 2 WHERE customer_id = 7 AND application_id IS NULL;

-- Verify the fix
SELECT 
    ca.account_number,
    ca.customer_id,
    bc.application_id,
    aa.first_name,
    aa.last_name
FROM customer_accounts ca
INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
LEFT JOIN account_applications aa ON bc.application_id = aa.application_id
WHERE ca.account_number IN ('SA-6837-2025', 'SA-9526-2025');
