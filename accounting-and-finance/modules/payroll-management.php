<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once 'api/payroll-calculation.php';

requireLogin();
$current_user = getCurrentUser();

// Get filter parameters from URL
$selected_employee = isset($_GET['employee']) ? $_GET['employee'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$filter_position = isset($_GET['position']) ? $_GET['position'] : '';
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Get payroll period selection (1-15 or 16-end of month)
$payroll_period = isset($_GET['payroll_period']) ? $_GET['payroll_period'] : '';
$payroll_month = isset($_GET['payroll_month']) ? $_GET['payroll_month'] : date('Y-m');

// Calculate period start and end dates based on selection
$period_start = '';
$period_end = '';
$period_label = '';

// Always use the selected payroll_month (don't reset to current month)
$year = date('Y', strtotime($payroll_month . '-01'));
$month = date('m', strtotime($payroll_month . '-01'));
$last_day = date('t', strtotime($payroll_month . '-01'));

if ($payroll_period === 'first') {
    // First half: 1-15
    $period_start = sprintf('%04d-%02d-01', $year, $month);
    $period_end = sprintf('%04d-%02d-15', $year, $month);
    $period_label = date('M 1-15, Y', strtotime($period_start));
} elseif ($payroll_period === 'second') {
    // Second half: 16-end of month
    $period_start = sprintf('%04d-%02d-16', $year, $month);
    $period_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
    $period_label = date('M 16', strtotime($period_start)) . '-' . date('t, Y', strtotime($period_start));
} else {
    // Full Month (empty payroll_period) - use selected month, not current month
    $period_start = sprintf('%04d-%02d-01', $year, $month);
    $period_end = sprintf('%04d-%02d-%02d', $year, $month, $last_day);
    $period_label = date('F Y', strtotime($payroll_month . '-01'));
}

// Auto-sync: Create missing employee_refs records for HRIS employees
// This ensures new employees added in HRIS automatically appear in the dropdown
$sync_query = "INSERT INTO employee_refs (
                    external_employee_no,
                    name,
                    department,
                    position,
                    base_monthly_salary,
                    employment_type,
                    external_source,
                    created_at
                )
                SELECT 
                    CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as external_employee_no,
                    CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as name,
                    COALESCE(d.department_name, '') as department,
                    COALESCE(p.position_title, '') as position,
                    COALESCE(c.salary, 0.00) as base_monthly_salary,
                    COALESCE(c.contract_type, 'regular') as employment_type,
                    'HRIS' as external_source,
                    COALESCE(e.hire_date, CURDATE()) as created_at
                FROM employee e
                LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                LEFT JOIN department d ON e.department_id = d.department_id
                LEFT JOIN `position` p ON e.position_id = p.position_id
                LEFT JOIN contract c ON e.contract_id = c.contract_id
                WHERE e.employee_id IS NOT NULL
                AND er.id IS NULL
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    department = VALUES(department),
                    position = VALUES(position),
                    base_monthly_salary = VALUES(base_monthly_salary),
                    employment_type = VALUES(employment_type)";

// Execute sync query silently (errors are logged but don't break the page)
try {
    $conn->query($sync_query);
    error_log("Auto-sync: Checked and created missing employee_refs records");
} catch (Exception $e) {
    error_log("Auto-sync error: " . $e->getMessage());
}

// Build dynamic query for employees with filters - JOIN with HRIS employee table
// FIXED: Now shows ALL HRIS employees, automatically creating employee_refs if needed
$employees_query = "SELECT 
                        COALESCE(er.id, NULL) as ref_id,
                        COALESCE(er.external_employee_no, CONCAT('EMP', LPAD(e.employee_id, 3, '0'))) as external_employee_no,
                        COALESCE(er.employment_type, 'regular') as employment_type,
                        COALESCE(er.base_monthly_salary, 0) as base_monthly_salary,
                        COALESCE(er.created_at, e.hire_date) as created_at,
                        COALESCE(er.department, d.department_name) as department,
                        COALESCE(er.position, p.position_title) as position,
                        e.employee_id as hris_employee_id,
                        e.first_name as hris_first_name,
                        e.middle_name as hris_middle_name,
                        e.last_name as hris_last_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as hris_full_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as name,
                        e.gender,
                        e.birth_date,
                        e.contact_number,
                        e.email,
                        e.address,
                        e.hire_date,
                        e.employment_status as hris_employment_status,
                        d.department_name as hris_department_name,
                        p.position_title as hris_position_title,
                        c.contract_id,
                        c.contract_type,
                        c.salary as contract_salary,
                        c.start_date as contract_start_date,
                        c.end_date as contract_end_date,
                        c.benefits as contract_benefits
                    FROM employee e
                    LEFT JOIN employee_refs er ON (
                        er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                        OR e.employee_id = CAST(SUBSTRING(er.external_employee_no, 4) AS UNSIGNED)
                    )
                    LEFT JOIN department d ON e.department_id = d.department_id
                    LEFT JOIN `position` p ON e.position_id = p.position_id
                    LEFT JOIN contract c ON e.contract_id = c.contract_id
                    WHERE e.employee_id IS NOT NULL";
$params = [];
$types = "";

if (!empty($search_term)) {
    $employees_query .= " AND (
        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) LIKE ?
        OR e.first_name LIKE ?
        OR e.last_name LIKE ?
        OR CONCAT('EMP', LPAD(e.employee_id, 3, '0')) LIKE ?
        OR e.employee_id LIKE ?
    )";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sssss";
}

if (!empty($filter_position)) {
    $employees_query .= " AND (p.position_title = ? OR COALESCE(er.position, p.position_title) = ?)";
    $params[] = $filter_position;
    $params[] = $filter_position;
    $types .= "ss";
}

if (!empty($filter_department)) {
    $employees_query .= " AND (d.department_name = ? OR COALESCE(er.department, d.department_name) = ?)";
    $params[] = $filter_department;
    $params[] = $filter_department;
    $types .= "ss";
}

