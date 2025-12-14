<?php
/**
 * Transaction Data API
 * Handles database queries for transaction recording module
 * 
 * Database Tables Used (from schema.sql):
 * - journal_entries: Main transaction records
 * - journal_lines: Individual debit/credit lines
 * - journal_types: Transaction types (GJ, CR, CD, etc.)
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
        case 'get_transactions':
            getTransactions();
            break;
        
        case 'get_transaction_details':
            getTransactionDetails();
            break;
        
        case 'get_audit_trail':
            getAuditTrail();
            break;
        
        case 'get_statistics':
            getStatistics();
            break;
        
        case 'soft_delete_transaction':
            softDeleteTransaction();
            break;
        
        case 'restore_transaction':
            restoreTransaction();
            break;
        
        case 'get_bin_items':
            getBinItems();
            break;
        
        case 'permanent_delete_transaction':
            permanentDeleteTransaction();
            break;
        
        case 'permanent_delete_bank_transaction':
            permanentDeleteBankTransaction();
            break;
        
        case 'restore_all_transactions':
            restoreAllTransactions();
            break;
        
        case 'empty_bin_transactions':
            emptyBinTransactions();
            break;
        
        case 'sync_bank_transactions':
            syncBankTransactionsToJournal();
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
 * Get transactions with optional filters
 * 
 * Query from schema tables:
 * - journal_entries
 * - journal_types
 * - users
 * - fiscal_periods
 */
