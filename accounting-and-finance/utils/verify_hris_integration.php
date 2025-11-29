<?php
/**
 * HRIS-Accounting Integration Verification Script
 * 
 * This script verifies that all HRIS data is properly accessible
 * and integrated with the accounting-and-finance system.
 * 
 * Usage: Run from command line or via browser
 * php verify_hris_integration.php
 */

require_once __DIR__ . '/../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "HRIS-ACCOUNTING INTEGRATION VERIFICATION\n";
echo "========================================\n\n";

$issues = [];
$warnings = [];
$success_count = 0;

// Test 1: Verify database connection
echo "1. Testing database connection...\n";
if (isset($conn) && $conn) {
    echo "   ✓ Database connection successful\n";
    $success_count++;
} else {
    $issues[] = "Database connection failed";
    echo "   ✗ Database connection failed\n";
    exit(1);
}

// Test 2: Verify HRIS employee table exists and has data
echo "\n2. Verifying HRIS employee table...\n";
$employee_check = $conn->query("SELECT COUNT(*) as count FROM employee");
if ($employee_check) {
    $emp_count = $employee_check->fetch_assoc()['count'];
    if ($emp_count > 0) {
        echo "   ✓ Employee table exists with $emp_count employee(s)\n";
        $success_count++;
    } else {
        $warnings[] = "Employee table exists but has no records";
        echo "   ⚠ Employee table exists but has no records\n";
    }
} else {
    $issues[] = "Employee table does not exist or is not accessible";
    echo "   ✗ Employee table does not exist or is not accessible\n";
}

