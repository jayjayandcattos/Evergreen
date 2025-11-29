<?php
/**
 * Check Session API
 * Verifies if employee is logged in
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if employee is logged in
$logged_in = isset($_SESSION['employee_logged_in']) && $_SESSION['employee_logged_in'] === true;

// Check session timeout
if ($logged_in && isset($_SESSION['session_timeout'])) {
    if (time() > $_SESSION['session_timeout']) {
        // Session expired
        session_destroy();
        $logged_in = false;
        
        echo json_encode([
            'logged_in' => false,
            'message' => 'Session expired. Please login again.'
        ]);
        exit();
    }
}

if ($logged_in) {
    echo json_encode([
        'logged_in' => true,
        'employee' => [
            'id' => $_SESSION['employee_id'] ?? null,
            'username' => $_SESSION['employee_username'] ?? null,
            'name' => $_SESSION['employee_name'] ?? null,
            'role' => $_SESSION['employee_role'] ?? null,
            'email' => $_SESSION['employee_email'] ?? null,
            'hris_employee_id' => $_SESSION['hris_employee_id'] ?? null
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>
