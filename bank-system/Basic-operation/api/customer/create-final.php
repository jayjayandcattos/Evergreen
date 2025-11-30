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
    // Check if session data exists
    if (!isset($_SESSION['customer_onboarding']) || !isset($_SESSION['customer_onboarding']['data'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please start over.'
        ]);
        exit();
    }

    $data = $_SESSION['customer_onboarding']['data'];
    $errors = [];

    // Helper function to extract string from potential array/object
    function extractString($value) {
        if (is_array($value) && !empty($value)) {
            // If it's an array, get the first element
            $first = $value[0];
            // If the first element is still an array or object, try to get a string value
            if (is_array($first)) {
                return $first['value'] ?? $first['email'] ?? $first['phone'] ?? $first[0] ?? '';
            } elseif (is_object($first)) {
                return $first->value ?? $first->email ?? $first->phone ?? '';
            }
            return (string)$first;
        } elseif (is_object($value)) {
            return $value->value ?? $value->email ?? $value->phone ?? '';
        }
        return $value;
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
        'email' => extractString($data['emails'] ?? $data['email'] ?? null),
        'mobile_number' => extractString($data['phones'] ?? $data['mobile_number'] ?? null),
        'address_line' => $data['address_line'] ?? $data['street'] ?? null,
        'province_id' => $data['province_id'] ?? null,
        'city_id' => $data['city_id'] ?? null,
        'barangay_id' => $data['barangay_id'] ?? null,
        'postal_code' => $data['postal_code'] ?? null,
        'country' => $data['country'] ?? $data['country_name'] ?? 'Philippines',
        'occupation' => $data['employment_status'] ?? $data['occupation'] ?? null,
        'employer_name' => $data['employer_name'] ?? '',
        'annual_income' => $data['annual_income'] ?? $data['source_of_funds'] ?? null,
        'password_hash' => $data['password_hash'] ?? null,
        'account_type' => $data['account_type'] ?? 'Savings' // Get account type from step 1
    ];

    // Validate required fields
    // Note: Either email OR mobile_number is required (at least one must be verified)
    $requiredFields = [
        'first_name', 'last_name', 'birth_date', 'birth_place',
        'gender', 'civil_status', 'nationality',
        'address_line', 'province_id', 'city_id', 'barangay_id', 'postal_code', 'country',
        'occupation', 'password_hash'
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
    $accountTypeName = $accountTypeBase . ' Account'; // Append " Account" to match database format
    
    // Generate unique account number based on account type
    $accountNumber = generateAccountNumber($db, $accountTypeName);

    // Get or create gender ID
    $genderId = getOrCreateGenderId($db, $mappedData['gender']);

    // Format birth_date to MySQL date format (YYYY-MM-DD)
    $birthDate = date('Y-m-d', strtotime($mappedData['birth_date']));
    
    // Note: phones table in unified schema doesn't have country_code_id column
    // Phone number should already include country code from step 2

    // Begin transaction
    $db->beginTransaction();

    try {
        // Get bank_id from session (generated in step 2 when sending verification code)
        $bank_id = $data['bank_id'] ?? null;
        if (!$bank_id && isset($_SESSION['customer_onboarding']['data']['bank_id'])) {
            $bank_id = $_SESSION['customer_onboarding']['data']['bank_id'];
        }
        
        // If bank_id still not found, generate a new one (fallback)
        if (!$bank_id) {
            $bank_id = sprintf("%04d", mt_rand(0, 9999));
            error_log("Warning: Bank ID not found in session, generated new one: " . $bank_id);
        }
        
        error_log("Using Bank ID for customer: " . $bank_id);
        
        // Generate unique referral code
        $referral_code = generateUniqueReferralCode($db);
        error_log("Generated referral code: " . $referral_code);
        
        // Insert into bank_customers table (unified schema)
        // Note: email is optional (can be NULL if only phone verification was used)
        $customerEmail = $hasEmail ? $mappedData['email'] : null;
        $customerPhone = $hasPhone ? $mappedData['mobile_number'] : null;
        
        // Get city and province names for address field
        $cityName = '';
        $provinceName = '';
        
        if ($mappedData['city_id']) {
            $stmt = $db->prepare("SELECT city_name FROM cities WHERE city_id = :city_id");
            $stmt->bindParam(':city_id', $mappedData['city_id']);
            $stmt->execute();
            $cityResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $cityName = $cityResult['city_name'] ?? '';
        }
        
        if ($mappedData['province_id']) {
            $stmt = $db->prepare("SELECT province_name FROM provinces WHERE province_id = :province_id");
            $stmt->bindParam(':province_id', $mappedData['province_id']);
            $stmt->execute();
            $provinceResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $provinceName = $provinceResult['province_name'] ?? '';
        }
        
        // Build full address string
        $fullAddress = $mappedData['address_line'];
        $cityProvince = trim($cityName . ', ' . $provinceName, ', ');
        
        $stmt = $db->prepare("
            INSERT INTO bank_customers (
                first_name, middle_name, last_name, email, password_hash, 
                address, city_province, contact_number, birthday, bank_id, referral_code, created_at
            ) VALUES (
                :first_name, :middle_name, :last_name, :email, :password_hash,
                :address, :city_province, :contact_number, :birthday, :bank_id, :referral_code, NOW()
            )
        ");
        
        $stmt->bindParam(':first_name', $mappedData['first_name']);
        $stmt->bindParam(':middle_name', $mappedData['middle_name']);
        $stmt->bindParam(':last_name', $mappedData['last_name']);
        $stmt->bindParam(':email', $customerEmail);
        $stmt->bindParam(':password_hash', $mappedData['password_hash']);
        $stmt->bindParam(':address', $fullAddress);
        $stmt->bindParam(':city_province', $cityProvince);
        $stmt->bindParam(':contact_number', $customerPhone);
        $stmt->bindParam(':birthday', $mappedData['birth_date']);
        $stmt->bindParam(':bank_id', $bank_id);
        $stmt->bindParam(':referral_code', $referral_code);
        $stmt->execute();

        $customerId = $db->lastInsertId();

        // Insert into customer_profiles table (unified schema)
        // Note: customer_profiles structure differs from old schema
        $stmt = $db->prepare("
            INSERT INTO customer_profiles (
                customer_id, date_of_birth, gender_id, marital_status, 
                nationality, occupation, company, income_range, profile_created_at
            ) VALUES (
                :customer_id, :birth_date, :gender_id, :civil_status,
                :nationality, :occupation, :employer_name, :annual_income, NOW()
            )
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':birth_date', $birthDate);
        $stmt->bindParam(':gender_id', $genderId);
        $stmt->bindParam(':civil_status', $mappedData['civil_status']);
        $stmt->bindParam(':nationality', $mappedData['nationality']);
        $stmt->bindParam(':occupation', $mappedData['occupation']);
        $stmt->bindParam(':employer_name', $mappedData['employer_name']);
        $stmt->bindParam(':annual_income', $mappedData['annual_income']);
        $stmt->execute();

        // Insert into emails table (only if email was provided)
        if ($hasEmail) {
            $stmt = $db->prepare("
                INSERT INTO emails (customer_id, email, is_primary, created_at)
                VALUES (:customer_id, :email, 1, NOW())
            ");
            
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindParam(':email', $mappedData['email']);
            $stmt->execute();
        }

        // Insert into phones table (only if phone was provided)
        if ($hasPhone) {
            $stmt = $db->prepare("
                INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
                VALUES (:customer_id, :phone_number, 'mobile', 1, NOW())
            ");
            
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindParam(':phone_number', $mappedData['mobile_number']);
            $stmt->execute();
        }

        // Insert into addresses table (unified schema)
        // Note: addresses table uses foreign keys for location hierarchy
        $stmt = $db->prepare("
            INSERT INTO addresses (
                customer_id, address_line, barangay_id, city_id, province_id, 
                postal_code, address_type, is_primary, created_at
            ) VALUES (
                :customer_id, :address_line, :barangay_id, :city_id, :province_id,
                :postal_code, 'home', 1, NOW()
            )
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':address_line', $mappedData['address_line']);
        $stmt->bindParam(':barangay_id', $mappedData['barangay_id']);
        $stmt->bindParam(':city_id', $mappedData['city_id']);
        $stmt->bindParam(':province_id', $mappedData['province_id']);
        $stmt->bindParam(':postal_code', $mappedData['postal_code']);
        $stmt->execute();

        // Create account based on selected account type (unified schema: customer_accounts)
        // Note: customer_accounts doesn't have balance column - balance is calculated from transactions
        // Get account type ID based on selected account type
        $stmt = $db->prepare("SELECT account_type_id FROM bank_account_types WHERE type_name = :type_name LIMIT 1");
        $stmt->bindParam(':type_name', $accountTypeName);
        $stmt->execute();
        $accountType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If account type doesn't exist, create it
        if (!$accountType) {
            // Insert the account type if it doesn't exist
            $stmt = $db->prepare("INSERT INTO bank_account_types (type_name, description) VALUES (:type_name, :description)");
            $description = $accountTypeName; // Already includes " Account" suffix
            $stmt->bindParam(':type_name', $accountTypeName);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            $accountTypeId = $db->lastInsertId();
        } else {
            $accountTypeId = $accountType['account_type_id'];
        }
        
        // Set interest rate: 0.5% (0.50) for Savings accounts, NULL for Checking accounts
        $interestRate = (strtolower($accountTypeName) === 'savings account') ? 0.50 : NULL;
        
        $stmt = $db->prepare("
            INSERT INTO customer_accounts (customer_id, account_number, account_type_id, interest_rate, created_at)
            VALUES (:customer_id, :account_number, :account_type_id, :interest_rate, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':account_type_id', $accountTypeId);
        $stmt->bindParam(':interest_rate', $interestRate);
        $stmt->execute();
        
        // Get the new account ID
        $accountId = $db->lastInsertId();
        
        // Link the account to the customer (CRITICAL: Required for account queries)
        $stmt = $db->prepare("
            INSERT INTO customer_linked_accounts (customer_id, account_id, is_active, linked_at)
            VALUES (:customer_id, :account_id, 1, NOW())
        ");
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_id', $accountId);
        $stmt->execute();

        // Commit transaction
        $db->commit();

        // Clear onboarding session
        unset($_SESSION['customer_onboarding']);

        // Return success with account number
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully',
            'account_number' => $accountNumber,
            'customer_id' => $customerId
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