// Test 3: Verify employee_refs sync
echo "\n3. Verifying employee_refs synchronization...\n";
$sync_check = $conn->query("
    SELECT 
        COUNT(DISTINCT e.employee_id) as hris_employees,
        COUNT(DISTINCT er.external_employee_no) as ref_employees
    FROM employee e
    LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
    WHERE e.employment_status = 'Active'
");
if ($sync_check) {
    $sync_data = $sync_check->fetch_assoc();
    $hris_count = $sync_data['hris_employees'];
    $ref_count = $sync_data['ref_employees'];
    
    if ($ref_count == $hris_count) {
        echo "   ✓ All $hris_count active HRIS employees have employee_refs entries\n";
        $success_count++;
    } else {
        $missing = $hris_count - $ref_count;
        $warnings[] = "$missing active HRIS employee(s) missing from employee_refs";
        echo "   ⚠ $missing active HRIS employee(s) missing from employee_refs ($hris_count HRIS vs $ref_count refs)\n";
    }
} else {
    $issues[] = "Cannot verify employee_refs synchronization";
    echo "   ✗ Cannot verify employee_refs synchronization\n";
}

// Test 4: Verify attendance table access
echo "\n4. Verifying HRIS attendance table access...\n";
$attendance_check = $conn->query("SELECT COUNT(*) as count FROM attendance");
if ($attendance_check) {
    $att_count = $attendance_check->fetch_assoc()['count'];
    echo "   ✓ Attendance table accessible with $att_count record(s)\n";
    $success_count++;
    
    // Check recent attendance records
    $recent_att = $conn->query("
        SELECT COUNT(*) as count 
        FROM attendance 
        WHERE DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    if ($recent_att) {
        $recent_count = $recent_att->fetch_assoc()['count'];
        echo "      - $recent_count attendance record(s) in last 30 days\n";
    }
} else {
    $issues[] = "Attendance table does not exist or is not accessible";
    echo "   ✗ Attendance table does not exist or is not accessible\n";
}

// Test 5: Verify leave_request table access
echo "\n5. Verifying HRIS leave_request table access...\n";
$leave_check = $conn->query("SELECT COUNT(*) as count FROM leave_request");
if ($leave_check) {
    $leave_count = $leave_check->fetch_assoc()['count'];
    echo "   ✓ Leave_request table accessible with $leave_count record(s)\n";
    $success_count++;
    
    // Check approved leaves
    $approved_check = $conn->query("
        SELECT COUNT(*) as count 
        FROM leave_request 
        WHERE (
            UPPER(TRIM(status)) = 'APPROVED' 
            OR LOWER(TRIM(status)) = 'approved'
            OR TRIM(status) = 'Approved'
        )
    ");
    if ($approved_check) {
        $approved_count = $approved_check->fetch_assoc()['count'];
        echo "      - $approved_count approved leave request(s)\n";
        
        // Check recent approved leaves
        $recent_approved = $conn->query("
            SELECT COUNT(*) as count 
            FROM leave_request 
            WHERE (
                UPPER(TRIM(status)) = 'APPROVED' 
                OR LOWER(TRIM(status)) = 'approved'
                OR TRIM(status) = 'Approved'
            )
            AND start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        if ($recent_approved) {
            $recent_approved_count = $recent_approved->fetch_assoc()['count'];
            echo "      - $recent_approved_count approved leave(s) in last 30 days\n";
        }
    }
} else {
    $issues[] = "Leave_request table does not exist or is not accessible";
    echo "   ✗ Leave_request table does not exist or is not accessible\n";
}

// Test 6: Verify leave request status values
echo "\n6. Verifying leave request status values...\n";
$status_check = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM leave_request 
    GROUP BY status
");
if ($status_check) {
    echo "   ✓ Leave request status breakdown:\n";
    $has_approved = false;
    while ($row = $status_check->fetch_assoc()) {
        $status = trim($row['status']);
        $count = $row['count'];
        echo "      - '$status': $count request(s)\n";
        if (strtolower($status) === 'approved') {
            $has_approved = true;
        }
    }
    if ($has_approved) {
        $success_count++;
        echo "   ✓ Found approved leave requests\n";
    } else {
        $warnings[] = "No approved leave requests found (status may be case-sensitive)";
        echo "   ⚠ No approved leave requests found\n";
    }
} else {
    $issues[] = "Cannot check leave request status values";
    echo "   ✗ Cannot check leave request status values\n";
}

// Test 7: Test employee_id extraction from external_employee_no
echo "\n7. Testing employee_id extraction from external_employee_no...\n";
$test_employee = $conn->query("
    SELECT e.employee_id, CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as external_no
    FROM employee e
    WHERE e.employment_status = 'Active'
    LIMIT 1
");
if ($test_employee && $test_employee->num_rows > 0) {
    $test_data = $test_employee->fetch_assoc();
    $test_external = $test_data['external_no'];
    $test_id = $test_data['employee_id'];
    
    // Test extraction
    if (preg_match('/EMP(\d+)/i', $test_external, $matches)) {
        $extracted_id = intval($matches[1]);
        if ($extracted_id == $test_id) {
            echo "   ✓ Employee ID extraction working correctly (EMP-{$test_id} -> {$extracted_id})\n";
            $success_count++;
        } else {
            $issues[] = "Employee ID extraction mismatch";
            echo "   ✗ Employee ID extraction mismatch\n";
        }
    } else {
        $issues[] = "Employee ID extraction pattern not working";
        echo "   ✗ Employee ID extraction pattern not working\n";
    }
} else {
    $warnings[] = "No active employees found to test ID extraction";
    echo "   ⚠ No active employees found to test ID extraction\n";
}

// Test 8: Verify attendance data can be queried with employee_id
echo "\n8. Testing attendance data query with employee_id...\n";
$att_test = $conn->query("
    SELECT COUNT(*) as count
    FROM attendance a
    INNER JOIN employee e ON a.employee_id = e.employee_id
    WHERE e.employment_status = 'Active'
    AND DATE(a.date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    LIMIT 1
");
if ($att_test) {
    $att_test_count = $att_test->fetch_assoc()['count'];
    echo "   ✓ Attendance query with employee_id working ($att_test_count recent record(s))\n";
    $success_count++;
} else {
    $issues[] = "Cannot query attendance data with employee_id";
    echo "   ✗ Cannot query attendance data with employee_id\n";
}

// Test 9: Verify leave requests can be queried with employee_id
echo "\n9. Testing leave request query with employee_id...\n";
$leave_test = $conn->query("
    SELECT COUNT(*) as count
    FROM leave_request lr
    INNER JOIN employee e ON lr.employee_id = e.employee_id
    WHERE e.employment_status = 'Active'
    AND (
        UPPER(TRIM(lr.status)) = 'APPROVED' 
        OR LOWER(TRIM(lr.status)) = 'approved'
        OR TRIM(lr.status) = 'Approved'
    )
    AND lr.start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
if ($leave_test) {
    $leave_test_count = $leave_test->fetch_assoc()['count'];
    echo "   ✓ Leave request query with employee_id working ($leave_test_count recent approved leave(s))\n";
    $success_count++;
} else {
    $issues[] = "Cannot query leave requests with employee_id";
    echo "   ✗ Cannot query leave requests with employee_id\n";
}

// Test 10: Verify payroll calculation can access HRIS data
echo "\n10. Testing payroll calculation data access...\n";
if (file_exists(__DIR__ . '/../modules/api/payroll-calculation.php')) {
    require_once __DIR__ . '/../modules/api/payroll-calculation.php';
    
    // Get a test employee
    $test_emp_query = $conn->query("
        SELECT CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as external_no
        FROM employee e
        WHERE e.employment_status = 'Active'
        LIMIT 1
    ");
    
    if ($test_emp_query && $test_emp_query->num_rows > 0) {
        $test_emp = $test_emp_query->fetch_assoc()['external_no'];
        $test_start = date('Y-m-01'); // First day of current month
        $test_end = date('Y-m-15'); // 15th of current month
        
        try {
            $calc_result = calculatePayrollFromAttendance($conn, $test_emp, $test_start, $test_end);
            if ($calc_result && isset($calc_result['attendance_summary'])) {
                echo "   ✓ Payroll calculation function working\n";
                echo "      - Total days: {$calc_result['attendance_summary']['total_days']}\n";
                echo "      - Present days: {$calc_result['attendance_summary']['present_days']}\n";
                echo "      - Leave days: {$calc_result['attendance_summary']['leave_days']}\n";
                echo "      - Absent days: {$calc_result['attendance_summary']['absent_days']}\n";
                $success_count++;
            } else {
                $warnings[] = "Payroll calculation returned invalid result";
                echo "   ⚠ Payroll calculation returned invalid result\n";
            }
        } catch (Exception $e) {
            $issues[] = "Payroll calculation error: " . $e->getMessage();
            echo "   ✗ Payroll calculation error: " . $e->getMessage() . "\n";
        }
    } else {
        $warnings[] = "No active employees found to test payroll calculation";
        echo "   ⚠ No active employees found to test payroll calculation\n";
    }
} else {
    $issues[] = "Payroll calculation file not found";
    echo "   ✗ Payroll calculation file not found\n";
}

// Summary
echo "\n========================================\n";
echo "VERIFICATION SUMMARY\n";
echo "========================================\n";
echo "✓ Successful checks: $success_count\n";
echo "⚠ Warnings: " . count($warnings) . "\n";
echo "✗ Issues: " . count($issues) . "\n\n";

if (count($warnings) > 0) {
    echo "WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

if (count($issues) > 0) {
    echo "ISSUES (require attention):\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✓ All critical checks passed! HRIS-Accounting integration is working correctly.\n";
    exit(0);
}

