-- ========================================
-- COMPREHENSIVE ACCOUNTING & FINANCE DATA
-- ========================================
-- This file contains ALL sample data for the accounting system
-- Run this after schema.sql to populate the database with comprehensive test data
-- 
-- This file includes merged data from:
-- - Original Sampled_data.sql (comprehensive system data)
-- - sample_expense_data.sql (expense tracking module data)
-- - sample_loan_data.sql (loan accounting module data)
-- 
-- Instructions:
-- 1. Open phpMyAdmin: http://localhost/phpmyadmin
-- 2. Click "BankingDB" database
-- 3. Click "SQL" tab
-- 4. Copy this entire file and paste into SQL box
-- 5. Click "Go" button
-- 6. Wait for success messages
-- ========================================

USE BankingDB;

-- ========================================
-- 1. ADMIN USER & ROLES
-- ========================================

-- Insert the admin user
-- Password: admin123 (properly hashed with PASSWORD_DEFAULT)
INSERT INTO users (id, username, password_hash, email, full_name, is_active, created_at) 
VALUES (
    1,
    'admin',
    '$2y$10$0G6Iza9uWgZ1y0ea/5lf7.P3qcY6CVgisAdKlNvq.ZnYYc6F.xDXS',
    'admin@system.com',
    'System Administrator',
    TRUE,
    NOW()
) ON DUPLICATE KEY UPDATE 
    username = VALUES(username),
    password_hash = VALUES(password_hash);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('Administrator', 'Full system access with all privileges')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Assign admin role to the admin user
INSERT INTO user_roles (user_id, role_id) VALUES (1, 1) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);

-- ========================================
-- 2. ACCOUNT TYPES & CHART OF ACCOUNTS
-- ========================================

-- Insert comprehensive account types
INSERT INTO account_types (name, category, description) VALUES
-- Assets
('Current Assets', 'asset', 'Assets expected to be converted to cash within one year'),
('Non-Current Assets', 'asset', 'Long-term assets'),
('Fixed Assets', 'asset', 'Tangible long-term assets'),
('Intangible Assets', 'asset', 'Non-physical assets like patents, trademarks'),
('Accumulated Depreciation', 'asset', 'Contra-asset for depreciation'),

-- Liabilities
('Current Liabilities', 'liability', 'Obligations due within one year'),
('Non-Current Liabilities', 'liability', 'Long-term liabilities'),
('Accrued Liabilities', 'liability', 'Expenses incurred but not yet paid'),
('Deferred Revenue', 'liability', 'Revenue received but not yet earned'),

-- Equity
('Equity', 'equity', 'Owner equity and retained earnings'),
('Capital Stock', 'equity', 'Share capital'),
('Retained Earnings', 'equity', 'Accumulated profits'),

-- Revenue
('Operating Revenue', 'revenue', 'Revenue from primary business operations'),
('Other Revenue', 'revenue', 'Revenue from other sources'),
('Interest Income', 'revenue', 'Interest earned on investments'),

-- Expenses
('Operating Expenses', 'expense', 'Expenses from primary business operations'),
('Administrative Expenses', 'expense', 'General and administrative costs'),
('Cost of Sales', 'expense', 'Direct costs of goods sold'),
('Interest Expense', 'expense', 'Interest paid on loans'),
('Other Expenses', 'expense', 'Non-operating expenses')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Get account type IDs
SET @current_assets = (SELECT id FROM account_types WHERE name = 'Current Assets' LIMIT 1);
SET @noncurrent_assets = (SELECT id FROM account_types WHERE name = 'Non-Current Assets' LIMIT 1);
SET @fixed_assets = (SELECT id FROM account_types WHERE name = 'Fixed Assets' LIMIT 1);
SET @intangible_assets = (SELECT id FROM account_types WHERE name = 'Intangible Assets' LIMIT 1);
SET @accum_dep = (SELECT id FROM account_types WHERE name = 'Accumulated Depreciation' LIMIT 1);
SET @current_liabilities = (SELECT id FROM account_types WHERE name = 'Current Liabilities' LIMIT 1);
SET @noncurrent_liabilities = (SELECT id FROM account_types WHERE name = 'Non-Current Liabilities' LIMIT 1);
SET @accrued_liabilities = (SELECT id FROM account_types WHERE name = 'Accrued Liabilities' LIMIT 1);
SET @deferred_revenue = (SELECT id FROM account_types WHERE name = 'Deferred Revenue' LIMIT 1);
SET @equity_type = (SELECT id FROM account_types WHERE name = 'Equity' LIMIT 1);
SET @capital_stock = (SELECT id FROM account_types WHERE name = 'Capital Stock' LIMIT 1);
SET @retained_earnings = (SELECT id FROM account_types WHERE name = 'Retained Earnings' LIMIT 1);
SET @operating_revenue = (SELECT id FROM account_types WHERE name = 'Operating Revenue' LIMIT 1);
SET @other_revenue = (SELECT id FROM account_types WHERE name = 'Other Revenue' LIMIT 1);
SET @interest_income = (SELECT id FROM account_types WHERE name = 'Interest Income' LIMIT 1);
SET @operating_expenses = (SELECT id FROM account_types WHERE name = 'Operating Expenses' LIMIT 1);
SET @admin_expenses = (SELECT id FROM account_types WHERE name = 'Administrative Expenses' LIMIT 1);
SET @cogs = (SELECT id FROM account_types WHERE name = 'Cost of Sales' LIMIT 1);
SET @interest_expense = (SELECT id FROM account_types WHERE name = 'Interest Expense' LIMIT 1);
SET @other_expenses = (SELECT id FROM account_types WHERE name = 'Other Expenses' LIMIT 1);

-- Insert comprehensive chart of accounts
INSERT INTO accounts (code, name, type_id, description, is_active, created_by) VALUES
-- CURRENT ASSETS
('1001', 'Cash on Hand', @current_assets, 'Petty cash fund', TRUE, 1),
('1002', 'Cash in Bank - BDO', @current_assets, 'BDO Unibank current account', TRUE, 1),
('1003', 'Cash in Bank - BPI', @current_assets, 'BPI savings account', TRUE, 1),
('1004', 'Cash in Bank - Metrobank', @current_assets, 'Metrobank payroll account', TRUE, 1),
('1005', 'Cash in Bank - Security Bank', @current_assets, 'Security Bank investment account', TRUE, 1),
('1101', 'Accounts Receivable - Trade', @current_assets, 'Customer receivables', TRUE, 1),
('1102', 'Accounts Receivable - Other', @current_assets, 'Other receivables', TRUE, 1),
('1201', 'Inventory - Raw Materials', @current_assets, 'Raw materials inventory', TRUE, 1),
('1202', 'Inventory - Finished Goods', @current_assets, 'Finished goods inventory', TRUE, 1),
('1203', 'Inventory - Work in Process', @current_assets, 'Work in process inventory', TRUE, 1),
('1301', 'Prepaid Expenses', @current_assets, 'Prepaid rent, insurance, etc.', TRUE, 1),
('1302', 'Prepaid Insurance', @current_assets, 'Insurance premiums paid in advance', TRUE, 1),
('1303', 'Prepaid Rent', @current_assets, 'Rent paid in advance', TRUE, 1),
('1401', 'Other Current Assets', @current_assets, 'Other current assets', TRUE, 1),

-- NON-CURRENT ASSETS
('1501', 'Office Equipment', @fixed_assets, 'Computers, furniture, fixtures', TRUE, 1),
('1502', 'Machinery and Equipment', @fixed_assets, 'Production machinery', TRUE, 1),
('1503', 'Vehicles', @fixed_assets, 'Company vehicles', TRUE, 1),
('1504', 'Building', @fixed_assets, 'Office building', TRUE, 1),
('1505', 'Land', @fixed_assets, 'Land property', TRUE, 1),
('1510', 'Accumulated Depreciation - Equipment', @accum_dep, 'Equipment depreciation', TRUE, 1),
('1511', 'Accumulated Depreciation - Machinery', @accum_dep, 'Machinery depreciation', TRUE, 1),
('1512', 'Accumulated Depreciation - Vehicles', @accum_dep, 'Vehicle depreciation', TRUE, 1),
('1513', 'Accumulated Depreciation - Building', @accum_dep, 'Building depreciation', TRUE, 1),
('1601', 'Intangible Assets', @intangible_assets, 'Patents, trademarks, goodwill', TRUE, 1),
('1602', 'Software Licenses', @intangible_assets, 'Software and licenses', TRUE, 1),
('1701', 'Long-term Investments', @noncurrent_assets, 'Long-term investment securities', TRUE, 1),

-- CURRENT LIABILITIES
('2001', 'Accounts Payable - Trade', @current_liabilities, 'Supplier payables', TRUE, 1),
('2002', 'Accounts Payable - Other', @current_liabilities, 'Other payables', TRUE, 1),
('2101', 'Salaries Payable', @current_liabilities, 'Accrued salaries', TRUE, 1),
('2102', 'Wages Payable', @current_liabilities, 'Accrued wages', TRUE, 1),
('2201', 'Taxes Payable', @current_liabilities, 'Income tax payable', TRUE, 1),
('2202', 'VAT Payable', @current_liabilities, 'Value Added Tax payable', TRUE, 1),
('2203', 'Withholding Tax Payable', @current_liabilities, 'Tax withheld from employees', TRUE, 1),
('2301', 'SSS Payable', @current_liabilities, 'SSS contributions payable', TRUE, 1),
('2302', 'PhilHealth Payable', @current_liabilities, 'PhilHealth contributions payable', TRUE, 1),
('2303', 'Pag-IBIG Payable', @current_liabilities, 'Pag-IBIG contributions payable', TRUE, 1),
('2401', 'Loans Payable - Current', @current_liabilities, 'Short-term loans', TRUE, 1),
('2501', 'Accrued Expenses', @accrued_liabilities, 'Accrued expenses', TRUE, 1),
('2502', 'Accrued Interest', @accrued_liabilities, 'Accrued interest payable', TRUE, 1),
('2601', 'Deferred Revenue', @deferred_revenue, 'Revenue received in advance', TRUE, 1),

-- NON-CURRENT LIABILITIES
('3001', 'Loans Payable - Long Term', @noncurrent_liabilities, 'Long-term bank loans', TRUE, 1),
('3002', 'Bonds Payable', @noncurrent_liabilities, 'Corporate bonds', TRUE, 1),
('3003', 'Mortgage Payable', @noncurrent_liabilities, 'Mortgage loans', TRUE, 1),

-- EQUITY
('4001', 'Capital Stock', @capital_stock, 'Share capital', TRUE, 1),
('4002', 'Additional Paid-in Capital', @equity_type, 'Additional paid-in capital', TRUE, 1),
('4101', 'Retained Earnings', @retained_earnings, 'Accumulated profits', TRUE, 1),
('4102', 'Current Year Profit/Loss', @retained_earnings, 'Current period earnings', TRUE, 1),
('4201', 'Treasury Stock', @equity_type, 'Treasury stock', TRUE, 1),

-- REVENUE
('5001', 'Sales Revenue', @operating_revenue, 'Product sales', TRUE, 1),
('5002', 'Service Revenue', @operating_revenue, 'Service income', TRUE, 1),
('5003', 'Consulting Revenue', @operating_revenue, 'Consulting services', TRUE, 1),
('5004', 'Rental Revenue', @operating_revenue, 'Rental income', TRUE, 1),
('5101', 'Interest Income', @interest_income, 'Bank interest', TRUE, 1),
('5102', 'Dividend Income', @other_revenue, 'Dividend income', TRUE, 1),
('5103', 'Other Income', @other_revenue, 'Miscellaneous income', TRUE, 1),
('5104', 'Gain on Sale of Assets', @other_revenue, 'Gains from asset sales', TRUE, 1),

-- OPERATING EXPENSES
('6001', 'Cost of Goods Sold', @cogs, 'Direct product costs', TRUE, 1),
('6002', 'Cost of Services', @cogs, 'Direct service costs', TRUE, 1),
('6101', 'Salaries and Wages', @operating_expenses, 'Employee compensation', TRUE, 1),
('6102', 'Employee Benefits', @operating_expenses, 'Health insurance, bonuses', TRUE, 1),
('6103', 'Payroll Taxes', @operating_expenses, 'SSS, PhilHealth, Pag-IBIG employer share', TRUE, 1),
('6201', 'Rent Expense', @operating_expenses, 'Office rent', TRUE, 1),
('6202', 'Utilities Expense', @operating_expenses, 'Electricity, water, internet', TRUE, 1),
('6203', 'Office Supplies Expense', @operating_expenses, 'Supplies and materials', TRUE, 1),
('6204', 'Professional Fees', @operating_expenses, 'Legal, accounting, consulting fees', TRUE, 1),
('6205', 'Marketing and Advertising', @operating_expenses, 'Promotional expenses', TRUE, 1),
('6206', 'Transportation and Travel', @operating_expenses, 'Travel costs', TRUE, 1),
('6207', 'Insurance Expense', @operating_expenses, 'Insurance premiums', TRUE, 1),
('6208', 'Depreciation Expense', @operating_expenses, 'Asset depreciation', TRUE, 1),
('6209', 'Repairs and Maintenance', @operating_expenses, 'Equipment maintenance', TRUE, 1),
('6210', 'Communication Expense', @operating_expenses, 'Phone, internet, postage', TRUE, 1),

-- ADMINISTRATIVE EXPENSES
('7001', 'General and Administrative', @admin_expenses, 'General administrative costs', TRUE, 1),
('7002', 'Management Salaries', @admin_expenses, 'Management compensation', TRUE, 1),
('7003', 'Office Equipment Expense', @admin_expenses, 'Office equipment costs', TRUE, 1),
('7004', 'Training and Development', @admin_expenses, 'Employee training', TRUE, 1),
('7005', 'Research and Development', @admin_expenses, 'R&D expenses', TRUE, 1),

-- OTHER EXPENSES
('8001', 'Interest Expense', @interest_expense, 'Loan interest', TRUE, 1),
('8002', 'Bank Charges', @other_expenses, 'Bank fees and charges', TRUE, 1),
('8003', 'Bad Debt Expense', @other_expenses, 'Uncollectible accounts', TRUE, 1),
('8004', 'Loss on Sale of Assets', @other_expenses, 'Losses from asset sales', TRUE, 1),
('8005', 'Miscellaneous Expense', @other_expenses, 'Other expenses', TRUE, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- 3. JOURNAL TYPES & FISCAL PERIODS
-- ========================================

-- Insert comprehensive journal types
INSERT INTO journal_types (code, name, auto_reversing, description) VALUES
('GJ', 'General Journal', FALSE, 'General journal entries'),
('CR', 'Cash Receipt', FALSE, 'Cash receipts and collections'),
('CD', 'Cash Disbursement', FALSE, 'Cash payments and disbursements'),
('PR', 'Payroll', FALSE, 'Payroll journal entries'),
('AP', 'Accounts Payable', FALSE, 'Supplier invoices and payments'),
('AR', 'Accounts Receivable', FALSE, 'Customer invoices and collections'),
('AJ', 'Adjusting Journal', TRUE, 'Period-end adjusting entries'),
('REV', 'Reversing Entry', TRUE, 'Reversing entries for accruals'),
('CLOSE', 'Closing Entry', FALSE, 'Year-end closing entries'),
('OPEN', 'Opening Entry', FALSE, 'Year-beginning opening entries'),
('SAL', 'Sales Journal', FALSE, 'Sales transactions'),
('PUR', 'Purchase Journal', FALSE, 'Purchase transactions'),
('BANK', 'Bank Reconciliation', FALSE, 'Bank reconciliation entries')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert fiscal periods for multiple years
INSERT INTO fiscal_periods (period_name, start_date, end_date, status) VALUES
-- 2024 Quarters
('FY2024-Q1', '2024-01-01', '2024-03-31', 'closed'),
('FY2024-Q2', '2024-04-01', '2024-06-30', 'closed'),
('FY2024-Q3', '2024-07-01', '2024-09-30', 'closed'),
('FY2024-Q4', '2024-10-01', '2024-12-31', 'closed'),

-- 2025 Quarters
('FY2025-Q1', '2025-01-01', '2025-03-31', 'open'),
('FY2025-Q2', '2025-04-01', '2025-06-30', 'open'),
('FY2025-Q3', '2025-07-01', '2025-09-30', 'open'),
('FY2025-Q4', '2025-10-01', '2025-12-31', 'open'),

-- Monthly periods for 2025
('January 2025', '2025-01-01', '2025-01-31', 'open'),
('February 2025', '2025-02-01', '2025-02-28', 'open'),
('March 2025', '2025-03-01', '2025-03-31', 'open'),
('April 2025', '2025-04-01', '2025-04-30', 'open'),
('May 2025', '2025-05-01', '2025-05-31', 'open'),
('June 2025', '2025-06-01', '2025-06-30', 'open'),
('July 2025', '2025-07-01', '2025-07-31', 'open'),
('August 2025', '2025-08-01', '2025-08-31', 'open'),
('September 2025', '2025-09-01', '2025-09-30', 'open'),
('October 2025', '2025-10-01', '2025-10-31', 'open'),
('November 2025', '2025-11-01', '2025-11-30', 'open'),
('December 2025', '2025-12-01', '2025-12-31', 'open')
ON DUPLICATE KEY UPDATE period_name = VALUES(period_name);

-- ========================================
-- 4. HRIS MODULE DATA (Department & Position)
-- ========================================

-- Insert departments
INSERT INTO department (department_id, department_name, description) VALUES
(1, 'IT', 'Information Technology Department'),
(2, 'Human Resources', 'Human Resources Department'),
(3, 'Finance', 'Finance and Accounting Department'),
(4, 'Marketing', 'Marketing and Sales Department'),
(5, 'Operations', 'Operations and Logistics Department'),
(6, 'Customer Service', 'Customer Service Department'),
(7, 'Sales', 'Sales Department')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

-- Insert positions (using backticks because 'position' is a reserved word)
INSERT INTO `position` (position_id, position_title, job_description, salary_grade) VALUES
(1, 'CTO', 'Chief Technology Officer', 15),
(2, 'CFO', 'Chief Financial Officer', 15),
(3, 'COO', 'Chief Operating Officer', 15),
(4, 'Marketing Director', 'Director of Marketing', 14),
(5, 'HR Manager', 'Human Resources Manager', 12),
(6, 'CS Manager', 'Customer Service Manager', 12),
(7, 'Sales Manager', 'Sales Manager', 12),
(8, 'Senior Accountant', 'Senior Accountant', 10),
(9, 'Senior Developer', 'Senior Software Developer', 11),
(10, 'Marketing Specialist', 'Marketing Specialist', 9),
(11, 'Software Developer', 'Software Developer', 9),
(12, 'Accountant', 'Accountant', 8),
(13, 'Sales Executive', 'Sales Executive', 8),
(14, 'CS Representative', 'Customer Service Representative', 7),
(15, 'Operations Coordinator', 'Operations Coordinator', 8),
(16, 'Content Creator', 'Content Creator', 7),
(17, 'Junior Developer', 'Junior Software Developer', 7),
(18, 'Payroll Specialist', 'Payroll Specialist', 8),
(19, 'Sales Representative', 'Sales Representative', 7),
(20, 'Warehouse Supervisor', 'Warehouse Supervisor', 9),
(21, 'Accounts Payable Clerk', 'Accounts Payable Clerk', 7),
(22, 'System Administrator', 'System Administrator', 9),
(23, 'Social Media Manager', 'Social Media Manager', 8),
(24, 'Account Manager', 'Account Manager', 9)
ON DUPLICATE KEY UPDATE position_title = VALUES(position_title);

-- ========================================
-- 4A. EMPLOYEE TABLE DATA (HRIS Core)
-- ========================================
-- Insert employee records linking to departments and positions
-- This connects HRIS module to the payroll system

INSERT INTO employee (employee_id, first_name, last_name, middle_name, gender, birth_date, contact_number, email, address, hire_date, department_id, position_id, employment_status) VALUES
-- Management (C-Suite & Directors)
(1, 'Juan', 'Santos', 'Carlos', 'Male', '1980-05-15', '09171234567', 'juan.santos@company.com', 'Makati City, Metro Manila', '2020-01-15', 2, 5, 'Active'),
(2, 'Maria Elena', 'Rodriguez', NULL, 'Female', '1978-03-20', '09171234568', 'maria.rodriguez@company.com', 'BGC, Taguig City', '2019-06-01', 3, 2, 'Active'),
(3, 'Jose Miguel', 'Cruz', NULL, 'Male', '1982-08-10', '09171234569', 'jose.cruz@company.com', 'Ortigas, Pasig City', '2021-02-01', 1, 1, 'Active'),
(4, 'Ana Patricia', 'Lopez', NULL, 'Female', '1985-11-25', '09171234570', 'ana.lopez@company.com', 'Mandaluyong City', '2020-03-15', 4, 4, 'Active'),
(5, 'Roberto Antonio', 'Garcia', NULL, 'Male', '1981-07-30', '09171234571', 'roberto.garcia@company.com', 'Quezon City', '2019-09-01', 5, 3, 'Active'),

-- Senior Staff (Managers & Senior Specialists)
(6, 'Carmen Sofia', 'Martinez', NULL, 'Female', '1987-04-12', '09171234572', 'carmen.martinez@company.com', 'San Juan City', '2021-05-01', 6, 6, 'Active'),
(7, 'Fernando Luis', 'Torres', NULL, 'Male', '1986-09-18', '09171234573', 'fernando.torres@company.com', 'Pasay City', '2020-07-01', 7, 7, 'Active'),
(8, 'Isabella Rose', 'Flores', NULL, 'Female', '1989-12-05', '09171234574', 'isabella.flores@company.com', 'Makati City', '2021-01-15', 3, 8, 'Active'),
(9, 'Miguel Angel', 'Reyes', NULL, 'Male', '1988-06-22', '09171234575', 'miguel.reyes@company.com', 'Taguig City', '2020-08-01', 1, 9, 'Active'),
(10, 'Sofia Grace', 'Villanueva', NULL, 'Female', '1990-02-14', '09171234576', 'sofia.villanueva@company.com', 'Mandaluyong City', '2021-03-01', 4, 10, 'Active'),

-- Mid-level Staff
(11, 'Carlos Eduardo', 'Mendoza', NULL, 'Male', '1992-10-08', '09171234577', 'carlos.mendoza@company.com', 'Pasig City', '2022-01-15', 1, 11, 'Active'),
(12, 'Patricia Isabel', 'Gutierrez', NULL, 'Female', '1991-03-17', '09171234578', 'patricia.gutierrez@company.com', 'Quezon City', '2022-02-01', 3, 12, 'Active'),
(13, 'Ricardo Manuel', 'Herrera', NULL, 'Male', '1990-07-23', '09171234579', 'ricardo.herrera@company.com', 'Manila City', '2021-06-01', 7, 13, 'Active'),
(14, 'Gabriela Alejandra', 'Morales', NULL, 'Female', '1993-05-11', '09171234580', 'gabriela.morales@company.com', 'Makati City', '2022-03-01', 6, 14, 'Active'),
(15, 'Diego Fernando', 'Ramos', NULL, 'Male', '1992-11-29', '09171234581', 'diego.ramos@company.com', 'Taguig City', '2021-09-01', 5, 15, 'Active'),

-- Junior Staff & Support Roles
(16, 'Valentina Sofia', 'Castillo', NULL, 'Female', '1994-08-06', '09171234582', 'valentina.castillo@company.com', 'Pasig City', '2022-05-01', 4, 16, 'Active'),
(17, 'Sebastian Alejandro', 'Vega', NULL, 'Male', '1993-12-19', '09171234583', 'sebastian.vega@company.com', 'Quezon City', '2022-04-15', 1, 17, 'Active'),
(18, 'Camila Esperanza', 'Ruiz', NULL, 'Female', '1992-01-31', '09171234584', 'camila.ruiz@company.com', 'Makati City', '2021-11-01', 3, 18, 'Active'),
(19, 'Nicolas Gabriel', 'Silva', NULL, 'Male', '1994-09-14', '09171234585', 'nicolas.silva@company.com', 'Mandaluyong City', '2022-06-01', 7, 19, 'Active'),
(20, 'Lucia Esperanza', 'Jimenez', NULL, 'Female', '1995-04-27', '09171234586', 'lucia.jimenez@company.com', 'Pasay City', '2022-07-01', 6, 14, 'Active'),

-- Additional Staff
(21, 'Andres Felipe', 'Castro', NULL, 'Male', '1991-10-03', '09171234587', 'andres.castro@company.com', 'Taguig City', '2021-10-01', 5, 20, 'Active'),
(22, 'Mariana Beatriz', 'Ortega', NULL, 'Female', '1993-06-16', '09171234588', 'mariana.ortega@company.com', 'Quezon City', '2022-01-01', 3, 21, 'Active'),
(23, 'Santiago Ignacio', 'Pena', NULL, 'Male', '1990-02-28', '09171234589', 'santiago.pena@company.com', 'Makati City', '2021-07-15', 1, 22, 'Active'),
(24, 'Daniela Fernanda', 'Vargas', NULL, 'Female', '1994-11-09', '09171234590', 'daniela.vargas@company.com', 'Pasig City', '2022-08-01', 4, 23, 'Active'),
(25, 'Alejandro Jose', 'Medina', NULL, 'Male', '1992-05-22', '09171234591', 'alejandro.medina@company.com', 'Mandaluyong City', '2021-12-01', 7, 24, 'Active')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name);

