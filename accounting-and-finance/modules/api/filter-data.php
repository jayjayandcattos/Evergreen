<?php
/**
 * Filter Data API Endpoint
 * Fetches REAL client data from operational subsystems (Bank System, Loan Subsystem, HRIS/Payroll)
 * NO mock accounting tables used - all data comes from real client transactions
 */

require_once '../../config/database.php';
require_once '../../includes/session.php';

// Require login
requireLogin();

// Set JSON header
header('Content-Type: application/json');

// Get request parameters
$action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$subsystem = $_GET['subsystem'] ?? '';
$account_type = $_GET['account_type'] ?? '';
$custom_search = $_GET['custom_search'] ?? '';

try {
    if ($action === 'filter_data') {
        $response = filterFinancialData($conn, $date_from, $date_to, $subsystem, $account_type, $custom_search);
    } else {
        $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Filter Financial Data from REAL client subsystems
 * Uses ONLY operational subsystem tables, NO mock accounting tables
 */
function filterFinancialData($conn, $date_from, $date_to, $subsystem, $account_type, $custom_search) {
    $all_transactions = [];
    
    // Set default date range if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    // 1. BANK SYSTEM: Get transactions from bank_transactions with real customer account data
    if (empty($subsystem) || $subsystem === 'bank-system') {
        if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0 && 
            $conn->query("SHOW TABLES LIKE 'customer_accounts'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'bank_customers'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'transaction_types'")->num_rows > 0) {
            
            // Build query to get bank transactions with customer names
            $bank_sql = "
                SELECT 
                    DATE(bt.created_at) as date,
                    ca.account_number as account_code,
                    CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name) as account_name,
                    bt.description,
                    CASE 
                        WHEN tt.type_name LIKE '%deposit%' OR tt.type_name LIKE '%interest%' THEN bt.amount
                        ELSE 0
                    END as debit,
                    CASE 
                        WHEN tt.type_name LIKE '%withdrawal%' OR tt.type_name LIKE '%transfer%' THEN bt.amount
                        ELSE 0
                    END as credit,
                    ca.balance,
                    'bank-system' as subsystem,
                    'asset' as account_type_category
                FROM bank_transactions bt
                INNER JOIN customer_accounts ca ON bt.account_id = ca.account_id
                INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                WHERE ca.is_locked = 0
                    AND DATE(bt.created_at) BETWEEN ? AND ?";
            
            $bank_params = [$date_from, $date_to];
            $bank_types = 'ss';
            
            // Apply custom search
            if (!empty($custom_search)) {
                $search_term = '%' . $custom_search . '%';
                $bank_sql .= " AND (ca.account_number LIKE ? OR bc.first_name LIKE ? OR bc.last_name LIKE ? OR bt.description LIKE ?)";
                $bank_params[] = $search_term;
                $bank_params[] = $search_term;
                $bank_params[] = $search_term;
                $bank_params[] = $search_term;
                $bank_types .= 'ssss';
            }
            
            // Apply account type filter for bank system (only assets)
            if (!empty($account_type) && $account_type !== 'asset') {
                // Skip bank transactions if not asset type
            } else {
                $bank_sql .= " ORDER BY bt.created_at DESC LIMIT 500";
                
                $stmt = $conn->prepare($bank_sql);
                if ($stmt && !empty($bank_params)) {
                    $stmt->bind_param($bank_types, ...$bank_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $all_transactions[] = [
                            'date' => $row['date'],
                            'account_code' => $row['account_code'],
                            'account_name' => trim($row['account_name']),
                            'description' => $row['description'] ?: 'Bank Transaction',
                            'debit' => floatval($row['debit']),
                            'credit' => floatval($row['credit']),
                            'balance' => floatval($row['balance']),
                            'subsystem' => 'bank-system'
                        ];
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // 2. LOAN SUBSYSTEM: Get loan applications and disbursements with real borrower data
    if (empty($subsystem) || $subsystem === 'loan') {
        if ($conn->query("SHOW TABLES LIKE 'loan_applications'")->num_rows > 0) {
            $loan_sql = "
                SELECT 
                    DATE(la.created_at) as date,
                    CONCAT('LOAN-', la.id) as account_code,
                    la.full_name as account_name,
                    CONCAT('Loan Application - ', la.loan_type, ' (', la.status, ')') as description,
                    CASE 
                        WHEN la.status IN ('Approved', 'Active', 'Disbursed') THEN la.loan_amount
                        ELSE 0
                    END as debit,
                    0 as credit,
                    la.loan_amount as balance,
                    'loan' as subsystem,
                    'liability' as account_type_category
                FROM loan_applications la
                WHERE DATE(la.created_at) BETWEEN ? AND ?";
            
            $loan_params = [$date_from, $date_to];
            $loan_types = 'ss';
            
            // Apply account type filter for loans (only liabilities)
            if (!empty($account_type) && $account_type !== 'liability') {
                // Skip loans if not liability type
            } else {
                // Apply custom search
                if (!empty($custom_search)) {
                    $search_term = '%' . $custom_search . '%';
                    $loan_sql .= " AND (la.full_name LIKE ? OR la.account_number LIKE ? OR la.loan_type LIKE ? OR la.status LIKE ?)";
                    $loan_params[] = $search_term;
                    $loan_params[] = $search_term;
                    $loan_params[] = $search_term;
                    $loan_params[] = $search_term;
                    $loan_types .= 'ssss';
                }
                
                $loan_sql .= " ORDER BY la.created_at DESC LIMIT 500";
                
                $stmt = $conn->prepare($loan_sql);
                if ($stmt && !empty($loan_params)) {
                    $stmt->bind_param($loan_types, ...$loan_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $all_transactions[] = [
                            'date' => $row['date'],
                            'account_code' => $row['account_code'],
                            'account_name' => trim($row['account_name']),
                            'description' => $row['description'],
                            'debit' => floatval($row['debit']),
                            'credit' => floatval($row['credit']),
                            'balance' => floatval($row['balance']),
                            'subsystem' => 'loan'
                        ];
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // 3. PAYROLL: Get payroll runs with real employee data from HRIS
    if (empty($subsystem) || $subsystem === 'payroll') {
        if ($conn->query("SHOW TABLES LIKE 'payroll_runs'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'payslips'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'employee_refs'")->num_rows > 0) {
            
            $payroll_sql = "
                SELECT 
                    DATE(pr.run_at) as date,
                    CONCAT('PAY-', ps.employee_external_no) as account_code,
                    CONCAT('Employee - ', ps.employee_external_no) as account_name,
                    CONCAT('Payroll Run - ', DATE_FORMAT(pr.run_at, '%M %Y')) as description,
                    ps.net_pay as debit,
                    ps.net_pay as credit,
                    ps.net_pay as balance,
                    'payroll' as subsystem,
                    'expense' as account_type_category
                FROM payroll_runs pr
                INNER JOIN payslips ps ON pr.id = ps.payroll_run_id
                WHERE pr.status IN ('completed', 'finalized')
                    AND DATE(pr.run_at) BETWEEN ? AND ?";
            
            $payroll_params = [$date_from, $date_to];
            $payroll_types = 'ss';
            
            // Apply account type filter for payroll (only expenses)
            if (!empty($account_type) && $account_type !== 'expense') {
                // Skip payroll if not expense type
            } else {
                // Apply custom search
                if (!empty($custom_search)) {
                    $search_term = '%' . $custom_search . '%';
                    $payroll_sql .= " AND (ps.employee_external_no LIKE ? OR ps.payslip_json LIKE ?)";
                    $payroll_params[] = $search_term;
                    $payroll_params[] = $search_term;
                    $payroll_types .= 'ss';
                }
                
                $payroll_sql .= " ORDER BY pr.run_at DESC LIMIT 500";
                
                $stmt = $conn->prepare($payroll_sql);
                if ($stmt && !empty($payroll_params)) {
                    $stmt->bind_param($payroll_types, ...$payroll_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        // Try to get employee name from HRIS if possible
                        $employee_name = $row['account_name'];
                        if ($conn->query("SHOW TABLES LIKE 'employee'")->num_rows > 0) {
                            $emp_id = str_replace('PAY-', '', $row['account_code']);
                            $emp_id = preg_replace('/[^0-9]/', '', $emp_id); // Extract numbers only
                            if (!empty($emp_id)) {
                                $emp_query = $conn->prepare("
                                    SELECT CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as emp_name
                                    FROM employee
                                    WHERE employee_id = ? OR employee_id = SUBSTRING(?, 4)
                                    LIMIT 1
                                ");
                                if ($emp_query) {
                                    $emp_query->bind_param("ss", $emp_id, $row['account_code']);
                                    $emp_query->execute();
                                    $emp_result = $emp_query->get_result();
                                    if ($emp_row = $emp_result->fetch_assoc()) {
                                        $employee_name = trim($emp_row['emp_name']);
                                    }
                                    $emp_query->close();
                                }
                            }
                        }
                        
                        $all_transactions[] = [
                            'date' => $row['date'],
                            'account_code' => $row['account_code'],
                            'account_name' => $employee_name,
                            'description' => $row['description'],
                            'debit' => floatval($row['debit']),
                            'credit' => floatval($row['credit']),
                            'balance' => floatval($row['balance']),
                            'subsystem' => 'payroll'
                        ];
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // 4. HRIS-SIA: Get employee contract data (if needed for financial reporting)
    if (empty($subsystem) || $subsystem === 'hris-sia') {
        if ($conn->query("SHOW TABLES LIKE 'contract'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'employee'")->num_rows > 0) {
            
            $hris_sql = "
                SELECT 
                    DATE(c.start_date) as date,
                    CONCAT('HRIS-', e.employee_id) as account_code,
                    CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name) as account_name,
                    CONCAT('Employee Contract - ', c.contract_type, ' (Salary: ₱', FORMAT(c.salary, 2), ')') as description,
                    0 as debit,
                    c.salary as credit,
                    c.salary as balance,
                    'hris-sia' as subsystem,
                    'expense' as account_type_category
                FROM contract c
                INNER JOIN employee e ON c.employee_id = e.employee_id
                WHERE DATE(c.start_date) BETWEEN ? AND ?";
            
            $hris_params = [$date_from, $date_to];
            $hris_types = 'ss';
            
            // Apply account type filter for HRIS (expenses)
            if (!empty($account_type) && $account_type !== 'expense') {
                // Skip HRIS if not expense type
            } else {
                // Apply custom search
                if (!empty($custom_search)) {
                    $search_term = '%' . $custom_search . '%';
                    $hris_sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR c.contract_type LIKE ?)";
                    $hris_params[] = $search_term;
                    $hris_params[] = $search_term;
                    $hris_params[] = $search_term;
                    $hris_types .= 'sss';
                }
                
                $hris_sql .= " ORDER BY c.start_date DESC LIMIT 500";
                
                $stmt = $conn->prepare($hris_sql);
                if ($stmt && !empty($hris_params)) {
                    $stmt->bind_param($hris_types, ...$hris_params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $all_transactions[] = [
                            'date' => $row['date'],
                            'account_code' => $row['account_code'],
                            'account_name' => trim($row['account_name']),
                            'description' => $row['description'],
                            'debit' => floatval($row['debit']),
                            'credit' => floatval($row['credit']),
                            'balance' => floatval($row['balance']),
                            'subsystem' => 'hris-sia'
                        ];
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // Filter by subsystem if specified
    if (!empty($subsystem) && $subsystem !== 'general-ledger') {
        $all_transactions = array_filter($all_transactions, function($t) use ($subsystem) {
            $subsystem_map = [
                'bank-system' => 'bank-system',
                'loan' => 'loan',
                'payroll' => 'payroll',
                'hris-sia' => 'hris-sia'
            ];
            return isset($subsystem_map[$subsystem]) && $t['subsystem'] === $subsystem_map[$subsystem];
        });
    }
    
    // Remove entries filtered out by account_type (already handled above)
    
    // Sort by date (newest first)
    usort($all_transactions, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return [
        'success' => true,
        'data' => array_values($all_transactions),
        'count' => count($all_transactions),
        'message' => count($all_transactions) > 0 ? 'Found ' . count($all_transactions) . ' records from operational subsystems' : 'No records found'
    ];
}
?>
