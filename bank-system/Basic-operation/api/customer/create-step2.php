<?php
/**
 * API Endpoint: Customer Onboarding - Step 2 (Security & Credentials)
 * Handles security information, credentials, and mobile verification
 */

// Suppress error display to ensure clean JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/validation.php';
require_once '../../includes/sms-service.php';

// Check request method
checkRequestMethod('POST');

try {
    // Get JSON input
    $input = getJsonInput();
    
    if (!$input) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid JSON input'
        ], 400);
    }
    
    // Check if step 1 is completed
    $sessionData = getOnboardingSession();
    if (empty($sessionData['data']) || $sessionData['step'] < 1) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Please complete step 1 first',
            'redirect' => 'customer-onboarding-details.html'
        ], 400);
    }
    
    // Sanitize input data
    $data = sanitizeInput($input);
    
    // Prepare data structure for validation
    // Note: Username removed - customers will use email for login (per unified schema)
    $validationData = [
        'password' => $data['password'] ?? '',
        'confirm_password' => $data['confirm_password'] ?? '',
        'mobile_number' => $data['mobile_number'] ?? ''
    ];
    
    // Validate all data
    $validation = validateStep2Data($validationData, $db);
    
    if (!$validation['valid']) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation['errors']
        ], 422);
    }
    
    // Check if mobile number is verified
    if (!isPhoneVerified()) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Please verify your mobile number first',
            'errors' => ['mobile_verification' => 'Mobile number not verified']
        ], 422);
    }
    
    // Hash password before storing
    $hashedPassword = password_hash($validationData['password'], PASSWORD_DEFAULT);
    
    // Store validated data in session (without plain password)
    // Note: No username - customers will use email for login (per unified schema)
    $sessionSaveData = [
        'password_hash' => $hashedPassword,
        'mobile_number' => $validationData['mobile_number'],
        'mobile_verified' => true
    ];
    
    updateOnboardingSession(2, $sessionSaveData);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Step 2 data saved successfully',
        'next_step' => 3,
        'redirect' => 'customer-onboarding-review.html'
    ]);
    
} catch (Exception $e) {
    error_log("Step 2 error: " . $e->getMessage());
    error_log("Step 2 error trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => (ini_get('display_errors') ? $e->getMessage() : 'Internal server error')
    ], 500);
} catch (Error $e) {
    error_log("Step 2 fatal error: " . $e->getMessage());
    error_log("Step 2 fatal error trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false,
        'message' => 'A fatal error occurred while processing your request',
        'error' => (ini_get('display_errors') ? $e->getMessage() : 'Internal server error')
    ], 500);
}
