<?php
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_expense_details':
            getExpenseDetails();
            break;
        case 'get_audit_trail':
            getAuditTrail();
            break;
        case 'export_expenses':
            exportExpenses();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Get expense details using REAL client data from operational subsystems
 */
function getExpenseDetails() {
    global $conn;
    
    $expenseId = $_GET['expense_id'] ?? '';
    if (empty($expenseId)) {
        throw new Exception('Expense ID is required');
    }
    
    // Try to determine expense type from ID format
    // Format: "EXP-123", "TXN-456", or just numeric ID
    $expenseType = 'expense_claim'; // default
    $numericId = $expenseId;
    
    if (strpos($expenseId, 'EXP-') === 0) {
        $numericId = str_replace('EXP-', '', $expenseId);
        $expenseType = 'expense_claim';
    } elseif (strpos($expenseId, 'TXN-') === 0 || strpos($expenseId, 'BT-') === 0) {
        $numericId = preg_replace('/[^0-9]/', '', $expenseId);
        $expenseType = 'bank_transaction';
    }
    
    $response = null;
    
    // Try expense_claim first (HRIS-SIA)
    if ($expenseType === 'expense_claim' && $conn->query("SHOW TABLES LIKE 'expense_claims'")->num_rows > 0) {
        $sql = "SELECT 
                    ec.id,
                    ec.claim_no,
                    COALESCE(CONCAT(e.first_name, ' ', IFNULL(e.middle_name, ''), ' ', e.last_name), ec.employee_external_no) as employee_name,
                    ec.employee_external_no,
                    ec.expense_date,
                    ec.amount,
                    ec.description,
                    ec.status,
                    COALESCE(ecat.name, 'Uncategorized') as category_name,
                    COALESCE(ecat.code, 'UNCAT') as category_code,
                    CONCAT('EXP-', ec.id) as account_code,
                    COALESCE(ecat.name, 'Expense Claim') as account_name,
                    ec.created_at,
                    'System' as created_by_name,
                    approver.full_name as approved_by_name,
                    ec.approved_at,
                    'expense_claim' as transaction_type
                FROM expense_claims ec
                LEFT JOIN employee e ON ec.employee_external_no = e.employee_id
                LEFT JOIN expense_categories ecat ON ec.category_id = ecat.id
                LEFT JOIN users approver ON ec.approved_by = approver.id
                WHERE ec.id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $numericId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $expense = $result->fetch_assoc();
                $response = [
                    'id' => $expense['id'],
                    'claim_no' => $expense['claim_no'],
                    'employee_name' => trim($expense['employee_name']),
                    'expense_date' => $expense['expense_date'],
                    'amount' => floatval($expense['amount']),
                    'description' => $expense['description'],
                    'status' => $expense['status'],
                    'category' => $expense['category_name'],
                    'category_code' => $expense['category_code'],
                    'account_code' => $expense['account_code'],
                    'account_name' => $expense['account_name'],
                    'created_by' => $expense['created_by_name'],
                    'created_at' => $expense['created_at'],
                    'approved_by' => $expense['approved_by_name'],
                    'approved_at' => $expense['approved_at'],
                    'transaction_type' => $expense['transaction_type']
                ];
            }
            $stmt->close();
        }
    }
    
    // Try bank_transaction if expense_claim not found
    if (!$response && ($expenseType === 'bank_transaction' || $conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0)) {
        $sql = "SELECT 
                    bt.transaction_id as id,
                    COALESCE(bt.transaction_ref, CONCAT('TXN-', bt.transaction_id)) as claim_no,
                    CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name) as employee_name,
                    ca.account_number as employee_external_no,
                    DATE(bt.created_at) as expense_date,
                    bt.amount,
                    bt.description,
                    'approved' as status,
                    tt.type_name as category_name,
                    tt.type_name as category_code,
                    ca.account_number as account_code,
                    CONCAT('Bank Fee - ', tt.type_name) as account_name,
                    bt.created_at,
                    'System' as created_by_name,
                    NULL as approved_by_name,
                    NULL as approved_at,
                    'bank_fee' as transaction_type
                FROM bank_transactions bt
                INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
                INNER JOIN customer_accounts ca ON bt.account_id = ca.account_id
                INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                WHERE bt.transaction_id = ?
                    AND (tt.type_name LIKE '%fee%' OR tt.type_name LIKE '%charge%' OR tt.type_name LIKE '%withdrawal%')
                    AND ca.is_locked = 0";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $numericId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $expense = $result->fetch_assoc();
                $response = [
                    'id' => $expense['id'],
                    'claim_no' => $expense['claim_no'],
                    'employee_name' => trim($expense['employee_name']),
                    'expense_date' => $expense['expense_date'],
                    'amount' => floatval($expense['amount']),
                    'description' => $expense['description'],
                    'status' => $expense['status'],
                    'category' => $expense['category_name'],
                    'category_code' => $expense['category_code'],
                    'account_code' => $expense['account_code'],
                    'account_name' => $expense['account_name'],
                    'created_by' => $expense['created_by_name'],
                    'created_at' => $expense['created_at'],
                    'approved_by' => $expense['approved_by_name'],
                    'approved_at' => $expense['approved_at'],
                    'transaction_type' => $expense['transaction_type']
                ];
            }
            $stmt->close();
        }
    }
    
    if (!$response) {
        throw new Exception('Expense not found');
    }
    
    echo json_encode(['success' => true, 'data' => $response]);
}

