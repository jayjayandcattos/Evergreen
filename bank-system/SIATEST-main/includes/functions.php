<?php
/**
 * Helper Functions for Evergreen Banking System
 */

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get province ID by name, create if doesn't exist
 */
function getOrCreateProvinceId($pdo, $provinceName) {
    try {
        // Check if province exists
        $stmt = $pdo->prepare("SELECT province_id FROM provinces WHERE province_name = :name");
        $stmt->bindParam(':name', $provinceName);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['province_id'];
        }
        
        // Create new province
        $stmt = $pdo->prepare("INSERT INTO provinces (province_name) VALUES (:name)");
        $stmt->bindParam(':name', $provinceName);
        $stmt->execute();
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Province error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get gender ID by name, create if doesn't exist
 */
function getOrCreateGenderId($pdo, $genderName) {
    try {
        // Check if gender exists
        $stmt = $pdo->prepare("SELECT gender_id FROM genders WHERE gender_name = :name");
        $stmt->bindParam(':name', $genderName);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['gender_id'];
        }
        
        // Create new gender
        $stmt = $pdo->prepare("INSERT INTO genders (gender_name) VALUES (:name)");
        $stmt->bindParam(':name', $genderName);
        $stmt->execute();
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Gender error: " . $e->getMessage());
        return null;
    }
}

/**
 * Initialize or get session data for customer onboarding
 */
function getOnboardingSession() {
    if (!isset($_SESSION['customer_onboarding'])) {
        $_SESSION['customer_onboarding'] = [
            'step' => 1,
            'data' => []
        ];
    }
    return $_SESSION['customer_onboarding'];
}

/**
 * Update session data for customer onboarding
 */
function updateOnboardingSession($step, $data) {
    $_SESSION['customer_onboarding']['step'] = $step;
    $_SESSION['customer_onboarding']['data'] = array_merge(
        $_SESSION['customer_onboarding']['data'] ?? [],
        $data
    );
}

/**
 * Clear onboarding session
 */
function clearOnboardingSession() {
    unset($_SESSION['customer_onboarding']);
}

/**
 * Get all country codes for dropdown
 * Automatically creates the table if it doesn't exist
 */
function getAllCountryCodes($pdo) {
    try {
        // Check if country_codes table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'country_codes'");
        if ($stmt->rowCount() === 0) {
            error_log("Country codes table does not exist. Creating it now...");
            
            // Create the country_codes table
            $createTableSql = "
                CREATE TABLE IF NOT EXISTS country_codes (
                    country_code_id INT AUTO_INCREMENT PRIMARY KEY,
                    country_name VARCHAR(100) NOT NULL,
                    phone_code VARCHAR(10) NOT NULL UNIQUE,
                    iso_code VARCHAR(3) NOT NULL UNIQUE,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_phone_code (phone_code),
                    INDEX idx_iso_code (iso_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $pdo->exec($createTableSql);
            
            // Insert default country codes (ignore duplicates)
            $insertSql = "
                INSERT IGNORE INTO country_codes (country_name, phone_code, iso_code) VALUES
                ('Philippines', '+63', 'PH'),
                ('United States', '+1', 'US'),
                ('United Kingdom', '+44', 'GB'),
                ('Singapore', '+65', 'SG'),
                ('Malaysia', '+60', 'MY'),
                ('Japan', '+81', 'JP'),
                ('China', '+86', 'CN'),
                ('South Korea', '+82', 'KR'),
                ('Australia', '+61', 'AU'),
                ('Canada', '+1', 'CA')
            ";
            
            $pdo->exec($insertSql);
            error_log("Country codes table created and populated successfully.");
        }
        
        // Fetch country codes
        $stmt = $pdo->query("SELECT * FROM country_codes ORDER BY country_name");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If table exists but is empty, populate it
        if (empty($result)) {
            error_log("Country codes table is empty. Populating it now...");
            $insertSql = "
                INSERT IGNORE INTO country_codes (country_name, phone_code, iso_code) VALUES
                ('Philippines', '+63', 'PH'),
                ('United States', '+1', 'US'),
                ('United Kingdom', '+44', 'GB'),
                ('Singapore', '+65', 'SG'),
                ('Malaysia', '+60', 'MY'),
                ('Japan', '+81', 'JP'),
                ('China', '+86', 'CN'),
                ('South Korea', '+82', 'KR'),
                ('Australia', '+61', 'AU'),
                ('Canada', '+1', 'CA')
            ";
            $pdo->exec($insertSql);
            
            // Fetch again
            $stmt = $pdo->query("SELECT * FROM country_codes ORDER BY country_name");
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Ensure we always return at least Philippines
        if (empty($result)) {
            return [
                [
                    'country_code_id' => 1,
                    'country_name' => 'Philippines',
                    'phone_code' => '+63',
                    'iso_code' => 'PH'
                ]
            ];
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("Country codes fetch error: " . $e->getMessage());
        // Return default Philippines code on error
        return [
            [
                'country_code_id' => 1,
                'country_name' => 'Philippines',
                'phone_code' => '+63',
                'iso_code' => 'PH'
            ]
        ];
    }
}

/**
 * Format phone number with country code
 */
function formatPhoneNumber($countryCode, $phoneNumber) {
    return $countryCode . ' ' . $phoneNumber;
}

/**
 * Log activity (for future audit trail)
 */
function logActivity($pdo, $action, $details, $employeeId = null) {
    // This is a placeholder for future implementation
    error_log("Activity: $action - " . json_encode($details));
}

/**
 * Generate unique account number
 */
function generateAccountNumber($pdo) {
    // Format: EVGXXXXXX (EVG + 8 random digits)
    // Updated to use customer_accounts table (unified schema)
    do {
        $accountNumber = 'EVG' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        
        // Check if account number exists in customer_accounts table
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_accounts WHERE account_number = :account_number");
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->execute();
        $result = $stmt->fetch();
        
    } while ($result['count'] > 0);
    
    return $accountNumber;
}

/**
 * Check if request method is correct
 */
function checkRequestMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        sendJsonResponse([
            'success' => false,
            'message' => "Invalid request method. Expected $method"
        ], 405);
    }
}

/**
 * Get POST data as JSON
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Validate CSRF token (for future implementation)
 */
function validateCsrfToken($token) {
    // Placeholder for CSRF protection
    return true;
}