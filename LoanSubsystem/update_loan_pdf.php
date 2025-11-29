<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Not logged in']));
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "bankingdb";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    exit(json_encode(['error' => 'DB error']));
}

// Add pdf_path column if missing
$conn->query("ALTER TABLE loan_applications ADD COLUMN IF NOT EXISTS pdf_path VARCHAR(255) DEFAULT NULL");

$data = json_decode(file_get_contents('php://input'), true);
$loan_id = intval($data['loan_id'] ?? 0);
$pdf_path = $data['pdf_path'] ?? '';

if ($loan_id <= 0 || !$pdf_path) {
    exit(json_encode(['error' => 'Invalid input']));
}

// Verify loan belongs to user
$stmt = $conn->prepare("SELECT email FROM loan_applications WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit(json_encode(['error' => 'Loan not found']));
}

$row = $result->fetch_assoc();
if ($row['email'] !== $_SESSION['user_email']) {
    exit(json_encode(['error' => 'Unauthorized']));
}
$stmt->close();

// Update PDF path
$stmt = $conn->prepare("UPDATE loan_applications SET pdf_path = ? WHERE id = ?");
$stmt->bind_param("si", $pdf_path, $loan_id);

if ($stmt->execute()) {
    exit(json_encode(['success' => true]));
} else {
    exit(json_encode(['error' => 'Update failed: ' . $conn->error]));
}

$stmt->close();
$conn->close();
?>