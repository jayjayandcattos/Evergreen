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
    $db = "bankingdb";

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
    
    // ✅ FIXED: Get loan_valid_id_type (FK to loan_valid_id.id) and valid_id_number
    $loan_valid_id_type = isset($_POST['loan_valid_id_type']) ? (int)$_POST['loan_valid_id_type'] : 0;
    $valid_id_number = isset($_POST['valid_id_number']) ? trim($_POST['valid_id_number']) : '';

    // Validation
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
    
    // Validate valid ID type and number
    if ($loan_valid_id_type <= 0) {
        throw new Exception("Please select a valid ID type.");
    }
    
    if (empty($valid_id_number)) {
        throw new Exception("Please enter your ID number.");
    }

    // ✅ FIXED: Verify that the loan_valid_id_type exists in loan_valid_id table
    $verify_id_stmt = $conn->prepare("SELECT id FROM loan_valid_id WHERE id = ?");
    if ($verify_id_stmt) {
        $verify_id_stmt->bind_param("i", $loan_valid_id_type);
        $verify_id_stmt->execute();
        $verify_result = $verify_id_stmt->get_result();
        if ($verify_result->num_rows === 0) {
            throw new Exception("Invalid ID type selected.");
        }
        $verify_id_stmt->close();
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
    
    // Round to 2 decimal places
    $monthly_payment = round($monthly_payment, 2);

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
    $max_file_size = 5 * 1024 * 1024; // 5MB

    // Upload Valid ID
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['attachment']['size'] > $max_file_size) {
            throw new Exception("Valid ID file size exceeds 5MB limit.");
        }
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
    } else {
        throw new Exception("Please upload your valid ID.");
    }

    // Upload Proof of Income
    if (isset($_FILES['proof_of_income']) && $_FILES['proof_of_income']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['proof_of_income']['size'] > $max_file_size) {
            throw new Exception("Proof of Income file size exceeds 5MB limit.");
        }
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
    } else {
        throw new Exception("Please upload your proof of income.");
    }

    // Upload COE Document
    if (isset($_FILES['coe_document']) && $_FILES['coe_document']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['coe_document']['size'] > $max_file_size) {
            throw new Exception("COE document file size exceeds 5MB limit.");
        }
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
    } else {
        throw new Exception("Please upload your Certificate of Employment (COE).");
    }

    // Get full name and other user details
    $full_name = $currentUser['full_name'] ?? '';
    $account_number = $currentUser['account_number'] ?? '';
    $contact_number = $currentUser['contact_number'] ?? '';
    $user_email_db = $currentUser['email'] ?? $email;

    // ✅ FIXED: Insert into loan_applications with correct column name loan_valid_id_type
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
        loan_valid_id_type,
        valid_id_number,
        monthly_payment,
        status,
        file_name,
        proof_of_income,
        coe_document,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, NOW())");

    if (!$insert_stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    // ✅ FIXED: Bind parameters - 15 total parameters
    // Types: i=integer, s=string, d=double
    // Order: loan_type_id(i), full_name(s), account_number(s), contact_number(s), 
    //        email(s), user_email(s), loan_terms(s), loan_amount(d), purpose(s),
    //        loan_valid_id_type(i), valid_id_number(s), monthly_payment(d), 
    //        file_name(s), proof_of_income(s), coe_document(s)
    
    $insert_stmt->bind_param(
        "issssssdsisssss",
        $loan_type_id,           // i - integer
        $full_name,              // s - string
        $account_number,         // s - string
        $contact_number,         // s - string
        $user_email_db,          // s - string (email column)
        $email,                  // s - string (user_email column)
        $loan_terms,             // s - string
        $loan_amount,            // d - double
        $purpose,                // s - string
        $loan_valid_id_type,     // i - integer (FK to loan_valid_id.id)
        $valid_id_number,        // s - string
        $monthly_payment,        // d - double
        $file_name,              // s - string
        $proof_of_income,        // s - string
        $coe_document            // s - string
    );

    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to submit loan application: " . $insert_stmt->error);
    }

    $loan_id = $insert_stmt->insert_id; 
    $insert_stmt->close();
    $conn->close();

    $response['success'] = true;
    $response['loan_id'] = $loan_id;
    $response['message'] = 'Loan application submitted successfully!';
    $response['reference_number'] = 'LOAN-' . str_pad($loan_id, 6, '0', STR_PAD_LEFT);
    $response['date'] = date('F d, Y');

} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    // Log the error with details
    error_log("Loan Submission Error: " . $e->getMessage());
    error_log("Line: " . $e->getLine());
}

echo json_encode($response);
exit();
?>