/**
 * Get audit trail using REAL data from operational subsystems
 * Supports expense_claims (HRIS), bank_transactions (Bank System), and other expense types
 */
function getAuditTrail() {
    global $conn;
    
    $expenseId = $_GET['expense_id'] ?? '';
    $general = $_GET['general'] ?? false;
    
    // Check if audit_logs table exists
    if ($conn->query("SHOW TABLES LIKE 'audit_logs'")->num_rows === 0) {
        // If no audit_logs table, return empty audit trail with a helpful message
        echo json_encode([
            'success' => true, 
            'data' => [],
            'message' => 'Audit logs table not found. Audit trail will be available once the table is created.'
        ]);
        return;
    }
    
    $auditTrail = [];
    
    if ($general) {
        // Get general audit trail for expense tracking module - all expense-related activities
        $sql = "SELECT 
                    al.id,
                    al.action,
                    al.object_type,
                    al.object_id,
                    al.old_values,
                    al.new_values,
                    al.additional_info,
                    al.created_at,
                    COALESCE(u.full_name, u.username, 'System') as user_name,
                    al.ip_address
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.object_type IN ('expense_claim', 'bank_transaction', 'expense')
                ORDER BY al.created_at DESC
                LIMIT 100";
        
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $auditTrail[] = [
                    'id' => $row['id'],
                    'action' => $row['action'],
                    'user' => $row['user_name'] ?? 'System',
                    'timestamp' => $row['created_at'],
                    'changes' => formatAuditChanges($row),
                    'ip_address' => $row['ip_address'] ?? 'N/A',
                    'object_type' => $row['object_type'],
                    'object_id' => $row['object_id']
                ];
            }
        }
    } else {
        // Get specific expense audit trail
        if (empty($expenseId)) {
            throw new Exception('Expense ID is required');
        }
        
        // Determine object type from expense ID
        $objectType = 'expense_claim';
        $numericId = $expenseId;
        
        if (strpos($expenseId, 'EXP-') === 0) {
            $numericId = str_replace('EXP-', '', $expenseId);
            $objectType = 'expense_claim';
        } elseif (strpos($expenseId, 'TXN-') === 0 || strpos($expenseId, 'BT-') === 0) {
            $numericId = preg_replace('/[^0-9]/', '', $expenseId);
            $objectType = 'bank_transaction';
        }
        
        // Try to find audit logs for this expense
        $sql = "SELECT 
                    al.id,
                    al.action,
                    al.object_type,
                    al.object_id,
                    al.old_values,
                    al.new_values,
                    al.additional_info,
                    al.created_at,
                    COALESCE(u.full_name, u.username, 'System') as user_name,
                    al.ip_address
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE (al.object_type = ? AND al.object_id = ?)
                ORDER BY al.created_at DESC
                LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $objectType, $numericId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $auditTrail[] = [
                    'id' => $row['id'],
                    'action' => $row['action'],
                    'user' => $row['user_name'] ?? 'System',
                    'timestamp' => $row['created_at'],
                    'changes' => formatAuditChanges($row),
                    'ip_address' => $row['ip_address'] ?? 'N/A',
                    'object_type' => $row['object_type'],
                    'object_id' => $row['object_id']
                ];
            }
            $stmt->close();
        }
        
        // If no audit logs found, create a default entry from expense record itself
        if (empty($auditTrail)) {
            // Try to get expense info to create a synthetic audit entry
            if ($objectType === 'expense_claim' && $conn->query("SHOW TABLES LIKE 'expense_claims'")->num_rows > 0) {
                $expenseSql = "SELECT ec.*, u.full_name as approved_by_name 
                              FROM expense_claims ec 
                              LEFT JOIN users u ON ec.approved_by = u.id 
                              WHERE ec.id = ?";
                $expenseStmt = $conn->prepare($expenseSql);
                if ($expenseStmt) {
                    $expenseStmt->bind_param('i', $numericId);
                    $expenseStmt->execute();
                    $expenseResult = $expenseStmt->get_result();
                    if ($expenseRow = $expenseResult->fetch_assoc()) {
                        $auditTrail[] = [
                            'id' => 0,
                            'action' => 'Created',
                            'user' => 'System',
                            'timestamp' => $expenseRow['created_at'],
                            'changes' => 'Expense claim created: ' . $expenseRow['claim_no'] . ' - Amount: ₱' . number_format($expenseRow['amount'], 2),
                            'ip_address' => 'N/A',
                            'object_type' => 'expense_claim',
                            'object_id' => $numericId
                        ];
                        
                        if ($expenseRow['approved_at']) {
                            $auditTrail[] = [
                                'id' => 0,
                                'action' => 'Approved',
                                'user' => $expenseRow['approved_by_name'] ?? 'System',
                                'timestamp' => $expenseRow['approved_at'],
                                'changes' => 'Expense claim approved',
                                'ip_address' => 'N/A',
                                'object_type' => 'expense_claim',
                                'object_id' => $numericId
                            ];
                        }
                    }
                    $expenseStmt->close();
                }
            } elseif ($objectType === 'bank_transaction' && $conn->query("SHOW TABLES LIKE 'bank_transactions'")->num_rows > 0) {
                $txnSql = "SELECT bt.*, tt.type_name 
                          FROM bank_transactions bt 
                          INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id 
                          WHERE bt.transaction_id = ?";
                $txnStmt = $conn->prepare($txnSql);
                if ($txnStmt) {
                    $txnStmt->bind_param('i', $numericId);
                    $txnStmt->execute();
                    $txnResult = $txnStmt->get_result();
                    if ($txnRow = $txnResult->fetch_assoc()) {
                        $auditTrail[] = [
                            'id' => 0,
                            'action' => 'Transaction Created',
                            'user' => 'System',
                            'timestamp' => $txnRow['created_at'],
                            'changes' => 'Bank transaction created: ' . ($txnRow['transaction_ref'] ?? 'TXN-' . $txnRow['transaction_id']) . ' - Type: ' . $txnRow['type_name'] . ' - Amount: ₱' . number_format($txnRow['amount'], 2),
                            'ip_address' => 'N/A',
                            'object_type' => 'bank_transaction',
                            'object_id' => $numericId
                        ];
                    }
                    $txnStmt->close();
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'data' => $auditTrail]);
}

