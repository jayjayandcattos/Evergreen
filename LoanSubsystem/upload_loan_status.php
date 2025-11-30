<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$loan_id = isset($input['loan_id']) ? (int)$input['loan_id'] : 0;
$status = isset($input['status']) ? trim($input['status']) : '';
$action = isset($input['action']) ? trim($input['action']) : '';
$custom_remarks = isset($input['remarks']) ? trim($input['remarks']) : '';

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid loan ID']);
    exit;
}

// Fetch loan details including email
$stmt = $conn->prepare("SELECT full_name, loan_amount, loan_terms, monthly_payment, email, user_email FROM loan_applications WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Loan not found']);
    exit;
}

$loan = $result->fetch_assoc();
$stmt->close();

// Get customer email (prefer email, fallback to user_email)
$customer_email = !empty($loan['email']) ? $loan['email'] : (!empty($loan['user_email']) ? $loan['user_email'] : null);

$admin_name = $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'Admin';
$timestamp = date('Y-m-d H:i:s');
$full_name = $loan['full_name'];
$loan_amount = number_format($loan['loan_amount'], 2);
$monthly_payment = number_format($loan['monthly_payment'], 2);
$term = $loan['loan_terms'];

// Process based on action
$remarks = '';
$alert_message = '';
$update_query = '';

switch ($action) {
    case 'first_approve':
        // Pending → Approved
        $remarks = "Dear $full_name,\n\nCongratulations! Your loan application for ₱$loan_amount has been APPROVED.\n\nPlease visit our bank within 30 days to claim your loan.\n\nLoan Details:\n- Amount: ₱$loan_amount\n- Term: $term\n- Monthly Payment: ₱$monthly_payment\n\nApproved by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Approved', remarks = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $remarks, $admin_name, $loan_id);
        $alert_message = "Loan approved! Client must claim within 30 days.";
        break;

    case 'second_approve':
        // Approved → Active
        $next_payment = date('Y-m-d', strtotime('+1 month'));
        $final_due = date('Y-m-d', strtotime("+{$term}"));
        
        $remarks = "Dear $full_name,\n\nYour loan is now ACTIVE!\n\nPayment Details:\n- Monthly Payment: ₱$monthly_payment\n- First Payment Due: " . date('F j, Y', strtotime($next_payment)) . "\n- Final Payment: " . date('F j, Y', strtotime($final_due)) . "\n\nActivated by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Active', remarks = ?, next_payment_due = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $next_payment, $final_due, $loan_id);
        $alert_message = "Loan activated successfully!";
        break;

    case 'first_reject':
        // Pending → Rejected
        $reason = $custom_remarks ?: 'Application does not meet requirements';
        $remarks = "Dear $full_name,\n\nYour loan application for ₱$loan_amount has been REJECTED.\n\nReason: $reason\n\nRejected by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Rejected', remarks = ?, rejection_remarks = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $reason, $admin_name, $loan_id);
        $alert_message = "Loan rejected successfully.";
        break;

    case 'second_reject':
        // Approved → Rejected
        $reason = $custom_remarks ?: 'Client did not claim within 30 days';
        $remarks = "Dear $full_name,\n\nYour approved loan for ₱$loan_amount has been CANCELLED.\n\nReason: $reason\n\nCancelled by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Rejected', remarks = ?, rejection_remarks = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $reason, $admin_name, $loan_id);
        $alert_message = "Approved loan cancelled.";
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed']);
    exit;
}

