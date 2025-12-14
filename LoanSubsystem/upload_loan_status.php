<?php
session_start();
date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// ✅ FIX: Use correct path for PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Check if files exist before requiring
$phpmailer_path = __DIR__ . '/PHPMailer-7.0.0/src/';

if (!file_exists($phpmailer_path . 'Exception.php')) {
    error_log("PHPMailer not found at: " . $phpmailer_path);
    echo json_encode(['success' => false, 'error' => 'PHPMailer library not found']);
    exit;
}

require $phpmailer_path . 'Exception.php';
require $phpmailer_path . 'PHPMailer.php';
require $phpmailer_path . 'SMTP.php';

// Rest of your code...

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

// Fetch loan details including email and loan type
$stmt = $conn->prepare("
    SELECT 
        la.full_name, 
        la.loan_amount, 
        la.loan_terms, 
        la.monthly_payment, 
        la.email, 
        la.user_email,
        la.next_payment_due,
        la.due_date,
        la.account_number,
        lt.name AS loan_type
    FROM loan_applications la
    LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
    WHERE la.id = ?
");

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
$loan_type = $loan['loan_type'] ?? 'Personal Loan';

// Process based on action
$remarks = '';
$alert_message = '';
$send_email = false;
$email_type = '';

switch ($action) {
    case 'first_approve':
        // Pending → Approved
        $remarks = "Dear $full_name,\n\nCongratulations! Your loan application for ₱$loan_amount has been APPROVED.\n\nPlease visit our bank within 30 days to claim your loan.\n\nLoan Details:\n- Amount: ₱$loan_amount\n- Term: $term\n- Monthly Payment: ₱$monthly_payment\n\nApproved by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Approved', remarks = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $remarks, $admin_name, $loan_id);
        $alert_message = "Loan approved! Client must claim within 30 days.";
        $send_email = true;
        $email_type = 'approved';
        break;

    case 'second_approve':
        // Approved → Active
        $next_payment = date('Y-m-d', strtotime('+1 month'));
        $final_due = date('Y-m-d', strtotime("+{$term}"));
        
        $remarks = "Dear $full_name,\n\nYour loan is now ACTIVE!\n\nPayment Details:\n- Monthly Payment: ₱$monthly_payment\n- First Payment Due: " . date('F j, Y', strtotime($next_payment)) . "\n- Final Payment: " . date('F j, Y', strtotime($final_due)) . "\n\nActivated by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Active', remarks = ?, next_payment_due = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $next_payment, $final_due, $loan_id);
        $alert_message = "Loan activated successfully!";
        $send_email = true;
        $email_type = 'active';
        break;

    case 'first_reject':
        // Pending → Rejected
        $reason = $custom_remarks ?: 'Application does not meet requirements';
        $remarks = "Dear $full_name,\n\nYour loan application for ₱$loan_amount has been REJECTED.\n\nReason: $reason\n\nRejected by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Rejected', remarks = ?, rejection_remarks = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $reason, $admin_name, $loan_id);
        $alert_message = "Loan rejected successfully.";
        $send_email = true;
        $email_type = 'rejected';
        break;

    case 'second_reject':
        // Approved → Rejected
        $reason = $custom_remarks ?: 'Client did not claim within 30 days';
        $remarks = "Dear $full_name,\n\nYour approved loan for ₱$loan_amount has been CANCELLED.\n\nReason: $reason\n\nCancelled by: $admin_name\nDate: $timestamp";
        
        $stmt = $conn->prepare("UPDATE loan_applications SET status = 'Rejected', remarks = ?, rejection_remarks = ?, rejected_by = ?, rejected_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $remarks, $reason, $admin_name, $loan_id);
        $alert_message = "Approved loan cancelled.";
        $send_email = true;
        $email_type = 'cancelled';
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
    
    // If loan was activated (second_approve), credit the selected account
    if ($action === 'second_approve') {
        if (!$customer_email) {
            $credit_error = "No email found in loan application (loan_id: {$loan_id})";
            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
        } else {
            error_log("Loan Credit Debug [Loan {$loan_id}]: Crediting account for email: {$customer_email}");
            
            // Get the account_number from loan application
            $loan_account_number = $loan['account_number'] ?? '';
            
            if (empty($loan_account_number)) {
                $credit_error = "No account number specified in loan application (loan_id: {$loan_id})";
                error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
            } else {
                error_log("Loan Credit Debug [Loan {$loan_id}]: Looking up account: {$loan_account_number}");
                
                // Find the account by account_number and verify it belongs to customer
                $account_stmt = $conn->prepare("
                    SELECT ca.account_id, ca.account_number, ca.account_status, ca.is_locked, bat.type_name
                    FROM customer_accounts ca
                    INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
                    INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
                    WHERE ca.account_number = ?
                    AND bc.email = ?
                    AND bat.type_name IN ('Savings Account', 'Checking Account')
                    LIMIT 1
                ");
                
                $account = null;
                $account_id = null;
                $account_number = null;
                
                if ($account_stmt) {
                    $account_stmt->bind_param("ss", $loan_account_number, $customer_email);
                    $account_stmt->execute();
                    $account_result = $account_stmt->get_result();
                    
                    if ($account_result->num_rows > 0) {
                        $account = $account_result->fetch_assoc();
                        $account_id = $account['account_id'];
                        $account_number = $account['account_number'];
                        
                        // Check if account is locked or closed
                        if ($account['is_locked'] == 1) {
                            $credit_error = "Account {$account_number} is locked. Please unlock the account or select a different account.";
                            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                            $account_id = null;
                        } elseif ($account['account_status'] === 'closed') {
                            $credit_error = "Account {$account_number} is closed. Please select a different active account.";
                            error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                            $account_id = null;
                        } else {
                            error_log("Loan Credit Debug [Loan {$loan_id}]: Found account_id: {$account_id}, account_number: {$account_number}, type: {$account['type_name']}");
                        }
                    } else {
                        $credit_error = "Account {$loan_account_number} not found or is not a Savings/Checking account for customer email: {$customer_email}";
                        error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                    }
                    
                    $account_stmt->close();
                } else {
                    $credit_error = "Failed to prepare account lookup query: " . $conn->error;
                    error_log("Loan Credit Error [Loan {$loan_id}]: {$credit_error}");
                }
                
                if ($account_id && $account_number) {
                    $transaction_ref = 'LOAN-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
                    $loan_amount_raw = (float)$loan['loan_amount'];
                    $description = "Loan Disbursement - Loan ID: {$loan_id}, Account: {$account_number}";
                    
                    error_log("Loan Credit Debug [Loan {$loan_id}]: Inserting transaction - ref: {$transaction_ref}, account_id: {$account_id}, amount: {$loan_amount_raw}");
                    
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
    }
    
    // Send email notification
    if ($send_email && $customer_email) {
        $email_sent = sendLoanEmail($customer_email, $full_name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp, $email_type, $loan, $custom_remarks);
        
        if ($email_sent) {
            error_log("Email notification sent successfully to {$customer_email} for loan {$loan_id} ({$email_type})");
        } else {
            error_log("Failed to send email notification to {$customer_email} for loan {$loan_id} ({$email_type})");
        }
    }
    
    // Build response
    $response = [
        'success' => true,
        'message' => $alert_message,
        'new_status' => $status
    ];
    
    if ($action === 'second_approve') {
        if ($credit_success) {
            $response['credit_status'] = 'success';
        } else if ($credit_error) {
            $response['credit_status'] = 'error';
            $response['credit_error'] = $credit_error;
            $response['message'] .= " Warning: Account credit failed - " . $credit_error;
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;

// ==================== EMAIL FUNCTIONS ====================

function sendLoanEmail($email, $name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp, $email_type, $loan_data, $rejection_reason = '') {
    $mail = new PHPMailer(true);
    
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'evrgrn.64@gmail.com';
        $mail->Password = 'dourhhbymvjejuct';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Timeout = 30;
        
        $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
        $mail->addAddress($email, $name);
        
        switch ($email_type) {
            case 'approved':
                $mail->Subject = 'Evergreen Banking - Loan Application APPROVED';
                $mail->Body = getApprovedEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp);
                break;
                
            case 'active':
                $mail->Subject = 'Evergreen Banking - Your Loan is Now ACTIVE';
                $mail->Body = getActiveEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp, $loan_data);
                break;
                
            case 'rejected':
                $mail->Subject = 'Evergreen Banking - Loan Application Status Update';
                $mail->Body = getRejectedEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $rejection_reason, $admin_name, $timestamp);
                break;
                
            case 'cancelled':
                $mail->Subject = 'Evergreen Banking - Approved Loan Cancelled';
                $mail->Body = getCancelledEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $rejection_reason, $admin_name, $timestamp);
                break;
                
            default:
                return false;
        }
        
        $mail->isHTML(true);
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function getApprovedEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp) {
    $total_payable = number_format((float)str_replace(',', '', $loan_amount) * 1.20, 2);
    
    return "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600;'>✅ Loan Approved!</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Dear <strong>{$name}</strong>,</p>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Congratulations! Your loan application has been <strong style='color: #28a745;'>APPROVED</strong>.</p>
                    <div style='background: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #155724; margin-top: 0; font-size: 18px;'>⚠️ Important: Claim Your Loan</h3>
                        <p style='color: #155724; margin: 10px 0; font-size: 15px; line-height: 1.6;'>Please visit our bank <strong>within 30 days</strong> to claim your approved loan.</p>
                    </div>
                    <div style='background: #f8f9fa; border-left: 4px solid #0d3d38; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #0d3d38; margin-top: 0; font-size: 18px;'>📋 Loan Details</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Loan ID:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>{$loan_id}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Loan Type:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>{$loan_type}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Approved Amount:</td><td style='padding: 8px 0; color: #0d3d38; font-weight: 700; font-size: 16px; text-align: right;'>₱{$loan_amount}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Loan Term:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>{$term}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Monthly Payment:</td><td style='padding: 8px 0; color: #0d3d38; font-weight: 700; font-size: 16px; text-align: right;'>₱{$monthly_payment}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Total Payable:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>₱{$total_payable}</td></tr>
                        </table>
                    </div>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>© 2025 Evergreen Banking. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}

function getActiveEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $monthly_payment, $term, $admin_name, $timestamp, $loan_data) {
    $total_payable = number_format((float)str_replace(',', '', $loan_amount) * 1.20, 2);
    $next_payment = $loan_data['next_payment_due'] ?? date('Y-m-d', strtotime('+1 month'));
    $final_payment = $loan_data['due_date'] ?? date('Y-m-d', strtotime("+{$term}"));
    $first_payment_due = date('F j, Y', strtotime($next_payment));
    $final_payment_date = date('F j, Y', strtotime($final_payment));
    
    return "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600;'>🎉 Loan Activated!</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Dear <strong>{$name}</strong>,</p>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Your loan has been <strong style='color: #28a745;'>ACTIVATED</strong>. The amount has been credited to your account.</p>
                    <div style='background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #0c5460; margin-top: 0; font-size: 18px;'>💡 Payment Information</h3>
                        <p style='color: #0c5460; margin: 10px 0; font-size: 15px;'><strong>First Payment Due:</strong> {$first_payment_due}<br><strong>Final Payment:</strong> {$final_payment_date}</p>
                    </div>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>© 2025 Evergreen Banking. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}

function getRejectedEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $reason, $admin_name, $timestamp) {
    return "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600;'>❌ Loan Application Update</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Dear <strong>{$name}</strong>,</p>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Your loan application has been <strong style='color: #dc3545;'>REJECTED</strong>.</p>
                    <div style='background: #f8d7da; border-left: 4px solid #dc3545; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #721c24; margin-top: 0; font-size: 18px;'>ℹ️ Reason</h3>
                        <p style='color: #721c24; margin: 10px 0; font-size: 15px;'>{$reason}</p>
                    </div>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>© 2025 Evergreen Banking. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}
function getCancelledEmailTemplate($name, $loan_id, $loan_type, $loan_amount, $reason, $admin_name, $timestamp) {
    return "
        <html>
        <body style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px; font-weight: 600;'>❌ Approved Loan Cancelled</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Dear <strong>{$name}</strong>,</p>
                    <p style='font-size: 16px; color: #333; line-height: 1.6;'>Your approved loan has been <strong style='color: #dc3545;'>CANCELLED</strong>.</p>
                    <div style='background: #f8d7da; border-left: 4px solid #dc3545; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #721c24; margin-top: 0; font-size: 18px;'>ℹ️ Reason for Cancellation</h3>
                        <p style='color: #721c24; margin: 10px 0; font-size: 15px;'>{$reason}</p>
                    </div>
                    <div style='background: #f8f9fa; border-left: 4px solid #0d3d38; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                        <h3 style='color: #0d3d38; margin-top: 0; font-size: 18px;'>📋 Loan Details</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Loan ID:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>{$loan_id}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Loan Type:</td><td style='padding: 8px 0; color: #333; font-weight: 600; text-align: right;'>{$loan_type}</td></tr>
                            <tr><td style='padding: 8px 0; color: #666; font-size: 14px;'>Approved Amount:</td><td style='padding: 8px 0; color: #0d3d38; font-weight: 700; font-size: 16px; text-align: right;'>₱{$loan_amount}</td></tr>
                        </table>
                    </div>
                </div>
                <div style='background: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                    <p style='color: #999; font-size: 12px; margin: 5px 0;'>© 2025 Evergreen Banking. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";
}
?>