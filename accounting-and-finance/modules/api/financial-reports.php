<?php
/**
 * Financial Reports API Endpoint
 * Uses REAL client data from operational subsystems ONLY
 * NO mock accounting tables - all data from Bank System, Loan Subsystem, HRIS-SIA
 */

require_once '../../config/database.php';
require_once '../../includes/session.php';

// Require login
requireLogin();

// Set JSON header
header('Content-Type: application/json');

// Get request parameters
$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$account_type = $_GET['account_type'] ?? '';
$show_subaccounts = $_GET['show_subaccounts'] ?? 'yes';
$as_of_date = $_GET['as_of_date'] ?? '';

// Validate report type
$valid_types = ['trial-balance', 'balance-sheet', 'income-statement', 'cash-flow', 'regulatory'];
if (!in_array($report_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    exit;
}

try {
    $response = [];
    
    switch ($report_type) {
        case 'trial-balance':
            $response = generateTrialBalance($conn, $date_from, $date_to, $account_type);
            break;
            
        case 'balance-sheet':
            $response = generateBalanceSheet($conn, $as_of_date, $show_subaccounts);
            break;
            
        case 'income-statement':
            $response = generateIncomeStatement($conn, $date_from, $date_to, $show_subaccounts);
            break;
            
        case 'cash-flow':
            $response = generateCashFlow($conn, $date_from, $date_to);
            break;
            
        case 'regulatory':
            $response = ['success' => true, 'message' => 'Regulatory reports are available for download'];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Report type not implemented'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
}

/**
 * Generate Trial Balance Report using REAL client data from subsystems
 */
function generateTrialBalance($conn, $date_from, $date_to, $account_type) {
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    $accounts = [];
    $total_debit = 0;
    $total_credit = 0;
    
    // 1. BANK SYSTEM: Get bank transactions with real customer accounts
    if (empty($account_type) || $account_type === 'asset') {
        if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0 && 
            $conn->query("SHOW TABLES LIKE 'customer_accounts'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'bank_customers'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'transaction_types'")->num_rows > 0) {
            
            $sql = "
                SELECT 
                    ca.account_number as code,
                    CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name) as name,
                    'asset' as account_type,
                    COALESCE(SUM(CASE 
                        WHEN tt.type_name LIKE '%deposit%' OR tt.type_name LIKE '%interest%' THEN bt.amount
                        ELSE 0
                    END), 0) as total_debit,
                    COALESCE(SUM(CASE 
                        WHEN tt.type_name LIKE '%withdrawal%' OR tt.type_name LIKE '%transfer%' THEN bt.amount
                        ELSE 0
                    END), 0) as total_credit
                FROM bank_transactions bt
                INNER JOIN customer_accounts ca ON bt.account_id = ca.account_id
                INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                WHERE ca.is_locked = 0
                    AND DATE(bt.created_at) BETWEEN ? AND ?
                GROUP BY ca.account_id, ca.account_number, bc.first_name, bc.middle_name, bc.last_name
            ";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $date_from, $date_to);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    if ($row['total_debit'] > 0 || $row['total_credit'] > 0) {
                        $accounts[] = $row;
                        $total_debit += $row['total_debit'];
                        $total_credit += $row['total_credit'];
                    }
                }
                $stmt->close();
            }
        }
    }
    
    // 2. LOAN SUBSYSTEM: Get loan applications with real borrower data
    if (empty($account_type) || $account_type === 'liability') {
        if ($conn->query("SHOW TABLES LIKE 'loan_applications'")->num_rows > 0) {
            $sql = "
                SELECT 
                    CONCAT('LOAN-', la.id) as code,
                    la.full_name as name,
                    'liability' as account_type,
                    COALESCE(SUM(CASE 
                        WHEN la.status IN ('Approved', 'Active', 'Disbursed') THEN la.loan_amount
                        ELSE 0
                    END), 0) as total_debit,
                    0 as total_credit
                FROM loan_applications la
                WHERE DATE(la.created_at) BETWEEN ? AND ?
                GROUP BY la.id, la.full_name
            ";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $date_from, $date_to);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    if ($row['total_debit'] > 0) {
                        $accounts[] = $row;
                        $total_debit += $row['total_debit'];
                        $total_credit += $row['total_credit'];
                    }
                }
                $stmt->close();
            }
        }
    }
    
    // 3. PAYROLL: Get payroll runs with real employee data
    if (empty($account_type) || $account_type === 'expense') {
        if ($conn->query("SHOW TABLES LIKE 'payroll_runs'")->num_rows > 0 &&
            $conn->query("SHOW TABLES LIKE 'payslips'")->num_rows > 0) {
            
            $sql = "
                SELECT 
                    CONCAT('PAY-', ps.employee_external_no) as code,
                    CONCAT('Employee Payroll - ', ps.employee_external_no) as name,
                    'expense' as account_type,
                    COALESCE(SUM(ps.net_pay), 0) as total_debit,
                    COALESCE(SUM(ps.net_pay), 0) as total_credit
                FROM payroll_runs pr
                INNER JOIN payslips ps ON pr.id = ps.payroll_run_id
                WHERE pr.status IN ('completed', 'finalized')
                    AND DATE(pr.run_at) BETWEEN ? AND ?
                GROUP BY ps.employee_external_no
            ";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $date_from, $date_to);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    if ($row['total_debit'] > 0) {
                        $accounts[] = $row;
                        $total_debit += $row['total_debit'];
                        $total_credit += $row['total_credit'];
                    }
                }
                $stmt->close();
            }
        }
    }
    
    // Sort accounts by code
    usort($accounts, function($a, $b) {
        return strcmp($a['code'], $b['code']);
    });
    
    return [
        'success' => true,
        'report_title' => 'Trial Balance',
        'period' => date('F d, Y', strtotime($date_from)) . ' to ' . date('F d, Y', strtotime($date_to)),
        'accounts' => $accounts,
        'total_debit' => $total_debit,
        'total_credit' => $total_credit,
        'is_balanced' => abs($total_debit - $total_credit) < 0.01
    ];
}

