<?php
require_once('fpdf/fpdf.php');

// Get chart image from POST request
$chartImageData = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $chartImageData = $input['chartImage'] ?? null;
}

// Input validation
if (!isset($_GET['type']) || !in_array($_GET['type'], ['all', 'active', 'approved', 'pending', 'rejected', 'closed'])) {
    echo json_encode(['error' => 'Invalid report type']);
    exit();
}

$report_type = $_GET['type'];

// Mock admin data
$mockAdmins = [
    [
        'full_name' => 'Jerome Malunes',
        'email' => 'jeromemalunes@gmail.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin',
        'loan_officer_id' => 'LO-0123'
    ]
];
$current_admin = $mockAdmins[0];

// Database connection
require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

if ($conn->connect_error) {
    echo json_encode(['error' => 'DB error: ' . $conn->connect_error]);
    exit();
}

// Build WHERE clause
$where_clause = '';
$report_title = 'All Loans';
switch ($report_type) {
    case 'active': 
        $where_clause = "WHERE la.status = 'Active'"; 
        $report_title = 'Active Loans';
        break;
    case 'approved': 
        $where_clause = "WHERE la.status = 'Approved'"; 
        $report_title = 'Approved Loans (Awaiting Claim)';
        break;
    case 'pending': 
        $where_clause = "WHERE la.status = 'Pending'"; 
        $report_title = 'Pending Loans';
        break;
    case 'rejected': 
        $where_clause = "WHERE la.status = 'Rejected'"; 
        $report_title = 'Rejected Loans';
        break;
    case 'closed': 
        $where_clause = "WHERE la.status = 'Closed'"; 
        $report_title = 'Closed Loans';
        break;
}

// Fetch loans with JOIN to loan_types
$sql = "SELECT 
    la.id AS client_id,
    la.full_name AS client_name,
    COALESCE(lt.name, 'Unknown') AS loan_type,
    la.loan_amount,
    la.loan_terms,
    la.monthly_payment,
    la.status,
    la.created_at,
    la.approved_at,
    la.rejected_at,
    la.next_payment_due,
    la.due_date
FROM loan_applications la
LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
$where_clause 
ORDER BY la.created_at DESC";

$result = $conn->query($sql);
$loans = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }
}

// COUNT loans by status (for overall analytics)
$counts = ['Active' => 0, 'Approved' => 0, 'Pending' => 0, 'Rejected' => 0, 'Closed' => 0];
$allLoansResult = $conn->query("SELECT status, COUNT(*) as total FROM loan_applications GROUP BY status");
if ($allLoansResult) {
    while ($row = $allLoansResult->fetch_assoc()) {
        $status = ucfirst(strtolower(trim($row['status'])));
        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int)$row['total'];
        }
    }
}

$conn->close();

// Generate PDF
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Header
$pdf->Cell(0, 15, 'EVERGREEN TRUST AND SAVINGS LOAN SERVICES', 0, 1, 'C');
$pdf->Ln(3);

