<?php
/**
 * API Endpoint: Check for Duplicate Email/Phone
 * Real-time validation for email and phone uniqueness
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
    
    if (!$input) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid JSON input'
        ], 400);
    }
    
    $type = $input['type'] ?? '';
    $value = $input['value'] ?? '';
    
    if (empty($type) || empty($value)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Type and value are required'
        ], 400);
    }
    
    if ($type === 'email') {
        // Validate email format first
        $emailValidation = validateEmail($value);
        if (!$emailValidation['valid']) {
            sendJsonResponse([
                'success' => false,
                'exists' => false,
                'message' => $emailValidation['message']
            ], 400);
        }
        
        // Check if exists
        $exists = checkEmailExists($db, $value);
        
        sendJsonResponse([
            'success' => true,
            'exists' => $exists,
            'message' => $exists ? 'Email already registered' : 'Email available'
        ]);
        
    } else if ($type === 'phone') {
        // Validate phone format first
        $phoneValidation = validatePhone($value);
        if (!$phoneValidation['valid']) {
            sendJsonResponse([
                'success' => false,
                'exists' => false,
                'message' => $phoneValidation['message']
            ], 400);
        }
        
        // Check if exists
        $exists = checkPhoneExists($db, $phoneValidation['clean_phone']);
        
        sendJsonResponse([
            'success' => true,
            'exists' => $exists,
            'message' => $exists ? 'Phone number already registered' : 'Phone number available'
        ]);
        
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid type. Must be "email" or "phone"'
        ], 400);
    }
    
} catch (Exception $e) {
    error_log("Duplicate check error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while checking for duplicates'
    ], 500);
}