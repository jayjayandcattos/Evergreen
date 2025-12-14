<?php
/**
 * Payslip Data API
 * Provides payslip data for employees from HRIS system
 * 
 * This API allows HRIS employees to view their payslips from the accounting system
 */

require_once '../../config/database.php';
require_once 'payroll-calculation.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get action parameter
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_payslips':
            getPayslips();
            break;
        default:
            throw new Exception('Invalid action. Use action=get_payslips');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Get payslips for an employee
 * Accepts employee_id from HRIS and converts to external_employee_no
 */
function getPayslips() {
    global $conn;
    
    // Get employee_id from query parameter
    $employee_id = $_GET['employee_id'] ?? '';
    
    if (empty($employee_id)) {
        throw new Exception('Employee ID is required');
    }
    
    // Validate employee_id is numeric
    if (!is_numeric($employee_id)) {
        throw new Exception('Invalid employee ID format');
    }
    
    $employee_id = intval($employee_id);
    
    // Convert employee_id to external_employee_no format (EMP001, EMP002, etc.)
    $external_employee_no = 'EMP' . str_pad($employee_id, 3, '0', STR_PAD_LEFT);
    
    // Query payslips from accounting system
    // Join with payroll_runs and payroll_periods to get period information
    $query = "SELECT 
                ps.id,
                ps.employee_external_no,
                ps.gross_pay,
                ps.total_deductions,
                ps.net_pay,
                ps.payslip_json,
                ps.created_at,
                pr.run_at,
                pr.status as payroll_status,
                pp.period_start,
                pp.period_end
              FROM payslips ps
              INNER JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
              INNER JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
              WHERE ps.employee_external_no = ?
              ORDER BY pr.run_at DESC, ps.created_at DESC
              LIMIT 20";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $external_employee_no);
    
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $payslips = [];
    
    while ($row = $result->fetch_assoc()) {
        // Parse payslip_json if available
        $breakdown = [
            'earnings' => [],
            'deductions' => []
        ];
        
        if (!empty($row['payslip_json'])) {
            $json_data = json_decode($row['payslip_json'], true);
            
            if ($json_data && is_array($json_data)) {
                // Extract earnings from JSON - check multiple possible structures
                if (isset($json_data['earnings']) && is_array($json_data['earnings'])) {
                    // Direct earnings array
                    foreach ($json_data['earnings'] as $earning) {
                        if (is_array($earning) && isset($earning['name']) && isset($earning['amount'])) {
                            $breakdown['earnings'][] = [
                                'name' => $earning['name'],
                                'amount' => floatval($earning['amount'])
                            ];
                        }
                    }
                } else {
                    // Try to reconstruct from individual fields
                    if (isset($json_data['basic_salary']) && floatval($json_data['basic_salary']) > 0) {
                        $breakdown['earnings'][] = [
                            'name' => 'Basic Salary',
                            'amount' => floatval($json_data['basic_salary'])
                        ];
                    }
                    if (isset($json_data['overtime_pay']) && floatval($json_data['overtime_pay']) > 0) {
                        $breakdown['earnings'][] = [
                            'name' => 'Overtime Pay',
                            'amount' => floatval($json_data['overtime_pay'])
                        ];
                    }
                    if (isset($json_data['allowances']) && floatval($json_data['allowances']) > 0) {
                        $breakdown['earnings'][] = [
                            'name' => 'Allowances',
                            'amount' => floatval($json_data['allowances'])
                        ];
                    }
                    if (isset($json_data['bonus']) && floatval($json_data['bonus']) > 0) {
                        $breakdown['earnings'][] = [
                            'name' => 'Bonus',
                            'amount' => floatval($json_data['bonus'])
                        ];
                    }
                }
                
                // Extract deductions from JSON - check multiple possible structures
                // MATCHING ACCOUNTING SYSTEM: Saved payslips should only show mandatory deductions
                // Attendance-based deductions are only shown when calculating from attendance (no saved payslip)
                $attendance_deduction_names = [
                    'Absent Days Deduction',
                    'Unpaid Leave Days Deduction', 
                    'Half Day Deduction',
                    'Late Arrival Penalty',
                    'Absent Days',
                    'Unpaid Leave',
                    'Half Day',
                    'Late Penalty',
                    'Late Arrival'
                ];
                
                if (isset($json_data['deductions']) && is_array($json_data['deductions'])) {
                    // Direct deductions array - filter out attendance-based deductions for saved payslips
                    foreach ($json_data['deductions'] as $deduction) {
                        if (is_array($deduction) && isset($deduction['name']) && isset($deduction['amount'])) {
                            $deduction_name = $deduction['name'];
                            // Skip attendance-based deductions (matching accounting system logic for saved payslips)
                            $is_attendance_deduction = false;
                            foreach ($attendance_deduction_names as $att_name) {
                                if (stripos($deduction_name, $att_name) !== false) {
                                    $is_attendance_deduction = true;
                                    break;
                                }
                            }
                            
                            if (!$is_attendance_deduction) {
                                $breakdown['deductions'][] = [
                                    'name' => $deduction_name,
                                    'amount' => floatval($deduction['amount'])
                                ];
                            }
                        }
                    }
                } else {
                    // Try to reconstruct from individual fields - ONLY mandatory deductions for saved payslips
                    if (isset($json_data['sss_employee']) && floatval($json_data['sss_employee']) > 0) {
                        $breakdown['deductions'][] = [
                            'name' => 'SSS Employee Contribution',
                            'amount' => floatval($json_data['sss_employee'])
                        ];
                    }
                    if (isset($json_data['philhealth_employee']) && floatval($json_data['philhealth_employee']) > 0) {
                        $breakdown['deductions'][] = [
                            'name' => 'PhilHealth Employee Contribution',
                            'amount' => floatval($json_data['philhealth_employee'])
                        ];
                    }
                    if (isset($json_data['pagibig_employee']) && floatval($json_data['pagibig_employee']) > 0) {
                        $breakdown['deductions'][] = [
                            'name' => 'Pag-IBIG Employee Contribution',
                            'amount' => floatval($json_data['pagibig_employee'])
                        ];
                    }
                    if (isset($json_data['withholding_tax']) && floatval($json_data['withholding_tax']) > 0) {
                        $breakdown['deductions'][] = [
                            'name' => 'Withholding Tax (BIR)',
                            'amount' => floatval($json_data['withholding_tax'])
                        ];
                    }
                    // NOTE: Do NOT include attendance-based deductions (absent, late, half_day, unpaid_leave)
                    // from saved payslips - matching accounting system logic
                }
            }
        }
        
        // If no earnings found, use gross_pay as fallback
        if (empty($breakdown['earnings']) && $row['gross_pay'] > 0) {
            $breakdown['earnings'][] = [
                'name' => 'Gross Pay',
                'amount' => floatval($row['gross_pay'])
            ];
        }
        
        // If no deductions found but total_deductions exists, show it
        if (empty($breakdown['deductions']) && $row['total_deductions'] > 0) {
            $breakdown['deductions'][] = [
                'name' => 'Total Deductions',
                'amount' => floatval($row['total_deductions'])
            ];
        }
        
        $payslips[] = [
            'id' => intval($row['id']),
            'employee_external_no' => $row['employee_external_no'],
            'employee_id' => $employee_id,
            'period_start' => $row['period_start'],
            'period_end' => $row['period_end'],
            'run_at' => $row['run_at'],
            'payroll_status' => $row['payroll_status'],
            'gross_pay' => floatval($row['gross_pay']),
            'total_deductions' => floatval($row['total_deductions']),
            'net_pay' => floatval($row['net_pay']),
            'breakdown' => $breakdown,
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    
    // MATCHING ACCOUNTING SYSTEM LOGIC EXACTLY:
    // The Overall tab ALWAYS uses calculated payroll from attendance ($attendance_payroll_adjustments)
    // It shows attendance-based deductions ONLY when !$payslip_data (no saved payslip for that period)
    // 
    // KEY INSIGHT: The accounting system's Overall tab ALWAYS calculates from attendance,
    // regardless of whether there's a saved payslip. It only hides attendance-based deductions
    // when there's a saved payslip.
    
    // Check which periods have saved payslips (to know when to hide attendance-based deductions)
    $saved_periods = [];
    foreach ($payslips as $saved) {
        $period_key = $saved['period_start'] . '_' . $saved['period_end'];
        $saved_periods[$period_key] = $saved;
    }
    
    // ALWAYS calculate payroll from attendance for recent periods (matching accounting system's Overall tab)
    // This is what the accounting system shows - calculated payroll, not saved payslip data
    $calculated_payslips = calculatePayslipsFromAttendance($conn, $external_employee_no, $employee_id, $saved_periods);
    
    // Use calculated payslips as primary source (matching accounting system's Overall tab)
    // The accounting system always shows calculated payroll from attendance
    $all_payslips = $calculated_payslips;
    
    // Add saved payslips only for older periods we haven't calculated (beyond 3 months)
    // But these should also be recalculated from attendance to match Overall tab behavior
    $calculated_period_keys = [];
    foreach ($calculated_payslips as $calc) {
        $period_key = $calc['period_start'] . '_' . $calc['period_end'];
        $calculated_period_keys[$period_key] = true;
    }
    
    // For saved payslips in periods we haven't calculated, we could add them
    // but the accounting system's Overall tab always uses calculated payroll, so we'll skip them
    // to maintain consistency
    
    // Sort by period_end descending (most recent first)
    usort($all_payslips, function($a, $b) {
        return strtotime($b['period_end']) - strtotime($a['period_end']);
    });
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $all_payslips,
        'employee_id' => $employee_id,
        'employee_external_no' => $external_employee_no,
        'count' => count($all_payslips)
    ]);
}