/**
 * Generate Balance Sheet Report using REAL client data
 */
function generateBalanceSheet($conn, $as_of_date, $show_subaccounts) {
    // Set default date if not provided
    if (empty($as_of_date)) {
        $as_of_date = date('Y-m-d');
    }
    
    $assets = [];
    $liabilities = [];
    $equity = [];
    
    // ASSETS: Bank customer account balances
    if ($conn->query("SHOW TABLES LIKE 'customer_accounts'")->num_rows > 0 &&
        $conn->query("SHOW TABLES LIKE 'bank_customers'")->num_rows > 0) {
        
        $sql = "
            SELECT 
                ca.account_number as code,
                CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name) as name,
                ca.balance,
                'asset' as category
            FROM customer_accounts ca
            INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
            WHERE ca.is_locked = 0
                AND ca.balance > 0
            ORDER BY ca.account_number
        ";
        
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
        }
    }
    
    // Add bank_accounts if exists
    if ($conn->query("SHOW TABLES LIKE 'bank_accounts'")->num_rows > 0) {
        $sql = "
            SELECT 
                CONCAT('BANK-', account_id) as code,
                account_name as name,
                current_balance as balance,
                'asset' as category
            FROM bank_accounts
            WHERE is_active = 1
                AND current_balance > 0
        ";
        
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
        }
    }
    
    // LIABILITIES: Outstanding loan balances
    if ($conn->query("SHOW TABLES LIKE 'loan_applications'")->num_rows > 0) {
        $sql = "
            SELECT 
                CONCAT('LOAN-', id) as code,
                full_name as name,
                loan_amount as balance,
                'liability' as category
            FROM loan_applications
            WHERE status IN ('Approved', 'Active', 'Disbursed')
                AND loan_amount > 0
            ORDER BY id
        ";
        
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Subtract loan payments if available
                $loan_payments = 0;
                if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0 &&
                    $conn->query("SHOW TABLES LIKE 'transaction_types'")->num_rows > 0) {
                    $payment_sql = "
                        SELECT COALESCE(SUM(amount), 0) as payments
                        FROM bank_transactions bt
                        INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                        WHERE tt.type_name LIKE '%loan%payment%'
                            AND bt.description LIKE ?
                    ";
                    $payment_stmt = $conn->prepare($payment_sql);
                    $loan_search = '%LOAN-' . $row['code'] . '%';
                    if ($payment_stmt) {
                        $payment_stmt->bind_param('s', $loan_search);
                        $payment_stmt->execute();
                        $payment_result = $payment_stmt->get_result();
                        if ($payment_row = $payment_result->fetch_assoc()) {
                            $loan_payments = $payment_row['payments'];
                        }
                        $payment_stmt->close();
                    }
                }
                $row['balance'] = max(0, $row['balance'] - $loan_payments);
                if ($row['balance'] > 0) {
                    $liabilities[] = $row;
                }
            }
        }
    }
    
    // EQUITY: Calculated as Assets - Liabilities
    $total_assets = array_sum(array_column($assets, 'balance'));
    $total_liabilities = array_sum(array_column($liabilities, 'balance'));
    $total_equity = $total_assets - $total_liabilities;
    
    if ($total_equity != 0) {
        $equity[] = [
            'code' => 'EQUITY-001',
            'name' => 'Total Equity (Assets - Liabilities)',
            'balance' => $total_equity,
            'category' => 'equity'
        ];
    }
    
    return [
        'success' => true,
        'report_title' => 'Balance Sheet',
        'as_of_date' => date('F d, Y', strtotime($as_of_date)),
        'assets' => $assets,
        'liabilities' => $liabilities,
        'equity' => $equity,
        'total_assets' => $total_assets,
        'total_liabilities' => $total_liabilities,
        'total_equity' => $total_equity,
        'total_liabilities_equity' => $total_liabilities + $total_equity,
        'is_balanced' => abs($total_assets - ($total_liabilities + $total_equity)) < 0.01
    ];
}

