<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$user_email = $_SESSION['user_email'];

$stmt = $conn->prepare("
    SELECT 
        id, loan_type, loan_amount, loan_terms, monthly_payment, due_date,
        status, remarks, created_at, approved_by, approved_at, next_payment_due,
        rejected_by, rejected_at, rejection_remarks, pdf_path
    FROM loan_applications 
    WHERE email = ? 
    ORDER BY created_at DESC
");

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

echo json_encode($loans);
$stmt->close();
$conn->close();
?>