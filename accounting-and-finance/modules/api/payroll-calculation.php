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
    
    // Get employee_id from external_employee_no (format: EMP001 -> 1, EMP002 -> 2, etc.)
    $employee_id_from_external = null;
    if (preg_match('/EMP(\d+)/i', $employee_external_no, $matches)) {
        $employee_id_from_external = intval($matches[1]);
    } else {
        $employee_id_from_external = is_numeric($employee_external_no) ? intval($employee_external_no) : null;
    }
    
    // Get attendance data for the period from BOTH HRIS attendance AND employee_attendance tables
    // This combines data from both sources using UNION ALL
    $attendance_query = "SELECT * FROM (
                            -- From HRIS attendance table (uses employee_id)
                            SELECT 
                                DATE(a.date) as attendance_date,
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
                                CASE 
                                    WHEN COALESCE(a.total_hours, 
                                        CASE 
                                            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, a.time_out) + (TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) % 60) / 60.0
                                            ELSE 0.00
                                        END
                                    ) > 8.0 
                                    THEN COALESCE(a.total_hours, 
                                        CASE 
                                            WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL 
                                            THEN TIMESTAMPDIFF(HOUR, a.time_in, a.time_out) + (TIMESTAMPDIFF(MINUTE, a.time_in, a.time_out) % 60) / 60.0
                                            ELSE 0.00
                                        END
                                    ) - 8.0
                                    ELSE 0.00
                                END as overtime_hours,
                                CASE 
                                    WHEN TIME(a.time_in) > '08:00:00' AND TIME(a.time_in) <= '09:00:00'
                                    THEN TIMESTAMPDIFF(MINUTE, '08:00:00', TIME(a.time_in))
                                    WHEN LOWER(a.status) = 'late'
                                    THEN COALESCE(TIMESTAMPDIFF(MINUTE, '08:00:00', TIME(a.time_in)), 30)
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
                                ea.attendance_date,
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
                        ORDER BY attendance_date ASC";
    
    // Prepare and execute query
    if ($employee_id_from_external) {
        $stmt = $conn->prepare($attendance_query);
        if ($stmt) {
            // Bind parameters: employee_id (for HRIS), period dates, employee_external_no (for accounting), period dates
            $stmt->bind_param("isssss", 
                $employee_id_from_external, $period_start, $period_end,  // For HRIS attendance table (i, s, s)
                $employee_external_no, $period_start, $period_end        // For employee_attendance table (s, s, s)
            );
            $stmt->execute();
            $attendance_result = $stmt->get_result();
        } else {
            // Fallback to accounting table only if HRIS query fails
            $fallback_query = "SELECT 
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
            $stmt = $conn->prepare($fallback_query);
            $stmt->bind_param("sss", $employee_external_no, $period_start, $period_end);
            $stmt->execute();
            $attendance_result = $stmt->get_result();
        }
    } else {
        // Fallback to accounting table only if employee_id cannot be extracted
        $fallback_query = "SELECT 
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
        $stmt = $conn->prepare($fallback_query);
    $stmt->bind_param("sss", $employee_external_no, $period_start, $period_end);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    }
    
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
    
    // Fetch leave requests from HRIS and merge with attendance data
    $leave_attendance_records = [];
    if ($employee_id_from_external) {
        // Get approved leave requests for the selected period
        $leave_query = "SELECT 
                            lr.leave_request_id,
                            lr.start_date,
                            lr.end_date,
                            lr.total_days,
                            lr.reason,
                            lt.leave_name,
                            lt.paid_unpaid
                        FROM leave_request lr
                        LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
                        WHERE lr.employee_id = ?
                        AND UPPER(TRIM(lr.status)) = 'APPROVED'";
        
        $leave_params = [];
        $leave_types = "";
        
        // For period-based: check if leave overlaps with period
        $leave_query .= " AND (
                            (lr.start_date <= ? AND lr.end_date >= ?)
                            OR (lr.start_date BETWEEN ? AND ?)
                            OR (lr.end_date BETWEEN ? AND ?)
                        )";
        $leave_params = [$employee_id_from_external, $period_end, $period_start, $period_start, $period_end, $period_start, $period_end];
        $leave_types = "issssss"; // 1 integer + 6 strings = 7 parameters
        
        $leave_stmt = $conn->prepare($leave_query);
        if ($leave_stmt) {
            $leave_stmt->bind_param($leave_types, ...$leave_params);
            $leave_stmt->execute();
            $leave_result = $leave_stmt->get_result();
            
            // Create a map of existing attendance dates to avoid duplicates
            $attendance_dates_map = [];
            $temp_attendance_data = [];
            while ($temp_row = $attendance_result->fetch_assoc()) {
                $date_str = date('Y-m-d', strtotime($temp_row['attendance_date']));
                $attendance_dates_map[$date_str] = true;
                $temp_attendance_data[] = $temp_row;
            }
            // Reset result pointer by recreating the query result (we'll merge below)
            $attendance_result->data_seek(0);
            
            // Add leave days to attendance data
            while ($leave = $leave_result->fetch_assoc()) {
                $start_date = new DateTime($leave['start_date']);
                $end_date = new DateTime($leave['end_date']);
                $leave_name = $leave['leave_name'] ?? 'Approved Leave';
                $leave_reason = $leave['reason'] ?? '';
                $is_paid = strtolower($leave['paid_unpaid'] ?? 'unpaid') === 'paid';
                
                // Generate all dates in the leave range
                $current_date = clone $start_date;
                while ($current_date <= $end_date) {
                    $date_str = $current_date->format('Y-m-d');
                    $date_check = $current_date->format('Y-m-d');
                    
                    // Check if this date is within the selected period
                    if ($date_check >= $period_start && $date_check <= $period_end) {
                        // Only add if date is in period and not already in attendance data
                        if (!isset($attendance_dates_map[$date_str])) {
                            $leave_attendance_records[] = [
                                'attendance_date' => $date_str,
                                'time_in' => null,
                                'time_out' => null,
                                'status' => 'leave',
                                'hours_worked' => $is_paid ? 8.00 : 0.00, // Paid leave = full day, unpaid = 0
                                'overtime_hours' => 0.00,
                                'late_minutes' => 0,
                                'remarks' => "Leave: $leave_name" . ($leave_reason ? " - $leave_reason" : ""),
                                'source' => 'hris_leave',
                                'is_paid_leave' => $is_paid
                            ];
                            $attendance_dates_map[$date_str] = true;
                        }
                    }
                    
                    $current_date->modify('+1 day');
                }
            }
            $leave_stmt->close();
        }
    }
    
    // Merge attendance data and leave records
    $all_attendance_records = [];
    
    // Only process attendance result if it exists and is valid
    if (isset($attendance_result) && $attendance_result) {
        $attendance_result->data_seek(0); // Reset pointer
        while ($row = $attendance_result->fetch_assoc()) {
        // Normalize date format
        $row['attendance_date'] = date('Y-m-d', strtotime($row['attendance_date']));
        // For HRIS records, ensure overtime is calculated correctly
        if ($row['source'] === 'hris' && $row['hours_worked'] > 8.0) {
            $row['overtime_hours'] = $row['hours_worked'] - 8.0;
            $row['hours_worked'] = 8.0; // Regular hours capped at 8
        }
            $all_attendance_records[] = $row;
        }
    }
    // Add leave records
    if (!empty($leave_attendance_records)) {
        $all_attendance_records = array_merge($all_attendance_records, $leave_attendance_records);
    }
    
    // Sort by date
    usort($all_attendance_records, function($a, $b) {
        return strtotime($a['attendance_date']) - strtotime($b['attendance_date']);
    });
    
    // Process each attendance record
    foreach ($all_attendance_records as $row) {
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
                $is_paid_leave = isset($row['is_paid_leave']) ? $row['is_paid_leave'] : false;
                
                // Check if it's paid leave from HRIS leave_type table
                if ($is_paid_leave) {
                    // Paid leave: Full day pay (no deduction)
                    $calculation['salary_adjustments']['basic_salary'] += $daily_rate;
                    $calculation['attendance_summary']['present_days']++; // Count as present for paid leave
                    $calculation['attendance_summary']['regular_hours'] += 8.00; // Full day
                    $calculation['attendance_summary']['total_hours'] += 8.00;
                } else {
                    // Unpaid leave: Deduct full day pay
                $calculation['salary_adjustments']['absent_deduction'] += $daily_rate;
                }
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

