<?php
/**
 * SMS Service for Mobile Verification
 * This is a basic implementation using Semaphore SMS API (Philippine SMS provider)
 * You can replace with Twilio, Vonage, or other SMS providers
 */

// SMS Configuration - Update these with your actual credentials
define('SMS_API_TOKEN', 'f9f9fc2d936d5b4cfb9b47f3dc9bb53e8f019520'); // iProg SMS API Token
define('SMS_SENDER_NAME', 'EVERGREEN');
define('SMS_PROVIDER', 'mock'); // Options: iprog, semaphore, twilio, mock - SET TO 'mock' FOR TESTING

/**
 * Send SMS verification code
 * @param string $phoneNumber Phone number with country code (e.g., +639123456789)
 * @param string $code 4-digit verification code
 * @return array Result with success status and message
 */
function sendVerificationSMS($phoneNumber, $code) {
    $message = "Your EVERGREEN verification code is: $code. Valid for 5 minutes. Do not share this code.";
    
    switch (SMS_PROVIDER) {
        case 'iprog':
            return sendIProgSMS($phoneNumber, $message);
        case 'semaphore':
            return sendSemaphoreSMS($phoneNumber, $message);
        case 'twilio':
            return sendTwilioSMS($phoneNumber, $message);
        case 'mock':
        default:
            return sendMockSMS($phoneNumber, $message, $code);
    }
}

/**
 * Send SMS via iProg SMS (Philippine SMS provider)
 */
function sendIProgSMS($phoneNumber, $message) {
    try {
        $url = 'https://sms.iprogtech.com/api/v1/sms_messages';
        
        // Remove + from phone number and ensure it starts with 63
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = '63' . substr($cleanPhone, 1);
        } elseif (substr($cleanPhone, 0, 2) !== '63') {
            $cleanPhone = '63' . $cleanPhone;
        }
        
        $data = [
            'api_token' => SMS_API_TOKEN,
            'phone_number' => $cleanPhone,
            'message' => $message
        ];
        
        error_log("iProg SMS Request URL: " . $url);
        error_log("iProg SMS Request Data: " . json_encode($data));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("iProg SMS Response Code: " . $httpCode);
        error_log("iProg SMS Response Body: " . $output);
        
        $result = json_decode($output, true);
        
        if ($httpCode === 200 && isset($result['status']) && $result['status'] === 200) {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'provider' => 'iprog'
            ];
        } else {
            error_log("iProg SMS Error: " . $output);
            return [
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $result['message'] ?? 'Unknown error'
            ];
        }
        
    } catch (Exception $e) {
        error_log("iProg SMS Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'SMS service error'
        ];
    }
}

/**
 * Send SMS via Semaphore (Philippine SMS provider)
 */
function sendSemaphoreSMS($phoneNumber, $message) {
    try {
        $url = 'https://api.semaphore.co/api/v4/messages';
        
        $data = [
            'apikey' => SMS_API_TOKEN,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => SMS_SENDER_NAME
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($output, true);
        
        if ($httpCode === 200 && isset($result[0]['status']) && $result[0]['status'] === 'Queued') {
            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'provider' => 'semaphore'
            ];
        } else {
            error_log("Semaphore SMS Error: " . $output);
            return [
                'success' => false,
                'message' => 'Failed to send SMS',
                'error' => $result['message'] ?? 'Unknown error'
            ];
        }
        
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'SMS service error'
        ];
    }
}

/**
 * Send SMS via Twilio (International SMS provider)
 */
function sendTwilioSMS($phoneNumber, $message) {
    // Implement Twilio SMS sending
    // Requires Twilio PHP SDK: composer require twilio/sdk
    
    try {
        // Example Twilio implementation:
        // $twilio = new Twilio\Rest\Client(TWILIO_SID, TWILIO_TOKEN);
        // $message = $twilio->messages->create($phoneNumber, [
        //     'from' => TWILIO_PHONE_NUMBER,
        //     'body' => $message
        // ]);
        
        return [
            'success' => false,
            'message' => 'Twilio integration not configured'
        ];
        
    } catch (Exception $e) {
        error_log("Twilio SMS Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'SMS service error'
        ];
    }
}

/**
 * Mock SMS for testing (logs to file instead of sending)
 */
function sendMockSMS($phoneNumber, $message, $code) {
    // For development/testing - just log the code
    error_log("MOCK SMS to $phoneNumber: Code is $code");
    
    // Store in session for testing
    $_SESSION['mock_sms_code'] = $code;
    $_SESSION['mock_sms_phone'] = $phoneNumber;
    $_SESSION['mock_sms_time'] = time();
    
    return [
        'success' => true,
        'message' => 'SMS sent successfully (MOCK MODE)',
        'provider' => 'mock',
        'dev_code' => $code // Only in mock mode for testing
    ];
}

/**
 * Generate 4-digit verification code
 */
function generateVerificationCode() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Store verification code in session
 */
function storeVerificationCode($phoneNumber, $code) {
    $_SESSION['verification'] = [
        'phone' => $phoneNumber,
        'code' => $code,
        'expires' => time() + (5 * 60), // 5 minutes
        'attempts' => 0
    ];
}

/**
 * Verify code
 */
function verifyCode($phoneNumber, $code) {
    if (!isset($_SESSION['verification'])) {
        return [
            'valid' => false,
            'message' => 'No verification code found. Please request a new code.'
        ];
    }
    
    $verification = $_SESSION['verification'];
    
    // Check if code is expired
    if (time() > $verification['expires']) {
        unset($_SESSION['verification']);
        return [
            'valid' => false,
            'message' => 'Verification code has expired. Please request a new code.'
        ];
    }
    
    // Check phone number match
    if ($verification['phone'] !== $phoneNumber) {
        return [
            'valid' => false,
            'message' => 'Phone number mismatch.'
        ];
    }
    
    // Check attempts
    if ($verification['attempts'] >= 3) {
        unset($_SESSION['verification']);
        return [
            'valid' => false,
            'message' => 'Too many failed attempts. Please request a new code.'
        ];
    }
    
    // Verify code
    if ($verification['code'] === $code) {
        $_SESSION['verification']['verified'] = true;
        return [
            'valid' => true,
            'message' => 'Code verified successfully'
        ];
    } else {
        $_SESSION['verification']['attempts']++;
        return [
            'valid' => false,
            'message' => 'Invalid verification code. ' . (3 - $_SESSION['verification']['attempts']) . ' attempts remaining.'
        ];
    }
}

/**
 * Check if phone is verified
 */
function isPhoneVerified() {
    return isset($_SESSION['verification']['verified']) && $_SESSION['verification']['verified'] === true;
}

/**
 * Clear verification data
 */
function clearVerification() {
    unset($_SESSION['verification']);
}
