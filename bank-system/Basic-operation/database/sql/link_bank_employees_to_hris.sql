-- ============================================================================
-- EMPLOYEE TABLE INTEGRATION - LINK BANK_EMPLOYEES TO HRIS EMPLOYEE TABLE
-- ============================================================================
-- Purpose: Establish relationship between bank_employees (banking system login)
--          and employee table (HRIS system) to ensure data consistency
-- Date: November 29, 2025
-- ============================================================================

-- Step 1: Add hris_employee_id column to bank_employees
-- This column creates a reference to the main employee table in HRIS
ALTER TABLE bank_employees 
ADD COLUMN hris_employee_id INT NULL AFTER employee_id,
ADD CONSTRAINT fk_bank_emp_hris 
    FOREIGN KEY (hris_employee_id) 
    REFERENCES employee(employee_id) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- ============================================================================
-- Step 2: Link existing bank employees to HRIS records (if applicable)
-- ============================================================================

-- Example: If "John Doe" in bank_employees corresponds to an HRIS employee
-- UPDATE bank_employees 
-- SET hris_employee_id = (
--     SELECT employee_id FROM employee 
--     WHERE first_name = 'John' AND last_name = 'Doe' 
--     LIMIT 1
-- )
-- WHERE username = 'teller1';

-- ============================================================================
-- Step 3: Create view for unified employee data
-- ============================================================================

CREATE OR REPLACE VIEW v_bank_employee_details AS
SELECT 
    be.employee_id as bank_employee_id,
    be.username,
    be.role,
    be.is_active as bank_account_active,
    be.hris_employee_id,
    
    -- Use HRIS data if available, fallback to bank_employees data
    COALESCE(e.first_name, be.first_name) as first_name,
    COALESCE(e.last_name, be.last_name) as last_name,
    COALESCE(e.middle_name, '') as middle_name,
    COALESCE(e.email, be.email) as email,
    COALESCE(e.contact_number, '') as contact_number,
    COALESCE(e.address, '') as address,
    COALESCE(e.city, '') as city,
    COALESCE(e.province, '') as province,
    
    -- HRIS-specific fields
    e.gender,
    e.birth_date,
    e.hire_date,
    e.department_id,
    e.position_id,
    e.employment_status,
    
    be.created_at as bank_account_created,
    be.updated_at as bank_account_updated
FROM bank_employees be
LEFT JOIN employee e ON be.hris_employee_id = e.employee_id;

-- ============================================================================
-- Step 4: Sample queries for common operations
-- ============================================================================

-- Query 1: Get all bank employees with their HRIS details
-- SELECT * FROM v_bank_employee_details;

-- Query 2: Find bank employees without HRIS link
-- SELECT bank_employee_id, username, first_name, last_name 
-- FROM v_bank_employee_details 
-- WHERE hris_employee_id IS NULL;

-- Query 3: Find HRIS employees without bank access
-- SELECT employee_id, first_name, last_name, email, employment_status
-- FROM employee 
-- WHERE employee_id NOT IN (
--     SELECT hris_employee_id FROM bank_employees WHERE hris_employee_id IS NOT NULL
-- )
-- AND employment_status = 'Active';

-- ============================================================================
-- Step 5: Procedure to create bank access for HRIS employee
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE create_bank_access_for_hris_employee(
    IN p_hris_employee_id INT,
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_role ENUM('admin','teller','manager')
)
BEGIN
    DECLARE v_email VARCHAR(100);
    DECLARE v_first_name VARCHAR(50);
    DECLARE v_last_name VARCHAR(50);
    
    -- Get HRIS employee details
    SELECT email, first_name, last_name 
    INTO v_email, v_first_name, v_last_name
    FROM employee 
    WHERE employee_id = p_hris_employee_id;
    
    -- Check if HRIS employee exists
    IF v_email IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'HRIS employee not found';
    END IF;
    
    -- Check if username already exists
    IF EXISTS (SELECT 1 FROM bank_employees WHERE username = p_username) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Username already exists';
    END IF;
    
    -- Create bank_employees record linked to HRIS
    INSERT INTO bank_employees (
        hris_employee_id,
        username,
        password_hash,
        email,
        first_name,
        last_name,
        role,
        is_active,
        employee_name
    ) VALUES (
        p_hris_employee_id,
        p_username,
        p_password, -- Should be hashed before calling this procedure
        v_email,
        v_first_name,
        v_last_name,
        p_role,
        1,
        CONCAT(v_first_name, ' ', v_last_name)
    );
    
    SELECT LAST_INSERT_ID() as new_bank_employee_id;
END$$

DELIMITER ;

-- ============================================================================
-- USAGE EXAMPLES
-- ============================================================================

-- Example 1: Create bank access for HRIS employee #1 (Juan Santos)
/*
CALL create_bank_access_for_hris_employee(
    1,                              -- hris_employee_id
    'juan.santos',                  -- username
    '$2y$10$hashedpassword...',     -- password_hash (use password_hash() in PHP)
    'teller'                        -- role
);
*/

-- Example 2: Link existing bank employee to HRIS record
/*
UPDATE bank_employees 
SET hris_employee_id = 1 
WHERE username = 'admin';
*/

-- Example 3: View all employees with banking access
/*
SELECT 
    bank_employee_id,
    username,
    CONCAT(first_name, ' ', last_name) as full_name,
    email,
    role,
    employment_status,
    department_id,
    CASE 
        WHEN hris_employee_id IS NOT NULL THEN 'Linked to HRIS'
        ELSE 'Banking Only'
    END as employee_type
FROM v_bank_employee_details
WHERE bank_account_active = 1
ORDER BY last_name, first_name;
*/

-- ============================================================================
-- BENEFITS OF THIS INTEGRATION
-- ============================================================================
/*
1. Data Consistency: Employee information synced between HRIS and Banking
2. Single Source of Truth: HRIS employee table is authoritative
3. Flexibility: Bank-only accounts still supported (hris_employee_id can be NULL)
4. Audit Trail: Both systems maintain their own timestamps
5. Subsystem Independence: Other subsystems using 'employee' table unaffected
6. Easy Reporting: View provides unified data access
*/

-- ============================================================================
-- MIGRATION NOTES
-- ============================================================================
/*
1. The bank_employees table continues to work independently
2. Login authentication still uses bank_employees.username and password_hash
3. Employee data (name, email, etc.) pulled from HRIS when available
4. NULL hris_employee_id means banking-only account (no HRIS record)
5. Foreign key ON DELETE SET NULL preserves bank access if HRIS record deleted
6. API updated to use COALESCE for data priority (HRIS > bank_employees)
*/
