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

// Build dynamic query for employees with filters
$employees_query = "SELECT * FROM employee_refs WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_term)) {
    $employees_query .= " AND (name LIKE ? OR external_employee_no LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($filter_position)) {
    $employees_query .= " AND position = ?";
    $params[] = $filter_position;
    $types .= "s";
}

if (!empty($filter_department)) {
    $employees_query .= " AND department = ?";
    $params[] = $filter_department;
    $types .= "s";
}

if (!empty($filter_type)) {
    $employees_query .= " AND employment_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$employees_query .= " ORDER BY name";

// Execute query with parameters
if (!empty($params)) {
    $stmt = $conn->prepare($employees_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $employees_result = $stmt->get_result();
} else {
    $employees_result = $conn->query($employees_query);
}

// Get unique values for filter dropdowns
$positions_query = "SELECT DISTINCT position FROM employee_refs WHERE position IS NOT NULL AND position != '' ORDER BY position";
$positions_result = $conn->query($positions_query);

$departments_query = "SELECT DISTINCT department FROM employee_refs WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departments_result = $conn->query($departments_query);

$types_query = "SELECT DISTINCT employment_type FROM employee_refs ORDER BY employment_type";
$types_result = $conn->query($types_query);

// Get employee data for selected employee or first employee
if ($selected_employee) {
    $employee_query = "SELECT * FROM employee_refs WHERE external_employee_no = ?";
    $stmt = $conn->prepare($employee_query);
    $stmt->bind_param("s", $selected_employee);
    $stmt->execute();
    $employee_result = $stmt->get_result();
    $current_employee = $employee_result->fetch_assoc();
} else {
    $employee_result = $conn->query("SELECT * FROM employee_refs ORDER BY name LIMIT 1");
    $current_employee = $employee_result->fetch_assoc();
    $selected_employee = $current_employee ? $current_employee['external_employee_no'] : '';
}

// Get employee base salary directly from employee_refs table
$position_salary = 0;
if ($selected_employee && $current_employee && isset($current_employee['base_monthly_salary'])) {
    $position_salary = floatval($current_employee['base_monthly_salary']);
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

// Get payslip data for selected employee
$payslip_data = null;
if ($selected_employee) {
    $payslip_query = "SELECT ps.*, pr.run_at, pr.status as payroll_status 
                      FROM payslips ps 
                      JOIN payroll_runs pr ON ps.payroll_run_id = pr.id 
                      WHERE ps.employee_external_no = ? 
                      ORDER BY pr.run_at DESC 
                      LIMIT 1";
    $payslip_stmt = $conn->prepare($payslip_query);
    $payslip_stmt->bind_param("s", $selected_employee);
    $payslip_stmt->execute();
    $payslip_result = $payslip_stmt->get_result();
    $payslip_data = $payslip_result->fetch_assoc();
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
    if (preg_match('/EMP(\d+)/i', $selected_employee, $matches)) {
        $employee_id_from_external = intval($matches[1]);
    } else {
        // Try direct match if it's already a number
        $employee_id_from_external = is_numeric($selected_employee) ? intval($selected_employee) : null;
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
                $attendance_stmt->bind_param("issss", 
                    $employee_id_from_external, $period_start, $period_end,  // For HRIS attendance table
                    $selected_employee, $period_start, $period_end           // For employee_attendance table
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
                    <div class="row align-items-end g-3">
                        <div class="col-md-6">
                            <div class="employee-selector-header">
                                <label for="employee-select" class="form-label mb-1">Select Employee:</label>
                                <select class="form-select" id="employee-select" onchange="changeEmployee()">
                                    <option value="">Choose an employee...</option>
                                    <?php 
                                    $employees_result->data_seek(0);
                                    while($emp = $employees_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $emp['external_employee_no']; ?>" 
                                                <?php echo ($emp['external_employee_no'] == $selected_employee) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emp['name'] . ' (' . $emp['external_employee_no'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="payroll-period-selector">
                                <label for="payroll-period-select" class="form-label mb-1">Payroll Period:</label>
                                <div class="d-flex gap-2">
                                    <select class="form-select" id="payroll-month-select" onchange="changePayrollPeriod()">
                                        <?php
                                        // Always show current month first, then previous months
                                        $current_month = date('Y-m');
                                        $current_month_label = date('F Y');
                                        
                                        // Check if current month is selected (or no month selected, default to current)
                                        $is_current_selected = (empty($payroll_month) || $payroll_month == $current_month) ? 'selected' : '';
                                        
                                        // Show current month first
                                        echo "<option value=\"$current_month\" $is_current_selected>$current_month_label</option>";
                                        
                                        // Then show previous 5 months
                                        for ($i = 1; $i <= 5; $i++) {
                                            $month_date = date('Y-m', strtotime("-$i months"));
                                            $month_label = date('F Y', strtotime("-$i months"));
                                            // Only select if it's not the current month and matches the selected month
                                            $selected = ($payroll_month == $month_date && $payroll_month != $current_month) ? 'selected' : '';
                                            echo "<option value=\"$month_date\" $selected>$month_label</option>";
                                        }
                                        ?>
                                    </select>
                                    <select class="form-select" id="payroll-period-select" onchange="changePayrollPeriod()">
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
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="alert alert-info mb-0 py-2">
                                <i class="fas fa-calendar-check me-2"></i>
                                <strong>Selected Period:</strong> <?php echo htmlspecialchars($period_label); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Advanced Search and Filters -->
        <div class="search-filters-section mb-3">
            <div class="search-filters-card">
                <div class="filters-header">
                    <div class="filters-title-section">
                        <i class="fas fa-search me-2"></i>
                        <h5 class="mb-0">Search & Filter Employees</h5>
                        <div class="results-counter">
                            <span class="badge-employee-count">
                                <?php echo $employees_result->num_rows; ?> employee<?php echo $employees_result->num_rows != 1 ? 's' : ''; ?> found
                            </span>
                        </div>
                    </div>
                    <button class="btn-toggle-filters" onclick="toggleFilters()" type="button">
                        <i class="fas fa-filter me-1"></i>Filters
                        <i class="fas fa-chevron-down ms-1" id="filter-chevron"></i>
                    </button>
                </div>
                        
                        <div class="filters-content" id="filters-content">
                            <form method="GET" class="filters-form">
                                <input type="hidden" name="employee" value="<?php echo htmlspecialchars($selected_employee); ?>">
                                <div class="row g-3">
                                    <!-- Search Bar -->
                                    <div class="col-md-4">
                                        <label for="search" class="form-label">Search</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="search" name="search" 
                                                   placeholder="Search by name or employee number..." 
                                                   value="<?php echo htmlspecialchars($search_term); ?>">
                                        </div>
                                    </div>
                                    
                                    <!-- Position Filter -->
                                    <div class="col-md-2">
                                        <label for="position" class="form-label">Position</label>
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
                                        <label for="department" class="form-label">Department</label>
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
                                        <label for="type" class="form-label">Type</label>
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
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-search me-1"></i>Search
                                            </button>
                                            <a href="?" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-times me-1"></i>Clear
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
                    <button class="nav-link" id="payroll-info-tab" data-bs-toggle="pill" data-bs-target="#payroll-info" type="button" role="tab">
                        Payroll Information
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
                        // Calculate salary rates
                        $base_salary = floatval($current_employee['base_monthly_salary'] ?? 0);
                        $working_days_per_month = 22; // Standard Philippine working days
                        $hours_per_day = 8;
                        $daily_rate = $base_salary > 0 ? $base_salary / $working_days_per_month : 0;
                        $hourly_rate = $daily_rate > 0 ? $daily_rate / $hours_per_day : 0;
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
                                            <tr>
                                                <td>Date of Joining</td>
                                                <td><?php echo date('F d, Y', strtotime($current_employee['created_at'])); ?></td>
                                            </tr>
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

            <!-- PAYROLL INFORMATION TAB -->
            <div class="tab-pane fade" id="payroll-info" role="tabpanel">
                <div class="payroll-content-card">
                    <h3 class="payroll-section-title">Payroll Information</h3>
                    
                    <?php if ($payslip_data): ?>
                        <div class="payslip-header">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Pay Period:</strong> <?php echo date('F Y', strtotime($payslip_data['run_at'])); ?>
                                </div>
                                <div class="col-md-6 text-end">
                                    <span class="status-badge status-<?php echo $payslip_data['payroll_status']; ?>">
                                        <?php echo ucfirst($payslip_data['payroll_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Summary for Payroll Tab -->
                    <?php if ($selected_employee && $attendance_summary): ?>
                    <div class="mb-4">
                        <h5 class="section-subtitle mb-3">Attendance Summary - <?php echo $period_label ? htmlspecialchars($period_label) : date('F Y', strtotime($display_month . '-01')); ?></h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="attendance-summary-card present">
                                    <div class="summary-icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['present_days']; ?></div>
                                        <div class="summary-label">Present Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card absent">
                                    <div class="summary-icon"><i class="fas fa-times-circle"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['absent_days']; ?></div>
                                        <div class="summary-label">Absent Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card late">
                                    <div class="summary-icon"><i class="fas fa-clock"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['late_days']; ?></div>
                                        <div class="summary-label">Late Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card leave">
                                    <div class="summary-icon"><i class="fas fa-calendar-times"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['leave_days']; ?></div>
                                        <div class="summary-label">Leave Days</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="payroll-two-column">
                        <!-- Earnings Column -->
                        <div class="payroll-column-card">
                            <div class="payroll-column-title">Earnings</div>
                            <table class="payroll-items-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_earnings_total = 0;
                                    if ($earnings_result && $earnings_result->num_rows > 0): 
                                        $earnings_result->data_seek(0);
                                        while($earning = $earnings_result->fetch_assoc()): 
                                            // Use payslip JSON data if available for accurate Philippine calculations
                                            // Otherwise, apply attendance-based adjustments
                                            $amount = 0;
                                            if ($payslip_data && $payslip_data['payslip_json']) {
                                                $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                                switch($earning['code']) {
                                                    case 'BASIC': $amount = $payslip_json['basic_salary'] ?? $earning['value']; break;
                                                    case 'COLA': $amount = $payslip_json['cola'] ?? $earning['value']; break;
                                                    case 'MEAL': $amount = $payslip_json['meal_allowance'] ?? $earning['value']; break;
                                                    case 'COMM': $amount = $payslip_json['comm_allowance'] ?? $earning['value']; break;
                                                    case 'RICE': $amount = $payslip_json['rice_subsidy'] ?? $earning['value']; break;
                                                    case 'TRANSPORT': $amount = $payslip_json['transport_allowance'] ?? $earning['value']; break;
                                                    default: $amount = $earning['value']; break;
                                                }
                                            } else {
                                                // Apply position-based salary and attendance adjustments for basic salary
                                                if ($earning['code'] === 'BASIC') {
                                                    // Use position-based salary if available, otherwise use component value
                                                    $base_amount = ($position_salary > 0) ? $position_salary : $earning['value'];
                                                    
                                                    // Apply attendance-based adjustments if available
                                                    if ($attendance_payroll_adjustments) {
                                                        // If adjusted salary is calculated, use it; otherwise use base
                                                        $adj_salary = $attendance_payroll_adjustments['salary_adjustments']['adjusted_salary'];
                                                        $amount = ($adj_salary > 0) ? $adj_salary : $base_amount;
                                                    } else {
                                                        $amount = $base_amount;
                                                    }
                                                } else {
                                                    $amount = $earning['value'];
                                                }
                                            }
                                            
                                            // Add overtime pay if this is the overtime component
                                            if ($earning['code'] === 'OVERTIME' && $attendance_payroll_adjustments) {
                                                $amount = $attendance_payroll_adjustments['salary_adjustments']['overtime_pay'];
                                            }
                                            
                                            $current_earnings_total += $amount;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($earning['name']); ?></td>
                                            <td class="amount-cell">₱<?php echo number_format($amount, 2); ?></td>
                                        </tr>
                                    <?php 
                                        endwhile; 
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No earnings data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="payroll-total-row">
                                        <td><strong>Gross Earnings</strong></td>
                                        <td class="amount-cell"><strong>₱<?php echo number_format($current_earnings_total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Deductions Column -->
                        <div class="payroll-column-card">
                            <div class="payroll-column-title">Deductions</div>
                            <table class="payroll-items-table">
                                <thead>
                                    <tr>
                                        <th>Particulars</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_deductions_total = 0;
                                    
                                    // Add attendance-based deductions first (before regular deductions)
                                    if ($attendance_payroll_adjustments && !$payslip_data): 
                                        $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                        
                                        // Absent days deduction
                                        if ($adj['absent_deduction'] > 0): ?>
                                        <tr>
                                            <td>Absent Days Deduction</td>
                                            <td class="amount-cell">₱<?php echo number_format($adj['absent_deduction'], 2); ?></td>
                                        </tr>
                                    <?php 
                                        $current_deductions_total += $adj['absent_deduction'];
                                        endif;
                                        
                                        // Half day deduction
                                        if ($adj['half_day_deduction'] > 0): ?>
                                        <tr>
                                            <td>Half Day Deduction</td>
                                            <td class="amount-cell">₱<?php echo number_format($adj['half_day_deduction'], 2); ?></td>
                                        </tr>
                                    <?php 
                                        $current_deductions_total += $adj['half_day_deduction'];
                                        endif;
                                        
                                        // Late penalty
                                        if ($adj['late_penalty'] > 0): ?>
                                        <tr>
                                            <td>Late Arrival Penalty</td>
                                            <td class="amount-cell">₱<?php echo number_format($adj['late_penalty'], 2); ?></td>
                                        </tr>
                                    <?php 
                                        $current_deductions_total += $adj['late_penalty'];
                                        endif;
                                    endif;
                                    
                                    // Regular deductions
                                    if ($deductions_result && $deductions_result->num_rows > 0): 
                                        $deductions_result->data_seek(0);
                                        while($deduction = $deductions_result->fetch_assoc()): 
                                            // Use payslip JSON data if available for accurate Philippine calculations
                                            $amount = 0;
                                            if ($payslip_data && $payslip_data['payslip_json']) {
                                                $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                                switch($deduction['code']) {
                                                    case 'SSS_EMP': $amount = $payslip_json['sss_emp'] ?? $deduction['value']; break;
                                                    case 'PAGIBIG_EMP': $amount = $payslip_json['pagibig_emp'] ?? $deduction['value']; break;
                                                    case 'PHILHEALTH_EMP': $amount = $payslip_json['philhealth_emp'] ?? $deduction['value']; break;
                                                    case 'WHT': $amount = $payslip_json['withholding_tax'] ?? $deduction['value']; break;
                                                    case 'LOAN': $amount = $payslip_json['loan_deduction'] ?? $deduction['value']; break;
                                                    case 'UNIFORM': $amount = $payslip_json['uniform_deduction'] ?? $deduction['value']; break;
                                                    default: $amount = $deduction['value']; break;
                                                }
                                            } else {
                                                $amount = $deduction['value'];
                                            }
                                            $current_deductions_total += $amount;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($deduction['name']); ?></td>
                                            <td class="amount-cell">₱<?php echo number_format($amount, 2); ?></td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">No deductions data available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="payroll-total-row">
                                        <td><strong>Total Deductions</strong></td>
                                        <td class="amount-cell"><strong>₱<?php echo number_format($current_deductions_total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Net Salary -->
                    <div class="net-salary-box">
                        <div class="label">Net Salary:</div>
                        <div class="amount">₱<?php echo number_format($current_earnings_total - $current_deductions_total, 2); ?></div>
                    </div>
                    
                    <!-- Recent Payslips -->
                    <?php if ($recent_payslips_result && $recent_payslips_result->num_rows > 0): ?>
                        <div class="recent-payslips-section">
                            <h5 class="section-subtitle">Recent Payslips</h5>
                            <div class="table-container">
                                <table class="history-table">
                                    <thead>
                                        <tr>
                                            <th>Pay Period</th>
                                            <th>Gross Pay</th>
                                            <th>Deductions</th>
                                            <th>Net Pay</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recent_payslips_result->data_seek(0);
                                        while($payslip = $recent_payslips_result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo date('M Y', strtotime($payslip['run_at'])); ?></td>
                                                <td>₱<?php echo number_format($payslip['gross_pay'], 2); ?></td>
                                                <td>₱<?php echo number_format($payslip['total_deductions'], 2); ?></td>
                                                <td>₱<?php echo number_format($payslip['net_pay'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payslip['payroll_status']; ?>">
                                                        <?php echo ucfirst($payslip['payroll_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAX MANAGEMENT TAB -->
            <div class="tab-pane fade" id="tax-mgmt" role="tabpanel">
                <div class="payroll-content-card">
                    <h3 class="payroll-section-title">Tax Details</h3>
                    
                    <?php if ($payslip_data): ?>
                        <div class="tax-period-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tax Period:</strong> <?php echo date('F Y', strtotime($payslip_data['run_at'])); ?>
                                </div>
                                <div class="col-md-6 text-end">
                                    <strong>Employee:</strong> <?php echo htmlspecialchars($current_employee['name']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Summary for Tax Tab -->
                    <?php if ($selected_employee && $attendance_summary): ?>
                    <div class="mb-4">
                        <h5 class="section-subtitle mb-3">Attendance Summary - <?php echo $period_label ? htmlspecialchars($period_label) : date('F Y', strtotime($display_month . '-01')); ?></h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="attendance-summary-card present">
                                    <div class="summary-icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['present_days']; ?></div>
                                        <div class="summary-label">Present Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card absent">
                                    <div class="summary-icon"><i class="fas fa-times-circle"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['absent_days']; ?></div>
                                        <div class="summary-label">Absent Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card late">
                                    <div class="summary-icon"><i class="fas fa-clock"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['late_days']; ?></div>
                                        <div class="summary-label">Late Days</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="attendance-summary-card leave">
                                    <div class="summary-icon"><i class="fas fa-calendar-times"></i></div>
                                    <div class="summary-content">
                                        <div class="summary-number"><?php echo $attendance_summary['leave_days']; ?></div>
                                        <div class="summary-label">Leave Days</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="tax-details-container">
                        <!-- Tax Deductions -->
                        <div class="tax-section">
                            <div class="tax-section-header">Employee Tax Contributions</div>
                            <table class="tax-items-table">
                                <?php 
                                $employee_tax_total = 0;
                                if ($tax_result && $tax_result->num_rows > 0): 
                                    $tax_result->data_seek(0);
                                    while($tax = $tax_result->fetch_assoc()): 
                                        // Use payslip JSON data if available for accurate Philippine calculations
                                        $tax_amount = 0;
                                        if ($payslip_data && $payslip_data['payslip_json']) {
                                            $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                            switch($tax['code']) {
                                                case 'SSS_TAX': $tax_amount = $payslip_json['sss_emp'] ?? $tax['value']; break;
                                                case 'PAGIBIG_TAX': $tax_amount = $payslip_json['pagibig_emp'] ?? $tax['value']; break;
                                                case 'PHILHEALTH_TAX': $tax_amount = $payslip_json['philhealth_emp'] ?? $tax['value']; break;
                                                case 'WHT_TAX': $tax_amount = $payslip_json['withholding_tax'] ?? $tax['value']; break;
                                                default: $tax_amount = $tax['value']; break;
                                            }
                                        } else {
                                            $tax_amount = $tax['value'];
                                        }
                                        $employee_tax_total += $tax_amount;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tax['name']); ?></td>
                                        <td>₱<?php echo number_format($tax_amount, 2); ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No tax data available</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="tax-total-row">
                                    <td><strong>Total Employee Tax</strong></td>
                                    <td><strong>₱<?php echo number_format($employee_tax_total, 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Employer Contributions -->
                        <div class="tax-section">
                            <div class="tax-section-header">Employer Contribution</div>
                            <table class="tax-items-table">
                                <?php 
                                $employer_total = 0;
                                if ($employer_contrib_result && $employer_contrib_result->num_rows > 0): 
                                    $employer_contrib_result->data_seek(0);
                                    while($contrib = $employer_contrib_result->fetch_assoc()): 
                                        // Calculate actual employer contribution based on Philippine rates
                                        $contrib_amount = 0;
                                        if ($payslip_data && $payslip_data['payslip_json']) {
                                            $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                            $basic_salary = $payslip_json['basic_salary'] ?? 0;
                                            switch($contrib['code']) {
                                                case 'SSS_ER': $contrib_amount = $basic_salary * 0.085; break; // 8.5% of basic
                                                case 'PAGIBIG_ER': $contrib_amount = 100; break; // Fixed ₱100
                                                case 'PHILHEALTH_ER': $contrib_amount = $basic_salary * 0.03; break; // 3% of basic
                                                case 'SSS_EC': $contrib_amount = 10; break; // Fixed ₱10
                                                case '13TH_MONTH_ER': $contrib_amount = $basic_salary * 0.0833; break; // 8.33% of basic
                                                default: $contrib_amount = $contrib['value']; break;
                                            }
                                        } else {
                                            $contrib_amount = $contrib['value'];
                                        }
                                        $employer_total += $contrib_amount;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contrib['name']); ?></td>
                                        <td>₱<?php echo number_format($contrib_amount, 2); ?></td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No employer contribution data available</td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="tax-total-row">
                                    <td><strong>Total Employer Contribution</strong></td>
                                    <td><strong>₱<?php echo number_format($employer_total, 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Tax Summary -->
                        <div class="tax-summary-box">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="tax-summary-item">
                                        <span class="label">Employee Tax:</span>
                                        <span class="value">₱<?php echo number_format($employee_tax_total, 2); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tax-summary-item">
                                        <span class="label">Employer Contribution:</span>
                                        <span class="value">₱<?php echo number_format($employer_total, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="tax-summary-total">
                                <span class="label">Total Tax Burden:</span>
                                <span class="value">₱<?php echo number_format($employee_tax_total + $employer_total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- OVERALL TAB -->
            <div class="tab-pane fade" id="overall" role="tabpanel">
                <div class="payroll-content-card">
                    <h3 class="payroll-section-title">Payroll Complete Details</h3>
                    
                    <!-- Company Header -->
                    <div class="overall-header">
                        <div class="bank-name"><?php echo htmlspecialchars($company_bank['bank_name'] ?? 'BANK NAME'); ?></div>
                        <div class="company-name">(<?php echo htmlspecialchars($company_bank['name'] ?? 'Company Name'); ?>)</div>
                        <div class="company-address">Company Address</div>
                    </div>

                    <!-- Employee Details Section -->
                    <div class="overall-section">
                        <div class="overall-section-title">Employee Details</div>
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
                                <tr>
                                    <td>Bank Name</td>
                                    <td><?php echo htmlspecialchars($company_bank['bank_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td>Bank Account Number</td>
                                    <td><?php echo htmlspecialchars($company_bank['account_number'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td>Date of Joining</td>
                                    <td><?php echo date('m/d/Y', strtotime($employee['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td>Payout Date</td>
                                    <td><?php echo date('m/d/Y'); ?></td>
                                </tr>
                                <tr>
                                    <td>Position</td>
                                    <td><?php echo htmlspecialchars($employee['position'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                            
                            <!-- Attendance Summary for Overall Tab - Hidden in print -->
                            <?php if ($selected_employee && $attendance_summary): ?>
                            <div class="mt-4 no-print">
                                <div class="overall-section-title mb-3">Attendance Summary - <?php echo $period_label ? htmlspecialchars($period_label) : date('F Y', strtotime($display_month . '-01')); ?></div>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="attendance-summary-card present">
                                            <div class="summary-icon"><i class="fas fa-check-circle"></i></div>
                                            <div class="summary-content">
                                                <div class="summary-number"><?php echo $attendance_summary['present_days']; ?></div>
                                                <div class="summary-label">Present Days</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="attendance-summary-card absent">
                                            <div class="summary-icon"><i class="fas fa-times-circle"></i></div>
                                            <div class="summary-content">
                                                <div class="summary-number"><?php echo $attendance_summary['absent_days']; ?></div>
                                                <div class="summary-label">Absent Days</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="attendance-summary-card late">
                                            <div class="summary-icon"><i class="fas fa-clock"></i></div>
                                            <div class="summary-content">
                                                <div class="summary-number"><?php echo $attendance_summary['late_days']; ?></div>
                                                <div class="summary-label">Late Days</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="attendance-summary-card leave">
                                            <div class="summary-icon"><i class="fas fa-calendar-times"></i></div>
                                            <div class="summary-content">
                                                <div class="summary-number"><?php echo $attendance_summary['leave_days']; ?></div>
                                                <div class="summary-label">Leave Days</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
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
                                        $earnings_result->data_seek(0); // Reset pointer
                                        $total_earnings_overall = 0;
                                        if ($earnings_result && $earnings_result->num_rows > 0): 
                                            while($earning = $earnings_result->fetch_assoc()): 
                                                // Use same logic as Payroll Information tab
                                                $amount = 0;
                                                if ($payslip_data && $payslip_data['payslip_json']) {
                                                    $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                                    switch($earning['code']) {
                                                        case 'BASIC': $amount = $payslip_json['basic_salary'] ?? $earning['value']; break;
                                                        case 'COLA': $amount = $payslip_json['cola'] ?? $earning['value']; break;
                                                        case 'MEAL': $amount = $payslip_json['meal_allowance'] ?? $earning['value']; break;
                                                        case 'COMM': $amount = $payslip_json['comm_allowance'] ?? $earning['value']; break;
                                                        case 'RICE': $amount = $payslip_json['rice_subsidy'] ?? $earning['value']; break;
                                                        case 'TRANSPORT': $amount = $payslip_json['transport_allowance'] ?? $earning['value']; break;
                                                        default: $amount = $earning['value']; break;
                                                    }
                                                } else {
                                                    // Apply position-based salary and attendance adjustments for basic salary
                                                    if ($earning['code'] === 'BASIC') {
                                                        // Use position-based salary if available, otherwise use component value
                                                        $base_amount = ($position_salary > 0) ? $position_salary : $earning['value'];
                                                        
                                                        // Apply attendance-based adjustments if available
                                                        if ($attendance_payroll_adjustments) {
                                                            // If adjusted salary is calculated, use it; otherwise use base
                                                            $adj_salary = $attendance_payroll_adjustments['salary_adjustments']['adjusted_salary'];
                                                            $amount = ($adj_salary > 0) ? $adj_salary : $base_amount;
                                                        } else {
                                                            $amount = $base_amount;
                                                        }
                                                    } else {
                                                        $amount = $earning['value'];
                                                    }
                                                }
                                                
                                                // Add overtime pay if this is the overtime component
                                                if ($earning['code'] === 'OVERTIME' && $attendance_payroll_adjustments) {
                                                    $amount = $attendance_payroll_adjustments['salary_adjustments']['overtime_pay'];
                                                }
                                                
                                                $total_earnings_overall += $amount;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($earning['name']); ?></td>
                                                <td class="amount-cell">₱<?php echo number_format($amount, 2); ?></td>
                                            </tr>
                                        <?php endwhile; endif; ?>
                                    </tbody>
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
                                        
                                        // Add attendance-based deductions first (before regular deductions)
                                        if ($attendance_payroll_adjustments && !$payslip_data): 
                                            $adj = $attendance_payroll_adjustments['salary_adjustments'];
                                            
                                            // Absent days deduction
                                            if ($adj['absent_deduction'] > 0): ?>
                                            <tr>
                                                <td>Absent Days Deduction</td>
                                                <td class="amount-cell">₱<?php echo number_format($adj['absent_deduction'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            $total_deductions_overall += $adj['absent_deduction'];
                                            endif;
                                            
                                            // Half day deduction
                                            if ($adj['half_day_deduction'] > 0): ?>
                                            <tr>
                                                <td>Half Day Deduction</td>
                                                <td class="amount-cell">₱<?php echo number_format($adj['half_day_deduction'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            $total_deductions_overall += $adj['half_day_deduction'];
                                            endif;
                                            
                                            // Late penalty
                                            if ($adj['late_penalty'] > 0): ?>
                                            <tr>
                                                <td>Late Arrival Penalty</td>
                                                <td class="amount-cell">₱<?php echo number_format($adj['late_penalty'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            $total_deductions_overall += $adj['late_penalty'];
                                            endif;
                                        endif;
                                        
                                        // Regular deductions
                                        if ($deductions_result && $deductions_result->num_rows > 0): 
                                            while($deduction = $deductions_result->fetch_assoc()): 
                                                // Use same logic as Payroll Information tab
                                                $amount = 0;
                                                if ($payslip_data && $payslip_data['payslip_json']) {
                                                    $payslip_json = json_decode($payslip_data['payslip_json'], true);
                                                    switch($deduction['code']) {
                                                        case 'SSS_EMP': $amount = $payslip_json['sss_emp'] ?? $deduction['value']; break;
                                                        case 'PAGIBIG_EMP': $amount = $payslip_json['pagibig_emp'] ?? $deduction['value']; break;
                                                        case 'PHILHEALTH_EMP': $amount = $payslip_json['philhealth_emp'] ?? $deduction['value']; break;
                                                        case 'WHT': $amount = $payslip_json['withholding_tax'] ?? $deduction['value']; break;
                                                        case 'LOAN': $amount = $payslip_json['loan_deduction'] ?? $deduction['value']; break;
                                                        case 'UNIFORM': $amount = $payslip_json['uniform_deduction'] ?? $deduction['value']; break;
                                                        default: $amount = $deduction['value']; break;
                                                    }
                                                } else {
                                                    $amount = $deduction['value'];
                                                }
                                                $total_deductions_overall += $amount;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($deduction['name']); ?></td>
                                                <td class="amount-cell">₱<?php echo number_format($amount, 2); ?></td>
                                            </tr>
                                        <?php endwhile; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Employer Contribution -->
                    <div class="overall-section">
                        <div class="overall-section-title">Employer Contribution</div>
                        <table class="tax-items-table">
                            <?php 
                            $employer_contrib_result->data_seek(0); // Reset pointer
                            if ($employer_contrib_result && $employer_contrib_result->num_rows > 0): 
                                while($contrib = $employer_contrib_result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contrib['name']); ?></td>
                                    <td>₱<?php echo number_format($contrib['value'], 2); ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="overall-summary-box">
                        <div class="overall-summary-row">
                            <span class="label">Gross Earnings:</span>
                            <span class="value">₱<?php echo number_format($total_earnings_overall, 2); ?></span>
                        </div>
                        <div class="overall-summary-row">
                            <span class="label">Total Deductions:</span>
                            <span class="value">₱<?php echo number_format($total_deductions_overall, 2); ?></span>
                        </div>
                        <div class="overall-summary-row">
                            <span class="label">Net Salary:</span>
                            <span class="value">₱<?php echo number_format($total_earnings_overall - $total_deductions_overall, 2); ?></span>
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