/**
 * Generate Income Statement using REAL client data
 */
function generateIncomeStatement($conn, $date_from, $date_to, $show_subaccounts) {
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    $revenue = [];
    $expenses = [];
    
    // REVENUE: Bank interest income
    if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0 &&
        $conn->query("SHOW TABLES LIKE 'transaction_types'")->num_rows > 0) {
        
        $sql = "
            SELECT 
                'INT-001' as code,
                'Bank Interest Income' as name,
                COALESCE(SUM(bt.amount), 0) as balance,
                'revenue' as category
            FROM bank_transactions bt
            INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
            WHERE (tt.type_name LIKE '%interest%' OR bt.description LIKE '%interest%')
                AND DATE(bt.created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc() && $row['balance'] > 0) {
                $revenue[] = $row;
            }
            $stmt->close();
        }
    }
    
    // REVENUE: Estimated loan interest income (20% annual rate)
    if ($conn->query("SHOW TABLES LIKE 'loan_applications'")->num_rows > 0) {
        $sql = "
            SELECT 
                'LOAN-INT-001' as code,
                'Loan Interest Income (Estimated)' as name,
                COALESCE(SUM(loan_amount * 0.20 / 12), 0) as balance,
                'revenue' as category
            FROM loan_applications
            WHERE status IN ('Approved', 'Active', 'Disbursed')
                AND DATE(created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc() && $row['balance'] > 0) {
                $revenue[] = $row;
            }
            $stmt->close();
        }
    }
    
    // EXPENSES: Payroll expenses
    if ($conn->query("SHOW TABLES LIKE 'payroll_runs'")->num_rows > 0) {
        $sql = "
            SELECT 
                'PAY-EXP-001' as code,
                'Payroll Expenses' as name,
                COALESCE(SUM(total_net), 0) as balance,
                'expense' as category
            FROM payroll_runs
            WHERE status IN ('completed', 'finalized')
                AND DATE(run_at) BETWEEN ? AND ?
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc() && $row['balance'] > 0) {
                $expenses[] = $row;
            }
            $stmt->close();
        }
    }
    
    $total_revenue = array_sum(array_column($revenue, 'balance'));
    $total_expenses = array_sum(array_column($expenses, 'balance'));
    $net_income = $total_revenue - $total_expenses;
    
    return [
        'success' => true,
        'report_title' => 'Income Statement',
        'period' => date('F d, Y', strtotime($date_from)) . ' to ' . date('F d, Y', strtotime($date_to)),
        'revenue' => $revenue,
        'expenses' => $expenses,
        'total_revenue' => $total_revenue,
        'total_expenses' => $total_expenses,
        'net_income' => $net_income,
        'net_income_percentage' => $total_revenue > 0 ? ($net_income / $total_revenue) * 100 : 0
    ];
}

