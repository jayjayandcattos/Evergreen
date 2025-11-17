<?php
/**
 * Financial Reports API Endpoint
 * Handles AJAX requests for generating financial reports
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
 * Generate Trial Balance Report
 */
function generateTrialBalance($conn, $date_from, $date_to, $account_type) {
    // Log report generation
    logActivity('generate', 'financial_reporting', "Generated Trial Balance report ($date_from to $date_to)", $conn);
    
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    $sql = "SELECT 
                a.code,
                a.name,
                at.category as account_type,
                COALESCE(SUM(CASE WHEN jl.debit > 0 THEN jl.debit ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN jl.credit > 0 THEN jl.credit ELSE 0 END), 0) as total_credit
            FROM accounts a
            INNER JOIN account_types at ON a.type_id = at.id
            LEFT JOIN journal_lines jl ON a.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
            WHERE a.is_active = 1
                AND (je.entry_date BETWEEN ? AND ? OR je.entry_date IS NULL)
                AND je.status = 'posted'";
    
    $params = [$date_from, $date_to];
    $types = 'ss';
    
    if (!empty($account_type)) {
        $sql .= " AND at.category = ?";
        $params[] = $account_type;
        $types .= 's';
    }
    
    $sql .= " GROUP BY a.id, a.code, a.name, at.category
              ORDER BY a.code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accounts = [];
    $total_debit = 0;
    $total_credit = 0;
    
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
        $total_debit += $row['total_debit'];
        $total_credit += $row['total_credit'];
    }
    
    $stmt->close();
    
    // Check if we have data
    if (empty($accounts)) {
        return [
            'success' => false,
            'message' => 'No data found for the selected period. Please ensure transactions are posted in the system.'
        ];
    }
    
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
 * Generate Balance Sheet Report
 */
function generateBalanceSheet($conn, $as_of_date, $show_subaccounts) {
    // Set default date if not provided
    if (empty($as_of_date)) {
        $as_of_date = date('Y-m-d');
    }
    
    $detail_level = ($show_subaccounts === 'yes') ? 'detail' : 'summary';
    
    // Get Assets
    $assets = getAccountsByCategory($conn, 'asset', $as_of_date, $detail_level);
    $total_assets = array_sum(array_column($assets, 'balance'));
    
    // Get Liabilities
    $liabilities = getAccountsByCategory($conn, 'liability', $as_of_date, $detail_level);
    $total_liabilities = array_sum(array_column($liabilities, 'balance'));
    
    // Get Equity
    $equity = getAccountsByCategory($conn, 'equity', $as_of_date, $detail_level);
    $total_equity = array_sum(array_column($equity, 'balance'));
    
    // Check if we have data
    if (empty($assets) && empty($liabilities) && empty($equity)) {
        return [
            'success' => false,
            'message' => 'No data found. Please ensure accounts and transactions are set up in the system.'
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
 * Generate Income Statement Report
 */
function generateIncomeStatement($conn, $date_from, $date_to, $show_subaccounts) {
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    $detail_level = ($show_subaccounts === 'yes') ? 'detail' : 'summary';
    
    // Get Revenue
    $revenue = getAccountsByCategory($conn, 'revenue', $date_to, $detail_level, $date_from);
    $total_revenue = array_sum(array_column($revenue, 'balance'));
    
    // Get Expenses
    $expenses = getAccountsByCategory($conn, 'expense', $date_to, $detail_level, $date_from);
    $total_expenses = array_sum(array_column($expenses, 'balance'));
    
    // Calculate Net Income
    $net_income = $total_revenue - $total_expenses;
    
    // Check if we have data
    if (empty($revenue) && empty($expenses)) {
        return [
            'success' => false,
            'message' => 'No revenue or expense data found for the selected period.'
        ];
    }
    
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
 * Generate Cash Flow Statement
 */
function generateCashFlow($conn, $date_from, $date_to) {
    // Set default dates if not provided
    if (empty($date_from)) {
        $date_from = date('Y-01-01');
    }
    if (empty($date_to)) {
        $date_to = date('Y-m-d');
    }
    
    // Operating Activities (Net Income + Non-cash expenses)
    $operating_sql = "SELECT 
                        'Operating Activities' as category,
                        SUM(CASE WHEN at.category = 'revenue' THEN jl.credit - jl.debit ELSE 0 END) as revenue,
                        SUM(CASE WHEN at.category = 'expense' THEN jl.debit - jl.credit ELSE 0 END) as expenses
                      FROM journal_lines jl
                      INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                      INNER JOIN accounts a ON jl.account_id = a.id
                      INNER JOIN account_types at ON a.type_id = at.id
                      WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND at.category IN ('revenue', 'expense')";
    
    $stmt = $conn->prepare($operating_sql);
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $operating = $result->fetch_assoc();
    $stmt->close();
    
    $cash_from_operations = ($operating['revenue'] ?? 0) - ($operating['expenses'] ?? 0);
    
    // Investing Activities (Asset purchases/sales)
    $investing_sql = "SELECT 
                        SUM(jl.debit - jl.credit) as investing_cash_flow
                      FROM journal_lines jl
                      INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                      INNER JOIN accounts a ON jl.account_id = a.id
                      INNER JOIN account_types at ON a.type_id = at.id
                      WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND at.category = 'asset'
                        AND a.name LIKE '%investment%' OR a.name LIKE '%equipment%'";
    
    $stmt = $conn->prepare($investing_sql);
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $investing = $result->fetch_assoc();
    $stmt->close();
    
    $cash_from_investing = ($investing['investing_cash_flow'] ?? 0) * -1;
    
    // Financing Activities (Loans, equity)
    $financing_sql = "SELECT 
                        SUM(jl.credit - jl.debit) as financing_cash_flow
                      FROM journal_lines jl
                      INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                      INNER JOIN accounts a ON jl.account_id = a.id
                      INNER JOIN account_types at ON a.type_id = at.id
                      WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND (at.category = 'liability' OR at.category = 'equity')";
    
    $stmt = $conn->prepare($financing_sql);
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $financing = $result->fetch_assoc();
    $stmt->close();
    
    $cash_from_financing = $financing['financing_cash_flow'] ?? 0;
    
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

/**
 * Helper function to get accounts by category
 */
function getAccountsByCategory($conn, $category, $as_of_date, $detail_level = 'detail', $date_from = null) {
    $sql = "SELECT 
                a.code,
                a.name,
                at.category,
                COALESCE(SUM(jl.debit - jl.credit), 0) as balance
            FROM accounts a
            INNER JOIN account_types at ON a.type_id = at.id
            LEFT JOIN journal_lines jl ON a.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
            WHERE a.is_active = 1
                AND at.category = ?
                AND (je.entry_date <= ? OR je.entry_date IS NULL)
                AND (je.status = 'posted' OR je.status IS NULL)";
    
    $params = [$category, $as_of_date];
    $types = 'ss';
    
    if ($date_from !== null) {
        $sql .= " AND (je.entry_date >= ? OR je.entry_date IS NULL)";
        $params[] = $date_from;
        $types .= 's';
    }
    
    $sql .= " GROUP BY a.id, a.code, a.name, at.category";
    
    if ($detail_level === 'summary') {
        $sql .= " HAVING ABS(balance) > 0.01";
    }
    
    $sql .= " ORDER BY a.code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        // Adjust balance sign for certain account types
        if (in_array($category, ['liability', 'equity', 'revenue'])) {
            $row['balance'] = $row['balance'] * -1;
        }
        $accounts[] = $row;
    }
    
    $stmt->close();
    
    return $accounts;
}
?>

