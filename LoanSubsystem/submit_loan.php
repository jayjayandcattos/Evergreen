
<?php
session_start();
header('Content-Type: application/json');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$response = ['success' => false, 'error' => '', 'loan_id' => null];

try {
    // Auto-login bridge: Check if user is logged in via marketing system
    if (!isset($_SESSION['user_email'])) {
        // Check for marketing session variables (from evergreen-marketing)
        if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
            $_SESSION['user_email'] = $_SESSION['email'];
            $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
            $_SESSION['user_role'] = 'client';
        } else {
            throw new Exception("Not authenticated. Please log in.");
        }
    }

    // Database connection
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "BankingDB";

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get user data from bank_customers database
    $email = $_SESSION['user_email'];
    $user_stmt = $conn->prepare("SELECT 
        bc.customer_id,
        bc.first_name,
        bc.middle_name,
        bc.last_name,
        bc.email,
        bc.contact_number,
        TRIM(CONCAT(bc.first_name, ' ', IFNULL(CONCAT(bc.middle_name, ' '), ''), bc.last_name)) as full_name,
        (SELECT ca.account_number 
         FROM customer_accounts ca 
         WHERE ca.customer_id = bc.customer_id 
         LIMIT 1) as account_number
    FROM bank_customers bc
    WHERE bc.email = ?
    LIMIT 1");

    if (!$user_stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $result = $user_stmt->get_result();
    $currentUser = $result->fetch_assoc();
    $user_stmt->close();

    if (!$currentUser) {
        throw new Exception("User not found in database for email: " . $email);
    }

    // Validate required fields
    $loan_type_id = isset($_POST['loan_type_id']) ? (int)$_POST['loan_type_id'] : 0;
    $loan_terms = isset($_POST['loan_terms']) ? trim($_POST['loan_terms']) : '';
    $loan_amount = isset($_POST['loan_amount']) ? (float)$_POST['loan_amount'] : 0;
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';

    if ($loan_type_id <= 0) {
        throw new Exception("Invalid loan type selected.");
    }

    if (empty($loan_terms)) {
        throw new Exception("Please select loan terms.");
    }
    
    if ($loan_amount < 5000) {
        throw new Exception("Loan amount must be at least ₱5,000.");
    }
    
    if (empty($purpose)) {
        throw new Exception("Please provide the purpose of the loan.");
    }

    // Calculate monthly payment (20% annual interest rate)
    $interest_rate = 0.20; // 20% per annum
    $term_months = (int)filter_var($loan_terms, FILTER_SANITIZE_NUMBER_INT);
    if ($term_months <= 0) {
        throw new Exception("Invalid loan term.");
    }

    // Monthly payment calculation: P * (r(1+r)^n) / ((1+r)^n - 1)
    $monthly_rate = $interest_rate / 12;
    $monthly_payment = $loan_amount * ($monthly_rate * pow(1 + $monthly_rate, $term_months)) / (pow(1 + $monthly_rate, $term_months) - 1);

    // Handle file uploads
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception("Failed to create uploads directory.");
        }
    }

    $file_name = '';
    $proof_of_income = '';
    $coe_document = '';

    // Upload Valid ID
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Invalid file type for Valid ID. Allowed: " . implode(', ', $allowed_ext));
        }
        $file_name = 'uploads/valid_id_' . time() . '_' . uniqid() . '.' . $file_ext;
        $file_path = __DIR__ . '/' . $file_name;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
            throw new Exception("Failed to upload valid ID.");
        }
    }

    // Upload Proof of Income
    if (isset($_FILES['proof_of_income']) && $_FILES['proof_of_income']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['proof_of_income']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Invalid file type for Proof of Income.");
        }
        $proof_of_income = 'uploads/proof_income_' . time() . '_' . uniqid() . '.' . $file_ext;
        $proof_path = __DIR__ . '/' . $proof_of_income;
        if (!move_uploaded_file($_FILES['proof_of_income']['tmp_name'], $proof_path)) {
            throw new Exception("Failed to upload proof of income.");
        }
    }

    // Upload COE Document
    if (isset($_FILES['coe_document']) && $_FILES['coe_document']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['coe_document']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Invalid file type for COE. Only PDF, DOC, DOCX allowed.");
        }
        $coe_document = 'uploads/coe_' . time() . '_' . uniqid() . '.' . $file_ext;
        $coe_path = __DIR__ . '/' . $coe_document;
        if (!move_uploaded_file($_FILES['coe_document']['tmp_name'], $coe_path)) {
            throw new Exception("Failed to upload COE document.");
        }
    }

    // Get full name and other user details
    $full_name = $currentUser['full_name'] ?? '';
    $account_number = $currentUser['account_number'] ?? '';
    $contact_number = $currentUser['contact_number'] ?? '';
    $user_email_db = $currentUser['email'] ?? $email;

    // ✅ FIXED: Removed loan_type column from INSERT
    $insert_stmt = $conn->prepare("INSERT INTO loan_applications (
        loan_type_id,
        full_name,
        account_number,
        contact_number,
        email,
        user_email,
        loan_terms,
        loan_amount,
        purpose,
        monthly_payment,
        status,
        file_name,
        proof_of_income,
        coe_document,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, NOW())");

    if (!$insert_stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    // ✅ FIXED: Removed loan_type_name from bind_param (changed from "isssssssdsssss" to "isssssssdssss")
    $insert_stmt->bind_param(
        "isssssssdssss",
        $loan_type_id,
        $full_name,
        $account_number,
        $contact_number,
        $user_email_db,
        $email,
        $loan_terms,
        $loan_amount,
        $purpose,
        $monthly_payment,
        $file_name,
        $proof_of_income,
        $coe_document
    );

    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to submit loan application: " . $insert_stmt->error);
    }

    $loan_id = $insert_stmt->insert_id;
    $insert_stmt->close();
    $conn->close();

// At the end of submit_loan.php, replace the response with:

$response['success'] = true;
$response['loan_id'] = $loan_id;
$response['message'] = 'Loan application submitted successfully!';
$response['redirect'] = 'index.php?scrollTo=dashboard'; // ✅ Add redirect URL

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    // Log the error
    error_log("Loan Submission Error: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>