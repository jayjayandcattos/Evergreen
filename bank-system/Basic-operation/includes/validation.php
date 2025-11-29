<?php
/**
 * Validation Functions for Customer Onboarding
 */

/**
 * Validate email format
 */
function validateEmail($email) {
    if (empty($email)) {
        return ['valid' => false, 'message' => 'Email is required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email format'];
    }
    
    if (!strpos($email, '@')) {
        return ['valid' => false, 'message' => 'Email must contain @'];
    }
    
    return ['valid' => true];
}

/**
 * Validate phone number format
 */
function validatePhone($phone) {
    if (empty($phone)) {
        return ['valid' => false, 'message' => 'Phone number is required'];
    }
    
    // Remove spaces, dashes, parentheses, and plus sign
    $cleanPhone = preg_replace('/[\s\-\(\)\+]/', '', $phone);
    
    // Check if it contains only digits after cleaning
    if (!preg_match('/^\d+$/', $cleanPhone)) {
        return ['valid' => false, 'message' => 'Phone number should contain only digits'];
    }
    
    // Check length (7-15 digits is standard international range)
    if (strlen($cleanPhone) < 7 || strlen($cleanPhone) > 15) {
        return ['valid' => false, 'message' => 'Phone number must be between 7 and 15 digits'];
    }
    
    return ['valid' => true, 'clean_phone' => $cleanPhone];
}

/**
 * Validate date of birth (must be 18+)
 */
function validateAge($dateOfBirth) {
    if (empty($dateOfBirth)) {
        return ['valid' => false, 'message' => 'Date of birth is required'];
    }
    
    $dob = new DateTime($dateOfBirth);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
    if ($age < 18) {
        return ['valid' => false, 'message' => 'Customer must be at least 18 years old'];
    }
    
    if ($age > 120) {
        return ['valid' => false, 'message' => 'Invalid date of birth'];
    }
    
    return ['valid' => true, 'age' => $age];
}

/**
 * Validate required field
 */
function validateRequired($value, $fieldName) {
    if (empty(trim($value))) {
        return ['valid' => false, 'message' => "$fieldName is required"];
    }
    return ['valid' => true];
}

/**
 * Validate name (letters, spaces, hyphens only)
 */
function validateName($name, $fieldName) {
    $validation = validateRequired($name, $fieldName);
    if (!$validation['valid']) {
        return $validation;
    }
    
    if (!preg_match("/^[a-zA-Z\s\-']+$/", $name)) {
        return ['valid' => false, 'message' => "$fieldName can only contain letters, spaces, hyphens, and apostrophes"];
    }
    
    if (strlen($name) > 50) {
        return ['valid' => false, 'message' => "$fieldName must be less than 50 characters"];
    }
    
    return ['valid' => true];
}

/**
 * Check if email already exists in database
 */
