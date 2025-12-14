<?php
/**
 * Employee Logout API
 * Destroys session and logs out employee
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy session
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>
