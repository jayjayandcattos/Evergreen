<?php
/**
 * Open New Account API
 * Opens a new Savings or Checking account for an existing customer
 */

// Start session before any output
session_start();

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);
error_log("=== Account Opening API Called ===");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

try {
    // Get form data (multipart/form-data for file upload)
    $input = $_POST;
    error_log("POST data: " . print_r($input, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    if (empty($input)) {
        error_log("ERROR: Empty input data");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        exit();
    }

    // Validate required fields
    $errors = [];
    
    // Validate existing account number
    if (empty($input['existing_account_number'])) {
        $errors['existing_account_number'] = 'Existing account number is required';
    }
    
    if (empty($input['account_type'])) {
        $errors['account_type'] = 'Account type is required';
    } else {
        // Normalize incoming type to canonical names
        $typeRaw = trim($input['account_type']);
        $typeNormalized = $typeRaw;
        if (strcasecmp($typeRaw, 'Savings') === 0) {
            $typeNormalized = 'Savings Account';
        } elseif (strcasecmp($typeRaw, 'Checking') === 0) {
            $typeNormalized = 'Checking Account';
        }

        // Accept both canonical and short forms
        $allowed = ['Savings', 'Checking', 'Savings Account', 'Checking Account'];
        if (!in_array($typeRaw, $allowed, true) && !in_array($typeNormalized, ['Savings Account', 'Checking Account'], true)) {
            $errors['account_type'] = 'Invalid account type. Must be Savings Account or Checking Account';
        } else {
            // Overwrite input with normalized canonical name for downstream usage
            $input['account_type'] = $typeNormalized;
        }
    }

    // Validate ID fields
    if (empty($input['id_type'])) {
        $errors['id_type'] = 'ID type is required';
    }
    
    if (empty($input['id_number'])) {
        $errors['id_number'] = 'ID number is required';
    }

    // Validate ID image uploads
    if (!isset($_FILES['id_front_image']) || $_FILES['id_front_image']['error'] !== UPLOAD_ERR_OK) {
        $errors['id_front_image'] = 'Front image of ID is required';
    }
    
    if (!isset($_FILES['id_back_image']) || $_FILES['id_back_image']['error'] !== UPLOAD_ERR_OK) {
        $errors['id_back_image'] = 'Back image of ID is required';
    }

    // Validate initial deposit if provided
    $initialDeposit = null;
    $depositSource = null;
    $sourceAccountNumber = null;
    
    if (isset($input['initial_deposit']) && $input['initial_deposit'] !== null && $input['initial_deposit'] !== '') {
        $initialDeposit = floatval($input['initial_deposit']);
        if ($initialDeposit < 0) {
            $errors['initial_deposit'] = 'Initial deposit cannot be negative';
        }
        
        // If deposit amount is provided, validate deposit source
        if ($initialDeposit > 0) {
            $depositSource = $input['deposit_source'] ?? null;
            
            if (empty($depositSource)) {
                $errors['deposit_source'] = 'Please select a deposit source (Cash or Transfer)';
            } elseif ($depositSource === 'transfer') {
                $sourceAccountNumber = $input['source_account_number'] ?? null;
                if (empty($sourceAccountNumber)) {
                    $errors['source_account_number'] = 'Please select a source account for transfer';
                }
            }
        }
    }

    // Return validation errors if any
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit();
    }

    // account_type now holds canonical name; do not re-append
    $accountTypeName = $input['account_type'];

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get customer_id from existing account number OR from form data
    $customerId = null;
    
    // First check if customer_id was passed from frontend
    if (!empty($input['customer_id'])) {
        $customerId = intval($input['customer_id']);
        error_log("Using customer_id from form: " . $customerId);
    }
    
    // If not provided, fetch it from the account number
    if (!$customerId && !empty($input['existing_account_number'])) {
        $existingAccountNumber = trim($input['existing_account_number']);
        
        $stmt = $db->prepare("
            SELECT ca.account_id, ca.account_number, ca.customer_id, ca.is_locked
            FROM customer_accounts ca
            WHERE ca.account_number = :account_number
            LIMIT 1
        ");
        $stmt->bindParam(':account_number', $existingAccountNumber);
        $stmt->execute();
        $existingAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAccount) {
            echo json_encode([
                'success' => false,
                'message' => 'The existing account number was not found in the system.',
                'errors' => [
                    'existing_account_number' => 'This account number does not exist.'
                ]
            ]);
            exit();
        }
        
        // Check if existing account is locked
        if ($existingAccount['is_locked']) {
            echo json_encode([
                'success' => false,
                'message' => 'The existing account is locked. Please contact customer service.',
                'errors' => [
                    'existing_account_number' => 'This account is locked and cannot be used for verification.'
                ]
            ]);
            exit();
        }
        
        // Get customer_id from the existing account
        $customerId = $existingAccount['customer_id'];
    }

    // Fetch customer information from existing account's bank_customers and application
    $existingApplication = null;
    if (!empty($input['existing_account_number'])) {
        $existingAccountNumber = trim($input['existing_account_number']);
        
        // Try to get application data, use LEFT JOIN to handle cases where application_id might be NULL
        $stmt = $db->prepare("
            SELECT 
                COALESCE(aa.first_name, bc.first_name) as first_name,
                COALESCE(aa.middle_name, bc.middle_name) as middle_name,
                COALESCE(aa.last_name, bc.last_name) as last_name,
                COALESCE(aa.date_of_birth, bc.birthday) as date_of_birth,
                COALESCE(aa.email, bc.email) as email,
                COALESCE(aa.phone_number, bc.contact_number) as phone_number,
                COALESCE(aa.street_address, bc.address) as street_address,
                aa.place_of_birth,
                aa.gender,
                aa.civil_status,
                aa.nationality,
                aa.barangay_id,
                aa.city_id,
                aa.province_id,
                aa.postal_code,
                aa.employment_status,
                aa.employer_name,
                aa.occupation,
                aa.annual_income
            FROM customer_accounts ca
            INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
            LEFT JOIN account_applications aa ON bc.application_id = aa.application_id
            WHERE ca.account_number = :account_number
            LIMIT 1
        ");
        $stmt->bindParam(':account_number', $existingAccountNumber);
        $stmt->execute();
        $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingApplication) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not retrieve customer information from existing account',
                'errors' => [
                    'existing_account_number' => 'Unable to fetch customer data from this account'
                ]
            ]);
            exit();
        }
    }

    // Handle file uploads for ID images
    $uploadDir = '../../uploads/id_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $idFrontPath = null;
    $idBackPath = null;

    // Upload front image
    if (isset($_FILES['id_front_image']) && $_FILES['id_front_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['id_front_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'id_front_' . $customerId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['id_front_image']['tmp_name'], $targetPath)) {
            $idFrontPath = 'uploads/id_images/' . $fileName;
        }
    }

    // Upload back image
    if (isset($_FILES['id_back_image']) && $_FILES['id_back_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['id_back_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'id_back_' . $customerId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['id_back_image']['tmp_name'], $targetPath)) {
            $idBackPath = 'uploads/id_images/' . $fileName;
        }
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Get employee ID from session (if available) or default to NULL
        $employeeId = $_SESSION['employee_id'] ?? null;
        
        // Generate unique application number
        $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 4, '0', STR_PAD_LEFT);
        
        // Verify application number is unique
        $stmt = $db->prepare("SELECT application_id FROM account_applications WHERE application_number = :app_num");
        $stmt->bindParam(':app_num', $applicationNumber);
        $stmt->execute();
        while ($stmt->fetch()) {
            // Generate new number if conflict
            $applicationNumber = 'APP-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 4, '0', STR_PAD_LEFT);
            $stmt->execute();
        }
        
        // Create a pending account_applications record with data from existing application + new ID info
        if ($existingApplication && $idFrontPath && $idBackPath) {
            
            $stmt = $db->prepare("
                INSERT INTO account_applications (
                    application_number,
                    application_status,
                    customer_id,
                    first_name,
                    middle_name,
                    last_name,
                    date_of_birth,
                    place_of_birth,
                    gender,
                    civil_status,
                    nationality,
                    email,
                    phone_number,
                    street_address,
                    barangay_id,
                    city_id,
                    province_id,
                    postal_code,
                    id_type,
                    id_number,
                    employment_status,
                    employer_name,
                    occupation,
                    annual_income,
                    account_type,
                    terms_accepted,
                    privacy_acknowledged,
                    submitted_at
                ) VALUES (
                    :application_number,
                    'pending',
                    :customer_id,
                    :first_name,
                    :middle_name,
                    :last_name,
                    :date_of_birth,
                    :place_of_birth,
                    :gender,
                    :civil_status,
                    :nationality,
                    :email,
                    :phone_number,
                    :street_address,
                    :barangay_id,
                    :city_id,
                    :province_id,
                    :postal_code,
                    :id_type,
                    :id_number,
                    :employment_status,
                    :employer_name,
                    :occupation,
                    :annual_income,
                    :account_type,
                    1,
                    1,
                    NOW()
                )
            ");
            
            $stmt->bindParam(':application_number', $applicationNumber);
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindValue(':first_name', $existingApplication['first_name']);
            $stmt->bindValue(':middle_name', $existingApplication['middle_name']);
            $stmt->bindValue(':last_name', $existingApplication['last_name']);
            $stmt->bindValue(':date_of_birth', $existingApplication['date_of_birth'] ?? '2000-01-01');
            $stmt->bindValue(':place_of_birth', $existingApplication['place_of_birth'] ?? 'N/A');
            $stmt->bindValue(':gender', $existingApplication['gender'] ?? 'Other');
            $stmt->bindValue(':civil_status', $existingApplication['civil_status'] ?? 'Single');
            $stmt->bindValue(':nationality', $existingApplication['nationality'] ?? 'Filipino');
            $stmt->bindValue(':email', $existingApplication['email']);
            $stmt->bindValue(':phone_number', $existingApplication['phone_number']);
            $stmt->bindValue(':street_address', $existingApplication['street_address']);
            $stmt->bindValue(':barangay_id', $existingApplication['barangay_id'] ?? null);
            $stmt->bindValue(':city_id', $existingApplication['city_id'] ?? null);
            $stmt->bindValue(':province_id', $existingApplication['province_id'] ?? null);
            $stmt->bindValue(':postal_code', $existingApplication['postal_code'] ?? null);
            $stmt->bindParam(':id_type', $input['id_type']);
            $stmt->bindParam(':id_number', $input['id_number']);
            $stmt->bindValue(':employment_status', $existingApplication['employment_status'] ?? 'Employed');
            $stmt->bindValue(':employer_name', $existingApplication['employer_name'] ?? 'N/A');
            $stmt->bindValue(':occupation', $existingApplication['occupation'] ?? 'N/A');
            $stmt->bindValue(':annual_income', $existingApplication['annual_income'] ?? 0);
            $stmt->bindParam(':account_type', $input['account_type']);
            $stmt->execute();
            
            $newApplicationId = $db->lastInsertId();
            
            // Insert ID documents into application_documents table
            $docStmt = $db->prepare("
                INSERT INTO application_documents (
                    application_id,
                    document_type,
                    file_path,
                    uploaded_at
                ) VALUES 
                (:app_id, 'id_front', :id_front, NOW()),
                (:app_id2, 'id_back', :id_back, NOW())
            ");
            $docStmt->bindParam(':app_id', $newApplicationId);
            $docStmt->bindParam(':app_id2', $newApplicationId);
            $docStmt->bindParam(':id_front', $idFrontPath);
            $docStmt->bindParam(':id_back', $idBackPath);
            $docStmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        // Return success with application number
        echo json_encode([
            'success' => true,
            'message' => 'Account application submitted successfully! Your application is pending approval.',
            'application_number' => $applicationNumber,
            'application_id' => $newApplicationId,
            'account_type' => $input['account_type'],
            'status' => 'pending'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Open account error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting your application. Please try again.',
        'debug' => $e->getMessage()
    ]);
}

