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
    
    // First, try to extract from external_employee_no format (EMP001, EMP002, etc.)
    if (preg_match('/EMP(\d+)/i', $employee_external_no, $matches)) {
        $employee_id_from_external = intval($matches[1]);
    } else {
        // Try direct match if it's already a number
        if (is_numeric($employee_external_no)) {
            $employee_id_from_external = intval($employee_external_no);
        } else {
            // Fallback: Try to get employee_id from employee_refs or employee table
            $fallback_query = "SELECT e.employee_id 
                              FROM employee e 
                              LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                              WHERE er.external_employee_no = ? OR CONCAT('EMP', LPAD(e.employee_id, 3, '0')) = ?
                              LIMIT 1";
            $fallback_stmt = $conn->prepare($fallback_query);
            if ($fallback_stmt) {
                $fallback_stmt->bind_param("ss", $employee_external_no, $employee_external_no);
                $fallback_stmt->execute();
                $fallback_result = $fallback_stmt->get_result();
                if ($fallback_row = $fallback_result->fetch_assoc()) {
                    $employee_id_from_external = intval($fallback_row['employee_id']);
                }
                $fallback_stmt->close();
            }
        }
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
    
    // Get employee base salary - MATCH ACCOUNTING SYSTEM: prioritize contract salary first, then employee_refs
    // We need employee_id to join with contract table, so we'll query using the extracted employee_id
    $base_salary = 0;
    
    if ($employee_id_from_external) {
        // Query with priority: contract salary first, then employee_refs base_monthly_salary
        $employee_query = "SELECT 
                            c.salary as contract_salary,
                            er.base_monthly_salary
                          FROM employee e
                          LEFT JOIN contract c ON e.contract_id = c.contract_id
                          LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                          WHERE e.employee_id = ?
                          LIMIT 1";
        $emp_stmt = $conn->prepare($employee_query);
        
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $employee_id_from_external);
            $emp_stmt->execute();
            $emp_result = $emp_stmt->get_result();
            $employee_data = $emp_result->fetch_assoc();
            $emp_stmt->close();
            
            // MATCH ACCOUNTING SYSTEM PRIORITY: contract salary first, then employee_refs base_monthly_salary
            if ($employee_data) {
                // First try HRIS contract salary (priority 1)
                if (!empty($employee_data['contract_salary']) && floatval($employee_data['contract_salary']) > 0) {
                    $base_salary = floatval($employee_data['contract_salary']);
                } elseif (isset($employee_data['base_monthly_salary']) && floatval($employee_data['base_monthly_salary']) > 0) {
                    // Then try employee_refs base_monthly_salary (priority 2)
                    $base_salary = floatval($employee_data['base_monthly_salary']);
                }
            }
        }
    }
    
    // Fallback: Try employee_refs directly if we couldn't get employee_id
    if ($base_salary == 0) {
        $emp_query_fallback = "SELECT base_monthly_salary FROM employee_refs WHERE external_employee_no = ? LIMIT 1";
        $emp_stmt_fallback = $conn->prepare($emp_query_fallback);
        
        if ($emp_stmt_fallback) {
            $emp_stmt_fallback->bind_param("s", $employee_external_no);
            $emp_stmt_fallback->execute();
            $emp_result_fallback = $emp_stmt_fallback->get_result();
            $employee_data_fallback = $emp_result_fallback->fetch_assoc();
            $emp_stmt_fallback->close();
            
            if ($employee_data_fallback && isset($employee_data_fallback['base_monthly_salary'])) {
                $base_salary = floatval($employee_data_fallback['base_monthly_salary']);
            }
        }
    }
    
    // Final fallback: base salary components if employee salary not found
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
        // For bi-monthly: calculate daily rate based on prorated salary and period days
        $daily_rate = $prorated_base_salary / $period_days;
        $hourly_rate = $daily_rate / $hours_per_day;
    } else {
        // Full month: use standard 22 working days (not calendar days)
        // No proration needed for full month
        $prorated_base_salary = $base_salary;
        $daily_rate = $base_salary / $working_days_per_month;
        $hourly_rate = $daily_rate / $hours_per_day;
    }
    
    // Fallback if base_salary is 0
    if ($base_salary == 0) {
        $daily_rate = 0;
        $hourly_rate = 0;
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
        // Improved overlap logic: leave overlaps if it starts before period ends AND ends after period starts
        $leave_query .= " AND (
                            (lr.start_date <= ? AND lr.end_date >= ?)
                        )";
        $leave_params = [$employee_id_from_external, $period_end, $period_start];
        $leave_types = "iss"; // 1 integer + 2 strings = 3 parameters
        
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
    
    // Round all adjustments
    $calculation['salary_adjustments']['basic_salary'] = round($calculation['salary_adjustments']['basic_salary'], 2);
    $calculation['salary_adjustments']['absent_deduction'] = round($calculation['salary_adjustments']['absent_deduction'], 2);
    $calculation['salary_adjustments']['half_day_deduction'] = round($calculation['salary_adjustments']['half_day_deduction'], 2);
    $calculation['salary_adjustments']['late_penalty'] = round($calculation['salary_adjustments']['late_penalty'], 2);
    $calculation['salary_adjustments']['overtime_pay'] = round($calculation['salary_adjustments']['overtime_pay'], 2);
    
    // Calculate Gross Salary = basic_salary (earned from present days) + overtime_pay
    $gross_salary = $calculation['salary_adjustments']['basic_salary'] + $calculation['salary_adjustments']['overtime_pay'];
    $calculation['salary_adjustments']['gross_salary'] = round($gross_salary, 2);
    
    // Calculate Net Salary = Gross Salary - absent deductions - half day deductions - late penalties
    // Note: Mandatory contributions and withholding tax are calculated separately in the payroll management page
    $net_salary_before_tax = $gross_salary 
        - $calculation['salary_adjustments']['absent_deduction']
        - $calculation['salary_adjustments']['half_day_deduction']
        - $calculation['salary_adjustments']['late_penalty'];
    $calculation['salary_adjustments']['net_salary_before_tax'] = round($net_salary_before_tax, 2);
    
    // Keep adjusted_salary for backward compatibility (same as net_salary_before_tax)
    $calculation['salary_adjustments']['adjusted_salary'] = round($net_salary_before_tax, 2);
    
    // Store the prorated base salary for reference
    $base_salary_for_calculation = isset($prorated_base_salary) ? $prorated_base_salary : $base_salary;
    $calculation['salary_adjustments']['prorated_base_salary'] = round($base_salary_for_calculation, 2);
    
    // Store daily rate and hourly rate for reference
    $calculation['salary_adjustments']['daily_rate'] = round($daily_rate, 2);
    $calculation['salary_adjustments']['hourly_rate'] = round($hourly_rate, 2);
    
    return $calculation;
}

