<?php
/**
 * Payroll Calculation API
 * Calculates payroll based on attendance data
 * 
 * Note: Functions receive $conn as parameter, so database.php is only required
 * when this file is used as a standalone API endpoint
 */

// Functions don't require database connection at include time
// They receive $conn as a parameter when called

/**
 * Calculate payroll for an employee based on attendance
 * 
 * @param mysqli $conn Database connection
 * @param string $employee_external_no Employee external number
 * @param string $period_start Period start date (YYYY-MM-DD)
 * @param string $period_end Period end date (YYYY-MM-DD)
 * @param array $base_salary_components Base salary components array
 * @return array Calculated payroll data
 */
function calculatePayrollFromAttendance($conn, $employee_external_no, $period_start, $period_end, $base_salary_components = []) {
    
    // Get attendance data for the period
    $attendance_query = "SELECT 
                            attendance_date,
                            status,
                            hours_worked,
                            overtime_hours,
                            late_minutes,
                            remarks
                        FROM employee_attendance 
                        WHERE employee_external_no = ? 
                        AND attendance_date BETWEEN ? AND ?
                        ORDER BY attendance_date ASC";
    
    $stmt = $conn->prepare($attendance_query);
    $stmt->bind_param("sss", $employee_external_no, $period_start, $period_end);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    
    // Initialize calculation results
    $calculation = [
        'attendance_summary' => [
            'total_days' => 0,
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
            'leave_days' => 0,
            'half_day_days' => 0,
            'total_hours' => 0,
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'total_late_minutes' => 0
        ],
        'salary_adjustments' => [
            'basic_salary' => 0,
            'absent_deduction' => 0,
            'half_day_deduction' => 0,
            'late_penalty' => 0,
            'overtime_pay' => 0,
            'adjusted_salary' => 0
        ],
        'attendance_records' => []
    ];
    
    // Process attendance records
    $daily_rate = 0;
    $hourly_rate = 0;
    $base_salary = 0;
    
    // Get employee base salary directly from employee_refs table
    $employee_data = null;
    
    // Try prepared statement first
    $employee_query = "SELECT base_monthly_salary FROM employee_refs WHERE external_employee_no = ? LIMIT 1";
    $emp_stmt = $conn->prepare($employee_query);
    
    if ($emp_stmt === false) {
        // If prepare fails (likely column doesn't exist or SQL error), use fallback query
        $escaped_emp_no = $conn->real_escape_string($employee_external_no);
        $fallback_query = "SELECT base_monthly_salary FROM employee_refs WHERE external_employee_no = '$escaped_emp_no' LIMIT 1";
        $fallback_result = $conn->query($fallback_query);
        
        if ($fallback_result === false) {
            // If that also fails, try SELECT * to check if table exists at all
            $fallback_query2 = "SELECT * FROM employee_refs WHERE external_employee_no = '$escaped_emp_no' LIMIT 1";
            $fallback_result2 = $conn->query($fallback_query2);
            $employee_data = $fallback_result2 ? $fallback_result2->fetch_assoc() : null;
        } else {
            $employee_data = $fallback_result->fetch_assoc();
        }
    } else {
        $emp_stmt->bind_param("s", $employee_external_no);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee_data = $emp_result->fetch_assoc();
        $emp_stmt->close();
    }
    
    // Get base salary from employee_refs table (Philippine market rates)
    if ($employee_data && isset($employee_data['base_monthly_salary'])) {
        $base_salary = floatval($employee_data['base_monthly_salary']);
    }
    
    // Fallback to base salary components if employee salary not found
    if ($base_salary == 0 && !empty($base_salary_components)) {
        foreach ($base_salary_components as $component) {
            if (isset($component['code']) && $component['code'] === 'BASIC') {
                $base_salary = floatval($component['value'] ?? 0);
                break;
            }
        }
    }
    
    // Calculate daily and hourly rates (assuming 20-22 working days per month, 8 hours per day)
    $working_days_per_month = 22; // Standard Philippine working days
    $hours_per_day = 8;
    
    // Calculate period duration to determine if it's a bi-monthly period
    $start_date = new DateTime($period_start);
    $end_date = new DateTime($period_end);
    $period_days = $start_date->diff($end_date)->days + 1; // +1 to include both start and end dates
    
    // For bi-monthly periods (approximately 15 days), prorate the base salary
    $prorated_base_salary = $base_salary;
    if ($period_days <= 16) {
        // This is likely a bi-monthly period (first or second half)
        // Prorate based on actual period days vs full month
        $days_in_month = (int)$end_date->format('t'); // Last day of the month
        $prorated_base_salary = ($base_salary / $days_in_month) * $period_days;
    }
    
    if ($prorated_base_salary > 0) {
        // Calculate daily rate based on prorated salary and period days
        $daily_rate = $prorated_base_salary / $period_days;
        $hourly_rate = $daily_rate / $hours_per_day;
    } else if ($base_salary > 0) {
        // Fallback to monthly calculation
        $daily_rate = $base_salary / $working_days_per_month;
        $hourly_rate = $daily_rate / $hours_per_day;
    }
    
    // Overtime rate is 125% of hourly rate (Philippine standard)
    $overtime_rate = $hourly_rate * 1.25;
    
    // Late penalty: deduct 1% of daily rate for every 15 minutes late (or customize as needed)
    $late_penalty_per_15min = $daily_rate * 0.01;
    
    // Process each attendance record
    while ($row = $attendance_result->fetch_assoc()) {
        $calculation['attendance_records'][] = $row;
        $calculation['attendance_summary']['total_days']++;
        
        $status = $row['status'];
        $hours_worked = floatval($row['hours_worked'] ?? 0);
        $overtime_hours = floatval($row['overtime_hours'] ?? 0);
        $late_minutes = intval($row['late_minutes'] ?? 0);
        
        // Calculate based on attendance status
        switch ($status) {
            case 'present':
                $calculation['attendance_summary']['present_days']++;
                $calculation['attendance_summary']['regular_hours'] += $hours_worked;
                $calculation['attendance_summary']['total_hours'] += $hours_worked;
                
                // Full day pay
                $calculation['salary_adjustments']['basic_salary'] += $daily_rate;
                
                // Add overtime pay
                if ($overtime_hours > 0) {
                    $calculation['attendance_summary']['overtime_hours'] += $overtime_hours;
                    $calculation['salary_adjustments']['overtime_pay'] += $overtime_hours * $overtime_rate;
                }
                
                // Late penalty
                if ($late_minutes > 0) {
                    $calculation['attendance_summary']['late_days']++;
                    $calculation['attendance_summary']['total_late_minutes'] += $late_minutes;
                    $penalty_units = ceil($late_minutes / 15); // Every 15 minutes = 1 penalty unit
                    $late_penalty = $penalty_units * $late_penalty_per_15min;
                    $calculation['salary_adjustments']['late_penalty'] += $late_penalty;
                }
                break;
                
            case 'late':
                $calculation['attendance_summary']['present_days']++;
                $calculation['attendance_summary']['late_days']++;
                $calculation['attendance_summary']['regular_hours'] += $hours_worked;
                $calculation['attendance_summary']['total_hours'] += $hours_worked;
                $calculation['attendance_summary']['total_late_minutes'] += $late_minutes;
                
                // Full day pay (but with late penalty)
                $calculation['salary_adjustments']['basic_salary'] += $daily_rate;
                
                // Late penalty
                if ($late_minutes > 0) {
                    $penalty_units = ceil($late_minutes / 15);
                    $late_penalty = $penalty_units * $late_penalty_per_15min;
                    $calculation['salary_adjustments']['late_penalty'] += $late_penalty;
                }
                
                // Add overtime pay
                if ($overtime_hours > 0) {
                    $calculation['attendance_summary']['overtime_hours'] += $overtime_hours;
                    $calculation['salary_adjustments']['overtime_pay'] += $overtime_hours * $overtime_rate;
                }
                break;
                
            case 'half_day':
                $calculation['attendance_summary']['present_days']++;
                $calculation['attendance_summary']['half_day_days']++;
                $calculation['attendance_summary']['regular_hours'] += $hours_worked;
                $calculation['attendance_summary']['total_hours'] += $hours_worked;
                
                // Half day pay (50% of daily rate)
                $half_day_pay = $daily_rate * 0.5;
                $calculation['salary_adjustments']['basic_salary'] += $half_day_pay;
                $calculation['salary_adjustments']['half_day_deduction'] += $daily_rate * 0.5;
                break;
                
            case 'absent':
                $calculation['attendance_summary']['absent_days']++;
                
                // No pay for absent days (unless it's paid leave, which should be handled separately)
                $calculation['salary_adjustments']['absent_deduction'] += $daily_rate;
                break;
                
            case 'leave':
                $calculation['attendance_summary']['leave_days']++;
                
                // Check if it's paid leave (you may want to add a leave_type field)
                // For now, we'll assume leave is unpaid unless specified otherwise
                // You can modify this based on your company policy
                $calculation['salary_adjustments']['absent_deduction'] += $daily_rate;
                break;
        }
    }
    
    $stmt->close();
    
    // Calculate adjusted salary
    $calculation['salary_adjustments']['basic_salary'] = round($calculation['salary_adjustments']['basic_salary'], 2);
    $calculation['salary_adjustments']['absent_deduction'] = round($calculation['salary_adjustments']['absent_deduction'], 2);
    $calculation['salary_adjustments']['half_day_deduction'] = round($calculation['salary_adjustments']['half_day_deduction'], 2);
    $calculation['salary_adjustments']['late_penalty'] = round($calculation['salary_adjustments']['late_penalty'], 2);
    $calculation['salary_adjustments']['overtime_pay'] = round($calculation['salary_adjustments']['overtime_pay'], 2);
    
    // Adjusted salary = Base salary - absent deductions - half day deductions - late penalties + overtime
    // Use prorated base salary for bi-monthly periods
    $base_salary_for_calculation = isset($prorated_base_salary) ? $prorated_base_salary : $base_salary;
    $expected_days = $calculation['attendance_summary']['total_days'];
    
    if ($expected_days > 0) {
        // Pro-rated based on attendance
        $attendance_rate = $calculation['attendance_summary']['present_days'] / $expected_days;
        $adjusted_base = $base_salary_for_calculation * $attendance_rate;
        
        // Apply adjustments
        $calculation['salary_adjustments']['adjusted_salary'] = round(
            $adjusted_base 
            - $calculation['salary_adjustments']['absent_deduction']
            - $calculation['salary_adjustments']['half_day_deduction'] 
            - $calculation['salary_adjustments']['late_penalty']
            + $calculation['salary_adjustments']['overtime_pay']
        , 2);
    } else {
        // If no attendance days, use prorated base salary as starting point
        $calculation['salary_adjustments']['adjusted_salary'] = round($base_salary_for_calculation, 2);
    }
    
    // Store the prorated base salary for reference
    $calculation['salary_adjustments']['prorated_base_salary'] = round($base_salary_for_calculation, 2);
    
    return $calculation;
}

