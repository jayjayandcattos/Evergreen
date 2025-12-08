<?php
session_start([
   'cookie_httponly' => true,
   'cookie_secure' => isset($_SERVER['HTTPS']),
   'use_strict_mode' => true
]);

header('Content-Type: application/json');

// Database connection
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if this is a FormData submission (with file) or JSON
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'multipart/form-data') !== false || !empty($_POST)) {
        // FormData submission - get data from $_POST
        $data = $_POST;
        
        // Parse JSON arrays
        if (isset($data['selectedCards'])) {
            $data['selectedCards'] = json_decode($data['selectedCards'], true) ?: [];
        }
        if (isset($data['additionalServices'])) {
            $data['additionalServices'] = json_decode($data['additionalServices'], true) ?: [];
        }
    } else {
        // JSON submission
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true);
    }
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        exit;
    }
    
    // Handle file upload
    $idDocumentPath = null;
    if (isset($_FILES['id_document']) && $_FILES['id_document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/id_documents/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $fileType = $_FILES['id_document']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
            exit;
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['id_document']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 5MB.']);
            exit;
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['id_document']['name'], PATHINFO_EXTENSION);
        $uniqueName = 'id_' . uniqid() . '_' . time() . '.' . $extension;
        $targetPath = $uploadDir . $uniqueName;
        
        if (move_uploaded_file($_FILES['id_document']['tmp_name'], $targetPath)) {
            $idDocumentPath = $targetPath;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload ID document.']);
            exit;
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate unique application number
        $applicationNumber = generateApplicationNumber($conn);
        
        // Extract and sanitize data
        $firstName = $conn->real_escape_string(trim($data['firstName']));
        $lastName = $conn->real_escape_string(trim($data['lastName']));
        $email = $conn->real_escape_string(trim($data['email']));
        $phoneNumber = $conn->real_escape_string(trim($data['phoneNumber']));
        $dateOfBirth = $conn->real_escape_string(trim($data['dateOfBirth']));
        $streetAddress = $conn->real_escape_string(trim($data['streetAddress']));
        $barangay = $conn->real_escape_string(trim($data['barangay']));
        $city = $conn->real_escape_string(trim($data['city']));
        $state = $conn->real_escape_string(trim($data['state']));
        $zipCode = $conn->real_escape_string(trim($data['zipCode']));
        $ssn = $conn->real_escape_string(trim($data['socialSecurityNumber']));
        $idType = $conn->real_escape_string(trim($data['idType']));
        $idNumber = $conn->real_escape_string(trim($data['idNumber']));
        $employmentStatus = $conn->real_escape_string(trim($data['employmentStatus']));
        $employerName = $conn->real_escape_string(trim($data['employerName']));
        $jobTitle = $conn->real_escape_string(trim($data['jobTitle']));
        $annualIncome = floatval($data['annualIncome']);
        $accountType = $conn->real_escape_string(trim($data['accountType']));
        
        // Selected cards as comma-separated string
        $selectedCards = isset($data['selectedCards']) && is_array($data['selectedCards']) 
            ? implode(',', $data['selectedCards']) 
            : '';
        
        // Additional services as comma-separated string
        $additionalServices = isset($data['additionalServices']) && is_array($data['additionalServices']) 
            ? implode(',', $data['additionalServices']) 
            : '';
        
        // Terms and agreements
        $termsAccepted = isset($data['termsAccepted']) && $data['termsAccepted'] ? 1 : 0;
        $privacyAcknowledged = isset($data['privacyAcknowledged']) && $data['privacyAcknowledged'] ? 1 : 0;
        $marketingConsent = isset($data['marketingConsent']) && $data['marketingConsent'] ? 1 : 0;
        
        // Insert into account_applications table
        $sql = "INSERT INTO account_applications (
            application_number, application_status,
            first_name, last_name, email, phone_number, date_of_birth,
            street_address, barangay, city, state, zip_code,
            ssn, id_type, id_number, id_document_path,
            employment_status, employer_name, job_title, annual_income,
            account_type, selected_cards, additional_services,
            terms_accepted, privacy_acknowledged, marketing_consent,
            submitted_at
        ) VALUES (
            ?, 'pending',
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssssssssssssssdsssiii",
            $applicationNumber,
            $firstName, $lastName, $email, $phoneNumber, $dateOfBirth,
            $streetAddress, $barangay, $city, $state, $zipCode,
            $ssn, $idType, $idNumber, $idDocumentPath,
            $employmentStatus, $employerName, $jobTitle, $annualIncome,
            $accountType, $selectedCards, $additionalServices,
            $termsAccepted, $privacyAcknowledged, $marketingConsent
        );
        
        $stmt->execute();
        $applicationId = $stmt->insert_id;
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully',
            'application_number' => $applicationNumber,
            'application_id' => $applicationId
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        error_log("Application submission error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Error processing application. Please try again.'
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();

// ========================================
// HELPER FUNCTIONS
// ========================================

function generateApplicationNumber($conn) {
    $year = date('Y');
    $prefix = 'APP-' . $year . '-';
    
    // Get the last application number for this year
    $sql = "SELECT application_number FROM account_applications 
            WHERE application_number LIKE ? 
            ORDER BY application_id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastNumber = intval(substr($row['application_number'], -5));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    $stmt->close();
    
    return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
}
?>
