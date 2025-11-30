<?php
// Suppress error output to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Clean any output that might have been generated
ob_clean();

header('Content-Type: application/json');

// Auto-login bridge: Check if user is logged in via marketing system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        exit(json_encode(['error' => 'Not authenticated']));
    }
}

// Use BankingDB database (same as admin and accounting system)
require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

if (!$conn || $conn->connect_error) {
    exit(json_encode(['error' => 'Database connection failed: ' . ($conn ? $conn->connect_error : 'Connection is null')]));
}

$email = $_SESSION['user_email'];

// Fetch all loans for the user - use COALESCE to get loan_type from either loan_types table or loan_type column
$stmt = $conn->prepare("
    SELECT 
        la.id,
        la.full_name,
        la.email,
        la.contact_number,
        la.loan_amount,
        la.loan_terms,
        la.monthly_payment,
        la.status,
        la.next_payment_due,
        la.due_date,
        la.created_at,
        la.approved_at,
        la.rejected_at,
        la.remarks,
        la.rejection_remarks,
        la.pdf_approved,
        la.pdf_active,
        la.pdf_rejected,
        COALESCE(lt.name, la.loan_type, 'Unknown') AS loan_type
    FROM loan_applications la
    LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
    WHERE (la.email = ? OR la.user_email = ?)
    AND (la.deleted_at IS NULL OR la.deleted_at = '')
    ORDER BY la.id DESC
");

if (!$stmt) {
    $conn->close();
    exit(json_encode(['error' => 'Database query failed: ' . $conn->error]));
}

$stmt->bind_param("ss", $email, $email);
if (!$stmt->execute()) {
    $error_msg = $stmt->error;
    $stmt->close();
    $conn->close();
    exit(json_encode(['error' => 'Query execution failed: ' . $error_msg]));
}

$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

$stmt->close();
$conn->close();

// Clean any trailing output and send JSON
ob_clean();
echo json_encode($loans);
ob_end_flush();
?>