if ($stmt->execute()) {
    $credit_error = null;
    $credit_success = false;
    
    // If loan was activated (second_approve), credit the account
    if ($action === 'second_approve') {
        if (!$customer_email) {
            $credit_error = "No email found in loan application (loan_id: {$loan_id})";
            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
        } else {
            // Log email being used for lookup
            error_log("Loan Credit Debug [Loan {$loan_id}]: Looking up account for email: {$customer_email}");
            
            // Try to find account - first attempt: prefer Savings account, active status
            $account_stmt = $conn->prepare("
                SELECT ca.account_id, ca.account_number, ca.account_status, ca.is_locked
                FROM customer_accounts ca
                INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                WHERE bc.email = ?
                AND ca.is_locked = 0
                AND (ca.account_status = 'active' OR ca.account_status IS NULL OR ca.account_status != 'closed')
                ORDER BY 
                    CASE WHEN ca.account_type_id = 1 THEN 1 ELSE 2 END,
                    ca.created_at ASC
                LIMIT 1
            ");
            
            $account = null;
            $account_id = null;
            $account_number = null;
            
            if ($account_stmt) {
                $account_stmt->bind_param("s", $customer_email);
                $account_stmt->execute();
                $account_result = $account_stmt->get_result();
                
                if ($account_result->num_rows > 0) {
                    $account = $account_result->fetch_assoc();
                    $account_id = $account['account_id'];
                    $account_number = $account['account_number'];
                    error_log("Loan Credit Debug [Loan {$loan_id}]: Found account_id: {$account_id}, account_number: {$account_number}");
                } else {
                    // Fallback: try any unlocked account (even if status is not 'active')
                    error_log("Loan Credit Debug [Loan {$loan_id}]: No active account found, trying fallback query");
                    $account_stmt->close();
                    
                    $fallback_stmt = $conn->prepare("
                        SELECT ca.account_id, ca.account_number
                        FROM customer_accounts ca
                        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                        WHERE bc.email = ?
                        AND ca.is_locked = 0
                        AND ca.account_status != 'closed'
                        ORDER BY ca.created_at ASC
                        LIMIT 1
                    ");
                    
                    if ($fallback_stmt) {
                        $fallback_stmt->bind_param("s", $customer_email);
                        $fallback_stmt->execute();
                        $fallback_result = $fallback_stmt->get_result();
                        
                        if ($fallback_result->num_rows > 0) {
                            $account = $fallback_result->fetch_assoc();
                            $account_id = $account['account_id'];
                            $account_number = $account['account_number'];
                            error_log("Loan Credit Debug [Loan {$loan_id}]: Fallback found account_id: {$account_id}, account_number: {$account_number}");
                        } else {
                            // Last attempt: try with user_email field if different
                            $fallback_stmt->close();
                            if (!empty($loan['user_email']) && $loan['user_email'] !== $customer_email) {
                                error_log("Loan Credit Debug [Loan {$loan_id}]: Trying user_email field: {$loan['user_email']}");
                                $email_fallback_stmt = $conn->prepare("
                                    SELECT ca.account_id, ca.account_number
                                    FROM customer_accounts ca
                                    INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                                    WHERE bc.email = ?
                                    AND ca.is_locked = 0
                                    ORDER BY ca.created_at ASC
                                    LIMIT 1
                                ");
                                
                                if ($email_fallback_stmt) {
                                    $email_fallback_stmt->bind_param("s", $loan['user_email']);
                                    $email_fallback_stmt->execute();
                                    $email_fallback_result = $email_fallback_stmt->get_result();
                                    
                                    if ($email_fallback_result->num_rows > 0) {
                                        $account = $email_fallback_result->fetch_assoc();
                                        $account_id = $account['account_id'];
                                        $account_number = $account['account_number'];
                                        error_log("Loan Credit Debug [Loan {$loan_id}]: Found account using user_email: {$account_id}");
                                    }
                                    $email_fallback_stmt->close();
                                }
                            }
                        }
                        
                        if (!$account_id) {
                            $credit_error = "No unlocked account found for customer email: {$customer_email}";
                            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                        }
                    }
                }
                
                if ($account_stmt && !$account_stmt->closed) {
                    $account_stmt->close();
                }
            } else {
                $credit_error = "Failed to prepare account lookup query: " . $conn->error;
                error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
            }
            
            // If account found, insert transaction
            if ($account_id && $account_number) {
                // Transaction type ID 6 = Loan Disbursement
                $transaction_ref = 'LOAN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $loan_amount_raw = (float)$loan['loan_amount']; // Use raw amount, not formatted
                $description = "Loan Disbursement - Loan ID: {$loan_id}, Account: {$account_number}";
                
                error_log("Loan Credit Debug [Loan {$loan_id}]: Inserting transaction - ref: {$transaction_ref}, account_id: {$account_id}, amount: {$loan_amount_raw}");
                
                // Insert transaction into bank_transactions
                $transaction_stmt = $conn->prepare("
                    INSERT INTO bank_transactions (
                        transaction_ref,
                        account_id,
                        transaction_type_id,
                        amount,
                        description,
                        created_at
                    ) VALUES (?, ?, 6, ?, ?, NOW())
                ");
                
                if ($transaction_stmt) {
                    $transaction_stmt->bind_param("sids", $transaction_ref, $account_id, $loan_amount_raw, $description);
                    
                    if ($transaction_stmt->execute()) {
                        $transaction_id = $transaction_stmt->insert_id;
                        if ($transaction_id > 0) {
                            $credit_success = true;
                            $alert_message .= " Account credited with ₱" . number_format($loan_amount_raw, 2) . ".";
                            error_log("Loan Credit Success [Loan {$loan_id}]: Transaction inserted successfully - transaction_id: {$transaction_id}, ref: {$transaction_ref}");
                        } else {
                            $credit_error = "Transaction executed but no transaction_id returned";
                            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                        }
                    } else {
                        $credit_error = "Failed to execute transaction insert: " . $transaction_stmt->error;
                        error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                    }
                    
                    $transaction_stmt->close();
                } else {
                    $credit_error = "Failed to prepare transaction insert query: " . $conn->error;
                    error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                }
            }
        }
    }
    
    // Build response
    $response = [
        'success' => true,
        'message' => $alert_message,
        'new_status' => $status
    ];
    
    // Include credit status in response for debugging
    if ($action === 'second_approve') {
        if ($credit_success) {
            $response['credit_status'] = 'success';
        } else if ($credit_error) {
            $response['credit_status'] = 'error';
            $response['credit_error'] = $credit_error;
            // Append error to message so admin sees it
            $response['message'] .= " Warning: Account credit failed - " . $credit_error;
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>