-- ========================================
-- 4B. EMPLOYEE REFERENCE DATA (External HRIS Integration)
-- ========================================
-- This table is used by payroll system via employee_external_no
-- Links to employee table through mapping

INSERT INTO employee_refs (external_employee_no, name, department, position, base_monthly_salary, employment_type, external_source) VALUES
-- Management (C-Suite & Directors) - Philippine Market Rates
('EMP001', 'Juan Carlos Santos', 'Human Resources', 'HR Manager', 65000.00, 'regular', 'HRIS'),
('EMP002', 'Maria Elena Rodriguez', 'Finance', 'CFO', 200000.00, 'regular', 'HRIS'),
('EMP003', 'Jose Miguel Cruz', 'IT', 'CTO', 220000.00, 'regular', 'HRIS'),
('EMP004', 'Ana Patricia Lopez', 'Marketing', 'Marketing Director', 120000.00, 'regular', 'HRIS'),
('EMP005', 'Roberto Antonio Garcia', 'Operations', 'COO', 200000.00, 'regular', 'HRIS'),

-- Senior Staff (Managers & Senior Specialists)
('EMP006', 'Carmen Sofia Martinez', 'Customer Service', 'CS Manager', 55000.00, 'regular', 'HRIS'),
('EMP007', 'Fernando Luis Torres', 'Sales', 'Sales Manager', 70000.00, 'regular', 'HRIS'),
('EMP008', 'Isabella Rose Flores', 'Finance', 'Senior Accountant', 48000.00, 'regular', 'HRIS'),
('EMP009', 'Miguel Angel Reyes', 'IT', 'Senior Developer', 85000.00, 'regular', 'HRIS'),
('EMP010', 'Sofia Grace Villanueva', 'Marketing', 'Marketing Specialist', 42000.00, 'regular', 'HRIS'),

-- Mid-level Staff
('EMP011', 'Carlos Eduardo Mendoza', 'IT', 'Software Developer', 55000.00, 'regular', 'HRIS'),
('EMP012', 'Patricia Isabel Gutierrez', 'Finance', 'Accountant', 35000.00, 'regular', 'HRIS'),
('EMP013', 'Ricardo Manuel Herrera', 'Sales', 'Sales Executive', 40000.00, 'regular', 'HRIS'),
('EMP014', 'Gabriela Alejandra Morales', 'Customer Service', 'CS Representative', 25000.00, 'regular', 'HRIS'),
('EMP015', 'Diego Fernando Ramos', 'Operations', 'Operations Coordinator', 32000.00, 'regular', 'HRIS'),

-- Junior Staff & Support Roles
('EMP016', 'Valentina Sofia Castillo', 'Marketing', 'Content Creator', 28000.00, 'contract', 'HRIS'),
('EMP017', 'Sebastian Alejandro Vega', 'IT', 'Junior Developer', 38000.00, 'contract', 'HRIS'),
('EMP018', 'Camila Esperanza Ruiz', 'Finance', 'Payroll Specialist', 32000.00, 'regular', 'HRIS'),
('EMP019', 'Nicolas Gabriel Silva', 'Sales', 'Sales Representative', 30000.00, 'contract', 'HRIS'),
('EMP020', 'Lucia Esperanza Jimenez', 'Customer Service', 'CS Representative', 25000.00, 'part-time', 'HRIS'),

-- Additional Staff
('EMP021', 'Andres Felipe Castro', 'Operations', 'Warehouse Supervisor', 45000.00, 'regular', 'HRIS'),
('EMP022', 'Mariana Beatriz Ortega', 'Finance', 'Accounts Payable Clerk', 28000.00, 'regular', 'HRIS'),
('EMP023', 'Santiago Ignacio Pena', 'IT', 'System Administrator', 55000.00, 'regular', 'HRIS'),
('EMP024', 'Daniela Fernanda Vargas', 'Marketing', 'Social Media Manager', 35000.00, 'contract', 'HRIS'),
('EMP025', 'Alejandro Jose Medina', 'Sales', 'Account Manager', 50000.00, 'regular', 'HRIS')
ON DUPLICATE KEY UPDATE name = VALUES(name), base_monthly_salary = VALUES(base_monthly_salary);

-- ========================================
-- 4C. USER ACCOUNT LINKING (HRIS-User System Integration)
-- ========================================
-- Links employees to user accounts for system access
-- This connects HRIS employee records to authentication system

-- Link admin user (already exists in users table) to employee system
-- Note: Additional user_account records can be created as needed
-- The employee_id links to the employee table, enabling HRIS-Payroll integration
INSERT INTO user_account (user_id, employee_id, username, password_hash, role, last_login) VALUES
(1, 1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NOW() - INTERVAL 5 DAY)
ON DUPLICATE KEY UPDATE employee_id = VALUES(employee_id);

-- ========================================
-- HRIS-PAYROLL CONNECTION DOCUMENTATION
-- ========================================
-- 
-- IMPORTANT: Understanding the HRIS-Payroll Data Flow
-- 
-- 1. HRIS CORE TABLES (HRIS Module):
--    - department: Organizational departments
--    - position: Job positions (note: backticked because 'position' is a reserved word)
--    - employee: Core employee records with foreign keys to department and position
--    - employee_attendance: Daily attendance tracking
-- 
-- 2. PAYROLL SYSTEM TABLES (Payroll Module):
--    - employee_refs: External employee references used by payroll system
--      * Uses: external_employee_no (EMP001, EMP002, etc.)
--      * Contains: base_monthly_salary, employment_type
--    - payslips: Payroll payslips linked via employee_external_no
--    - payroll_runs: Payroll processing runs
--    - payroll_periods: Payroll periods
-- 
-- 3. DATA MAPPING:
--    - employee.employee_id (1-25) corresponds to employee_refs.external_employee_no (EMP001-EMP025)
--    - employee_refs.external_employee_no is used throughout payroll system:
--      * payslips.employee_external_no
--      * employee_attendance.employee_external_no
--      * expense_claims.employee_external_no
--      * loans.borrower_external_no
-- 
-- 4. INTEGRATION POINTS:
--    - Payroll calculations use employee_refs.base_monthly_salary
--    - Attendance tracking uses employee_attendance linked via employee_external_no
--    - Department and position info flows from employee -> department/position tables
--    - User accounts link via user_account.employee_id for system access
-- 
-- 5. EXAMPLE FLOW:
--    Employee Record (HRIS) -> employee_refs (Payroll Interface) -> Payroll Processing -> Payslip
--    employee_id: 1 -> external_employee_no: EMP001 -> payroll calculation -> payslip record
-- 
-- ========================================

-- ========================================
-- 4D. EMPLOYEE ATTENDANCE DATA FOR NOVEMBER 2025
-- ========================================
-- Note: November 2025 has 30 days. Workdays exclude weekends (Nov 1,2,8,9,15,16,22,23,29,30 are weekends)
-- Workdays: 3,4,5,6,7,10,11,12,13,14,17,18,19,20,21,24,25,26,27,28 (20 workdays)
-- Expanded attendance records with varied patterns for testing Daily Attendance Records

INSERT INTO employee_attendance (employee_external_no, attendance_date, time_in, time_out, status, hours_worked, overtime_hours, late_minutes, remarks) VALUES
-- EMP001: Random mixed pattern
('EMP001', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-04', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP001', '2025-11-05', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP001', '2025-11-06', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP001', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP001', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-11', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP001', '2025-11-12', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP001', '2025-11-13', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP001', '2025-11-14', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP001', '2025-11-17', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-18', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP001', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-20', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP001', '2025-11-21', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP001', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-25', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP001', '2025-11-26', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP001', '2025-11-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP001', '2025-11-28', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),

