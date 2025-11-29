<?php
/**
 * API Endpoint: Send SMS Verification Code
 * Sends a 4-digit verification code to the provided mobile number
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
    
    if (!$input || !isset($input['phone_number'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Phone number is required'
        ], 400);
    }
    
    $phoneNumber = sanitizeInput($input['phone_number']);
    
    // Validate phone number format
    $phoneValidation = validatePhone($phoneNumber);
    if (!$phoneValidation['valid']) {
        sendJsonResponse([
            'success' => false,
            'message' => $phoneValidation['message']
        ], 422);
    }
    
    // Check if we need to verify this is from step 1 data
    $sessionData = getOnboardingSession();
    if (empty($sessionData['data'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Please complete step 1 first'
        ], 400);
    }
    
    // Check rate limiting (prevent spam)
    if (isset($_SESSION['last_sms_time']) && (time() - $_SESSION['last_sms_time']) < 30) {
        $waitTime = 30 - (time() - $_SESSION['last_sms_time']);
        sendJsonResponse([
            'success' => false,
            'message' => "Please wait $waitTime seconds before requesting a new code"
        ], 429);
    }
    
    // Generate verification code
    $code = generateVerificationCode();
    
    // Generate bank ID (4-digit number, like in signup.php)
    $bank_id = sprintf("%04d", mt_rand(0, 9999));
    
    // Store code and bank_id in session
    storeVerificationCode($phoneNumber, $code);
    
    // Store bank_id in session for later use
    if (!isset($_SESSION['customer_onboarding'])) {
        $_SESSION['customer_onboarding'] = [];
    }
    if (!isset($_SESSION['customer_onboarding']['data'])) {
        $_SESSION['customer_onboarding']['data'] = [];
    }
    $_SESSION['customer_onboarding']['data']['bank_id'] = $bank_id;
    
    error_log("Generated Bank ID: " . $bank_id);
    
    // Send SMS
    $smsResult = sendVerificationSMS($phoneNumber, $code);
    
    if ($smsResult['success']) {
        $_SESSION['last_sms_time'] = time();
        
        $response = [
            'success' => true,
            'message' => 'Verification code sent successfully',
            'expires_in' => 300, // 5 minutes in seconds
            'session_id' => session_id(),
            'bank_id' => $bank_id // Always include bank_id so user can see it for login
        ];
        
        // Include code in response for mock/testing mode
        if (SMS_PROVIDER === 'mock' && isset($smsResult['dev_code'])) {
            $response['dev_code'] = $smsResult['dev_code'];
        }
        
        sendJsonResponse($response);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => $smsResult['message'] ?? 'Failed to send verification code'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Send verification code error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while sending verification code'
    ], 500);
}
