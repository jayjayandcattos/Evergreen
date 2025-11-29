<?php
/**
 * Update Session Data API
 * Updates customer onboarding session data from review page edits
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'No data provided'
        ]);
        exit();
    }

    // Check if session exists
    if (!isset($_SESSION['customer_onboarding'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Session not found'
        ]);
        exit();
    }

    // Update session data
    foreach ($input as $key => $value) {
        // Handle password update separately
        if ($key === 'password' && isset($input['confirm_password'])) {
            // Validate password match
            if ($value !== $input['confirm_password']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Passwords do not match'
                ]);
                exit();
            }
            
            // Hash the new password
            $hashedPassword = password_hash($value, PASSWORD_DEFAULT);
            $_SESSION['customer_onboarding']['data']['password_hash'] = $hashedPassword;
            
            // Don't store plain passwords
            continue;
        }
        
        if ($key !== 'confirm_password') {
            $_SESSION['customer_onboarding']['data'][$key] = $value;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Session data updated successfully',
        'data' => $_SESSION['customer_onboarding']['data']
    ]);

} catch (Exception $e) {
    error_log("Update session error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating session data'
    ]);
}
?>
