<?php
/**
 * Payslip Data API
 * Handles database queries for employee payslip information
 * 
 * Database Tables Used:
 * - payslips: Main payslip records with detailed JSON data
 * - payroll_runs: Payroll run information
 * - payroll_periods: Pay period information
 */

// Start output buffering to prevent any HTML output
ob_start();

// Disable error display to prevent HTML error pages
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set error handler to catch any errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
} catch (Exception $e) {
    // Clear any output and return JSON error
    ob_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'System error: ' . $e->getMessage()
    ]);
    exit();
}

// Start session if not already started (for HRIS session check)
// Configure session to match HRIS settings
if (session_status() == PHP_SESSION_NONE) {
    // Match HRIS session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Set CORS headers to allow requests from HRIS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_payslips';

try {
    switch ($action) {
        case 'get_payslips':
            getPayslips();
            break;
        
        case 'get_payslip_details':
            getPayslipDetails();
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    // Clear any output and return JSON error
    ob_clean();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    ob_end_flush();
    exit();
}

/**
 * Get payslips for an employee
 * Accepts employee_id from HRIS and converts to employee_external_no
 */
function getPayslips() {
    global $conn;
    
    // Get employee_id from request (from HRIS)
    $requested_employee_id = $_GET['employee_id'] ?? $_POST['employee_id'] ?? '';
    
    // Get employee_id from session (HRIS session)
    $session_employee_id = $_SESSION['employee_id'] ?? '';
    
    // Determine which employee_id to use
    $employee_id = '';
    if (!empty($requested_employee_id)) {
        $employee_id = $requested_employee_id;
    } elseif (!empty($session_employee_id)) {
        $employee_id = $session_employee_id;
    }
    
    // Validate and convert to integer
    if (empty($employee_id)) {
        throw new Exception('Employee ID is required');
    }
    
    // Convert to integer (handles both string and numeric)
    $employee_id = intval($employee_id);
    if ($employee_id <= 0 || $employee_id > 99999) {
        throw new Exception('Invalid employee ID format. Employee ID must be between 1 and 99999.');
    }
    
    // Validate that employee can only access their own payslip
    // Convert both to integers for proper comparison
    $session_employee_id_int = !empty($session_employee_id) ? intval($session_employee_id) : 0;
    $requested_employee_id_int = !empty($requested_employee_id) ? intval($requested_employee_id) : 0;
    
    if ($session_employee_id_int > 0 && $requested_employee_id_int > 0 && $session_employee_id_int != $requested_employee_id_int) {
        throw new Exception('Unauthorized: You can only access your own payslip');
    }
    
    // If no session employee_id but request has one, use the requested one
    // (This allows the API to work when called from HRIS with employee_id parameter)
    
    // Convert employee_id to employee_external_no (e.g., 26 -> 'EMP026')
    $employee_external_no = 'EMP' . str_pad($employee_id, 3, '0', STR_PAD_LEFT);
    
    // Log for debugging (remove in production)
    error_log("Payslip API: Fetching payslips for employee_id: $employee_id, external_no: $employee_external_no");
    
    // Query payslips table joined with payroll_runs and payroll_periods
    $sql = "SELECT 
                ps.id,
                ps.employee_external_no,
                ps.gross_pay,
                ps.total_deductions,
                ps.net_pay,
                ps.payslip_json,
                ps.created_at,
                pr.id as payroll_run_id,
                pr.run_at,
                pr.status as payroll_status,
                pp.id as payroll_period_id,
                pp.period_start,
                pp.period_end,
                pp.frequency
            FROM payslips ps
            JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
            LEFT JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
            WHERE ps.employee_external_no = ?
            ORDER BY pr.run_at DESC, ps.created_at DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $employee_external_no);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Log result count
    $row_count = $result->num_rows;
    error_log("Payslip API: Found $row_count payslips for $employee_external_no");
    
    $payslips = [];
    while ($row = $result->fetch_assoc()) {
        // Parse payslip_json if available
        $payslip_json = null;
        if (!empty($row['payslip_json'])) {
            $payslip_json = json_decode($row['payslip_json'], true);
        }
        
        // Structure the response with proper NULL handling
        $payslip_data = [
            'id' => $row['id'],
            'employee_external_no' => $row['employee_external_no'],
            'gross_pay' => floatval($row['gross_pay'] ?? 0),
            'total_deductions' => floatval($row['total_deductions'] ?? 0),
            'net_pay' => floatval($row['net_pay'] ?? 0),
            'payroll_run_id' => $row['payroll_run_id'] ?? null,
            'run_at' => $row['run_at'] ?? null,
            'payroll_status' => $row['payroll_status'] ?? 'unknown',
            'period_start' => $row['period_start'] ?? null,
            'period_end' => $row['period_end'] ?? null,
            'frequency' => $row['frequency'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'breakdown' => []
        ];
        
        // Add detailed breakdown from payslip_json
        if ($payslip_json && is_array($payslip_json)) {
            // Earnings breakdown
            $earnings = [];
            if (isset($payslip_json['basic_salary'])) {
                $earnings[] = ['name' => 'Basic Salary', 'amount' => floatval($payslip_json['basic_salary'])];
            }
            if (isset($payslip_json['cola'])) {
                $earnings[] = ['name' => 'Cost of Living Allowance', 'amount' => floatval($payslip_json['cola'])];
            }
            if (isset($payslip_json['meal_allowance'])) {
                $earnings[] = ['name' => 'Meal Allowance', 'amount' => floatval($payslip_json['meal_allowance'])];
            }
            if (isset($payslip_json['comm_allowance'])) {
                $earnings[] = ['name' => 'Communication Allowance', 'amount' => floatval($payslip_json['comm_allowance'])];
            }
            if (isset($payslip_json['rice_subsidy'])) {
                $earnings[] = ['name' => 'Rice Subsidy', 'amount' => floatval($payslip_json['rice_subsidy'])];
            }
            if (isset($payslip_json['transport_allowance'])) {
                $earnings[] = ['name' => 'Transport Allowance', 'amount' => floatval($payslip_json['transport_allowance'])];
            }
            if (isset($payslip_json['bonus'])) {
                $earnings[] = ['name' => 'Bonus', 'amount' => floatval($payslip_json['bonus'])];
            }
            
            // Deductions breakdown
            $deductions = [];
            if (isset($payslip_json['sss_emp'])) {
                $deductions[] = ['name' => 'SSS', 'amount' => floatval($payslip_json['sss_emp'])];
            }
            if (isset($payslip_json['pagibig_emp'])) {
                $deductions[] = ['name' => 'Pag-IBIG', 'amount' => floatval($payslip_json['pagibig_emp'])];
            }
            if (isset($payslip_json['philhealth_emp'])) {
                $deductions[] = ['name' => 'PhilHealth', 'amount' => floatval($payslip_json['philhealth_emp'])];
            }
            if (isset($payslip_json['withholding_tax'])) {
                $deductions[] = ['name' => 'Withholding Tax', 'amount' => floatval($payslip_json['withholding_tax'])];
            }
            if (isset($payslip_json['loan_deduction'])) {
                $deductions[] = ['name' => 'Loan Deduction', 'amount' => floatval($payslip_json['loan_deduction'])];
            }
            if (isset($payslip_json['uniform_deduction'])) {
                $deductions[] = ['name' => 'Uniform Deduction', 'amount' => floatval($payslip_json['uniform_deduction'])];
            }
            
            $payslip_data['breakdown'] = [
                'earnings' => $earnings,
                'deductions' => $deductions
            ];
        }
        
        $payslips[] = $payslip_data;
    }
    
    $stmt->close();
    
    // If no payslips found in payslips table, try payroll_payslips table as fallback
    if (count($payslips) == 0) {
        error_log("Payslip API: No payslips found in payslips table, checking payroll_payslips table");
        
        // Query payroll_payslips table (HRIS table) as fallback
        $fallback_sql = "SELECT 
                            payslip_id as id,
                            employee_id,
                            pay_period_start as period_start,
                            pay_period_end as period_end,
                            gross_salary as gross_pay,
                            deduction as total_deductions,
                            net_pay,
                            release_date,
                            NULL as payslip_json,
                            NULL as created_at,
                            NULL as payroll_run_id,
                            release_date as run_at,
                            'completed' as payroll_status,
                            NULL as payroll_period_id,
                            NULL as frequency
                        FROM payroll_payslips
                        WHERE employee_id = ?
                        ORDER BY pay_period_end DESC, payslip_id DESC
                        LIMIT 10";
        
        $fallback_stmt = $conn->prepare($fallback_sql);
        if ($fallback_stmt) {
            $fallback_stmt->bind_param("i", $employee_id);
            if (!$fallback_stmt->execute()) {
                error_log("Payslip API: Fallback query execution failed: " . $fallback_stmt->error);
                $fallback_stmt->close();
            } else {
                $fallback_result = $fallback_stmt->get_result();
                
                $fallback_count = $fallback_result->num_rows;
                error_log("Payslip API: Found $fallback_count payslips in payroll_payslips table for employee_id: $employee_id");
                
                while ($row = $fallback_result->fetch_assoc()) {
                $payslip_data = [
                    'id' => $row['id'],
                    'employee_external_no' => $employee_external_no,
                    'gross_pay' => floatval($row['gross_pay'] ?? 0),
                    'total_deductions' => floatval($row['total_deductions'] ?? 0),
                    'net_pay' => floatval($row['net_pay'] ?? 0),
                    'payroll_run_id' => $row['payroll_run_id'] ?? null,
                    'run_at' => $row['run_at'] ?? null,
                    'payroll_status' => $row['payroll_status'] ?? 'completed',
                    'period_start' => $row['period_start'] ?? null,
                    'period_end' => $row['period_end'] ?? null,
                    'frequency' => $row['frequency'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'breakdown' => [
                        'earnings' => [
                            ['name' => 'Basic Salary', 'amount' => floatval($row['gross_pay'] ?? 0)]
                        ],
                        'deductions' => [
                            ['name' => 'Total Deductions', 'amount' => floatval($row['total_deductions'] ?? 0)]
                        ]
                    ]
                ];
                $payslips[] = $payslip_data;
                }
                $fallback_stmt->close();
                error_log("Payslip API: Processed " . count($payslips) . " payslips from payroll_payslips table");
            }
        } else {
            error_log("Payslip API: Failed to prepare fallback query: " . $conn->error);
        }
    }
    
    // Additional debugging: Check if employee exists
    $employee_check = null;
    if (count($payslips) == 0) {
        // Check if employee table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'employee'");
        if ($table_check && $table_check->num_rows > 0) {
            $check_employee_sql = "SELECT employee_id, first_name, last_name FROM employee WHERE employee_id = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_employee_sql);
            if ($check_stmt) {
                $check_stmt->bind_param("i", $employee_id);
                if ($check_stmt->execute()) {
                    $employee_check = $check_stmt->get_result()->fetch_assoc();
                    if ($employee_check) {
                        error_log("Payslip API: Employee exists (ID: $employee_id, Name: " . ($employee_check['first_name'] ?? '') . " " . ($employee_check['last_name'] ?? '') . ") but has no payslips");
                    } else {
                        error_log("Payslip API: Employee ID $employee_id not found in employee table");
                    }
                } else {
                    error_log("Payslip API: Error checking employee existence: " . $check_stmt->error);
                }
                $check_stmt->close();
            } else {
                error_log("Payslip API: Failed to prepare employee check query: " . $conn->error);
            }
        } else {
            error_log("Payslip API: Employee table does not exist in database");
        }
    }
    
    // Prepare response with metadata
    $response = [
        'success' => true,
        'data' => $payslips,
        'count' => count($payslips),
        'employee_id' => $employee_id,
        'employee_external_no' => $employee_external_no,
        'searched_tables' => ['payslips', 'payroll_payslips']
    ];
    
    // Add employee existence info if checked
    if (isset($employee_check)) {
        $response['employee_exists'] = !empty($employee_check);
        if (!empty($employee_check)) {
            $response['employee_name'] = trim(($employee_check['first_name'] ?? '') . ' ' . ($employee_check['last_name'] ?? ''));
        }
    }
    
    echo json_encode($response);
    
    ob_end_flush();
    exit();
}

/**
 * Get detailed information for a specific payslip
 */
function getPayslipDetails() {
    global $conn;
    
    $payslip_id = $_GET['payslip_id'] ?? $_POST['payslip_id'] ?? '';
    $employee_id = $_GET['employee_id'] ?? $_POST['employee_id'] ?? '';
    
    if (empty($payslip_id)) {
        throw new Exception('Payslip ID is required');
    }
    
    if (empty($employee_id)) {
        $employee_id = $_SESSION['employee_id'] ?? '';
    }
    
    if (empty($employee_id)) {
        throw new Exception('Employee ID is required');
    }
    
    // Convert employee_id to employee_external_no
    $employee_external_no = 'EMP' . str_pad($employee_id, 3, '0', STR_PAD_LEFT);
    
    // Get payslip details
    $sql = "SELECT 
                ps.*,
                pr.id as payroll_run_id,
                pr.run_at,
                pr.status as payroll_status,
                pp.id as payroll_period_id,
                pp.period_start,
                pp.period_end,
                pp.frequency
            FROM payslips ps
            JOIN payroll_runs pr ON ps.payroll_run_id = pr.id
            LEFT JOIN payroll_periods pp ON pr.payroll_period_id = pp.id
            WHERE ps.id = ? AND ps.employee_external_no = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("is", $payslip_id, $employee_external_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $payslip = $result->fetch_assoc();
    
    if (!$payslip) {
        throw new Exception('Payslip not found');
    }
    
    // Parse payslip_json
    $payslip_json = null;
    if (!empty($payslip['payslip_json'])) {
        $payslip_json = json_decode($payslip['payslip_json'], true);
    }
    
    // Structure the response similar to getPayslips
    $payslip_data = [
        'id' => $payslip['id'],
        'employee_external_no' => $payslip['employee_external_no'],
        'gross_pay' => floatval($payslip['gross_pay']),
        'total_deductions' => floatval($payslip['total_deductions']),
        'net_pay' => floatval($payslip['net_pay']),
        'payroll_run_id' => $payslip['payroll_run_id'],
        'run_at' => $payslip['run_at'],
        'payroll_status' => $payslip['payroll_status'],
        'period_start' => $payslip['period_start'],
        'period_end' => $payslip['period_end'],
        'frequency' => $payslip['frequency'],
        'created_at' => $payslip['created_at'],
        'breakdown' => []
    ];
    
    // Add detailed breakdown from payslip_json
    if ($payslip_json && is_array($payslip_json)) {
        $earnings = [];
        $deductions = [];
        
        // Earnings
        if (isset($payslip_json['basic_salary'])) {
            $earnings[] = ['name' => 'Basic Salary', 'amount' => floatval($payslip_json['basic_salary'])];
        }
        if (isset($payslip_json['cola'])) {
            $earnings[] = ['name' => 'Cost of Living Allowance', 'amount' => floatval($payslip_json['cola'])];
        }
        if (isset($payslip_json['meal_allowance'])) {
            $earnings[] = ['name' => 'Meal Allowance', 'amount' => floatval($payslip_json['meal_allowance'])];
        }
        if (isset($payslip_json['comm_allowance'])) {
            $earnings[] = ['name' => 'Communication Allowance', 'amount' => floatval($payslip_json['comm_allowance'])];
        }
        if (isset($payslip_json['rice_subsidy'])) {
            $earnings[] = ['name' => 'Rice Subsidy', 'amount' => floatval($payslip_json['rice_subsidy'])];
        }
        if (isset($payslip_json['transport_allowance'])) {
            $earnings[] = ['name' => 'Transport Allowance', 'amount' => floatval($payslip_json['transport_allowance'])];
        }
        if (isset($payslip_json['bonus'])) {
            $earnings[] = ['name' => 'Bonus', 'amount' => floatval($payslip_json['bonus'])];
        }
        
        // Deductions
        if (isset($payslip_json['sss_emp'])) {
            $deductions[] = ['name' => 'SSS', 'amount' => floatval($payslip_json['sss_emp'])];
        }
        if (isset($payslip_json['pagibig_emp'])) {
            $deductions[] = ['name' => 'Pag-IBIG', 'amount' => floatval($payslip_json['pagibig_emp'])];
        }
        if (isset($payslip_json['philhealth_emp'])) {
            $deductions[] = ['name' => 'PhilHealth', 'amount' => floatval($payslip_json['philhealth_emp'])];
        }
        if (isset($payslip_json['withholding_tax'])) {
            $deductions[] = ['name' => 'Withholding Tax', 'amount' => floatval($payslip_json['withholding_tax'])];
        }
        if (isset($payslip_json['loan_deduction'])) {
            $deductions[] = ['name' => 'Loan Deduction', 'amount' => floatval($payslip_json['loan_deduction'])];
        }
        if (isset($payslip_json['uniform_deduction'])) {
            $deductions[] = ['name' => 'Uniform Deduction', 'amount' => floatval($payslip_json['uniform_deduction'])];
        }
        
        $payslip_data['breakdown'] = [
            'earnings' => $earnings,
            'deductions' => $deductions
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $payslip_data
    ]);
    
    ob_end_flush();
    exit();
}

