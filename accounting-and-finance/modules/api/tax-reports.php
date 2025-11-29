<?php
/**
 * Tax Reports API Endpoint
 * Handles AJAX requests for generating tax reports
 */

// Start output buffering to catch any errors
ob_start();

try {
    require_once '../../config/database.php';
    
    // Set JSON header first
    header('Content-Type: application/json');

    // For testing, use default user ID (in production, check session)
    $current_user_id = 1;
    
    // Try to get session if available
    if (file_exists('../../includes/session.php')) {
        require_once '../../includes/session.php';
        if (function_exists('isLoggedIn') && isLoggedIn()) {
            $current_user_id = $_SESSION['user_id'] ?? 1;
        }
    }

    // Get request parameters
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $tax_type = $_POST['tax_type'] ?? $_GET['tax_type'] ?? '';
    $period_start = $_POST['period_start'] ?? $_GET['period_start'] ?? '';
    $period_end = $_POST['period_end'] ?? $_GET['period_end'] ?? '';
    $format = $_POST['format'] ?? $_GET['format'] ?? 'json';

    // Clear any output that might have been generated
    ob_clean();

    switch ($action) {
        case 'generate_income_tax':
            $response = generateIncomeTaxReport($conn, $period_start, $period_end, $current_user_id);
            break;
            
        case 'generate_payroll_tax':
            $response = generatePayrollTaxReport($conn, $period_start, $period_end, $current_user_id);
            break;
            
        case 'generate_sales_tax':
            $response = generateSalesTaxReport($conn, $period_start, $period_end, $current_user_id);
            break;
            
        case 'get_tax_summary':
            $response = getTaxSummary($conn);
            break;
            
        case 'get_recent_reports':
            $response = getRecentTaxReports($conn);
            break;
            
        case 'get_tax_report':
            $report_id = $_GET['report_id'] ?? '';
            $response = getTaxReport($conn, $report_id);
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clear any output
    ob_clean();
    
    // Return JSON error
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Error $e) {
    // Clear any output
    ob_clean();
    
    // Return JSON error
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// End output buffering
ob_end_flush();

/**
 * Generate Income Tax Report
 */
function generateIncomeTaxReport($conn, $period_start, $period_end, $user_id = 1) {
    // Set default period if not provided
    if (empty($period_start)) {
        $period_start = date('Y-01-01');
    }
    if (empty($period_end)) {
        $period_end = date('Y-12-31');
    }
    
    // Get revenue data
    $revenue_sql = "SELECT 
                        SUM(jl.credit - jl.debit) as total_revenue
                    FROM journal_lines jl
                    INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                    INNER JOIN accounts a ON jl.account_id = a.id
                    INNER JOIN account_types at ON a.type_id = at.id
                    WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND at.category = 'revenue'";
    
    $stmt = $conn->prepare($revenue_sql);
    $stmt->bind_param('ss', $period_start, $period_end);
    $stmt->execute();
    $revenue_result = $stmt->get_result();
    $total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;
    $stmt->close();
    
    // Get expense data
    $expense_sql = "SELECT 
                        SUM(jl.debit - jl.credit) as total_expenses
                    FROM journal_lines jl
                    INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                    INNER JOIN accounts a ON jl.account_id = a.id
                    INNER JOIN account_types at ON a.type_id = at.id
                    WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND at.category = 'expense'";
    
    $stmt = $conn->prepare($expense_sql);
    $stmt->bind_param('ss', $period_start, $period_end);
    $stmt->execute();
    $expense_result = $stmt->get_result();
    $total_expenses = $expense_result->fetch_assoc()['total_expenses'] ?? 0;
    $stmt->close();
    
    // Calculate taxable income
    $taxable_income = $total_revenue - $total_expenses;
    
    // Calculate estimated tax (simplified - 30% corporate tax rate)
    $estimated_tax = max(0, $taxable_income * 0.30);
    
    // Get detailed breakdown
    $breakdown_sql = "SELECT 
                        a.code,
                        a.name,
                        at.category,
                        SUM(CASE WHEN at.category = 'revenue' THEN jl.credit - jl.debit ELSE 0 END) as revenue_amount,
                        SUM(CASE WHEN at.category = 'expense' THEN jl.debit - jl.credit ELSE 0 END) as expense_amount
                    FROM journal_lines jl
                    INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                    INNER JOIN accounts a ON jl.account_id = a.id
                    INNER JOIN account_types at ON a.type_id = at.id
                    WHERE je.entry_date BETWEEN ? AND ?
                        AND je.status = 'posted'
                        AND at.category IN ('revenue', 'expense')
                    GROUP BY a.id, a.code, a.name, at.category
                    ORDER BY at.category, a.code";
    
    $stmt = $conn->prepare($breakdown_sql);
    $stmt->bind_param('ss', $period_start, $period_end);
    $stmt->execute();
    $breakdown_result = $stmt->get_result();
    
    $breakdown = [];
    while ($row = $breakdown_result->fetch_assoc()) {
        $breakdown[] = $row;
    }
    $stmt->close();
    
    // Prepare report data for storage
    $reportData = [
        'total_revenue' => $total_revenue,
        'total_expenses' => $total_expenses,
        'taxable_income' => $taxable_income,
        'estimated_tax' => $estimated_tax,
        'tax_rate' => 30.0,
        'breakdown' => $breakdown,
        'filing_deadline' => date('Y-04-15', strtotime($period_end . ' +1 year'))
    ];
    
    // Save report to database
    $reportDataJson = json_encode($reportData);
    $saveStmt = $conn->prepare("INSERT INTO tax_reports (report_type, period_start, period_end, generated_by, report_data) VALUES ('income_tax', ?, ?, ?, ?)");
    $saveStmt->bind_param('ssis', $period_start, $period_end, $user_id, $reportDataJson);
    $saveStmt->execute();
    $reportId = $conn->insert_id;
    $saveStmt->close();
    
    return [
        'success' => true,
        'report_id' => $reportId,
        'report_type' => 'Income Tax Report',
        'period' => date('F d, Y', strtotime($period_start)) . ' to ' . date('F d, Y', strtotime($period_end)),
        'total_revenue' => $total_revenue,
        'total_expenses' => $total_expenses,
        'taxable_income' => $taxable_income,
        'estimated_tax' => $estimated_tax,
        'tax_rate' => 30.0,
        'breakdown' => $breakdown,
        'filing_deadline' => date('Y-04-15', strtotime($period_end . ' +1 year')),
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Generate Payroll Tax Report
 */
function generatePayrollTaxReport($conn, $period_start, $period_end, $user_id = 1) {
    // Set default period if not provided
    if (empty($period_start)) {
        $period_start = date('Y-m-01', strtotime('-3 months'));
    }
    if (empty($period_end)) {
        $period_end = date('Y-m-t', strtotime('-1 month'));
    }
    
    // Get payroll data
    $payroll_sql = "SELECT 
                        pr.run_at,
                        pr.total_gross,
                        pr.total_deductions,
                        pr.total_net,
                        COUNT(ps.id) as employee_count
                    FROM payroll_runs pr
                    LEFT JOIN payslips ps ON pr.id = ps.payroll_run_id
                    WHERE pr.run_at BETWEEN ? AND ?
                    GROUP BY pr.id, pr.run_at, pr.total_gross, pr.total_deductions, pr.total_net
                    ORDER BY pr.run_at";
    
    $stmt = $conn->prepare($payroll_sql);
    $stmt->bind_param('ss', $period_start, $period_end);
    $stmt->execute();
    $payroll_result = $stmt->get_result();
    
    $payroll_data = [];
    $total_gross = 0;
    $total_deductions = 0;
    $total_net = 0;
    $total_employees = 0;
    
    while ($row = $payroll_result->fetch_assoc()) {
        $payroll_data[] = $row;
        $total_gross += $row['total_gross'];
        $total_deductions += $row['total_deductions'];
        $total_net += $row['total_net'];
        $total_employees += $row['employee_count'];
    }
    $stmt->close();
    
    // Calculate tax withholdings (simplified)
    $sss_contribution = $total_gross * 0.11; // 11% SSS
    $philhealth_contribution = $total_gross * 0.03; // 3% PhilHealth
    $pagibig_contribution = $total_gross * 0.02; // 2% Pag-IBIG
    $withholding_tax = $total_gross * 0.15; // 15% withholding tax
    
    $total_tax_withheld = $sss_contribution + $philhealth_contribution + $pagibig_contribution + $withholding_tax;
    
    // Prepare report data for storage
    $reportData = [
        'total_gross_pay' => $total_gross,
        'total_deductions' => $total_deductions,
        'total_net_pay' => $total_net,
        'total_employees' => $total_employees,
        'tax_withholdings' => [
            'sss_contribution' => $sss_contribution,
            'philhealth_contribution' => $philhealth_contribution,
            'pagibig_contribution' => $pagibig_contribution,
            'withholding_tax' => $withholding_tax,
            'total_withheld' => $total_tax_withheld
        ],
        'payroll_runs' => $payroll_data,
        'filing_deadline' => date('Y-m-10', strtotime($period_end . ' +1 month'))
    ];
    
    // Save report to database
    $reportDataJson = json_encode($reportData);
    $saveStmt = $conn->prepare("INSERT INTO tax_reports (report_type, period_start, period_end, generated_by, report_data) VALUES ('payroll_tax', ?, ?, ?, ?)");
    $saveStmt->bind_param('ssis', $period_start, $period_end, $user_id, $reportDataJson);
    $saveStmt->execute();
    $reportId = $conn->insert_id;
    $saveStmt->close();
    
    return [
        'success' => true,
        'report_id' => $reportId,
        'report_type' => 'Payroll Tax Report',
        'period' => date('F d, Y', strtotime($period_start)) . ' to ' . date('F d, Y', strtotime($period_end)),
        'total_gross_pay' => $total_gross,
        'total_deductions' => $total_deductions,
        'total_net_pay' => $total_net,
        'total_employees' => $total_employees,
        'tax_withholdings' => [
            'sss_contribution' => $sss_contribution,
            'philhealth_contribution' => $philhealth_contribution,
            'pagibig_contribution' => $pagibig_contribution,
            'withholding_tax' => $withholding_tax,
            'total_withheld' => $total_tax_withheld
        ],
        'payroll_runs' => $payroll_data,
        'filing_deadline' => date('Y-m-10', strtotime($period_end . ' +1 month')),
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Generate Sales Tax Report
 */
function generateSalesTaxReport($conn, $period_start, $period_end, $user_id = 1) {
    // Set default period if not provided
    if (empty($period_start)) {
        $period_start = date('Y-m-01', strtotime('-1 month'));
    }
    if (empty($period_end)) {
        $period_end = date('Y-m-t', strtotime('-1 month'));
    }
    
    // Get sales data with VAT
    $sales_sql = "SELECT 
                    DATE(je.entry_date) as sale_date,
                    SUM(CASE WHEN a.name LIKE '%VAT%' OR a.name LIKE '%tax%' THEN jl.credit ELSE 0 END) as vat_collected,
                    SUM(CASE WHEN at.category = 'revenue' AND a.name NOT LIKE '%VAT%' AND a.name NOT LIKE '%tax%' THEN jl.credit ELSE 0 END) as gross_sales,
                    COUNT(DISTINCT je.id) as transaction_count
                FROM journal_lines jl
                INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                INNER JOIN accounts a ON jl.account_id = a.id
                INNER JOIN account_types at ON a.type_id = at.id
                WHERE je.entry_date BETWEEN ? AND ?
                    AND je.status = 'posted'
                    AND (at.category = 'revenue' OR a.name LIKE '%VAT%' OR a.name LIKE '%tax%')
                GROUP BY DATE(je.entry_date)
                ORDER BY sale_date";
    
    $stmt = $conn->prepare($sales_sql);
    $stmt->bind_param('ss', $period_start, $period_end);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    
    $sales_data = [];
    $total_gross_sales = 0;
    $total_vat_collected = 0;
    $total_transactions = 0;
    
    while ($row = $sales_result->fetch_assoc()) {
        $sales_data[] = $row;
        $total_gross_sales += $row['gross_sales'];
        $total_vat_collected += $row['vat_collected'];
        $total_transactions += $row['transaction_count'];
    }
    $stmt->close();
    
    // Calculate VAT breakdown
    $vat_rate = 12.0; // 12% VAT rate
    $net_sales = $total_gross_sales;
    $vat_on_sales = $total_vat_collected;
    
    // Prepare report data for storage
    $reportData = [
        'total_gross_sales' => $total_gross_sales,
        'total_vat_collected' => $total_vat_collected,
        'net_sales' => $net_sales,
        'vat_rate' => $vat_rate,
        'total_transactions' => $total_transactions,
        'daily_sales' => $sales_data,
        'filing_deadline' => date('Y-m-20', strtotime($period_end . ' +1 month'))
    ];
    
    // Save report to database
    $reportDataJson = json_encode($reportData);
    $saveStmt = $conn->prepare("INSERT INTO tax_reports (report_type, period_start, period_end, generated_by, report_data) VALUES ('sales_tax', ?, ?, ?, ?)");
    $saveStmt->bind_param('ssis', $period_start, $period_end, $user_id, $reportDataJson);
    $saveStmt->execute();
    $reportId = $conn->insert_id;
    $saveStmt->close();
    
    return [
        'success' => true,
        'report_id' => $reportId,
        'report_type' => 'Sales Tax Report',
        'period' => date('F d, Y', strtotime($period_start)) . ' to ' . date('F d, Y', strtotime($period_end)),
        'total_gross_sales' => $total_gross_sales,
        'total_vat_collected' => $total_vat_collected,
        'net_sales' => $net_sales,
        'vat_rate' => $vat_rate,
        'total_transactions' => $total_transactions,
        'daily_sales' => $sales_data,
        'filing_deadline' => date('Y-m-20', strtotime($period_end . ' +1 month')),
        'generated_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Get Recent Tax Reports
 */
function getRecentTaxReports($conn) {
    $sql = "SELECT 
                tr.id,
                tr.report_type,
                tr.period_start,
                tr.period_end,
                tr.generated_date,
                tr.status,
                u.full_name as generated_by_name,
                tr.report_data
            FROM tax_reports tr
            INNER JOIN users u ON tr.generated_by = u.id
            ORDER BY tr.generated_date DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    $reports = [];
    
    while ($row = $result->fetch_assoc()) {
        $reportData = json_decode($row['report_data'], true);
        
        $reports[] = [
            'id' => $row['id'],
            'report_type' => ucwords(str_replace('_', ' ', $row['report_type'])) . ' Report',
            'period' => date('M d, Y', strtotime($row['period_start'])) . ' - ' . date('M d, Y', strtotime($row['period_end'])),
            'generated_date' => $row['generated_date'],
            'generated_by' => $row['generated_by_name'],
            'status' => $row['status'],
            'summary' => getReportSummary($row['report_type'], $reportData)
        ];
    }
    
    return [
        'success' => true,
        'reports' => $reports
    ];
}

/**
 * Get Individual Tax Report
 */
function getTaxReport($conn, $report_id) {
    if (empty($report_id)) {
        return ['success' => false, 'message' => 'Report ID required'];
    }
    
    $sql = "SELECT 
                tr.*,
                u.full_name as generated_by_name
            FROM tax_reports tr
            INNER JOIN users u ON tr.generated_by = u.id
            WHERE tr.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Report not found'];
    }
    
    $row = $result->fetch_assoc();
    $reportData = json_decode($row['report_data'], true);
    
    return [
        'success' => true,
        'report' => [
            'id' => $row['id'],
            'report_type' => ucwords(str_replace('_', ' ', $row['report_type'])) . ' Report',
            'period' => date('F d, Y', strtotime($row['period_start'])) . ' to ' . date('F d, Y', strtotime($row['period_end'])),
            'generated_date' => $row['generated_date'],
            'generated_by' => $row['generated_by_name'],
            'status' => $row['status'],
            'data' => $reportData
        ]
    ];
}

/**
 * Get Report Summary for display
 */
function getReportSummary($reportType, $reportData) {
    switch ($reportType) {
        case 'income_tax':
            return 'Taxable Income: ₱' . number_format($reportData['taxable_income'] ?? 0, 2) . 
                   ' | Estimated Tax: ₱' . number_format($reportData['estimated_tax'] ?? 0, 2);
        case 'payroll_tax':
            return 'Total Withheld: ₱' . number_format($reportData['tax_withholdings']['total_withheld'] ?? 0, 2) . 
                   ' | Employees: ' . ($reportData['total_employees'] ?? 0);
        case 'sales_tax':
            return 'VAT Collected: ₱' . number_format($reportData['total_vat_collected'] ?? 0, 2) . 
                   ' | Transactions: ' . ($reportData['total_transactions'] ?? 0);
        default:
            return 'Report generated';
    }
}

/**
 * Get Tax Summary for dashboard
 */
function getTaxSummary($conn) {
    $current_year = date('Y');
    
    // Income Tax Summary
    $income_tax = generateIncomeTaxReport($conn, $current_year . '-01-01', $current_year . '-12-31', 1);
    
    // Payroll Tax Summary (current quarter)
    $quarter_start = date('Y-m-01', strtotime('first day of this month -2 months'));
    $quarter_end = date('Y-m-t', strtotime('last day of this month'));
    $payroll_tax = generatePayrollTaxReport($conn, $quarter_start, $quarter_end, 1);
    
    // Sales Tax Summary (current month)
    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $sales_tax = generateSalesTaxReport($conn, $month_start, $month_end, 1);
    
    return [
        'success' => true,
        'income_tax' => [
            'estimated_tax' => $income_tax['estimated_tax'],
            'taxable_income' => $income_tax['taxable_income'],
            'filing_deadline' => $income_tax['filing_deadline']
        ],
        'payroll_tax' => [
            'total_withheld' => $payroll_tax['tax_withholdings']['total_withheld'],
            'employee_count' => $payroll_tax['total_employees'],
            'filing_deadline' => $payroll_tax['filing_deadline']
        ],
        'sales_tax' => [
            'vat_collected' => $sales_tax['total_vat_collected'],
            'gross_sales' => $sales_tax['total_gross_sales'],
            'filing_deadline' => $sales_tax['filing_deadline']
        ]
    ];
}
?>