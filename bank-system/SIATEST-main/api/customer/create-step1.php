<?php
/**
 * API Endpoint: Customer Onboarding - Step 1 (Personal Details)
 * Handles form submission for step 1 and stores data in session
 */

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    // Sanitize input data
    $data = sanitizeInput($input);
    
    // Prepare data structure for validation
    $validationData = [
        'first_name' => $data['first_name'] ?? '',
        'middle_name' => $data['middle_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
        'address_line' => $data['address_line'] ?? '',
        'city' => $data['city'] ?? '',
        'province' => $data['province'] ?? '',
        'gender' => $data['gender'] ?? '',
        'date_of_birth' => $data['date_of_birth'] ?? '',
        'place_of_birth' => $data['place_of_birth'] ?? '',
        'marital_status' => $data['marital_status'] ?? '',
        'nationality' => $data['nationality'] ?? '',
        'emails' => $data['emails'] ?? [],
        'phones' => $data['phones'] ?? [],
        'postal_code' => $data['postal_code'] ?? '',
        'source_of_funds' => $data['source_of_funds'] ?? '',
        'employment_status' => $data['employment_status'] ?? '',
        'employer_name' => $data['employer_name'] ?? '',
        'employer_address' => $data['employer_address'] ?? ''
    ];
    
    // Validate all data
    $validation = validateStep1Data($validationData, $db);
    
    if (!$validation['valid']) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation['errors']
        ], 422);
    }
    
    // Store validated data in session
    updateOnboardingSession(1, $validationData);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Step 1 data saved successfully',
        'next_step' => 2
    ]);
    
} catch (Exception $e) {
    error_log("Step 1 error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while processing your request'
    ], 500);
}