/**
 * Get payroll calculation summary for display
 */
function getPayrollCalculationSummary($conn, $employee_external_no, $period_start, $period_end) {
    
    // Get base salary components for the employee
    $salary_query = "SELECT * FROM salary_components WHERE type = 'earning' AND is_active = 1 ORDER BY name";
    $salary_result = $conn->query($salary_query);
    $base_components = [];
    
    if ($salary_result) {
        while ($component = $salary_result->fetch_assoc()) {
            $base_components[] = $component;
        }
    }
    
    // Calculate payroll
    $calculation = calculatePayrollFromAttendance($conn, $employee_external_no, $period_start, $period_end, $base_components);
    
    return $calculation;
}

/**
 * API Endpoint for AJAX requests
 * Only loads database.php when used as standalone API endpoint
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($conn)) {
    // Load database connection for standalone API usage
    $db_paths = [
        __DIR__ . '/../../config/database.php',
        dirname(__DIR__) . '/../config/database.php',
        '../../config/database.php'
    ];
    
    foreach ($db_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($conn)) {
        echo json_encode([
            'success' => false,
            'error' => 'Database connection not available'
        ]);
        exit;
    }
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'calculate_payroll':
            if (isset($_POST['employee_no']) && isset($_POST['period_start']) && isset($_POST['period_end'])) {
                $employee_no = $_POST['employee_no'];
                $period_start = $_POST['period_start'];
                $period_end = $_POST['period_end'];
                
                $calculation = getPayrollCalculationSummary($conn, $employee_no, $period_start, $period_end);
                
                echo json_encode([
                    'success' => true,
                    'data' => $calculation
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required parameters'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
    
    exit;
}

