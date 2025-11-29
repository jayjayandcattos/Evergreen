<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$loan_id = isset($input['loan_id']) ? (int)$input['loan_id'] : 0;
$status = trim($input['status'] ?? '');
$remarks = trim($input['remarks'] ?? '');

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid loan ID']);
    exit;
}

if (!in_array($status, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$timestamp = date('Y-m-d H:i:s');

if ($status === 'Approved') {
    // First, get loan application details
    $loan_stmt = $conn->prepare("
        SELECT loan_amount, account_number, email, loan_type, full_name 
        FROM loan_applications 
        WHERE id = ?
    ");
    $loan_stmt->bind_param("i", $loan_id);
    $loan_stmt->execute();
    $loan_result = $loan_stmt->get_result();
    $loan_data = $loan_result->fetch_assoc();
    $loan_stmt->close();
    
    if (!$loan_data) {
        echo json_encode(['success' => false, 'error' => 'Loan application not found']);
        exit;
    }
    
    // Calculate next payment due date (1 month from now)
    $next_payment_due = date('Y-m-d', strtotime('+1 month'));
    
    // Start transaction for atomicity
    $conn->begin_transaction();
    
    try {
        // Approval: set approved fields, clear rejection fields
        $stmt = $conn->prepare("
            UPDATE loan_applications 
            SET status = ?, remarks = ?, 
                approved_by = ?, approved_at = ?,
                next_payment_due = ?,
                rejected_by = NULL, rejected_at = NULL, rejection_remarks = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("sssssi", $status, $remarks, $admin_name, $timestamp, $next_payment_due, $loan_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update loan application: " . $stmt->error);
        }
        $stmt->close();
        
        // Get customer account_id from account_number
        $account_stmt = $conn->prepare("
            SELECT account_id 
            FROM customer_accounts 
            WHERE account_number = ?
            LIMIT 1
        ");
        $account_stmt->bind_param("s", $loan_data['account_number']);
        $account_stmt->execute();
        $account_result = $account_stmt->get_result();
        $account_data = $account_result->fetch_assoc();
        $account_stmt->close();
        
        if ($account_data && isset($account_data['account_id'])) {
            $account_id = $account_data['account_id'];
            
            // Generate transaction reference (format: LD + YYYYMMDD + loan_id + random)
            $transaction_ref = 'LD' . date('Ymd') . str_pad($loan_id, 6, '0', STR_PAD_LEFT) . rand(100, 999);
            
            // Get employee_id if available (from session or default to NULL)
            $employee_id = null;
            if (isset($_SESSION['user_id'])) {
                // Try to get employee_id from user_account or bank_employees
                $emp_stmt = $conn->prepare("
                    SELECT be.employee_id 
                    FROM bank_employees be
                    WHERE be.employee_name LIKE ?
                    LIMIT 1
                ");
                $admin_search = '%' . $admin_name . '%';
                $emp_stmt->bind_param("s", $admin_search);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();
                if ($emp_row = $emp_result->fetch_assoc()) {
                    $employee_id = $emp_row['employee_id'];
                }
                $emp_stmt->close();
            }
            
            // Create bank transaction for loan disbursement
            $transaction_type_id = 6; // Loan disbursement
            $description = "Loan Disbursement - {$loan_data['loan_type']} Loan (ID: {$loan_id})";
            
            // Build SQL with optional employee_id
            if ($employee_id !== null) {
                $trans_stmt = $conn->prepare("
                    INSERT INTO bank_transactions (
                        transaction_ref,
                        account_id,
                        transaction_type_id,
                        amount,
                        description,
                        employee_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $trans_stmt->bind_param(
                    "siidsi",
                    $transaction_ref,
                    $account_id,
                    $transaction_type_id,
                    $loan_data['loan_amount'],
                    $description,
                    $employee_id
                );
            } else {
                $trans_stmt = $conn->prepare("
                    INSERT INTO bank_transactions (
                        transaction_ref,
                        account_id,
                        transaction_type_id,
                        amount,
                        description,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $trans_stmt->bind_param(
                    "siids",
                    $transaction_ref,
                    $account_id,
                    $transaction_type_id,
                    $loan_data['loan_amount'],
                    $description
                );
            }
            
            if (!$trans_stmt->execute()) {
                throw new Exception("Failed to create bank transaction: " . $trans_stmt->error);
            }
            $trans_stmt->close();
        } else {
            // Log warning but don't fail the approval if account not found
            error_log("Warning: Account not found for loan approval. Account number: " . $loan_data['account_number']);
        }
        
        // Commit transaction
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
} else {
    // Rejection: set rejected fields, clear approval fields
    $stmt = $conn->prepare("
        UPDATE loan_applications 
        SET status = ?, remarks = ?, 
            rejected_by = ?, rejected_at = ?, rejection_remarks = ?,
            approved_by = NULL, approved_at = NULL, next_payment_due = NULL
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $status, $remarks, $admin_name, $timestamp, $remarks, $loan_id);
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed']);
    exit;
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Status updated',
        'new_status' => $status,
        'new_remarks' => $remarks
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>