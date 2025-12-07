<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Access denied']));
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    exit(json_encode(['error' => 'DB connection failed']));
}

$loan_id = intval($_GET['id'] ?? 0);
if ($loan_id <= 0) {
    exit(json_encode(['error' => 'Invalid loan ID']));
}

$stmt = $conn->prepare("SELECT * FROM loan_applications WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'id' => $row['id'],
        'full_name' => $row['full_name'] ?? '',
        'account_number' => $row['account_number'] ?? '',
        'contact_number' => $row['contact_number'] ?? '',
        'email' => $row['email'] ?? '',
        'job' => $row['job'] ?? '',
        'monthly_salary' => $row['monthly_salary'] ?? '0',
        'loan_type' => $row['loan_type'] ?? '',
        'loan_amount' => $row['loan_amount'] ?? '0',
        'loan_terms' => $row['loan_terms'] ?? '',
        'purpose' => $row['purpose'] ?? '',
        'created_at' => $row['created_at'] ?? '',
        'due_date' => $row['due_date'] ?? '',
        'monthly_payment' => $row['monthly_payment'] ?? '0',
        'file_url' => $row['file_name'] ?? '',
        'proof_of_income' => $row['proof_of_income'] ?? '',  // Add this line
        'coe_document' => $row['coe_document'] ?? '',        // Add this line
        'status' => $row['status'] ?? '',
        'remarks' => $row['remarks'] ?? '',
        'approved_by' => $row['approved_by'] ?? '',
        'approved_at' => $row['approved_at'] ?? '',
        'next_payment_due' => $row['next_payment_due'] ?? '',
        'rejected_by' => $row['rejected_by'] ?? '',
        'rejected_at' => $row['rejected_at'] ?? '',
        'rejection_remarks' => $row['rejection_remarks'] ?? ''
    ]);
} else {
    echo json_encode(['error' => 'Loan not found']);
}

$stmt->close();
$conn->close();
?>