<?php
/**
 * Get Session Data API
 * Retrieves customer onboarding session data for review page
 */

// Start session before any output
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Check if session exists
    if (!isset($_SESSION['customer_onboarding']) || !isset($_SESSION['customer_onboarding']['data'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No session data found'
        ]);
        exit();
    }

    // Return session data
    echo json_encode([
        'success' => true,
        'data' => $_SESSION['customer_onboarding']['data']
    ]);

} catch (Exception $e) {
    error_log("Get session data error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving session data'
    ]);
}
?>
