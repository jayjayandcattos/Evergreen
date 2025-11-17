-- ========================================
-- FIX LEAVE ATTENDANCE DATA
-- ========================================
-- This script fixes leave_request status values and ensures
-- employees are properly set up for attendance/leave tracking
-- ========================================

USE BankingDB;

-- Step 1: Normalize ALL leave_request status values to 'Approved' (consistent case)
-- This fixes both 'approved' (lowercase) and 'Approved' (capitalized) to be consistent
UPDATE leave_request 
SET status = 'Approved' 
WHERE UPPER(TRIM(status)) = 'APPROVED';

-- Step 2: Ensure employees 22 and 3 are Active
UPDATE employee 
SET employment_status = 'Active' 
WHERE employee_id IN (22, 3) 
AND (employment_status IS NULL OR employment_status != 'Active');

-- Step 3: Ensure ALL active employees have proper employment_status
UPDATE employee 
SET employment_status = 'Active' 
WHERE employment_status IS NULL 
AND employee_id IN (SELECT DISTINCT employee_id FROM leave_request WHERE UPPER(TRIM(status)) = 'APPROVED');

-- Step 4: Ensure date fields are proper DATE type (remove any time components)
UPDATE leave_request 
SET start_date = DATE(start_date),
    end_date = DATE(end_date)
WHERE start_date IS NOT NULL AND end_date IS NOT NULL;

-- Step 5: Fix specific leave requests mentioned by user
-- Employee 22 (Mariana) - Leave Request ID 10: Nov 17-19, 2025
UPDATE leave_request 
SET status = 'Approved',
    start_date = '2025-11-17',
    end_date = '2025-11-19',
    total_days = 3
WHERE leave_request_id = 10 
AND employee_id = 22;

-- Employee 3 (Jose) - Leave Request ID 2: Nov 15-16, 2025  
UPDATE leave_request 
SET status = 'Approved',
    start_date = '2025-11-15',
    end_date = '2025-11-16',
    total_days = 2
WHERE leave_request_id = 2 
AND employee_id = 3;

-- Step 6: Add/update index for better query performance on leave_request
-- Check if index exists before dropping (safer approach)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = 'BankingDB' 
    AND TABLE_NAME = 'leave_request' 
    AND INDEX_NAME = 'idx_leave_status_date'
);

SET @sql = IF(@index_exists > 0,
    'DROP INDEX idx_leave_status_date ON leave_request',
    'SELECT "Index does not exist, will create new one" as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create the index
CREATE INDEX idx_leave_status_date ON leave_request(employee_id, status, start_date, end_date);

-- Step 6: Verify the data
SELECT 
    'VERIFICATION' as check_type,
    lr.leave_request_id,
    lr.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    e.employment_status,
    lr.status as leave_status,
    UPPER(TRIM(lr.status)) as normalized_status,
    lr.start_date,
    lr.end_date,
    lt.leave_name,
    CASE 
        WHEN e.employment_status = 'Active' AND UPPER(TRIM(lr.status)) = 'APPROVED' THEN 'OK'
        ELSE 'NEEDS FIX'
    END as status_check
FROM leave_request lr
INNER JOIN employee e ON lr.employee_id = e.employee_id
LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
WHERE lr.employee_id IN (22, 3)
ORDER BY lr.leave_request_id;

-- Step 8: Test query for Nov 17, 2025
SELECT 
    'TEST QUERY FOR 2025-11-17' as test_name,
    lr.leave_request_id,
    lr.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
    lr.start_date,
    lr.end_date,
    lr.status,
    lt.leave_name,
    CASE 
        WHEN CAST('2025-11-17' AS DATE) >= CAST(lr.start_date AS DATE) 
         AND CAST('2025-11-17' AS DATE) <= CAST(lr.end_date AS DATE) 
        THEN 'MATCHES'
        ELSE 'NO MATCH'
    END as date_match
FROM leave_request lr
INNER JOIN employee e ON lr.employee_id = e.employee_id
LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
WHERE lr.employee_id IN (22, 3)
AND e.employment_status = 'Active'
AND UPPER(TRIM(lr.status)) = 'APPROVED'
ORDER BY lr.leave_request_id;

SELECT '=== FIX COMPLETE ===' as status;

