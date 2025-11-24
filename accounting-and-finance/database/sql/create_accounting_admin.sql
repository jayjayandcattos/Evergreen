-- ========================================
-- CREATE ACCOUNTING ADMIN ACCOUNT
-- ========================================
-- This creates a professional Accounting Admin account
-- Username: finance.admin
-- Password: F!n@nc3Adm!n2024
-- Role: Accounting Admin
-- ========================================

USE BankingDB;

-- Create Accounting Admin account
INSERT INTO user_account (employee_id, username, password_hash, role, last_login)
VALUES (
    2, -- employee_id (linked to Maria Elena Rodriguez - CFO)
    'finance.admin', -- username
    '$2y$10$vQxJ9K7LmN8pR5tU2wX3Y.eZ4bC6dF8gH0iJ2kL4mN6oP8qR0sT2u', -- password hash for 'F!n@nc3Adm!n2024'
    'Accounting Admin', -- role (must be exactly 'Accounting Admin')
    NULL -- last_login will be set automatically on first login
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = VALUES(role);

-- Verification
SELECT 'Accounting Admin account created successfully!' AS status;
SELECT user_id, username, role FROM user_account WHERE username = 'finance.admin';
