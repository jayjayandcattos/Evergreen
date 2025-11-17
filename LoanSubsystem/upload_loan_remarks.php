<?php
session_start();
date_default_timezone_set('Asia/Manila'); // <- ONLY THIS LINE ADDED

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

if (!isset($_SESSION['user_email']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$loan_id = intval($input['loan_id'] ?? 0);
$remarks = trim($input['remarks'] ?? '');

if ($loan_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid loan ID']);
    exit();
}
if (empty($remarks)) {
    echo json_encode(['success' => false, 'error' => 'Remarks cannot be empty']);
    exit();
}

// Only update remarks, keep status as Pending
$stmt = $conn->prepare("UPDATE loan_applications SET remarks = ? WHERE id = ?");
$stmt->bind_param("si", $remarks, $loan_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'remarks' => $remarks
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}

$stmt->close();
$conn->close();
?>