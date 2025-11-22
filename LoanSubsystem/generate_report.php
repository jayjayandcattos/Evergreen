<?php
session_start();
require_once('fpdf/fpdf.php');
require_once('config/database.php');

// Input validation
if (!isset($_GET['type']) || !in_array($_GET['type'], ['all', 'approved', 'pending', 'rejected', 'closed'])) {
    echo json_encode(['error' => 'Invalid report type']);
    exit();
}

// Check if user is authenticated as admin
if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$report_type = $_GET['type'];

// Get admin data from session (real client data)
$current_admin = [
    'full_name' => $_SESSION['full_name'] ?? 'Loan Officer',
    'email' => $_SESSION['user_email'] ?? '',
    'loan_officer_id' => $_SESSION['loan_officer_id'] ?? 'LO-0000'
];

// Database connection using config
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Build WHERE clause - Use case-insensitive matching
$where_clause = '';
switch ($report_type) {
    case 'approved': $where_clause = "WHERE LOWER(TRIM(status)) = 'approved'"; break;
    case 'pending': $where_clause = "WHERE LOWER(TRIM(status)) = 'pending'"; break;
    case 'rejected': $where_clause = "WHERE LOWER(TRIM(status)) = 'rejected'"; break;
    case 'closed': $where_clause = "WHERE LOWER(TRIM(status)) = 'closed'"; break;
    // 'all' → no WHERE clause
}

// Fetch loans with real client data from loan_applications
// Use proper WHERE clause with status matching
$sql = "SELECT 
    la.id AS client_id,
    la.full_name AS client_name,
    la.loan_type,
    la.loan_amount,
    la.loan_terms,
    la.monthly_payment,
    la.status,
    la.created_at,
    la.next_payment_due,
    la.due_date,
    la.account_number,
    la.email,
    la.contact_number
FROM loan_applications la";

// Build WHERE clause properly
if (!empty($where_clause)) {
    $sql .= " " . $where_clause;
}

$sql .= " ORDER BY la.created_at DESC";

// Execute query with error handling
$result = $conn->query($sql);
$loans = [];

if (!$result) {
    // Log error and return JSON error response
    $error_msg = "Query failed: " . $conn->error;
    error_log($error_msg);
    error_log("SQL Query: " . $sql);
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit();
}

