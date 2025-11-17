<?php
/**
 * Database Configuration for LoanSubsystem
 * Connects to BankingDB.customers table
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'BankingDB');

/**
 * Get database connection
 * @return mysqli|null Returns mysqli connection or null on failure
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return null;
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Get user by email from bank_customers table
 * Joins with emails, phones, and customer_accounts tables to get complete user data
 * @param string $email User email
 * @return array|null User data or null if not found
 */
function getUserByEmail($email) {
    $conn = getDBConnection();
    if (!$conn) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            bc.customer_id as id,
            bc.first_name,
            bc.middle_name,
            bc.last_name,
            e.email,
            p.phone_number as contact_number,
            (SELECT ca.account_number 
             FROM customer_accounts ca 
             WHERE ca.customer_id = bc.customer_id 
             LIMIT 1) as account_number,
            bc.password_hash as password,
            TRIM(CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name)) as full_name,
            CONCAT(bc.first_name, ' ', bc.last_name) as display_name
        FROM bank_customers bc
        INNER JOIN emails e ON bc.customer_id = e.customer_id
        LEFT JOIN phones p ON bc.customer_id = p.customer_id AND p.is_primary = 1
        WHERE e.email = ?
        LIMIT 1
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Verify user password
 * @param string $email User email
 * @param string $password Plain text password
 * @return array|null User data if password is correct, null otherwise
 */
function verifyUserPassword($email, $password) {
    $user = getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']); // Remove password from returned data
        $user['role'] = 'client'; // Default role for bank_customers
        return $user;
    }
    
    return null;
}

/**
 * Get admin user by email from users table
 * Checks if user has admin role via user_roles and roles tables
 * @param string $email Admin email
 * @return array|null User data or null if not found or not admin
 */
function getAdminByEmail($email) {
    $conn = getDBConnection();
    if (!$conn) {
        return null;
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.full_name,
            u.password_hash as password,
            u.is_active,
            u.last_login,
            GROUP_CONCAT(r.name) as roles
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.email = ? 
        AND u.is_active = 1
        GROUP BY u.id, u.username, u.email, u.full_name, u.password_hash, u.is_active, u.last_login
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user has admin role (case-insensitive, also check for 'Administrator')
    if ($user && $user['roles']) {
        $roles = explode(',', $user['roles']);
        $roles = array_map('trim', $roles); // Remove whitespace
        $roles = array_map('strtolower', $roles); // Convert to lowercase for comparison
        
        $hasAdminRole = in_array('admin', $roles) || 
                       in_array('administrator', $roles) ||
                       preg_match('/admin/i', implode(',', $roles));
        
        if ($hasAdminRole) {
            $user['display_name'] = $user['full_name'];
            return $user;
        }
    }
    
    return null;
}

/**
 * Verify admin password from users table
 * @param string $email Admin email
 * @param string $password Plain text password
 * @return array|null Admin data if credentials are correct, null otherwise
 */
function verifyAdminPassword($email, $password) {
    $user = getAdminByEmail($email);
    
    if (!$user) {
        error_log("Admin login failed: User not found or not admin - Email: $email");
        return null;
    }
    
    if (!password_verify($password, $user['password'])) {
        error_log("Admin login failed: Invalid password - Email: $email");
        return null;
    }
    
    unset($user['password']); // Remove password from returned data
    $user['role'] = 'admin';
    
    // Generate loan officer ID if not exists (format: LO-XXXX)
    if (empty($user['loan_officer_id'])) {
        $user['loan_officer_id'] = 'LO-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);
    }
    
    return $user;
}

