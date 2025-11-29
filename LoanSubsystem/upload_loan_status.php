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
require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

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

// Fetch loan details
$stmt = $conn->prepare("SELECT id, loan_type_id, full_name, account_number, loan_amount, loan_terms, monthly_payment, user_email FROM loan_applications WHERE id = ?");
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
        
        // Create loan record in `loans` table
        $loan_no = 'LN-' . date('Ymd') . '-' . str_pad($loan_id, 4, '0', STR_PAD_LEFT);
        $interest_rate = 0.20;
        $term_months = (int)filter_var($term, FILTER_SANITIZE_NUMBER_INT);
        $admin_email = $_SESSION['user_email'];
        
        // Get admin user ID
        $admin_id = 1; // Default fallback
        $u_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($u_stmt) {
            $u_stmt->bind_param("s", $admin_email);
            $u_stmt->execute();
            $u_res = $u_stmt->get_result();
            if ($u_row = $u_res->fetch_assoc()) {
                $admin_id = $u_row['id'];
            }
            $u_stmt->close();
        }

        // Insert into loans
        $ins_stmt = $conn->prepare("
            INSERT INTO loans (
                loan_no, loan_type_id, borrower_external_no, principal_amount, 
                interest_rate, start_date, term_months, monthly_payment, 
                current_balance, next_payment_due, status, application_id, created_by
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'active', ?, ?)
        ");
        
        if ($ins_stmt) {
            $current_balance = $loan['loan_amount'] * (1 + $interest_rate); // Principal + Interest
            $ins_stmt->bind_param(
                "sisddiddsii", 
                $loan_no, 
                $loan['loan_type_id'], 
                $loan['account_number'], 
                $loan['loan_amount'], 
                $interest_rate, 
                $term_months, 
                $loan['monthly_payment'], 
                $current_balance, 
                $next_payment, 
                $loan_id, 
                $admin_id
            );
            $ins_stmt->execute();
            $new_loan_id = $ins_stmt->insert_id;
            $ins_stmt->close();
            
            // Update loan_applications with loan_id
            $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Active', remarks = ?, next_payment_due = ?, due_date = ?, loan_id = ? WHERE id = ?");
            $stmt->bind_param("sssii", $remarks, $next_payment, $final_due, $new_loan_id, $loan_id);
        } else {
             // Fallback if insert fails (should not happen)
             $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Active', remarks = ?, next_payment_due = ?, due_date = ? WHERE id = ?");
             $stmt->bind_param("sssi", $remarks, $next_payment, $final_due, $loan_id);
        }
        
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
    echo json_encode([
        'success' => true,
        'message' => $alert_message,
        'new_status' => $status
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>