// Process results - fetch all loan applications matching the status
while ($row = $result->fetch_assoc()) {
        // For Pending and Rejected loans, no payments have been made yet
        // Only calculate payments for Approved loans
        $total_paid = 0.00;
        $remaining_balance = floatval($row['loan_amount']);
        
        // Only calculate payments for approved loans
        if ($row['status'] === 'Approved' && !empty($row['account_number'])) {
            // Get account_id from account_number
            $account_stmt = $conn->prepare("
                SELECT account_id 
                FROM customer_accounts 
                WHERE account_number = ? 
                LIMIT 1
            ");
            if ($account_stmt) {
                $account_stmt->bind_param("s", $row['account_number']);
                $account_stmt->execute();
                $account_result = $account_stmt->get_result();
                if ($account_data = $account_result->fetch_assoc()) {
                    $account_id = $account_data['account_id'];
                    
                    // Get loan payment transaction type ID
                    $type_stmt = $conn->prepare("
                        SELECT transaction_type_id 
                        FROM transaction_types 
                        WHERE (type_name LIKE '%loan%payment%' 
                           OR type_name LIKE '%loan%repayment%'
                           OR type_name LIKE '%loan payment%'
                           OR type_name LIKE '%loan repayment%')
                        LIMIT 1
                    ");
                    $type_stmt->execute();
                    $type_result = $type_stmt->get_result();
                    $transaction_type_id = null;
                    if ($type_row = $type_result->fetch_assoc()) {
                        $transaction_type_id = $type_row['transaction_type_id'];
                    }
                    $type_stmt->close();
                    
                    // Calculate total paid from bank_transactions
                    $loan_id = intval($row['client_id']);
                    $loan_id_pattern = "%loan%{$loan_id}%";
                    $loan_payment_pattern = "%loan%payment%";
                    
                    if ($transaction_type_id) {
                        $payment_sql = "
                            SELECT COALESCE(SUM(ABS(amount)), 0) as total_paid
                            FROM bank_transactions bt
                            WHERE bt.account_id = ?
                            AND (
                                bt.description LIKE ? 
                                OR bt.description LIKE ?
                                OR bt.description LIKE ?
                                OR (bt.transaction_type_id = ? AND bt.description LIKE '%loan%')
                            )
                        ";
                        $loan_id_direct = "%Loan ID: {$loan_id}%";
                        $payment_stmt = $conn->prepare($payment_sql);
                        if ($payment_stmt) {
                            $payment_stmt->bind_param("issi", 
                                $account_id, 
                                $loan_id_pattern,
                                $loan_payment_pattern,
                                $loan_id_direct,
                                $transaction_type_id
                            );
                            $payment_stmt->execute();
                            $payment_result = $payment_stmt->get_result();
                            if ($payment_row = $payment_result->fetch_assoc()) {
                                $total_paid = floatval($payment_row['total_paid']);
                            }
                            $payment_stmt->close();
                        }
                    } else {
                        // Fallback: search by description patterns
                        $payment_sql = "
                            SELECT COALESCE(SUM(ABS(amount)), 0) as total_paid
                            FROM bank_transactions bt
                            WHERE bt.account_id = ?
                            AND (
                                bt.description LIKE ? 
                                OR bt.description LIKE ?
                                OR bt.description LIKE ?
                                OR bt.description LIKE '%loan%repayment%'
                            )
                        ";
                        $loan_id_direct = "%Loan ID: {$loan_id}%";
                        $payment_stmt = $conn->prepare($payment_sql);
                        if ($payment_stmt) {
                            $payment_stmt->bind_param("iss", 
                                $account_id, 
                                $loan_id_pattern,
                                $loan_payment_pattern,
                                $loan_id_direct
                            );
                            $payment_stmt->execute();
                            $payment_result = $payment_stmt->get_result();
                            if ($payment_row = $payment_result->fetch_assoc()) {
                                $total_paid = floatval($payment_row['total_paid']);
                            }
                            $payment_stmt->close();
                        }
                    }
                }
                $account_stmt->close();
            }
        }
        
        // Calculate remaining balance (for pending/rejected, it equals loan amount)
        if ($row['status'] === 'Approved') {
            $remaining_balance = max(0, floatval($row['loan_amount']) - $total_paid);
        }
        
        // Add calculated fields to loan data
        $row['total_paid'] = $total_paid;
        $row['remaining_balance'] = $remaining_balance;
        
        $loans[] = $row;
    }

// ✅ COUNT loans by status for Notes section
$counts = ['Approved' => 0, 'Pending' => 0, 'Rejected' => 0, 'Closed' => 0];
foreach ($loans as $loan) {
    $status = ucfirst(strtolower(trim($loan['status'])));
    if (array_key_exists($status, $counts)) {
        $counts[$status]++;
    }
}

// Generate PDF
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Header
$pdf->Cell(0, 15, 'EVERGREEN TRUST AND SAVINGS LOAN SERVICES', 0, 1, 'C');
$pdf->Ln(5);

// Loan Officer Report Header
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Loan Officer Report (Monthly)', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 8, 'Reporting Period: ' . date('F Y'), 0, 1, 'L');
$pdf->Cell(0, 8, 'Prepared by: Loan Officer - ' . $current_admin['full_name'], 0, 1, 'L');
$pdf->Cell(0, 8, 'Department: Loan Subsystem', 0, 1, 'L');
$pdf->Ln(10);

// Table header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$w = [25, 40, 35, 30, 30, 35, 35, 35, 25];
$header = ['Client ID','Client Name','Loan Type','Loan Amount','Term','Monthly Payment','Total Paid','Remaining Balance','Status'];
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table rows - Using real client data
$pdf->SetFont('Arial', '', 9);
foreach ($loans as $loan) {
    $loan_amount = number_format($loan['loan_amount'], 2);
    $monthly_payment = number_format($loan['monthly_payment'] ?? 0, 2);
    // Use calculated real data instead of hardcoded values
    $total_paid = number_format($loan['total_paid'] ?? 0, 2);
    $remaining_balance = number_format($loan['remaining_balance'] ?? $loan['loan_amount'], 2);
    $status = ucfirst($loan['status']);
    
    $data = [
        $loan['client_id'],
        substr($loan['client_name'], 0, 25),
        $loan['loan_type'],
        'PHP ' . $loan_amount,
        $loan['loan_terms'],
        'PHP ' . $monthly_payment,
        'PHP ' . $total_paid,
        'PHP ' . $remaining_balance,
        $status
    ];

    for ($i = 0; $i < count($data); $i++) {
        $pdf->Cell($w[$i], 7, $data[$i], 1, 0, 'L');
    }
    $pdf->Ln();
}

// Notes section - NO BULLETS, NO SPECIAL CHARACTERS
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Notes:', 0, 1);
$pdf->SetFont('Arial', '', 10);

$notes = [
    "Total Paid and Remaining Balance are based on current system records.",
    "This report was generated on " . date('F j, Y \a\t g:i A') . ".",
    "Approved Loans: " . $counts['Approved'],
    "Rejected Loans: " . $counts['Rejected'],
    "Pending Loans: " . $counts['Pending']
];

foreach ($notes as $note) {
    $pdf->MultiCell(0, 6, $note, 0, 'L');
}

// Footer
$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Generated by Evergreen Trust and Savings - Page ' . $pdf->PageNo(), 0, 0, 'C');

// Save PDF
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = "loan_report_{$report_type}_" . date('YmdHis') . ".pdf";
$fullPath = $uploadDir . $filename;
$pdf->Output('F', $fullPath);

// Close database connection
if ($conn) {
    $conn->close();
}

echo json_encode(['success' => true, 'filename' => $fullPath]);
?>