/**
 * Calculate SSS Contribution (2025 Rates)
 * Employee: 5.5%, Employer: 9.5%
 * Monthly Salary Credit (MSC) range: ₱5,000 - ₱35,000
 * 
 * @param float $monthly_salary Monthly basic salary
 * @return array ['employee' => float, 'employer' => float]
 */
function calculateSSSContribution($monthly_salary) {
    // Apply MSC limits
    $msc_min = 5000;
    $msc_max = 35000;
    
    // Clamp salary to MSC range
    $msc = max($msc_min, min($msc_max, $monthly_salary));
    
    // 2025 rates: Employee 5.5%, Employer 9.5%
    $employee_contribution = $msc * 0.055;
    $employer_contribution = $msc * 0.095;
    
    return [
        'employee' => round($employee_contribution, 2),
        'employer' => round($employer_contribution, 2),
        'msc' => $msc
    ];
}

/**
 * Calculate PhilHealth Contribution (2025 Rates)
 * Total: 5% (2.5% employee, 2.5% employer)
 * Income ceiling: ₱100,000
 * 
 * @param float $monthly_salary Monthly basic salary
 * @return array ['employee' => float, 'employer' => float]
 */
function calculatePhilHealthContribution($monthly_salary) {
    // Apply income ceiling
    $income_ceiling = 100000;
    $base_salary = min($monthly_salary, $income_ceiling);
    
    // 2025 rates: 2.5% each (5% total)
    $employee_contribution = $base_salary * 0.025;
    $employer_contribution = $base_salary * 0.025;
    
    return [
        'employee' => round($employee_contribution, 2),
        'employer' => round($employer_contribution, 2)
    ];
}

/**
 * Calculate Pag-IBIG (HDMF) Contribution (2025 Rates)
 * Employee: 2%, Employer: 2%
 * Maximum contribution base: ₱5,000 (resulting in ₱100 max contribution each)
 * 
 * @param float $monthly_salary Monthly basic salary
 * @return array ['employee' => float, 'employer' => float]
 */
function calculatePagIBIGContribution($monthly_salary) {
    // Maximum contribution base is ₱5,000
    $contribution_base = min($monthly_salary, 5000);
    
    // 2% each
    $employee_contribution = $contribution_base * 0.02;
    $employer_contribution = $contribution_base * 0.02;
    
    return [
        'employee' => round($employee_contribution, 2),
        'employer' => round($employer_contribution, 2)
    ];
}

/**
 * Calculate BIR Withholding Tax (2025 Progressive Rates)
 * Based on taxable income (gross salary minus SSS, PhilHealth, Pag-IBIG)
 * 
 * @param float $taxable_income Taxable income after mandatory deductions
 * @return float Withholding tax amount
 */
function calculateBIRWithholdingTax($taxable_income) {
    $tax = 0;
    
    // BIR 2025 Progressive Tax Brackets (Revised Withholding Tax Table effective January 1, 2023)
    // Reference: https://taxcalculatorphilippines.com/
    if ($taxable_income <= 20833) {
        // ₱0 - ₱20,833: 0%
        $tax = 0;
    } elseif ($taxable_income <= 33332) {
        // ₱20,833 - ₱33,332: 0.00 + 15% over ₱20,833
        $tax = ($taxable_income - 20833) * 0.15;
    } elseif ($taxable_income <= 66666) {
        // ₱33,333 - ₱66,666: ₱2,500.00 + 20% over ₱33,333
        $tax = 2500.00 + (($taxable_income - 33333) * 0.20);
    } elseif ($taxable_income <= 166666) {
        // ₱66,667 - ₱166,666: ₱10,833.33 + 25% of excess over ₱66,667
        $tax = 10833.33 + (($taxable_income - 66667) * 0.25);
    } elseif ($taxable_income <= 666666) {
        // ₱166,667 - ₱666,666: ₱40,833.33 + 30% of excess over ₱166,667
        $tax = 40833.33 + (($taxable_income - 166667) * 0.30);
    } else {
        // Above ₱666,666: ₱200,833.33 + 35% of excess over ₱666,667
        $tax = 200833.33 + (($taxable_income - 666667) * 0.35);
    }
    
    return round(max(0, $tax), 2);
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