// Report Title
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Loan Officer Report: ' . $report_title, 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Reporting Period: ' . date('F Y'), 0, 1, 'L');
$pdf->Cell(0, 6, 'Prepared by: Loan Officer - ' . $current_admin['full_name'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Department: Loan Subsystem', 0, 1, 'L');
$pdf->Ln(8);

// ✅ ADD PIE CHART IMAGE WITH FIXED LAYOUT
if ($chartImageData) {
    // Decode base64 chart image
    $chartImageData = str_replace('data:image/png;base64,', '', $chartImageData);
    $chartImageData = str_replace(' ', '+', $chartImageData);
    $decodedImage = base64_decode($chartImageData);
    
    // Save temporary chart image
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $tempChartPath = $uploadDir . 'temp_chart_' . time() . '.png';
    file_put_contents($tempChartPath, $decodedImage);
    
    // Section title
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Loan Portfolio Analytics', 0, 1, 'L');
    $pdf->Ln(2);
    
    // ✅ Store current Y position
    $startY = $pdf->GetY();
    
    // Insert chart image (left side)
    $pdf->Image($tempChartPath, 15, $startY, 90, 60);
    
    // ✅ Add statistics box (right side) - properly positioned
    $statsX = 115;
    $statsY = $startY;
    
    $pdf->SetXY($statsX, $statsY);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(70, 8, 'Overall Statistics:', 1, 1, 'C', true);
    
    // Active Loans
    $pdf->SetXY($statsX, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Active Loans:', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 7, $counts['Active'], 1, 1, 'C');
    
    // Approved
    $pdf->SetXY($statsX, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Approved (Awaiting):', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 7, $counts['Approved'], 1, 1, 'C');
    
    // Pending
    $pdf->SetXY($statsX, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Pending Review:', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 7, $counts['Pending'], 1, 1, 'C');
    
    // Rejected
    $pdf->SetXY($statsX, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 7, 'Rejected:', 1, 0, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(30, 7, $counts['Rejected'], 1, 1, 'C');
    
    // Total (highlighted)
    $pdf->SetXY($statsX, $pdf->GetY());
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(40, 7, 'Total Loans:', 1, 0, 'L', true);
    $pdf->Cell(30, 7, array_sum($counts), 1, 1, 'C', true);
    
    // Delete temporary chart
    @unlink($tempChartPath);
    
    // ✅ Move to proper position after chart section
    $pdf->SetY($startY + 65);
    $pdf->Ln(5);
}

// ✅ FIXED TABLE - Starts after chart section
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(200, 200, 200);

// Column widths adjusted for landscape
$w = [20, 40, 30, 30, 25, 30, 30, 30, 25];
$header = ['Loan ID','Client Name','Loan Type','Amount','Term','Monthly Payment','Total Payable','Status','Date'];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table rows
$pdf->SetFont('Arial', '', 8);
foreach ($loans as $loan) {
    // ✅ Check if we need a new page
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(200, 200, 200);
        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);
    }
    
    $loan_amount = number_format($loan['loan_amount'], 2);
    $monthly_payment = number_format($loan['monthly_payment'], 2);
    $total_payable = number_format($loan['loan_amount'] * 1.20, 2);
    $status = ucfirst($loan['status']);
    
    // Determine which date to display
    $display_date = '';
    if ($loan['status'] === 'Active' && $loan['approved_at']) {
        $display_date = date('m/d/Y', strtotime($loan['approved_at']));
    } elseif ($loan['status'] === 'Approved' && $loan['approved_at']) {
        $display_date = date('m/d/Y', strtotime($loan['approved_at']));
    } elseif ($loan['status'] === 'Rejected' && $loan['rejected_at']) {
        $display_date = date('m/d/Y', strtotime($loan['rejected_at']));
    } else {
        $display_date = date('m/d/Y', strtotime($loan['created_at']));
    }
    
    $data = [
        $loan['client_id'],
        substr($loan['client_name'], 0, 25),
        substr($loan['loan_type'], 0, 18),
        'PHP ' . $loan_amount,
        $loan['loan_terms'],
        'PHP ' . $monthly_payment,
        'PHP ' . $total_payable,
        $status,
        $display_date
    ];

    for ($i = 0; $i < count($data); $i++) {
        $pdf->Cell($w[$i], 7, $data[$i], 1, 0, 'L');
    }
    $pdf->Ln();
}

// Summary Statistics
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Summary Statistics:', 0, 1);
$pdf->SetFont('Arial', '', 10);

$summary = [
    "Total Loans in Report: " . count($loans),
    "Report Generated: " . date('F j, Y \a\t g:i A')
];

foreach ($summary as $line) {
    $pdf->Cell(0, 6, $line, 0, 1, 'L');
}

// Notes
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, 'Important Notes:', 0, 1);
$pdf->SetFont('Arial', '', 9);

$notes = [
    "- This report is generated from the Evergreen Trust and Savings Loan Management System.",
    "- All monetary amounts are in Philippine Peso (PHP).",
    "- Interest rate applied: 20% per annum.",
    "- Approved loans require client to claim within 30 days.",
    "- For inquiries, contact the Loan Officer Department."
];

foreach ($notes as $note) {
    $pdf->Cell(0, 5, $note, 0, 1, 'L');
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

echo json_encode(['success' => true, 'filename' => $fullPath]);
?>