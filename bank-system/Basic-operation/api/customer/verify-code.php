<?php
/**
 * API Endpoint: Verify SMS Code
 * Verifies the 4-digit code entered by the user
 */

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/validation.php';
require_once '../../includes/sms-service.php';

// Check request method
checkRequestMethod('POST');

try {
    // Get JSON input
    $input = getJsonInput();
    
    if (!$input || !isset($input['phone_number']) || !isset($input['code'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Phone number and code are required'
        ], 400);
    }
    
    $phoneNumber = sanitizeInput($input['phone_number']);
    $code = sanitizeInput($input['code']);
    
    // Verify the code
    $verificationResult = verifyCode($phoneNumber, $code);
    
    if ($verificationResult['valid']) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Code verified successfully',
            'verified' => true
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => $verificationResult['message'],
            'verified' => false
        ], 422);
    }
    
} catch (Exception $e) {
    error_log("Verify code error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while verifying code'
    ], 500);
}
