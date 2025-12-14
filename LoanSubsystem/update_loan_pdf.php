
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
$db = "BankingDB";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    exit(json_encode(['error' => 'DB error: ' . $conn->connect_error]));
}

// ✅ Ensure columns exist
$conn->query("ALTER TABLE loan_applications ADD COLUMN IF NOT EXISTS pdf_approved VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE loan_applications ADD COLUMN IF NOT EXISTS pdf_active VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE loan_applications ADD COLUMN IF NOT EXISTS pdf_rejected VARCHAR(255) DEFAULT NULL");

$data = json_decode(file_get_contents('php://input'), true);
$loan_id = intval($data['loan_id'] ?? 0);
$pdf_filename = $data['pdf_path'] ?? ''; // This is just the filename
$pdf_type = $data['type'] ?? '';

if ($loan_id <= 0 || !$pdf_filename || !$pdf_type) {
    exit(json_encode(['error' => 'Invalid input - missing loan_id, pdf_path, or type']));
}

// ✅ Validate type
if (!in_array($pdf_type, ['approved', 'active', 'rejected'])) {
    exit(json_encode(['error' => 'Invalid type. Must be: approved, active, or rejected']));
}

// Verify loan belongs to user
$stmt = $conn->prepare("SELECT email FROM loan_applications WHERE id = ?");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    exit(json_encode(['error' => 'Loan not found']));
}

$row = $result->fetch_assoc();
if ($row['email'] !== $_SESSION['user_email']) {
    $stmt->close();
    $conn->close();
    exit(json_encode(['error' => 'Unauthorized']));
}
$stmt->close();

// ✅ CRITICAL FIX: Add uploads/ prefix to the path
$full_pdf_path = 'uploads/' . $pdf_filename;

// ✅ Verify the file actually exists before saving to database
if (!file_exists($full_pdf_path)) {
    $conn->close();
    exit(json_encode(['error' => 'PDF file not found on server: ' . $full_pdf_path]));
}

// ✅ Update the correct PDF column based on type
$column_map = [
    'approved' => 'pdf_approved',
    'active' => 'pdf_active',
    'rejected' => 'pdf_rejected'
];

$column = $column_map[$pdf_type];

// ✅ Store the full path (with uploads/ prefix)
$sql = "UPDATE loan_applications SET $column = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $full_pdf_path, $loan_id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    exit(json_encode([
        'success' => true, 
        'type' => $pdf_type,
        'column' => $column,
        'pdf_path' => $full_pdf_path, // Return full path for frontend
        'message' => 'PDF path updated successfully'
    ]));
} else {
    $error = $conn->error;
    $stmt->close();
    $conn->close();
    exit(json_encode(['error' => 'Update failed: ' . $error]));
}
?>