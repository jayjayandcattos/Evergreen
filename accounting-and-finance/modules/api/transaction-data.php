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
        
        case 'restore_all_transactions':
            restoreAllTransactions();
            break;
        
        case 'empty_bin_transactions':
            emptyBinTransactions();
            break;
        
        case 'test_api':
            testAPI();
            break;
        
        case 'test_delete_simple':
            testDeleteSimple();
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
 */
function getTransactionDetails() {
    global $conn;
    
    $transactionId = $_GET['id'] ?? '';
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Get main transaction data
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
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
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
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lines = [];
    while ($row = $result->fetch_assoc()) {
        $lines[] = $row;
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
 */
function getAuditTrail() {
    global $conn;
    
    $transactionId = $_GET['id'] ?? '';
    
    $sql = "SELECT 
                al.*,
                u.username,
                u.full_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.object_type = 'journal_entry'";
    
    $params = [];
    $types = '';
    
    if (!empty($transactionId)) {
        $sql .= " AND al.object_id = ?";
        $params[] = $transactionId;
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
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Check if soft delete columns exist
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
            $stmt->bind_param('ii', $currentUser['id'], $transactionId);
            $stmt->execute();
        } else if ($columnsExist && !$deletedStatusExists) {
            // Has deleted_at column but status ENUM doesn't have 'deleted'
            // Use 'voided' status but still track deletion metadata
            $sql = "UPDATE journal_entries 
                    SET status = 'voided',
                        deleted_at = NOW(), 
                        deleted_by = ?
                    WHERE id = ? AND status != 'voided'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $currentUser['id'], $transactionId);
            $stmt->execute();
        } else {
            // Fallback: just update status to 'voided' (no soft delete metadata)
            $sql = "UPDATE journal_entries 
                    SET status = 'voided'
                    WHERE id = ? AND status != 'voided'";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $transactionId);
            $stmt->execute();
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
            $auditStmt->bind_param('iiss', $currentUser['id'], $transactionId, $action, $ipAddress);
            $auditStmt->execute();
        }
        
        // Log activity
        logActivity('delete', 'transaction_reading', "Deleted transaction #$transactionId", $conn);
        
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
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check what status we're dealing with
        $hasDeletedAt = checkSoftDeleteColumnsExist();
        $hasDeletedStatus = checkDeletedStatusExists();
        
        if ($hasDeletedAt && $hasDeletedStatus) {
            // Full soft delete: restore from 'deleted' status to 'posted'
            $sql = "UPDATE journal_entries 
                    SET status = 'posted', 
                        deleted_at = NULL, 
                        deleted_by = NULL,
                        restored_at = NOW(),
                        restored_by = ?
                    WHERE id = ? AND status = 'deleted'";
        } else if ($hasDeletedAt && !$hasDeletedStatus) {
            // Has deleted_at but no 'deleted' status: restore from 'voided' with deleted_at to 'posted'
            $sql = "UPDATE journal_entries 
                    SET status = 'posted',
                        deleted_at = NULL, 
                        deleted_by = NULL,
                        restored_at = NOW(),
                        restored_by = ?
                    WHERE id = ? AND status = 'voided' AND deleted_at IS NOT NULL";
        } else {
            // No soft delete columns: restore from 'voided' status to 'posted'
            $sql = "UPDATE journal_entries 
                    SET status = 'posted'
                    WHERE id = ? AND status = 'voided'";
        }
        
        if ($hasDeletedAt) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $currentUser['id'], $transactionId);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $transactionId);
        }
        
        $stmt->execute();
        
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
    
    // Check if we have deleted_at column for proper soft delete tracking
    $hasDeletedAt = checkSoftDeleteColumnsExist();
    $hasDeletedStatus = checkDeletedStatusExists();
    
    if ($hasDeletedAt) {
        // Use deleted_at column to find deleted items
        $whereClause = "je.deleted_at IS NOT NULL";
    } else if ($hasDeletedStatus) {
        // Use status = 'deleted'
        $whereClause = "je.status = 'deleted'";
    } else {
        // Fallback: look for voided status (our current implementation)
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
 * Test API endpoint to debug issues
 */
function testAPI() {
    global $conn;
    
    $currentUser = getCurrentUser();
    
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'user' => $currentUser,
        'database_connected' => $conn ? true : false,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION
    ]);
    
    ob_end_flush();
    exit();
}

/**
 * Simple delete test - minimal logic
 */
function testDeleteSimple() {
    global $conn;
    
    $transactionId = $_POST['transaction_id'] ?? '';
    $currentUser = getCurrentUser();
    
    echo json_encode([
        'success' => true,
        'message' => 'Simple delete test successful',
        'transaction_id' => $transactionId,
        'user_id' => $currentUser['id'] ?? 'no user',
        'database_connected' => $conn ? true : false,
    ]);
    
    ob_end_flush();
    exit();
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
        
        $restoredCount = 0;
        
        if ($hasDeletedAt && $hasDeletedStatus) {
            // Full soft delete: restore from 'deleted' status
            $sql = "UPDATE journal_entries 
                    SET status = 'posted', 
                        deleted_at = NULL, 
                        deleted_by = NULL,
                        restored_at = NOW(),
                        restored_by = ?
                    WHERE status = 'deleted'";
        } else if ($hasDeletedAt && !$hasDeletedStatus) {
            // Has deleted_at but no 'deleted' status: restore from 'voided' with deleted_at
            $sql = "UPDATE journal_entries 
                    SET status = 'posted',
                        deleted_at = NULL, 
                        deleted_by = NULL,
                        restored_at = NOW(),
                        restored_by = ?
                    WHERE status = 'voided' AND deleted_at IS NOT NULL";
        } else {
            // No soft delete columns: restore from 'voided' status
            $sql = "UPDATE journal_entries 
                    SET status = 'posted'
                    WHERE status = 'voided'";
        }
        
        if ($hasDeletedAt) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $currentUser['id']);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
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
            logActivity('restore_all', 'transaction_reading', "Restored $restoredCount transactions from bin", $conn);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully restored $restoredCount transactions",
            'restored_count' => $restoredCount
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
        
        // Get count of items to be deleted
        $countSql = "SELECT COUNT(*) as count FROM journal_entries WHERE status IN ('deleted', 'voided')";
        $countResult = $conn->query($countSql);
        $countRow = $countResult->fetch_assoc();
        $deletedCount = $countRow['count'];
        
        if ($deletedCount > 0) {
            // Delete journal lines first (foreign key constraint)
            $deleteLinesSql = "DELETE jl FROM journal_lines jl 
                               INNER JOIN journal_entries je ON jl.journal_entry_id = je.id 
                               WHERE je.status IN ('deleted', 'voided')";
            $conn->query($deleteLinesSql);
            
            // Delete the journal entries
            $deleteEntrySql = "DELETE FROM journal_entries WHERE status IN ('deleted', 'voided')";
            $conn->query($deleteEntrySql);
            
            // Log activity
            logActivity('empty_bin', 'transaction_reading', "Permanently deleted $deletedCount transactions from bin", $conn);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully permanently deleted $deletedCount transactions",
            'deleted_count' => $deletedCount
        ]);
        
        ob_end_flush();
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

