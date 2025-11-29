USE BankingDB;

-- Update all employees with a fresh password hash for "password"
UPDATE bank_employees SET password_hash='$2y$10$kCoxX3xFyKc0QPuoiUdqVeDshsMP54kAS5DPoP6YLqFbozEkjh89W' WHERE username='admin';
UPDATE bank_employees SET password_hash='$2y$10$kCoxX3xFyKc0QPuoiUdqVeDshsMP54kAS5DPoP6YLqFbozEkjh89W' WHERE username='teller1';
UPDATE bank_employees SET password_hash='$2y$10$kCoxX3xFyKc0QPuoiUdqVeDshsMP54kAS5DPoP6YLqFbozEkjh89W' WHERE username='testuser';

-- Verify
SELECT username, email, first_name, last_name, role FROM bank_employees WHERE username IN ('admin', 'teller1', 'testuser');
