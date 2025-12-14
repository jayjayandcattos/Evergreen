<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1); // Log errors to file

session_start([
   'cookie_httponly' => true,
   'cookie_secure' => isset($_SERVER['HTTPS']),
   'use_strict_mode' => true
]);

header('Content-Type: application/json');

// Set error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred',
            'debug' => $errstr . ' in ' . $errfile . ' on line ' . $errline
        ]);
        exit;
    }
});

// Database connection
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "BankingDB";

try {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
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
        
        // Log received data for debugging
        error_log("Received data: " . print_r($data, true));
        error_log("FILES: " . print_r($_FILES, true));
        
    } catch (Exception $e) {
        error_log("Error in data processing: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Generate unique application number
        $applicationNumber = generateApplicationNumber($conn);
        
        // Validate required fields
        $requiredFields = ['firstName', 'lastName', 'email', 'phoneNumber', 'dateOfBirth', 'streetAddress', 'zipCode', 'idType', 'idNumber', 'employmentStatus', 'jobTitle', 'annualIncome', 'accountType'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new Exception("Missing required fields: " . implode(', ', $missingFields));
        }
        
        // Extract and sanitize data (matching create-final.php structure)
        $firstName = $conn->real_escape_string(trim($data['firstName']));
        $middleName = isset($data['middleName']) ? $conn->real_escape_string(trim($data['middleName'])) : '';
        $lastName = $conn->real_escape_string(trim($data['lastName']));
        $email = $conn->real_escape_string(trim($data['email']));
        $phoneNumber = $conn->real_escape_string(trim($data['phoneNumber']));
        $dateOfBirth = date('Y-m-d', strtotime($data['dateOfBirth'])); // Format to MySQL date
        
        // Get profile data from customer (gender, nationality, place_of_birth, civil_status, source_of_funds)
        $gender = isset($data['gender']) ? $conn->real_escape_string(trim($data['gender'])) : null;
        $nationality = isset($data['nationality']) ? $conn->real_escape_string(trim($data['nationality'])) : null;
        $placeOfBirth = isset($data['placeOfBirth']) ? $conn->real_escape_string(trim($data['placeOfBirth'])) : null;
        $civilStatus = isset($data['civilStatus']) ? $conn->real_escape_string(trim($data['civilStatus'])) : null;
        $sourceOfFunds = isset($data['sourceOfFunds']) ? $conn->real_escape_string(trim($data['sourceOfFunds'])) : null;
        
        $streetAddress = $conn->real_escape_string(trim($data['streetAddress']));
        
        // Get IDs from session (these should be IDs, not names)
        $customer_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        
        // Get location IDs from the customer's existing address in database
        $barangayId = null;
        $cityId = null;
        $provinceId = null;
        
        if ($customer_id) {
            $locSql = "SELECT province_id, city_id, barangay_id 
                       FROM addresses 
                       WHERE customer_id = ? AND is_primary = 1 
                       LIMIT 1";
            $locStmt = $conn->prepare($locSql);
            $locStmt->bind_param("i", $customer_id);
            $locStmt->execute();
            $locResult = $locStmt->get_result();
            
            if ($locResult->num_rows > 0) {
                $location = $locResult->fetch_assoc();
                $provinceId = $location['province_id'];
                $cityId = $location['city_id'];
                $barangayId = $location['barangay_id'];
            }
            $locStmt->close();
        }
        
        $postalCode = $conn->real_escape_string(trim($data['zipCode']));
        
        $idType = $conn->real_escape_string(trim($data['idType']));
        $idNumber = $conn->real_escape_string(trim($data['idNumber']));
        $employmentStatus = $conn->real_escape_string(trim($data['employmentStatus']));
        $employerName = isset($data['employerName']) ? $conn->real_escape_string(trim($data['employerName'])) : '';
        $occupation = $conn->real_escape_string(trim($data['jobTitle']));
        $annualIncome = floatval($data['annualIncome']);
        $accountType = $conn->real_escape_string(trim($data['accountType']));
        
        // Terms accepted
        $termsAccepted = 1; // Always 1 if they submitted the form
        
        // Handle ID image uploads (matching create-final.php structure)
        // Use the same upload directory as Basic-operation
        $uploadDir = '../Basic-operation/uploads/id_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Insert into account_applications table (matching create-final.php structure)
        $sql = "INSERT INTO account_applications (
            application_number, application_status, customer_id,
            first_name, middle_name, last_name, 
            date_of_birth, place_of_birth, gender, civil_status, nationality,
            email, phone_number,
            street_address, barangay_id, city_id, province_id, postal_code,
            id_type, id_number,
            employment_status, employer_name, occupation, annual_income, source_of_funds,
            account_type, terms_accepted,
            submitted_at
        ) VALUES (
            ?, 'pending', ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            NOW()
        )";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param(
            "sissssssssssiiiissssssdssi",
            $applicationNumber,
            $customer_id,
            $firstName, $middleName, $lastName,
            $dateOfBirth, $placeOfBirth, $gender, $civilStatus, $nationality,
            $email, $phoneNumber,
            $streetAddress, $barangayId, $cityId, $provinceId, $postalCode,
            $idType, $idNumber,
            $employmentStatus, $employerName, $occupation, $annualIncome, $sourceOfFunds,
            $accountType, $termsAccepted
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $applicationId = $stmt->insert_id;
        $stmt->close();
        
        // Handle ID image uploads AFTER getting application_id (matching account-opening.js structure)
        // Use the same upload directory as Basic-operation
        $uploadDir = '../Basic-operation/uploads/id_images/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Upload front image
        if (isset($_FILES['id_front_image']) && $_FILES['id_front_image']['error'] === UPLOAD_ERR_OK) {
            $frontFile = $_FILES['id_front_image'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!in_array($frontFile['type'], $allowedTypes)) {
                throw new Exception('Invalid front image file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($frontFile['size'] > $maxSize) {
                throw new Exception('Front image file is too large. Maximum size is 5MB.');
            }
            
            $frontExt = pathinfo($frontFile['name'], PATHINFO_EXTENSION);
            $frontFilename = 'id_front_' . $applicationId . '_' . time() . '.' . $frontExt;
            $frontPath = $uploadDir . $frontFilename;
            
            if (move_uploaded_file($frontFile['tmp_name'], $frontPath)) {
                $idFrontPath = 'uploads/id_images/' . $frontFilename;
                
                // Assign file data to variables for bind_param
                $frontFileName = $frontFile['name'];
                $frontFileSize = $frontFile['size'];
                $frontFileType = $frontFile['type'];
                
                // Store in application_documents table
                $docSql = "INSERT INTO application_documents 
                    (application_id, document_type, file_name, file_path, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?, ?)";
                $docStmt = $conn->prepare($docSql);
                $docType = 'id_front';
                $docStmt->bind_param(
                    "isssis",
                    $applicationId,
                    $docType,
                    $frontFileName,
                    $idFrontPath,
                    $frontFileSize,
                    $frontFileType
                );
                $docStmt->execute();
                $docStmt->close();
                
                error_log("Front ID image uploaded: " . $idFrontPath);
            }
        }
        
        // Upload back image
        if (isset($_FILES['id_back_image']) && $_FILES['id_back_image']['error'] === UPLOAD_ERR_OK) {
            $backFile = $_FILES['id_back_image'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!in_array($backFile['type'], $allowedTypes)) {
                throw new Exception('Invalid back image file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024;
            if ($backFile['size'] > $maxSize) {
                throw new Exception('Back image file is too large. Maximum size is 5MB.');
            }
            
            $backExt = pathinfo($backFile['name'], PATHINFO_EXTENSION);
            $backFilename = 'id_back_' . $applicationId . '_' . time() . '.' . $backExt;
            $backPath = $uploadDir . $backFilename;
            
            if (move_uploaded_file($backFile['tmp_name'], $backPath)) {
                $idBackPath = 'uploads/id_images/' . $backFilename;
                
                // Assign file data to variables for bind_param
                $backFileName = $backFile['name'];
                $backFileSize = $backFile['size'];
                $backFileType = $backFile['type'];
                
                // Store in application_documents table
                $docSql = "INSERT INTO application_documents 
                    (application_id, document_type, file_name, file_path, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?, ?)";
                $docStmt = $conn->prepare($docSql);
                $docType = 'id_back';
                $docStmt->bind_param(
                    "isssis",
                    $applicationId,
                    $docType,
                    $backFileName,
                    $idBackPath,
                    $backFileSize,
                    $backFileType
                );
                $docStmt->execute();
                $docStmt->close();
                
                error_log("Back ID image uploaded: " . $idBackPath);
            }
        }
        
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
        if ($conn) {
            $conn->rollback();
        }
        
        error_log("Application submission error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        echo json_encode([
            'success' => false,
            'message' => 'Error processing application: ' . $e->getMessage(),
            'debug' => [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

if (isset($conn)) {
    $conn->close();
}

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
