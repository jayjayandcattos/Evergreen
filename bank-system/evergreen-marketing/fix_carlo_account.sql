-- Fix Carlo Baclao's account
-- This script creates the missing bank_customers record and a new account

USE BankingDB;

-- 1. Create bank_customers record for Carlo Baclao
INSERT INTO bank_customers (first_name, middle_name, last_name, email, password_hash, created_at)
SELECT 
    bu.first_name,
    bu.middle_name,
    bu.last_name,
    bu.email,
    bu.password,
    NOW()
FROM bank_users bu
WHERE bu.email = 'xeroha6543@okcdeals.com'
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name);

-- Get the customer_id that was just created
SET @carlo_customer_id = (SELECT customer_id FROM bank_customers WHERE email = 'xeroha6543@okcdeals.com');

-- 2. Create email record
INSERT INTO emails (customer_id, email, is_primary, created_at)
VALUES (@carlo_customer_id, 'xeroha6543@okcdeals.com', 1, NOW())
ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary);

-- 3. Create phone record (using a placeholder if not available)
INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
SELECT @carlo_customer_id, bu.contact_number, 'mobile', 1, NOW()
FROM bank_users bu
WHERE bu.email = 'xeroha6543@okcdeals.com'
ON DUPLICATE KEY UPDATE phone_type = VALUES(phone_type);

-- 4. Create address record
INSERT INTO addresses (customer_id, address_line, city, province_id, address_type, is_primary, created_at)
SELECT @carlo_customer_id, bu.address, bu.city_province, 1, 'home', 1, NOW()
FROM bank_users bu
WHERE bu.email = 'xeroha6543@okcdeals.com'
ON DUPLICATE KEY UPDATE address_type = VALUES(address_type);

-- 5. Create a new savings account for Carlo
SET @account_number = CONCAT('SA-', LPAD(@carlo_customer_id, 6, '0'), '-', YEAR(NOW()));

INSERT INTO customer_accounts (customer_id, account_number, account_type_id, interest_rate, is_locked, created_at)
VALUES (@carlo_customer_id, @account_number, 1, 2.50, 0, NOW());

SET @carlo_account_id = LAST_INSERT_ID();

-- 6. Link the account to the customer
INSERT INTO customer_linked_accounts (customer_id, account_id, linked_at, is_active)
VALUES (@carlo_customer_id, @carlo_account_id, NOW(), 1);

-- 7. Create welcome bonus transaction
SET @transaction_ref = CONCAT('TXN-WELCOME-', @carlo_customer_id, '-', UNIX_TIMESTAMP());

INSERT INTO bank_transactions (transaction_ref, account_id, transaction_type_id, amount, description, created_at)
VALUES (@transaction_ref, @carlo_account_id, 1, 1000.00, 'Welcome Bonus - Account Opening', NOW());

-- Show the results
SELECT 'Carlo Baclao Account Created Successfully!' as Status;
SELECT @carlo_customer_id as customer_id, @account_number as account_number, @carlo_account_id as account_id;

-- Verify the data
SELECT 'Bank Customers Record:' as Info;
SELECT customer_id, first_name, last_name, email FROM bank_customers WHERE customer_id = @carlo_customer_id;

SELECT 'Customer Account:' as Info;
SELECT account_id, account_number, account_type_id FROM customer_accounts WHERE account_id = @carlo_account_id;

SELECT 'Account Balance:' as Info;
SELECT SUM(amount) as balance FROM bank_transactions WHERE account_id = @carlo_account_id;
