<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Buffer output to prevent headers already sent error
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-7.0.0/src/Exception.php';
require 'PHPMailer-7.0.0/src/PHPMailer.php';
require 'PHPMailer-7.0.0/src/SMTP.php';
include("db_connect.php");

// ⭐ TEMPORARY TEST CODE - Add after include("db_connect.php");
if (isset($_GET['test_referral'])) {
    $test_code = generateUniqueReferralCode($conn);
    die("Generated test code: " . $test_code);
}

// Function to generate unique referral code
function generateUniqueReferralCode($conn) {
    do {
        // Generate a 6-character code (3 letters + 3 numbers)
        $code = '';
        for ($i = 0; $i < 3; $i++) {
            $code .= chr(rand(65, 90)); // A-Z
        }
        for ($i = 0; $i < 3; $i++) {
            $code .= rand(0, 9); // 0-9
        }
        
        // Check if code already exists
        $sql = "SELECT customer_id FROM bank_customers WHERE referral_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
    } while ($exists);
      return $code;
  }

  // Generate referral code
$referral_code = generateUniqueReferralCode($conn);

error_log("Generated referral code: " . $referral_code);
error_log("Referral code length: " . strlen($referral_code));

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $address = trim($_POST['address']);
    $city_province = trim($_POST['city_province']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $birthday = $_POST['birthday'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms_accepted = isset($_POST['terms']) ? true : false;

    if (empty($first_name) || empty($middle_name) || empty($last_name) || 
        empty($address) || empty($city_province) || empty($email) || 
        empty($contact_number) || empty($birthday) || empty($password) || 
        empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!$terms_accepted) {
        $error = "You must agree to the Terms and Conditions.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check_sql = "SELECT customer_id FROM bank_customers WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
    $error = "Email already registered.";
} else 
    $check_sql = "SELECT customer_id FROM bank_customers WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        // Generate verification code and bank ID
        $verification_code = sprintf("%06d", rand(0, 999999));
        $bank_id = sprintf("%04d", mt_rand(0, 9999));
        
        // Generate unique referral code
        $referral_code = generateUniqueReferralCode($conn);
        
        // Debug logging
        error_log("=== NEW USER REGISTRATION ===");
        error_log("Email: " . $email);
        error_log("Generated referral code: " . $referral_code);
        error_log("Verification code: " . $verification_code);
        error_log("Bank ID: " . $bank_id);
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Store user data in session
        $_SESSION['temp_registration'] = [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'address' => $address,
            'city_province' => $city_province,
            'email' => $email,
            'contact_number' => $contact_number,
            'birthday' => $birthday,
            'password' => $hashed_password,
            'verification_code' => $verification_code,
            'bank_id' => $bank_id,
            'referral_code' => $referral_code
        ];
        
        // Verify the code was stored
        error_log("Stored in session - Referral code: " . $_SESSION['temp_registration']['referral_code']);
        
        // Send verification email
        $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'evrgrn.64@gmail.com';
                $mail->Password = 'dourhhbymvjejuct';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                $mail->Timeout = 30;
                
                // Recipients
                $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
                $mail->addAddress($email, $first_name . ' ' . $last_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Evergreen - Verify Your Email';
                $mail->Body = "
                    <html>
                    <body style='font-family: Arial, sans-serif; padding: 20px;'>
                        <h2 style='color: #0d3d38;'>Welcome to Evergreen Banking!</h2>
                        <p>Thank you for creating an account. Here are your important details:</p>
                        
                        <div style='background: #f5f5f5; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                            <h3 style='color: #0d3d38; margin-bottom: 10px;'>Bank ID: <span style='color: #1a6b62;'>{$bank_id}</span></h3>
                            <h3 style='color: #0d3d38;'>Verification Code:</h3>
                            <h1 style='color: #0d3d38; letter-spacing: 5px; font-size: 36px;'>{$verification_code}</h1>
                        </div>
                        
                        <p>Please keep your Bank ID safe as you'll need it for future transactions.</p>
                        <p>Use the verification code above to verify your email address.</p>
                        <p>This code will expire in 10 minutes.</p>
                        <p style='color: #666; font-size: 12px; margin-top: 20px;'>If you didn't create an account, please ignore this email.</p>
                    </body>
                    </html>
                ";
                
                error_log("Attempting to send verification email to: " . $email);
                $mail->send();
                error_log("Verification email sent successfully to: " . $email);
                
                ob_end_clean();
                header("Location: verify.php");
                exit;
                
            } catch (Exception $e) {
                error_log("Failed to send verification email: " . $mail->ErrorInfo);
                $_SESSION['email_error'] = "Failed to send verification code. Please try again.";
                ob_end_clean();
                header("Location: verify.php");
                exit;
            }
        }
        $check_stmt->close();
    }
}