function formatAuditChanges($row) {
    $changes = [];
    
    if ($row['old_values']) {
        $oldValues = json_decode($row['old_values'], true);
        $newValues = json_decode($row['new_values'], true);
        
        if ($oldValues && $newValues) {
            foreach ($newValues as $key => $newValue) {
                $oldValue = $oldValues[$key] ?? '';
                if ($oldValue !== $newValue) {
                    $changes[] = ucfirst(str_replace('_', ' ', $key)) . ': "' . $oldValue . '" → "' . $newValue . '"';
                }
            }
        }
    }
    
    if ($row['additional_info']) {
        $additionalInfo = json_decode($row['additional_info'], true);
        if ($additionalInfo && isset($additionalInfo['description'])) {
            $changes[] = $additionalInfo['description'];
        }
    }
    
    return empty($changes) ? $row['action'] : implode(', ', $changes);
}

function exportExpenses() {
    global $conn;
    
    // Log export activity
    logActivity('export', 'expense_tracking', 'Exported expenses to CSV/Excel', $conn);
    
    // Get filter parameters
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $status = $_GET['status'] ?? '';
    $accountNumber = $_GET['account_number'] ?? '';
    
    // Build query (same as main page)
    $sql = "SELECT 
                ec.id,
                ec.claim_no as transaction_number,
                ec.employee_external_no as employee_name,
                ec.expense_date as transaction_date,
                ec.amount,
                ec.description,
                ec.status,
                ecat.name as category_name,
                ecat.code as category_code,
                a.code as account_code,
                a.name as account_name,
                ec.created_at,
                'System' as created_by_name,
                approver.full_name as approved_by_name,
                ec.approved_at
            FROM expense_claims ec
            LEFT JOIN expense_categories ecat ON ec.category_id = ecat.id
            LEFT JOIN accounts a ON ecat.account_id = a.id
            LEFT JOIN users approver ON ec.approved_by = approver.id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($dateFrom)) {
        $sql .= " AND ec.expense_date >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND ec.expense_date <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $sql .= " AND ec.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($accountNumber)) {
        $sql .= " AND a.code LIKE ?";
        $params[] = '%' . $accountNumber . '%';
        $types .= 's';
    }
    
    $sql .= " ORDER BY ec.expense_date DESC, ec.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Generate CSV
    $filename = 'expense_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers (simplified, matching print layout)
    fputcsv($output, [
        'Transaction #',
        'Date',
        'Employee',
        'Category',
        'Account',
        'Amount',
        'Status',
        'Description'
    ]);
    
    // CSV data
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['transaction_number'],
            date('M d, Y', strtotime($row['transaction_date'])),
            $row['employee_name'],
            $row['category_name'],
            $row['account_code'] . ' - ' . $row['account_name'],
            number_format($row['amount'], 2),
            ucfirst($row['status']),
            $row['description'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}
?>
