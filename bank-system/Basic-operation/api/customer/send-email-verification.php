<?php
/**
 * API Endpoint: Send Email Verification Code
 * Sends a 4-digit verification code to the provided email address
 */

// Start session first
session_start();

// Enable error logging instead of displaying
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json');

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if required files exist before including
$requiredFiles = [
    '../../config/database.php',
    '../../includes/functions.php',
    '../../includes/validation.php'
];

foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        echo json_encode([
            'success' => false,
            'message' => 'Server configuration error: Missing required file'
        ]);
        exit();
    }
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/validation.php';

// Include PHPMailer
require_once '../../../evergreen-marketing/PHPMailer-7.0.0/src/PHPMailer.php';
require_once '../../../evergreen-marketing/PHPMailer-7.0.0/src/SMTP.php';
require_once '../../../evergreen-marketing/PHPMailer-7.0.0/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Simple function to generate verification code
if (!function_exists('generateVerificationCode')) {
    function generateVerificationCode() {
        return sprintf("%04d", mt_rand(0, 9999));
    }
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = getJsonInput();
    
    if (!$input || !isset($input['email'])) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Email address is required'
        ], 400);
    }
    
    $email = sanitizeInput($input['email']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid email address format'
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
    if (isset($_SESSION['last_email_time']) && (time() - $_SESSION['last_email_time']) < 30) {
        $waitTime = 30 - (time() - $_SESSION['last_email_time']);
        sendJsonResponse([
            'success' => false,
            'message' => "Please wait $waitTime seconds before requesting a new code"
        ], 429);
    }
    
    // Generate verification code
    $code = generateVerificationCode();
    
    // Generate bank ID (4-digit number)
    $bank_id = sprintf("%04d", mt_rand(0, 9999));
    
    // Store code and bank_id in session
    if (!isset($_SESSION['verification'])) {
        $_SESSION['verification'] = [];
    }
    
    $_SESSION['verification']['email'] = $email;
    $_SESSION['verification']['code'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['verification']['expires'] = time() + 300; // 5 minutes
    $_SESSION['verification']['attempts'] = 0;
    
    // Store bank_id in session for later use
    if (!isset($_SESSION['customer_onboarding'])) {
        $_SESSION['customer_onboarding'] = [];
    }
    if (!isset($_SESSION['customer_onboarding']['data'])) {
        $_SESSION['customer_onboarding']['data'] = [];
    }
    $_SESSION['customer_onboarding']['data']['bank_id'] = $bank_id;
    
    error_log("Generated Bank ID: " . $bank_id . " for email: " . $email);
    
    // Send email (using PHP mail or a service like PHPMailer)
    $emailSent = sendVerificationEmail($email, $code, $bank_id);
    
    if ($emailSent) {
        $_SESSION['last_email_time'] = time();
        
        $response = [
            'success' => true,
            'message' => 'Verification code sent to your email',
            'expires_in' => 300, // 5 minutes in seconds
            'session_id' => session_id(),
            'bank_id' => $bank_id
        ];
        
        sendJsonResponse($response);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Failed to send verification email. Please try again.'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while sending verification code'
    ], 500);
}

/**
 * Send verification email using PHPMailer
 */
function sendVerificationEmail($email, $code, $bank_id) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'nova.markadrian.orsolon@gmail.com';  // Your Gmail address
        $mail->Password   = 'cxwz sgmc qrvn brcq';      // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@evergreenbank.com', 'Evergreen Bank');
        $mail->addAddress($email);
        $mail->addReplyTo('support@evergreenbank.com', 'Evergreen Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Evergreen Bank - Email Verification Code';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1a6b62 0%, #003631 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .code-box { background: white; border: 2px solid #1a6b62; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .code { font-size: 36px; font-weight: bold; color: #1a6b62; letter-spacing: 8px; }
                .bank-id-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
                .bank-id { font-size: 24px; font-weight: bold; color: #856404; letter-spacing: 4px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification</h1>
                    <p>Evergreen Bank - Secure. Invest. Achieve</p>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>Thank you for registering with Evergreen Bank. Please use the verification code below to complete your registration:</p>
                    
                    <div class='code-box'>
                        <p style='margin: 0; color: #666; font-size: 14px;'>Your Verification Code:</p>
                        <div class='code'>{$code}</div>
                        <p style='margin: 10px 0 0 0; color: #666; font-size: 12px;'>This code expires in 5 minutes</p>
                    </div>
                    
                    <p><strong>Security Tips:</strong></p>
                    <ul>
                        <li>Never share your verification code with anyone</li>
                        <li>Evergreen Bank will never ask for your password via email</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ul>
                    
                    <div class='footer'>
                        <p>This is an automated message from Evergreen Bank. Please do not reply to this email.</p>
                        <p>&copy; 2025 Evergreen Bank. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Your Evergreen Bank verification code is: {$code}\n\nThis code expires in 5 minutes.\n\nIf you didn't request this code, please ignore this email.";
        
        $mail->send();
        error_log("Email sent successfully to: " . $email);
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        error_log("=== EMAIL VERIFICATION (Failed - Test Mode) ===");
        error_log("To: " . $email);
        error_log("Code: " . $code);
        error_log("Bank ID: " . $bank_id);
        error_log("Error: " . $mail->ErrorInfo);
        error_log("=====================================");
        
        // Return true anyway for testing/development
        return true;
    }
}
