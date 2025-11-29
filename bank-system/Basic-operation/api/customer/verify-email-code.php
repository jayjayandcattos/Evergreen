<?php
/**
 * API Endpoint: Verify Email Code
 * Verifies the email verification code entered by the user
 */

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/validation.php';

// Check request method
checkRequestMethod('POST');

try {
    // Get JSON input
    $input = getJsonInput();
    
    if (!$input || !isset($input['code'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Verification code is required'
        ], 400);
    }
    
    $code = sanitizeInput($input['code']);
    
    // Check if verification data exists
    if (!isset($_SESSION['verification'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'No verification code was sent. Please request a new code.'
        ], 400);
    }
    
    $verification = $_SESSION['verification'];
    
    // Check if code has expired
    if (time() > $verification['expires']) {
        unset($_SESSION['verification']);
        sendJsonResponse([
            'success' => false,
            'message' => 'Verification code has expired. Please request a new code.'
        ], 400);
    }
    
    // Check attempt limit
    if ($verification['attempts'] >= 5) {
        unset($_SESSION['verification']);
        sendJsonResponse([
            'success' => false,
            'message' => 'Too many failed attempts. Please request a new code.'
        ], 429);
    }
    
    // Verify the code
    if (password_verify($code, $verification['code'])) {
        // Code is correct
        $_SESSION['verification']['verified'] = true;
        $_SESSION['verification']['verified_at'] = time();
        
        // Store verified email in customer onboarding data
        if (!isset($_SESSION['customer_onboarding']['data'])) {
            $_SESSION['customer_onboarding']['data'] = [];
        }
        $_SESSION['customer_onboarding']['data']['verified_email'] = $verification['email'];
        
        $bank_id = $_SESSION['customer_onboarding']['data']['bank_id'] ?? 'N/A';
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Email verified successfully',
            'bank_id' => $bank_id
        ]);
    } else {
        // Code is incorrect
        $_SESSION['verification']['attempts']++;
        $attemptsLeft = 5 - $_SESSION['verification']['attempts'];
        
        sendJsonResponse([
            'success' => false,
            'message' => "Invalid verification code. $attemptsLeft attempts remaining."
        ], 422);
    }
    
} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred during verification'
    ], 500);
}