/**
 * Generate Cash Flow Statement using REAL client data
 */
function generateCashFlow($conn, $date_from, $date_to) {
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    // Operating Activities: Deposits - Withdrawals
    $cash_from_operations = 0;
    if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0 &&
        $conn->query("SHOW TABLES LIKE 'transaction_types'")->num_rows > 0) {
        
        // Deposits
        $deposit_sql = "
            SELECT COALESCE(SUM(bt.amount), 0) as deposits
            FROM bank_transactions bt
            INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
            WHERE tt.type_name LIKE '%deposit%'
                AND DATE(bt.created_at) BETWEEN ? AND ?
        ";
        $stmt = $conn->prepare($deposit_sql);
        $deposits = 0;
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $deposits = $row['deposits'];
            }
            $stmt->close();
        }
        
        // Withdrawals
        $withdrawal_sql = "
            SELECT COALESCE(SUM(bt.amount), 0) as withdrawals
            FROM bank_transactions bt
            INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
            WHERE tt.type_name LIKE '%withdrawal%'
                AND DATE(bt.created_at) BETWEEN ? AND ?
        ";
        $stmt = $conn->prepare($withdrawal_sql);
        $withdrawals = 0;
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $withdrawals = $row['withdrawals'];
            }
            $stmt->close();
        }
        
        $cash_from_operations = $deposits - $withdrawals;
    }
    
    // Investing Activities: Currently zero (no investment data in subsystems)
    $cash_from_investing = 0;
    
    // Financing Activities: Loan disbursements
    $cash_from_financing = 0;
    if ($conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0) {
        $financing_sql = "
            SELECT COALESCE(SUM(bt.amount), 0) as disbursements
            FROM bank_transactions bt
            WHERE (bt.description LIKE '%loan%disbursement%' OR bt.description LIKE '%loan%disbursed%')
                AND DATE(bt.created_at) BETWEEN ? AND ?
        ";
        $stmt = $conn->prepare($financing_sql);
        if ($stmt) {
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $cash_from_financing = -abs($row['disbursements']); // Negative for outflow
            }
            $stmt->close();
        }
    }
    
    $net_cash_change = $cash_from_operations + $cash_from_investing + $cash_from_financing;
    
    return [
        'success' => true,
        'report_title' => 'Cash Flow Statement',
        'period' => date('F d, Y', strtotime($date_from)) . ' to ' . date('F d, Y', strtotime($date_to)),
        'cash_from_operations' => $cash_from_operations,
        'cash_from_investing' => $cash_from_investing,
        'cash_from_financing' => $cash_from_financing,
        'net_cash_change' => $net_cash_change
    ];
}
?>