// Example usage in your registration process:
/*
$referral_code = generateUniqueReferralCode($conn);

$sql = "INSERT INTO users (first_name, last_name, email, password, referral_code, total_points) 
        VALUES (?, ?, ?, ?, ?, 0.00)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $referral_code);
$stmt->execute();
*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evergreen - Sign Up</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
      overflow-y: auto;
      background: #f8f9fa;
    }

    .left {
      width: 45%;
      background: white;
      display: flex;
      flex-direction: column;
      padding: 40px 60px;
      padding-top: 120px;
      position: relative;
      box-shadow: 2px 0 30px rgba(0, 0, 0, 0.05);
      z-index: 2;
    }

    .logo {
      position: absolute;
      top: 30px;
      left: 30px;
      display: flex;
      align-items: center;
      gap: 12px;
      width: auto;
      transition: transform 0.3s ease;
    }

    .logo:hover {
      transform: scale(1.05);
    }

    .logo img {
      width: 48px;
      height: 48px;
      filter: drop-shadow(0 2px 8px rgba(13, 61, 56, 0.2));
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }

    .logo-text .name {
      font-size: 16px;
      font-weight: 700;
      color: #0d3d38;
      letter-spacing: 0.5px;
    }

    .logo-text .tagline {
      font-size: 10px;
      color: #666;
      letter-spacing: 0.3px;
      margin-top: 2px;
    }

    .back-container {
      position: absolute;
      top: 30px;
      right: 60px;
    }

    .back-container a {
      padding-bottom: 20px;
    }

    .back-link {
      font-size: 24px;
      text-decoration: none;
      color: #003631;
      transition: all 0.3s ease;
      padding: 10px 18px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(13, 61, 56, 0.05);
      backdrop-filter: blur(10px);
      padding-top: -30%;
    }

    .back-link:hover {
      background: rgba(13, 61, 56, 0.1);
      transform: translateX(-4px);
    } 

    h2 {
      color: #0d3d38;
      font-size: 42px;
      font-weight: 600;
      margin-bottom: 10px;
      letter-spacing: -1px;
      text-align: center;
      background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .subtitle {
      text-align: center;
      color: #777777ff;
      font-size: 14px;
      margin-bottom: 40px;
      font-weight: 400;
    }

    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 13px;
      text-align: center;
      backdrop-filter: blur(10px);
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert.error {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
      color: #dc3545;
      border: 1px solid rgba(220, 53, 69, 0.3);
    }

    .error-message {
      color: #dc3545;
      font-size: 13px;
      margin-top: 4px;
      display: none; /* Hidden by default */
      font-weight: 400;
      line-height: 1.4;
    }

    .error-message.show {
      display: block !important; /* Force display when shown */
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    input.error {
      border-color: #dc3545 !important;
      background: #fff5f5 !important;
    }

    .password-container {
      position: relative;
    }

    .eye-icon {
      position: absolute;
      right: 20px;
      top: 49%;
      transform: translateY(-50%);
      cursor: pointer;
      width: 24px;
      height: 24px;
      background: none;
      border: none;
      padding: 0;
      font-size: 18px;
      color: #999;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .eye-icon:hover {
      color: #0d3d38;
      transform: translateY(-50%) scale(1.1);
    }

    .password-strength {
      margin-top: 10px;
      font-size: 12px;
    }

    .strength-bar {
      height: 6px;
      background: #e9ecef;
      border-radius: 10px;
      margin-top: 8px;
      overflow: hidden;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 10px;
    }

    .strength-fill.weak {
      width: 33%;
      background: linear-gradient(90deg, #dc3545 0%, #e74c3c 100%);
    }

    .strength-fill.medium {
      width: 66%;
      background: linear-gradient(90deg, #ffc107 0%, #f39c12 100%);
    }

    .strength-fill.strong {
      width: 100%;
      background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    }

    .password-requirements {
      margin-top: 12px;
      padding: 14px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 12px;
      display: none;
      border: 1px solid #dee2e6;
    }

    .password-requirements.show {
      display: block;
      animation: slideDown 0.3s ease;
    }

    .requirement {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
      font-size: 12px;
      color: #666;
      transition: all 0.3s;
    }

    .requirement:last-child {
      margin-bottom: 0;
    }

    .requirement.met {
      color: #28a745;
    }

    .req-icon {
      font-size: 16px;
      font-weight: bold;
      transition: all 0.3s;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e9ecef;
      font-size: 10px;
    }

    .requirement.met .req-icon {
      background: #28a745;
      color: white;
    }

    .requirement.met .req-icon::before {
      content: '✓';
    }

    .password-match {
      margin-top: 10px;
      font-size: 12px;
      min-height: 18px;
      font-weight: 500;
    }

    #match-text {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    #match-text.match {
      color: #28a745;
    }

    #match-text.no-match {
      color: #dc3545;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  width: 100%;
}

.form-row .input-wrapper {
  width: 100%;
}

.form-row .input-wrapper input {
  width: 100%;
}

.form-row .password-container {
  width: 100%;
}

.form-row .password-container input {
  width: 100%;
}

    .input-wrapper {
      display: flex;
      flex-direction: column;
    }

    .input-wrapper.full {
      grid-column: span 2;
    }

    .input-label {
      font-size: 13px;
      color: #0d3d38;
      margin-bottom: 8px;
      display: block;
      font-weight: 600;
      letter-spacing: 0.3px;
    }

    input {
      padding: 14px 18px;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      font-size: 14px;
      background: #f8f9fa;
      color: #333;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-family: inherit;
    }

    input::placeholder {
      color: #adb5bd;
      font-size: 13px;
    }

    input:focus {
      outline: none;
      border-color: #0d3d38;
      background: white;
      box-shadow: 0 0 0 4px rgba(13, 61, 56, 0.1);
      transform: translateY(-2px);
    }

    input:hover:not(:focus) {
      border-color: #d0d5dd;
    }

    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 10px;
      justify-content: center;
      padding: 12px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .checkbox-wrapper input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: #0d3d38;
    }

    .checkbox-wrapper label {
      font-size: 12px;
      color: #666;
      cursor: pointer;
      user-select: none;
    }

    .checkbox-wrapper a {
      color: #0d3d38;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .checkbox-wrapper a:hover {
      color: #1a6b62;
      text-decoration: underline;
    }

    .create-btn {
      width: 100%;
      background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%);
      color: white;
      padding: 16px;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 1px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      margin-top: 12px;
      box-shadow: 0 4px 16px rgba(13, 61, 56, 0.2);
      text-transform: uppercase;
    }

    .create-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(13, 61, 56, 0.3);
    }

    .create-btn:active {
      transform: translateY(0);
    }

    .login-text {
      text-align: center;
      font-size: 13px;
      color: #666;
      margin-top: 24px;
      margin-bottom: 20px;
    }

    .login-text a {
      color: #0d3d38;
      text-decoration: none;
      font-weight: 700;
      transition: color 0.3s;
    }

    .login-text a:hover {
      color: #1a6b62;
      text-decoration: underline;
    }

    .right {
      width: 55%;
      background: linear-gradient(135deg, #0a3833 0%, #0d4a44 30%, #1a6b62 70%, #4d9d95 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }

    .circle-bg {
      position: absolute;
      border-radius: 50%;
      filter: blur(60px);
      opacity: 0.3;
    }

    .circle-1 {
      width: 800px;
      height: 800px;
      top: -300px;
      right: -250px;
      background: radial-gradient(circle, rgba(90, 140, 135, 0.4) 0%, transparent 70%);
      animation: float 20s ease-in-out infinite;
    }

    .circle-2 {
      width: 650px;
      height: 650px;
      bottom: -250px;
      left: -200px;
      background: radial-gradient(circle, rgba(60, 130, 125, 0.3) 0%, transparent 70%);
      animation: float 15s ease-in-out infinite reverse;
    }

    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(30px, 30px) scale(1.05); }
    }

    .right-content {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      width: 100%;
    }

    .welcome-text {
      font-size: 56px;
      font-weight: 300;
      color: white;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
      text-align: left;
      opacity: 0.95;
      text-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
    }

    h1 {
      font-size: 72px;
      font-weight: 700;
      color: white;
      margin-bottom: 20px;
      letter-spacing: 2px;
      text-align: left;
      text-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
    }

    .right .subtitle {
      font-size: 18px;
      color: white;
      margin-bottom: 80px;
      font-weight: 300;
      letter-spacing: 0.5px;
      text-align: left;
      opacity: 0.8;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .laptop-img {
      position: absolute;
      right: -50px;
      top: 50%;
      left: 15%;
      transform: translateY(-50%) perspective(1200px) rotateY(-12deg);
      max-width: 1100px;
      width: 100%;
      filter: drop-shadow(0 40px 80px rgba(0, 0, 0, 0.4));
      animation: floatLaptop 6s ease-in-out infinite;
    }

    @keyframes floatLaptop {
      0%, 100% { transform: translateY(-50%) perspective(1200px) rotateY(-12deg); }
      50% { transform: translateY(-48%) perspective(1200px) rotateY(-12deg); }
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
      .left {
        width: 50%;
        padding: 40px 40px;
        padding-top: 100px;
      }

      .right {
        width: 50%;
      }

      h2 {
        font-size: 36px;
      }

      .welcome-text {
        font-size: 48px;
      }

      h1 {
        font-size: 60px;
      }
    }

    @media (max-width: 968px) {
      body {
        flex-direction: column;
      }

      .left {
        width: 100%;
        padding: 30px 30px;
        padding-top: 100px;
        box-shadow: none;
      }

      .right {
        width: 100%;
        padding: 60px 30px;
        min-height: 500px;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .input-wrapper.full {
        grid-column: span 1;
      }

      h2 {
        font-size: 32px;
      }

      .welcome-text {
        font-size: 40px;
      }

      h1 {
        font-size: 48px;
      }

      .right .subtitle {
        font-size: 16px;
      }

      .laptop-img {
        left: 10%;
        right: -30px;
      }

      .logo {
        left: 20px;
        top: 20px;
      }

      .back-container {
        right: 20px;
        top: 20px;
      }
    }

    @media (max-width: 480px) {
      .left {
        padding: 20px 20px;
        padding-top: 90px;
      }

      h2 {
        font-size: 28px;
        margin-bottom: 8px;
      }

      .left .subtitle {
        font-size: 13px;
        margin-bottom: 30px;
      }

      input {
        padding: 12px 16px;
        font-size: 13px;
      }

      input .password-container {
        width: 100%;
      }

      .create-btn {
        padding: 14px;
        font-size: 13px;
      }

      .welcome-text {
        font-size: 32px;
      }

      h1 {
        font-size: 40px;
      }

      .right {
        padding: 40px 20px;
        min-height: 400px;
      }
    }

    /* Smooth scroll behavior */
    html {
      scroll-behavior: smooth;
    }

    /* Loading animation for form submission */
    .create-btn.loading {
      position: relative;
      color: transparent;
    }

    .create-btn.loading::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      top: 50%;
      left: 50%;
      margin-left: -10px;
      margin-top: -10px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="left">
    <div class="logo">
      <img src="images/loginlogo.png" alt="Logo">
      <div class="logo-text">
        <span class="name">EVERGREEN</span>
        <span class="tagline">Secure. Invest. Achieve</span>
      </div>
    </div>

    <div class="back-container">
      <a href="login.php" class="back-link">←</a>
    </div>

    <h2>Create an Account</h2>

    <?php if (!empty($error)): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" id="signupForm" novalidate>
      <div class="form-row">
        <div class="input-wrapper">
          <label class="input-label">First Name</label>
          <input type="text" name="first_name" id="first_name" placeholder="Juan" 
                 value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
          <span class="error-message" id="first_name_error">This field is required</span>
        </div>
        <div class="input-wrapper">
          <label class="input-label">Middle Name</label>
          <input type="text" name="middle_name" id="middle_name" placeholder="Andrade"
                 value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
          <span class="error-message" id="middle_name_error">This field is required</span>
        </div>
      </div>

      <div class="input-wrapper full">
        <label class="input-label">Surname</label>
        <input type="text" name="last_name" id="last_name" placeholder="Dela Cruz" 
               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
        <span class="error-message" id="last_name_error">This field is required</span>
      </div>

      <div class="form-row">
        <div class="input-wrapper" style="grid-column: span 2;">
          <label class="input-label">House number/Street/Brgy</label>
          <input type="text" name="address" id="address" placeholder="29 Simforosa st. Brgy. Nagkaisang" 
                 value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
          <span class="error-message" id="address_error">This field is required</span>
        </div>
      </div>

      <div class="form-row">
        <div class="input-wrapper">
          <label class="input-label">City/Province</label>
          <input type="text" name="city_province" id="city_province" placeholder="Metro Manila" 
                 value="<?php echo isset($_POST['city_province']) ? htmlspecialchars($_POST['city_province']) : ''; ?>">
          <span class="error-message" id="city_province_error">This field is required</span>
        </div>
        <div class="input-wrapper">
          <label class="input-label">Email</label>
          <input type="email" name="email" id="email" placeholder="example@gmail.com" 
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          <span class="error-message" id="email_error">This field is required</span>
        </div>
      </div>

      <div class="form-row">
        <div class="input-wrapper">
          <label class="input-label">Contact Number</label>
          <input type="tel" name="contact_number" id="contact_number" placeholder="0927 379 2682" 
                 value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>">
          <span class="error-message" id="contact_number_error">This field is required</span>
        </div>
        <div class="input-wrapper">
          <label class="input-label">Birthday</label>
          <input type="text" name="birthday" id="birthday" placeholder="MM/DD/YEAR" onfocus="(this.type='date')" 
                 value="<?php echo isset($_POST['birthday']) ? htmlspecialchars($_POST['birthday']) : ''; ?>">
          <span class="error-message" id="birthday_error">This field is required</span>
        </div>
      </div>

      <div class="form-row">
        <div class="input-wrapper">
          <label class="input-label">Password</label>
          <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Password">
            <button type="button" class="eye-icon" onclick="togglePassword('password')"></button>
          </div>
          <span class="error-message" id="password_error">This field is required</span>
          <div class="password-strength">
            <div class="strength-bar">
              <div class="strength-fill" id="strength-fill"></div>
            </div>
            <span id="strength-text" style="color: #666; font-size: 11px; margin-top: 4px; display: block;"></span>
          </div>
          <div class="password-requirements" id="password-requirements">
            <div class="requirement" id="req-length">
              <span class="req-icon"></span>
              <span class="req-text">At least 8 characters</span>
            </div>
            <div class="requirement" id="req-case">
              <span class="req-icon"></span>
              <span class="req-text">Upper & lowercase letters</span>
            </div>
            <div class="requirement" id="req-number">
              <span class="req-icon"></span>
              <span class="req-text">At least one number</span>
            </div>
            <div class="requirement" id="req-special">
              <span class="req-icon"></span>
              <span class="req-text">At least one special character</span>
            </div>
          </div>
        </div>
        <div class="input-wrapper">
          <label class="input-label">Confirm Password</label>
          <div class="password-container">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password">
            <button type="button" class="eye-icon" onclick="togglePassword('confirm_password')"></button>
          </div>
          <span class="error-message" id="confirm_password_error">This field is required</span>
          <div class="password-match">
            <span id="match-text"></span>
          </div>
        </div>
      </div>

      <div class="checkbox-wrapper">
        <input type="checkbox" id="terms" name="terms">
        <label for="terms">I agree with <a class="terms-vis" href="#">Terms and Conditions</a></label>
      </div>
      <p class="error-message" id="terms_error" style="text-align: center; margin-top: 8px;"></p>

      <button type="submit" class="create-btn">CREATE</button>
    </form>

    <div class="login-text">
      Already have an account? <a href="login.php">Log In</a>
    </div>
  </div>

  <div class="right">
    <div class="circle-bg circle-1"></div>
    <div class="circle-bg circle-2"></div>
    
    <div class="right-content">
      <p class="welcome-text">Welcome to</p>
      <h1>EVERGREEN</h1>
      <p class="subtitle">Sign up to create an account!</p>
      <img src="images/laptop.png" alt="Laptop" class="laptop-img">
    </div>
  </div>

    <!-- Modal -->
   <div class="modal-container" style="display:
   none;">
    <div class="popup">
        <div class="head-logo">
            <img src="images/Logo.png.png" alt="logo" class="logo-popup">
            <div class="head-wrap">
              <h4 id="web-title">EVERGREEN</h4>
              <p id="web-catch">Secure Invest Achieve</p>  
            </div>
        </div>
        <div class="join-us">
            <h1 class="join-text">Terms and Agreements</h1>
            <div class="terms-body">
              <!-- Place the terms here -->
            <h1 class="heading">Terms and Agreement</h1>
            <p class="sub-heading">Please review our terms and conditions carefully before proceeding</p>
            <div class="body-container">
              <div class="wrap-tnc">
                <h3 class="conditions-head">1. Overview</h3>
                <p class="conditions-para">
                  Welcome to our platform. These Terms and Agreement (“Terms”) outline the rules, conditions, and guidelines for accessing and using our services. By using or accessing this platform in any manner, you acknowledge that you have read, understood, and agreed to be bound by these Terms. If you do not agree with any part of these Terms, you must discontinue your use of the platform immediately.
                </p>
              </div>
              <div class="wrap-tnc">
                <h3 class="conditions-head">2. Acceptance of Terms</h3>
                <p class="conditions-para">By creating an account, accessing the platform, or using any of our services, you agree to comply with these Terms. Your continued use of the platform signifies your acceptance of any updated or modified Terms that may be implemented in the future. We reserve the right to update, change, or replace any part of these Terms at any time without prior notice. It is your responsibility to review these Terms periodically for changes.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">3. User Responsibilities</h3>
                <p class="conditions-para">As a user, you agree to:</p>
                <p class="conditions-para">Use the platform in a lawful and ethical manner.</p>
                <p class="conditions-para">Provide accurate, complete, and up-to-date information when required.</p>
                <p class="conditions-para">Maintain the confidentiality of any login credentials and be responsible for all activities under your account.</p>
                <p class="conditions-para">Avoid any actions that may disrupt, damage, or impair the platform’s services, security, or performance.</p>
                <p class="conditions-para">You are strictly prohibited from engaging in fraudulent activities, unauthorized access, hacking, distributing malicious software, or interfering with the proper functioning of the platform.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">4. Privacy &amp; Data Protection</h3>
                <p class="conditions-para">Your privacy is important to us. Any personal data collected during your use of this platform will be handled in accordance with our Privacy Policy. By using the platform, you consent to the collection, storage, use, and disclosure of your information as described in the Privacy Policy. We will take reasonable measures to protect your data; however, we cannot guarantee absolute security due to the nature of digital communications.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">5. Intellectual Property Rights</h3>
                <p class="conditions-para">All content available on this platform—including but not limited to text, images, graphics, logos, icons, design layout, software, and other materials—is the property of the platform or its licensors and is protected by copyright, trademark, and other intellectual property laws. You agree not to copy, reproduce, distribute, modify, or create derivative works from any content on the platform without prior written consent from the rightful owner.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">6. Restrictions &amp; Limitations</h3>
                <p class="conditions-para">You agree NOT to:</p>
                <p class="conditions-para">Use the platform for illegal, harmful, abusive, or misleading purposes.</p>
                <p class="conditions-para">Impersonate any person, entity, or misrepresent your affiliation.</p>
                <p class="conditions-para">Reverse-engineer, decompile, or tamper with the platform’s systems or features.</p>
                <p class="conditions-para">Upload or transmit any viruses, malware, or harmful code.</p>
                <p class="conditions-para">We reserve the right to restrict access, suspend, or terminate accounts found violating these Terms or engaging in suspicious activities.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">7. Termination of Use</h3>
                <p class="conditions-para">We may, at our sole discretion and without prior notice, suspend or terminate your access to the platform if we believe you have violated these Terms, engaged in unlawful behavior, or acted in a way that may harm the platform, other users, or our reputation. Upon termination, all rights granted under these Terms will immediately cease, and you must stop all use of the platform.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">8. Disclaimer of Warranties</h3>
                <p class="conditions-para">The platform is provided on an “as is” and “as available” basis. We do not warrant that:</p>
                <p class="conditions-para">The platform will always be secure, uninterrupted, or error-free.</p>
                <p class="conditions-para">Any defects or issues will be corrected.</p>
                <p class="conditions-para">The information provided is accurate, reliable, or up-to-date.</p>
                <p class="conditions-para">Your use of the platform is solely at your own risk. We disclaim all warranties, whether express or implied, including fitness for a particular purpose, merchantability, and non-infringement.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">9. Limitation of Liability</h3>
                <p class="conditions-para">To the fullest extent permitted by law, we shall not be liable for any damages—direct, indirect, incidental, special, consequential, or exemplary—that arise from your use or inability to use the platform. This includes, but is not limited to, loss of data, loss of profits, system failure, or any other damages, even if we have been advised of the possibility of such damages.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">10. Amendments &amp; Modifications</h3>
                <p class="conditions-para">We reserve the right to modify, update, or discontinue any part of the platform, services, or these Terms at any time. Any changes will be effective immediately upon posting on the platform. Your continued use after changes are posted constitutes acceptance of the revised Terms. We are not obligated to notify users individually about modifications.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">11. Governing Law &amp; Dispute Resolution</h3>
                <p class="conditions-para">These Terms and any disputes arising from them shall be governed by and interpreted in accordance with local applicable laws. Any disagreements or claims shall be resolved through good-faith negotiation first. If unresolved, the issue shall be settled through the appropriate legal process or arbitration, depending on jurisdiction.</p>
              </div>

              <div class="wrap-tnc">
                <h3 class="conditions-head">12. Contact Information</h3>
                <p class="conditions-para">If you have questions, concerns, feedback, or require clarification regarding these Terms, you may contact us through the following channels:</p>
                <p class="conditions-para">Email: support@example.com</p>
                <p class="conditions-para">Phone: (000) 000-0000</p>
              </div>
            </div>
        </div>
            <div class="btn-wrap">
                <button class="action">Confirm</button>
            </div>
        <hr>
    </div>
    </div>
  </div>
  <style>
        /* Modal Popup */
        .modal-container {
            position: fixed;
            top: 0;
            left: 0;
            background-color: rgba(255, 255, 255, 0.4);
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .popup {
            background-color: #003631;
            color: white;
            padding: 25px;
            border-radius: 15px;
            width: 40%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .head-popup {
            display: flex;
            justify-content: flex-end;
        }

        .exit-btn {
            cursor: pointer;
            border: none;
        }

        .head-logo {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        #web-title {
            color: white;
        }

        .head-wrap {
            display: flex;
            flex-direction: column;
            gap: 2px;
            color: #F1B24A;
        }

        .logo-popup {
            width: 45px;
            height: 45px;
        }

        .join-us {
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .join-text {
            color: white;
        }

        .join-text {
            font-weight: 500;
            font-size: 25px;
            text-align: center;
        }

        .terms-body {
          width: 100%;
          height: 250px;       /* fixed height so overflow can occur */
          overflow: auto;      /* adds scrollbars when needed */
          border: 1px solid #ccc;
          padding: 10px;
          background-color: white;
          color: #003631;
          display: flex;
          flex-direction: column;
          gap: 10px;
          border-radius: 15px;
          scrollbar-width: thin;         /* Firefox scrollbar size */
          scrollbar-color: #888 #f1f1f1;
        }

        .terms-body::-webkit-scrollbar {
          width: 8px;                 /* scrollbar width */
        }

        .terms-body::-webkit-scrollbar-track {
          background: #f1f1f1;        /* scrollbar track */
          border-radius: 10px;        /* round track */
        }

        .terms-body::-webkit-scrollbar-thumb {
          background-color: #888;     /* scrollbar thumb color */
          border-radius: 10px;        /* round thumb */
          border: 2px solid #f1f1f1;  /* gives padding-like effect */
        }

        .terms-body::-webkit-scrollbar-thumb:hover {
          background-color: #555;     /* darker on hover */
        }

        .wrap-tnc {
        display: flex;
        flex-direction: column;
        gap: 8px; /* adds space between heading and paragraph */
        align-items: center; /* centers the paragraph horizontally */
        padding: 10px 20px;
        border-bottom: 1px solid #ddd;
      }

        .body-container {
          display: flex;
          flex-direction: column;
          gap: 15px;
        }

        .conditions-head {
          align-self: flex-start; /* move the heading to the left */
          font-weight: bold;
          color: #003631;
        }

        .conditions-para {
          text-align: left;
          max-width: 90%; /* keep paragraphs nicely contained */
          line-height: 1.6;
        }

        .heading, .sub-heading {
          color: #003631;
          text-align: center;
        }

        .heading {
          font-size: 25px;
        }

        .btn-wrap {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn-wrap a {
            text-decoration: none;
        }

        .action {
            font-size: larger;
            font-weight: 500;
            padding: 10px 40px;
            background-color: #F1B24A;
            color: #003631;
            cursor: pointer;
            border-radius: 20px;
        }
  </style>
  <script>
          termsVisible();

      function termsVisible() {
        const modalCont = document.querySelector(".modal-container");
        const termsBtn = document.querySelector(".terms-vis");
        const confirmBtn = document.querySelector(".action");
        const termsCheckbox = document.getElementById("terms");

        // Safety check
        if (!modalCont || !termsBtn || !confirmBtn || !termsCheckbox) {
          console.warn("One or more elements are missing from the DOM.");
          return;
        }

        // Open modal when clicking "Terms and Conditions"
        termsBtn.addEventListener("click", (e) => {
          e.preventDefault();
          modalCont.style.display = "flex";
        });

        // Close modal and check the checkbox
        confirmBtn.addEventListener("click", () => {
          modalCont.style.display = "none";
          termsCheckbox.checked = true;
          const termsError = document.getElementById("terms_error");
          if (termsError) {
            termsError.classList.remove('show');
          }
        });
      }

      function togglePassword(id) {
        const input = document.getElementById(id);
        const btn = input.nextElementSibling;
        if (input.type === 'password') {
          input.type = 'text';
          btn.innerHTML = '<i class="fa-solid fa-eye"></i>';
        } else {
          input.type = 'password';
          btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
        }
      }

      // Initialize
      document.addEventListener('DOMContentLoaded', function() {
        const eyeIcons = document.querySelectorAll('.eye-icon');
        eyeIcons.forEach(icon => {
          icon.innerHTML = '<i class="fa-solid fa-eye-slash"></i>'; // Start with regular eye
        });
      });

      // Password strength checker with requirements
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirm_password');

      if (password) {
        const requirements = document.getElementById('password-requirements');
        
        password.addEventListener('input', function() {
          const passwordValue = this.value;
          
          if (passwordValue.length > 0) {
            requirements.classList.add('show');
          } else {
            requirements.classList.remove('show');
          }
          
          const strengthFill = document.getElementById('strength-fill');
          const strengthText = document.getElementById('strength-text');
          
          const reqLength = document.getElementById('req-length');
          const reqCase = document.getElementById('req-case');
          const reqNumber = document.getElementById('req-number');
          const reqSpecial = document.getElementById('req-special');
          
          let strength = 0;
          
          // Length check
          if (passwordValue.length >= 8) {
            reqLength.classList.add('met');
            strength++;
          } else {
            reqLength.classList.remove('met');
          }
          
          // Upper & lowercase check
          if (passwordValue.match(/[a-z]/) && passwordValue.match(/[A-Z]/)) {
            reqCase.classList.add('met');
            strength++;
          } else {
            reqCase.classList.remove('met');
          }
          
          // Number check
          if (passwordValue.match(/[0-9]/)) {
            reqNumber.classList.add('met');
            strength++;
          } else {
            reqNumber.classList.remove('met');
          }
          
          // Special character check
          if (passwordValue.match(/[^a-zA-Z0-9]/)) {
            reqSpecial.classList.add('met');
            strength++;
          } else {
            reqSpecial.classList.remove('met');
          }
          
          // Update strength bar and text - FIXED VERSION
          // Remove all previous classes
          strengthFill.classList.remove('weak', 'medium', 'strong');
          
          if (passwordValue.length === 0) {
            strengthFill.style.width = '0%';
            strengthText.textContent = '';
          } else if (strength === 1) {
            strengthFill.classList.add('weak');
            strengthFill.style.width = '25%'; // Force width
            strengthText.textContent = 'Weak password';
            strengthText.style.color = '#dc3545';
          } else if (strength === 2) {
            strengthFill.classList.add('weak');
            strengthFill.style.width = '50%'; // Force width
            strengthText.textContent = 'Fair password';
            strengthText.style.color = '#ff6b6b';
          } else if (strength === 3) {
            strengthFill.classList.add('medium');
            strengthFill.style.width = '75%'; // Force width
            strengthText.textContent = 'Good password';
            strengthText.style.color = '#ffc107';
          } else if (strength === 4) {
            strengthFill.classList.add('strong');
            strengthFill.style.width = '100%'; // Force width
            strengthText.textContent = 'Strong password';
            strengthText.style.color = '#28a745';
          }
          
          // Check password match if confirm password has value
          if (confirmPassword && confirmPassword.value) {
            checkPasswordMatch();
          }
        });
      }

      // Password match checker
      if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordMatch);
      }

      function checkPasswordMatch() {
        const passwordValue = password.value;
        const confirmValue = confirmPassword.value;
        const matchText = document.getElementById('match-text');
        
        if (confirmValue.length === 0) {
          matchText.textContent = '';
          matchText.className = '';
          return;
        }
        
        if (passwordValue === confirmValue) {
          matchText.textContent = '✓ Passwords match';
          matchText.className = 'match';
        } else {
          matchText.textContent = '✕ Passwords do not match';
          matchText.className = 'no-match';
        }
      }

      // Signup Form Validation - THIS IS THE KEY FIX
      document.getElementById('signupForm').addEventListener('submit', function(e) {
        // ALWAYS prevent form submission first
        e.preventDefault();
        
        let isValid = true;
        
        // Required fields to validate
        const requiredFields = [
          'first_name',
          'middle_name',
          'last_name', 
          'address', 
          'city_province', 
          'email', 
          'contact_number', 
          'birthday', 
          'password', 
          'confirm_password'
        ];
        
        // Validate ALL required fields
        requiredFields.forEach(fieldId => {
          const field = document.getElementById(fieldId);
          const errorMsg = document.getElementById(fieldId + '_error');
          
          if (!field || !errorMsg) return;
          
          if (!field.value.trim()) {
            field.classList.add('error');
            errorMsg.textContent = 'This field is required';
            errorMsg.classList.add('show');
            isValid = false;
          } else {
            field.classList.remove('error');
            errorMsg.classList.remove('show');
          }
        });
        
        // Validate email format
        const emailField = document.getElementById('email');
        const emailError = document.getElementById('email_error');
        if (emailField && emailField.value.trim()) {
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          if (!emailPattern.test(emailField.value)) {
            emailField.classList.add('error');
            emailError.textContent = 'Please enter a valid email address';
            emailError.classList.add('show');
            isValid = false;
          }
        }
        
        // Validate password match
        if (password && confirmPassword && password.value && confirmPassword.value) {
          if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('error');
            const confirmError = document.getElementById('confirm_password_error');
            confirmError.textContent = 'Passwords do not match';
            confirmError.classList.add('show');
            isValid = false;
          }
        }
        
        // Validate terms checkbox - THIS RUNS REGARDLESS
        const terms = document.getElementById('terms');
        const termsError = document.getElementById('terms_error');
        if (terms && termsError) {
          if (!terms.checked) {
            termsError.textContent = '⚠ You must agree to the Terms and Conditions';
            termsError.classList.add('show');
            termsError.style.display = 'block';
            termsError.style.color = '#dc3545';
            termsError.style.fontSize = '13px';
            termsError.style.marginTop = '8px';
            termsError.style.textAlign = 'center';
            isValid = false;
          } else {
            termsError.classList.remove('show');
            termsError.style.display = 'none';
            termsError.textContent = '';
          }
        }
        
        // Only submit if everything is valid
        if (isValid) {
          // All validations passed - actually submit the form
          this.submit();
        } else {
          // Scroll to first error
          const firstError = document.querySelector('input.error, .error-message.show');
          if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const firstErrorInput = document.querySelector('input.error');
            if (firstErrorInput) {
              setTimeout(() => firstErrorInput.focus(), 300);
            }
          }
        }
      });

      // Remove error styling on input
      document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="date"]').forEach(input => {
        input.addEventListener('input', function() {
          if (this.value.trim()) {
            this.classList.remove('error');
            const errorMsg = document.getElementById(this.id + '_error');
            if (errorMsg) {
              errorMsg.classList.remove('show');
            }
          }
        });
      });

      // Remove terms error on checkbox change
      document.getElementById('terms').addEventListener('change', function() {
        const termsError = document.getElementById('terms_error');
        if (this.checked && termsError) {
          termsError.classList.remove('show');
          termsError.style.display = 'none';
        }
      });
</script>
</body>
</html>