<?php
/**
 * Loan Data API
 * Handles database queries for loan accounting module
 * 
 * Database Tables Used:
 * - loans: Main loan records
 * - loan_types: Types of loans
 * - loan_payments: Payment history
 * - accounts: Chart of accounts
 * - users: User information
 * - audit_logs: Audit trail tracking
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
    require_once dirname(__DIR__, 2) . '/includes/session.php';
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

// Verify user is logged in
if (!isLoggedIn()) {
    // Clear any output and return JSON error
    ob_clean();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_loans':
            getLoans();
            break;
        
        case 'get_loan_details':
            getLoanDetails();
            break;
        
        case 'get_audit_trail':
            getAuditTrail();
            break;
        
        case 'get_statistics':
            getStatistics();
            break;
        
        case 'soft_delete_loan':
            softDeleteLoan();
            break;
        
        case 'get_bin_items':
            getBinItems();
            break;
        
        case 'restore_loan':
            restoreLoan();
            break;
        
        case 'permanent_delete_loan':
            permanentDeleteLoan();
            break;
        
        case 'export_excel':
            exportToExcel();
            break;
        
        case 'process_payment':
            processPayment();
            break;
        
        case 'get_application_details':
            getApplicationDetails();
            break;
        case 'delete_application':
            deleteApplication();
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
 * Get loans with optional filters
 */
