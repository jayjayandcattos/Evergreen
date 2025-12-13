<?php
/**
 * Create Final Customer Account API
 * Final step: Validate, check duplicates, and insert customer into database
 */

// Start session before any output
session_start();

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

// Set error handler to ensure JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred',
            'debug' => [
                'error' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ]
        ]);
        exit;
    }
});

try {
    // Check if this is multipart/form-data (file upload) or JSON
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // Handle multipart/form-data with files
        $data = $_POST;
        
        // Decode JSON strings back to arrays/objects
        foreach ($data as $key => $value) {
            if (is_string($value) && (substr($value, 0, 1) === '[' || substr($value, 0, 1) === '{')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                    error_log("Decoded JSON for key '$key': " . print_r($decoded, true));
                }
            }
        }
        
        error_log("Using data from multipart POST");
        error_log("POST data: " . print_r($data, true));
        error_log("FILES data: " . print_r($_FILES, true));
        error_log("🔍 id_type from POST: " . ($data['id_type'] ?? 'NOT SET'));
        error_log("🔍 id_number from POST: " . ($data['id_number'] ?? 'NOT SET'));
    } else {
        // Get JSON input from POST body
        $input = file_get_contents('php://input');
        $postData = json_decode($input, true);
        
        // Use POST data if available, otherwise fallback to session
        if ($postData && !empty($postData)) {
            $data = $postData;
            error_log("Using data from JSON POST body");
            
            // Also update session for consistency
            $_SESSION['customer_onboarding']['data'] = array_merge(
                $_SESSION['customer_onboarding']['data'] ?? [],
                $data
            );
        } elseif (isset($_SESSION['customer_onboarding']) && isset($_SESSION['customer_onboarding']['data'])) {
            $data = $_SESSION['customer_onboarding']['data'];
            error_log("Using data from session");
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Session expired. Please start over.'
            ]);
            exit();
        }
    }
    
    error_log("Final data being processed: " . json_encode($data));
    $errors = [];

    // Helper function to extract string from potential array/object
    function extractString($value) {
        if (empty($value)) {
            return '';
        }
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value) && !empty($value)) {
            // If it's an array, get the first element
            $first = $value[0];
            // If the first element is still an array or object, try to get a string value
            if (is_array($first)) {
                return trim($first['value'] ?? $first['email'] ?? $first['full_number'] ?? $first['number'] ?? $first[0] ?? '');
            } elseif (is_object($first)) {
                return trim($first->value ?? $first->email ?? $first->full_number ?? $first->number ?? '');
            }
            return trim((string)$first);
        } elseif (is_object($value)) {
            return trim($value->value ?? $value->email ?? $value->full_number ?? $value->number ?? '');
        }
        return trim((string)$value);
    }

    // Map field names (handle variations from different steps)
    $mappedData = [
        'first_name' => $data['first_name'] ?? null,
        'middle_name' => $data['middle_name'] ?? '',
        'last_name' => $data['last_name'] ?? null,
        'birth_date' => $data['date_of_birth'] ?? $data['birth_date'] ?? null,
        'birth_place' => $data['place_of_birth'] ?? $data['birth_place'] ?? null,
        'gender' => $data['gender'] ?? null,
        'civil_status' => $data['marital_status'] ?? $data['civil_status'] ?? null,
        'nationality' => $data['nationality'] ?? null,
        'email' => extractString($data['email'] ?? $data['emails'] ?? ''),
        'mobile_number' => extractString($data['mobile_number'] ?? $data['phones'] ?? ''),
        'address_line' => $data['address_line'] ?? $data['street'] ?? null,
        'province_id' => $data['province_id'] ?? null,
        'city_id' => $data['city_id'] ?? null,
        'barangay_id' => $data['barangay_id'] ?? null,
        'postal_code' => $data['postal_code'] ?? null,
        'country' => $data['country'] ?? $data['country_name'] ?? 'Philippines',
        'employment_status' => $data['employment_status'] ?? null,
        'occupation' => $data['job_title'] ?? $data['occupation'] ?? null,
        'employer_name' => $data['employer_name'] ?? '',
        'annual_income' => $data['annual_income'] ?? null,
        'source_of_funds' => $data['source_of_funds'] ?? null,
        'password_hash' => $data['password_hash'] ?? null, // NULL for walk-in registrations
        'account_type' => $data['account_type'] ?? 'Savings', // Get account type from step 1
        'id_type' => $data['id_type'] ?? null, // From step 2
        'id_number' => $data['id_number'] ?? null, // From step 2
        'created_by_employee_id' => $_SESSION['employee_id'] ?? null // Employee who created this
    ];
    
    // Debug logging
    error_log("Raw data - email: " . json_encode($data['email'] ?? $data['emails'] ?? 'NULL'));
    error_log("Raw data - mobile: " . json_encode($data['mobile_number'] ?? $data['phones'] ?? 'NULL'));
    error_log("Mapped Data:");
    error_log("  - email: '" . ($mappedData['email'] ?? 'NULL') . "'");
    error_log("  - mobile_number: '" . ($mappedData['mobile_number'] ?? 'NULL') . "'");
    error_log("  - employment_status: " . ($mappedData['employment_status'] ?? 'NULL'));
    error_log("  - occupation: " . ($mappedData['occupation'] ?? 'NULL'));
    error_log("  - source_of_funds: " . ($mappedData['source_of_funds'] ?? 'NULL'));
    error_log("  - id_type: '" . ($mappedData['id_type'] ?? 'NULL') . "'");
    error_log("  - id_number: '" . ($mappedData['id_number'] ?? 'NULL') . "'");

    // Validate required fields
    // Note: Either email OR mobile_number is required (at least one must be verified)
    // password_hash is NOT required for walk-in registrations (will be NULL until approval)
    $requiredFields = [
        'first_name', 'last_name', 'birth_date', 'birth_place',
        'gender', 'civil_status', 'nationality',
        'address_line', 'province_id', 'city_id', 'barangay_id', 'postal_code', 'country',
        'occupation'
    ];

    foreach ($requiredFields as $field) {
        if (empty($mappedData[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }

    // Check that at least one contact method (email OR phone) is provided
    $hasEmail = !empty($mappedData['email']);
    $hasPhone = !empty($mappedData['mobile_number']);
    
    if (!$hasEmail && !$hasPhone) {
        $errors['contact'] = "At least one contact method (email or phone number) is required";
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        exit();
    }

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Check for duplicate email (only if email is provided)
    if ($hasEmail) {
        $stmt = $db->prepare("SELECT customer_id FROM emails WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $mappedData['email']);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => false,
                'message' => 'This email address is already registered',
                'errors' => ['email' => 'Email already exists']
            ]);
            exit();
        }
    }

    // Check for duplicate phone (only if phone is provided)
    if ($hasPhone) {
        $stmt = $db->prepare("SELECT customer_id FROM phones WHERE phone_number = :phone LIMIT 1");
        $stmt->bindParam(':phone', $mappedData['mobile_number']);
        $stmt->execute();
        
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => false,
                'message' => 'This phone number is already registered',
                'errors' => ['mobile_number' => 'Phone number already exists']
            ]);
            exit();
        }
    }

    // Note: Username check removed - customers will use email for login (per unified schema)
    // Email uniqueness is already checked above

    // Get account type from mapped data (selected in step 1)
    $accountTypeBase = $mappedData['account_type'] ?? 'Savings';
    
    // Format birth_date to MySQL date format (YYYY-MM-DD)
    $birthDate = date('Y-m-d', strtotime($mappedData['birth_date']));

    // Begin transaction
    $db->beginTransaction();

    try {
        
        // Generate unique application number for account_applications table
        $application_number = generateApplicationNumber($db);
        error_log("Generated application number: " . $application_number);
        
        // Determine contact information (at least one is required)
        $customerEmail = $hasEmail ? $mappedData['email'] : null;
        $customerPhone = $hasPhone ? $mappedData['mobile_number'] : null;

        // Handle ID image uploads BEFORE creating records (need customer_id first but we'll use temp name)
        $idFrontPath = null;
        $idBackPath = null;
        $uploadDir = '../../uploads/id_images/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Step 1: Create comprehensive account_applications record FIRST
        // This stores all customer data for the pending application
        
        $stmt = $db->prepare("
            INSERT INTO account_applications (
                application_number, application_status,
                first_name, middle_name, last_name, 
                date_of_birth, place_of_birth, gender, civil_status, nationality,
                email, phone_number,
                street_address, barangay_id, city_id, province_id, postal_code,
                id_type, id_number,
                employment_status, employer_name, occupation, annual_income,
                account_type, terms_accepted,
                submitted_at
            ) VALUES (
                :application_number, 'pending',
                :first_name, :middle_name, :last_name,
                :date_of_birth, :place_of_birth, :gender, :civil_status, :nationality,
                :email, :phone_number,
                :street_address, :barangay_id, :city_id, :province_id, :postal_code,
                :id_type, :id_number,
                :employment_status, :employer_name, :occupation, :annual_income,
                :account_type, 1,
                NOW()
            )
        ");
        
        $stmt->bindParam(':application_number', $application_number);
        $stmt->bindParam(':first_name', $mappedData['first_name']);
        $stmt->bindParam(':middle_name', $mappedData['middle_name']);
        $stmt->bindParam(':last_name', $mappedData['last_name']);
        $stmt->bindParam(':date_of_birth', $birthDate);
        $stmt->bindParam(':place_of_birth', $mappedData['birth_place']);
        $stmt->bindParam(':gender', $mappedData['gender']);
        $stmt->bindParam(':civil_status', $mappedData['civil_status']);
        $stmt->bindParam(':nationality', $mappedData['nationality']);
        $stmt->bindParam(':email', $customerEmail);
        $stmt->bindParam(':phone_number', $customerPhone);
        $stmt->bindParam(':street_address', $mappedData['address_line']);
        $stmt->bindParam(':barangay_id', $mappedData['barangay_id']);
        $stmt->bindParam(':city_id', $mappedData['city_id']);
        $stmt->bindParam(':province_id', $mappedData['province_id']);
        $stmt->bindParam(':postal_code', $mappedData['postal_code']);
        $stmt->bindParam(':id_type', $mappedData['id_type']);
        $stmt->bindParam(':id_number', $mappedData['id_number']);
        $stmt->bindParam(':employment_status', $mappedData['employment_status']);
        $stmt->bindParam(':employer_name', $mappedData['employer_name']);
        $stmt->bindParam(':occupation', $mappedData['occupation']);
        $stmt->bindParam(':annual_income', $mappedData['annual_income']);
        $stmt->bindParam(':account_type', $accountTypeBase);
        $stmt->execute();

        $applicationId = $db->lastInsertId();
        error_log("Created account_applications record with ID: " . $applicationId);
        
        // Step 2: Create bank_customers record linked to the application
        // This creates the customer record but WITHOUT login credentials (pending approval)
        // After approval, password can be set up
        
        // Build address string for bank_customers.address field
        $fullAddress = trim($mappedData['address_line'] ?? '');
        
        // Get city and province names for city_province field
        $cityProvince = '';
        if (!empty($mappedData['city_id']) && !empty($mappedData['province_id'])) {
            try {
                $locStmt = $db->prepare("
                    SELECT c.city_name, p.province_name 
                    FROM cities c 
                    JOIN provinces p ON c.province_id = p.province_id 
                    WHERE c.city_id = :city_id AND p.province_id = :province_id
                ");
                $locStmt->bindParam(':city_id', $mappedData['city_id']);
                $locStmt->bindParam(':province_id', $mappedData['province_id']);
                $locStmt->execute();
                $location = $locStmt->fetch(PDO::FETCH_ASSOC);
                if ($location) {
                    $cityProvince = trim($location['city_name'] . ', ' . $location['province_name']);
                    error_log("City/Province resolved: " . $cityProvince);
                }
            } catch (PDOException $e) {
                error_log("Error fetching city/province names: " . $e->getMessage());
            }
        }
        
        $stmt = $db->prepare("
            INSERT INTO bank_customers (
                application_id,
                last_name,
                first_name,
                middle_name,
                address,
                city_province,
                email,
                contact_number,
                birthday,
                password_hash,
                is_verified,
                created_at
            ) VALUES (
                :application_id,
                :last_name,
                :first_name,
                :middle_name,
                :address,
                :city_province,
                :email,
                :contact_number,
                :birthday,
                NULL,
                0,
                NOW()
            )
        ");
        
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->bindParam(':last_name', $mappedData['last_name']);
        $stmt->bindParam(':first_name', $mappedData['first_name']);
        $stmt->bindParam(':middle_name', $mappedData['middle_name']);
        $stmt->bindParam(':address', $fullAddress);
        $stmt->bindParam(':city_province', $cityProvince);
        $stmt->bindParam(':email', $customerEmail);
        $stmt->bindParam(':contact_number', $customerPhone);
        $stmt->bindParam(':birthday', $birthDate);
        $stmt->execute();

        $customerId = $db->lastInsertId();
        error_log("Created bank_customers record with ID: " . $customerId);
        
        // Step 3: Now handle ID image uploads with proper customer_id
        // Upload front image
        if (isset($_FILES['id_front_image']) && $_FILES['id_front_image']['error'] === UPLOAD_ERR_OK) {
            $frontFile = $_FILES['id_front_image'];
            $frontExt = pathinfo($frontFile['name'], PATHINFO_EXTENSION);
            $frontFilename = 'id_front_' . $customerId . '_' . time() . '.' . $frontExt;
            $frontPath = $uploadDir . $frontFilename;
            
            if (move_uploaded_file($frontFile['tmp_name'], $frontPath)) {
                $idFrontPath = 'uploads/id_images/' . $frontFilename;
                error_log("Front ID image uploaded: " . $idFrontPath);
                
                // Store in application_documents table
                $docStmt = $db->prepare("
                    INSERT INTO application_documents 
                    (application_id, document_type, file_name, file_path, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $docStmt->execute([
                    $applicationId,
                    'id_front',
                    $frontFile['name'],
                    $idFrontPath,
                    $frontFile['size'],
                    $frontFile['type']
                ]);
                error_log("Stored front ID in application_documents table");
            }
        }
        
        // Upload back image
        if (isset($_FILES['id_back_image']) && $_FILES['id_back_image']['error'] === UPLOAD_ERR_OK) {
            $backFile = $_FILES['id_back_image'];
            $backExt = pathinfo($backFile['name'], PATHINFO_EXTENSION);
            $backFilename = 'id_back_' . $customerId . '_' . time() . '.' . $backExt;
            $backPath = $uploadDir . $backFilename;
            
            if (move_uploaded_file($backFile['tmp_name'], $backPath)) {
                $idBackPath = 'uploads/id_images/' . $backFilename;
                error_log("Back ID image uploaded: " . $idBackPath);
                
                // Store in application_documents table
                $docStmt = $db->prepare("
                    INSERT INTO application_documents 
                    (application_id, document_type, file_name, file_path, file_size, mime_type) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $docStmt->execute([
                    $applicationId,
                    'id_back',
                    $backFile['name'],
                    $idBackPath,
                    $backFile['size'],
                    $backFile['type']
                ]);
                error_log("Stored back ID in application_documents table");
            }
        }
        
        // Step 4: Link customer_id to account_applications (bidirectional linking)
        $updateStmt = $db->prepare("
            UPDATE account_applications 
            SET customer_id = :customer_id
            WHERE application_id = :application_id
        ");
        $updateStmt->bindParam(':customer_id', $customerId);
        $updateStmt->bindParam(':application_id', $applicationId);
        $updateStmt->execute();
        error_log("Linked customer_id in account_applications");

        // Step 5: Link the application to the bank_customers record (bidirectional linking complete)
        $stmt = $db->prepare("UPDATE bank_customers SET application_id = :application_id WHERE customer_id = :customer_id");
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        error_log("Linked bank_customers to account_applications");

        // NOTE: emails, phones, addresses, customer_profiles, and customer_accounts are NOT created here
        // They will be created after the application is approved by an employee
        // This is a walk-in registration - customer gets minimal login record and application pending approval

        // Commit transaction
        $db->commit();

        // Clear onboarding session
        unset($_SESSION['customer_onboarding']);

        // Return success with application number (NOT account number yet)
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully. Your application is pending approval.',
            'account_number' => $application_number, // Show application number instead
            'customer_id' => $customerId,
            'application_id' => $applicationId,
            'status' => 'pending'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error in create-final.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Exception $e) {
    error_log("Create final account error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your account. Please try again.',
        'debug' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Generate unique referral code
 * @param PDO $db Database connection
 * @return string Unique referral code
 */
function generateUniqueReferralCode($db) {
    do {
        // Generate a 6-character code (3 letters + 3 numbers)
        $code = '';
        for ($i = 0; $i < 3; $i++) {
            $code .= chr(rand(65, 90)); // A-Z
        }
        for ($i = 0; $i < 3; $i++) {
            $code .= rand(0, 9); // 0-9
        }
        
        // Check if code already exists
        $stmt = $db->prepare("SELECT customer_id FROM bank_customers WHERE referral_code = :code LIMIT 1");
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        $exists = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        $stmt->closeCursor();
        
    } while ($exists);
    
    return $code;
}

/**
 * Generate unique application number
 * Format: APP-YYYYMMDD-XXXX (e.g., APP-20251207-0001)
 * @param PDO $db Database connection
 * @return string Unique application number
 */
function generateApplicationNumber($db) {
    $date = date('Ymd');
    $prefix = 'APP-' . $date . '-';
    
    $maxAttempts = 10;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        // Get the highest number for today
        $stmt = $db->prepare("
            SELECT application_number 
            FROM account_applications 
            WHERE application_number LIKE :prefix 
            ORDER BY application_number DESC 
            LIMIT 1
        ");
        $likePrefix = $prefix . '%';
        $stmt->bindParam(':prefix', $likePrefix);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Extract the sequence number and increment
            $lastNumber = substr($result['application_number'], -4);
            $newNumber = str_pad((int)$lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // First application today
            $newNumber = '0001';
        }
        
        $applicationNumber = $prefix . $newNumber;
        
        // Check if this number already exists (for concurrent requests)
        $checkStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM account_applications 
            WHERE application_number = :app_number
        ");
        $checkStmt->bindParam(':app_number', $applicationNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult['count'] == 0) {
            // This number is available
            return $applicationNumber;
        }
        
        // Number already exists, increment and try again
        $attempt++;
        error_log("Application number {$applicationNumber} already exists, retrying (attempt {$attempt})");
    }
    
    // If we couldn't find a unique number after max attempts, use a random suffix
    $randomSuffix = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $randomSuffix;
}

/**
 * Get country code ID from phone number
 */
function getCountryCodeId($db, $phoneNumber) {
    // Default to Philippines (+63) if not found
    $defaultCountryCodeId = 1;
    
    // Extract country code from phone number (assumes +XX format)
    $pattern = '/^\+(\d{1,3})/';
    if (preg_match($pattern, $phoneNumber, $matches)) {
        $phoneCode = '+' . $matches[1];
        
        $stmt = $db->prepare("SELECT country_code_id FROM country_codes WHERE phone_code = :phone_code LIMIT 1");
        $stmt->bindParam(':phone_code', $phoneCode);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['country_code_id'];
        }
    }
    
    return $defaultCountryCodeId;
}
?>
