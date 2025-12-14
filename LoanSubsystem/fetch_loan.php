<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_email'])) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not authenticated. Please log in.']);
        exit;
    }
}

// DB Connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$email = $_SESSION['user_email'];

// ✅ FIXED: Corrected JOIN condition to properly fetch valid_id_type name
$stmt = $conn->prepare("
    SELECT 
        la.id,
        la.loan_type_id,
        la.full_name,
        la.account_number,
        la.contact_number,
        la.email,
        la.user_email,
        la.job,
        la.monthly_salary,
        la.loan_terms,
        la.loan_amount,
        la.purpose,
        la.loan_valid_id_type,
        la.valid_id_number,
        la.monthly_payment,
        la.status,
        la.due_date,
        la.next_payment_due,
        la.file_name,
        la.proof_of_income,
        la.coe_document,
        la.pdf_path,
        la.pdf_approved,
        la.pdf_active,
        la.pdf_rejected,
        la.created_at,
        la.approved_by,
        la.approved_at,
        la.rejected_by,
        la.rejected_at,
        la.remarks,
        la.rejection_remarks,
        lt.name AS loan_type,
        lvi.valid_id_type
    FROM loan_applications la
    LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
    LEFT JOIN loan_valid_id lvi ON la.loan_valid_id_type = lvi.id
    WHERE la.email = ?
    ORDER BY la.id DESC
");

if (!$stmt) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Query prepare failed: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("s", $email);

if (!$stmt->execute()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Query execute failed: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$result = $stmt->get_result();
$loans = [];

while ($row = $result->fetch_assoc()) {
    // ✅ Ensure all required fields have fallback values
    $row['loan_type'] = $row['loan_type'] ?? 'Unknown Loan Type';
    $row['loan_amount'] = $row['loan_amount'] ?? '0.00';
    $row['monthly_payment'] = $row['monthly_payment'] ?? '0.00';
    $row['valid_id_type'] = $row['valid_id_type'] ?? 'N/A';
    $row['valid_id_number'] = $row['valid_id_number'] ?? 'N/A';
    
    // ✅ Ensure file paths are set
    $row['file_name'] = $row['file_name'] ?? '';
    $row['proof_of_income'] = $row['proof_of_income'] ?? '';
    $row['coe_document'] = $row['coe_document'] ?? '';
    
    $loans[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($loans);
exit;
?>