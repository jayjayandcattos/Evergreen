<?php
// ✅ CRITICAL: Start output buffering FIRST
ob_start();
// Enable error logging to file instead of output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/pdf_errors.log');
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Clear any accidental output
if (ob_get_length()) ob_clean();

try {
    // ✅ Set JSON header FIRST
    header('Content-Type: application/json; charset=utf-8');

    // Check if FPDF exists
    if (!file_exists('fpdf/fpdf.php')) {
        throw new Exception('FPDF library not found in fpdf/ folder');
    }
    require_once('fpdf/fpdf.php');

    // Validate inputs
    if (!isset($_GET['loan_id'])) {
        throw new Exception('Missing loan_id parameter');
    }
    if (!isset($_GET['type'])) {
        throw new Exception('Missing type parameter');
    }

    $loan_id = intval($_GET['loan_id']);
    $notif_type = trim($_GET['type']);

    if ($loan_id <= 0) {
        throw new Exception('Invalid loan_id: must be greater than 0');
    }

    if (!in_array($notif_type, ['approved', 'active', 'rejected'])) {
        throw new Exception('Invalid type. Must be: approved, active, or rejected');
    }

    // Database connection
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "BankingDB";
    
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Fetch loan with JOIN
    $sql = "SELECT 
                la.*,
                COALESCE(lt.name, 'Unknown') AS loan_type_name
            FROM loan_applications la
            LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
            WHERE la.id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $loan_id);
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        throw new Exception('Loan not found with ID: ' . $loan_id);
    }
    
    $loan = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    // Validate loan data
    if (empty($loan['full_name'])) {
        throw new Exception('Loan data incomplete: missing full_name');
    }
    if (empty($loan['loan_amount'])) {
        throw new Exception('Loan data incomplete: missing loan_amount');
    }

    // Create uploads directory
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Failed to create uploads directory. Check folder permissions.');
        }
    }

    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('uploads/ directory is not writable. Run: chmod 777 uploads/');
    }

    // Create PDF
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

    // Greeting
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

    // ✅ LOAN DETAILS SECTION (FIRST) - Removed Loan Duration
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'LOAN DETAILS', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);

    $details = [
        'Loan ID' => $loan['id'],
        'Loan Type' => $loan['loan_type_name'],
        'Loan Amount' => 'PHP ' . number_format($loan['loan_amount'], 2),
        'Interest Rate' => '20% per annum',
        'Monthly Payment' => 'PHP ' . number_format($loan['monthly_payment'] ?? 0, 2),
        'Total Amount Payable' => 'PHP ' . number_format($loan['loan_amount'] * 1.20, 2)
    ];

    if ($notif_type === 'approved') {
        $details['Status'] = 'Approved - Awaiting Claim';
    } elseif ($notif_type === 'active') {
        $details['Status'] = 'Active';
    } elseif ($notif_type === 'rejected') {
        $details['Status'] = 'Rejected';
    }

    foreach ($details as $label => $value) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 7, $label . ':', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $value, 0, 1, 'L');
    }

    $pdf->Ln(5);

    // ✅ IMPORTANT DETAILS SECTION (REVISED)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'TIME DURATION', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    // Date Where the Loan was Approved
    if ($notif_type === 'approved' || $notif_type === 'active') {
        if (!empty($loan['approved_at'])) {
            $approved_date = date('F j, Y', strtotime($loan['approved_at']));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Date Approved:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $approved_date, 0, 1, 'L');
        }
    }
    
    // Date Where it Started (for active loans only)
    if ($notif_type === 'active') {
        if (!empty($loan['approved_at'])) {
            $term_start = date('F j, Y', strtotime($loan['approved_at']));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Date Where it Started:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $term_start, 0, 1, 'L');
        }
    }
    
    // Loan Duration (moved from Loan Details)
    if (!empty($loan['loan_terms'])) {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(60, 7, 'Loan Duration:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, $loan['loan_terms'], 0, 1, 'L');
    }
    
    // Deadline of the Loan
    if ($notif_type === 'approved') {
        // For approved loans - show claim deadline
        if (!empty($loan['approved_at'])) {
            $claim_deadline = date('F j, Y', strtotime($loan['approved_at'] . ' + 30 days'));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Deadline of the Loan:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $claim_deadline , 0, 1, 'L');
        }
    } elseif ($notif_type === 'active') {
        // For active loans - show final payment deadline
        if (!empty($loan['due_date'])) {
            $loan_deadline = date('F j, Y', strtotime($loan['due_date']));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Deadline of the Loan:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $loan_deadline . ' (Final Payment)', 0, 1, 'L');
        }
        
        // Next Payment Due (for active loans)
        if (!empty($loan['next_payment_due'])) {
            $next_payment = date('F j, Y', strtotime($loan['next_payment_due']));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Next Payment Due:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $next_payment, 0, 1, 'L');
        }
    } elseif ($notif_type === 'rejected') {
        // For rejected loans - show rejection date
        if (!empty($loan['rejected_at'])) {
            $rejected_date = date('F j, Y', strtotime($loan['rejected_at']));
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(60, 7, 'Rejection Date:', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, $rejected_date, 0, 1, 'L');
        }
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

    // ✅ Generate unique filename with .pdf extension
    $filename = "loan_{$notif_type}_{$loan_id}_" . time() . ".pdf";
    $fullPath = $uploadDir . $filename;

    // ✅ Save PDF to file
    $pdf->Output('F', $fullPath);

    // Verify file was created
    if (!file_exists($fullPath)) {
        throw new Exception('PDF file was not created. Check uploads/ folder permissions (chmod 777 uploads/)');
    }

    // ✅ Verify it's actually a PDF file
    $filesize = filesize($fullPath);
    if ($filesize === 0) {
        throw new Exception('PDF file is empty (0 bytes)');
    }

    // Clear output buffer before sending JSON
    ob_end_clean();

    // ✅ Output clean JSON with full path included
    echo json_encode([
        'success' => true, 
        'filename' => $filename,
        'full_path' => $uploadDir . $filename,
        'filesize' => $filesize,
        'type' => $notif_type,
        'loan_id' => $loan_id,
        'message' => 'PDF generated successfully'
    ]);

} catch (Exception $e) {
    // Log error to file
    error_log('PDF Generation Error: ' . $e->getMessage());
    // Clear buffer
    if (ob_get_length()) ob_clean();
    // Return error as JSON
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Error $e) {
    error_log('PDF Generation Fatal Error: ' . $e->getMessage());
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Fatal error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

exit;
?>