function getLoans() {
    global $conn;
    
    // Get filter parameters
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $transactionType = $_GET['transaction_type'] ?? '';
    $status = $_GET['status'] ?? '';
    $accountNumber = $_GET['account_number'] ?? '';
    
    // Base query - matching actual schema columns
    $sql = "SELECT 
                l.id,
                l.loan_no as loan_number,
                l.borrower_external_no as borrower_name,
                l.principal_amount as loan_amount,
                l.interest_rate,
                l.term_months as loan_term,
                l.start_date,
                DATE_ADD(l.start_date, INTERVAL l.term_months MONTH) as maturity_date,
                l.current_balance as outstanding_balance,
                l.status,
                'loan' as transaction_type,
                lt.name as loan_type_name,
                '' as account_code,
                '' as account_name,
                l.created_at,
                u.full_name as created_by_name
            FROM loans l
            LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
            LEFT JOIN users u ON l.created_by = u.id
            WHERE (l.deleted_at IS NULL OR l.deleted_at = '')";
    
    $params = [];
    $types = '';
    
    // Apply filters
    if (!empty($dateFrom)) {
        $sql .= " AND l.start_date >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND l.start_date <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $sql .= " AND l.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($accountNumber)) {
        $sql .= " AND l.loan_no LIKE ?";
        $searchTerm = "%{$accountNumber}%";
        $params[] = $searchTerm;
        $types .= 's';
    }
    
    $sql .= " ORDER BY l.start_date DESC, l.loan_no DESC";
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $loans = [];
    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $loans,
        'count' => count($loans)
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Get detailed information for a specific loan
 * Including payment schedule and transaction history
 */
function getLoanDetails() {
    global $conn;
    
    $loanId = $_GET['id'] ?? '';
    
    if (empty($loanId)) {
        throw new Exception('Loan ID is required');
    }
    
    // Get main loan data
    $sql = "SELECT 
                l.*,
                l.loan_no as loan_number,
                l.borrower_external_no as borrower_name,
                l.principal_amount as loan_amount,
                l.term_months as loan_term,
                DATE_ADD(l.start_date, INTERVAL l.term_months MONTH) as maturity_date,
                l.current_balance as outstanding_balance,
                lt.name as loan_type_name,
                lt.description as loan_type_description,
                '' as account_code,
                '' as account_name,
                u.username as created_by_username,
                u.full_name as created_by_name
            FROM loans l
            LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
            LEFT JOIN users u ON l.created_by = u.id
            WHERE l.id = ? AND (l.deleted_at IS NULL OR l.deleted_at = '')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $loanId);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan = $result->fetch_assoc();
    
    if (!$loan) {
        throw new Exception('Loan not found');
    }
    
    // Initialize payment schedule as empty array (always return this field)
    $loan['payment_schedule'] = [];
    $loan['total_paid'] = 0;
    $loan['last_payment_date'] = null;
    
    // Get payment history from loan_payments table (for all loan statuses including paid)
    if (tableExists('loan_payments')) {
        $sql = "SELECT 
                    lp.*,
                    lp.payment_date as due_date,
                    lp.amount as total_payment,
                    (lp.principal_amount + lp.interest_amount) as calculated_total,
                    l.current_balance
                FROM loan_payments lp
                INNER JOIN loans l ON lp.loan_id = l.id
                WHERE lp.loan_id = ?
                ORDER BY lp.payment_date ASC";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $paymentSchedule = [];
            $runningBalance = floatval($loan['loan_amount']);
            $totalPaid = 0;
            $lastPaymentDate = null;
            
            while ($row = $result->fetch_assoc()) {
            // Calculate balance after this payment
            $paymentAmount = floatval($row['principal_amount']);
            $runningBalance -= $paymentAmount;
            $totalPaid += floatval($row['principal_amount']);
            
            // Track last payment date
            if (!$lastPaymentDate || $row['payment_date'] > $lastPaymentDate) {
                $lastPaymentDate = $row['payment_date'];
            }
            
            $paymentSchedule[] = [
                'due_date' => $row['payment_date'],
                'payment_date' => $row['payment_date'],
                'principal' => $row['principal_amount'],
                'principal_amount' => $row['principal_amount'],
                'interest' => $row['interest_amount'],
                'interest_amount' => $row['interest_amount'],
                'total_payment' => $row['amount'] ? $row['amount'] : ($row['principal_amount'] + $row['interest_amount']),
                'total_amount' => $row['amount'] ? $row['amount'] : ($row['principal_amount'] + $row['interest_amount']),
                'balance' => max(0, $runningBalance),
                'status' => 'paid', // All payments in loan_payments table are completed payments
                'payment_reference' => $row['payment_reference'] ?? null,
                'created_at' => $row['created_at'] ?? null
            ];
            }
            
            // Update loan with payment data
            $loan['payment_schedule'] = $paymentSchedule;
            $loan['total_paid'] = $totalPaid;
            $loan['last_payment_date'] = $lastPaymentDate;
        }
        
        // Calculate payment status
        $remainingBalance = floatval($loan['current_balance']);
        $loanAmount = floatval($loan['loan_amount']);
        
        if ($remainingBalance <= 0.01) {
            $loan['payment_status'] = 'Fully Paid';
        } elseif ($loan['status'] === 'defaulted') {
            $loan['payment_status'] = 'Overdue';
        } elseif ($loan['status'] === 'active' || $loan['status'] === 'pending') {
            // Check if overdue based on maturity date
            $maturityDate = strtotime($loan['maturity_date']);
            $today = strtotime(date('Y-m-d'));
            if ($maturityDate < $today && $remainingBalance > 0.01) {
                $loan['payment_status'] = 'Overdue';
            } else {
                $loan['payment_status'] = 'Active';
            }
        } else {
            $loan['payment_status'] = ucfirst($loan['status']);
        }
    } else {
        // No payments table, set defaults
        $loan['total_paid'] = 0;
        $loan['last_payment_date'] = null;
        $loan['payment_status'] = $loan['status'] === 'paid' ? 'Fully Paid' : ($loan['status'] === 'defaulted' ? 'Overdue' : 'Active');
    }
    
    // Get transaction history if table exists
    if (tableExists('loan_transactions')) {
        $sql = "SELECT 
                    lt.*,
                    u.full_name as processed_by_name
                FROM loan_transactions lt
                LEFT JOIN users u ON lt.processed_by = u.id
                WHERE lt.loan_id = ?
                ORDER BY lt.transaction_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        $loan['transactions'] = $transactions;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $loan
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Get audit trail for loans
 * Uses audit_logs table
 */
function getAuditTrail() {
    global $conn;
    
    $loanId = $_GET['id'] ?? '';
    
    $sql = "SELECT 
                al.*,
                u.username,
                u.full_name,
                l.loan_no as loan_number
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN loans l ON CAST(al.object_id AS UNSIGNED) = l.id
            WHERE al.object_type = 'loan'";
    
    $params = [];
    $types = '';
    
    if (!empty($loanId)) {
        $sql .= " AND al.object_id = ?";
        $params[] = $loanId;
        $types .= 's';
    }
    
    $sql .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Get loan statistics for dashboard cards
 */
function getStatistics() {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total_loans,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_loans,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_loans,
                SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as defaulted_loans,
                SUM(principal_amount) as total_amount,
                SUM(current_balance) as total_outstanding
            FROM loans
            WHERE (deleted_at IS NULL OR deleted_at = '')";
    
    $result = $conn->query($sql);
    $stats = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Soft delete loan (move to bin)
 * Updates loans table to mark as deleted
 */
function softDeleteLoan() {
    global $conn;
    
    $loanId = $_POST['loan_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($loanId)) {
        throw new Exception('Loan ID is required');
    }
    
    // Ensure soft delete columns exist
    ensureSoftDeleteColumnsExist($conn);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First check if loan exists and is not already deleted
        $checkSql = "SELECT id, status FROM loans WHERE id = ? AND (deleted_at IS NULL OR deleted_at = '')";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('i', $loanId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $loan = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if (!$loan) {
            throw new Exception('Loan not found or already deleted');
        }
        
        // Soft delete by setting deleted_at timestamp (preserve original status)
        // Use COALESCE to handle cases where column might not exist yet
        $sql = "UPDATE loans 
                SET deleted_at = NOW(), deleted_by = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare delete statement: ' . $conn->error);
        }
        
        $stmt->bind_param('ii', $currentUser['id'], $loanId);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute delete: ' . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Loan not found or already deleted (affected_rows: 0)');
        }
        $stmt->close();
        
        // Verify the deletion worked
        $verifySql = "SELECT id, deleted_at FROM loans WHERE id = ?";
        $verifyStmt = $conn->prepare($verifySql);
        $verifyStmt->bind_param('i', $loanId);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();
        $verifyLoan = $verifyResult->fetch_assoc();
        $verifyStmt->close();
        
        if (!$verifyLoan || empty($verifyLoan['deleted_at'])) {
            throw new Exception('Verification failed: Loan was not properly soft deleted');
        }
        
        // Log the deletion in audit trail
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'DELETE', 'loan', ?, 'Loan moved to bin', ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iis', $currentUser['id'], $loanId, $ipAddress);
            $auditStmt->execute();
        }
        
        // Log activity
        logActivity('delete', 'loan_accounting', "Deleted loan #$loanId", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan moved to bin successfully'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Restore loan from bin
 */
function restoreLoan() {
    global $conn;
    
    $loanId = $_POST['loan_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($loanId)) {
        throw new Exception('Loan ID is required');
    }
    
    // Ensure soft delete columns exist
    ensureSoftDeleteColumnsExist($conn);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Restore loan by clearing deleted_at (preserve original status)
        $sql = "UPDATE loans 
                SET deleted_at = NULL, deleted_by = NULL
                WHERE id = ? AND deleted_at IS NOT NULL AND deleted_at != ''";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $loanId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Loan not found or not in bin');
        }
        $stmt->close();
        
        // Log the restoration in audit trail
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'RESTORE', 'loan', ?, 'Loan restored from bin', ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iis', $currentUser['id'], $loanId, $ipAddress);
            $auditStmt->execute();
        }
        
        // Log activity
        logActivity('restore', 'loan_accounting', "Restored loan #$loanId from bin", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan restored successfully'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Get all deleted loans (bin items)
 */
function getBinItems() {
    global $conn;
    
    // Ensure soft delete columns exist
    ensureSoftDeleteColumnsExist($conn);
    
    $sql = "SELECT 
                l.id,
                l.loan_no as loan_number,
                l.borrower_external_no as borrower_name,
                l.principal_amount as loan_amount,
                l.current_balance as outstanding_balance,
                l.start_date,
                DATE_ADD(l.start_date, INTERVAL l.term_months MONTH) as maturity_date,
                l.deleted_at,
                l.status,
                lt.name as loan_type_name,
                COALESCE(u.username, '') as deleted_by_username,
                COALESCE(u.full_name, '') as deleted_by_name,
                'loan' as item_type
            FROM loans l
            LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
            LEFT JOIN users u ON l.deleted_by = u.id
            WHERE l.deleted_at IS NOT NULL AND l.deleted_at != ''
            ORDER BY l.deleted_at DESC";
    
    $result = $conn->query($sql);
    
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $items
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Permanently delete loan (hard delete)
 */
function permanentDeleteLoan() {
    global $conn;
    
    $loanId = $_POST['loan_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($loanId)) {
        throw new Exception('Loan ID is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Log the permanent deletion in audit trail first
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'PERMANENT_DELETE', 'loan', ?, 'Loan permanently deleted from bin', ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iis', $currentUser['id'], $loanId, $ipAddress);
            $auditStmt->execute();
        }
        
        // Delete related records first (foreign key constraints)
        if (tableExists('loan_payments')) {
            $deletePaymentsSql = "DELETE FROM loan_payments WHERE loan_id = ?";
            $deletePaymentsStmt = $conn->prepare($deletePaymentsSql);
            $deletePaymentsStmt->bind_param('i', $loanId);
            $deletePaymentsStmt->execute();
        }
        
        if (tableExists('loan_transactions')) {
            $deleteTransSql = "DELETE FROM loan_transactions WHERE loan_id = ?";
            $deleteTransStmt = $conn->prepare($deleteTransSql);
            $deleteTransStmt->bind_param('i', $loanId);
            $deleteTransStmt->execute();
        }
        
        // Delete the loan (only if soft deleted)
        $deleteLoanSql = "DELETE FROM loans WHERE id = ? AND deleted_at IS NOT NULL AND deleted_at != ''";
        $deleteLoanStmt = $conn->prepare($deleteLoanSql);
        $deleteLoanStmt->bind_param('i', $loanId);
        $deleteLoanStmt->execute();
        
        if ($deleteLoanStmt->affected_rows === 0) {
            throw new Exception('Loan not found or not in bin');
        }
        $deleteLoanStmt->close();
        
        // Log activity
        logActivity('permanent_delete', 'loan_accounting', "Permanently deleted loan #$loanId from bin", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Loan permanently deleted'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Export loans to Excel
 */
function exportToExcel() {
    global $conn;
    
    // Log export activity
    logActivity('export', 'loan_accounting', 'Exported loans to Excel', $conn);
    
    // This would require PHPSpreadsheet library
    // For now, return a message
    throw new Exception('Excel export requires PHPSpreadsheet library to be installed');
}

/**
 * Process a loan payment
 * Records payment in loan_payments table and updates loan balance
 */
function processPayment() {
    global $conn;
    
    $loanId = $_POST['loan_id'] ?? '';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $amount = $_POST['amount'] ?? '';
    $principalAmount = $_POST['principal_amount'] ?? '';
    $interestAmount = $_POST['interest_amount'] ?? '';
    $paymentReference = $_POST['payment_reference'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($loanId)) {
        throw new Exception('Loan ID is required');
    }
    
    if (empty($amount) || floatval($amount) <= 0) {
        throw new Exception('Payment amount must be greater than zero');
    }
    
    // Calculate principal and interest if not provided
    if (empty($principalAmount) || empty($interestAmount)) {
        // Get loan details to calculate interest
        $loanSql = "SELECT principal_amount, interest_rate, current_balance, monthly_payment 
                    FROM loans WHERE id = ?";
        $loanStmt = $conn->prepare($loanSql);
        $loanStmt->bind_param('i', $loanId);
        $loanStmt->execute();
        $loanResult = $loanStmt->get_result();
        $loan = $loanResult->fetch_assoc();
        
        if (!$loan) {
            throw new Exception('Loan not found');
        }
        
        // Calculate interest based on outstanding balance
        $monthlyInterestRate = floatval($loan['interest_rate']) / 12 / 100;
        $outstandingBalance = floatval($loan['current_balance']);
        
        // If only total amount provided, split it proportionally
        $totalPayment = floatval($amount);
        if (empty($principalAmount)) {
            $interestAmount = min($outstandingBalance * $monthlyInterestRate, $totalPayment * 0.3); // Interest shouldn't exceed 30% of payment
            $principalAmount = $totalPayment - $interestAmount;
        } elseif (empty($interestAmount)) {
            $principalAmount = floatval($principalAmount);
            $interestAmount = $totalPayment - $principalAmount;
        }
    } else {
        $principalAmount = floatval($principalAmount);
        $interestAmount = floatval($interestAmount);
        $totalPayment = floatval($amount);
        
        // Validate that amounts add up
        if (abs(($principalAmount + $interestAmount) - $totalPayment) > 0.01) {
            throw new Exception('Principal and interest amounts must equal total payment amount');
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current loan balance
        $loanSql = "SELECT current_balance, principal_amount, status FROM loans WHERE id = ? FOR UPDATE";
        $loanStmt = $conn->prepare($loanSql);
        $loanStmt->bind_param('i', $loanId);
        $loanStmt->execute();
        $loanResult = $loanStmt->get_result();
        $loan = $loanResult->fetch_assoc();
        
        if (!$loan) {
            throw new Exception('Loan not found');
        }
        
        $currentBalance = floatval($loan['current_balance']);
        $principalAmount = min($principalAmount, $currentBalance); // Can't pay more principal than owed
        
        // Insert payment record
        $paymentSql = "INSERT INTO loan_payments 
                       (loan_id, payment_date, amount, principal_amount, interest_amount, payment_reference, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $paymentStmt = $conn->prepare($paymentSql);
        $paymentStmt->bind_param('isddds', $loanId, $paymentDate, $totalPayment, $principalAmount, $interestAmount, $paymentReference);
        $paymentStmt->execute();
        
        // Update loan balance
        $newBalance = max(0, $currentBalance - $principalAmount);
        
        // Determine new status
        $newStatus = $loan['status'];
        if ($newBalance <= 0.01 && $loan['status'] === 'active') {
            $newStatus = 'paid';
        } elseif ($loan['status'] === 'pending' && $newBalance > 0) {
            $newStatus = 'active';
        }
        
        $updateSql = "UPDATE loans 
                      SET current_balance = ?, 
                          status = ?,
                          updated_at = NOW()
                      WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('dsi', $newBalance, $newStatus, $loanId);
        $updateStmt->execute();
        
        // Log the payment in audit trail
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'PAYMENT', 'loan', ?, ?, ?, NOW())";
            
            $auditInfo = json_encode([
                'payment_amount' => $totalPayment,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'payment_date' => $paymentDate,
                'payment_reference' => $paymentReference,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance
            ]);
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iiss', $currentUser['id'], $loanId, $auditInfo, $ipAddress);
            $auditStmt->execute();
        }
        
        // Automatically create journal entry for loan payment
        $journalEntryId = createLoanPaymentJournalEntry($conn, $loanId, $totalPayment, $principalAmount, $interestAmount, $paymentDate, $paymentReference, $currentUser, $paymentStmt->insert_id);
        
        // Update loan_payments with journal_entry_id
        if ($journalEntryId) {
            $updatePaymentSql = "UPDATE loan_payments SET journal_entry_id = ? WHERE id = ?";
            $updatePaymentStmt = $conn->prepare($updatePaymentSql);
            $paymentId = $paymentStmt->insert_id;
            $updatePaymentStmt->bind_param('ii', $journalEntryId, $paymentId);
            $updatePaymentStmt->execute();
        }
        
        // Log activity
        logActivity('payment', 'loan_accounting', "Recorded payment of ₱" . number_format($totalPayment, 2) . " for loan #$loanId", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'data' => [
                'payment_id' => $paymentStmt->insert_id,
                'new_balance' => $newBalance,
                'new_status' => $newStatus
            ]
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Automatically create journal entry for loan payment
 */
function createLoanPaymentJournalEntry($conn, $loanId, $totalPayment, $principalAmount, $interestAmount, $paymentDate, $paymentReference, $currentUser, $paymentId) {
    try {
        // Get current fiscal period
        $fiscalPeriodSql = "SELECT id FROM fiscal_periods WHERE status = 'open' ORDER BY start_date DESC LIMIT 1";
        $fiscalResult = $conn->query($fiscalPeriodSql);
        if (!$fiscalResult || $fiscalResult->num_rows === 0) {
            return null;
        }
        $fiscalPeriod = $fiscalResult->fetch_assoc();
        $fiscalPeriodId = $fiscalPeriod['id'];
        
        // Get or create journal type
        $journalTypeSql = "SELECT id FROM journal_types WHERE code = 'LP' LIMIT 1";
        $journalTypeResult = $conn->query($journalTypeSql);
        if (!$journalTypeResult || $journalTypeResult->num_rows === 0) {
            $insertTypeSql = "INSERT INTO journal_types (code, name, description) VALUES ('LP', 'Loan Payment', 'Automatic journal entry from loan payments')";
            $conn->query($insertTypeSql);
            $journalTypeId = $conn->insert_id;
        } else {
            $journalType = $journalTypeResult->fetch_assoc();
            $journalTypeId = $journalType['id'];
        }
        
        // Get loan details
        $loanSql = "SELECT loan_no FROM loans WHERE id = ?";
        $loanStmt = $conn->prepare($loanSql);
        $loanStmt->bind_param('i', $loanId);
        $loanStmt->execute();
        $loanResult = $loanStmt->get_result();
        $loan = $loanResult->fetch_assoc();
        $loanNo = $loan['loan_no'] ?? "LOAN-{$loanId}";
        
        // Get accounts
        $cashAccountSql = "SELECT id FROM accounts WHERE code = '1001' LIMIT 1";
        $loanAccountSql = "SELECT id FROM accounts WHERE code = '1103' LIMIT 1";
        $interestAccountSql = "SELECT id FROM accounts WHERE code = '5101' LIMIT 1";
        
        $cashResult = $conn->query($cashAccountSql);
        $loanResult = $conn->query($loanAccountSql);
        $interestResult = $conn->query($interestAccountSql);
        
        if (!$cashResult || $cashResult->num_rows === 0) return null;
        if (!$loanResult || $loanResult->num_rows === 0) return null;
        if (!$interestResult || $interestResult->num_rows === 0) return null;
        
        $cashAccount = $cashResult->fetch_assoc();
        $loanAccount = $loanResult->fetch_assoc();
        $interestAccount = $interestResult->fetch_assoc();
        
        $cashAccountId = $cashAccount['id'];
        $loanAccountId = $loanAccount['id'];
        $interestAccountId = $interestAccount['id'];
        
        // Generate journal number
        $journalNo = 'LP-' . date('Ymd') . '-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT);
        $description = "Loan payment for {$loanNo} - Principal: ₱" . number_format($principalAmount, 2) . ", Interest: ₱" . number_format($interestAmount, 2);
        $referenceNo = 'LP-' . $paymentId;
        
        // Create journal entry
        $journalSql = "INSERT INTO journal_entries 
            (journal_no, journal_type_id, entry_date, description, fiscal_period_id, 
             reference_no, total_debit, total_credit, status, created_by, posted_by, posted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'posted', ?, ?, NOW())";
        
        $stmt = $conn->prepare($journalSql);
        $stmt->bind_param('siissiddii', 
            $journalNo, 
            $journalTypeId, 
            $paymentDate,
            $description,
            $fiscalPeriodId,
            $referenceNo,
            $totalPayment,
            $totalPayment,
            $currentUser['id'],
            $currentUser['id']
        );
        
        if (!$stmt->execute()) {
            return null;
        }
        
        $journalEntryId = $conn->insert_id;
        
        // Create journal lines
        // Debit Cash
        $lineSql = "INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
                   VALUES (?, ?, ?, 0, ?)";
        $stmt = $conn->prepare($lineSql);
        $memo = "Loan payment received - {$loanNo}";
        $stmt->bind_param('iids', $journalEntryId, $cashAccountId, $totalPayment, $memo);
        $stmt->execute();
        
        // Credit Loan Receivable (principal)
        $lineSql = "INSERT INTO journal_lines (journal_entry_id, account_id, debit, credit, memo)
                   VALUES (?, ?, 0, ?, ?)";
        $stmt = $conn->prepare($lineSql);
        $memo = "Principal payment - {$loanNo}";
        $stmt->bind_param('iids', $journalEntryId, $loanAccountId, $principalAmount, $memo);
        $stmt->execute();
        
        // Credit Interest Income (interest)
        if ($interestAmount > 0) {
            $stmt = $conn->prepare($lineSql);
            $memo = "Interest income - {$loanNo}";
            $stmt->bind_param('iids', $journalEntryId, $interestAccountId, $interestAmount, $memo);
            $stmt->execute();
        }
        
        return $journalEntryId;
    } catch (Exception $e) {
        error_log("Error creating loan payment journal entry: " . $e->getMessage());
        return null;
    }
}

/**
 * Ensure soft delete columns exist in loans table
 */
function ensureSoftDeleteColumnsExist($conn) {
    try {
        // Check if deleted_at column exists
        $checkSql = "SHOW COLUMNS FROM loans LIKE 'deleted_at'";
        $result = $conn->query($checkSql);
        
        if (!$result || $result->num_rows === 0) {
            // Add deleted_at column
            $alterSql = "ALTER TABLE loans ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
            if (!$conn->query($alterSql)) {
                error_log("Failed to add deleted_at column: " . $conn->error);
            } else {
                error_log("Successfully added deleted_at column to loans table");
            }
        }
        
        // Check if deleted_by column exists
        $checkSql = "SHOW COLUMNS FROM loans LIKE 'deleted_by'";
        $result = $conn->query($checkSql);
        
        if (!$result || $result->num_rows === 0) {
            // Add deleted_by column
            $alterSql = "ALTER TABLE loans ADD COLUMN deleted_by INT NULL DEFAULT NULL";
            if (!$conn->query($alterSql)) {
                error_log("Failed to add deleted_by column: " . $conn->error);
            } else {
                error_log("Successfully added deleted_by column to loans table");
            }
        }
        
        // Add index if it doesn't exist
        $indexCheck = "SHOW INDEX FROM loans WHERE Key_name = 'idx_deleted_at'";
        $indexResult = $conn->query($indexCheck);
        if (!$indexResult || $indexResult->num_rows === 0) {
            $indexSql = "ALTER TABLE loans ADD INDEX idx_deleted_at (deleted_at)";
            if (!$conn->query($indexSql)) {
                error_log("Failed to add idx_deleted_at index: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail - columns might already exist
        error_log("Error ensuring soft delete columns exist: " . $e->getMessage());
    }
}

/**
 * Get detailed information for a loan application
 * Including all application fields from the updated schema
 */
function getApplicationDetails() {
    global $conn;
    
    $applicationId = $_GET['id'] ?? '';
    
    if (empty($applicationId)) {
        throw new Exception('Application ID is required');
    }
    
    // Get main application data with all new fields
    $sql = "SELECT 
                la.*,
                la.id as application_id,
                CONCAT('APP-', la.id) as application_number,
                COALESCE(la.full_name, la.user_email) as borrower_name,
                la.loan_amount,
                la.loan_type,
                la.loan_terms,
                la.monthly_payment,
                la.due_date,
                la.next_payment_due,
                la.status,
                la.purpose,
                la.remarks,
                la.file_name,
                la.proof_of_income,
                la.coe_document,
                la.pdf_path,
                la.approved_by,
                la.approved_at,
                la.rejected_by,
                la.rejected_at,
                la.rejection_remarks,
                lt.name as loan_type_name,
                lt.description as loan_type_description,
                lt.interest_rate as loan_type_interest_rate,
                u_app.full_name as approved_by_name,
                u_rej.full_name as rejected_by_name,
                la.created_at
            FROM loan_applications la
            LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
            LEFT JOIN users u_app ON la.approved_by_user_id = u_app.id
            LEFT JOIN users u_rej ON la.rejected_by_user_id = u_rej.id
            WHERE la.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $application = $result->fetch_assoc();
    
    if (!$application) {
        throw new Exception('Application not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $application
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Delete loan application (hard delete)
 */
function deleteApplication() {
    global $conn;
    
    $applicationId = $_POST['application_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($applicationId)) {
        throw new Exception('Application ID is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if application exists
        $checkSql = "SELECT id, status FROM loan_applications WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('i', $applicationId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $application = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if (!$application) {
            throw new Exception('Application not found');
        }
        
        // Delete the application
        $deleteSql = "DELETE FROM loan_applications WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $applicationId);
        $deleteStmt->execute();
        
        if ($deleteStmt->affected_rows === 0) {
            throw new Exception('Failed to delete application');
        }
        $deleteStmt->close();
        
        // Log the deletion in audit trail
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'DELETE', 'loan_application', ?, 'Loan application permanently deleted', ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iis', $currentUser['id'], $applicationId, $ipAddress);
            $auditStmt->execute();
        }
        
        // Log activity
        logActivity('delete', 'loan_accounting', "Deleted loan application #$applicationId", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application deleted successfully'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Check if a table exists
 */
function tableExists($tableName) {
    global $conn;
    
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

