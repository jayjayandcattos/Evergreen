<?php
/**
 * API Endpoint: Get Country Codes
 * Returns list of country codes for phone number selection
 */

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check request method
checkRequestMethod('GET');

try {
    // Get all country codes (function will create table if needed)
    $countryCodes = getAllCountryCodes($db);
    
    if (empty($countryCodes)) {
        // Fallback: return at least Philippines
        $countryCodes = [
            [
                'country_code_id' => 1,
                'country_name' => 'Philippines',
                'phone_code' => '+63',
                'iso_code' => 'PH'
            ]
        ];
    }
    
    sendJsonResponse([
        'success' => true,
        'data' => $countryCodes,
        'count' => count($countryCodes)
    ]);
    
} catch (Exception $e) {
    error_log("Get country codes error: " . $e->getMessage());
    
    // Even on error, return at least Philippines
    sendJsonResponse([
        'success' => true, // Set to true so frontend doesn't fail
        'data' => [
            [
                'country_code_id' => 1,
                'country_name' => 'Philippines',
                'phone_code' => '+63',
                'iso_code' => 'PH'
            ]
        ],
        'count' => 1,
        'warning' => 'Using default country code. ' . $e->getMessage()
    ]);
}