<?php
/**
 * Employee Login API
 * Authenticates employee credentials and creates session
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $rememberMe = $input['rememberMe'] ?? false;
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username and password are required'
        ]);
        exit();
    }
    
    // Get database connection
    $db = getDBConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Query employee by username
    $stmt = $db->prepare("
        SELECT 
            be.employee_id,
            be.username,
            be.password_hash,
            be.email,
            be.first_name,
            be.last_name,
            be.email as employee_email,
            be.role,
            be.is_active
        FROM bank_employees be
        WHERE be.username = :username
        LIMIT 1
    ");
    
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if employee exists
    if (!$employee) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit();
    }
    
    // Check if account is active
    if ($employee['is_active'] != 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been deactivated. Please contact administrator.'
        ]);
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $employee['password_hash'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit();
    }
    
    // Login successful - Create session
    $_SESSION['employee_logged_in'] = true;
    $_SESSION['employee_id'] = $employee['employee_id'];
    $_SESSION['employee_username'] = $employee['username'];
    $_SESSION['employee_name'] = ($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '');
    $_SESSION['employee_role'] = $employee['role'];
    $_SESSION['employee_email'] = $employee['employee_email'] ?? $employee['email'];
    $_SESSION['login_time'] = time();
    
    // Set session timeout (8 hours)
    $_SESSION['session_timeout'] = time() + (8 * 60 * 60);
    
    // If remember me is checked, extend session
    if ($rememberMe) {
        // Set session cookie to expire in 30 days
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            30 * 24 * 60 * 60, // 30 days
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    
    // Update last login time
    $updateStmt = $db->prepare("
        UPDATE bank_employees 
        SET updated_at = CURRENT_TIMESTAMP 
        WHERE employee_id = :employee_id
    ");
    $updateStmt->bindParam(':employee_id', $employee['employee_id'], PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'employee' => [
            'id' => $employee['employee_id'],
            'username' => $employee['username'],
            'first_name' => $employee['first_name'] ?? '',
            'last_name' => $employee['last_name'] ?? '',
            'role' => $employee['role'],
            'email' => $employee['employee_email'] ?? $employee['email'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during login. Please try again.',
        'error' => (ini_get('display_errors') ? $e->getMessage() : 'Internal server error')
    ]);
} catch (PDOException $e) {
    error_log('Database error in employee-login.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please check your database configuration.',
        'error' => (ini_get('display_errors') ? $e->getMessage() : 'Database error')
    ]);
}
?>