if (!empty($filter_type)) {
    $employees_query .= " AND COALESCE(er.employment_type, 'regular') = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$employees_query .= " ORDER BY e.employee_id ASC";

// Execute query with parameters
if (!empty($params)) {
    $stmt = $conn->prepare($employees_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $employees_result = $stmt->get_result();
} else {
    $employees_result = $conn->query($employees_query);
}

// Get unique values for filter dropdowns - FIXED: Now pulls from HRIS tables
$positions_query = "SELECT DISTINCT p.position_title as position 
                    FROM `position` p 
                    INNER JOIN employee e ON e.position_id = p.position_id 
                    WHERE p.position_title IS NOT NULL AND p.position_title != '' 
                    ORDER BY p.position_title";
$positions_result = $conn->query($positions_query);

$departments_query = "SELECT DISTINCT d.department_name as department 
                      FROM department d 
                      INNER JOIN employee e ON e.department_id = d.department_id 
                      WHERE d.department_name IS NOT NULL AND d.department_name != '' 
                      ORDER BY d.department_name";
$departments_result = $conn->query($departments_query);

$types_query = "SELECT DISTINCT COALESCE(er.employment_type, 'regular') as employment_type 
                FROM employee e 
                LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                ORDER BY employment_type";
$types_result = $conn->query($types_query);

// Get employee data for selected employee or first employee
// FIXED: Now pulls from HRIS employee table first, with employee_refs as optional supplement
if ($selected_employee) {
    // Extract employee_id from external_employee_no (EMP001 -> 1)
    $employee_id_from_external = null;
    if (preg_match('/EMP(\d+)/i', $selected_employee, $matches)) {
        $employee_id_from_external = intval($matches[1]);
    }
    
    $employee_query = "SELECT 
                        COALESCE(er.id, NULL) as ref_id,
                        CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as external_employee_no,
                        COALESCE(er.employment_type, 'regular') as employment_type,
                        COALESCE(er.base_monthly_salary, c.salary, 0) as base_monthly_salary,
                        COALESCE(er.created_at, e.hire_date) as created_at,
                        e.employee_id as hris_employee_id,
                        e.first_name as hris_first_name,
                        e.middle_name as hris_middle_name,
                        e.last_name as hris_last_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as hris_full_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as name,
                        e.first_name,
                        e.middle_name,
                        e.last_name,
                        e.gender,
                        e.birth_date,
                        e.contact_number,
                        e.email,
                        e.address,
                        e.hire_date,
                        e.employment_status as hris_employment_status,
                        e.employment_status,
                        d.department_name as hris_department_name,
                        d.department_name as department,
                        p.position_title as hris_position_title,
                        p.position_title as position,
                        c.contract_id,
                        c.contract_type,
                        c.salary as contract_salary,
                        c.start_date as contract_start_date,
                        c.end_date as contract_end_date,
                        c.benefits as contract_benefits
                    FROM employee e
                    LEFT JOIN employee_refs er ON (
                        er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                        OR e.employee_id = CAST(SUBSTRING(er.external_employee_no, 4) AS UNSIGNED)
                    )
                    LEFT JOIN department d ON e.department_id = d.department_id
                    LEFT JOIN `position` p ON e.position_id = p.position_id
                    LEFT JOIN contract c ON e.contract_id = c.contract_id
                    WHERE e.employee_id = ?";
    $stmt = $conn->prepare($employee_query);
    $stmt->bind_param("i", $employee_id_from_external);
    $stmt->execute();
    $employee_result = $stmt->get_result();
    $current_employee = $employee_result->fetch_assoc();
} else {
    $employee_result = $conn->query("SELECT 
                        COALESCE(er.id, NULL) as ref_id,
                        CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as external_employee_no,
                        COALESCE(er.employment_type, 'regular') as employment_type,
                        COALESCE(er.base_monthly_salary, c.salary, 0) as base_monthly_salary,
                        COALESCE(er.created_at, e.hire_date) as created_at,
                        e.employee_id as hris_employee_id,
                        e.first_name as hris_first_name,
                        e.middle_name as hris_middle_name,
                        e.last_name as hris_last_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as hris_full_name,
                        CONCAT(e.first_name, ' ', COALESCE(e.middle_name, ''), ' ', e.last_name) as name,
                        e.first_name,
                        e.middle_name,
                        e.last_name,
                        e.gender,
                        e.birth_date,
                        e.contact_number,
                        e.email,
                        e.address,
                        e.hire_date,
                        e.employment_status as hris_employment_status,
                        e.employment_status,
                        d.department_name as hris_department_name,
                        d.department_name as department,
                        p.position_title as hris_position_title,
                        p.position_title as position,
                        c.contract_id,
                        c.contract_type,
                        c.salary as contract_salary,
                        c.start_date as contract_start_date,
                        c.end_date as contract_end_date,
                        c.benefits as contract_benefits
                    FROM employee e
                    LEFT JOIN employee_refs er ON (
                        er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                        OR e.employee_id = CAST(SUBSTRING(er.external_employee_no, 4) AS UNSIGNED)
                    )
                    LEFT JOIN department d ON e.department_id = d.department_id
                    LEFT JOIN `position` p ON e.position_id = p.position_id
                    LEFT JOIN contract c ON e.contract_id = c.contract_id
                    ORDER BY e.first_name, e.last_name LIMIT 1");
    $current_employee = $employee_result->fetch_assoc();
    $selected_employee = $current_employee ? $current_employee['external_employee_no'] : '';
}

// Get employee base salary - prioritize HRIS contract salary, then employee_refs
$position_salary = 0;
if ($selected_employee && $current_employee) {
    // First try HRIS contract salary, then employee_refs base_monthly_salary
    if (!empty($current_employee['contract_salary']) && floatval($current_employee['contract_salary']) > 0) {
        $position_salary = floatval($current_employee['contract_salary']);
    } elseif (isset($current_employee['base_monthly_salary'])) {
        $position_salary = floatval($current_employee['base_monthly_salary']);
    }
}

// Fetch salary components for earnings
$earnings_query = "SELECT * FROM salary_components WHERE type = 'earning' AND is_active = 1 ORDER BY name";
$earnings_result = $conn->query($earnings_query);

// Fetch salary components for deductions
$deductions_query = "SELECT * FROM salary_components WHERE type = 'deduction' AND is_active = 1 ORDER BY name";
$deductions_result = $conn->query($deductions_query);

// Fetch salary components for tax
$tax_query = "SELECT * FROM salary_components WHERE type = 'tax' AND is_active = 1 ORDER BY name";
$tax_result = $conn->query($tax_query);

// Fetch salary components for employer contributions
$employer_contrib_query = "SELECT * FROM salary_components WHERE type = 'employer_contrib' AND is_active = 1 ORDER BY name";
$employer_contrib_result = $conn->query($employer_contrib_query);


// Fetch bank accounts for company info
$bank_accounts_query = "SELECT * FROM bank_accounts WHERE is_active = 1 LIMIT 1";
$bank_account_result = $conn->query($bank_accounts_query);
$company_bank = $bank_account_result->fetch_assoc();

// Calculate totals for payroll
$total_earnings = 0;
$total_deductions = 0;
$total_employer_contrib = 0;

// Calculate earnings total
if ($earnings_result) {
    $earnings_result->data_seek(0);
    while($earning = $earnings_result->fetch_assoc()) {
        $total_earnings += $earning['value'];
    }
}

// Calculate deductions total
if ($deductions_result) {
    $deductions_result->data_seek(0);
    while($deduction = $deductions_result->fetch_assoc()) {
        $total_deductions += $deduction['value'];
    }
}

// Calculate employer contributions total
if ($employer_contrib_result) {
    $employer_contrib_result->data_seek(0);
    while($contrib = $employer_contrib_result->fetch_assoc()) {
        $total_employer_contrib += $contrib['value'];
    }
}

// Get payslip data for selected employee and period
// Check if there's a saved payslip for the selected period (matching HRIS logic)
$payslip_data = null;
$has_saved_payslip_for_period = false;
if ($selected_employee && $period_start && $period_end) {
    // Check for payslip matching the selected period exactly
    $payslip_query = "SELECT ps.*, pr.run_at, pr.status as payroll_status, pp.period_start, pp.period_end
                      FROM payslips ps 
                      JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
                      JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
                      WHERE ps.employee_external_no = ?
                      AND pp.period_start = ?
                      AND pp.period_end = ?
                      LIMIT 1";
    $payslip_stmt = $conn->prepare($payslip_query);
    if ($payslip_stmt) {
        $payslip_stmt->bind_param("sss", $selected_employee, $period_start, $period_end);
        $payslip_stmt->execute();
        $payslip_result = $payslip_stmt->get_result();
        $payslip_data = $payslip_result->fetch_assoc();
        $has_saved_payslip_for_period = ($payslip_data !== null);
        $payslip_stmt->close();
    }
    
    // If no payslip found for exact period, get most recent for reference
    if (!$payslip_data) {
        $payslip_query = "SELECT ps.*, pr.run_at, pr.status as payroll_status 
                          FROM payslips ps 
                          JOIN payroll_runs pr ON ps.payroll_run_id = pr.id 
                          WHERE ps.employee_external_no = ? 
                          ORDER BY pr.run_at DESC 
                          LIMIT 1";
        $payslip_stmt = $conn->prepare($payslip_query);
        if ($payslip_stmt) {
            $payslip_stmt->bind_param("s", $selected_employee);
            $payslip_stmt->execute();
            $payslip_result = $payslip_stmt->get_result();
            $payslip_data = $payslip_result->fetch_assoc();
            $payslip_stmt->close();
        }
    }
}

// Get recent payslips for history
$recent_payslips_query = "SELECT ps.*, pr.run_at, pr.status as payroll_status 
                          FROM payslips ps 
                          JOIN payroll_runs pr ON ps.payroll_run_id = pr.id 
                          WHERE ps.employee_external_no = ? 
                          ORDER BY pr.run_at DESC 
                          LIMIT 5";
$recent_payslips_stmt = $conn->prepare($recent_payslips_query);
$recent_payslips_stmt->bind_param("s", $selected_employee);
$recent_payslips_stmt->execute();
$recent_payslips_result = $recent_payslips_stmt->get_result();

// Get attendance data for selected employee (current month)
$attendance_data = [];
$attendance_summary = [
    'total_days' => 0,
    'present_days' => 0,
    'absent_days' => 0,
    'late_days' => 0,
    'leave_days' => 0,
    'total_hours' => 0,
    'regular_hours' => 0,
    'overtime_hours' => 0
];

if ($selected_employee) {
    // Use the payroll_month for attendance display - this ensures proper month filtering
    $display_month = $payroll_month;
    
    // Get employee_id from external_employee_no (format: EMP001 -> 1, EMP002 -> 2, etc.)
    // Extract numeric part from external_employee_no
    $employee_id_from_external = null;
    
    // First, try to extract from external_employee_no format (EMP001, EMP002, etc.)
    if (preg_match('/EMP(\d+)/i', $selected_employee, $matches)) {
        $employee_id_from_external = intval($matches[1]);
        error_log("Extracted employee_id=$employee_id_from_external from external_employee_no=$selected_employee");
    } else {
        // Try direct match if it's already a number
        if (is_numeric($selected_employee)) {
            $employee_id_from_external = intval($selected_employee);
            error_log("Using direct numeric employee_id=$employee_id_from_external from selected_employee=$selected_employee");
        } else {
            // Fallback: Try to get employee_id from employee_refs or employee table
            $fallback_query = "SELECT e.employee_id 
                              FROM employee e 
                              LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                              WHERE er.external_employee_no = ? OR CONCAT('EMP', LPAD(e.employee_id, 3, '0')) = ?
                              LIMIT 1";
            $fallback_stmt = $conn->prepare($fallback_query);
            if ($fallback_stmt) {
                $fallback_stmt->bind_param("ss", $selected_employee, $selected_employee);
                $fallback_stmt->execute();
                $fallback_result = $fallback_stmt->get_result();
                if ($fallback_row = $fallback_result->fetch_assoc()) {
                    $employee_id_from_external = intval($fallback_row['employee_id']);
                    error_log("Fallback: Found employee_id=$employee_id_from_external for external_employee_no=$selected_employee");
                }
                $fallback_stmt->close();
            }
        }
    }
    
    // Final validation: ensure we have a valid employee_id
    if (!$employee_id_from_external || $employee_id_from_external <= 0) {
        error_log("ERROR: Could not extract valid employee_id from selected_employee=$selected_employee");
        // Try one more time with a direct database lookup
        $direct_lookup = "SELECT e.employee_id 
                        FROM employee e 
                        WHERE CONCAT('EMP', LPAD(e.employee_id, 3, '0')) = ?
                        LIMIT 1";
        $lookup_stmt = $conn->prepare($direct_lookup);
        if ($lookup_stmt) {
            $lookup_stmt->bind_param("s", $selected_employee);
            $lookup_stmt->execute();
            $lookup_result = $lookup_stmt->get_result();
            if ($lookup_row = $lookup_result->fetch_assoc()) {
                $employee_id_from_external = intval($lookup_row['employee_id']);
                error_log("Direct lookup successful: Found employee_id=$employee_id_from_external for $selected_employee");
            }
            $lookup_stmt->close();
        }
    }
    
    // Build attendance query to read from BOTH HRIS attendance AND employee_attendance tables
    // This combines data from both sources using UNION ALL
    if ($payroll_period === 'first' || $payroll_period === 'second') {
        // Use period dates for filtering - SPECIFIC PERIOD SELECTED (1-15 or 16-end)
        $attendance_query = "SELECT * FROM (
                                -- From HRIS attendance table (uses employee_id)
                                SELECT 
                                    DATE(a.date) as date,
                                    TIME(a.time_in) as time_in,
                                    TIME(a.time_out) as time_out,
                                    CASE 
                                        WHEN LOWER(a.status) = 'present' THEN 'present'
                                        WHEN LOWER(a.status) = 'absent' THEN 'absent'
                                        WHEN LOWER(a.status) = 'late' THEN 'late'
                                        WHEN LOWER(a.status) = 'leave' THEN 'leave'
                                        WHEN LOWER(a.status) LIKE '%half%' OR LOWER(a.status) LIKE '%half_day%' THEN 'half_day'
                                        ELSE 'present'
                                    END as status,
                                    COALESCE(a.total_hours, 
                                        CASE 
                                            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, a.time_out) + (TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) % 60) / 60.0
                                            WHEN a.time_in IS NOT NULL AND DATE(a.date) < CURDATE()
                                            THEN 8.00
                                            WHEN a.time_in IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, NOW()) + (TIMESTAMPDIFF(MINUTE, a.time_in, NOW()) % 60) / 60.0
                                            ELSE 0.00
                                        END
                                    ) as hours_worked,
                                    0.00 as overtime_hours,
                                    CASE 
                                        WHEN TIME(a.time_in) > '08:00:00' AND TIME(a.time_in) <= '09:00:00'
                                        THEN TIMESTAMPDIFF(MINUTE, '08:00:00', TIME(a.time_in))
                                        ELSE 0
                                    END as late_minutes,
                                    COALESCE(a.remarks, '') as remarks,
                                    'hris' as source
                                FROM attendance a
                                WHERE a.employee_id = ? 
                                AND DATE(a.date) BETWEEN ? AND ?
                                
                                UNION ALL
                                
                                -- From employee_attendance table (uses employee_external_no)
                                SELECT 
                                    ea.attendance_date as date,
                                    ea.time_in,
                                    ea.time_out,
                                    CASE 
                                        WHEN LOWER(ea.status) = 'present' THEN 'present'
                                        WHEN LOWER(ea.status) = 'absent' THEN 'absent'
                                        WHEN LOWER(ea.status) = 'late' THEN 'late'
                                        WHEN LOWER(ea.status) = 'leave' THEN 'leave'
                                        WHEN LOWER(ea.status) LIKE '%half%' OR LOWER(ea.status) LIKE '%half_day%' THEN 'half_day'
                                        ELSE 'present'
                                    END as status,
                                    COALESCE(ea.hours_worked, 0.00) as hours_worked,
                                    COALESCE(ea.overtime_hours, 0.00) as overtime_hours,
                                    COALESCE(ea.late_minutes, 0) as late_minutes,
                                    COALESCE(ea.remarks, '') as remarks,
                                    'accounting' as source
                                FROM employee_attendance ea
                                WHERE ea.employee_external_no = ?
                                AND ea.attendance_date BETWEEN ? AND ?
                            ) combined_attendance
                            ORDER BY date DESC";
    } else {
        // Fallback to FULL MONTH filtering - shows all records for the month
        $attendance_query = "SELECT * FROM (
                                -- From HRIS attendance table (uses employee_id)
                                SELECT 
                                    DATE(a.date) as date,
                                    TIME(a.time_in) as time_in,
                                    TIME(a.time_out) as time_out,
                                    CASE 
                                        WHEN LOWER(a.status) = 'present' THEN 'present'
                                        WHEN LOWER(a.status) = 'absent' THEN 'absent'
                                        WHEN LOWER(a.status) = 'late' THEN 'late'
                                        WHEN LOWER(a.status) = 'leave' THEN 'leave'
                                        WHEN LOWER(a.status) LIKE '%half%' OR LOWER(a.status) LIKE '%half_day%' THEN 'half_day'
                                        ELSE 'present'
                                    END as status,
                                    COALESCE(a.total_hours, 
                                        CASE 
                                            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, a.time_out) + (TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) % 60) / 60.0
                                            WHEN a.time_in IS NOT NULL AND DATE(a.date) < CURDATE()
                                            THEN 8.00
                                            WHEN a.time_in IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, NOW()) + (TIMESTAMPDIFF(MINUTE, a.time_in, NOW()) % 60) / 60.0
                                            ELSE 0.00
                                        END
                                    ) as hours_worked,
                                    0.00 as overtime_hours,
                                    CASE 
                                        WHEN TIME(a.time_in) > '08:00:00' AND TIME(a.time_in) <= '09:00:00'
                                        THEN TIMESTAMPDIFF(MINUTE, '08:00:00', TIME(a.time_in))
                                        ELSE 0
                                    END as late_minutes,
                                    COALESCE(a.remarks, '') as remarks,
                                    'hris' as source
                                FROM attendance a
                                WHERE a.employee_id = ? 
                                AND DATE_FORMAT(a.date, '%Y-%m') = ?
                                
                                UNION ALL
                                
                                -- From employee_attendance table (uses employee_external_no)
                                SELECT 
                                    ea.attendance_date as date,
                                    ea.time_in,
                                    ea.time_out,
                                    CASE 
                                        WHEN LOWER(ea.status) = 'present' THEN 'present'
                                        WHEN LOWER(ea.status) = 'absent' THEN 'absent'
                                        WHEN LOWER(ea.status) = 'late' THEN 'late'
                                        WHEN LOWER(ea.status) = 'leave' THEN 'leave'
                                        WHEN LOWER(ea.status) LIKE '%half%' OR LOWER(ea.status) LIKE '%half_day%' THEN 'half_day'
                                        ELSE 'present'
                                    END as status,
                                    COALESCE(ea.hours_worked, 0.00) as hours_worked,
                                    COALESCE(ea.overtime_hours, 0.00) as overtime_hours,
                                    COALESCE(ea.late_minutes, 0) as late_minutes,
                                    COALESCE(ea.remarks, '') as remarks,
                                    'accounting' as source
                                FROM employee_attendance ea
                                WHERE ea.employee_external_no = ?
                                AND DATE_FORMAT(ea.attendance_date, '%Y-%m') = ?
                            ) combined_attendance
                            ORDER BY date DESC";
    }
    
    if ($employee_id_from_external) {
        $attendance_stmt = $conn->prepare($attendance_query);
        
        if (!$attendance_stmt) {
            error_log("PREPARE FAILED: " . $conn->error);
            error_log("Query: " . substr($attendance_query, 0, 500));
        } else {
            if ($payroll_period === 'first' || $payroll_period === 'second') {
                // Bind parameters: employee_id (for HRIS), period dates, employee_external_no (for accounting), period dates
                // Query has 6 placeholders total:
                // 1. a.employee_id = ? (integer)
                // 2. DATE(a.date) BETWEEN ? AND ? (2 strings: period_start, period_end)
                // 3. ea.employee_external_no = ? (string)
                // 4. ea.attendance_date BETWEEN ? AND ? (2 strings: period_start, period_end)
                // Total: 1 integer + 5 strings = "isssss"
                $attendance_stmt->bind_param("isssss", 
                    $employee_id_from_external, $period_start, $period_end,  // For HRIS attendance table (i, s, s)
                    $selected_employee, $period_start, $period_end           // For employee_attendance table (s, s, s)
                );
                error_log("Fetching attendance for employee_id=$employee_id_from_external / external_no=$selected_employee, period=$period_start to $period_end");
            } else {
                // Bind parameters: employee_id (for HRIS), month, employee_external_no (for accounting), month
                $attendance_stmt->bind_param("isss", 
                    $employee_id_from_external, $display_month,  // For HRIS attendance table
                    $selected_employee, $display_month          // For employee_attendance table
                );
                error_log("Fetching attendance for employee_id=$employee_id_from_external / external_no=$selected_employee, month=$display_month");
            }
            
            if (!$attendance_stmt->execute()) {
                error_log("EXECUTE FAILED: " . $attendance_stmt->error);
            }
            
            $attendance_result = $attendance_stmt->get_result();
            
            if (!$attendance_result) {
                error_log("GET_RESULT FAILED: " . $attendance_stmt->error);
            } else {
                $record_count = 0;
                while ($row = $attendance_result->fetch_assoc()) {
                    // Overtime is already calculated in employee_attendance, but recalculate for HRIS records
                    if ($row['source'] === 'hris' && $row['hours_worked'] > 8.0) {
                        $row['overtime_hours'] = $row['hours_worked'] - 8.0;
                        $row['hours_worked'] = 8.0; // Regular hours capped at 8
                    }
                    $attendance_data[] = $row;
                    $record_count++;
                }
                error_log("Found $record_count attendance records from both sources (HRIS + Accounting)");
            }
        }
            } else {
        error_log("Could not extract employee_id from: $selected_employee");
    }
    
    // Fetch leave requests from HRIS and merge with attendance data
    // FIXED: Now properly fetches approved leaves from HRIS
    if ($employee_id_from_external && $employee_id_from_external > 0) {
        // Get approved leave requests for the selected period
        $leave_query = "SELECT 
                            lr.leave_request_id,
                            lr.start_date,
                            lr.end_date,
                            lr.total_days,
                            lr.reason,
                            lt.leave_name,
                            lt.paid_unpaid,
                            lr.status
                        FROM leave_request lr
                        LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
                        WHERE lr.employee_id = ?
                        AND (UPPER(TRIM(lr.status)) = 'APPROVED' OR LOWER(TRIM(lr.status)) = 'approved')";
        
        error_log("Fetching leave requests for employee_id=$employee_id_from_external (external_no=$selected_employee)");
        
        $leave_params = [];
        $leave_types = "";
        
        if ($payroll_period === 'first' || $payroll_period === 'second') {
            // For period-based: check if leave overlaps with period
            // Improved overlap logic: leave overlaps if it starts before period ends AND ends after period starts
            $leave_query .= " AND (
                                (lr.start_date <= ? AND lr.end_date >= ?)
                            )";
            $leave_params = [$employee_id_from_external, $period_end, $period_start];
            $leave_types = "iss"; // 1 integer + 2 strings = 3 parameters
        } else {
            // For full month: check if leave overlaps with month
            // Improved logic: leave overlaps if it starts before month ends AND ends after month starts
            $month_start = $display_month . '-01';
            $month_end = date('Y-m-t', strtotime($display_month . '-01'));
            $leave_query .= " AND (
                                (lr.start_date <= ? AND lr.end_date >= ?)
                            )";
            $leave_params = [$employee_id_from_external, $month_end, $month_start];
            $leave_types = "iss";
        }
        
        $leave_stmt = $conn->prepare($leave_query);
        if ($leave_stmt) {
            // Log the query parameters for debugging
            if ($payroll_period === 'first' || $payroll_period === 'second') {
                error_log("Leave query - Period: $period_start to $period_end, Employee ID: $employee_id_from_external");
            } else {
                error_log("Leave query - Month: $display_month, Employee ID: $employee_id_from_external");
            }
            
            $leave_stmt->bind_param($leave_types, ...$leave_params);
            if (!$leave_stmt->execute()) {
                error_log("Leave query execution failed: " . $leave_stmt->error);
                error_log("Leave query: " . substr($leave_query, 0, 500));
                error_log("Leave params: " . print_r($leave_params, true));
            }
            $leave_result = $leave_stmt->get_result();
            
            if (!$leave_result) {
                error_log("Leave query get_result failed: " . $leave_stmt->error);
            } else {
                $leave_count = $leave_result->num_rows;
                error_log("Found $leave_count leave request(s) for employee_id=$employee_id_from_external");
                
                // Log each leave found for debugging
                if ($leave_count > 0) {
                    $leave_result->data_seek(0);
                    while ($leave_row = $leave_result->fetch_assoc()) {
                        error_log("Leave found: ID={$leave_row['leave_request_id']}, Start={$leave_row['start_date']}, End={$leave_row['end_date']}, Status={$leave_row['status']}");
                    }
                    $leave_result->data_seek(0); // Reset pointer
                }
            }
            
            // Create a map of attendance dates to avoid duplicates
            $attendance_dates = [];
            foreach ($attendance_data as $att_record) {
                $attendance_dates[date('Y-m-d', strtotime($att_record['date']))] = true;
            }
            
            // Add leave days to attendance data
            while ($leave = $leave_result->fetch_assoc()) {
                $start_date = new DateTime($leave['start_date']);
                $end_date = new DateTime($leave['end_date']);
                $leave_name = $leave['leave_name'] ?? 'Approved Leave';
                $leave_reason = $leave['reason'] ?? '';
                
                // Generate all dates in the leave range
                $current_date = clone $start_date;
                while ($current_date <= $end_date) {
                    $date_str = $current_date->format('Y-m-d');
                    
                    // Check if this date is within the selected period
                    $include_date = false;
                    if ($payroll_period === 'first' || $payroll_period === 'second') {
                        $date_check = $current_date->format('Y-m-d');
                        if ($date_check >= $period_start && $date_check <= $period_end) {
                            $include_date = true;
                        }
                    } else {
                        $date_check = $current_date->format('Y-m');
                        if ($date_check === $display_month) {
                            $include_date = true;
                        }
                    }
                    
                    // Only add if date is in period and not already in attendance data
                    if ($include_date && !isset($attendance_dates[$date_str])) {
                        $attendance_data[] = [
                            'date' => $date_str,
                            'time_in' => null,
                            'time_out' => null,
                            'status' => 'leave',
                            'hours_worked' => 0.00,
                            'overtime_hours' => 0.00,
                            'late_minutes' => 0,
                            'remarks' => "Leave: $leave_name - $leave_reason",
                            'source' => 'hris_leave'
                        ];
                        $attendance_dates[$date_str] = true;
                    }
                    
                    $current_date->modify('+1 day');
                }
            }
            
            // Sort attendance data by date DESC (newest first)
            usort($attendance_data, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            $leave_stmt->close();
        }
    }
    
    // Calculate summary from attendance data for display (will be overridden by API if available)
    foreach ($attendance_data as $row) {
        $attendance_summary['total_days']++;
        $attendance_summary['total_hours'] += $row['hours_worked'];
        $attendance_summary['overtime_hours'] += $row['overtime_hours'];
        
        switch ($row['status']) {
            case 'present':
                $attendance_summary['present_days']++;
                $attendance_summary['regular_hours'] += $row['hours_worked'];
                break;
            case 'late':
                $attendance_summary['late_days']++;
                $attendance_summary['present_days']++;
                $attendance_summary['regular_hours'] += $row['hours_worked'];
                break;
            case 'absent':
                $attendance_summary['absent_days']++;
                break;
            case 'leave':
                $attendance_summary['leave_days']++;
                break;
            case 'half_day':
                $attendance_summary['present_days']++;
                $attendance_summary['regular_hours'] += $row['hours_worked'];
                break;
        }
    }
}

