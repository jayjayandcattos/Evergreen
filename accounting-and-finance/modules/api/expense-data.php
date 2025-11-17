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

function getExpenseDetails() {
    global $conn;
    
    $expenseId = $_GET['expense_id'] ?? '';
    if (empty($expenseId)) {
        throw new Exception('Expense ID is required');
    }
    
    $sql = "SELECT 
                ec.id,
                ec.claim_no,
                ec.employee_external_no,
                ec.expense_date,
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
                ec.approved_at,
                payment.payment_no,
                payment.payment_date,
                payment.payment_type
            FROM expense_claims ec
            LEFT JOIN expense_categories ecat ON ec.category_id = ecat.id
            LEFT JOIN accounts a ON ecat.account_id = a.id
            LEFT JOIN users approver ON ec.approved_by = approver.id
            LEFT JOIN payments payment ON ec.payment_id = payment.id
            WHERE ec.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $expenseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Expense not found');
    }
    
    $expense = $result->fetch_assoc();
    
    // Format the response
    $response = [
        'id' => $expense['id'],
        'claim_no' => $expense['claim_no'],
        'employee_name' => $expense['employee_external_no'],
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
        'payment_no' => $expense['payment_no'],
        'payment_date' => $expense['payment_date'],
        'payment_type' => $expense['payment_type']
    ];
    
    echo json_encode(['success' => true, 'data' => $response]);
}

function getAuditTrail() {
    global $conn;
    
    $expenseId = $_GET['expense_id'] ?? '';
    $general = $_GET['general'] ?? false;
    
    if ($general) {
        // Get general audit trail for expense tracking module
        $sql = "SELECT 
                    al.id,
                    al.action,
                    al.object_type,
                    al.object_id,
                    al.old_values,
                    al.new_values,
                    al.additional_info,
                    al.created_at,
                    u.full_name as user_name,
                    al.ip_address
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.object_type = 'expense_claim'
                ORDER BY al.created_at DESC
                LIMIT 50";
        
        $stmt = $conn->prepare($sql);
    } else {
        // Get specific expense audit trail
        if (empty($expenseId)) {
            throw new Exception('Expense ID is required');
        }
        
        $sql = "SELECT 
                    al.id,
                    al.action,
                    al.object_type,
                    al.object_id,
                    al.old_values,
                    al.new_values,
                    al.additional_info,
                    al.created_at,
                    u.full_name as user_name,
                    al.ip_address
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.object_type = 'expense_claim' 
                AND al.object_id = ?
                ORDER BY al.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $expenseId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $auditTrail = [];
    while ($row = $result->fetch_assoc()) {
        $auditTrail[] = [
            'id' => $row['id'],
            'action' => $row['action'],
            'user' => $row['user_name'] ?? 'System',
            'timestamp' => $row['created_at'],
            'changes' => formatAuditChanges($row),
            'ip_address' => $row['ip_address']
        ];
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