/**
 * Calculate payslips from attendance data when no saved payslips exist
 * This generates payslip data from calculated payroll for recent periods
 * Only calculates for periods that don't have saved payslips (matching accounting system)
 */
function calculatePayslipsFromAttendance($conn, $external_employee_no, $employee_id, $saved_periods = []) {
    $payslips = [];
    
    // Get employee's base salary - MATCH ACCOUNTING SYSTEM: prioritize contract salary first, then employee_refs
    $salary_query = "SELECT 
                        c.salary as contract_salary,
                        er.base_monthly_salary
                    FROM employee e
                    LEFT JOIN contract c ON e.contract_id = c.contract_id
                    LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                    WHERE e.employee_id = ?
                    LIMIT 1";
    
    $salary_stmt = $conn->prepare($salary_query);
    if (!$salary_stmt) {
        return $payslips; // Return empty if query fails
    }
    
    $salary_stmt->bind_param('i', $employee_id);
    $salary_stmt->execute();
    $salary_result = $salary_stmt->get_result();
    $salary_row = $salary_result->fetch_assoc();
    
    // MATCH ACCOUNTING SYSTEM PRIORITY: contract salary first, then employee_refs base_monthly_salary
    $base_salary = 0;
    if ($salary_row) {
        // First try HRIS contract salary (priority 1)
        if (!empty($salary_row['contract_salary']) && floatval($salary_row['contract_salary']) > 0) {
            $base_salary = floatval($salary_row['contract_salary']);
        } elseif (isset($salary_row['base_monthly_salary']) && floatval($salary_row['base_monthly_salary']) > 0) {
            // Then try employee_refs base_monthly_salary (priority 2)
            $base_salary = floatval($salary_row['base_monthly_salary']);
        }
    }
    $salary_stmt->close();
    
    if ($base_salary <= 0) {
        return $payslips; // No salary data available
    }
    
    // Get salary components for earnings
    $earnings_query = "SELECT * FROM salary_components WHERE type = 'earning' AND is_active = 1 ORDER BY name";
    $earnings_result = $conn->query($earnings_query);
    $base_components = [];
    if ($earnings_result) {
        while($earning = $earnings_result->fetch_assoc()) {
            $base_components[] = $earning;
        }
    }
    
    // Calculate payroll for current month and recent months (matching accounting system)
    // Start with current month, then go back 2 more months
    // Only calculate for periods that DON'T have saved payslips
    $months_to_check = 3;
    for ($i = 0; $i < $months_to_check; $i++) {
        $check_month = date('Y-m', strtotime("-$i months"));
        $period_start = $check_month . '-01';
        $period_end = date('Y-m-t', strtotime($period_start));
        
        // Skip if this period has a saved payslip (matching accounting system logic)
        $period_key = $period_start . '_' . $period_end;
        if (isset($saved_periods[$period_key])) {
            continue; // Use saved payslip instead
        }
        
        // Calculate for FULL MONTH (matching accounting system default)
        
        // Calculate payroll for this period
        $payroll_data = calculatePayrollFromAttendance(
            $conn,
            $external_employee_no,
            $period_start,
            $period_end,
            $base_components
        );
        
        if ($payroll_data && isset($payroll_data['salary_adjustments'])) {
            $adj = $payroll_data['salary_adjustments'];
            
            // Only include if there's actual payroll data
            $gross_pay = isset($adj['gross_salary']) ? $adj['gross_salary'] : ($adj['basic_salary'] + ($adj['overtime_pay'] ?? 0));
            if ($gross_pay > 0 || isset($adj['basic_salary'])) {
                // Build earnings breakdown
                $earnings = [];
                if (isset($adj['basic_salary']) && $adj['basic_salary'] > 0) {
                    $earnings[] = [
                        'name' => 'Basic Salary',
                        'amount' => floatval($adj['basic_salary'])
                    ];
                }
                if (isset($adj['overtime_pay']) && $adj['overtime_pay'] > 0) {
                    $earnings[] = [
                        'name' => 'Overtime Pay',
                        'amount' => floatval($adj['overtime_pay'])
                    ];
                }
                
                // Build deductions breakdown - EXACT ORDER matching accounting system
                $deductions = [];
                
                // STEP 1: Calculate unpaid leave deduction FIRST (needed to separate from absent deduction)
                $unpaid_leave_deduction = 0;
                $unpaid_leave_days = 0;
                $daily_rate_for_deduction = isset($adj['daily_rate']) && $adj['daily_rate'] > 0 ? $adj['daily_rate'] : ($base_salary / 22);
                
                if ($daily_rate_for_deduction > 0 && $employee_id) {
                    // Query unpaid leave days for the period (matching accounting system query)
                    $month_end = date('Y-m-t', strtotime($period_start));
                    $unpaid_leave_query = "SELECT 
                                                lr.start_date,
                                                lr.end_date,
                                                lt.paid_unpaid
                                            FROM leave_request lr
                                            LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
                                            WHERE lr.employee_id = ?
                                            AND (UPPER(TRIM(lr.status)) = 'APPROVED' OR LOWER(TRIM(lr.status)) = 'approved')
                                            AND LOWER(TRIM(COALESCE(lt.paid_unpaid, 'unpaid'))) = 'unpaid'
                                            AND (lr.start_date <= ? AND lr.end_date >= ?)";
                    
                    $unpaid_leave_stmt = $conn->prepare($unpaid_leave_query);
                    if ($unpaid_leave_stmt) {
                        $unpaid_leave_stmt->bind_param("iss", $employee_id, $month_end, $period_start);
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
                                        if ($date_str >= $period_start && $date_str <= $period_end) {
                                            $unpaid_leave_days++;
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
                }
                
                // STEP 2: Add attendance-based deductions in EXACT order (matching accounting system)
                // CRITICAL: Only show attendance-based deductions when there's NO saved payslip for this period
                // This matches accounting system logic: if ($attendance_payroll_adjustments && !$payslip_data && $employee_id_from_external)
                $has_saved_payslip = isset($saved_periods[$period_key]);
                
                if (!$has_saved_payslip) {
                    // 1. Absent Days Deduction (actual absences, excluding unpaid leaves)
                    $actual_absent_deduction = isset($adj['absent_deduction']) ? max(0, $adj['absent_deduction'] - $unpaid_leave_deduction) : 0;
                    if ($actual_absent_deduction > 0) {
                        $deductions[] = [
                            'name' => 'Absent Days Deduction',
                            'amount' => floatval($actual_absent_deduction)
                        ];
                    }
                    
                    // 2. Unpaid Leave Days Deduction
                    if ($unpaid_leave_deduction > 0) {
                        $deductions[] = [
                            'name' => 'Unpaid Leave Days Deduction',
                            'amount' => floatval($unpaid_leave_deduction)
                        ];
                    }
                    
                    // 3. Half Day Deduction
                    if (isset($adj['half_day_deduction']) && $adj['half_day_deduction'] > 0) {
                        $deductions[] = [
                            'name' => 'Half Day Deduction',
                            'amount' => floatval($adj['half_day_deduction'])
                        ];
                    }
                    
                    // 4. Late Arrival Penalty
                    if (isset($adj['late_penalty']) && $adj['late_penalty'] > 0) {
                        $deductions[] = [
                            'name' => 'Late Arrival Penalty',
                            'amount' => floatval($adj['late_penalty'])
                        ];
                    }
                }
                
                // STEP 3: Calculate mandatory contributions using prorated_base_salary (matching accounting system)
                $basic_for_contrib = isset($adj['prorated_base_salary']) ? $adj['prorated_base_salary'] : $base_salary;
                
                // Calculate mandatory contributions using 2025 rates (based on BASE salary, not gross)
                $sss = calculateSSSContribution($basic_for_contrib);
                $philhealth = calculatePhilHealthContribution($basic_for_contrib);
                $pagibig = calculatePagIBIGContribution($basic_for_contrib);
                
                // 5. SSS Employee Contribution
                if ($sss['employee'] > 0) {
                    $deductions[] = [
                        'name' => 'SSS Employee Contribution',
                        'amount' => floatval($sss['employee'])
                    ];
                }
                
                // 6. PhilHealth Employee Contribution
                if ($philhealth['employee'] > 0) {
                    $deductions[] = [
                        'name' => 'PhilHealth Employee Contribution',
                        'amount' => floatval($philhealth['employee'])
                    ];
                }
                
                // 7. Pag-IBIG Employee Contribution
                if ($pagibig['employee'] > 0) {
                    $deductions[] = [
                        'name' => 'Pag-IBIG Employee Contribution',
                        'amount' => floatval($pagibig['employee'])
                    ];
                }
                
                // STEP 4: Calculate withholding tax using gross_salary (matching accounting system)
                // Taxable income = gross_salary - contributions
                $taxable_income = $gross_pay - $sss['employee'] - $philhealth['employee'] - $pagibig['employee'];
                $withholding_tax = calculateBIRWithholdingTax($taxable_income);
                
                // 8. Withholding Tax (BIR)
                if ($withholding_tax > 0) {
                    $deductions[] = [
                        'name' => 'Withholding Tax (BIR)',
                        'amount' => floatval($withholding_tax)
                    ];
                }
                
                // Calculate total deductions
                $total_deductions = 0;
                foreach ($deductions as $ded) {
                    $total_deductions += $ded['amount'];
                }
                
                // Calculate net pay
                $net_pay = $gross_pay - $total_deductions;
                
                // Only add if there's meaningful data
                if ($gross_pay > 0 || $total_deductions > 0) {
                    $payslips[] = [
                        'id' => 0, // Temporary ID for calculated payslips
                        'employee_external_no' => $external_employee_no,
                        'employee_id' => $employee_id,
                        'period_start' => $period_start,
                        'period_end' => $period_end,
                        'run_at' => date('Y-m-d H:i:s'), // Current time for calculated payslips
                        'payroll_status' => 'calculated', // Indicates this is calculated, not finalized
                        'gross_pay' => floatval($gross_pay),
                        'total_deductions' => floatval($total_deductions),
                        'net_pay' => floatval($net_pay),
                        'breakdown' => [
                            'earnings' => $earnings,
                            'deductions' => $deductions
                        ],
                        'created_at' => date('Y-m-d H:i:s'),
                        'is_calculated' => true // Flag to indicate this is calculated data
                    ];
                }
            }
        }
    }
    
    // Sort by period_end descending (most recent first)
    usort($payslips, function($a, $b) {
        return strtotime($b['period_end']) - strtotime($a['period_end']);
    });
    
    return $payslips;
}