// Calculate attendance-based payroll adjustments for selected period
$attendance_payroll_adjustments = null;
if ($selected_employee) {
    // Use period dates if available, otherwise use current month
    $calc_period_start = $period_start ? $period_start : date('Y-m-01');
    $calc_period_end = $period_end ? $period_end : date('Y-m-t');
    
    // Get base salary components
    $base_components = [];
    if ($earnings_result) {
        $earnings_result->data_seek(0);
        while($earning = $earnings_result->fetch_assoc()) {
            $base_components[] = $earning;
        }
        // Reset pointer for later use
        $earnings_result->data_seek(0);
    }
    
    // Calculate payroll based on attendance for the selected period
    // For bi-monthly periods, we need to prorate the monthly salary
    $attendance_payroll_adjustments = calculatePayrollFromAttendance(
        $conn, 
        $selected_employee, 
        $calc_period_start, 
        $calc_period_end,
        $base_components
    );
    
    // If it's a bi-monthly period, adjust the base salary calculation
    if ($payroll_period && ($payroll_period === 'first' || $payroll_period === 'second')) {
        // For bi-monthly periods, the base salary should be half of monthly salary
        // The calculation function already handles this based on attendance days
        // But we need to ensure the base salary is prorated correctly
        if (isset($attendance_payroll_adjustments['salary_adjustments'])) {
            // The calculation already accounts for actual days worked
            // We just need to ensure the base is correct for the period
        }
    }
    
    // Use attendance summary from API calculation as single source of truth
    if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['attendance_summary'])) {
        $attendance_summary = $attendance_payroll_adjustments['attendance_summary'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - Accounting and Finance System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="../assets/image/LOGO.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/financial-reporting.css">
    <link rel="stylesheet" href="../assets/css/payroll-management.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-4">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/image/LOGO.png" alt="Evergreen Logo" class="logo-img">
                </div>
                <div class="logo-text">
                    <h1>EVERGREEN</h1>
                    <p>Secure. Invest. Achieve</p>
                </div>
            </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../core/dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="modulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-th-large me-1"></i>Modules
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="modulesDropdown">
                            <li><a class="dropdown-item" href="general-ledger.php"><i class="fas fa-book me-2"></i>General Ledger</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-line me-2"></i>Financial Reporting</a></li>
                            <li><a class="dropdown-item" href="loan-accounting.php"><i class="fas fa-hand-holding-usd me-2"></i>Loan Accounting</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="transaction-reading.php"><i class="fas fa-exchange-alt me-2"></i>Transaction Reading</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-receipt me-2"></i>Expense Tracking</a></li>
                            <li><a class="dropdown-item active" href="payroll-management.php"><i class="fas fa-users me-2"></i>Payroll Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-bar me-2"></i>Financial Statements</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-money-bill-wave me-2"></i>Cash Flow Report</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-clipboard-list me-2"></i>Expense Summary</a></li>
                            <li><a class="dropdown-item" href="payroll-management.php"><i class="fas fa-wallet me-2"></i>Payroll Report</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="bin-station.php"><i class="fas fa-trash-alt me-2"></i>Bin Station</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="database-settings.php"><i class="fas fa-database me-2"></i>Database Settings</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown d-none d-md-block">
                    <a class="nav-icon-btn" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom notifications-dropdown" aria-labelledby="notificationsDropdown">
                        <li class="dropdown-header">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-item text-center text-muted"><small>Loading notifications...</small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="activity-log.php">View All Notifications</a></li>
                    </ul>
                </div>
                
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <a class="user-profile-btn" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <i class="fas fa-chevron-down ms-2 d-none d-lg-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="user-dropdown-header">
                                <i class="fas fa-user-circle fa-2x"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($current_user['username']); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="activity-log.php"><i class="fas fa-history me-2"></i>Activity Log</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../core/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="container-fluid py-3">
        <!-- Beautiful Page Header -->
        <div class="beautiful-page-header mb-3">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="header-content">
                            <h1 class="page-title-beautiful">
                                <i class="fas fa-users me-3"></i>
                                Payroll Management
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Manage employee payroll, attendance, and tax information
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="header-info-card">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Database Status</div>
                                    <div class="info-value status-connected">Connected</div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Current Period</div>
                                    <div class="info-value"><?php echo date('F Y'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="header-actions mt-3">
                    <!-- Primary Selection Card -->
                    <div class="selection-card mb-3">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="selection-group">
                                    <label for="employee-select" class="selection-label">
                                        <i class="fas fa-user me-2"></i>Select Employee
                                    </label>
                                    <select class="form-select form-select-lg" id="employee-select" onchange="changeEmployee()">
                                    <option value="">Choose an employee...</option>
                                    <?php 
                                    $employees_result->data_seek(0);
                                    while($emp = $employees_result->fetch_assoc()): 
                                        // Use HRIS full name if available, otherwise use employee_refs name
                                        $display_name = !empty($emp['hris_full_name']) ? trim($emp['hris_full_name']) : ($emp['name'] ?? 'Unknown');
                                            
                                            // Ensure external_employee_no is in correct format (EMP001, EMP002, etc.)
                                            // Use employee_id to generate consistent format
                                            $employee_id = intval($emp['hris_employee_id'] ?? 0);
                                            if ($employee_id > 0) {
                                                $employee_number = 'EMP' . str_pad($employee_id, 3, '0', STR_PAD_LEFT);
                                            } else {
                                                // Fallback to existing external_employee_no if employee_id not available
                                                $employee_number = $emp['external_employee_no'] ?? '';
                                                // Normalize format if needed
                                                if (preg_match('/EMP(\d+)/i', $employee_number, $matches)) {
                                                    $employee_number = 'EMP' . str_pad(intval($matches[1]), 3, '0', STR_PAD_LEFT);
                                                }
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($employee_number); ?>" 
                                                <?php echo ($employee_number == $selected_employee) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($display_name . ' (' . $employee_number . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                            <div class="col-lg-7">
                                <div class="selection-group">
                                    <label for="payroll-period-select" class="selection-label">
                                        <i class="fas fa-calendar-alt me-2"></i>Payroll Period
                                    </label>
                                    <div class="period-selectors">
                                        <select class="form-select form-select-lg" id="payroll-month-select" onchange="changePayrollPeriod()">
                                        <?php
                                            // Query database for months with attendance or leave data from HRIS
                                            // This dynamically shows months that have actual data
                                            $months_query = "SELECT DISTINCT DATE_FORMAT(a.date, '%Y-%m') as month
                                                            FROM attendance a
                                                            UNION
                                                            SELECT DISTINCT DATE_FORMAT(lr.start_date, '%Y-%m') as month
                                                            FROM leave_request lr
                                                            WHERE UPPER(TRIM(lr.status)) = 'APPROVED'
                                                            UNION
                                                            SELECT DISTINCT DATE_FORMAT(lr.end_date, '%Y-%m') as month
                                                            FROM leave_request lr
                                                            WHERE UPPER(TRIM(lr.status)) = 'APPROVED'
                                                            UNION
                                                            SELECT DISTINCT DATE_FORMAT(ea.attendance_date, '%Y-%m') as month
                                                            FROM employee_attendance ea
                                                            ORDER BY month DESC";
                                            
                                            $months_result = $conn->query($months_query);
                                            $available_months = [];
                                            
                                            if ($months_result && $months_result->num_rows > 0) {
                                                while ($month_row = $months_result->fetch_assoc()) {
                                                    $available_months[] = $month_row['month'];
                                                }
                                            }
                                            
                                            // Always include current month even if no data yet
                                        $current_month = date('Y-m');
                                            if (!in_array($current_month, $available_months)) {
                                                array_unshift($available_months, $current_month);
                                            }
                                            
                                            // Remove duplicates and sort descending
                                            $available_months = array_unique($available_months);
                                            rsort($available_months);
                                            
                                            // If no months found, at least show current month
                                            if (empty($available_months)) {
                                                $available_months = [$current_month];
                                            }
                                            
                                            // Build dropdown options
                                            foreach ($available_months as $month_date) {
                                                $month_label = date('F Y', strtotime($month_date . '-01'));
                                                $selected = ($payroll_month == $month_date || (empty($payroll_month) && $month_date == $current_month)) ? 'selected' : '';
                                            echo "<option value=\"$month_date\" $selected>$month_label</option>";
                                        }
                                        ?>
                                    </select>
                                        <select class="form-select form-select-lg" id="payroll-period-select" onchange="changePayrollPeriod()">
                                        <?php
                                        // Use current month if no month is selected
                                        $display_month = !empty($payroll_month) ? $payroll_month : date('Y-m');
                                        $last_day = date('t', strtotime($display_month . '-01'));
                                        ?>
                                        <option value="">Full Month</option>
                                        <option value="first" <?php echo ($payroll_period === 'first') ? 'selected' : ''; ?>>1-15</option>
                                        <option value="second" <?php echo ($payroll_period === 'second') ? 'selected' : ''; ?>>16-<?php echo $last_day; ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if ($payroll_period && $period_label): ?>
                        <div class="selected-period-badge mt-2">
                                <i class="fas fa-calendar-check me-2"></i>
                            <span><strong>Selected Period:</strong> <?php echo htmlspecialchars($period_label); ?></span>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Employee Search and Filters -->
        <div class="search-filters-section mb-3">
            <div class="search-filters-card">
                <div class="filters-header">
                    <div class="filters-title-section">
                        <i class="fas fa-users me-2"></i>
                        <h5 class="mb-0">Find Employee</h5>
                        <div class="results-counter">
                            <span class="badge-employee-count">
                                <i class="fas fa-user-check me-1"></i>
                                <?php echo $employees_result->num_rows; ?> employee<?php echo $employees_result->num_rows != 1 ? 's' : ''; ?> available
                            </span>
                        </div>
                    </div>
                    <button class="btn-toggle-filters" onclick="toggleFilters()" type="button" aria-expanded="false">
                        <i class="fas fa-filter me-1"></i>Advanced Filters
                        <i class="fas fa-chevron-down ms-1" id="filter-chevron"></i>
                    </button>
                </div>
                        
                        <div class="filters-content" id="filters-content">
                    <form method="GET" class="filters-form" id="employee-filter-form">
                                <input type="hidden" name="employee" value="<?php echo htmlspecialchars($selected_employee); ?>">
                        <input type="hidden" name="payroll_month" value="<?php echo htmlspecialchars($payroll_month); ?>">
                        <input type="hidden" name="payroll_period" value="<?php echo htmlspecialchars($payroll_period); ?>">
                                <div class="row g-3">
                                    <!-- Search Bar -->
                                    <div class="col-md-4">
                                <label for="search" class="form-label">
                                    <i class="fas fa-search me-1"></i>Search Employee
                                </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Name, employee number, or ID..." 
                                                   value="<?php echo htmlspecialchars($search_term); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Position Filter -->
                                    <div class="col-md-2">
                                <label for="position" class="form-label">
                                    <i class="fas fa-briefcase me-1"></i>Position
                                </label>
                                        <select class="form-select" id="position" name="position">
                                            <option value="">All Positions</option>
                                            <?php 
                                            $positions_result->data_seek(0);
                                            while($pos = $positions_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($pos['position']); ?>" 
                                                        <?php echo ($pos['position'] == $filter_position) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($pos['position']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Department Filter -->
                                    <div class="col-md-2">
                                <label for="department" class="form-label">
                                    <i class="fas fa-building me-1"></i>Department
                                </label>
                                        <select class="form-select" id="department" name="department">
                                            <option value="">All Departments</option>
                                            <?php 
                                            $departments_result->data_seek(0);
                                            while($dept = $departments_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                                        <?php echo ($dept['department'] == $filter_department) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['department']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Employment Type Filter -->
                                    <div class="col-md-2">
                                <label for="type" class="form-label">
                                    <i class="fas fa-user-tag me-1"></i>Employment Type
                                </label>
                                        <select class="form-select" id="type" name="type">
                                            <option value="">All Types</option>
                                            <?php 
                                            $types_result->data_seek(0);
                                            while($type = $types_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($type['employment_type']); ?>" 
                                                        <?php echo ($type['employment_type'] == $filter_type) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($type['employment_type']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-1"></i>Apply
                                            </button>
                                    <a href="?employee=<?php echo htmlspecialchars($selected_employee); ?>&payroll_month=<?php echo htmlspecialchars($payroll_month); ?>&payroll_period=<?php echo htmlspecialchars($payroll_period); ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
        <!-- Tab Navigation -->
        <div class="payroll-tabs-container">
            <ul class="nav nav-pills payroll-nav-tabs" id="payrollTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="employee-details-tab" data-bs-toggle="pill" data-bs-target="#employee-details" type="button" role="tab">
                        Employee Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tax-mgmt-tab" data-bs-toggle="pill" data-bs-target="#tax-mgmt" type="button" role="tab">
                        Tax Management
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="overall-tab" data-bs-toggle="pill" data-bs-target="#overall" type="button" role="tab">
                        Overall
                    </button>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="tab-content" id="payrollTabsContent">
            
            <!-- EMPLOYEE DETAILS TAB -->
            <div class="tab-pane fade show active" id="employee-details" role="tabpanel">
                <div class="payroll-content-card">
                    <h3 class="payroll-section-title">Employee Details</h3>
                    
                    <?php if ($current_employee): 
                        // Calculate salary rates using the same logic as payroll calculation API
                        $base_salary = floatval($current_employee['base_monthly_salary'] ?? 0);
                        $working_days_per_month = 22; // Standard Philippine working days
                        $hours_per_day = 8;
                        
                        // Use period-based calculation if attendance adjustments are available
                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments'])) {
                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                            // Use daily_rate and hourly_rate from calculation if available
                            if (isset($adj['daily_rate']) && $adj['daily_rate'] > 0) {
                                $daily_rate = $adj['daily_rate'];
                                $hourly_rate = isset($adj['hourly_rate']) ? $adj['hourly_rate'] : ($daily_rate / $hours_per_day);
                            } else {
                                // Fallback to monthly calculation
                        $daily_rate = $base_salary > 0 ? $base_salary / $working_days_per_month : 0;
                        $hourly_rate = $daily_rate > 0 ? $daily_rate / $hours_per_day : 0;
                            }
                        } else {
                            // Calculate based on selected period
                            if ($payroll_period === 'first' || $payroll_period === 'second') {
                                // Bi-monthly period: prorate the salary
                                $year = date('Y', strtotime($payroll_month . '-01'));
                                $month = date('m', strtotime($payroll_month . '-01'));
                                $last_day = date('t', strtotime($payroll_month . '-01'));
                                
                                if ($payroll_period === 'first') {
                                    $period_days = 15;
                                } else {
                                    $period_days = $last_day - 15;
                                }
                                
                                $prorated_salary = ($base_salary / $last_day) * $period_days;
                                $daily_rate = $prorated_salary / $period_days;
                                $hourly_rate = $daily_rate / $hours_per_day;
                            } else {
                                // Full month
                                $daily_rate = $base_salary > 0 ? $base_salary / $working_days_per_month : 0;
                                $hourly_rate = $daily_rate > 0 ? $daily_rate / $hours_per_day : 0;
                            }
                        }
                    ?>
                        <div class="employee-details-container">
                            <div class="employee-details-main-layout">
                                <!-- Left Column: Photo + Employee Info -->
                                <div class="employee-left-column">
                                    <div class="employee-photo-section">
                                        <div class="employee-photo">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <div class="employee-status">
                                            <span class="status-badge status-<?php echo strtolower($current_employee['employment_type']); ?>">
                                                <?php echo strtoupper($current_employee['employment_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="employee-info-section">
                                        <table class="employee-info-table">
                                            <tr>
                                                <td>Employee Number</td>
                                                <td><?php echo htmlspecialchars($current_employee['external_employee_no']); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Full Name</td>
                                                <td><?php echo htmlspecialchars($current_employee['name'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <?php if (!empty($current_employee['first_name'])): ?>
                                            <tr>
                                                <td>First Name</td>
                                                <td><?php echo htmlspecialchars($current_employee['first_name']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['middle_name'])): ?>
                                            <tr>
                                                <td>Middle Name</td>
                                                <td><?php echo htmlspecialchars($current_employee['middle_name']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['last_name'])): ?>
                                            <tr>
                                                <td>Last Name</td>
                                                <td><?php echo htmlspecialchars($current_employee['last_name']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>Position</td>
                                                <td><?php echo htmlspecialchars($current_employee['position'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Department</td>
                                                <td><?php echo htmlspecialchars($current_employee['department'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Employment Type</td>
                                                <td><?php echo ucfirst($current_employee['employment_type'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <?php if (!empty($current_employee['employment_status'])): ?>
                                            <tr>
                                                <td>Employment Status</td>
                                                <td><?php echo ucfirst($current_employee['employment_status']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['gender'])): ?>
                                            <tr>
                                                <td>Gender</td>
                                                <td><?php echo ucfirst($current_employee['gender']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['birth_date'])): ?>
                                            <tr>
                                                <td>Birth Date</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['birth_date'])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['email'])): ?>
                                            <tr>
                                                <td>Email</td>
                                                <td><?php echo htmlspecialchars($current_employee['email']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['contact_number'])): ?>
                                            <tr>
                                                <td>Contact Number</td>
                                                <td><?php echo htmlspecialchars($current_employee['contact_number']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['address'])): ?>
                                            <tr>
                                                <td>Address</td>
                                                <td><?php echo htmlspecialchars($current_employee['address']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['hire_date'])): ?>
                                            <tr>
                                                <td>Hire Date (HRIS)</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['hire_date'])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>Date of Joining</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['created_at'])); ?></td>
                                            </tr>
                                            <?php if (!empty($current_employee['contract_type'])): ?>
                                            <tr>
                                                <td>Contract Type</td>
                                                <td><?php echo htmlspecialchars($current_employee['contract_type']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['contract_start_date'])): ?>
                                            <tr>
                                                <td>Contract Start</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['contract_start_date'])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['contract_end_date'])): ?>
                                            <tr>
                                                <td>Contract End</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['contract_end_date'])); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($current_employee['contract_benefits'])): ?>
                                            <tr>
                                                <td>Contract Benefits</td>
                                                <td><?php echo htmlspecialchars($current_employee['contract_benefits']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td>Bank Name</td>
                                                <td><?php echo htmlspecialchars($company_bank['bank_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Bank Account Number</td>
                                                <td><?php echo htmlspecialchars($company_bank['account_number'] ?? 'N/A'); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Right Column: Salary Cards -->
                                <div class="employee-right-column">
                                    <!-- Salary Rates Card -->
                                    <div class="salary-rates-card">
                                        <h6 class="salary-rates-title">SALARY RATES</h6>
                                        <table class="salary-rates-table">
                                            <tr>
                                                <td>Monthly Salary</td>
                                                <td class="amount-cell">₱<?php echo number_format($base_salary, 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Daily Rate</td>
                                                <td class="amount-cell">₱<?php echo number_format($daily_rate, 2); ?></td>
                                            </tr>
                                            <tr>
                                                <td>Hourly Rate</td>
                                                <td class="amount-cell">₱<?php echo number_format($hourly_rate, 2); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    
                                    <!-- Payroll Impact Card -->
                                    <?php if ($attendance_payroll_adjustments): 
                                        $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                        $has_impact = $adj['absent_deduction'] > 0 || $adj['half_day_deduction'] > 0 || $adj['late_penalty'] > 0 || $adj['overtime_pay'] > 0;
                                    ?>
                                    <div class="payroll-impact-card">
                                        <div class="payroll-impact-header">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Payroll Impact</span>
                                        </div>
                                        <div class="payroll-impact-content">
                                            <?php if (!$has_impact): ?>
                                            <div class="payroll-impact-item">
                                                <i class="fas fa-check-circle text-success"></i>
                                                <span class="text-success">Perfect attendance - no payroll adjustments</span>
                                            </div>
                                            <?php else: ?>
                                                <?php if ($adj['overtime_pay'] > 0): ?>
                                                <div class="payroll-impact-item">
                                                    <span class="impact-label text-success">Overtime Pay</span>
                                                    <span class="impact-amount text-success">+₱<?php echo number_format($adj['overtime_pay'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($adj['absent_deduction'] > 0): ?>
                                                <div class="payroll-impact-item">
                                                    <span class="impact-label text-danger">Absent Deduction</span>
                                                    <span class="impact-amount text-danger">-₱<?php echo number_format($adj['absent_deduction'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($adj['half_day_deduction'] > 0): ?>
                                                <div class="payroll-impact-item">
                                                    <span class="impact-label text-warning">Half Day Deduction</span>
                                                    <span class="impact-amount text-warning">-₱<?php echo number_format($adj['half_day_deduction'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($adj['late_penalty'] > 0): ?>
                                                <div class="payroll-impact-item">
                                                    <span class="impact-label text-danger">Late Penalty</span>
                                                    <span class="impact-amount text-danger">-₱<?php echo number_format($adj['late_penalty'], 2); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attendance Summary Cards -->
                        <div class="attendance-summary-section">
                            <h5 class="section-subtitle">Attendance Summary - <?php echo $period_label ? htmlspecialchars($period_label) : date('F Y', strtotime($display_month . '-01')); ?></h5>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="attendance-summary-card present">
                                        <div class="summary-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="summary-content">
                                            <div class="summary-number"><?php echo $attendance_summary['present_days']; ?></div>
                                            <div class="summary-label">Present Days</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-summary-card absent">
                                        <div class="summary-icon">
                                            <i class="fas fa-times-circle"></i>
                                        </div>
                                        <div class="summary-content">
                                            <div class="summary-number"><?php echo $attendance_summary['absent_days']; ?></div>
                                            <div class="summary-label">Absent Days</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-summary-card late">
                                        <div class="summary-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="summary-content">
                                            <div class="summary-number"><?php echo $attendance_summary['late_days']; ?></div>
                                            <div class="summary-label">Late Days</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-summary-card leave">
                                        <div class="summary-icon">
                                            <i class="fas fa-calendar-times"></i>
                                        </div>
                                        <div class="summary-content">
                                            <div class="summary-number"><?php echo $attendance_summary['leave_days']; ?></div>
                                            <div class="summary-label">Leave Days</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hours Summary -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="hours-summary-card">
                                        <div class="hours-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="hours-content">
                                            <div class="hours-number"><?php echo number_format($attendance_summary['total_hours'], 1); ?></div>
                                            <div class="hours-label">Total Hours</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="hours-summary-card">
                                        <div class="hours-icon">
                                            <i class="fas fa-business-time"></i>
                                        </div>
                                        <div class="hours-content">
                                            <div class="hours-number"><?php echo number_format($attendance_summary['regular_hours'], 1); ?></div>
                                            <div class="hours-label">Regular Hours</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="hours-summary-card">
                                        <div class="hours-icon">
                                            <i class="fas fa-plus-circle"></i>
                                        </div>
                                        <div class="hours-content">
                                            <div class="hours-number"><?php echo number_format($attendance_summary['overtime_hours'], 1); ?></div>
                                            <div class="hours-label">Overtime Hours</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attendance Records Table -->
                        <div class="attendance-records-section">
                            <div class="section-header">
                                <h5 class="section-subtitle">Daily Attendance Records</h5>
                                <div class="attendance-filters">
                                    <select class="form-select form-select-sm" id="attendance-month-filter">
                                        <option value="<?php echo date('Y-m'); ?>" <?php echo ($display_month == date('Y-m')) ? 'selected' : ''; ?>><?php echo date('F Y'); ?></option>
                                        <option value="<?php echo date('Y-m', strtotime('-1 month')); ?>" <?php echo ($display_month == date('Y-m', strtotime('-1 month'))) ? 'selected' : ''; ?>><?php echo date('F Y', strtotime('-1 month')); ?></option>
                                        <option value="<?php echo date('Y-m', strtotime('-2 months')); ?>" <?php echo ($display_month == date('Y-m', strtotime('-2 months'))) ? 'selected' : ''; ?>><?php echo date('F Y', strtotime('-2 months')); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-container">
                                <table class="attendance-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Status</th>
                                            <th>Hours Worked</th>
                                            <th>Overtime</th>
                                            <th>Late (mins)</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attendance_data)): ?>
                                            <?php foreach ($attendance_data as $record): ?>
                                                <tr class="attendance-row status-<?php echo $record['status']; ?>">
                                                    <td><?php echo date('M d', strtotime($record['date'])); ?></td>
                                                    <td><?php echo date('D', strtotime($record['date'])); ?></td>
                                                    <td>
                                                        <?php if ($record['time_in']): ?>
                                                            <span class="time-badge"><?php echo date('H:i', strtotime($record['time_in'])); ?></span>
                                                        <?php else: ?>
                                                            <span class="time-badge absent">--:--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['time_out']): ?>
                                                            <span class="time-badge"><?php echo date('H:i', strtotime($record['time_out'])); ?></span>
                                                        <?php else: ?>
                                                            <span class="time-badge absent">--:--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="hours-badge"><?php echo number_format($record['hours_worked'], 1); ?>h</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($record['overtime_hours'] > 0): ?>
                                                            <span class="overtime-badge"><?php echo number_format($record['overtime_hours'], 1); ?>h</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($record['late_minutes'] > 0): ?>
                                                            <span class="late-badge"><?php echo $record['late_minutes']; ?>m</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="remarks-cell">
                                                        <?php echo htmlspecialchars($record['remarks'] ?? ''); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    <i class="fas fa-calendar-times me-2"></i>
                                                    No attendance records found for this month
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Attendance Statistics -->
                        <div class="attendance-statistics">
                            <h5 class="section-subtitle">Attendance Statistics</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-number">
                                                <?php 
                                                $attendance_rate = $attendance_summary['total_days'] > 0 
                                                    ? round(($attendance_summary['present_days'] / $attendance_summary['total_days']) * 100, 1) 
                                                    : 0; 
                                                echo $attendance_rate; 
                                                ?>%
                                            </div>
                                            <div class="stat-label">Attendance Rate</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-number"><?php echo $attendance_summary['total_days']; ?></div>
                                            <div class="stat-label">Total Working Days</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h4>No Employee Selected</h4>
                            <p>Please select an employee from the dropdown above to view their details.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAX MANAGEMENT TAB -->
            <div class="tab-pane fade" id="tax-mgmt" role="tabpanel">
                <div class="payroll-content-card">
                    <div class="tax-calculator-container">
                        <?php
                        // Calculate monthly income (GROSS salary = basic_salary + overtime_pay)
                        // Use GROSS salary for tax calculations, NOT adjusted_salary (which has deductions)
                        $tax_monthly_income = 0;
                        
                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments'])) {
                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                            // Use gross_salary if available, otherwise calculate from basic_salary + overtime_pay
                            if (isset($adj['gross_salary']) && $adj['gross_salary'] > 0) {
                                $tax_monthly_income = $adj['gross_salary'];
                            } else {
                                $tax_monthly_income = $adj['basic_salary'] + $adj['overtime_pay'];
                            }
                        }
                        
                        // Fallback to base salary if no attendance adjustments
                        if ($tax_monthly_income == 0) {
                            if ($earnings_result && $earnings_result->num_rows > 0) {
                                        $earnings_result->data_seek(0);
                                while($earning = $earnings_result->fetch_assoc()) {
                                                if ($earning['code'] === 'BASIC') {
                                        $tax_monthly_income = floatval($earning['value']);
                                        break;
                                    }
                                }
                                $earnings_result->data_seek(0);
                            }
                            
                            // If still no basic salary found, use position salary
                            if ($tax_monthly_income == 0) {
                                $tax_monthly_income = $position_salary > 0 ? $position_salary : 0;
                            }
                        }
                        
                        // For tax calculations:
                        // - Monthly Income (display) = GROSS salary (basic + overtime)
                        // - Contributions (SSS, PhilHealth, Pag-IBIG) are calculated on BASE monthly salary
                        // - Withholding Tax is calculated on (GROSS salary - contributions)
                        
                        // Get base monthly salary for contribution calculations
                        $tax_basic_salary = $position_salary > 0 ? $position_salary : 0;
                        if ($tax_basic_salary == 0 && $earnings_result) {
                            $earnings_result->data_seek(0);
                            while($earning = $earnings_result->fetch_assoc()) {
                                if ($earning['code'] === 'BASIC') {
                                    $tax_basic_salary = floatval($earning['value']);
                                    break;
                                }
                            }
                            $earnings_result->data_seek(0);
                        }
                        
                        // If we have attendance adjustments, get the base salary from there
                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments']['prorated_base_salary'])) {
                            $tax_basic_salary = $attendance_payroll_adjustments['salary_adjustments']['prorated_base_salary'];
                        }
                        
                        // Calculate mandatory contributions using 2025 rates (based on BASE salary)
                        $tax_sss_contrib = calculateSSSContribution($tax_basic_salary);
                        $tax_philhealth_contrib = calculatePhilHealthContribution($tax_basic_salary);
                        $tax_pagibig_contrib = calculatePagIBIGContribution($tax_basic_salary);
                        
                        // Calculate taxable income and withholding tax (based on GROSS salary minus contributions)
                        $tax_taxable_income = $tax_monthly_income - $tax_sss_contrib['employee'] - $tax_philhealth_contrib['employee'] - $tax_pagibig_contrib['employee'];
                        $tax_income_tax = calculateBIRWithholdingTax($tax_taxable_income);
                        
                        // Calculate totals
                        $tax_net_pay_after_tax = $tax_monthly_income - $tax_income_tax;
                        $tax_total_contributions = $tax_sss_contrib['employee'] + $tax_philhealth_contrib['employee'] + $tax_pagibig_contrib['employee'];
                        $tax_total_deductions = $tax_income_tax + $tax_total_contributions;
                        $tax_net_pay_after_deductions = $tax_monthly_income - $tax_total_deductions;
                        ?>
                        
                        <!-- Input Section -->
                        <div class="tax-input-section">
                            <div class="tax-input-group">
                                <label for="monthly-income-display">Monthly Income</label>
                                <div class="tax-input-display">₱<?php echo number_format($tax_monthly_income, 2); ?></div>
                    </div>
                            <div class="tax-membership-group">
                                <div class="tax-membership-item">
                                    <label>SSS Membership:</label>
                                    <span class="tax-membership-badge">Employed</span>
                            </div>
                                <div class="tax-membership-item">
                                    <label>PhilHealth Membership:</label>
                                    <span class="tax-membership-badge">Employed</span>
                        </div>
                                <div class="tax-membership-item">
                                    <label>Pag-IBIG Membership:</label>
                                    <span class="tax-membership-badge">Employed</span>
                                </div>
                </div>
            </div>

                        <!-- Computation Result Section -->
                        <div class="tax-computation-result">
                            <h4 class="tax-computation-title">Computation Result</h4>
                            
                            <div class="row g-4">
                                <!-- Left Column - Tax Computation -->
                                <div class="col-md-6">
                                    <div class="tax-computation-box">
                                        <h5 class="tax-box-title">Tax Computation</h5>
                                        <div class="tax-computation-item">
                                            <span class="tax-item-label">Income Tax:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_income_tax, 2); ?></span>
                                </div>
                                        <div class="tax-computation-item">
                                            <span class="tax-item-label">Net Pay after Tax:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_net_pay_after_tax, 2); ?></span>
                                </div>
                            </div>
                        </div>
                                
                                <!-- Right Column - Monthly Contributions -->
                                <div class="col-md-6">
                                    <div class="tax-computation-box">
                                        <h5 class="tax-box-title">Monthly Contributions</h5>
                                        <div class="tax-computation-item">
                                            <span class="tax-item-label">SSS:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_sss_contrib['employee'], 2); ?></span>
                                    </div>
                                        <div class="tax-computation-item">
                                            <span class="tax-item-label">PhilHealth:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_philhealth_contrib['employee'], 2); ?></span>
                                </div>
                                        <div class="tax-computation-item">
                                            <span class="tax-item-label">Pag-IBIG:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_pagibig_contrib['employee'], 2); ?></span>
                            </div>
                                        <div class="tax-computation-item tax-total">
                                            <span class="tax-item-label">Total Contributions:</span>
                                            <span class="tax-item-value">₱<?php echo number_format($tax_total_contributions, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                                    </div>
                            
                            <!-- Summary Deductions -->
                            <div class="tax-summary-deductions">
                                    <div class="tax-summary-item">
                                    <span class="tax-summary-label">Total Deductions:</span>
                                    <span class="tax-summary-value">₱<?php echo number_format($tax_total_deductions, 2); ?></span>
                                    </div>
                                <div class="tax-summary-item tax-summary-final">
                                    <span class="tax-summary-label">Net Pay after Deductions:</span>
                                    <span class="tax-summary-value">₱<?php 
                                        $final_net_pay = max(0, $tax_net_pay_after_deductions);
                                        echo number_format($final_net_pay, 2); 
                                    ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- OVERALL TAB -->
            <div class="tab-pane fade" id="overall" role="tabpanel">
                <div class="payroll-content-card">
                    <h3 class="payroll-section-title">Payslip</h3>
                    
                    <!-- Company Header (only if real data exists) -->
                    <?php if (!empty($company_bank['bank_name']) && $company_bank['bank_name'] !== 'BANK NAME'): ?>
                    <div class="overall-header">
                        <div class="bank-name"><?php echo htmlspecialchars($company_bank['bank_name']); ?></div>
                        <?php if (!empty($company_bank['name']) && $company_bank['name'] !== 'Company Name'): ?>
                        <div class="company-name">(<?php echo htmlspecialchars($company_bank['name']); ?>)</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Employee Details Section -->
                    <div class="overall-section">
                        <div class="overall-section-title">Employee Information</div>
                        <?php 
                        $employees_result->data_seek(0); // Reset pointer
                        if ($employees_result && $employees_result->num_rows > 0): 
                            $employee = $employees_result->fetch_assoc();
                        ?>
                            <table class="employee-info-table">
                                <tr>
                                    <td>Employee Code</td>
                                    <td><?php echo htmlspecialchars($employee['external_employee_no']); ?></td>
                                </tr>
                                <tr>
                                    <td>Employee Name</td>
                                    <td><?php echo htmlspecialchars($employee['name'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php if (!empty($current_employee['position']) || !empty($current_employee['hris_position_title'])): ?>
                                <tr>
                                    <td>Position</td>
                                    <td><?php echo htmlspecialchars($current_employee['position'] ?? $current_employee['hris_position_title'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($current_employee['department']) || !empty($current_employee['hris_department_name'])): ?>
                                <tr>
                                    <td>Department</td>
                                    <td><?php echo htmlspecialchars($current_employee['department'] ?? $current_employee['hris_department_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($current_employee['employment_type'])): ?>
                                <tr>
                                    <td>Employment Type</td>
                                    <td><?php echo ucfirst(htmlspecialchars($current_employee['employment_type'])); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($position_salary > 0): ?>
                                <tr>
                                    <td>Base Monthly Salary</td>
                                    <td>₱<?php echo number_format($position_salary, 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>Pay Period</td>
                                    <td><?php echo $period_label ? htmlspecialchars($period_label) : date('F Y', strtotime($display_month . '-01')); ?></td>
                                </tr>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Earnings and Deductions -->
                    <div class="overall-section">
                        <div class="payroll-two-column">
                            <!-- Earnings -->
                            <div>
                                <div class="overall-section-title">Earnings</div>
                                <table class="payroll-items-table">
                                    <thead>
                                        <tr>
                                            <th>Particulars</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Use attendance_payroll_adjustments as primary source (same as Employee Details and Tax Management)
                                        $total_earnings_overall = 0;
                                        
                                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments'])) {
                                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                            
                                            // Basic Salary (from attendance - earned from present days)
                                            if (isset($adj['basic_salary']) && $adj['basic_salary'] > 0) {
                                                $total_earnings_overall += $adj['basic_salary'];
                                        ?>
                                            <tr>
                                                <td>Basic Salary</td>
                                                <td class="amount-cell">₱<?php echo number_format($adj['basic_salary'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            }
                                            
                                            // Overtime Pay (from attendance)
                                            if (isset($adj['overtime_pay']) && $adj['overtime_pay'] > 0) {
                                                $total_earnings_overall += $adj['overtime_pay'];
                                        ?>
                                            <tr>
                                                <td>Overtime Pay</td>
                                                <td class="amount-cell">₱<?php echo number_format($adj['overtime_pay'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            }
                                        }
                                        
                                        // Show message if no earnings found
                                        if ($total_earnings_overall == 0): ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-muted py-3">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    No earnings data available for this period
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if ($total_earnings_overall > 0): ?>
                                    <tfoot>
                                        <tr class="payroll-total-row">
                                            <td><strong>Total Earnings</strong></td>
                                            <td class="amount-cell"><strong>₱<?php echo number_format($total_earnings_overall, 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <!-- Deductions -->
                            <div>
                                <div class="overall-section-title">Deductions</div>
                                <table class="payroll-items-table">
                                    <thead>
                                        <tr>
                                            <th>Particulars</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $deductions_result->data_seek(0); // Reset pointer
                                        $total_deductions_overall = 0;
                                        
                                        // Calculate unpaid leave deduction separately
                                        $unpaid_leave_deduction = 0;
                                        $unpaid_leave_days = 0;
                                        $actual_absent_deduction = 0;
                                        $half_day_deduction_amount = 0;
                                        $late_penalty_amount = 0;
                                        
                                        // MATCHING HRIS LOGIC EXACTLY: Only include attendance-based deductions when NO saved payslip exists for this period
                                        // This ensures consistency with HRIS payroll calculations (payslip-data.php logic)
                                        if ($attendance_payroll_adjustments && $employee_id_from_external && !$has_saved_payslip_for_period) {
                                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                            
                                            // Get daily rate for calculation
                                            $daily_rate_for_deduction = 0;
                                            if (isset($adj['daily_rate']) && $adj['daily_rate'] > 0) {
                                                $daily_rate_for_deduction = $adj['daily_rate'];
                                            } elseif ($position_salary > 0) {
                                                // Calculate daily rate from base salary
                                                $working_days = 22; // Standard working days per month
                                                $daily_rate_for_deduction = $position_salary / $working_days;
                                            }
                                            
                                            // Query unpaid leave days for the period
                                            if ($daily_rate_for_deduction > 0) {
                                                $unpaid_leave_query = "SELECT 
                                                            lr.start_date,
                                                            lr.end_date,
                                                            lt.paid_unpaid
                                                        FROM leave_request lr
                                                        LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
                                                        WHERE lr.employee_id = ?
                                                        AND (UPPER(TRIM(lr.status)) = 'APPROVED' OR LOWER(TRIM(lr.status)) = 'approved')
                                                        AND LOWER(TRIM(COALESCE(lt.paid_unpaid, 'unpaid'))) = 'unpaid'";
                                                
                                                $unpaid_leave_params = [];
                                                $unpaid_leave_types = "";
                                                
                                                if ($payroll_period === 'first' || $payroll_period === 'second') {
                                                    $unpaid_leave_query .= " AND (lr.start_date <= ? AND lr.end_date >= ?)";
                                                    $unpaid_leave_params = [$employee_id_from_external, $period_end, $period_start];
                                                    $unpaid_leave_types = "iss";
                                                } else {
                                                    $month_start = $display_month . '-01';
                                                    $month_end = date('Y-m-t', strtotime($display_month . '-01'));
                                                    $unpaid_leave_query .= " AND (lr.start_date <= ? AND lr.end_date >= ?)";
                                                    $unpaid_leave_params = [$employee_id_from_external, $month_end, $month_start];
                                                    $unpaid_leave_types = "iss";
                                                }
                                                
                                                $unpaid_leave_stmt = $conn->prepare($unpaid_leave_query);
                                                if ($unpaid_leave_stmt) {
                                                    $unpaid_leave_stmt->bind_param($unpaid_leave_types, ...$unpaid_leave_params);
                                                    if ($unpaid_leave_stmt->execute()) {
                                                        $unpaid_leave_result = $unpaid_leave_stmt->get_result();
                                                        if ($unpaid_leave_result) {
                                                            while ($unpaid_leave = $unpaid_leave_result->fetch_assoc()) {
                                                                $leave_start = new DateTime($unpaid_leave['start_date']);
                                                                $leave_end = new DateTime($unpaid_leave['end_date']);
                                                                
                                                                // Count days within the payroll period
                                                                $current_date = clone $leave_start;
                                                                while ($current_date <= $leave_end) {
                                                                    $date_str = $current_date->format('Y-m-d');
                                                                    if ($payroll_period === 'first' || $payroll_period === 'second') {
                                                                        if ($date_str >= $period_start && $date_str <= $period_end) {
                                                                            $unpaid_leave_days++;
                                                                        }
                                                                    } else {
                                                                        $month_start = $display_month . '-01';
                                                                        $month_end = date('Y-m-t', strtotime($display_month . '-01'));
                                                                        if ($date_str >= $month_start && $date_str <= $month_end) {
                                                                            $unpaid_leave_days++;
                                                                        }
                                                                    }
                                                                    $current_date->modify('+1 day');
                                                                }
                                                            }
                                                            $unpaid_leave_stmt->close();
                                                        }
                                                    }
                                                }
                                                
                                                // Calculate unpaid leave deduction
                                                $unpaid_leave_deduction = $unpaid_leave_days * $daily_rate_for_deduction;
                                                
                                                // Calculate actual absent deduction (excluding unpaid leaves)
                                                // Note: absent_deduction from calculation includes both actual absences AND unpaid leaves
                                                // So we subtract unpaid_leave_deduction to get actual absent deduction
                                                $actual_absent_deduction = max(0, ($adj['absent_deduction'] ?? 0) - $unpaid_leave_deduction);
                                            } else {
                                                // Fallback: If we can't calculate daily rate, we can't separate unpaid leaves
                                                // So use absent_deduction as is (which includes both absences and unpaid leaves)
                                                $actual_absent_deduction = $adj['absent_deduction'] ?? 0;
                                                $unpaid_leave_deduction = 0; // Can't calculate separately without daily rate
                                            }
                                            
                                            // Store other deduction amounts for calculation
                                            $half_day_deduction_amount = $adj['half_day_deduction'] ?? 0;
                                            $late_penalty_amount = $adj['late_penalty'] ?? 0;
                                            
                                            // Add attendance-based deductions to total (matching HRIS logic)
                                            // These are only included when there's NO saved payslip for the period
                                            $total_deductions_overall += $actual_absent_deduction;
                                            $total_deductions_overall += $unpaid_leave_deduction;
                                            $total_deductions_overall += $half_day_deduction_amount;
                                            $total_deductions_overall += $late_penalty_amount;
                                        }
                                        
                                        // Display attendance-based deductions ONLY when NO saved payslip exists (matching HRIS logic)
                                        if ($attendance_payroll_adjustments && $employee_id_from_external && !$has_saved_payslip_for_period) {
                                            // Absent days deduction (actual absences, excluding unpaid leaves)
                                            if ($actual_absent_deduction > 0): ?>
                                            <tr>
                                                <td>Absent Days Deduction</td>
                                                <td class="amount-cell">₱<?php echo number_format($actual_absent_deduction, 2); ?></td>
                                            </tr>
                                        <?php 
                                            endif;
                                            
                                            // Unpaid Leave Days Deduction
                                            if ($unpaid_leave_deduction > 0): ?>
                                            <tr>
                                                <td>Unpaid Leave Days Deduction</td>
                                                <td class="amount-cell">₱<?php echo number_format($unpaid_leave_deduction, 2); ?></td>
                                            </tr>
                                        <?php 
                                            endif;
                                            
                                            // Half day deduction
                                            if ($half_day_deduction_amount > 0): ?>
                                            <tr>
                                                <td>Half Day Deduction</td>
                                                <td class="amount-cell">₱<?php echo number_format($half_day_deduction_amount, 2); ?></td>
                                            </tr>
                                        <?php 
                                            endif;
                                            
                                            // Late penalty
                                            if ($late_penalty_amount > 0): ?>
                                            <tr>
                                                <td>Late Arrival Penalty</td>
                                                <td class="amount-cell">₱<?php echo number_format($late_penalty_amount, 2); ?></td>
                                            </tr>
                                        <?php 
                                            endif;
                                        }
                                        
                                        // Calculate mandatory government contributions using 2025 rates
                                        // Use GROSS salary from attendance (same as Tax Management tab)
                                        $overall_gross_salary = 0;
                                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments'])) {
                                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                            // Use gross_salary if available, otherwise calculate from basic_salary + overtime_pay
                                            if (isset($adj['gross_salary']) && $adj['gross_salary'] > 0) {
                                                $overall_gross_salary = $adj['gross_salary'];
                                            } else {
                                                $overall_gross_salary = $adj['basic_salary'] + $adj['overtime_pay'];
                                            }
                                        }
                                        
                                        // Fallback to total_earnings_overall if no attendance data
                                        if ($overall_gross_salary == 0) {
                                            $overall_gross_salary = $total_earnings_overall;
                                        }
                                        
                                        // For tax calculations, use the BASE monthly salary (not gross with overtime)
                                        // This is because SSS, PhilHealth, Pag-IBIG are calculated on base salary, not gross
                                        $overall_basic_salary = $position_salary > 0 ? $position_salary : 0;
                                        
                                        // Get basic salary from earnings if not available
                                        if ($overall_basic_salary == 0 && $earnings_result) {
                                            $earnings_result->data_seek(0);
                                            while($earning = $earnings_result->fetch_assoc()) {
                                                if ($earning['code'] === 'BASIC') {
                                                    // Use the original base salary value, not the gross salary
                                                    $overall_basic_salary = floatval($earning['value']);
                                                    break;
                                                }
                                            }
                                            $earnings_result->data_seek(0); // Reset pointer
                                        }
                                        
                                        // If we have attendance adjustments, get the base salary from there
                                        if ($attendance_payroll_adjustments && isset($attendance_payroll_adjustments['salary_adjustments']['prorated_base_salary'])) {
                                            $overall_basic_salary = $attendance_payroll_adjustments['salary_adjustments']['prorated_base_salary'];
                                        }
                                        
                                        // Calculate mandatory contributions using 2025 rates
                                        $overall_sss_contrib = calculateSSSContribution($overall_basic_salary);
                                        $overall_philhealth_contrib = calculatePhilHealthContribution($overall_basic_salary);
                                        $overall_pagibig_contrib = calculatePagIBIGContribution($overall_basic_salary);
                                        
                                        // Calculate taxable income and withholding tax
                                        $overall_taxable_income = $overall_gross_salary - $overall_sss_contrib['employee'] - $overall_philhealth_contrib['employee'] - $overall_pagibig_contrib['employee'];
                                        $overall_withholding_tax = calculateBIRWithholdingTax($overall_taxable_income);
                                        
                                        // Add mandatory government deductions
                                        $total_deductions_overall += $overall_sss_contrib['employee'];
                                        $total_deductions_overall += $overall_philhealth_contrib['employee'];
                                        $total_deductions_overall += $overall_pagibig_contrib['employee'];
                                        $total_deductions_overall += $overall_withholding_tax;
                                        ?>
                                        
                                        <!-- Mandatory Government Deductions -->
                                        <tr>
                                            <td>SSS Employee Contribution</td>
                                            <td class="amount-cell">₱<?php echo number_format($overall_sss_contrib['employee'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td>PhilHealth Employee Contribution</td>
                                            <td class="amount-cell">₱<?php echo number_format($overall_philhealth_contrib['employee'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Pag-IBIG Employee Contribution</td>
                                            <td class="amount-cell">₱<?php echo number_format($overall_pagibig_contrib['employee'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Withholding Tax (BIR)</td>
                                            <td class="amount-cell">₱<?php echo number_format($overall_withholding_tax, 2); ?></td>
                                        </tr>
                                        
                                        <?php
                                        // Optional deductions (only show if they have values and are not unnecessary)
                                        $overall_unnecessary_deductions = ['MEDICAL', 'LATE', 'ABSENT', 'ADVANCE', 'LOAN', 'UNIFORM']; // These are handled by attendance system or not needed
                                        
                                        if ($deductions_result && $deductions_result->num_rows > 0): 
                                            $deductions_result->data_seek(0);
                                            while($deduction = $deductions_result->fetch_assoc()): 
                                                // Skip unnecessary deductions
                                                if (in_array($deduction['code'], $overall_unnecessary_deductions)) {
                                                    continue;
                                                }
                                                
                                                // Skip mandatory deductions already shown above
                                                if (in_array($deduction['code'], ['SSS_EMP', 'PAGIBIG_EMP', 'PHILHEALTH_EMP', 'WHT'])) {
                                                    continue;
                                                }
                                                
                                                // Use payslip JSON data if available
                                                $amount = 0;
                                                if ($payslip_data && $payslip_data['payslip_json']) {
                                                    $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                                    switch($deduction['code']) {
                                                        case 'LOAN': $amount = $payslip_json['loan_deduction'] ?? $deduction['value']; break;
                                                        case 'UNIFORM': $amount = $payslip_json['uniform_deduction'] ?? $deduction['value']; break;
                                                        default: $amount = $deduction['value']; break;
                                                    }
                                                } else {
                                                    $amount = $deduction['value'];
                                                }
                                                
                                                // Only show if amount is greater than 0
                                                if ($amount > 0) {
                                                $total_deductions_overall += $amount;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($deduction['name']); ?></td>
                                                <td class="amount-cell">₱<?php echo number_format($amount, 2); ?></td>
                                            </tr>
                                        <?php 
                                                }
                                            endwhile; 
                                        endif; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="overall-summary-box">
                        <div class="overall-summary-row">
                            <span class="label">Gross Earnings:</span>
                            <span class="value">₱<?php echo number_format($overall_gross_salary > 0 ? $overall_gross_salary : $total_earnings_overall, 2); ?></span>
                        </div>
                        <div class="overall-summary-row">
                            <span class="label">Total Deductions:</span>
                            <span class="value">₱<?php echo number_format($total_deductions_overall, 2); ?></span>
                        </div>
                        <div class="overall-summary-row">
                            <span class="label">Net Salary:</span>
                            <span class="value">₱<?php 
                                $net_salary = ($overall_gross_salary > 0 ? $overall_gross_salary : $total_earnings_overall) - $total_deductions_overall;
                                // Ensure net salary is never negative - set to 0 if negative
                                $net_salary = max(0, $net_salary);
                                echo number_format($net_salary, 2); 
                            ?></span>
                        </div>
                    </div>

                    <!-- Print Button -->
                    <div class="text-center mt-4 no-print">
                        <button class="btn-print" onclick="printPayslip()">
                            <i class="fas fa-print me-2"></i>Print Payslip
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <p>&copy; <?php echo date('Y'); ?> Evergreen Accounting & Finance. All rights reserved.</p>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/payroll-management.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
