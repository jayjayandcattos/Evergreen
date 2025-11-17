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
        'email' => is_array($data['emails'] ?? null) ? $data['emails'][0] : ($data['email'] ?? null),
        'mobile_number' => $data['mobile_number'] ?? (is_array($data['phones'] ?? null) ? $data['phones'][0] : null),
        'address_line' => $data['address_line'] ?? $data['street'] ?? null,
        'city' => $data['city'] ?? null,
        'province' => $data['province'] ?? $data['province_name'] ?? null,
        'postal_code' => $data['postal_code'] ?? null,
        'country' => $data['country'] ?? $data['country_name'] ?? 'Philippines',
        'occupation' => $data['employment_status'] ?? $data['occupation'] ?? null,
        'employer_name' => $data['employer_name'] ?? '',
        'annual_income' => $data['annual_income'] ?? $data['source_of_funds'] ?? null,
        'password_hash' => $data['password_hash'] ?? null,
        'account_type' => $data['account_type'] ?? 'Savings' // Get account type from step 1
    ];

    // Validate required fields
    // Note: Username removed - customers will use email for login (per unified schema)
    $requiredFields = [
        'first_name', 'last_name', 'birth_date', 'birth_place',
        'gender', 'civil_status', 'nationality', 'email', 'mobile_number',
        'address_line', 'city', 'province', 'postal_code', 'country',
        'occupation', 'password_hash'
    ];

    foreach ($requiredFields as $field) {
        if (empty($mappedData[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
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

    // Check for duplicate email
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

    // Check for duplicate phone
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

    // Note: Username check removed - customers will use email for login (per unified schema)
    // Email uniqueness is already checked above

    // Get account type from mapped data (selected in step 1)
    $accountTypeName = $mappedData['account_type'] ?? 'Savings';
    
    // Generate unique account number based on account type
    $accountNumber = generateAccountNumber($db, $accountTypeName);

    // Get or create gender ID
    $genderId = getOrCreateGenderId($db, $mappedData['gender']);

    // Get or create province ID
    $provinceId = getOrCreateProvinceId($db, $mappedData['province']);

    // Format birth_date to MySQL date format (YYYY-MM-DD)
    $birthDate = date('Y-m-d', strtotime($mappedData['birth_date']));
    
    // Note: phones table in unified schema doesn't have country_code_id column
    // Phone number should already include country code from step 2

    // Begin transaction
    $db->beginTransaction();

    try {
        // Insert into bank_customers table (unified schema)
        // Note: No username field - customers will use email for login
        $stmt = $db->prepare("
            INSERT INTO bank_customers (first_name, middle_name, last_name, password_hash, created_at)
            VALUES (:first_name, :middle_name, :last_name, :password_hash, NOW())
        ");
        
        $stmt->bindParam(':first_name', $mappedData['first_name']);
        $stmt->bindParam(':middle_name', $mappedData['middle_name']);
        $stmt->bindParam(':last_name', $mappedData['last_name']);
        $stmt->bindParam(':password_hash', $mappedData['password_hash']);
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

        // Insert into emails table
        $stmt = $db->prepare("
            INSERT INTO emails (customer_id, email, is_primary, created_at)
            VALUES (:customer_id, :email, 1, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':email', $mappedData['email']);
        $stmt->execute();

        // Insert into phones table (unified schema)
        // Note: phones table doesn't have country_code_id - phone_number should include country code
        $stmt = $db->prepare("
            INSERT INTO phones (customer_id, phone_number, phone_type, is_primary, created_at)
            VALUES (:customer_id, :phone_number, 'mobile', 1, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':phone_number', $mappedData['mobile_number']);
        $stmt->execute();

        // Insert into addresses table (unified schema)
        // Note: addresses table doesn't have 'country' field, only province_id
        $stmt = $db->prepare("
            INSERT INTO addresses (
                customer_id, address_line, city, province_id, 
                postal_code, address_type, is_primary, created_at
            ) VALUES (
                :customer_id, :address_line, :city, :province_id,
                :postal_code, 'home', 1, NOW()
            )
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':address_line', $mappedData['address_line']);
        $stmt->bindParam(':city', $mappedData['city']);
        $stmt->bindParam(':province_id', $provinceId);
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
            $description = $accountTypeName . ' Account';
            $stmt->bindParam(':type_name', $accountTypeName);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            $accountTypeId = $db->lastInsertId();
        } else {
            $accountTypeId = $accountType['account_type_id'];
        }
        
        // Set interest rate: 0.5% (0.50) for Savings accounts, NULL for Checking accounts
        $interestRate = (strtolower($accountTypeName) === 'savings') ? 0.50 : NULL;
        
        $stmt = $db->prepare("
            INSERT INTO customer_accounts (customer_id, account_number, account_type_id, interest_rate, created_at)
            VALUES (:customer_id, :account_number, :account_type_id, :interest_rate, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':account_type_id', $accountTypeId);
        $stmt->bindParam(':interest_rate', $interestRate);
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

} catch (Exception $e) {
    error_log("Create final account error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating your account. Please try again.',
        'debug' => $e->getMessage()
    ]);
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
