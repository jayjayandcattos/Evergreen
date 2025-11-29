<?php
session_start();

// Auto-login bridge: Check if user is logged in via marketing system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        die("Error: Not authenticated. Please log in.");
    }
}

// Get user data from bank_customers database
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";
$bankingConn = new mysqli($host, $user, $pass, $db);

$currentUser = null;
if (!$bankingConn->connect_error) {
    $email = $_SESSION['user_email'];
    $sql = "SELECT 
                bc.customer_id,
                bc.first_name,
                bc.middle_name,
                bc.last_name,
                bc.email,
                bc.contact_number,
                TRIM(CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name)) as full_name,
                (SELECT ca.account_number 
                 FROM customer_accounts ca 
                 WHERE ca.customer_id = bc.customer_id 
                 LIMIT 1) as account_number
            FROM bank_customers bc
            WHERE bc.email = ?
            LIMIT 1";
    $stmt = $bankingConn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentUser = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'account_number' => $row['account_number'] ?? 'N/A',
                'contact_number' => $row['contact_number'] ?? 'N/A',
                'job' => $_POST['job'] ?? 'Not Specified',
                'monthly_salary' => isset($_POST['monthly_salary']) ? (float)$_POST['monthly_salary'] : 0
            ];
        }
        $stmt->close();
    }
    $bankingConn->close();
}

if (!$currentUser) {
    die("Error: User not found.");
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "loan_system";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ✅ Validate loan_type_id
$loan_type_id = isset($_POST['loan_type_id']) ? (int)$_POST['loan_type_id'] : 0;
if ($loan_type_id <= 0) {
    die("Error: Please select a valid loan type.");
}

// Get form data
$loan_terms = $_POST['loan_terms'] ?? '12 Months';
$loan_amount = floatval($_POST['loan_amount'] ?? 0);
$purpose = $_POST['purpose'] ?? '';
$user_email = $_POST['email'] ?? $currentUser['email'];

// Validate loan amount
if ($loan_amount < 5000) {
    die("Error: Loan amount must be at least ₱5,000.");
}

// Calculate monthly payment (20% annual interest)
$term_months = (int) filter_var($loan_terms, FILTER_SANITIZE_NUMBER_INT);
$term_months = max(1, $term_months);
$annual_rate = 0.20;
$monthly_rate = $annual_rate / 12;

if ($monthly_rate > 0 && $term_months > 0) {
    $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $term_months)) / (pow(1 + $monthly_rate, $term_months) - 1);
} else {
    $monthly_payment = $loan_amount / max(1, $term_months);
}

// Calculate due date
$due_date = (new DateTime())->modify("+$term_months months")->format('Y-m-d');

// Calculate next payment due (1 month from now)
$next_payment_due = (new DateTime())->modify("+1 month")->format('Y-m-d');

// Handle file uploads
$target_dir = "uploads/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Validate file uploads
if (!isset($_FILES["attachment"]) || $_FILES["attachment"]["error"] !== UPLOAD_ERR_OK) {
    die("Error: Valid ID is required.");
}
if (!isset($_FILES["proof_of_income"]) || $_FILES["proof_of_income"]["error"] !== UPLOAD_ERR_OK) {
    die("Error: Proof of Income is required.");
}
if (!isset($_FILES["coe_document"]) || $_FILES["coe_document"]["error"] !== UPLOAD_ERR_OK) {
    die("Error: Certificate of Employment is required.");
}

// Generate unique filenames
$valid_id_path = $target_dir . uniqid() . "_" . basename($_FILES["attachment"]["name"]);
$proof_income_path = $target_dir . uniqid() . "_" . basename($_FILES["proof_of_income"]["name"]);
$coe_path = $target_dir . uniqid() . "_" . basename($_FILES["coe_document"]["name"]);

// Move uploaded files
if (!move_uploaded_file($_FILES["attachment"]["tmp_name"], $valid_id_path)) {
    die("Error: Failed to upload Valid ID.");
}
if (!move_uploaded_file($_FILES["proof_of_income"]["tmp_name"], $proof_income_path)) {
    die("Error: Failed to upload Proof of Income.");
}
if (!move_uploaded_file($_FILES["coe_document"]["tmp_name"], $coe_path)) {
    die("Error: Failed to upload Certificate of Employment.");
}

// ✅ Prepare INSERT statement - CORRECTED COLUMN ORDER
$stmt = $conn->prepare("
    INSERT INTO loan_applications (
        loan_type_id,
        full_name,
        account_number,
        contact_number,
        email,
        job,
        monthly_salary,
        loan_terms,
        loan_amount,
        purpose,
        file_name,
        proof_of_income,
        coe_document,
        monthly_payment,
        due_date,
        next_payment_due,
        status,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

// ✅ Bind parameters - CORRECTED TYPES
$stmt->bind_param(
    "isssssdsdssssdss",
    $loan_type_id,                    // i = integer
    $currentUser['full_name'],         // s = string
    $currentUser['account_number'],    // s = string
    $currentUser['contact_number'],    // s = string
    $user_email,                       // s = string (email column)
    $currentUser['job'],               // s = string
    $currentUser['monthly_salary'],    // d = double/decimal
    $loan_terms,                       // s = string
    $loan_amount,                      // d = double/decimal
    $purpose,                          // s = string
    $valid_id_path,                    // s = string
    $proof_income_path,                // s = string
    $coe_path,                         // s = string
    $monthly_payment,                  // d = double/decimal
    $due_date,                         // s = string (date)
    $next_payment_due                  // s = string (date)
);

// Execute the query
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    // Redirect to dashboard with success message
    header("Location: index.php?scrollTo=dashboard&success=1");
    exit();
} else {
    $error_message = $stmt->error;
    $stmt->close();
    $conn->close();
    die("Database error: " . $error_message);
}
?>