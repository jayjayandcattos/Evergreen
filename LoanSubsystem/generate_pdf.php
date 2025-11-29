<?php
// ✅ CRITICAL: Start output buffering and suppress all errors BEFORE any output
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// ✅ Clear any accidental output
if (ob_get_length()) ob_clean();

try {
    // ✅ Set JSON header FIRST
    header('Content-Type: application/json; charset=utf-8');
    
    if (!file_exists('fpdf/fpdf.php')) {
        echo json_encode(['success' => false, 'error' => 'FPDF library not found. Please install FPDF in fpdf/ folder']);
        exit;
    }

    require_once('fpdf/fpdf.php');

    if (!isset($_GET['loan_id']) || !isset($_GET['type'])) {
        echo json_encode(['success' => false, 'error' => 'Missing loan_id or type parameter']);
        exit;
    }

    $loan_id = intval($_GET['loan_id']);
    $notif_type = trim($_GET['type']); // 'approved', 'active', or 'rejected'
    
    if ($loan_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid loan_id']);
        exit;
    }

    if (!in_array($notif_type, ['approved', 'active', 'rejected'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid type. Must be: approved, active, or rejected']);
        exit;
    }

    $conn = new mysqli("localhost", "root", "", "loan_system");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    $stmt = $conn->prepare("SELECT la.*, COALESCE(lt.name, 'Unknown') AS loan_type_name FROM loan_applications la LEFT JOIN loan_types lt ON la.loan_type_id = lt.id WHERE la.id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database query failed']);
        exit;
    }
    
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Loan not found']);
        exit;
    }

    $loan = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create uploads directory']);
            exit;
        }
    }

    // ✅ Create PDF in memory
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 25);

    // Header
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 15, 'EVERGREEN TRUST AND SAVINGS', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LOAN SERVICES', 0, 1, 'C');
    $pdf->Ln(10);

    // Title based on notification type
    $pdf->SetFont('Arial', 'B', 14);
    if ($notif_type === 'approved') {
        $pdf->Cell(0, 10, 'LOAN APPROVAL NOTIFICATION', 0, 1, 'C');
    } elseif ($notif_type === 'active') {
        $pdf->Cell(0, 10, 'LOAN ACTIVATION NOTIFICATION', 0, 1, 'C');
    } elseif ($notif_type === 'rejected') {
        $pdf->Cell(0, 10, 'LOAN REJECTION NOTICE', 0, 1, 'C');
    }
    $pdf->Ln(5);

    // Date and greeting
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, 'Date: ' . date('F j, Y'), 0, 1, 'L');
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Dear ' . $loan['full_name'] . ',', 0, 1, 'L');
    $pdf->Ln(3);

    // Message content
    $pdf->SetFont('Arial', '', 11);
    $message = '';

    if ($notif_type === 'approved') {
        $message = "We are pleased to inform you that your loan application has been APPROVED!\n\n";
        $message .= "Please visit our bank within 30 days to claim your loan. Failure to claim within this period will result in cancellation.\n\n";
        $message .= "Please bring a valid ID and be prepared to sign the loan agreement documents.";
    } elseif ($notif_type === 'active') {
        $message = "Thank you for applying and claiming your loan with Evergreen Trust and Savings!\n\n";
        $message .= "Your loan has been successfully disbursed and is now ACTIVE. Please ensure to make your monthly payments on time to maintain a good credit standing.\n\n";

        if (!empty($loan['next_payment_due'])) {
            $next_due = date('F j, Y', strtotime($loan['next_payment_due']));
            $message .= "Your first payment of PHP " . number_format($loan['monthly_payment'], 2) . " is due on {$next_due}.\n\n";
        }

        $message .= "Payment Options:\n";
        $message .= "- Visit any Evergreen Trust and Savings branch\n";
        $message .= "- Online banking portal\n";
        $message .= "- Auto-debit arrangement\n\n";
        $message .= "Late payments may incur penalties and affect your credit score. Please pay within the designated due date each month.";
    } elseif ($notif_type === 'rejected') {
        $message = "We regret to inform you that your loan application has been REJECTED.\n\n";
        $rejection_reason = $loan['rejection_remarks'] ?: 'Your application does not meet our current lending requirements.';
        $message .= "REASON FOR REJECTION:\n" . $rejection_reason . "\n\n";
        $message .= "You may reapply for a new loan in the future. Please contact our loan officer for more details.";
    }

    $pdf->MultiCell(0, 6, $message, 0, 'L');
    $pdf->Ln(5);

    // Loan details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'LOAN DETAILS', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);

    $details = [
        'Loan ID' => $loan['id'],
        'Loan Type' => $loan['loan_type_name'],
        'Loan Amount' => 'PHP ' . number_format($loan['loan_amount'], 2),
        'Loan Term' => $loan['loan_terms'],
        'Interest Rate' => '20% per annum',
        'Monthly Payment' => 'PHP ' . number_format($loan['monthly_payment'], 2),
        'Total Amount Payable' => 'PHP ' . number_format($loan['loan_amount'] * 1.20, 2)
    ];

    if ($notif_type === 'approved') {
        if (!empty($loan['approved_at'])) {
            $claim_deadline = date('F j, Y', strtotime($loan['approved_at'] . ' + 30 days'));
            $details['Approval Date'] = date('F j, Y', strtotime($loan['approved_at']));
            $details['Claim Deadline'] = $claim_deadline;
        }
        $details['Status'] = 'Approved - Awaiting Claim';
    } elseif ($notif_type === 'active') {
        if (!empty($loan['approved_at'])) {
            $details['Activation Date'] = date('F j, Y', strtotime($loan['approved_at']));
        }
        if (!empty($loan['next_payment_due'])) {
            $details['Next Payment Due'] = date('F j, Y', strtotime($loan['next_payment_due']));
        }
        if (!empty($loan['due_date'])) {
            $details['Final Payment Due'] = date('F j, Y', strtotime($loan['due_date']));
        }
        $details['Status'] = 'Active';
    } elseif ($notif_type === 'rejected') {
        if (!empty($loan['rejected_at'])) {
            $details['Rejection Date'] = date('F j, Y', strtotime($loan['rejected_at']));
        }
        $details['Status'] = 'Rejected';
    }

    foreach ($details as $label => $value) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 7, $label . ':', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $value, 0, 1, 'L');
    }

    // Footer
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    
    if ($notif_type === 'rejected') {
        $footer_text = "We appreciate your interest in Evergreen Trust and Savings. If you have any questions about your application, please feel free to contact us.";
    } else {
        $footer_text = "Thank you for choosing Evergreen Trust and Savings. We are committed to providing you with excellent financial services.";
    }
    $pdf->MultiCell(0, 6, $footer_text, 0, 'C');

    $pdf->Ln(5);
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 5, 'For inquiries: support@evergreenbank.com | Phone: 1-800-EVERGREEN', 0, 1, 'C');

    $pdf->SetY(-20);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 10, 'Generated by Evergreen Trust and Savings - ' . date('Y-m-d H:i:s'), 0, 0, 'C');

    // ✅ FIXED: Unique filename for each notification type
    $filename = "loan_{$notif_type}_{$loan_id}_" . time() . ".pdf";
    $fullPath = $uploadDir . $filename;

    // ✅ Save PDF to file (not output to browser)
    $pdf->Output('F', $fullPath);
    
    if (!file_exists($fullPath)) {
        echo json_encode(['success' => false, 'error' => 'PDF file was not created. Check uploads/ folder permissions.']);
        exit;
    }

    // ✅ Clear output buffer before sending JSON
    ob_end_clean();
    
    // ✅ Output ONLY clean JSON
    echo json_encode([
        'success' => true, 
        'filename' => $fullPath,
        'type' => $notif_type,
        'loan_id' => $loan_id,
        'message' => 'PDF generated successfully'
    ]);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
} catch (Error $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
}

exit;
?>