function getTransactions() {
    global $conn;
    
    // Get filter parameters
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';
    $account = $_GET['account'] ?? '';
    
    // Base query using schema tables
    $sql = "SELECT 
                je.id,
                je.journal_no,
                je.entry_date,
                jt.code as type_code,
                jt.name as type_name,
                je.description,
                je.reference_no,
                je.total_debit,
                je.total_credit,
                je.status,
                u.username as created_by,
                u.full_name as created_by_name,
                je.created_at,
                je.posted_at,
                fp.period_name as fiscal_period
            FROM journal_entries je
            INNER JOIN journal_types jt ON je.journal_type_id = jt.id
            INNER JOIN users u ON je.created_by = u.id
            LEFT JOIN fiscal_periods fp ON je.fiscal_period_id = fp.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Apply filters
    if (!empty($dateFrom)) {
        $sql .= " AND je.entry_date >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND je.entry_date <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    if (!empty($type)) {
        $sql .= " AND jt.code = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $sql .= " AND je.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Filter by account number (join with journal_lines and accounts)
    if (!empty($account)) {
        $sql .= " AND EXISTS (
            SELECT 1 FROM journal_lines jl
            INNER JOIN accounts a ON jl.account_id = a.id
            WHERE jl.journal_entry_id = je.id AND a.code LIKE ?
        )";
        $params[] = "%{$account}%";
        $types .= 's';
    }
    
    $sql .= " ORDER BY je.entry_date DESC, je.journal_no DESC";
    
    // Prepare and execute
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'count' => count($transactions)
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Get detailed information for a specific transaction
 * Including all journal lines
 * Handles both journal entries (JE- prefix) and bank transactions (BT- prefix)
 */
function getTransactionDetails() {
    global $conn;
    
    $transactionId = $_GET['id'] ?? '';
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Extract numeric ID and determine source from prefix
    $numericId = $transactionId;
    $source = 'journal'; // default
    
    if (is_string($transactionId) && strpos($transactionId, '-') !== false) {
        $parts = explode('-', $transactionId);
        if (count($parts) > 1) {
            $prefix = strtoupper($parts[0]);
            $numericId = $parts[1];
            
            if ($prefix === 'BT') {
                $source = 'bank';
            } elseif ($prefix === 'JE') {
                $source = 'journal';
            }
        }
    }
    
    $numericId = (int)$numericId;
    if ($numericId <= 0) {
        throw new Exception('Invalid transaction ID');
    }
    
    $transaction = null;
    $lines = [];
    
    if ($source === 'bank') {
        // Get bank transaction details
        $sql = "SELECT 
                    bt.transaction_id as id,
                    bt.transaction_ref as journal_no,
                    DATE(bt.created_at) as entry_date,
                    COALESCE(bt.description, 'Bank Transaction') as description,
                    bt.transaction_ref as reference_no,
                    CASE WHEN bt.amount > 0 THEN bt.amount ELSE 0 END as total_debit,
                    CASE WHEN bt.amount < 0 THEN ABS(bt.amount) ELSE 0 END as total_credit,
                    'posted' as status,
                    COALESCE(be.employee_name, 'System') as created_by,
                    COALESCE(be.employee_name, 'System') as created_by_name,
                    bt.created_at,
                    bt.created_at as posted_at,
                    DATE_FORMAT(bt.created_at, '%Y-%m') as fiscal_period,
                    tt.type_name as type_code,
                    tt.type_name as type_name,
                    'bank' as source
                FROM bank_transactions bt
                INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                LEFT JOIN bank_employees be ON bt.employee_id = be.employee_id
                WHERE bt.transaction_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $numericId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if (!$transaction) {
            throw new Exception('Bank transaction not found');
        }
        
        // Bank transactions don't have journal lines, so lines array stays empty
    } else {
        // Get journal entry details
        $sql = "SELECT 
                    je.*,
                    jt.code as type_code,
                    jt.name as type_name,
                    u.username as created_by,
                    u.full_name as created_by_name,
                    fp.period_name as fiscal_period,
                    pu.username as posted_by,
                    pu.full_name as posted_by_name
                FROM journal_entries je
                INNER JOIN journal_types jt ON je.journal_type_id = jt.id
                INNER JOIN users u ON je.created_by = u.id
                LEFT JOIN fiscal_periods fp ON je.fiscal_period_id = fp.id
                LEFT JOIN users pu ON je.posted_by = pu.id
                WHERE je.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $numericId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        if (!$transaction) {
            throw new Exception('Journal entry not found');
        }
        
        // Get journal lines
        $sql = "SELECT 
                    jl.*,
                    a.code as account_code,
                    a.name as account_name,
                    at.category as account_category
                FROM journal_lines jl
                INNER JOIN accounts a ON jl.account_id = a.id
                INNER JOIN account_types at ON a.type_id = at.id
                WHERE jl.journal_entry_id = ?
                ORDER BY jl.id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $numericId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $lines[] = $row;
        }
    }
    
    $transaction['lines'] = $lines;
    
    echo json_encode([
        'success' => true,
        'data' => $transaction
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Get audit trail for transactions
 * Uses audit_logs table from schema
 * Handles both journal entries (JE- prefix) and bank transactions (BT- prefix)
 */
function getAuditTrail() {
    global $conn;
    
    $transactionId = $_GET['id'] ?? '';
    
    // Extract numeric ID from prefixed ID if present
    $numericId = $transactionId;
    $objectType = 'journal_entry'; // default
    
    if (is_string($transactionId) && strpos($transactionId, '-') !== false) {
        $parts = explode('-', $transactionId);
        if (count($parts) > 1) {
            $prefix = strtoupper($parts[0]);
            $numericId = $parts[1];
            
            if ($prefix === 'BT') {
                $objectType = 'bank_transaction';
            } elseif ($prefix === 'JE') {
                $objectType = 'journal_entry';
            }
        }
    }
    
    $sql = "SELECT 
                al.*,
                u.username,
                u.full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.object_type = ?";
    
    $params = [$objectType];
    $types = 's';
    
    if (!empty($numericId) && $numericId !== '') {
        $sql .= " AND al.object_id = ?";
        $params[] = (int)$numericId;
        $types .= 'i';
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
 * Get transaction statistics for dashboard cards
 */
function getStatistics() {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN DATE(entry_date) = CURDATE() THEN 1 ELSE 0 END) as today_count,
                SUM(total_debit) as total_debit,
                SUM(total_credit) as total_credit
            FROM journal_entries";
    
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
 * Export transactions to Excel
 * Note: Requires PHPSpreadsheet library
 */
function exportToExcel() {
    global $conn;
    $currentUser = getCurrentUser();
    
    // Log export activity
    logActivity('export', 'transaction_reading', 'Exported transactions to Excel', $conn);
    
    // This would require PHPSpreadsheet library
    // Implementation example:
    /*
    require_once '../../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Add headers
    $sheet->setCellValue('A1', 'Journal No');
    $sheet->setCellValue('B1', 'Date');
    // ... etc
    
    // Get data and populate
    // ...
    
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="transactions.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer->save('php://output');
    */
    
    throw new Exception('Excel export requires PHPSpreadsheet library to be installed');
}

/**
 * Soft delete transaction (move to bin)
 * Updates journal_entries table to mark as deleted
 */
function softDeleteTransaction() {
    global $conn;
    
    $originalTransactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($originalTransactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Extract numeric ID and determine source from prefix
    $numericId = $originalTransactionId;
    $source = 'journal'; // default
    
    if (is_string($originalTransactionId) && strpos($originalTransactionId, '-') !== false) {
        $parts = explode('-', $originalTransactionId);
        if (count($parts) > 1) {
            $prefix = strtoupper($parts[0]);
            $numericId = $parts[1];
            
            if ($prefix === 'BT') {
                $source = 'bank';
            } elseif ($prefix === 'JE') {
                $source = 'journal';
            }
        }
    }
    
    $numericId = (int)$numericId;
    if ($numericId <= 0) {
        throw new Exception('Invalid transaction ID');
    }
    
    // Handle bank transactions - soft delete to bin station
    if ($source === 'bank') {
        $conn->begin_transaction();
        try {
            // Check if deleted_at column exists in bank_transactions, if not add it
            $checkCol = $conn->query("SHOW COLUMNS FROM bank_transactions LIKE 'deleted_at'");
            if ($checkCol && $checkCol->num_rows === 0) {
                $conn->query("ALTER TABLE bank_transactions ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL");
                $conn->query("ALTER TABLE bank_transactions ADD COLUMN deleted_by INT NULL DEFAULT NULL");
            }
            
            // Log the deletion in audit trail (if audit_logs table exists)
            if (tableExists('audit_logs')) {
                $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                             VALUES (?, 'DELETE', 'bank_transaction', ?, 'Bank transaction moved to bin', ?, NOW())";
                $auditStmt = $conn->prepare($auditSql);
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $auditStmt->bind_param('iis', $currentUser['id'], $numericId, $ipAddress);
                $auditStmt->execute();
            }
            
            // Soft delete - set deleted_at timestamp
            $sql = "UPDATE bank_transactions SET deleted_at = NOW(), deleted_by = ? WHERE transaction_id = ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param('ii', $currentUser['id'], $numericId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Bank transaction not found or already deleted');
            }
            
            logActivity('delete', 'transaction_reading', "Deleted bank transaction #$numericId", $conn);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Bank transaction moved to bin successfully',
                'soft_delete_available' => true,
                'transaction_id' => $originalTransactionId,
                'numeric_id' => $numericId
            ]);
            
            ob_end_flush();
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    // Check if soft delete columns exist for journal entries
    $columnsExist = checkSoftDeleteColumnsExist();
    $deletedStatusExists = checkDeletedStatusExists();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if ($columnsExist && $deletedStatusExists) {
            // Full soft delete support: use deleted status with timestamps
            $sql = "UPDATE journal_entries 
                    SET status = 'deleted', 
                        deleted_at = NOW(), 
                        deleted_by = ?
                    WHERE id = ? AND status NOT IN ('voided', 'deleted')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param('ii', $currentUser['id'], $numericId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
        } else if ($columnsExist && !$deletedStatusExists) {
            // Has deleted_at column but status ENUM doesn't have 'deleted'
            // Use 'voided' status but still track deletion metadata
            $sql = "UPDATE journal_entries 
                    SET status = 'voided',
                        deleted_at = NOW(), 
                        deleted_by = ?
                    WHERE id = ? AND status NOT IN ('voided', 'deleted')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param('ii', $currentUser['id'], $numericId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
        } else {
            // Fallback: just update status to 'voided' (no soft delete metadata)
            $sql = "UPDATE journal_entries 
                    SET status = 'voided'
                    WHERE id = ? AND status NOT IN ('voided', 'deleted')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            $stmt->bind_param('i', $numericId);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Transaction not found or already deleted/voided');
        }
        
        // Log the deletion in audit trail (if audit_logs table exists)
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'DELETE', 'journal_entry', ?, ?, ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $action = $columnsExist ? 'Transaction moved to bin' : 'Transaction voided (soft delete not available)';
            $auditStmt->bind_param('iiss', $currentUser['id'], $numericId, $action, $ipAddress);
            $auditStmt->execute();
        }
        
        // Log activity
        logActivity('delete', 'transaction_reading', "Deleted transaction #$numericId", $conn);
        
        $conn->commit();
        
        $message = $columnsExist ? 'Transaction moved to bin successfully' : 'Transaction voided successfully (soft delete columns not available)';
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'soft_delete_available' => $columnsExist
        ]);
        
        // Flush output buffer
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Check if soft delete columns exist in journal_entries table
 */
function checkSoftDeleteColumnsExist() {
    global $conn;
    
    $result = $conn->query("SHOW COLUMNS FROM journal_entries LIKE 'deleted_at'");
    return $result && $result->num_rows > 0;
}

/**
 * Check if 'deleted' status exists in journal_entries status ENUM
 */
function checkDeletedStatusExists() {
    global $conn;
    
    $result = $conn->query("SHOW COLUMNS FROM journal_entries LIKE 'status'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $type = $row['Type'];
        // Check if 'deleted' is in the ENUM values
        return strpos($type, "'deleted'") !== false;
    }
    return false;
}

/**
 * Check if a table exists
 */
function tableExists($tableName) {
    global $conn;
    
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

/**
 * Restore transaction from bin
 * Updates journal_entries table to restore from deleted state
 */
function restoreTransaction() {
    global $conn;
    
    $originalTransactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($originalTransactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Extract numeric ID and determine source from prefix
    $numericId = $originalTransactionId;
    $source = 'journal'; // default
    
    if (is_string($originalTransactionId) && strpos($originalTransactionId, '-') !== false) {
        $parts = explode('-', $originalTransactionId);
        if (count($parts) > 1) {
            $prefix = strtoupper($parts[0]);
            $numericId = (int)$parts[1];
            if ($prefix === 'BT') {
                $source = 'bank';
            }
        }
    }
    $numericId = (int)$numericId;
    
    if ($numericId <= 0) {
        throw new Exception('Invalid transaction ID');
    }
    
    // Handle bank transaction restore
    if ($source === 'bank') {
        $conn->begin_transaction();
        try {
            $sql = "UPDATE bank_transactions SET deleted_at = NULL, deleted_by = NULL WHERE transaction_id = ? AND deleted_at IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $numericId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Bank transaction not found in bin');
            }
            
            logActivity('restore', 'transaction_reading', "Restored bank transaction #$numericId", $conn);
            
            $conn->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Bank transaction restored successfully'
            ]);
            
            ob_end_flush();
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    // Start transaction for journal entries
    $conn->begin_transaction();
    
    try {
        // Check what status we're dealing with
        $hasDeletedAt = checkSoftDeleteColumnsExist();
        $hasDeletedStatus = checkDeletedStatusExists();
        
        // Check if restored_at and restored_by columns exist
        $hasRestoredColumns = false;
        try {
            $result = $conn->query("SHOW COLUMNS FROM journal_entries LIKE 'restored_at'");
            $hasRestoredColumns = $result && $result->num_rows > 0;
        } catch (Exception $e) {
            $hasRestoredColumns = false;
        }
        
        if ($hasDeletedAt && $hasDeletedStatus) {
            // Full soft delete: restore from 'deleted' status to 'posted'
            if ($hasRestoredColumns) {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted', 
                            deleted_at = NULL, 
                            deleted_by = NULL,
                            restored_at = NOW(),
                            restored_by = ?
                        WHERE id = ? AND status = 'deleted'";
            } else {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted', 
                            deleted_at = NULL, 
                            deleted_by = NULL
                        WHERE id = ? AND status = 'deleted'";
            }
        } else if ($hasDeletedAt && !$hasDeletedStatus) {
            // Has deleted_at but no 'deleted' status: restore from 'voided' with deleted_at to 'posted'
            if ($hasRestoredColumns) {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted',
                            deleted_at = NULL, 
                            deleted_by = NULL,
                            restored_at = NOW(),
                            restored_by = ?
                        WHERE id = ? AND status = 'voided' AND deleted_at IS NOT NULL";
            } else {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted',
                            deleted_at = NULL, 
                            deleted_by = NULL
                        WHERE id = ? AND status = 'voided' AND deleted_at IS NOT NULL";
            }
        } else {
            // No soft delete columns: restore from 'voided' status to 'posted'
            $sql = "UPDATE journal_entries 
                    SET status = 'posted'
                    WHERE id = ? AND status = 'voided'";
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        if ($hasDeletedAt && $hasRestoredColumns) {
            $stmt->bind_param('ii', $currentUser['id'], $numericId);
        } else if ($hasDeletedAt) {
            $stmt->bind_param('i', $numericId);
        } else {
            $stmt->bind_param('i', $numericId);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception('Transaction not found or not in bin');
        }
        
        // Log the restoration in audit trail
        $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                     VALUES (?, 'RESTORE', 'journal_entry', ?, 'Transaction restored from bin', ?, NOW())";
        
        $auditStmt = $conn->prepare($auditSql);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $auditStmt->bind_param('iis', $currentUser['id'], $transactionId, $ipAddress);
        $auditStmt->execute();
        
        // Log activity
        logActivity('restore', 'transaction_reading', "Restored transaction #$transactionId from bin", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction restored successfully'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Get all deleted transactions (bin items)
 */
function getBinItems() {
    global $conn;
    
    $items = [];
    
    // 1. Get deleted journal entries
    $hasDeletedAt = checkSoftDeleteColumnsExist();
    $hasDeletedStatus = checkDeletedStatusExists();
    
    if ($hasDeletedAt) {
        $whereClause = "je.deleted_at IS NOT NULL";
    } else if ($hasDeletedStatus) {
        $whereClause = "je.status = 'deleted'";
    } else {
        $whereClause = "je.status = 'voided'";
    }
    
    $sql = "SELECT 
                je.id,
                je.journal_no,
                je.entry_date,
                je.description,
                je.reference_no,
                je.total_debit,
                je.total_credit,
                " . ($hasDeletedAt ? "je.deleted_at" : "je.updated_at as deleted_at") . ",
                jt.code as type_code,
                jt.name as type_name,
                " . ($hasDeletedAt ? "u.username as deleted_by_username, u.full_name as deleted_by_name" : "NULL as deleted_by_username, NULL as deleted_by_name") . ",
                'journal_entry' as item_type
            FROM journal_entries je
            INNER JOIN journal_types jt ON je.journal_type_id = jt.id
            " . ($hasDeletedAt ? "LEFT JOIN users u ON je.deleted_by = u.id" : "") . "
            WHERE $whereClause
            ORDER BY " . ($hasDeletedAt ? "je.deleted_at" : "je.updated_at") . " DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    // 2. Get deleted bank transactions
    $checkBankDeletedAt = $conn->query("SHOW COLUMNS FROM bank_transactions LIKE 'deleted_at'");
    if ($checkBankDeletedAt && $checkBankDeletedAt->num_rows > 0) {
        $bankSql = "SELECT 
                        bt.transaction_id as id,
                        bt.transaction_ref as journal_no,
                        DATE(bt.created_at) as entry_date,
                        COALESCE(bt.description, 'Bank Transaction') as description,
                        bt.transaction_ref as reference_no,
                        CASE WHEN bt.amount > 0 THEN bt.amount ELSE 0 END as total_debit,
                        CASE WHEN bt.amount < 0 THEN ABS(bt.amount) ELSE 0 END as total_credit,
                        bt.deleted_at,
                        tt.type_name as type_code,
                        tt.type_name as type_name,
                        NULL as deleted_by_username,
                        NULL as deleted_by_name,
                        'bank_transaction' as item_type
                    FROM bank_transactions bt
                    INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                    WHERE bt.deleted_at IS NOT NULL
                    ORDER BY bt.deleted_at DESC";
        
        $bankResult = $conn->query($bankSql);
        if ($bankResult) {
            while ($row = $bankResult->fetch_assoc()) {
                $items[] = $row;
            }
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
 * Permanently delete transaction (hard delete)
 * Completely removes transaction and related data from database
 */
function permanentDeleteTransaction() {
    global $conn;
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, log the permanent deletion in audit trail
        $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                     VALUES (?, 'PERMANENT_DELETE', 'journal_entry', ?, 'Transaction permanently deleted from bin', ?, NOW())";
        
        $auditStmt = $conn->prepare($auditSql);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $auditStmt->bind_param('iis', $currentUser['id'], $transactionId, $ipAddress);
        $auditStmt->execute();
        
        // Delete journal lines first (foreign key constraint)
        $deleteLinesSql = "DELETE FROM journal_lines WHERE journal_entry_id = ?";
        $deleteLinesStmt = $conn->prepare($deleteLinesSql);
        $deleteLinesStmt->bind_param('i', $transactionId);
        $deleteLinesStmt->execute();
        
        // Delete the journal entry (handle both 'deleted' and 'voided' status)
        $deleteEntrySql = "DELETE FROM journal_entries WHERE id = ? AND status IN ('deleted', 'voided')";
        $deleteEntryStmt = $conn->prepare($deleteEntrySql);
        $deleteEntryStmt->bind_param('i', $transactionId);
        $deleteEntryStmt->execute();
        
        if ($deleteEntryStmt->affected_rows === 0) {
            throw new Exception('Transaction not found or not in bin');
        }
        
        // Log permanent delete activity
        logActivity('permanent_delete', 'transaction_reading', "Permanently deleted transaction #$transactionId from bin", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction permanently deleted'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Permanently delete a bank transaction from bin
 */
function permanentDeleteBankTransaction() {
    global $conn;
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    $numericId = (int)$transactionId;
    if ($numericId <= 0) {
        throw new Exception('Invalid transaction ID');
    }
    
    $conn->begin_transaction();
    
    try {
        // Log the permanent deletion in audit trail
        if (tableExists('audit_logs')) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'PERMANENT_DELETE', 'bank_transaction', ?, 'Bank transaction permanently deleted from bin', ?, NOW())";
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('iis', $currentUser['id'], $numericId, $ipAddress);
            $auditStmt->execute();
        }
        
        // Permanently delete the bank transaction
        $deleteSql = "DELETE FROM bank_transactions WHERE transaction_id = ? AND deleted_at IS NOT NULL";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $numericId);
        $deleteStmt->execute();
        
        if ($deleteStmt->affected_rows === 0) {
            throw new Exception('Bank transaction not found or not in bin');
        }
        
        logActivity('permanent_delete', 'transaction_reading', "Permanently deleted bank transaction #$numericId from bin", $conn);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bank transaction permanently deleted'
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Restore all transactions from bin
 * Restores all deleted/voided transactions back to posted status
 */
function restoreAllTransactions() {
    global $conn;
    
    $currentUser = getCurrentUser();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check what status we're dealing with
        $hasDeletedAt = checkSoftDeleteColumnsExist();
        $hasDeletedStatus = checkDeletedStatusExists();
        
        // Check if restored_at and restored_by columns exist
        $hasRestoredColumns = false;
        try {
            $result = $conn->query("SHOW COLUMNS FROM journal_entries LIKE 'restored_at'");
            $hasRestoredColumns = $result && $result->num_rows > 0;
        } catch (Exception $e) {
            $hasRestoredColumns = false;
        }
        
        $restoredCount = 0;
        
        if ($hasDeletedAt && $hasDeletedStatus) {
            // Full soft delete: restore from 'deleted' status
            if ($hasRestoredColumns) {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted', 
                            deleted_at = NULL, 
                            deleted_by = NULL,
                            restored_at = NOW(),
                            restored_by = ?
                        WHERE status = 'deleted'";
            } else {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted', 
                            deleted_at = NULL, 
                            deleted_by = NULL
                        WHERE status = 'deleted'";
            }
        } else if ($hasDeletedAt && !$hasDeletedStatus) {
            // Has deleted_at but no 'deleted' status: restore from 'voided' with deleted_at
            if ($hasRestoredColumns) {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted',
                            deleted_at = NULL, 
                            deleted_by = NULL,
                            restored_at = NOW(),
                            restored_by = ?
                        WHERE status = 'voided' AND deleted_at IS NOT NULL";
            } else {
                $sql = "UPDATE journal_entries 
                        SET status = 'posted',
                            deleted_at = NULL, 
                            deleted_by = NULL
                        WHERE status = 'voided' AND deleted_at IS NOT NULL";
            }
        } else {
            // No soft delete columns: restore from 'voided' status
            $sql = "UPDATE journal_entries 
                    SET status = 'posted'
                    WHERE status = 'voided'";
        }
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        if ($hasDeletedAt && $hasRestoredColumns) {
            $stmt->bind_param('i', $currentUser['id']);
        } else if ($hasDeletedAt) {
            // No parameters needed if no restored columns
        } else {
            // No parameters needed
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        
        $restoredCount = $stmt->affected_rows;
        
        // Log the restoration in audit trail
        if ($restoredCount > 0) {
            $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                         VALUES (?, 'RESTORE_ALL', 'journal_entry', 0, 'Restored all transactions from bin', ?, NOW())";
            
            $auditStmt = $conn->prepare($auditSql);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bind_param('is', $currentUser['id'], $ipAddress);
            $auditStmt->execute();
            
            // Log activity
            logActivity('restore_all', 'transaction_reading', "Restored $restoredCount journal entries from bin", $conn);
        }
        
        // Also restore bank transactions
        $bankRestoredCount = 0;
        $checkBankDeletedAt = $conn->query("SHOW COLUMNS FROM bank_transactions LIKE 'deleted_at'");
        if ($checkBankDeletedAt && $checkBankDeletedAt->num_rows > 0) {
            $bankSql = "UPDATE bank_transactions SET deleted_at = NULL, deleted_by = NULL WHERE deleted_at IS NOT NULL";
            $bankResult = $conn->query($bankSql);
            if ($bankResult) {
                $bankRestoredCount = $conn->affected_rows;
            }
            if ($bankRestoredCount > 0) {
                logActivity('restore_all', 'transaction_reading', "Restored $bankRestoredCount bank transactions from bin", $conn);
            }
        }
        
        $totalRestored = $restoredCount + $bankRestoredCount;
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully restored $totalRestored transactions ($restoredCount journal entries, $bankRestoredCount bank transactions)",
            'restored_count' => $totalRestored
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Empty bin - permanently delete all transactions
 * Completely removes all deleted/voided transactions from database
 */
function emptyBinTransactions() {
    global $conn;
    
    $currentUser = getCurrentUser();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First, log the permanent deletion in audit trail
        $auditSql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, additional_info, ip_address, created_at) 
                     VALUES (?, 'EMPTY_BIN', 'journal_entry', 0, 'Permanently deleted all transactions from bin', ?, NOW())";
        
        $auditStmt = $conn->prepare($auditSql);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $auditStmt->bind_param('is', $currentUser['id'], $ipAddress);
        $auditStmt->execute();
        
        // Get count of journal entries to be deleted
        $countSql = "SELECT COUNT(*) as count FROM journal_entries WHERE status IN ('deleted', 'voided')";
        $countResult = $conn->query($countSql);
        $countRow = $countResult->fetch_assoc();
        $journalDeletedCount = $countRow['count'];
        
        if ($journalDeletedCount > 0) {
            // Delete journal lines first (foreign key constraint)
            $deleteLinesSql = "DELETE jl FROM journal_lines jl 
                               INNER JOIN journal_entries je ON jl.journal_entry_id = je.id 
                               WHERE je.status IN ('deleted', 'voided')";
            $conn->query($deleteLinesSql);
            
            // Delete the journal entries
            $deleteEntrySql = "DELETE FROM journal_entries WHERE status IN ('deleted', 'voided')";
            $conn->query($deleteEntrySql);
            
            logActivity('empty_bin', 'transaction_reading', "Permanently deleted $journalDeletedCount journal entries from bin", $conn);
        }
        
        // Also delete bank transactions in bin
        $bankDeletedCount = 0;
        $checkBankDeletedAt = $conn->query("SHOW COLUMNS FROM bank_transactions LIKE 'deleted_at'");
        if ($checkBankDeletedAt && $checkBankDeletedAt->num_rows > 0) {
            $bankCountSql = "SELECT COUNT(*) as count FROM bank_transactions WHERE deleted_at IS NOT NULL";
            $bankCountResult = $conn->query($bankCountSql);
            $bankCountRow = $bankCountResult->fetch_assoc();
            $bankDeletedCount = $bankCountRow['count'];
            
            if ($bankDeletedCount > 0) {
                $deleteBankSql = "DELETE FROM bank_transactions WHERE deleted_at IS NOT NULL";
                $conn->query($deleteBankSql);
                logActivity('empty_bin', 'transaction_reading', "Permanently deleted $bankDeletedCount bank transactions from bin", $conn);
            }
        }
        
        $totalDeleted = $journalDeletedCount + $bankDeletedCount;
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully permanently deleted $totalDeleted transactions ($journalDeletedCount journal entries, $bankDeletedCount bank transactions)",
            'deleted_count' => $totalDeleted
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