function checkEmailExists($pdo, $email, $excludeCustomerId = null) {
    try {
        $sql = "SELECT COUNT(*) as count FROM emails WHERE email = :email";
        if ($excludeCustomerId) {
            $sql .= " AND customer_id != :customer_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        if ($excludeCustomerId) {
            $stmt->bindParam(':customer_id', $excludeCustomerId);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if phone number already exists in database
 */
function checkPhoneExists($pdo, $phoneNumber, $excludeCustomerId = null) {
    try {
        $sql = "SELECT COUNT(*) as count FROM phones WHERE phone_number = :phone";
        if ($excludeCustomerId) {
            $sql .= " AND customer_id != :customer_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':phone', $phoneNumber);
        if ($excludeCustomerId) {
            $stmt->bindParam(':customer_id', $excludeCustomerId);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Phone check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate all step 1 form data
 */
function validateStep1Data($data, $pdo) {
    $errors = [];
    
    // Validate names
    $firstNameValidation = validateName($data['first_name'], 'First name');
    if (!$firstNameValidation['valid']) {
        $errors['first_name'] = $firstNameValidation['message'];
    }
    
    $lastNameValidation = validateName($data['last_name'], 'Last name');
    if (!$lastNameValidation['valid']) {
        $errors['last_name'] = $lastNameValidation['message'];
    }
    
    // Middle name is optional but validate if provided
    if (!empty($data['middle_name'])) {
        $middleNameValidation = validateName($data['middle_name'], 'Middle name');
        if (!$middleNameValidation['valid']) {
            $errors['middle_name'] = $middleNameValidation['message'];
        }
    }
    
    // Validate address
    $addressValidation = validateRequired($data['address_line'], 'Address');
    if (!$addressValidation['valid']) {
        $errors['address_line'] = $addressValidation['message'];
    }
    
    $cityValidation = validateRequired($data['city'], 'City');
    if (!$cityValidation['valid']) {
        $errors['city'] = $cityValidation['message'];
    }
    
    // Validate date of birth
    $ageValidation = validateAge($data['date_of_birth']);
    if (!$ageValidation['valid']) {
        $errors['date_of_birth'] = $ageValidation['message'];
    }
    
    // Validate emails
    if (empty($data['emails']) || !is_array($data['emails'])) {
        $errors['emails'] = 'At least one email is required';
    } else {
        foreach ($data['emails'] as $index => $email) {
            $emailValidation = validateEmail($email);
            if (!$emailValidation['valid']) {
                $errors["email_$index"] = $emailValidation['message'];
            } else {
                // Check for duplicates in database
                if (checkEmailExists($pdo, $email)) {
                    $errors["email_$index"] = 'This email is already registered';
                }
            }
        }
    }
    
    // Validate phones
    if (empty($data['phones']) || !is_array($data['phones'])) {
        $errors['phones'] = 'At least one phone number is required';
    } else {
        foreach ($data['phones'] as $index => $phoneData) {
            $phoneValidation = validatePhone($phoneData['number']);
            if (!$phoneValidation['valid']) {
                $errors["phone_$index"] = $phoneValidation['message'];
            } else {
                // Check for duplicates in database
                if (checkPhoneExists($pdo, $phoneValidation['clean_phone'])) {
                    $errors["phone_$index"] = 'This phone number is already registered';
                }
            }
        }
    }
    
    // Validate account type (required)
    if (empty($data['account_type'])) {
        $errors['account_type'] = 'Account type is required';
    } else {
        $validAccountTypes = ['Savings', 'Checking'];
        if (!in_array($data['account_type'], $validAccountTypes)) {
            $errors['account_type'] = 'Invalid account type. Please select Savings or Checking';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate username
 */
function validateUsername($username, $pdo = null) {
    if (empty(trim($username))) {
        return ['valid' => false, 'message' => 'Username is required'];
    }
    
    // Username must be 5-20 characters
    if (strlen($username) < 5 || strlen($username) > 20) {
        return ['valid' => false, 'message' => 'Username must be between 5 and 20 characters'];
    }
    
    // Username can only contain letters, numbers, and underscores
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
    }
    
    // Username must start with a letter
    if (!preg_match('/^[a-zA-Z]/', $username)) {
        return ['valid' => false, 'message' => 'Username must start with a letter'];
    }
    
    // Check if username exists in database
    if ($pdo) {
        if (checkUsernameExists($pdo, $username)) {
            return ['valid' => false, 'message' => 'This username is already taken'];
        }
    }
    
    return ['valid' => true];
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (empty($password)) {
        return ['valid' => false, 'message' => 'Password is required'];
    }
    
    // Password must be at least 8 characters
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    
    // Password must contain at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    
    // Password must contain at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    
    // Password must contain at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    
    // Password must contain at least one special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one special character'];
    }
    
    return ['valid' => true];
}

/**
 * Validate password confirmation
 */
function validatePasswordConfirmation($password, $confirmPassword) {
    if ($password !== $confirmPassword) {
        return ['valid' => false, 'message' => 'Passwords do not match'];
    }
    return ['valid' => true];
}

/**
 * Check if username already exists in database
 */
function checkUsernameExists($pdo, $username, $excludeCustomerId = null) {
    try {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE customer_username = :username";
        if ($excludeCustomerId) {
            $sql .= " AND customer_id != :customer_id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        if ($excludeCustomerId) {
            $stmt->bindParam(':customer_id', $excludeCustomerId);
        }
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Username check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate all step 2 form data (security & credentials)
 */
function validateStep2Data($data, $pdo) {
    $errors = [];
    
    // Note: Username validation removed - customers will use email for login (per unified schema)
    
    // Validate password
    if (empty($data['password'])) {
        $errors['password'] = 'Password is required';
    } else {
        $passwordValidation = validatePassword($data['password']);
        if (!$passwordValidation['valid']) {
            $errors['password'] = $passwordValidation['message'];
        }
    }
    
    // Validate password confirmation
    if (!isset($data['confirm_password'])) {
        $errors['confirm_password'] = 'Password confirmation is required';
    } else {
        $confirmValidation = validatePasswordConfirmation($data['password'], $data['confirm_password']);
        if (!$confirmValidation['valid']) {
            $errors['confirm_password'] = $confirmValidation['message'];
        }
    }
    
    // Validate mobile number (should be from step 1)
    if (empty($data['mobile_number'])) {
        $errors['mobile_number'] = 'Mobile number is required';
    } else {
        $phoneValidation = validatePhone($data['mobile_number']);
        if (!$phoneValidation['valid']) {
            $errors['mobile_number'] = $phoneValidation['message'];
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}