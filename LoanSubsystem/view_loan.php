<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

/* ---------------------------------------------
   ACCESS CONTROL
-----------------------------------------------*/
if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

/* ---------------------------------------------
   DATABASE CONNECTION
-----------------------------------------------*/
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

/* ---------------------------------------------
   VALIDATE INPUT
-----------------------------------------------*/
$loan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($loan_id <= 0) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => 'Invalid loan ID']);
    exit;
}

/* ---------------------------------------------
   MAIN QUERY - FIXED JOIN
-----------------------------------------------*/
$stmt = $conn->prepare("
    SELECT 
        la.*,
        lt.name AS loan_type,
        lvi.valid_id_type
    FROM loan_applications la
    LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
    LEFT JOIN loan_valid_id lvi ON la.loan_valid_id_type = lvi.loan_valid_id_type
    WHERE la.id = ?
");

if (!$stmt) {
    ob_clean();
    echo json_encode(['error' => 'Database query failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    ob_clean();
    http_response_code(404);
    echo json_encode(['error' => 'Loan application not found']);
    exit;
}

$row = $result->fetch_assoc();

/* ---------------------------------------------
   FORMAT FILE FIELDS
-----------------------------------------------*/
$row['file_url']        = !empty($row['file_name']) ? $row['file_name'] : '';
$row['proof_of_income'] = !empty($row['proof_of_income']) ? $row['proof_of_income'] : '';
$row['coe_document']    = !empty($row['coe_document']) ? $row['coe_document'] : '';

/* ---------------------------------------------
   FINAL JSON OUTPUT
-----------------------------------------------*/
ob_clean();
echo json_encode([
    'id' => $row['id'],
    'full_name' => $row['full_name'] ?? '',
    'account_number' => $row['account_number'] ?? '',
    'contact_number' => $row['contact_number'] ?? '',
    'email' => $row['email'] ?? '',
    'job' => $row['job'] ?? '',
    'monthly_salary' => $row['monthly_salary'] ?? '0',
    'loan_type' => $row['loan_type'] ?? 'Unknown',
    'loan_amount' => $row['loan_amount'] ?? '0',
    'loan_terms' => $row['loan_terms'] ?? '',
    'purpose' => $row['purpose'] ?? '',

    // ✅ FIXED: Return the valid_id_type name from loan_valid_id table
    'valid_id_type' => $row['valid_id_type'] ?? 'N/A',
    'valid_id_number' => $row['valid_id_number'] ?? '',

    'created_at' => $row['created_at'] ?? '',
    'due_date' => $row['due_date'] ?? '',
    'monthly_payment' => $row['monthly_payment'] ?? '0',
    'file_url' => $row['file_url'],
    'proof_of_income' => $row['proof_of_income'],
    'coe_document' => $row['coe_document'],
    'status' => $row['status'] ?? '',
    'remarks' => $row['remarks'] ?? '',
    'approved_by' => $row['approved_by'] ?? '',
    'approved_at' => $row['approved_at'] ?? '',
    'next_payment_due' => $row['next_payment_due'] ?? '',
    'rejected_by' => $row['rejected_by'] ?? '',
    'rejected_at' => $row['rejected_at'] ?? '',
    'rejection_remarks' => $row['rejection_remarks'] ?? ''
]);

$stmt->close();
$conn->close();
ob_end_flush();
?>