-- EMP002: Random mixed pattern
('EMP002', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP002', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-05', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP002', '2025-11-06', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP002', '2025-11-07', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP002', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-11', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP002', '2025-11-12', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP002', '2025-11-13', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP002', '2025-11-17', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP002', '2025-11-18', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP002', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-20', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP002', '2025-11-21', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP002', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP002', '2025-11-26', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP002', '2025-11-27', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP002', '2025-11-28', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),

-- EMP003: Random mixed pattern
('EMP003', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-04', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP003', '2025-11-05', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP003', '2025-11-06', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP003', '2025-11-10', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP003', '2025-11-11', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP003', '2025-11-12', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-13', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP003', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP003', '2025-11-17', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-18', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP003', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP003', '2025-11-20', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP003', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP003', '2025-11-25', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP003', '2025-11-26', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP003', '2025-11-27', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP003', '2025-11-28', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),

-- EMP004: Random mixed pattern
('EMP004', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP004', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-05', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-06', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP004', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP004', '2025-11-10', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP004', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-12', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP004', '2025-11-13', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP004', '2025-11-14', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-17', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP004', '2025-11-18', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP004', '2025-11-19', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP004', '2025-11-20', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-21', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP004', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP004', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP004', '2025-11-26', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP004', '2025-11-27', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP004', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP005: Random mixed pattern
('EMP005', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-04', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP005', '2025-11-05', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP005', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP005', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-10', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP005', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-12', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP005', '2025-11-13', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-14', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP005', '2025-11-17', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP005', '2025-11-18', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP005', '2025-11-19', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP005', '2025-11-20', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP005', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-25', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP005', '2025-11-26', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP005', '2025-11-27', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP005', '2025-11-28', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),

-- EMP006: Random mixed pattern
('EMP006', '2025-11-03', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP006', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-05', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP006', '2025-11-06', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP006', '2025-11-10', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP006', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-12', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP006', '2025-11-13', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP006', '2025-11-14', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP006', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-19', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP006', '2025-11-20', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP006', '2025-11-21', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP006', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-25', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP006', '2025-11-26', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP006', '2025-11-27', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP006', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP007: Random mixed pattern
('EMP007', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-04', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP007', '2025-11-05', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-06', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP007', '2025-11-07', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP007', '2025-11-10', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP007', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-12', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-13', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP007', '2025-11-14', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP007', '2025-11-17', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP007', '2025-11-18', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP007', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-20', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP007', '2025-11-21', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP007', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-26', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP007', '2025-11-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP007', '2025-11-28', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),

-- EMP008: Random mixed pattern
('EMP008', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-04', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP008', '2025-11-05', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP008', '2025-11-06', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP008', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-10', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP008', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-12', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP008', '2025-11-13', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP008', '2025-11-14', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP008', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP008', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-19', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP008', '2025-11-20', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP008', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-24', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP008', '2025-11-25', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP008', '2025-11-26', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP008', '2025-11-27', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP008', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP009: Random mixed pattern
('EMP009', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP009', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-05', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP009', '2025-11-06', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP009', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP009', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-11', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP009', '2025-11-12', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-13', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP009', '2025-11-14', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP009', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP009', '2025-11-18', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP009', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-20', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP009', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP009', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP009', '2025-11-26', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP009', '2025-11-27', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP009', '2025-11-28', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),

-- EMP010: Random mixed pattern
('EMP010', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-04', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP010', '2025-11-05', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP010', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP010', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-10', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP010', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-12', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP010', '2025-11-13', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP010', '2025-11-14', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP010', '2025-11-17', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-18', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP010', '2025-11-19', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP010', '2025-11-20', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP010', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP010', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP010', '2025-11-26', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP010', '2025-11-27', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP010', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP011: Random mixed pattern
('EMP011', '2025-11-03', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP011', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-05', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-06', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP011', '2025-11-07', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP011', '2025-11-10', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP011', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-12', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP011', '2025-11-13', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP011', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP011', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP011', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP011', '2025-11-20', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP011', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP011', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP011', '2025-11-26', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP011', '2025-11-27', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP011', '2025-11-28', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),

-- EMP012: Random mixed pattern
('EMP012', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-04', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP012', '2025-11-05', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP012', '2025-11-06', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP012', '2025-11-10', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP012', '2025-11-11', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP012', '2025-11-12', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-13', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP012', '2025-11-14', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP012', '2025-11-18', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP012', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP012', '2025-11-20', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP012', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP012', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP012', '2025-11-26', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP012', '2025-11-27', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP012', '2025-11-28', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),

-- EMP013: Random mixed pattern
('EMP013', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP013', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP013', '2025-11-05', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP013', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP013', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP013', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP013', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP013', '2025-11-12', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP013', '2025-11-13', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP013', '2025-11-14', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP013', '2025-11-17', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP013', '2025-11-18', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP013', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP013', '2025-11-20', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP013', '2025-11-21', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP013', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP013', '2025-11-25', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP013', '2025-11-26', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP013', '2025-11-27', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP013', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP014: Random mixed pattern
('EMP014', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP014', '2025-11-04', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP014', '2025-11-05', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP014', '2025-11-06', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP014', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP014', '2025-11-10', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP014', '2025-11-11', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP014', '2025-11-12', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP014', '2025-11-13', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP014', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP014', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP014', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP014', '2025-11-19', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP014', '2025-11-20', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP014', '2025-11-21', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP014', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP014', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP014', '2025-11-26', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP014', '2025-11-27', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP014', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP015: Random mixed pattern
('EMP015', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP015', '2025-11-04', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP015', '2025-11-05', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP015', '2025-11-06', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP015', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP015', '2025-11-10', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP015', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP015', '2025-11-12', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP015', '2025-11-13', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP015', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP015', '2025-11-17', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP015', '2025-11-18', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP015', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP015', '2025-11-20', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP015', '2025-11-21', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP015', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP015', '2025-11-25', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP015', '2025-11-26', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP015', '2025-11-27', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP015', '2025-11-28', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),

-- EMP016: Random mixed pattern
('EMP016', '2025-11-03', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP016', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP016', '2025-11-05', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP016', '2025-11-06', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP016', '2025-11-07', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP016', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP016', '2025-11-11', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP016', '2025-11-12', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP016', '2025-11-13', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP016', '2025-11-14', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP016', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP016', '2025-11-18', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP016', '2025-11-19', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP016', '2025-11-20', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP016', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP016', '2025-11-24', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP016', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP016', '2025-11-26', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP016', '2025-11-27', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP016', '2025-11-28', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),

-- EMP017: Random mixed pattern
('EMP017', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP017', '2025-11-04', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP017', '2025-11-05', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP017', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP017', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP017', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP017', '2025-11-11', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP017', '2025-11-12', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP017', '2025-11-13', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP017', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP017', '2025-11-17', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP017', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP017', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP017', '2025-11-20', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP017', '2025-11-21', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP017', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP017', '2025-11-25', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP017', '2025-11-26', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP017', '2025-11-27', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP017', '2025-11-28', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),

-- EMP018: Random mixed pattern
('EMP018', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP018', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP018', '2025-11-05', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP018', '2025-11-06', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP018', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP018', '2025-11-10', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP018', '2025-11-11', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP018', '2025-11-12', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP018', '2025-11-13', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP018', '2025-11-14', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP018', '2025-11-17', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP018', '2025-11-18', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP018', '2025-11-19', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP018', '2025-11-20', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP018', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP018', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP018', '2025-11-25', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP018', '2025-11-26', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP018', '2025-11-27', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP018', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP019: Random mixed pattern
('EMP019', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP019', '2025-11-04', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP019', '2025-11-05', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP019', '2025-11-06', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP019', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP019', '2025-11-10', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP019', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP019', '2025-11-12', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP019', '2025-11-13', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP019', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP019', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP019', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP019', '2025-11-19', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP019', '2025-11-20', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP019', '2025-11-21', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP019', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP019', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP019', '2025-11-26', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP019', '2025-11-27', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP019', '2025-11-28', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),

-- EMP020: Random mixed pattern
('EMP020', '2025-11-03', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP020', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP020', '2025-11-05', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP020', '2025-11-06', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP020', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP020', '2025-11-10', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP020', '2025-11-11', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP020', '2025-11-12', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP020', '2025-11-13', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP020', '2025-11-14', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP020', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP020', '2025-11-18', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP020', '2025-11-19', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP020', '2025-11-20', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP020', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP020', '2025-11-24', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP020', '2025-11-25', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP020', '2025-11-26', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP020', '2025-11-27', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP020', '2025-11-28', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),

-- EMP021: Random mixed pattern
('EMP021', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP021', '2025-11-04', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP021', '2025-11-05', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP021', '2025-11-06', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP021', '2025-11-07', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP021', '2025-11-10', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP021', '2025-11-11', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP021', '2025-11-12', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP021', '2025-11-13', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP021', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP021', '2025-11-17', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP021', '2025-11-18', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP021', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP021', '2025-11-20', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP021', '2025-11-21', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP021', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP021', '2025-11-25', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP021', '2025-11-26', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP021', '2025-11-27', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP021', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP022: Random mixed pattern
('EMP022', '2025-11-03', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP022', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP022', '2025-11-05', '08:30:00', '17:00:00', 'late', 8.00, 0.00, 30, 'Late arrival'),
('EMP022', '2025-11-06', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP022', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP022', '2025-11-10', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP022', '2025-11-11', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP022', '2025-11-12', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP022', '2025-11-13', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP022', '2025-11-14', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP022', '2025-11-17', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP022', '2025-11-18', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP022', '2025-11-19', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP022', '2025-11-20', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP022', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP022', '2025-11-24', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP022', '2025-11-25', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP022', '2025-11-26', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP022', '2025-11-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP022', '2025-11-28', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),

-- EMP023: Random mixed pattern
('EMP023', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP023', '2025-11-04', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP023', '2025-11-05', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP023', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP023', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP023', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP023', '2025-11-11', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP023', '2025-11-12', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP023', '2025-11-13', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP023', '2025-11-14', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP023', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP023', '2025-11-18', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP023', '2025-11-19', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP023', '2025-11-20', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP023', '2025-11-21', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP023', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP023', '2025-11-25', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP023', '2025-11-26', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP023', '2025-11-27', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP023', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP024: Random mixed pattern
('EMP024', '2025-11-03', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP024', '2025-11-04', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP024', '2025-11-05', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP024', '2025-11-06', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP024', '2025-11-07', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP024', '2025-11-10', '08:00:00', '19:00:00', 'present', 10.00, 2.00, 0, 'Overtime work'),
('EMP024', '2025-11-11', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP024', '2025-11-12', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arrival'),
('EMP024', '2025-11-13', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP024', '2025-11-14', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP024', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP024', '2025-11-18', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP024', '2025-11-19', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP024', '2025-11-20', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP024', '2025-11-21', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP024', '2025-11-24', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP024', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP024', '2025-11-26', '08:25:00', '17:00:00', 'late', 8.00, 0.00, 25, 'Late arrival'),
('EMP024', '2025-11-27', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP024', '2025-11-28', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),

-- EMP025: Random mixed pattern
('EMP025', '2025-11-03', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP025', '2025-11-04', '08:10:00', '17:00:00', 'late', 8.00, 0.00, 10, 'Late arrival'),
('EMP025', '2025-11-05', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP025', '2025-11-06', '08:00:00', '18:00:00', 'present', 9.00, 1.00, 0, 'Overtime work'),
('EMP025', '2025-11-07', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Vacation leave'),
('EMP025', '2025-11-10', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP025', '2025-11-11', '08:20:00', '17:00:00', 'late', 8.00, 0.00, 20, 'Late arriv'),
('EMP025', '2025-11-12', '08:00:00', '12:00:00', 'half_day', 4.00, 0.00, 0, 'Half day'),
('EMP025', '2025-11-13', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP025', '2025-11-14', '08:00:00', '19:30:00', 'present', 10.50, 2.50, 0, 'Overtime work'),
('EMP025', '2025-11-17', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP025', '2025-11-18', '08:15:00', '17:00:00', 'late', 8.00, 0.00, 15, 'Late arrival'),
('EMP025', '2025-11-19', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP025', '2025-11-20', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent'),
('EMP025', '2025-11-21', '08:00:00', '20:00:00', 'present', 11.00, 3.00, 0, 'Overtime work'),
('EMP025', '2025-11-24', '08:00:00', '17:00:00', 'present', 8.00, 0.00, 0, 'Regular work day'),
('EMP025', '2025-11-25', NULL, NULL, 'leave', 0.00, 0.00, 0, 'Leave'),
('EMP025', '2025-11-26', '08:05:00', '17:00:00', 'late', 8.00, 0.00, 5, 'Late arrival'),
('EMP025', '2025-11-27', '08:00:00', '18:30:00', 'present', 9.50, 1.50, 0, 'Overtime work'),
('EMP025', '2025-11-28', NULL, NULL, 'absent', 0.00, 0.00, 0, 'Absent')
ON DUPLICATE KEY UPDATE hours_worked = VALUES(hours_worked), status = VALUES(status), overtime_hours = VALUES(overtime_hours), late_minutes = VALUES(late_minutes);
-- ========================================
-- 4E. HRIS ADDITIONAL TABLES (Leave, Contracts, Onboarding, Recruitment)
-- ========================================

-- Leave Types
INSERT INTO leave_type (leave_type_id, leave_name, purpose, duration, paid_unpaid) VALUES
(1, 'Vacation Leave', 'Annual vacation time', 'Per year', 'paid'),
(2, 'Sick Leave', 'Medical leave for illness', 'Per year', 'paid'),
(3, 'Maternity Leave', 'Maternity and childcare', 'Per occurrence', 'paid'),
(4, 'Paternity Leave', 'Paternity and childcare', 'Per occurrence', 'paid'),
(5, 'Emergency Leave', 'Family emergencies', 'Per year', 'paid'),
(6, 'Bereavement Leave', 'Death in family', 'Per occurrence', 'paid'),
(7, 'Service Incentive Leave', 'Service recognition', 'Per year', 'paid'),
(8, 'Sabbatical Leave', 'Extended leave for personal development', 'As needed', 'unpaid'),
(9, 'Study Leave', 'Educational purposes', 'As needed', 'unpaid'),
(10, 'Emergency Leave', 'Unforeseen circumstances', 'As needed', 'unpaid')
ON DUPLICATE KEY UPDATE leave_name = VALUES(leave_name);

-- Leave Requests
INSERT INTO leave_request (leave_request_id, employee_id, leave_type_id, start_date, end_date, total_days, reason, status, approver_id, date_requested, date_approved) VALUES
(1, 1, 1, '2025-12-20', '2025-12-22', 3, 'Year-end vacation with family', 'approved', 5, '2025-12-01', '2025-12-02'),
(2, 3, 2, '2025-11-15', '2025-11-16', 2, 'Medical treatment', 'approved', 5, '2025-11-14', '2025-11-14'),
(3, 7, 1, '2025-12-28', '2025-12-31', 4, 'Holiday vacation', 'pending', NULL, '2025-12-10', NULL),
(4, 12, 6, '2025-11-10', '2025-11-11', 2, 'Family bereavement', 'approved', 2, '2025-11-09', '2025-11-09'),
(5, 8, 3, '2025-01-15', '2025-03-15', 60, 'Maternity leave', 'approved', 2, '2024-12-01', '2024-12-02'),
(6, 15, 5, '2025-12-05', '2025-12-05', 1, 'Family emergency', 'approved', 5, '2025-12-04', '2025-12-04'),
(7, 20, 1, '2025-12-25', '2025-12-27', 3, 'Holiday vacation', 'pending', NULL, '2025-12-15', NULL)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Contracts
INSERT INTO contract (contract_id, employee_id, contract_type, start_date, end_date, salary, benefits) VALUES
(1, 1, 'Regular Employment', '2020-01-15', NULL, 65000.00, 'Health insurance, 13th month pay, retirement plan'),
(2, 2, 'Regular Employment', '2019-06-01', NULL, 200000.00, 'Executive benefits package, health insurance, car allowance'),
(3, 16, 'Contractual', '2022-05-01', '2025-04-30', 28000.00, 'Health insurance'),
(4, 17, 'Contractual', '2022-04-15', '2025-04-14', 38000.00, 'Health insurance'),
(5, 19, 'Contractual', '2022-06-01', '2025-05-31', 30000.00, 'Health insurance'),
(6, 20, 'Part-time', '2022-07-01', NULL, 25000.00, 'Pro-rated benefits'),
(7, 24, 'Contractual', '2022-08-01', '2025-07-31', 35000.00, 'Health insurance'),
(8, 3, 'Regular Employment', '2021-02-01', NULL, 220000.00, 'Executive benefits package, health insurance, technology allowance')
ON DUPLICATE KEY UPDATE salary = VALUES(salary);

-- Onboarding Records
INSERT INTO onboarding (onboarding_id, employee_id, start_date, department_id, completion_status) VALUES
(1, 11, '2022-01-15', 1, 'completed'),
(2, 12, '2022-02-01', 3, 'completed'),
(3, 16, '2022-05-01', 4, 'completed'),
(4, 24, '2022-08-01', 4, 'completed'),
(5, 25, '2021-12-01', 7, 'completed')
ON DUPLICATE KEY UPDATE completion_status = VALUES(completion_status);

-- Recruitment (Job Postings)
INSERT INTO recruitment (recruitment_id, job_title, department_id, date_posted, status, posted_by) VALUES
(1, 'Senior Software Developer', 1, '2025-11-01', 'open', 1),
(2, 'Marketing Manager', 4, '2025-11-15', 'open', 1),
(3, 'Finance Analyst', 3, '2025-12-01', 'closed', 1),
(4, 'Customer Service Representative', 6, '2025-12-10', 'open', 1),
(5, 'Operations Coordinator', 5, '2025-11-20', 'closed', 1)
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Applicants
INSERT INTO applicant (applicant_id, recruitment_id, full_name, email, contact_number, resume_file, application_status, archived_at) VALUES
(1, 1, 'John Michael Dela Cruz', 'john.delacruz@email.com', '09171234999', 'resume_john_delacruz.pdf', 'interview', NULL),
(2, 1, 'Mary Grace Santos', 'mary.santos@email.com', '09171234998', 'resume_mary_santos.pdf', 'shortlisted', NULL),
(3, 2, 'Peter James Garcia', 'peter.garcia@email.com', '09171234997', 'resume_peter_garcia.pdf', 'rejected', '2025-11-20'),
(4, 3, 'Lisa Ann Reyes', 'lisa.reyes@email.com', '09171234996', 'resume_lisa_reyes.pdf', 'hired', NULL),
(5, 4, 'Robert John Cruz', 'robert.cruz@email.com', '09171234995', 'resume_robert_cruz.pdf', 'pending', NULL)
ON DUPLICATE KEY UPDATE application_status = VALUES(application_status);

-- Interviews
INSERT INTO interview (interview_id, applicant_id, interviewer_id, interview_date, interview_result, remarks) VALUES
(1, 1, 9, '2025-11-20', 'passed', 'Strong technical skills, good communication'),
(2, 1, 3, '2025-11-25', 'passed', 'Technical assessment completed successfully'),
(3, 2, 9, '2025-11-18', 'pending', 'Scheduled for second round'),
(4, 4, 2, '2025-12-05', 'passed', 'Excellent analytical skills, ready for hiring'),
(5, 5, 6, '2025-12-15', 'pending', 'Initial screening completed')
ON DUPLICATE KEY UPDATE interview_result = VALUES(interview_result);

-- Attendance (Alternative attendance table using employee_id)
INSERT INTO attendance (attendance_id, employee_id, date, time_in, time_out, total_hours, status, remarks) VALUES
(1, 1, '2025-11-03', '2025-11-03 08:00:00', '2025-11-03 17:00:00', 8.00, 'Present', 'Regular work day'),
(2, 2, '2025-11-03', '2025-11-03 08:15:00', '2025-11-03 17:30:00', 8.25, 'Present', 'Late arrival'),
(3, 3, '2025-11-03', '2025-11-03 08:00:00', '2025-11-03 18:00:00', 9.00, 'Present', 'Overtime work'),
(4, 1, '2025-11-04', '2025-11-04 08:20:00', '2025-11-04 17:00:00', 7.67, 'Late', 'Late arrival - 20 minutes'),
(5, 5, '2025-11-05', NULL, NULL, 0.00, 'Absent', 'Absent without leave')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- System Logs
INSERT INTO system_logs (log_level, log_type, user_id, employee_id, ip_address, user_agent, action, details, request_data, created_at) VALUES
('INFO', 'authentication', 1, 1, '192.168.1.100', 'Mozilla/5.0', 'User Login', 'Admin user logged in successfully', '{"username":"admin"}', NOW() - INTERVAL 2 DAY),
('INFO', 'payroll', 1, 1, '192.168.1.100', 'Mozilla/5.0', 'Payroll Run', 'Payroll processed for November 2025', '{"period":"2025-11","employees":25}', NOW() - INTERVAL 5 DAY),
('WARNING', 'attendance', NULL, 5, '192.168.1.105', 'Mobile App', 'Late Arrival', 'Employee arrived 30 minutes late', '{"employee_id":5,"late_minutes":30}', NOW() - INTERVAL 3 DAY),
('ERROR', 'integration', 1, NULL, '192.168.1.100', 'API Client', 'HRIS Sync Failed', 'Failed to sync employee data from external HRIS', '{"error":"Connection timeout"}', NOW() - INTERVAL 1 DAY),
('INFO', 'loan', 1, 1, '192.168.1.100', 'Mozilla/5.0', 'Loan Created', 'New loan created for employee', '{"loan_no":"LN-1026","employee":"EMP001"}', NOW() - INTERVAL 7 DAY)
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

-- Login Attempts
INSERT INTO login_attempts (attempt_id, username, ip_address, attempt_time, success, failure_reason) VALUES
(1, 'admin', '192.168.1.100', NOW() - INTERVAL 2 DAY, TRUE, NULL),
(2, 'admin', '192.168.1.105', NOW() - INTERVAL 3 DAY, FALSE, 'Invalid password'),
(3, 'admin', '192.168.1.100', NOW() - INTERVAL 1 DAY, TRUE, NULL),
(4, 'juan.santos', '192.168.1.110', NOW() - INTERVAL 5 DAY, FALSE, 'User not found'),
(5, 'admin', '10.0.0.50', NOW() - INTERVAL 6 DAY, FALSE, 'Account locked - too many attempts')
ON DUPLICATE KEY UPDATE attempt_time = VALUES(attempt_time);

-- ========================================
-- 4F. BANKING MODULE SUPPORTING TABLES
-- ========================================

-- Genders (for customer profiles)
INSERT INTO genders (gender_id, gender_name) VALUES
(1, 'Male'),
(2, 'Female'),
(3, 'Other'),
(4, 'Prefer not to say')
ON DUPLICATE KEY UPDATE gender_name = VALUES(gender_name);

-- Provinces (Philippine provinces)
INSERT INTO provinces (province_id, province_name, country) VALUES
(1, 'Metro Manila', 'Philippines'),
(2, 'Cebu', 'Philippines'),
(3, 'Davao', 'Philippines'),
(4, 'Laguna', 'Philippines'),
(5, 'Cavite', 'Philippines'),
(6, 'Bulacan', 'Philippines'),
(7, 'Rizal', 'Philippines'),
(8, 'Pampanga', 'Philippines'),
(9, 'Quezon', 'Philippines'),
(10, 'Batangas', 'Philippines')
ON DUPLICATE KEY UPDATE province_name = VALUES(province_name);

-- Bank Account Types
INSERT INTO bank_account_types (account_type_id, type_name, description) VALUES
(1, 'Savings Account', 'Regular savings account with interest'),
(2, 'Checking Account', 'Checking account for daily transactions'),
(3, 'Time Deposit', 'Fixed-term deposit account'),
(4, 'Current Account', 'Business current account'),
(5, 'USD Account', 'Foreign currency savings account')
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

-- Bank Employees (for customer account creation tracking)
INSERT INTO bank_employees (employee_id, employee_name, created_at) VALUES
(1, 'Bank Teller 001', NOW() - INTERVAL 30 DAY),
(2, 'Bank Teller 002', NOW() - INTERVAL 25 DAY),
(3, 'Account Manager 001', NOW() - INTERVAL 20 DAY),
(4, 'Bank Officer 001', NOW() - INTERVAL 15 DAY)
ON DUPLICATE KEY UPDATE employee_name = VALUES(employee_name);

-- Bank Customers
INSERT INTO bank_customers (customer_id, last_name, first_name, middle_name, password_hash, created_at, created_by_employee_id) VALUES
(1, 'Reyes', 'Juan', 'Dela', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW() - INTERVAL 90 DAY, 1),
(2, 'Santos', 'Maria', 'Garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW() - INTERVAL 75 DAY, 1),
(3, 'Cruz', 'Jose', 'Ramos', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW() - INTERVAL 60 DAY, 2),
(4, 'Lopez', 'Anna', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW() - INTERVAL 45 DAY, 2),
(5, 'Garcia', 'Roberto', 'Fernandez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW() - INTERVAL 30 DAY, 3)
ON DUPLICATE KEY UPDATE last_name = VALUES(last_name);

-- Customer Profiles
INSERT INTO customer_profiles (profile_id, customer_id, gender_id, date_of_birth, marital_status, national_id, occupation, company, income_range, preferred_language, nationality, loyalty_member, profile_created_at) VALUES
(1, 1, 1, '1985-05-15', 'married', 'TIN-001-234-567-890', 'Software Engineer', 'Tech Solutions Inc.', '50000-100000', 'English', 'Filipino', TRUE, NOW() - INTERVAL 85 DAY),
(2, 2, 2, '1988-03-20', 'single', 'TIN-002-345-678-901', 'Marketing Manager', 'Digital Marketing Co.', '30000-50000', 'English', 'Filipino', TRUE, NOW() - INTERVAL 70 DAY),
(3, 3, 1, '1990-08-10', 'married', 'TIN-003-456-789-012', 'Business Owner', 'Small Business Corp', '100000-200000', 'Tagalog', 'Filipino', TRUE, NOW() - INTERVAL 55 DAY),
(4, 4, 2, '1992-11-25', 'single', 'TIN-004-567-890-123', 'Teacher', 'Public School', '20000-30000', 'Tagalog', 'Filipino', FALSE, NOW() - INTERVAL 40 DAY),
(5, 5, 1, '1987-07-30', 'married', 'TIN-005-678-901-234', 'Accountant', 'Accounting Firm', '40000-60000', 'English', 'Filipino', TRUE, NOW() - INTERVAL 25 DAY)
ON DUPLICATE KEY UPDATE marital_status = VALUES(marital_status);

-- Addresses
INSERT INTO addresses (address_id, customer_id, address_line, city, province_id, postal_code, address_type, is_primary, created_at) VALUES
(1, 1, '123 Main Street, Barangay San Antonio', 'Makati City', 1, '1200', 'home', TRUE, NOW() - INTERVAL 85 DAY),
(2, 2, '456 Rizal Avenue', 'Quezon City', 1, '1100', 'home', TRUE, NOW() - INTERVAL 70 DAY),
(3, 3, '789 EDSA Extension', 'Mandaluyong City', 1, '1550', 'home', TRUE, NOW() - INTERVAL 55 DAY),
(4, 4, '321 P. Burgos Street', 'Manila', 1, '1000', 'home', TRUE, NOW() - INTERVAL 40 DAY),
(5, 5, '654 Ayala Avenue', 'Makati City', 1, '1200', 'home', TRUE, NOW() - INTERVAL 25 DAY)
ON DUPLICATE KEY UPDATE address_line = VALUES(address_line);

-- Emails
INSERT INTO emails (email_id, customer_id, email, is_primary, created_at) VALUES
(1, 1, 'juan.reyes@email.com', TRUE, NOW() - INTERVAL 85 DAY),
(2, 2, 'maria.santos@email.com', TRUE, NOW() - INTERVAL 70 DAY),
(3, 3, 'jose.cruz@email.com', TRUE, NOW() - INTERVAL 55 DAY),
(4, 4, 'anna.lopez@email.com', TRUE, NOW() - INTERVAL 40 DAY),
(5, 5, 'roberto.garcia@email.com', TRUE, NOW() - INTERVAL 25 DAY)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Phones
INSERT INTO phones (phone_id, customer_id, phone_number, phone_type, is_primary, created_at) VALUES
(1, 1, '09171234111', 'mobile', TRUE, NOW() - INTERVAL 85 DAY),
(2, 2, '09171234112', 'mobile', TRUE, NOW() - INTERVAL 70 DAY),
(3, 3, '09171234113', 'mobile', TRUE, NOW() - INTERVAL 55 DAY),
(4, 4, '09171234114', 'mobile', TRUE, NOW() - INTERVAL 40 DAY),
(5, 5, '09171234115', 'mobile', TRUE, NOW() - INTERVAL 25 DAY)
ON DUPLICATE KEY UPDATE phone_number = VALUES(phone_number);

-- Customer Accounts
INSERT INTO customer_accounts (account_id, customer_id, account_number, account_type_id, interest_rate, last_interest_date, is_locked, created_at, created_by_employee_id) VALUES
(1, 1, 'SA-001-2024', 1, 2.50, '2025-11-30', FALSE, NOW() - INTERVAL 80 DAY, 1),
(2, 2, 'CA-002-2024', 2, 1.00, '2025-11-30', FALSE, NOW() - INTERVAL 65 DAY, 1),
(3, 3, 'TD-003-2024', 3, 3.50, '2025-11-30', FALSE, NOW() - INTERVAL 50 DAY, 2),
(4, 4, 'SA-004-2024', 1, 2.50, '2025-11-30', FALSE, NOW() - INTERVAL 35 DAY, 2),
(5, 5, 'CA-005-2024', 4, 1.50, '2025-11-30', FALSE, NOW() - INTERVAL 20 DAY, 3)
ON DUPLICATE KEY UPDATE account_number = VALUES(account_number);

-- Customer Linked Accounts
INSERT INTO customer_linked_accounts (link_id, customer_id, account_id, linked_at, is_active) VALUES
(1, 1, 1, NOW() - INTERVAL 80 DAY, TRUE),
(2, 2, 2, NOW() - INTERVAL 65 DAY, TRUE),
(3, 3, 3, NOW() - INTERVAL 50 DAY, TRUE),
(4, 4, 4, NOW() - INTERVAL 35 DAY, TRUE),
(5, 5, 5, NOW() - INTERVAL 20 DAY, TRUE)
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active);

-- Transaction Types
INSERT INTO transaction_types (transaction_type_id, type_name, description) VALUES
(1, 'Deposit', 'Cash or check deposit to account'),
(2, 'Withdrawal', 'Cash withdrawal from account'),
(3, 'Transfer', 'Transfer between accounts'),
(4, 'Interest Credit', 'Interest payment credited'),
(5, 'Service Charge', 'Bank service fee charged'),
(6, 'Loan Disbursement', 'Loan amount disbursed to account'),
(7, 'Loan Payment', 'Loan payment received')
ON DUPLICATE KEY UPDATE type_name = VALUES(type_name);

-- Bank Transactions
INSERT INTO bank_transactions (transaction_id, transaction_ref, account_id, transaction_type_id, amount, related_account_id, description, employee_id, created_at) VALUES
(1, 'TXN-2025-001', 1, 1, 50000.00, NULL, 'Initial deposit', 1, NOW() - INTERVAL 75 DAY),
(2, 'TXN-2025-002', 2, 1, 100000.00, NULL, 'Salary deposit', 1, NOW() - INTERVAL 60 DAY),
(3, 'TXN-2025-003', 1, 2, 5000.00, NULL, 'Cash withdrawal', 1, NOW() - INTERVAL 70 DAY),
(4, 'TXN-2025-004', 3, 1, 500000.00, NULL, 'Time deposit opening', 2, NOW() - INTERVAL 45 DAY),
(5, 'TXN-2025-005', 1, 4, 125.00, NULL, 'Monthly interest credit', 1, NOW() - INTERVAL 30 DAY),
(6, 'TXN-2025-006', 2, 2, 25000.00, NULL, 'Withdrawal', 1, NOW() - INTERVAL 55 DAY),
(7, 'TXN-2025-007', 1, 3, 10000.00, 2, 'Transfer to checking account', 1, NOW() - INTERVAL 65 DAY),
(8, 'TXN-2025-008', 5, 5, 100.00, NULL, 'Monthly service charge', 3, NOW() - INTERVAL 15 DAY)
ON DUPLICATE KEY UPDATE transaction_ref = VALUES(transaction_ref);

-- Payroll Payslips (Alternative payslip table using employee_id)
INSERT INTO payroll_payslips (payslip_id, employee_id, pay_period_start, pay_period_end, gross_salary, deduction, net_pay, release_date) VALUES
(1, 1, '2024-11-01', '2024-11-30', 65000.00, 11700.00, 53300.00, '2024-12-05'),
(2, 2, '2024-11-01', '2024-11-30', 200000.00, 36000.00, 164000.00, '2024-12-05'),
(3, 3, '2024-11-01', '2024-11-30', 220000.00, 39600.00, 180400.00, '2024-12-05'),
(4, 8, '2024-11-01', '2024-11-30', 48000.00, 8640.00, 39360.00, '2024-12-05'),
(5, 12, '2024-11-01', '2024-11-30', 35000.00, 6300.00, 28700.00, '2024-12-05')
ON DUPLICATE KEY UPDATE gross_salary = VALUES(gross_salary);

-- Reports (HRIS Reports)
INSERT INTO reports (report_id, attendance_summary, recruitment_summary, leave_summary, payroll_summary) VALUES
(1, 'Total attendance: 450 days, Present: 380, Absent: 50, Late: 20', 'Active postings: 3, Applications: 15, Hired: 2', 'Total leave requests: 25, Approved: 20, Pending: 5', 'November 2025 payroll: 25 employees, Total gross: 2,500,000 PHP'),
(2, 'Total attendance: 480 days, Present: 420, Absent: 40, Late: 20', 'Active postings: 2, Applications: 10, Hired: 1', 'Total leave requests: 30, Approved: 28, Pending: 2', 'December 2025 payroll: 25 employees, Total gross: 2,600,000 PHP')
ON DUPLICATE KEY UPDATE attendance_summary = VALUES(attendance_summary);

-- ========================================
-- 4G. BANKING MODULE - MISSIONS & BANK USERS
-- ========================================

-- Missions (Banking rewards/missions)
INSERT INTO missions (id, mission_text, points_value, created_at) VALUES
(1, 'Complete your first transaction', 100.00, NOW() - INTERVAL 90 DAY),
(2, 'Maintain minimum balance for 3 months', 500.00, NOW() - INTERVAL 60 DAY),
(3, 'Refer a friend to open an account', 1000.00, NOW() - INTERVAL 45 DAY),
(4, 'Use mobile banking 10 times', 300.00, NOW() - INTERVAL 30 DAY),
(5, 'Set up automatic bill payment', 200.00, NOW() - INTERVAL 20 DAY)
ON DUPLICATE KEY UPDATE mission_text = VALUES(mission_text);

-- Bank Users (Banking system users - different from employee users)
INSERT INTO bank_users (id, first_name, middle_name, last_name, address, city_province, email, contact_number, birthday, password, verification_code, bank_id, total_points, created_at, is_verified) VALUES
(1, 'Juan', 'Dela', 'Reyes', '123 Main Street, Barangay San Antonio', 'Makati City, Metro Manila', 'juan.reyes@bank.com', '09171234111', '1985-05-15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'VER001', 'BANK-001', 1500.00, NOW() - INTERVAL 85 DAY, TRUE),
(2, 'Maria', 'Garcia', 'Santos', '456 Rizal Avenue', 'Quezon City, Metro Manila', 'maria.santos@bank.com', '09171234112', '1988-03-20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'VER002', 'BANK-002', 800.00, NOW() - INTERVAL 70 DAY, TRUE),
(3, 'Jose', 'Ramos', 'Cruz', '789 EDSA Extension', 'Mandaluyong City, Metro Manila', 'jose.cruz@bank.com', '09171234113', '1990-08-10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'VER003', 'BANK-003', 2000.00, NOW() - INTERVAL 55 DAY, TRUE)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- User Missions (Banking mission completions)
INSERT INTO user_missions (id, user_id, mission_id, points_earned, completed_at) VALUES
(1, 1, 1, 100.00, NOW() - INTERVAL 80 DAY),
(2, 1, 2, 500.00, NOW() - INTERVAL 60 DAY),
(3, 1, 4, 300.00, NOW() - INTERVAL 30 DAY),
(4, 2, 1, 100.00, NOW() - INTERVAL 65 DAY),
(5, 2, 5, 200.00, NOW() - INTERVAL 15 DAY),
(6, 3, 1, 100.00, NOW() - INTERVAL 50 DAY),
(7, 3, 2, 500.00, NOW() - INTERVAL 45 DAY),
(8, 3, 3, 1000.00, NOW() - INTERVAL 40 DAY),
(9, 3, 4, 300.00, NOW() - INTERVAL 25 DAY),
(10, 3, 5, 200.00, NOW() - INTERVAL 20 DAY)
ON DUPLICATE KEY UPDATE points_earned = VALUES(points_earned);

-- ========================================
-- 5. BANK ACCOUNTS
-- ========================================

INSERT INTO bank_accounts (code, name, bank_name, account_number, currency, current_balance, is_active) VALUES
('BANK001', 'Evergreen Main Account', 'BDO Unibank', '1234567890', 'PHP', 2500000.00, TRUE),
('BANK002', 'Evergreen Payroll Account', 'Metrobank', '9876543210', 'PHP', 500000.00, TRUE),
('BANK003', 'Evergreen Operations Account', 'BPI', '5555666677', 'PHP', 1000000.00, TRUE),
('BANK004', 'Evergreen Investment Account', 'Security Bank', 'SB123456789', 'PHP', 1500000.00, TRUE),
('BANK005', 'Evergreen Savings Account', 'EastWest Bank', 'EW987654321', 'PHP', 750000.00, TRUE),
('BANK006', 'Evergreen USD Account', 'BDO Unibank', 'BDO-USD-001', 'USD', 50000.00, TRUE),
('BANK007', 'Evergreen Petty Cash', 'Cash', 'CASH-001', 'PHP', 50000.00, TRUE),
('BANK008', 'Evergreen Emergency Fund', 'UnionBank', 'UB456789123', 'PHP', 300000.00, TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- 6. SALARY COMPONENTS
-- ========================================

-- EARNINGS
INSERT INTO salary_components (code, name, type, calculation_method, value, description, is_active) VALUES
('BASIC', 'Basic Salary', 'earning', 'fixed', 25000.00, 'Monthly basic salary', TRUE),
('MEAL', 'Meal Allowance', 'earning', 'fixed', 2000.00, 'Monthly meal allowance', TRUE),
('COMM', 'Communication Allowance', 'earning', 'fixed', 1500.00, 'Monthly communication allowance', TRUE),
('RICE', 'Rice Subsidy Allowance', 'earning', 'fixed', 1000.00, 'Monthly rice subsidy', TRUE),
('TRANSPORT', 'Transportation Allowance', 'earning', 'fixed', 3000.00, 'Monthly transportation allowance', TRUE),
('NIGHT', 'Night Shift Pay', 'earning', 'per_hour', 50.00, 'Per hour night shift differential', TRUE),
('OT', 'Overtime Pay', 'earning', 'per_hour', 75.00, 'Per hour overtime rate', TRUE),
('WFH_WIFI', 'WFH Wifi Allowance', 'earning', 'fixed', 500.00, 'Work from home wifi allowance', TRUE),
('WFH_ELEC', 'WFH Electricity Subsidy', 'earning', 'fixed', 800.00, 'Work from home electricity subsidy', TRUE),
('BONUS', 'Performance Bonus', 'earning', 'fixed', 5000.00, 'Monthly performance bonus', TRUE),
('COMMISSION', 'Sales Commission', 'earning', 'percent', 2.50, '2.5% of sales', TRUE),
('HAZARD', 'Hazard Pay', 'earning', 'fixed', 1000.00, 'Hazardous work allowance', TRUE),
('SHIFT', 'Shift Differential', 'earning', 'per_hour', 25.00, 'Shift differential pay', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- DEDUCTIONS
INSERT INTO salary_components (code, name, type, calculation_method, value, description, is_active) VALUES
('SSS_EMP', 'SSS Employee Contribution', 'deduction', 'percent', 4.50, 'SSS employee contribution', TRUE),
('PAGIBIG_EMP', 'Pag-IBIG Employee Contribution', 'deduction', 'fixed', 100.00, 'Pag-IBIG employee contribution', TRUE),
('PHILHEALTH_EMP', 'PhilHealth Employee Contribution', 'deduction', 'percent', 3.00, 'PhilHealth employee contribution', TRUE),
('WHT', 'Withholding Tax', 'deduction', 'formula', 0.00, 'BIR withholding tax', TRUE),
('LOAN', 'Salary Loan Deduction', 'deduction', 'fixed', 2000.00, 'Monthly salary loan payment', TRUE),
('ADVANCE', 'Salary Advance', 'deduction', 'fixed', 1500.00, 'Salary advance deduction', TRUE),
('UNIFORM', 'Uniform Deduction', 'deduction', 'fixed', 300.00, 'Uniform cost deduction', TRUE),
('MEDICAL', 'Medical Deduction', 'deduction', 'fixed', 500.00, 'Medical insurance deduction', TRUE),
('LATE', 'Late Deduction', 'deduction', 'per_hour', 50.00, 'Late arrival deduction', TRUE),
('ABSENT', 'Absence Deduction', 'deduction', 'per_day', 1000.00, 'Absence deduction', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- TAXES
INSERT INTO salary_components (code, name, type, calculation_method, value, description, is_active) VALUES
('SSS_TAX', 'SSS Employee Contributions', 'tax', 'percent', 4.50, 'SSS employee contribution', TRUE),
('PAGIBIG_TAX', 'Pag-IBIG (HDMF) Employee Contributions', 'tax', 'fixed', 100.00, 'Pag-IBIG employee contribution', TRUE),
('PHILHEALTH_TAX', 'PhilHealth Employee Contributions', 'tax', 'percent', 3.00, 'PhilHealth employee contribution', TRUE),
('WHT_TAX', 'Withholding Tax', 'tax', 'formula', 0.00, 'BIR withholding tax', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- EMPLOYER CONTRIBUTIONS
INSERT INTO salary_components (code, name, type, calculation_method, value, description, is_active) VALUES
('PAGIBIG_ER', 'Pag-IBIG (HDMF) Employer Contribution', 'employer_contrib', 'fixed', 100.00, 'Pag-IBIG employer contribution', TRUE),
('PHILHEALTH_ER', 'PhilHealth Employer Contribution', 'employer_contrib', 'percent', 3.00, 'PhilHealth employer contribution', TRUE),
('SSS_EC_ER', 'SSS EC ER Contribution', 'employer_contrib', 'fixed', 10.00, 'SSS EC employer contribution', TRUE),
('SSS_ER', 'SSS Employer Contribution', 'employer_contrib', 'percent', 8.50, 'SSS employer contribution', TRUE),
('13TH_MONTH', '13th Month Pay', 'employer_contrib', 'percent', 8.33, '13th month pay', TRUE),
('SIL', 'Service Incentive Leave', 'employer_contrib', 'percent', 0.83, 'Service incentive leave', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- 7. EXPENSE CATEGORIES
-- ========================================

-- Note: Some categories reference EXP-001 through EXP-005 accounts for backward compatibility
INSERT INTO expense_categories (code, name, account_id, description, is_active) VALUES
('OFFICE', 'Office Supplies', (SELECT id FROM accounts WHERE code = '6203'), 'Office supplies and materials', TRUE),
('TRAVEL', 'Travel & Transportation', (SELECT id FROM accounts WHERE code = '6206'), 'Business travel expenses', TRUE),
('MEALS', 'Meals & Entertainment', (SELECT id FROM accounts WHERE code = '6205'), 'Business meals and entertainment', TRUE),
('UTILITIES', 'Utilities', (SELECT id FROM accounts WHERE code = '6202'), 'Electricity, water, internet', TRUE),
('FACILITIES', 'Facilities', (SELECT id FROM accounts WHERE code = '6201'), 'Office rent and facilities', TRUE),
('TRAINING', 'Training & Development', (SELECT id FROM accounts WHERE code = '7004'), 'Employee training and development', TRUE),
('EQUIPMENT', 'Equipment', (SELECT id FROM accounts WHERE code = '1501'), 'Office equipment and tools', TRUE),
('MARKETING', 'Marketing & Advertising', (SELECT id FROM accounts WHERE code = '6205'), 'Marketing and advertising expenses', TRUE),
('PROFESSIONAL', 'Professional Services', (SELECT id FROM accounts WHERE code = '6204'), 'Legal, accounting, consulting fees', TRUE),
('INSURANCE', 'Insurance', (SELECT id FROM accounts WHERE code = '6207'), 'Insurance premiums', TRUE),
('MAINTENANCE', 'Repairs & Maintenance', (SELECT id FROM accounts WHERE code = '6209'), 'Equipment maintenance and repairs', TRUE),
('COMM', 'Communication', (SELECT id FROM accounts WHERE code = '6210'), 'Phone, internet, and communication costs', TRUE),
('COMMUNICATION', 'Communication Services', (SELECT id FROM accounts WHERE code = '6210'), 'Phone, internet, postage', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert backup accounts for expense categories (if they don't exist)
INSERT IGNORE INTO accounts (code, name, type_id, description, is_active, created_by) VALUES
('EXP-001', 'Travel Expenses', (SELECT id FROM account_types WHERE category = 'expense' LIMIT 1), 'Business travel and transportation costs', TRUE, 1),
('EXP-002', 'Meals & Entertainment', (SELECT id FROM account_types WHERE category = 'expense' LIMIT 1), 'Business meals and client entertainment', TRUE, 1),
('EXP-003', 'Office Supplies', (SELECT id FROM account_types WHERE category = 'expense' LIMIT 1), 'Office supplies and equipment', TRUE, 1),
('EXP-004', 'Communication Expenses', (SELECT id FROM account_types WHERE category = 'expense' LIMIT 1), 'Phone, internet, and communication costs', TRUE, 1),
('EXP-005', 'Training & Development', (SELECT id FROM account_types WHERE category = 'expense' LIMIT 1), 'Employee training and development', TRUE, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- 8. LOAN TYPES
-- ========================================

INSERT INTO loan_types (code, name, max_amount, max_term_months, interest_rate, description, is_active) VALUES
('SALARY', 'Salary Loan', 50000.00, 12, 0.05, 'Employee salary loan', TRUE),
('EMERGENCY', 'Emergency Loan', 25000.00, 6, 0.08, 'Emergency financial assistance', TRUE),
('HOUSING', 'Housing Loan', 500000.00, 60, 0.06, 'Housing loan assistance', TRUE),
('EDUCATION', 'Education Loan', 100000.00, 24, 0.04, 'Educational assistance loan', TRUE),
('VEHICLE', 'Vehicle Loan', 300000.00, 36, 0.07, 'Vehicle purchase loan', TRUE),
('MEDICAL', 'Medical Loan', 15000.00, 12, 0.03, 'Medical emergency loan', TRUE),
('APPLIANCE', 'Appliance Loan', 20000.00, 18, 0.05, 'Home appliance loan', TRUE),
-- Additional loan types from sample_loan_data.sql
('PL', 'Personal Loan', 500000.00, 60, 12.5000, 'Personal loans for employees', TRUE),
('HL', 'Housing Loan (Extended)', 2000000.00, 360, 8.5000, 'Housing/Home loans with extended terms', TRUE),
('VL', 'Vehicle Loan (Extended)', 1000000.00, 60, 10.0000, 'Auto/Vehicle loans', TRUE),
('EL', 'Emergency Loan (Extended)', 100000.00, 12, 15.0000, 'Quick emergency loans', TRUE),
('SL', 'Salary Loan (Extended)', 200000.00, 24, 14.0000, 'Salary advance loans with higher limits', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ========================================
-- 9. COMPREHENSIVE JOURNAL ENTRIES
-- ========================================

-- Get account IDs for journal entries
SET @cash_hand = (SELECT id FROM accounts WHERE code = '1001');
SET @cash_bdo = (SELECT id FROM accounts WHERE code = '1002');
SET @cash_bpi = (SELECT id FROM accounts WHERE code = '1003');
SET @cash_metro = (SELECT id FROM accounts WHERE code = '1004');
SET @cash_security = (SELECT id FROM accounts WHERE code = '1005');
SET @ar_trade = (SELECT id FROM accounts WHERE code = '1101');
SET @ar_other = (SELECT id FROM accounts WHERE code = '1102');
SET @inventory_raw = (SELECT id FROM accounts WHERE code = '1201');
SET @inventory_finished = (SELECT id FROM accounts WHERE code = '1202');
SET @inventory_wip = (SELECT id FROM accounts WHERE code = '1203');
SET @prepaid_exp = (SELECT id FROM accounts WHERE code = '1301');
SET @prepaid_insurance = (SELECT id FROM accounts WHERE code = '1302');
SET @prepaid_rent = (SELECT id FROM accounts WHERE code = '1303');
SET @equipment = (SELECT id FROM accounts WHERE code = '1501');
SET @machinery = (SELECT id FROM accounts WHERE code = '1502');
SET @vehicles = (SELECT id FROM accounts WHERE code = '1503');
SET @building = (SELECT id FROM accounts WHERE code = '1504');
SET @land = (SELECT id FROM accounts WHERE code = '1505');
SET @accum_dep_equip = (SELECT id FROM accounts WHERE code = '1510');
SET @accum_dep_mach = (SELECT id FROM accounts WHERE code = '1511');
SET @accum_dep_veh = (SELECT id FROM accounts WHERE code = '1512');
SET @accum_dep_build = (SELECT id FROM accounts WHERE code = '1513');
SET @intangible = (SELECT id FROM accounts WHERE code = '1601');
SET @software = (SELECT id FROM accounts WHERE code = '1602');
SET @investments = (SELECT id FROM accounts WHERE code = '1701');

SET @ap_trade = (SELECT id FROM accounts WHERE code = '2001');
SET @ap_other = (SELECT id FROM accounts WHERE code = '2002');
SET @salaries_payable = (SELECT id FROM accounts WHERE code = '2101');
SET @wages_payable = (SELECT id FROM accounts WHERE code = '2102');
SET @taxes_payable = (SELECT id FROM accounts WHERE code = '2201');
SET @vat_payable = (SELECT id FROM accounts WHERE code = '2202');
SET @wht_payable = (SELECT id FROM accounts WHERE code = '2203');
SET @sss_payable = (SELECT id FROM accounts WHERE code = '2301');
SET @philhealth_payable = (SELECT id FROM accounts WHERE code = '2302');
SET @pagibig_payable = (SELECT id FROM accounts WHERE code = '2303');
SET @loan_current = (SELECT id FROM accounts WHERE code = '2401');
SET @accrued_exp = (SELECT id FROM accounts WHERE code = '2501');
SET @accrued_int = (SELECT id FROM accounts WHERE code = '2502');
SET @deferred_rev = (SELECT id FROM accounts WHERE code = '2601');

SET @loan_longterm = (SELECT id FROM accounts WHERE code = '3001');
SET @bonds_payable = (SELECT id FROM accounts WHERE code = '3002');
SET @mortgage_payable = (SELECT id FROM accounts WHERE code = '3003');

SET @capital_stock = (SELECT id FROM accounts WHERE code = '4001');
SET @paid_in_capital = (SELECT id FROM accounts WHERE code = '4002');
SET @retained_earnings = (SELECT id FROM accounts WHERE code = '4101');
SET @current_year_pl = (SELECT id FROM accounts WHERE code = '4102');
SET @treasury_stock = (SELECT id FROM accounts WHERE code = '4201');

SET @sales_revenue = (SELECT id FROM accounts WHERE code = '5001');
SET @service_revenue = (SELECT id FROM accounts WHERE code = '5002');
SET @consulting_revenue = (SELECT id FROM accounts WHERE code = '5003');
SET @rental_revenue = (SELECT id FROM accounts WHERE code = '5004');
SET @interest_income = (SELECT id FROM accounts WHERE code = '5101');
SET @dividend_income = (SELECT id FROM accounts WHERE code = '5102');
SET @other_income = (SELECT id FROM accounts WHERE code = '5103');
SET @gain_sale_assets = (SELECT id FROM accounts WHERE code = '5104');

SET @cogs = (SELECT id FROM accounts WHERE code = '6001');
SET @cost_services = (SELECT id FROM accounts WHERE code = '6002');
SET @salaries_wages = (SELECT id FROM accounts WHERE code = '6101');
SET @employee_benefits = (SELECT id FROM accounts WHERE code = '6102');
SET @payroll_taxes = (SELECT id FROM accounts WHERE code = '6103');
SET @rent_expense = (SELECT id FROM accounts WHERE code = '6201');
SET @utilities_expense = (SELECT id FROM accounts WHERE code = '6202');
SET @office_supplies = (SELECT id FROM accounts WHERE code = '6203');
SET @professional_fees = (SELECT id FROM accounts WHERE code = '6204');
SET @marketing_advertising = (SELECT id FROM accounts WHERE code = '6205');
SET @transportation_travel = (SELECT id FROM accounts WHERE code = '6206');
SET @insurance_expense = (SELECT id FROM accounts WHERE code = '6207');
SET @depreciation_expense = (SELECT id FROM accounts WHERE code = '6208');
SET @repairs_maintenance = (SELECT id FROM accounts WHERE code = '6209');
SET @communication_expense = (SELECT id FROM accounts WHERE code = '6210');

SET @general_admin = (SELECT id FROM accounts WHERE code = '7001');
SET @management_salaries = (SELECT id FROM accounts WHERE code = '7002');
SET @office_equipment_exp = (SELECT id FROM accounts WHERE code = '7003');
SET @training_development = (SELECT id FROM accounts WHERE code = '7004');
SET @research_development = (SELECT id FROM accounts WHERE code = '7005');

SET @interest_expense = (SELECT id FROM accounts WHERE code = '8001');
SET @bank_charges = (SELECT id FROM accounts WHERE code = '8002');
SET @bad_debt_expense = (SELECT id FROM accounts WHERE code = '8003');
SET @loss_sale_assets = (SELECT id FROM accounts WHERE code = '8004');
SET @miscellaneous_expense = (SELECT id FROM accounts WHERE code = '8005');

-- Get journal type IDs
SET @gj_type = (SELECT id FROM journal_types WHERE code = 'GJ');
SET @cr_type = (SELECT id FROM journal_types WHERE code = 'CR');
SET @cd_type = (SELECT id FROM journal_types WHERE code = 'CD');
SET @pr_type = (SELECT id FROM journal_types WHERE code = 'PR');
SET @ap_type = (SELECT id FROM journal_types WHERE code = 'AP');
SET @ar_type = (SELECT id FROM journal_types WHERE code = 'AR');
SET @aj_type = (SELECT id FROM journal_types WHERE code = 'AJ');

-- Get fiscal period IDs
SET @fiscal_q1_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1');
SET @fiscal_q2_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q2');
SET @fiscal_q3_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q3');
SET @fiscal_q4_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q4');
SET @jan_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'January 2025');
SET @feb_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'February 2025');
SET @mar_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'March 2025');
SET @apr_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'April 2025');
SET @may_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'May 2025');
SET @jun_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'June 2025');
SET @jul_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'July 2025');
SET @aug_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'August 2025');
SET @sep_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'September 2025');
SET @oct_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'October 2025');
SET @nov_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'November 2025');
SET @dec_2025 = (SELECT id FROM fiscal_periods WHERE period_name = 'December 2025');

-- ========================================
-- INITIAL CAPITAL INVESTMENT (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0001', @gj_type, '2025-01-02', 'Initial capital investment', @jan_2025, 'INV-001', 10000000.00, 10000000.00, 'posted', 1, NOW(), 1);

SET @je1 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je1, @cash_bdo, 5000000.00, 0.00, 'Cash deposit - BDO'),
(@je1, @cash_bpi, 2000000.00, 0.00, 'Cash deposit - BPI'),
(@je1, @equipment, 1000000.00, 0.00, 'Office equipment purchase'),
(@je1, @building, 1500000.00, 0.00, 'Building acquisition'),
(@je1, @land, 500000.00, 0.00, 'Land acquisition'),
(@je1, @capital_stock, 0.00, 10000000.00, 'Owner capital contribution');

-- ========================================
-- BANK LOAN (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0002', @gj_type, '2025-01-05', 'Bank loan proceeds', @jan_2025, 'LOAN-001', 2000000.00, 2000000.00, 'posted', 1, NOW(), 1);

SET @je2 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je2, @cash_metro, 2000000.00, 0.00, 'Loan proceeds'),
(@je2, @loan_longterm, 0.00, 2000000.00, 'Long-term loan payable');

-- ========================================
-- INVENTORY PURCHASE (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0003', @ap_type, '2025-01-10', 'Inventory purchase on account', @jan_2025, 'PO-001', 1500000.00, 1500000.00, 'posted', 1, NOW(), 1);

SET @je3 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je3, @inventory_raw, 800000.00, 0.00, 'Raw materials inventory'),
(@je3, @inventory_finished, 700000.00, 0.00, 'Finished goods inventory'),
(@je3, @ap_trade, 0.00, 1500000.00, 'Trade payable');

-- ========================================
-- SALES REVENUE - CASH (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0004', @cr_type, '2025-01-15', 'Cash sales', @jan_2025, 'INV-2501', 800000.00, 800000.00, 'posted', 1, NOW(), 1);

SET @je4 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je4, @cash_bdo, 800000.00, 0.00, 'Cash received'),
(@je4, @sales_revenue, 0.00, 800000.00, 'Product sales');

-- ========================================
-- COST OF GOODS SOLD (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0005', @gj_type, '2025-01-15', 'COGS for sales', @jan_2025, 'INV-2501', 480000.00, 480000.00, 'posted', 1, NOW(), 1);

SET @je5 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je5, @cogs, 480000.00, 0.00, 'Cost of goods sold'),
(@je5, @inventory_finished, 0.00, 480000.00, 'Inventory reduction');

-- ========================================
-- SERVICE REVENUE - CREDIT (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0006', @ar_type, '2025-01-20', 'Service revenue on account', @jan_2025, 'INV-2502', 600000.00, 600000.00, 'posted', 1, NOW(), 1);

SET @je6 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je6, @ar_trade, 600000.00, 0.00, 'Customer receivable'),
(@je6, @service_revenue, 0.00, 600000.00, 'Service income');

-- ========================================
-- PAYROLL PROCESSING (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0007', @pr_type, '2025-01-31', 'January payroll', @jan_2025, 'PR-2501', 500000.00, 500000.00, 'posted', 1, NOW(), 1);

SET @je7 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je7, @salaries_wages, 400000.00, 0.00, 'Employee salaries'),
(@je7, @employee_benefits, 50000.00, 0.00, 'Employee benefits'),
(@je7, @payroll_taxes, 50000.00, 0.00, 'Payroll taxes'),
(@je7, @sss_payable, 0.00, 18000.00, 'SSS payable'),
(@je7, @philhealth_payable, 0.00, 12000.00, 'PhilHealth payable'),
(@je7, @pagibig_payable, 0.00, 5000.00, 'Pag-IBIG payable'),
(@je7, @wht_payable, 0.00, 15000.00, 'Withholding tax payable'),
(@je7, @cash_metro, 0.00, 400000.00, 'Net pay');

-- ========================================
-- RENT EXPENSE (January 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0008', @cd_type, '2025-02-01', 'January rent payment', @feb_2025, 'RENT-JAN', 100000.00, 100000.00, 'posted', 1, NOW(), 1);

SET @je8 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je8, @rent_expense, 100000.00, 0.00, 'Office rent'),
(@je8, @cash_bdo, 0.00, 100000.00, 'Cash paid');

-- ========================================
-- UTILITIES EXPENSE (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0009', @cd_type, '2025-02-05', 'Utilities payment', @feb_2025, 'UTIL-FEB', 75000.00, 75000.00, 'posted', 1, NOW(), 1);

SET @je9 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je9, @utilities_expense, 75000.00, 0.00, 'Electricity and water'),
(@je9, @cash_bdo, 0.00, 75000.00, 'Cash paid');

-- ========================================
-- OFFICE SUPPLIES PURCHASE (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0010', @cd_type, '2025-02-10', 'Office supplies', @feb_2025, 'SUP-001', 50000.00, 50000.00, 'posted', 1, NOW(), 1);

SET @je10 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je10, @office_supplies, 50000.00, 0.00, 'Office supplies'),
(@je10, @cash_bpi, 0.00, 50000.00, 'Cash paid');

-- ========================================
-- MARKETING EXPENSE (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0011', @cd_type, '2025-02-15', 'Marketing campaign', @feb_2025, 'MKT-001', 150000.00, 150000.00, 'posted', 1, NOW(), 1);

SET @je11 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je11, @marketing_advertising, 150000.00, 0.00, 'Digital advertising'),
(@je11, @cash_bdo, 0.00, 150000.00, 'Cash paid');

-- ========================================
-- PROFESSIONAL FEES (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0012', @cd_type, '2025-02-20', 'Legal consultation', @feb_2025, 'LEGAL-001', 80000.00, 80000.00, 'posted', 1, NOW(), 1);

SET @je12 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je12, @professional_fees, 80000.00, 0.00, 'Legal fees'),
(@je12, @cash_bdo, 0.00, 80000.00, 'Cash paid');

-- ========================================
-- INTEREST INCOME (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0013', @cr_type, '2025-02-28', 'Bank interest earned', @feb_2025, 'INT-FEB', 10000.00, 10000.00, 'posted', 1, NOW(), 1);

SET @je13 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je13, @cash_bdo, 10000.00, 0.00, 'Interest received'),
(@je13, @interest_income, 0.00, 10000.00, 'Bank interest income');

-- ========================================
-- LOAN INTEREST PAYMENT (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0014', @cd_type, '2025-02-28', 'Loan interest payment', @feb_2025, 'LOAN-INT-FEB', 30000.00, 30000.00, 'posted', 1, NOW(), 1);

SET @je14 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je14, @interest_expense, 30000.00, 0.00, 'Interest on loan'),
(@je14, @cash_metro, 0.00, 30000.00, 'Cash paid');

-- ========================================
-- DEPRECIATION EXPENSE (February 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0015', @aj_type, '2025-02-28', 'Monthly depreciation', @feb_2025, 'DEP-FEB', 20000.00, 20000.00, 'posted', 1, NOW(), 1);

SET @je15 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je15, @depreciation_expense, 20000.00, 0.00, 'Equipment depreciation'),
(@je15, @accum_dep_equip, 0.00, 20000.00, 'Accumulated depreciation');

-- ========================================
-- CUSTOMER PAYMENT (March 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0016', @cr_type, '2025-03-05', 'Payment from ABC Corp', @mar_2025, 'CR-1001', 400000.00, 400000.00, 'posted', 1, NOW(), 1);

SET @je16 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je16, @cash_bpi, 400000.00, 0.00, 'Cash received'),
(@je16, @ar_trade, 0.00, 400000.00, 'AR collection');

-- ========================================
-- EQUIPMENT PURCHASE (March 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0017', @ap_type, '2025-03-10', 'Purchase computers', @mar_2025, 'INV-2001', 250000.00, 250000.00, 'posted', 1, NOW(), 1);

SET @je17 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je17, @equipment, 250000.00, 0.00, 'Equipment purchased'),
(@je17, @ap_trade, 0.00, 250000.00, 'AP to supplier');

-- ========================================
-- DRAFT ENTRY (March 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, created_by) 
VALUES ('JE-2025-0018', @gj_type, '2025-03-15', 'Depreciation for March', @mar_2025, 'ADJ-DEP', 20000.00, 20000.00, 'draft', 1);

SET @je18 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je18, @depreciation_expense, 20000.00, 0.00, 'Monthly depreciation'),
(@je18, @accum_dep_equip, 0.00, 20000.00, 'Accum. depreciation');

-- ========================================
-- TRANSPORTATION EXPENSE (March 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0019', @cd_type, '2025-03-20', 'Fuel and maintenance', @mar_2025, 'TRANS-001', 15000.00, 15000.00, 'posted', 1, NOW(), 1);

SET @je19 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je19, @transportation_travel, 15000.00, 0.00, 'Fuel'),
(@je19, @cash_hand, 0.00, 15000.00, 'Cash');

-- ========================================
-- SERVICE REVENUE - CASH (March 2025)
-- ========================================

INSERT IGNORE INTO journal_entries (journal_no, journal_type_id, entry_date, description, fiscal_period_id, reference_no, total_debit, total_credit, status, posted_by, posted_at, created_by) 
VALUES ('JE-2025-0020', @cr_type, '2025-03-25', 'Consulting services', @mar_2025, 'INV-5001', 300000.00, 300000.00, 'posted', 1, NOW(), 1);

SET @je20 = LAST_INSERT_ID();

INSERT IGNORE INTO journal_lines (journal_entry_id, account_id, debit, credit, memo) VALUES
(@je20, @cash_bdo, 300000.00, 0.00, 'Cash received'),
(@je20, @consulting_revenue, 0.00, 300000.00, 'Consulting revenue');

-- ========================================
-- 10. COMPREHENSIVE LOANS DATA
-- ========================================

INSERT IGNORE INTO loans (loan_no, loan_type_id, borrower_external_no, principal_amount, interest_rate, start_date, term_months, monthly_payment, current_balance, status, created_by, created_at) VALUES
-- Salary Loans
('LN-1001', 1, 'EMP001', 50000.00, 0.05, '2024-01-01', 12, 4500.00, 45000.00, 'active', 1, '2024-01-01 09:00:00'),
('LN-1003', 1, 'EMP005', 30000.00, 0.05, '2024-02-01', 12, 2700.00, 30000.00, 'active', 1, '2024-02-01 11:15:00'),
('LN-1007', 1, 'EMP004', 25000.00, 0.05, '2024-01-20', 12, 2250.00, 25000.00, 'paid', 1, '2024-01-20 15:10:00'),
('LN-1009', 1, 'EMP008', 40000.00, 0.05, '2024-02-15', 12, 3600.00, 40000.00, 'active', 1, '2024-02-15 14:30:00'),
('LN-1010', 1, 'EMP010', 20000.00, 0.05, '2024-03-01', 12, 1800.00, 20000.00, 'active', 1, '2024-03-01 10:00:00'),
('LN-1015', 1, 'EMP002', 35000.00, 0.05, '2024-11-01', 12, 3150.00, 35000.00, 'active', 1, '2024-11-01 09:00:00'),
('LN-1016', 1, 'EMP004', 18000.00, 0.05, '2024-11-15', 12, 1620.00, 18000.00, 'active', 1, '2024-11-15 11:15:00'),
('LN-1017', 1, 'EMP006', 28000.00, 0.05, '2024-12-01', 12, 2520.00, 28000.00, 'active', 1, '2024-12-01 10:00:00'),
('LN-1018', 1, 'EMP008', 200000.00, 0.05, '2024-09-01', 60, 4000.00, 200000.00, 'active', 1, '2024-09-01 14:30:00'),
('LN-1019', 1, 'EMP010', 35000.00, 0.05, '2024-12-10', 24, 1500.00, 35000.00, 'active', 1, '2024-12-10 10:00:00'),

-- Emergency Loans
('LN-1002', 2, 'EMP003', 20000.00, 0.08, '2024-01-15', 6, 3600.00, 18000.00, 'active', 1, '2024-01-15 10:30:00'),
('LN-1005', 2, 'EMP009', 15000.00, 0.08, '2024-02-10', 6, 2700.00, 15000.00, 'active', 1, '2024-02-10 16:45:00'),
('LN-1008', 2, 'EMP006', 10000.00, 0.08, '2024-02-15', 6, 1800.00, 10000.00, 'defaulted', 1, '2024-02-15 12:00:00'),
('LN-1011', 2, 'EMP002', 12000.00, 0.08, '2024-01-10', 6, 2160.00, 12000.00, 'active', 1, '2024-01-10 11:20:00'),

-- Housing Loans
('LN-1004', 3, 'EMP007', 400000.00, 0.06, '2023-06-01', 60, 8000.00, 320000.00, 'active', 1, '2023-06-01 14:20:00'),
('LN-1012', 3, 'EMP001', 300000.00, 0.06, '2023-08-01', 60, 6000.00, 240000.00, 'active', 1, '2023-08-01 15:30:00'),

-- Education Loans
('LN-1006', 4, 'EMP002', 80000.00, 0.04, '2023-09-01', 24, 3500.00, 56000.00, 'active', 1, '2023-09-01 13:30:00'),
('LN-1013', 4, 'EMP003', 60000.00, 0.04, '2024-01-05', 24, 2600.00, 60000.00, 'active', 1, '2024-01-05 09:15:00'),
('LN-1014', 4, 'EMP009', 45000.00, 0.04, '2024-02-20', 24, 1950.00, 45000.00, 'active', 1, '2024-02-20 16:00:00'),

-- Vehicle Loans
('LN-1020', 5, 'EMP011', 250000.00, 0.07, '2024-03-01', 36, 7500.00, 250000.00, 'active', 1, '2024-03-01 10:00:00'),
('LN-1021', 5, 'EMP013', 180000.00, 0.07, '2024-04-15', 36, 5400.00, 180000.00, 'active', 1, '2024-04-15 14:30:00'),

-- Medical Loans
('LN-1022', 6, 'EMP015', 12000.00, 0.03, '2024-05-01', 12, 1000.00, 12000.00, 'active', 1, '2024-05-01 09:00:00'),
('LN-1023', 6, 'EMP017', 8000.00, 0.03, '2024-06-10', 12, 667.00, 8000.00, 'active', 1, '2024-06-10 11:15:00'),

-- Appliance Loans
('LN-1024', 7, 'EMP019', 15000.00, 0.05, '2024-07-01', 18, 900.00, 15000.00, 'active', 1, '2024-07-01 10:00:00'),
('LN-1025', 7, 'EMP021', 20000.00, 0.05, '2024-08-15', 18, 1200.00, 20000.00, 'active', 1, '2024-08-15 14:30:00'),

-- Additional loans from sample_loan_data.sql (using extended loan types)
-- Note: These use different loan_type_id references based on the new types added above
('LOAN-2024-001', (SELECT id FROM loan_types WHERE code = 'PL'), 'EMP001', 150000.00, 12.5000, '2024-01-15', 36, 5025.00, 120000.00, 'active', 1, '2024-01-15 09:00:00'),
('LOAN-2024-002', (SELECT id FROM loan_types WHERE code = 'HL'), 'EMP002', 1500000.00, 8.5000, '2024-02-01', 240, 12850.00, 1450000.00, 'active', 1, '2024-02-01 11:00:00'),
('LOAN-2024-003', (SELECT id FROM loan_types WHERE code = 'VL'), 'EMP003', 500000.00, 10.0000, '2024-03-10', 60, 10625.00, 450000.00, 'active', 1, '2024-03-10 10:00:00'),
('LOAN-2024-004', (SELECT id FROM loan_types WHERE code = 'EL'), 'EMP004', 50000.00, 15.0000, '2023-12-01', 12, 4500.00, 0.00, 'paid', 1, '2023-12-01 09:00:00'),
('LOAN-2024-005', (SELECT id FROM loan_types WHERE code = 'SL'), 'EMP005', 100000.00, 14.0000, '2024-04-01', 24, 4850.00, 85000.00, 'active', 1, '2024-04-01 09:00:00'),
('LOAN-2024-006', (SELECT id FROM loan_types WHERE code = 'PL'), 'EMP001', 75000.00, 12.5000, '2024-05-15', 24, 3575.00, 65000.00, 'active', 1, '2024-05-15 10:00:00'),
('LOAN-2023-010', (SELECT id FROM loan_types WHERE code = 'PL'), 'EMP002', 100000.00, 12.5000, '2023-01-15', 36, 3350.00, 0.00, 'paid', 1, '2023-01-15 09:00:00'),
('LOAN-2023-015', (SELECT id FROM loan_types WHERE code = 'VL'), 'EMP003', 350000.00, 10.0000, '2023-06-01', 60, 7437.50, 280000.00, 'active', 1, '2023-06-01 09:00:00'),
('LOAN-2024-007', (SELECT id FROM loan_types WHERE code = 'EL'), 'EMP004', 25000.00, 15.0000, '2024-06-01', 12, 2250.00, 18000.00, 'active', 1, '2024-06-01 09:00:00'),
('LOAN-2024-008', (SELECT id FROM loan_types WHERE code = 'HL'), 'EMP005', 800000.00, 8.5000, '2024-07-01', 180, 7960.00, 795000.00, 'pending', 1, '2024-07-01 09:00:00')
ON DUPLICATE KEY UPDATE principal_amount = VALUES(principal_amount);

-- ========================================
-- 10.5. LOAN APPLICATIONS DATA (from subsystem)
-- ========================================

INSERT IGNORE INTO loan_applications (
    id, full_name, account_number, contact_number, email, job, monthly_salary, 
    user_email, loan_type, loan_terms, loan_amount, purpose, monthly_payment, 
    due_date, status, remarks, file_name, created_at, approved_by, approved_at, 
    next_payment_due, rejected_by, rejected_at, rejection_remarks, 
    proof_of_income, coe_document, pdf_path
) VALUES
(24, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', NULL, NULL, 'kurtrealisan@gmail.com', 'Home Loan', '24 Months', 5000.00, '0', NULL, NULL, 'Active', 'sdfsdfsdfsd', 'uploads/the-dark-knight-mixed-art-fvy9jfrmv7np7z0r.jpg', '2025-11-01 17:18:39', 'Jerome Malunes', '2025-11-02 17:55:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, 'kurtrealisan@gmail.com', 'Home Loan', '12 Months', 60000.00, 'For house building purposes', 5558.07, '2026-11-02', 'Rejected', 'Invalid ID', 'uploads/download.jpg', '2025-11-02 04:00:24', NULL, NULL, NULL, 'Jerome Malunes', '2025-11-02 17:29:08', 'Invalid ID', NULL, NULL, NULL),
(26, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Car Loan', '24 Months', 50000.00, 'For personal car purposes ', 2544.79, '2027-11-02', 'Active', 'Thank You!', 'uploads/download.jpg', '2025-11-02 10:44:49', 'Jerome Malunes', '2025-11-02 17:15:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Home Loan', '24 Months', 7000.00, 'For family house ni Carspeso', 356.27, '2027-11-02', 'Rejected', 'The ID is not valid', 'uploads/images.jpg', '2025-11-02 10:55:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Personal Loan', '6 Months', 6000.00, 'For study purposes ', 1059.14, '2026-05-02', 'Rejected', 'sffsdfsd', 'uploads/Jespic.jpg', '2025-11-02 12:45:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Home Loan', '30 Months', 6000.00, 'For housing purposes', 255.78, '2028-05-02', 'Active', 'Thank You!', 'uploads/Jespic.jpg', '2025-11-02 12:47:59', 'Jerome Malunes', '2025-11-02 16:44:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Multi-Purpose Loan', '6 Months', 5000.00, 'For multi purpose only', 882.61, '2026-05-02', 'Approved', 'sdfsdfsd', 'uploads/Jespic.jpg', '2025-11-02 13:38:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Kurt Realisan', '1001234567', '09123456789', 'kurtrealisan@gmail.com', 'Data Analyst', 20000.00, '', 'Multi-Purpose Loan', '6 Months', 7000.00, 'For purposes only', 1235.66, '2026-05-02', 'Active', 'OK', 'uploads/Jespic.jpg', '2025-11-02 17:01:28', 'Jerome Malunes', '2025-11-03 01:04:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Car Loan', '6 Months', 10000.00, 'For purposes', 1765.23, '2026-05-02', 'Rejected', 'Invalid ID', 'uploads/Jespic.jpg', '2025-11-02 21:29:52', NULL, NULL, NULL, 'Jerome Malunes', '2025-11-03 05:30:50', 'Invalid ID', NULL, NULL, NULL),
(33, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Home Loan', '6 Months', 5000.00, 'For buying house parts', 882.61, '2026-05-02', 'Active', 'Thank you!', 'uploads/Jespic.jpg', '2025-11-02 21:47:34', 'Jerome Malunes', '2025-11-03 05:48:14', '2025-12-03', NULL, NULL, NULL, NULL, NULL, NULL),
(34, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Multi-Purpose Loan', '6 Months', 7000.00, 'For investment', 1235.66, '2026-05-02', 'Active', 'Thank you for applying loans!! Please pay on the exact time', 'uploads/Jespic.jpg', '2025-11-02 22:24:57', 'Jerome Malunes', '2025-11-03 06:38:36', '2025-12-03', NULL, NULL, NULL, NULL, NULL, 'uploads/loan_approved_34_20251106141556.pdf'),
(35, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Personal Loan', '12 Months', 30000.00, 'For funds ', 2779.04, '2026-11-06', 'Rejected', 'Please input a clear picture of valid ID', 'uploads/Jespic.jpg', '2025-11-06 10:56:13', NULL, NULL, NULL, 'Jerome Malunes', '2025-11-06 19:06:39', 'Please input a clear picture of valid ID', 'uploads/Lord, I pray for this (2).png', 'uploads/download.jpg', 'uploads/loan_rejected_35_20251106141541.pdf'),
(36, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Home Loan', '24 Months', 9000.00, 'Bahay namin maliit lamang', 458.06, '2027-11-06', 'Active', 'Congratulations!!', 'uploads/Jespic.jpg', '2025-11-06 11:20:08', 'Jerome Malunes', '2025-11-06 19:57:54', '2025-12-06', NULL, NULL, NULL, 'uploads/the-dark-knight-mixed-art-fvy9jfrmv7np7z0r.jpg', 'uploads/ERD (1).png', 'uploads/loan_approved_36_20251106140535.pdf'),
(37, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Multi-Purpose Loan', '36 Months', 100000.00, 'For family planning', 3716.36, '2028-11-06', 'Active', 'Please be advised', 'uploads/Jespic.jpg', '2025-11-06 13:52:07', 'Jerome Malunes', '2025-11-06 21:52:50', '2025-12-06', NULL, NULL, NULL, 'uploads/the-dark-knight-mixed-art-fvy9jfrmv7np7z0r.jpg', 'uploads/ERD.png', 'uploads/loan_approved_37_20251106145455.pdf'),
(38, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Car Loan', '24 Months', 7000.00, 'pautang ssob', 356.27, '2027-11-06', 'Rejected', 'Please upload a clear picture of ID', 'uploads/Jespic.jpg', '2025-11-06 14:01:54', NULL, NULL, NULL, 'Jerome Malunes', '2025-11-06 22:27:53', 'Please upload a clear picture of ID', 'uploads/Lord, I pray for this (3).png', 'uploads/images.jpg', 'uploads/loan_rejected_38_20251106153300.pdf'),
(39, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Home Loan', '12 Months', 8000.00, 'Bahay Kubo', 741.08, '2026-11-06', 'Active', 'OK', 'uploads/Jespic.jpg', '2025-11-06 14:39:43', 'Jerome Malunes', '2025-11-06 22:42:16', '2025-12-06', NULL, NULL, NULL, 'uploads/download.jpg', 'uploads/images.jpg', 'uploads/loan_approved_39_20251106155223.pdf'),
(40, 'Mike Beringuela', '1004567890', '09456789012', 'mikeberinguela@gmail.com', 'Project Manager', 70000.00, '', 'Multi-Purpose Loan', '12 Months', 6000.00, 'For purpose', 555.81, '2026-11-07', 'Pending', NULL, 'uploads/Jespic.jpg', '2025-11-07 13:48:14', NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/download.jpg', 'uploads/images.jpg', NULL),
(41, 'Clarence Carpeso', '1006789012', '09678901234', 'clarencecarpeso@gmail.com', 'Crossfire Developer', 20000.00, '', 'Home Loan', '24 Months', 40000.00, 'oh when the saints , ipaghiganti mo ang iglesiaaaaaaaaaaaa', 2035.83, '2027-11-07', 'Active', 'Maureene', 'uploads/Jespic.jpg', '2025-11-07 17:11:58', 'Jerome Malunes', '2025-11-08 01:15:36', '2025-12-08', NULL, NULL, NULL, 'uploads/images.jpg', 'uploads/633f1770-3587-4d69-99c3-a9871b0818b9.jpg', 'uploads/loan_approved_41_20251107181558.pdf')
ON DUPLICATE KEY UPDATE 
    full_name = VALUES(full_name),
    account_number = VALUES(account_number),
    contact_number = VALUES(contact_number),
    email = VALUES(email),
    job = VALUES(job),
    monthly_salary = VALUES(monthly_salary),
    user_email = VALUES(user_email),
    loan_type = VALUES(loan_type),
    loan_terms = VALUES(loan_terms),
    loan_amount = VALUES(loan_amount),
    purpose = VALUES(purpose),
    monthly_payment = VALUES(monthly_payment),
    due_date = VALUES(due_date),
    status = VALUES(status),
    remarks = VALUES(remarks),
    file_name = VALUES(file_name),
    approved_by = VALUES(approved_by),
    approved_at = VALUES(approved_at),
    next_payment_due = VALUES(next_payment_due),
    rejected_by = VALUES(rejected_by),
    rejected_at = VALUES(rejected_at),
    rejection_remarks = VALUES(rejection_remarks),
    proof_of_income = VALUES(proof_of_income),
    coe_document = VALUES(coe_document),
    pdf_path = VALUES(pdf_path);

-- ========================================
-- 11. LOAN PAYMENTS DATA
-- ========================================

INSERT IGNORE INTO loan_payments (loan_id, payment_date, amount, principal_amount, interest_amount, payment_reference, journal_entry_id, created_at) VALUES
-- Loan 1 (EMP001 - Salary Loan)
(1, '2024-02-01', 4500.00, 4000.00, 500.00, 'PAY-2024-02-001', NULL, '2024-02-01 10:00:00'),
(1, '2024-03-01', 4500.00, 4000.00, 500.00, 'PAY-2024-03-001', NULL, '2024-03-01 10:00:00'),
(1, '2024-04-01', 4500.00, 4000.00, 500.00, 'PAY-2024-04-001', NULL, '2024-04-01 10:00:00'),
(1, '2024-05-01', 4500.00, 4000.00, 500.00, 'PAY-2024-05-001', NULL, '2024-05-01 10:00:00'),
(1, '2024-06-01', 4500.00, 4000.00, 500.00, 'PAY-2024-06-001', NULL, '2024-06-01 10:00:00'),
(1, '2024-07-01', 4500.00, 4000.00, 500.00, 'PAY-2024-07-001', NULL, '2024-07-01 10:00:00'),
(1, '2024-08-01', 4500.00, 4000.00, 500.00, 'PAY-2024-08-001', NULL, '2024-08-01 10:00:00'),
(1, '2024-09-01', 4500.00, 4000.00, 500.00, 'PAY-2024-09-001', NULL, '2024-09-01 10:00:00'),
(1, '2024-10-01', 4500.00, 4000.00, 500.00, 'PAY-2024-10-001', NULL, '2024-10-01 10:00:00'),
(1, '2024-11-01', 4500.00, 4000.00, 500.00, 'PAY-2024-11-001', NULL, '2024-11-01 10:00:00'),

-- Loan 2 (EMP003 - Emergency Loan)
(2, '2024-02-15', 3600.00, 3000.00, 600.00, 'PAY-2024-02-002', NULL, '2024-02-15 10:00:00'),
(2, '2024-03-15', 3600.00, 3000.00, 600.00, 'PAY-2024-03-002', NULL, '2024-03-15 10:00:00'),
(2, '2024-04-15', 3600.00, 3000.00, 600.00, 'PAY-2024-04-002', NULL, '2024-04-15 10:00:00'),
(2, '2024-05-15', 3600.00, 3000.00, 600.00, 'PAY-2024-05-002', NULL, '2024-05-15 10:00:00'),

-- Loan 3 (EMP005 - Salary Loan)
(3, '2024-03-01', 2700.00, 2500.00, 200.00, 'PAY-2024-03-003', NULL, '2024-03-01 10:00:00'),
(3, '2024-04-01', 2700.00, 2500.00, 200.00, 'PAY-2024-04-003', NULL, '2024-04-01 10:00:00'),
(3, '2024-05-01', 2700.00, 2500.00, 200.00, 'PAY-2024-05-003', NULL, '2024-05-01 10:00:00'),
(3, '2024-06-01', 2700.00, 2500.00, 200.00, 'PAY-2024-06-003', NULL, '2024-06-01 10:00:00'),
(3, '2024-07-01', 2700.00, 2500.00, 200.00, 'PAY-2024-07-003', NULL, '2024-07-01 10:00:00'),
(3, '2024-08-01', 2700.00, 2500.00, 200.00, 'PAY-2024-08-003', NULL, '2024-08-01 10:00:00'),
(3, '2024-09-01', 2700.00, 2500.00, 200.00, 'PAY-2024-09-003', NULL, '2024-09-01 10:00:00'),
(3, '2024-10-01', 2700.00, 2500.00, 200.00, 'PAY-2024-10-003', NULL, '2024-10-01 10:00:00'),
(3, '2024-11-01', 2700.00, 2500.00, 200.00, 'PAY-2024-11-003', NULL, '2024-11-01 10:00:00'),
(3, '2024-12-01', 2700.00, 2500.00, 200.00, 'PAY-2024-12-003', NULL, '2024-12-01 10:00:00'),

-- Loan 4 (EMP007 - Housing Loan)
(4, '2024-02-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-02-003', NULL, '2024-02-01 10:00:00'),
(4, '2024-03-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-03-004', NULL, '2024-03-01 10:00:00'),
(4, '2024-04-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-04-004', NULL, '2024-04-01 10:00:00'),
(4, '2024-05-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-05-004', NULL, '2024-05-01 10:00:00'),
(4, '2024-06-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-06-004', NULL, '2024-06-01 10:00:00'),
(4, '2024-07-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-07-004', NULL, '2024-07-01 10:00:00'),
(4, '2024-08-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-08-004', NULL, '2024-08-01 10:00:00'),
(4, '2024-09-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-09-004', NULL, '2024-09-01 10:00:00'),
(4, '2024-10-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-10-004', NULL, '2024-10-01 10:00:00'),
(4, '2024-11-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-11-004', NULL, '2024-11-01 10:00:00'),
(4, '2024-12-01', 8000.00, 6000.00, 2000.00, 'PAY-2024-12-004', NULL, '2024-12-01 10:00:00'),

-- Additional recent payments
(15, '2024-12-01', 3150.00, 2800.00, 350.00, 'PAY-2024-12-001', NULL, '2024-12-01 10:00:00'),
(16, '2024-12-15', 1620.00, 1500.00, 120.00, 'PAY-2024-12-002', NULL, '2024-12-15 10:00:00'),
(17, '2024-12-01', 2520.00, 2300.00, 220.00, 'PAY-2024-12-003', NULL, '2024-12-01 10:00:00'),
(18, '2024-12-01', 4000.00, 3000.00, 1000.00, 'PAY-2024-12-004', NULL, '2024-12-01 10:00:00'),
(19, '2024-12-15', 1500.00, 1300.00, 200.00, 'PAY-2024-12-005', NULL, '2024-12-15 10:00:00'),

-- Additional loan payments from sample_loan_data.sql for LOAN-2024-001 through LOAN-2024-008
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-02-15', 5025.00, 3775.00, 1250.00, 'PAY-2024-001', NULL, '2024-02-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-03-15', 5025.00, 3815.00, 1210.00, 'PAY-2024-002', NULL, '2024-03-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-04-15', 5025.00, 3855.00, 1170.00, 'PAY-2024-003', NULL, '2024-04-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-05-15', 5025.00, 3895.00, 1130.00, 'PAY-2024-004', NULL, '2024-05-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-06-15', 5025.00, 3935.00, 1090.00, 'PAY-2024-005', NULL, '2024-06-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-001' LIMIT 1), '2024-07-15', 5025.00, 3975.00, 1050.00, 'PAY-2024-006', NULL, '2024-07-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-002' LIMIT 1), '2024-03-01', 12850.00, 2225.00, 10625.00, 'PAY-2024-010', NULL, '2024-03-01 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-002' LIMIT 1), '2024-04-01', 12850.00, 2241.00, 10609.00, 'PAY-2024-011', NULL, '2024-04-01 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-003' LIMIT 1), '2024-04-10', 10625.00, 6458.33, 4166.67, 'PAY-2024-020', NULL, '2024-04-10 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2023-12-15', 4500.00, 3875.00, 625.00, 'PAY-2023-100', NULL, '2023-12-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-01-15', 4500.00, 3924.00, 576.00, 'PAY-2024-101', NULL, '2024-01-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-02-15', 4500.00, 3974.00, 526.00, 'PAY-2024-102', NULL, '2024-02-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-03-15', 4500.00, 4024.00, 476.00, 'PAY-2024-103', NULL, '2024-03-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-04-15', 4500.00, 4075.00, 425.00, 'PAY-2024-104', NULL, '2024-04-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-05-15', 4500.00, 4126.00, 374.00, 'PAY-2024-105', NULL, '2024-05-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-06-15', 4500.00, 4177.00, 323.00, 'PAY-2024-106', NULL, '2024-06-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-07-15', 4500.00, 4229.00, 271.00, 'PAY-2024-107', NULL, '2024-07-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-08-15', 4500.00, 4281.00, 219.00, 'PAY-2024-108', NULL, '2024-08-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-09-15', 4500.00, 4334.00, 166.00, 'PAY-2024-109', NULL, '2024-09-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-10-15', 4500.00, 4387.00, 113.00, 'PAY-2024-110', NULL, '2024-10-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-004' LIMIT 1), '2024-11-15', 4113.00, 4050.00, 63.00, 'PAY-2024-111', NULL, '2024-11-15 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-005' LIMIT 1), '2024-05-01', 4850.00, 3683.33, 1166.67, 'PAY-2024-200', NULL, '2024-05-01 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-005' LIMIT 1), '2024-06-01', 4850.00, 3726.00, 1124.00, 'PAY-2024-201', NULL, '2024-06-01 10:00:00'),
((SELECT id FROM loans WHERE loan_no = 'LOAN-2024-005' LIMIT 1), '2024-07-01', 4850.00, 3769.00, 1081.00, 'PAY-2024-202', NULL, '2024-07-01 10:00:00')
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- ========================================
-- 12. COMPREHENSIVE EXPENSE CLAIMS
-- ========================================

INSERT IGNORE INTO expense_claims (claim_no, employee_external_no, expense_date, category_id, amount, description, status, approved_by, approved_at, payment_id, journal_entry_id, created_at) VALUES
-- January 2024 Expenses
('EXP001', 'EMP001', '2024-01-10', 1, 2500.00, 'Office supplies for Q1', 'approved', 1, '2024-01-11 09:00:00', NULL, NULL, '2024-01-10 09:00:00'),
('EXP002', 'EMP002', '2024-01-15', 2, 1500.00, 'Client meeting transportation', 'approved', 1, '2024-01-16 14:30:00', NULL, NULL, '2024-01-15 14:30:00'),
('EXP003', 'EMP003', '2024-01-20', 3, 800.00, 'Team lunch meeting', 'pending', NULL, NULL, NULL, NULL, '2024-01-20 12:00:00'),
('EXP004', 'EMP004', '2024-01-25', 1, 1200.00, 'Marketing materials', 'approved', 1, '2024-01-26 11:00:00', NULL, NULL, '2024-01-25 11:00:00'),
('EXP005', 'EMP005', '2024-01-30', 2, 800.00, 'Site visit transportation', 'approved', 1, '2024-01-31 15:30:00', NULL, NULL, '2024-01-30 15:30:00'),

-- February 2024 Expenses
('EXP006', 'EMP001', '2024-02-01', 4, 150.00, 'Internet bill reimbursement', 'approved', 1, '2024-02-02 10:15:00', NULL, NULL, '2024-02-01 10:15:00'),
('EXP007', 'EMP002', '2024-02-05', 5, 2500.00, 'Office rent payment', 'approved', 1, '2024-02-06 08:30:00', NULL, NULL, '2024-02-05 08:30:00'),
('EXP008', 'EMP003', '2024-02-10', 1, 350.00, 'Office supplies', 'rejected', 1, '2024-02-11 16:45:00', NULL, NULL, '2024-02-10 16:45:00'),
('EXP009', 'EMP004', '2024-02-15', 2, 2000.00, 'Sales conference travel', 'approved', 1, '2024-02-16 11:20:00', NULL, NULL, '2024-02-15 11:20:00'),
('EXP010', 'EMP005', '2024-02-20', 6, 1200.00, 'Payroll software training', 'approved', 1, '2024-02-21 13:10:00', NULL, NULL, '2024-02-20 13:10:00'),
('EXP011', 'EMP006', '2024-02-25', 3, 600.00, 'Customer service team lunch', 'approved', 1, '2024-02-26 12:30:00', NULL, NULL, '2024-02-25 12:30:00'),
('EXP012', 'EMP007', '2024-02-28', 2, 1500.00, 'Sales territory visit', 'approved', 1, '2024-02-29 14:00:00', NULL, NULL, '2024-02-28 14:00:00'),

-- March 2024 Expenses
('EXP013', 'EMP001', '2024-03-01', 7, 5000.00, 'New computer equipment', 'pending', NULL, NULL, NULL, NULL, '2024-03-01 09:30:00'),
('EXP014', 'EMP002', '2024-03-05', 3, 600.00, 'Marketing team dinner', 'approved', 1, '2024-03-06 18:00:00', NULL, NULL, '2024-03-05 18:00:00'),
('EXP015', 'EMP003', '2024-03-10', 1, 800.00, 'Development tools license', 'approved', 1, '2024-03-11 10:45:00', NULL, NULL, '2024-03-10 10:45:00'),
('EXP016', 'EMP004', '2024-03-15', 2, 1200.00, 'Marketing event travel', 'pending', NULL, NULL, NULL, NULL, '2024-03-15 16:20:00'),
('EXP017', 'EMP005', '2024-03-20', 4, 200.00, 'Utilities reimbursement', 'approved', 1, '2024-03-21 11:15:00', NULL, NULL, '2024-03-20 11:15:00'),
('EXP018', 'EMP006', '2024-03-25', 3, 400.00, 'Team building lunch', 'approved', 1, '2024-03-26 13:00:00', NULL, NULL, '2024-03-25 13:00:00'),
('EXP019', 'EMP007', '2024-03-30', 2, 1800.00, 'Client meeting travel', 'approved', 1, '2024-03-31 15:30:00', NULL, NULL, '2024-03-30 15:30:00'),
('EXP020', 'EMP008', '2024-03-31', 1, 300.00, 'Office supplies', 'approved', 1, '2024-04-01 09:00:00', NULL, NULL, '2024-03-31 09:00:00'),

-- Additional expenses for more variety
('EXP021', 'EMP009', '2024-01-12', 6, 1500.00, 'IT certification training', 'approved', 1, '2024-01-13 14:30:00', NULL, NULL, '2024-01-12 14:30:00'),
('EXP022', 'EMP010', '2024-02-08', 3, 500.00, 'Content creation team lunch', 'approved', 1, '2024-02-09 12:00:00', NULL, NULL, '2024-02-08 12:00:00'),
('EXP023', 'EMP001', '2024-03-12', 2, 900.00, 'HR conference attendance', 'approved', 1, '2024-03-13 08:30:00', NULL, NULL, '2024-03-12 08:30:00'),
('EXP024', 'EMP002', '2024-01-18', 1, 600.00, 'Accounting software license', 'approved', 1, '2024-01-19 10:00:00', NULL, NULL, '2024-01-18 10:00:00'),
('EXP025', 'EMP003', '2024-02-22', 7, 3000.00, 'Server maintenance tools', 'pending', NULL, NULL, NULL, NULL, '2024-02-22 16:45:00'),

-- Recent expenses (December 2024)
('EXP026', 'EMP001', '2024-12-01', 1, 1200.00, 'Office supplies for December', 'approved', 1, '2024-12-02 09:00:00', NULL, NULL, '2024-12-01 09:00:00'),
('EXP027', 'EMP003', '2024-12-05', 2, 2500.00, 'Client meeting travel', 'submitted', NULL, NULL, NULL, NULL, '2024-12-05 14:30:00'),
('EXP028', 'EMP005', '2024-12-08', 3, 800.00, 'Team dinner meeting', 'approved', 1, '2024-12-09 12:00:00', NULL, NULL, '2024-12-08 12:00:00'),
('EXP029', 'EMP007', '2024-12-10', 2, 1800.00, 'Sales conference attendance', 'pending', NULL, NULL, NULL, NULL, '2024-12-10 16:20:00'),
('EXP030', 'EMP009', '2024-12-12', 6, 2000.00, 'IT training certification', 'approved', 1, '2024-12-13 11:15:00', NULL, NULL, '2024-12-12 11:15:00'),
('EXP031', 'EMP002', '2024-12-15', 1, 600.00, 'Office supplies', 'draft', NULL, NULL, NULL, NULL, '2024-12-15 10:00:00'),
('EXP032', 'EMP004', '2024-12-18', 3, 450.00, 'Marketing team lunch', 'submitted', NULL, NULL, NULL, NULL, '2024-12-18 12:30:00'),
('EXP033', 'EMP006', '2024-12-20', 2, 1200.00, 'Customer service training', 'approved', 1, '2024-12-21 13:00:00', NULL, NULL, '2024-12-20 13:00:00'),

-- Additional expense tracking sample data
('EXP-2024-001', 'EMP001', '2024-01-15', (SELECT id FROM expense_categories WHERE code = 'TRAVEL'), 2500.00, 'Business trip to Manila for client meeting', 'approved', 1, '2024-01-16 14:20:00', NULL, NULL, '2024-01-15 09:30:00'),
('EXP-2024-002', 'EMP002', '2024-01-18', (SELECT id FROM expense_categories WHERE code = 'MEALS'), 850.00, 'Client dinner meeting at Makati restaurant', 'approved', 1, '2024-01-19 10:30:00', NULL, NULL, '2024-01-18 08:45:00'),
('EXP-2024-003', 'EMP003', '2024-01-20', (SELECT id FROM expense_categories WHERE code = 'OFFICE'), 1200.00, 'Office supplies and stationery', 'submitted', NULL, NULL, NULL, NULL, '2024-01-20 14:20:00'),
('EXP-2024-004', 'EMP001', '2024-01-22', (SELECT id FROM expense_categories WHERE code = 'COMM'), 450.00, 'Mobile phone bill for business calls', 'draft', NULL, NULL, NULL, NULL, '2024-01-22 11:15:00'),
('EXP-2024-005', 'EMP004', '2024-01-25', (SELECT id FROM expense_categories WHERE code = 'TRAINING'), 3500.00, 'Professional certification course', 'approved', 1, '2024-01-26 16:45:00', NULL, NULL, '2024-01-25 16:30:00'),
('EXP-2024-006', 'EMP002', '2024-01-28', (SELECT id FROM expense_categories WHERE code = 'TRAVEL'), 1800.00, 'Taxi fares for client visits', 'rejected', 1, '2024-01-29 09:15:00', NULL, NULL, '2024-01-28 13:20:00'),
('EXP-2024-007', 'EMP005', '2024-01-30', (SELECT id FROM expense_categories WHERE code = 'MEALS'), 650.00, 'Team lunch meeting', 'paid', 1, '2024-01-31 11:20:00', NULL, NULL, '2024-01-30 10:45:00'),
('EXP-2024-008', 'EMP003', '2024-02-02', (SELECT id FROM expense_categories WHERE code = 'OFFICE'), 950.00, 'Computer accessories and cables', 'submitted', NULL, NULL, NULL, NULL, '2024-02-02 09:00:00'),
('EXP-2024-009', 'EMP001', '2024-02-05', (SELECT id FROM expense_categories WHERE code = 'COMM'), 380.00, 'Internet service for home office', 'draft', NULL, NULL, NULL, NULL, '2024-02-05 10:30:00'),
('EXP-2024-010', 'EMP004', '2024-02-08', (SELECT id FROM expense_categories WHERE code = 'TRAVEL'), 3200.00, 'Conference attendance in Cebu', 'approved', 1, '2024-02-09 13:30:00', NULL, NULL, '2024-02-08 14:00:00')
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- ========================================
-- 13. COMPREHENSIVE PAYMENTS DATA
-- ========================================

INSERT IGNORE INTO payments (payment_no, payment_date, payment_type, from_bank_account_id, payee_name, amount, reference_no, memo, status, journal_entry_id, created_by, created_at) VALUES
-- January 2024 Salary Payments
('PAY001', '2024-01-31', 'bank_transfer', 2, 'Juan Carlos Santos', 20500.00, 'SAL-2024-01-001', 'January salary payment', 'completed', NULL, 1, '2024-01-31 10:00:00'),
('PAY002', '2024-01-31', 'bank_transfer', 2, 'Maria Elena Rodriguez', 23000.00, 'SAL-2024-01-002', 'January salary payment', 'completed', NULL, 1, '2024-01-31 10:00:00'),
('PAY003', '2024-01-31', 'bank_transfer', 2, 'Jose Miguel Cruz', 24500.00, 'SAL-2024-01-003', 'January salary payment', 'completed', NULL, 1, '2024-01-31 10:00:00'),
('PAY004', '2024-01-31', 'bank_transfer', 2, 'Ana Patricia Lopez', 18000.00, 'SAL-2024-01-004', 'January salary payment', 'completed', NULL, 1, '2024-01-31 10:00:00'),
('PAY005', '2024-01-31', 'bank_transfer', 2, 'Roberto Antonio Garcia', 26000.00, 'SAL-2024-01-005', 'January salary payment', 'completed', NULL, 1, '2024-01-31 10:00:00'),

-- February 2024 Salary Payments
('PAY011', '2024-02-29', 'bank_transfer', 2, 'Juan Carlos Santos', 20500.00, 'SAL-2024-02-001', 'February salary payment', 'completed', NULL, 1, '2024-02-29 10:00:00'),
('PAY012', '2024-02-29', 'bank_transfer', 2, 'Maria Elena Rodriguez', 23000.00, 'SAL-2024-02-002', 'February salary payment', 'completed', NULL, 1, '2024-02-29 10:00:00'),
('PAY013', '2024-02-29', 'bank_transfer', 2, 'Jose Miguel Cruz', 24500.00, 'SAL-2024-02-003', 'February salary payment', 'completed', NULL, 1, '2024-02-29 10:00:00'),
('PAY014', '2024-02-29', 'bank_transfer', 2, 'Ana Patricia Lopez', 18000.00, 'SAL-2024-02-004', 'February salary payment', 'completed', NULL, 1, '2024-02-29 10:00:00'),
('PAY015', '2024-02-29', 'bank_transfer', 2, 'Roberto Antonio Garcia', 26000.00, 'SAL-2024-02-005', 'February salary payment', 'completed', NULL, 1, '2024-02-29 10:00:00'),

-- March 2024 Salary Payments
('PAY021', '2024-03-15', 'bank_transfer', 2, 'Juan Carlos Santos', 20500.00, 'SAL-2024-03-001', 'March salary payment', 'completed', NULL, 1, '2024-03-15 10:00:00'),
('PAY022', '2024-03-15', 'bank_transfer', 2, 'Maria Elena Rodriguez', 23000.00, 'SAL-2024-03-002', 'March salary payment', 'completed', NULL, 1, '2024-03-15 10:00:00'),
('PAY023', '2024-03-15', 'bank_transfer', 2, 'Jose Miguel Cruz', 24500.00, 'SAL-2024-03-003', 'March salary payment', 'completed', NULL, 1, '2024-03-15 10:00:00'),
('PAY024', '2024-03-15', 'bank_transfer', 2, 'Ana Patricia Lopez', 18000.00, 'SAL-2024-03-004', 'March salary payment', 'completed', NULL, 1, '2024-03-15 10:00:00'),
('PAY025', '2024-03-15', 'bank_transfer', 2, 'Roberto Antonio Garcia', 26000.00, 'SAL-2024-03-005', 'March salary payment', 'completed', NULL, 1, '2024-03-15 10:00:00'),

-- Expense Payments
('PAY031', '2024-01-15', 'check', 1, 'Office Supplies Inc.', 2500.00, 'CHK-2024-001', 'Office supplies payment', 'completed', NULL, 1, '2024-01-15 14:30:00'),
('PAY032', '2024-02-05', 'bank_transfer', 1, 'Building Management', 2500.00, 'RENT-2024-02', 'Office rent payment', 'completed', NULL, 1, '2024-02-05 08:30:00'),
('PAY033', '2024-02-15', 'bank_transfer', 1, 'Travel Agency', 2000.00, 'TRAVEL-2024-001', 'Sales conference travel', 'completed', NULL, 1, '2024-02-15 11:20:00'),
('PAY034', '2024-03-01', 'bank_transfer', 1, 'Tech Solutions Inc.', 5000.00, 'EQUIP-2024-001', 'Computer equipment', 'pending', NULL, 1, '2024-03-01 09:30:00'),
('PAY035', '2024-01-20', 'bank_transfer', 1, 'Software License Co.', 800.00, 'LIC-2024-001', 'Development tools license', 'completed', NULL, 1, '2024-01-20 10:45:00'),

-- Additional recent payments
('PAY036', '2024-12-01', 'bank_transfer', 1, 'Office Equipment Supplier', 15000.00, 'EQUIP-2024-001', 'New office chairs', 'completed', NULL, 1, '2024-12-01 14:30:00'),
('PAY037', '2024-12-05', 'check', 1, 'Marketing Agency', 30000.00, 'MKT-2024-Q4', 'Q4 marketing campaign', 'completed', NULL, 1, '2024-12-05 11:20:00'),
('PAY038', '2024-12-10', 'bank_transfer', 2, 'Software License Co.', 12000.00, 'LIC-2024-001', 'Annual software licenses', 'completed', NULL, 1, '2024-12-10 10:45:00'),
('PAY039', '2024-12-15', 'cash', NULL, 'Office Maintenance', 5000.00, 'MAINT-2024-001', 'Office cleaning services', 'completed', NULL, 1, '2024-12-15 16:00:00'),
('PAY040', '2024-12-20', 'bank_transfer', 3, 'Insurance Provider', 25000.00, 'INS-2024-Q4', 'Quarterly insurance premium', 'pending', NULL, 1, '2024-12-20 09:30:00')
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- ========================================
-- 14. PAYROLL DATA
-- ========================================

-- Payroll Periods
INSERT IGNORE INTO payroll_periods (period_start, period_end, frequency, status, created_at) VALUES
('2024-01-01', '2024-01-31', 'monthly', 'paid', '2024-01-01 00:00:00'),
('2024-02-01', '2024-02-29', 'monthly', 'paid', '2024-02-01 00:00:00'),
('2024-03-01', '2024-03-31', 'monthly', 'paid', '2024-03-01 00:00:00'),
('2024-04-01', '2024-04-30', 'monthly', 'paid', '2024-04-01 00:00:00'),
('2024-05-01', '2024-05-31', 'monthly', 'paid', '2024-05-01 00:00:00'),
('2024-06-01', '2024-06-30', 'monthly', 'paid', '2024-06-01 00:00:00'),
('2024-07-01', '2024-07-31', 'monthly', 'paid', '2024-07-01 00:00:00'),
('2024-08-01', '2024-08-31', 'monthly', 'paid', '2024-08-01 00:00:00'),
('2024-09-01', '2024-09-30', 'monthly', 'paid', '2024-09-01 00:00:00'),
('2024-10-01', '2024-10-31', 'monthly', 'paid', '2024-10-01 00:00:00'),
('2024-11-01', '2024-11-30', 'monthly', 'paid', '2024-11-01 00:00:00'),
('2024-12-01', '2024-12-31', 'monthly', 'processing', '2024-12-01 00:00:00'),
('2025-01-01', '2025-01-31', 'monthly', 'open', '2025-01-01 00:00:00')
ON DUPLICATE KEY UPDATE period_start = VALUES(period_start);

-- Payroll Runs
INSERT IGNORE INTO payroll_runs (payroll_period_id, run_by_user_id, run_at, total_gross, total_deductions, total_net, status, journal_entry_id) VALUES
(1, 1, '2024-01-31 18:00:00', 250000.00, 45000.00, 205000.00, 'completed', NULL),
(2, 1, '2024-02-29 18:00:00', 255000.00, 46000.00, 209000.00, 'completed', NULL),
(3, 1, '2024-03-31 18:00:00', 260000.00, 47000.00, 213000.00, 'completed', NULL),
(4, 1, '2024-04-30 18:00:00', 265000.00, 48000.00, 217000.00, 'completed', NULL),
(5, 1, '2024-05-31 18:00:00', 270000.00, 49000.00, 221000.00, 'completed', NULL),
(6, 1, '2024-06-30 18:00:00', 275000.00, 50000.00, 225000.00, 'completed', NULL),
(7, 1, '2024-07-31 18:00:00', 280000.00, 51000.00, 229000.00, 'completed', NULL),
(8, 1, '2024-08-31 18:00:00', 285000.00, 52000.00, 233000.00, 'completed', NULL),
(9, 1, '2024-09-30 18:00:00', 290000.00, 53000.00, 237000.00, 'completed', NULL),
(10, 1, '2024-10-31 18:00:00', 295000.00, 54000.00, 241000.00, 'completed', NULL),
(11, 1, '2024-11-30 18:00:00', 300000.00, 55000.00, 245000.00, 'completed', NULL),
(12, 1, '2024-12-15 10:00:00', 305000.00, 56000.00, 249000.00, 'draft', NULL),
(13, 1, '2025-01-15 10:00:00', 310000.00, 57000.00, 253000.00, 'draft', NULL)
ON DUPLICATE KEY UPDATE total_gross = VALUES(total_gross);

-- Comprehensive Payslips for All Employees
INSERT IGNORE INTO payslips (payroll_run_id, employee_external_no, gross_pay, total_deductions, net_pay, payslip_json) VALUES
-- January 2024 Payslips
(1, 'EMP001', 25000.00, 4500.00, 20500.00, '{"basic_salary": 25000, "allowances": 2000, "deductions": 4500}'),
(1, 'EMP002', 28000.00, 5000.00, 23000.00, '{"basic_salary": 28000, "allowances": 2000, "deductions": 5000}'),
(1, 'EMP003', 30000.00, 5500.00, 24500.00, '{"basic_salary": 30000, "allowances": 2000, "deductions": 5500}'),
(1, 'EMP004', 22000.00, 4000.00, 18000.00, '{"basic_salary": 22000, "allowances": 2000, "deductions": 4000}'),
(1, 'EMP005', 32000.00, 6000.00, 26000.00, '{"basic_salary": 32000, "allowances": 2000, "deductions": 6000}'),
(1, 'EMP006', 18000.00, 3500.00, 14500.00, '{"basic_salary": 18000, "allowances": 1500, "deductions": 3500}'),
(1, 'EMP007', 26000.00, 4800.00, 21200.00, '{"basic_salary": 26000, "allowances": 2000, "deductions": 4800}'),
(1, 'EMP008', 24000.00, 4400.00, 19600.00, '{"basic_salary": 24000, "allowances": 2000, "deductions": 4400}'),
(1, 'EMP009', 29000.00, 5200.00, 23800.00, '{"basic_salary": 29000, "allowances": 2000, "deductions": 5200}'),
(1, 'EMP010', 15000.00, 2800.00, 12200.00, '{"basic_salary": 15000, "allowances": 1000, "deductions": 2800}'),

-- December 2024 Payslips (Current)
(12, 'EMP001', 25000.00, 4500.00, 20500.00, '{"basic_salary": 25000, "allowances": 2000, "deductions": 4500, "bonus": 2000}'),
(12, 'EMP002', 28000.00, 5000.00, 23000.00, '{"basic_salary": 28000, "allowances": 2000, "deductions": 5000, "bonus": 2000}'),
(12, 'EMP003', 30000.00, 5500.00, 25500.00, '{"basic_salary": 30000, "allowances": 2000, "deductions": 5500, "bonus": 2000}'),
(12, 'EMP004', 22000.00, 4000.00, 18900.00, '{"basic_salary": 22000, "allowances": 2000, "deductions": 4000, "bonus": 1500}'),
(12, 'EMP005', 32000.00, 6000.00, 27200.00, '{"basic_salary": 32000, "allowances": 2000, "deductions": 6000, "bonus": 2500}'),
(12, 'EMP006', 18000.00, 3500.00, 15600.00, '{"basic_salary": 18000, "allowances": 1500, "deductions": 3500, "bonus": 1000}'),
(12, 'EMP007', 26000.00, 4800.00, 22200.00, '{"basic_salary": 26000, "allowances": 2000, "deductions": 4800, "bonus": 2000}'),
(12, 'EMP008', 24000.00, 4400.00, 20600.00, '{"basic_salary": 24000, "allowances": 2000, "deductions": 4400, "bonus": 1500}'),
(12, 'EMP009', 29000.00, 5200.00, 24700.00, '{"basic_salary": 29000, "allowances": 2000, "deductions": 5200, "bonus": 2000}'),
(12, 'EMP010', 15000.00, 2800.00, 13200.00, '{"basic_salary": 15000, "allowances": 1000, "deductions": 2800, "bonus": 1000}')
ON DUPLICATE KEY UPDATE gross_pay = VALUES(gross_pay);

-- ========================================
-- 15. COMPLIANCE & AUDIT DATA
-- ========================================

-- Integration Logs
INSERT IGNORE INTO integration_logs (source_system, endpoint, request_type, payload, response, status, error_message, created_at) VALUES
('HRIS', '/api/employees/sync', 'POST', '{"action":"sync","date":"2024-12-01"}', '{"status":"success","records_processed":25}', 'success', NULL, '2024-12-01 08:00:00'),
('HRIS', '/api/payroll/export', 'GET', '{"period":"2024-12","format":"csv"}', '{"status":"success","file_path":"/exports/payroll_2024_12.csv"}', 'success', NULL, '2024-12-15 17:30:00'),
('BANK_API', '/api/transactions/sync', 'POST', '{"account":"BDO","date":"2024-12-15"}', '{"status":"success","transactions":50}', 'success', NULL, '2024-12-15 18:00:00'),
('TAX_SYSTEM', '/api/compliance/submit', 'POST', '{"report_type":"bir","period":"2024-Q4"}', '{"status":"error","code":"VALIDATION_FAILED"}', 'error', 'Missing required field: tax_id', '2024-12-15 19:00:00'),
('ACCOUNTING_SOFTWARE', '/api/journal/import', 'POST', '{"entries":20,"format":"json"}', '{"status":"success","imported":20}', 'success', NULL, '2024-12-15 20:00:00'),
('PAYMENT_GATEWAY', '/api/payments/process', 'POST', '{"amount":50000,"currency":"PHP"}', '{"status":"pending","transaction_id":"TXN123456"}', 'pending', NULL, '2024-12-15 21:00:00'),
('EXPENSE_SYSTEM', '/api/receipts/upload', 'POST', '{"employee_id":"EMP001","amount":2500}', '{"status":"success","receipt_id":"RCP789"}', 'success', NULL, '2024-12-15 22:00:00'),
('LOAN_SYSTEM', '/api/loans/calculate', 'POST', '{"principal":100000,"rate":0.05,"term":12}', '{"status":"success","monthly_payment":8560.75}', 'success', NULL, '2024-12-15 23:00:00'),
('BANK_API', '/api/balance/check', 'GET', '{"account":"BANK001"}', '{"status":"success","balance":2500000}', 'success', NULL, '2024-12-16 08:00:00'),
('HRIS', '/api/attendance/sync', 'POST', '{"date":"2024-12-16"}', '{"status":"success","records":25}', 'success', NULL, '2024-12-16 09:00:00')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Audit Logs
INSERT IGNORE INTO audit_logs (user_id, ip_address, action, object_type, object_id, old_values, new_values, additional_info, created_at) VALUES
(1, '192.168.1.100', 'Create Journal Entry', 'journal_entry', 'JE-2025-0001', NULL, '{"amount":10000000,"type":"capital"}', '{"module":"financial_reporting"}', NOW() - INTERVAL 30 DAY),
(1, '192.168.1.101', 'Process Payroll', 'payroll_run', 'PR-2024-12', NULL, '{"employees":25,"total_gross":305000}', '{"period":"2024-12"}', NOW() - INTERVAL 10 DAY),
(1, '192.168.1.102', 'Generate Compliance Report', 'compliance_report', 'CR-2024-Q4', NULL, '{"type":"gaap","score":95}', '{"period":"2024-Q4"}', NOW() - INTERVAL 5 DAY),
(1, '192.168.1.103', 'Approve Expense Claim', 'expense_claim', 'EXP026', '{"status":"submitted"}', '{"status":"approved","approved_by":1}', '{"amount":1200,"category":"office_supplies"}', NOW() - INTERVAL 3 DAY),
(1, '127.0.0.1', 'System Backup', 'system', 'backup_2024_12_15', NULL, '{"status":"completed","size":"5.2GB"}', '{"type":"full_backup"}', NOW() - INTERVAL 1 DAY),
(1, '192.168.1.100', 'Update Account Balance', 'account_balance', 'AB-1001-Q4', '{"balance":500000}', '{"balance":525000}', '{"adjustment":"monthly_interest"}', NOW() - INTERVAL 15 DAY),
(1, '192.168.1.101', 'Export Payroll Data', 'payroll_export', 'PE-2024-12', NULL, '{"format":"csv","records":25}', '{"period":"2024-12"}', NOW() - INTERVAL 12 DAY),
(1, '192.168.1.102', 'View Financial Report', 'financial_report', 'FR-BS-2024-Q4', NULL, NULL, '{"report_type":"balance_sheet","period":"2024-Q4"}', NOW() - INTERVAL 7 DAY),
(1, '127.0.0.1', 'Login', 'user_session', '1', NULL, '{"login_time":"2024-12-15 08:00:00"}', '{"ip":"127.0.0.1"}', NOW() - INTERVAL 20 DAY),
(1, '192.168.1.100', 'Login', 'user_session', '1', NULL, '{"login_time":"2024-12-15 08:30:00"}', '{"ip":"192.168.1.100"}', NOW() - INTERVAL 18 DAY),
(1, '192.168.1.101', 'Create Loan', 'loan', 'LN-1025', NULL, '{"principal":20000,"rate":0.05,"term":18}', '{"borrower":"EMP021","type":"appliance"}', NOW() - INTERVAL 25 DAY),
(1, '192.168.1.102', 'Process Loan Payment', 'loan_payment', 'PAY-2024-12-005', NULL, '{"amount":1500,"principal":1300,"interest":200}', '{"loan_id":19}', NOW() - INTERVAL 8 DAY),
(1, '192.168.1.103', 'Generate Financial Report', 'financial_report', 'FR-IS-2024-Q4', NULL, '{"report_type":"income_statement","period":"2024-Q4"}', '{"format":"pdf"}', NOW() - INTERVAL 5 DAY),

-- Audit logs for expense claims (from expense tracking module)
(1, '192.168.1.100', 'Created', 'expense_claim', '1', NULL, '{"claim_no":"EXP-2024-001","amount":"2500.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-15 09:30:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '1', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-15 10:15:00'),
(1, '192.168.1.101', 'Approved', 'expense_claim', '1', '{"status":"submitted"}', '{"status":"approved"}', '{"description":"Expense claim approved by manager"}', '2024-01-16 14:20:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '2', NULL, '{"claim_no":"EXP-2024-002","amount":"850.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-18 08:45:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '2', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-18 09:00:00'),
(1, '192.168.1.101', 'Approved', 'expense_claim', '2', '{"status":"submitted"}', '{"status":"approved"}', '{"description":"Expense claim approved by manager"}', '2024-01-19 10:30:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '3', NULL, '{"claim_no":"EXP-2024-003","amount":"1200.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-20 14:20:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '3', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-20 14:35:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '4', NULL, '{"claim_no":"EXP-2024-004","amount":"450.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-22 11:15:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '5', NULL, '{"claim_no":"EXP-2024-005","amount":"3500.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-25 16:30:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '5', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-25 16:45:00'),
(1, '192.168.1.101', 'Approved', 'expense_claim', '5', '{"status":"submitted"}', '{"status":"approved"}', '{"description":"Expense claim approved by manager"}', '2024-01-26 16:45:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '6', NULL, '{"claim_no":"EXP-2024-006","amount":"1800.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-28 13:20:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '6', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-28 13:35:00'),
(1, '192.168.1.101', 'Rejected', 'expense_claim', '6', '{"status":"submitted"}', '{"status":"rejected"}', '{"description":"Expense claim rejected - insufficient documentation"}', '2024-01-29 09:15:00'),
(1, '192.168.1.100', 'Created', 'expense_claim', '7', NULL, '{"claim_no":"EXP-2024-007","amount":"650.00","status":"draft"}', '{"description":"Expense claim created"}', '2024-01-30 10:45:00'),
(1, '192.168.1.100', 'Updated', 'expense_claim', '7', '{"status":"draft"}', '{"status":"submitted"}', '{"description":"Status changed from draft to submitted"}', '2024-01-30 11:00:00'),
(1, '192.168.1.101', 'Approved', 'expense_claim', '7', '{"status":"submitted"}', '{"status":"approved"}', '{"description":"Expense claim approved by manager"}', '2024-01-31 11:20:00'),
(1, '192.168.1.101', 'Paid', 'expense_claim', '7', '{"status":"approved"}', '{"status":"paid"}', '{"description":"Payment processed"}', '2024-01-31 15:30:00')
ON DUPLICATE KEY UPDATE action = VALUES(action);

-- Compliance Reports
INSERT IGNORE INTO compliance_reports (report_type, period_start, period_end, generated_date, generated_by, status, file_path, report_data, compliance_score, issues_found, created_at) VALUES
('gaap', '2024-10-01', '2024-12-31', NOW() - INTERVAL 25 DAY, 1, 'completed', '/reports/gaap_2024_q4.pdf', '{"total_assets":15000000,"total_liabilities":5000000,"net_income":2000000}', 98.50, 'Excellent compliance. All transactions properly documented.', NOW() - INTERVAL 25 DAY),
('sox', '2024-11-01', '2024-12-31', NOW() - INTERVAL 10 DAY, 1, 'completed', '/reports/sox_2024_q4.pdf', '{"segregation_score":95,"audit_trail":100,"controls":90}', 95.00, 'Strong internal controls. Minor improvement needed in approval workflows.', NOW() - INTERVAL 10 DAY),
('bir', '2024-12-01', '2024-12-31', NOW() - INTERVAL 5 DAY, 1, 'generating', NULL, NULL, NULL, NULL, NOW() - INTERVAL 5 DAY),
('ifrs', '2024-10-01', '2024-12-31', NOW() - INTERVAL 55 DAY, 1, 'completed', '/reports/ifrs_2024_q4.pdf', '{"revenue_recognition":100,"asset_classification":95,"disclosure":90}', 95.00, 'IFRS standards properly implemented. Consider enhancing disclosure notes.', NOW() - INTERVAL 55 DAY),
('gaap', '2024-12-01', '2024-12-31', NOW() - INTERVAL 3 DAY, 1, 'failed', NULL, NULL, 0.00, 'Unable to generate report due to incomplete journal entries for December.', NOW() - INTERVAL 3 DAY),
('sox', '2024-01-01', '2024-03-31', NOW() - INTERVAL 90 DAY, 1, 'completed', '/reports/sox_2024_q1.pdf', '{"segregation_score":88,"audit_trail":95,"controls":85}', 89.00, 'Good compliance. Some entries created and posted by same user.', NOW() - INTERVAL 90 DAY),
('bir', '2024-01-01', '2024-03-31', NOW() - INTERVAL 85 DAY, 1, 'completed', '/reports/bir_2024_q1.pdf', '{"documentation":95,"tax_calculations":100,"filing":90}', 95.00, 'Most transactions properly documented. Consider adding more detailed reference numbers.', NOW() - INTERVAL 85 DAY)
ON DUPLICATE KEY UPDATE compliance_score = VALUES(compliance_score);

-- ========================================
-- 16. ACCOUNT BALANCES CALCULATION
-- ========================================

-- Calculate account balances from existing journal entries
INSERT IGNORE INTO account_balances (account_id, fiscal_period_id, opening_balance, debit_movements, credit_movements, closing_balance, last_updated)
SELECT 
    a.id as account_id,
    fp.id as fiscal_period_id,
    0.00 as opening_balance,
    COALESCE(SUM(jl.debit), 0.00) as debit_movements,
    COALESCE(SUM(jl.credit), 0.00) as credit_movements,
    COALESCE(SUM(jl.debit), 0.00) - COALESCE(SUM(jl.credit), 0.00) as closing_balance,
    NOW() as last_updated
FROM accounts a
CROSS JOIN fiscal_periods fp
LEFT JOIN journal_lines jl ON a.id = jl.account_id
LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id AND je.fiscal_period_id = fp.id AND je.status = 'posted'
WHERE a.is_active = 1
GROUP BY a.id, fp.id
ON DUPLICATE KEY UPDATE 
    debit_movements = VALUES(debit_movements),
    credit_movements = VALUES(credit_movements),
    closing_balance = VALUES(closing_balance),
    last_updated = VALUES(last_updated);

-- ========================================
-- 17. VERIFICATION & SUMMARY QUERIES
-- ========================================

SELECT '=== COMPREHENSIVE DATA INSERTION COMPLETE ===' AS status;

-- Show record counts for all major tables
SELECT 'DATA SUMMARY' AS section, 'Record Counts' AS info;

SELECT 'Users:' AS table_name, COUNT(*) AS record_count FROM users
UNION ALL
SELECT 'Roles:', COUNT(*) FROM roles
UNION ALL
SELECT 'Account Types:', COUNT(*) FROM account_types
UNION ALL
SELECT 'Accounts:', COUNT(*) FROM accounts
UNION ALL
SELECT 'Journal Types:', COUNT(*) FROM journal_types
UNION ALL
SELECT 'Fiscal Periods:', COUNT(*) FROM fiscal_periods
UNION ALL
SELECT 'Employee References:', COUNT(*) FROM employee_refs
UNION ALL
SELECT 'Bank Accounts:', COUNT(*) FROM bank_accounts
UNION ALL
SELECT 'Salary Components:', COUNT(*) FROM salary_components
UNION ALL
SELECT 'Expense Categories:', COUNT(*) FROM expense_categories
UNION ALL
SELECT 'Loan Types:', COUNT(*) FROM loan_types
UNION ALL
SELECT 'Journal Entries:', COUNT(*) FROM journal_entries
UNION ALL
SELECT 'Journal Lines:', COUNT(*) FROM journal_lines
UNION ALL
SELECT 'Loans:', COUNT(*) FROM loans
UNION ALL
SELECT 'Loan Payments:', COUNT(*) FROM loan_payments
UNION ALL
SELECT 'Expense Claims:', COUNT(*) FROM expense_claims
UNION ALL
SELECT 'Payments:', COUNT(*) FROM payments
UNION ALL
SELECT 'Payroll Periods:', COUNT(*) FROM payroll_periods
UNION ALL
SELECT 'Payroll Runs:', COUNT(*) FROM payroll_runs
UNION ALL
SELECT 'Payslips:', COUNT(*) FROM payslips
UNION ALL
SELECT 'Integration Logs:', COUNT(*) FROM integration_logs
UNION ALL
SELECT 'Audit Logs:', COUNT(*) FROM audit_logs
UNION ALL
SELECT 'Compliance Reports:', COUNT(*) FROM compliance_reports
UNION ALL
SELECT 'Account Balances:', COUNT(*) FROM account_balances;

-- Verify account balances are calculated correctly
SELECT 
    'ACCOUNT BALANCE VERIFICATION' AS check_type,
    COUNT(*) as total_accounts,
    SUM(CASE WHEN closing_balance > 0 THEN 1 ELSE 0 END) as debit_balance_accounts,
    SUM(CASE WHEN closing_balance < 0 THEN 1 ELSE 0 END) as credit_balance_accounts,
    SUM(CASE WHEN closing_balance = 0 THEN 1 ELSE 0 END) as zero_balance_accounts
FROM account_balances;

-- Trial balance check
SELECT 
    'TRIAL BALANCE CHECK' AS check_type,
    SUM(debit_movements) as total_debits,
    SUM(credit_movements) as total_credits,
    SUM(debit_movements) - SUM(credit_movements) as difference,
    CASE 
        WHEN ABS(SUM(debit_movements) - SUM(credit_movements)) < 0.01 THEN 'BALANCED'
        ELSE 'UNBALANCED'
    END as status
FROM account_balances;

-- Check GAAP compliance (should show balanced books)
SELECT 
    'GAAP Compliance Check' as check_type,
    SUM(jl.debit) as total_debits,
    SUM(jl.credit) as total_credits,
    CASE 
        WHEN ABS(SUM(jl.debit) - SUM(jl.credit)) < 0.01 THEN 'BALANCED'
        ELSE 'UNBALANCED'
    END as status
FROM journal_lines jl
INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
WHERE je.status = 'posted';

-- Check SOX compliance (segregation of duties)
SELECT 
    'SOX Compliance Check' as check_type,
    COUNT(*) as total_entries,
    SUM(CASE WHEN created_by != posted_by THEN 1 ELSE 0 END) as segregated_entries,
    ROUND((SUM(CASE WHEN created_by != posted_by THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as segregation_percentage
FROM journal_entries
WHERE status = 'posted';

-- Check BIR compliance (documentation)
SELECT 
    'BIR Compliance Check' as check_type,
    COUNT(*) as total_entries,
    SUM(CASE WHEN reference_no IS NOT NULL AND reference_no != '' THEN 1 ELSE 0 END) as documented_entries,
    ROUND((SUM(CASE WHEN reference_no IS NOT NULL AND reference_no != '' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as documentation_percentage
FROM journal_entries
WHERE status = 'posted';

-- Financial Summary
SELECT 
    'FINANCIAL SUMMARY' AS section,
    'Total Assets' AS category,
    SUM(CASE WHEN at.category = 'asset' THEN ab.closing_balance ELSE 0 END) AS amount
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
JOIN account_types at ON a.type_id = at.id
WHERE ab.fiscal_period_id = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1' LIMIT 1)

UNION ALL

SELECT 
    'FINANCIAL SUMMARY',
    'Total Liabilities',
    SUM(CASE WHEN at.category = 'liability' THEN ab.closing_balance ELSE 0 END)
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
JOIN account_types at ON a.type_id = at.id
WHERE ab.fiscal_period_id = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1' LIMIT 1)

UNION ALL

SELECT 
    'FINANCIAL SUMMARY',
    'Total Equity',
    SUM(CASE WHEN at.category = 'equity' THEN ab.closing_balance ELSE 0 END)
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
JOIN account_types at ON a.type_id = at.id
WHERE ab.fiscal_period_id = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1' LIMIT 1)

UNION ALL

SELECT 
    'FINANCIAL SUMMARY',
    'Total Revenue',
    SUM(CASE WHEN at.category = 'revenue' THEN ab.closing_balance ELSE 0 END)
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
JOIN account_types at ON a.type_id = at.id
WHERE ab.fiscal_period_id = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1' LIMIT 1)

UNION ALL

SELECT 
    'FINANCIAL SUMMARY',
    'Total Expenses',
    SUM(CASE WHEN at.category = 'expense' THEN ab.closing_balance ELSE 0 END)
FROM account_balances ab
JOIN accounts a ON ab.account_id = a.id
JOIN account_types at ON a.type_id = at.id
WHERE ab.fiscal_period_id = (SELECT id FROM fiscal_periods WHERE period_name = 'FY2025-Q1' LIMIT 1);

-- Loan Portfolio Summary
SELECT 
    'LOAN PORTFOLIO SUMMARY' AS section,
    lt.name AS loan_type,
    COUNT(*) AS total_loans,
    SUM(l.principal_amount) AS total_principal,
    SUM(l.current_balance) AS total_outstanding,
    AVG(l.interest_rate * 100) AS avg_interest_rate
FROM loans l
JOIN loan_types lt ON l.loan_type_id = lt.id
GROUP BY lt.id, lt.name
ORDER BY total_principal DESC;

-- Employee Summary
SELECT 
    'EMPLOYEE SUMMARY' AS section,
    department,
    COUNT(*) AS employee_count,
    employment_type
FROM employee_refs
GROUP BY department, employment_type
ORDER BY department, employment_type;

-- Recent Activity Summary
SELECT 
    'RECENT ACTIVITY SUMMARY' AS section,
    'Journal Entries (Last 30 days)' AS activity,
    COUNT(*) AS count
FROM journal_entries
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)

UNION ALL

SELECT 
    'RECENT ACTIVITY SUMMARY',
    'Expense Claims (Last 30 days)',
    COUNT(*)
FROM expense_claims
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)

UNION ALL

SELECT 
    'RECENT ACTIVITY SUMMARY',
    'Loan Payments (Last 30 days)',
    COUNT(*)
FROM loan_payments
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)

UNION ALL

SELECT 
    'RECENT ACTIVITY SUMMARY',
    'Payments (Last 30 days)',
    COUNT(*)
FROM payments
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ========================================
-- HRIS-PAYROLL INTEGRATION VERIFICATION
-- ========================================
-- This query demonstrates the connection between HRIS and Payroll systems

SELECT 
    'HRIS-PAYROLL CONNECTION' AS verification_type,
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    d.department_name AS department,
    p.position_title AS position,
    er.external_employee_no,
    er.base_monthly_salary AS payroll_base_salary,
    COUNT(DISTINCT ps.id) AS payslip_count,
    SUM(ps.gross_pay) AS total_gross_paid
FROM employee e
LEFT JOIN department d ON e.department_id = d.department_id
LEFT JOIN `position` p ON e.position_id = p.position_id
LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
LEFT JOIN payslips ps ON ps.employee_external_no = er.external_employee_no
WHERE e.employment_status = 'Active'
GROUP BY e.employee_id, e.first_name, e.last_name, d.department_name, p.position_title, er.external_employee_no, er.base_monthly_salary
ORDER BY e.employee_id
LIMIT 10;

-- Verification: Check employee attendance linked to payroll
SELECT 
    'ATTENDANCE-PAYROLL LINK' AS verification_type,
    er.external_employee_no,
    er.name AS employee_name,
    COUNT(DISTINCT ea.attendance_date) AS attendance_days,
    SUM(ea.hours_worked) AS total_hours,
    SUM(ea.overtime_hours) AS total_overtime,
    COUNT(DISTINCT ps.id) AS payslips_generated
FROM employee_refs er
LEFT JOIN employee_attendance ea ON ea.employee_external_no = er.external_employee_no
LEFT JOIN payslips ps ON ps.employee_external_no = er.external_employee_no
WHERE er.employment_type = 'regular'
GROUP BY er.external_employee_no, er.name
ORDER BY er.external_employee_no
LIMIT 10;

-- Verification: Department and Position summary from HRIS
SELECT 
    'HRIS DEPARTMENT SUMMARY' AS summary_type,
    d.department_name,
    COUNT(DISTINCT e.employee_id) AS total_employees,
    COUNT(DISTINCT p.position_id) AS total_positions,
    SUM(er.base_monthly_salary) AS total_monthly_salary_budget
FROM department d
LEFT JOIN employee e ON e.department_id = d.department_id
LEFT JOIN `position` p ON p.position_id = e.position_id
LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
GROUP BY d.department_id, d.department_name
ORDER BY total_employees DESC;

SELECT '=== ALL DATA SUCCESSFULLY INSERTED ===' AS final_status;
SELECT '=== ACCOUNTING & FINANCE SYSTEM IS READY FOR TESTING ===' AS ready_status;

