<?php
session_start();
include("db_connect.php");

// Clear forgot password session if user navigates away and comes back fresh
if (!isset($_POST['verify_email']) && !isset($_POST['verify_otp']) && 
    !isset($_POST['reset_password']) && !isset($_POST['resend_otp'])) {
    // User is visiting the page without submitting a form - reset the flow
    if (!isset($_GET['continue'])) {
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_time']);
        unset($_SESSION['otp_verified']);
    }
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-7.0.0/src/Exception.php';
require 'PHPMailer-7.0.0/src/PHPMailer.php';
require 'PHPMailer-7.0.0/src/SMTP.php';


$error = "";
$success = false;
$step = 1; // Step 1: Email verification, Step 2: OTP verification, Step 3: Reset password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_email'])) {
        // Step 1: Verify email and bank ID, send OTP
        $email = trim($_POST['email']);
        $bank_id = trim($_POST['bank_id']);
        
        if (empty($email) || empty($bank_id)) {
            $error = "Please fill in all fields.";
        } else {
            $sql = "SELECT id, first_name FROM bank_users WHERE email = ? AND bank_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $email, $bank_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $row = $result->fetch_assoc();
                
                // Generate 6-digit OTP
                $otp = sprintf("%06d", mt_rand(0, 999999));
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_user_id'] = $row['id'];
                $_SESSION['reset_email'] = $email;
                $_SESSION['otp_time'] = time();
                
                // Send OTP via PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP host
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'evrgrn.64@gmail.com'; // Your email
                    $mail->Password   = 'dourhhbymvjejuct'; // Your app password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    
                    // Recipients
                    $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
                    $mail->addAddress($email, $row['first_name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset - Verification Code';
                    $mail->Body    = '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                        <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                            <h1 style="color: white; margin: 0;">Password Reset</h1>
                        </div>
                        <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                            <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($row['first_name']) . '</strong>,</p>
                            <p style="font-size: 14px; color: #666;">You requested to reset your password. Use the verification code below:</p>
                            <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                                <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $otp . '</h2>
                            </div>
                            <p style="font-size: 13px; color: #666;">This code will expire in <strong>10 minutes</strong>.</p>
                            <p style="font-size: 13px; color: #666;">If you didn\'t request this, please ignore this email.</p>
                            <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
                            <p style="font-size: 12px; color: #999; text-align: center;">Best regards,<br>Evergreen Bank Team</p>
                        </div>
                    </div>
                    ';
                    $mail->AltBody = "Hello " . $row['first_name'] . ",\n\nYour password reset verification code is: " . $otp . "\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nEvergreen Bank";
                    
                    $mail->send();
                    $step = 2;
                } catch (Exception $e) {
                    $error = "Failed to send verification code. Please try again.";
                    // Optional: Log the error for debugging
                    // error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            } else {
                $error = "No account found with this email and Bank ID.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['verify_otp'])) {
        // Step 2: Verify OTP
        $otp_input = trim($_POST['otp']);
        
        if (empty($otp_input)) {
            $error = "Please enter the verification code.";
            $step = 2;
        } elseif (!isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_time'])) {
            $error = "Session expired. Please start over.";
            $step = 1;
        } elseif ((time() - $_SESSION['otp_time']) > 600) { // 10 minutes
            $error = "Verification code expired. Please request a new one.";
            unset($_SESSION['reset_otp']);
            unset($_SESSION['otp_time']);
            $step = 1;
        } elseif ($otp_input !== $_SESSION['reset_otp']) {
            $error = "Invalid verification code. Please try again.";
            $step = 2;
        } else {
            // OTP verified successfully
            $_SESSION['otp_verified'] = true;
            $step = 3;
        }
    } elseif (isset($_POST['reset_password'])) {
        // Step 3: Reset password
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            $error = "Please verify your email first.";
            $step = 1;
        } else {
            $new_password = trim($_POST['new_password']);
            $confirm_password = trim($_POST['confirm_password']);
            
            if (empty($new_password) || empty($confirm_password)) {
                $error = "Please fill in all fields.";
                $step = 3;
            } elseif ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
                $step = 3;
            } elseif (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters long.";
                $step = 3;
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $user_id = $_SESSION['reset_user_id'];
                
                $sql = "UPDATE bank_users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = true;
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_otp']);
                    unset($_SESSION['otp_time']);
                    unset($_SESSION['otp_verified']);
                } else {
                    $error = "Failed to reset password. Please try again.";
                    $step = 3;
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Resend OTP
        if (isset($_SESSION['reset_email']) && isset($_SESSION['reset_user_id'])) {
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['otp_time'] = time();
            
            $sql = "SELECT first_name FROM bank_users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['reset_user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Send OTP via PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'evrgrn.64@gmail.com';
                $mail->Password   = 'dourhhbymvjejuct';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                // Recipients
                $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
                $mail->addAddress($_SESSION['reset_email'], $row['first_name']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - New Verification Code';
                $mail->Body    = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">New Verification Code</h1>
                    </div>
                    <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                        <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($row['first_name']) . '</strong>,</p>
                        <p style="font-size: 14px; color: #666;">You requested a new verification code. Here it is:</p>
                        <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                            <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $otp . '</h2>
                        </div>
                        <p style="font-size: 13px; color: #666;">This code will expire in <strong>10 minutes</strong>.</p>
                        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
                        <p style="font-size: 12px; color: #999; text-align: center;">Best regards,<br>Evergreen Bank Team</p>
                    </div>
                </div>
                ';
                $mail->AltBody = "Hello " . $row['first_name'] . ",\n\nYour new password reset verification code is: " . $otp . "\n\nThis code will expire in 10 minutes.\n\nBest regards,\nEvergreen Bank";
                
                $mail->send();
                $step = 2;
            } catch (Exception $e) {
                $error = "Failed to resend verification code. Please try again.";
            }
            
            $stmt->close();
        }
    }
}

// Check current step from session
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    $step = 3;
} elseif (isset($_SESSION['reset_otp'])) {
    $step = 2;
}

// Handle session clearing request from JavaScript
if (isset($_GET['clear_session'])) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['otp_time']);
    unset($_SESSION['otp_verified']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evergreen - Forgot Password</title>
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
      background-image: url(images/bg-image.png);
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
      padding: 20px;
    }

    .container {
      width: 100%;
      max-width: 480px;
      margin: auto;
      background: white;
      border-radius: 24px;
      padding: 50px 40px;
      box-shadow: 0 20px 60px rgba(0, 54, 49, 0.08);
      position: relative;
    }

    .logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 40px;
    }

    .logo img {
      width: 48px;
      height: 48px;
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }

    .logo-text .name {
      font-size: 18px;
      font-weight: 700;
      color: #003631;
      letter-spacing: 0.5px;
    }

    .logo-text .tagline {
      font-size: 11px;
      color: #666;
      letter-spacing: 0.2px;
    }

    .back-link {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 24px;
      text-decoration: none;
      color: #003631;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s ease;
      background: #f8f9fa;
    }

    .back-link:hover {
      background: #e9ecef;
      transform: scale(1.05);
    }

    .icon-container {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 30px;
      font-size: 2.2rem;
      box-shadow: 0 10px 30px rgba(0, 54, 49, 0.2);
    }

    h2 {
      color: #003631;
      font-size: 32px;
      font-weight: 600;
      margin-bottom: 12px;
      text-align: center;
      letter-spacing: -0.5px;
    }

    .subtitle {
      text-align: center;
      color: #666;
      font-size: 14px;
      margin-bottom: 40px;
      line-height: 1.6;
    }

    .step-indicator {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 40px;
      gap: 12px;
    }

    .step {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #e9ecef;
      color: #666;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s;
      position: relative;
    }

    .step.active {
      background: #003631;
      color: white;
      transform: scale(1.15);
      box-shadow: 0 4px 12px rgba(0, 54, 49, 0.3);
    }

    .step.completed {
      background: #1a6b62;
      color: white;
    }

    .step.completed::after {
      content: '✓';
      position: absolute;
      font-size: 14px;
    }

    .step-line {
      width: 50px;
      height: 2px;
      background: #e9ecef;
      transition: all 0.3s;
    }

    .step-line.completed {
      background: #1a6b62;
    }

    .error-banner {
      background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
      border: 1px solid #dc3545;
      color: #dc3545;
      padding: 14px 18px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-size: 13px;
      text-align: center;
      font-weight: 500;
    }

    .input-wrapper {
      margin-bottom: 24px;
    }

    .input-label {
      font-size: 13px;
      color: #003631;
      margin-bottom: 8px;
      display: block;
      font-weight: 600;
    }

    input {
      width: 100%;
      padding: 16px 18px;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      font-size: 14px;
      background: #f8f9fa;
      color: #333;
      transition: all 0.2s;
      font-family: inherit;
    }

    input::placeholder {
      color: #adb5bd;
    }

    input:focus {
      outline: none;
      border-color: #003631;
      background: white;
      box-shadow: 0 0 0 3px rgba(0, 54, 49, 0.1);
    }

    input.error {
      border-color: #dc3545;
      background: #fff5f5;
    }

    .otp-container {
      display: flex;
      gap: 12px;
      justify-content: center;
      margin-bottom: 24px;
    }

    .otp-input {
      width: 56px;
      height: 56px;
      text-align: center;
      font-size: 24px;
      font-weight: 600;
      border: 2px solid #e9ecef;
      border-radius: 12px;
      background: #f8f9fa;
      transition: all 0.2s;
    }

    .otp-input:focus {
      border-color: #003631;
      background: white;
      box-shadow: 0 0 0 3px rgba(0, 54, 49, 0.1);
      outline: none;
    }

    .password-container {
      position: relative;
    }

    .eye-icon {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      width: 20px;
      height: 20px;
      background: none;
      border: none;
      padding: 0;
      font-size: 18px;
      color: #666;
      transition: color 0.2s;
    }

    .eye-icon:hover {
      color: #003631;
    }

    .password-strength {
      margin-top: 10px;
      font-size: 12px;
    }

    .strength-bar {
      height: 4px;
      background: #e9ecef;
      border-radius: 2px;
      margin-top: 6px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      width: 0%;
      transition: all 0.3s;
      border-radius: 2px;
    }

    .strength-fill.weak {
      width: 33%;
      background: #dc3545;
    }

    .strength-fill.medium {
      width: 66%;
      background: #ffc107;
    }

    .strength-fill.strong {
      width: 100%;
      background: #28a745;
    }

    .submit-btn {
      width: 100%;
      background: linear-gradient(135deg, #003631 0%, #1a6b62 100%);
      color: white;
      padding: 16px;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: all 0.3s;
      margin-top: 10px;
      box-shadow: 0 4px 12px rgba(0, 54, 49, 0.2);
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 54, 49, 0.3);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    .resend-link {
      text-align: center;
      margin-top: 20px;
      font-size: 13px;
      color: #666;
    }

    .resend-link button {
      background: none;
      border: none;
      color: #003631;
      font-weight: 600;
      cursor: pointer;
      text-decoration: underline;
      font-size: 13px;
    }

    .resend-link button:hover {
      color: #1a6b62;
    }

    .timer {
      color: #dc3545;
      font-weight: 600;
    }

    .back-to-login {
      text-align: center;
      margin-top: 30px;
      font-size: 13px;
      color: #666;
    }

    .back-to-login a {
      color: #003631;
      text-decoration: none;
      font-weight: 600;
    }

    .back-to-login a:hover {
      text-decoration: underline;
    }

        /* Padlock */

    .padlock {
      width: 45px;
      height: 50px;
    }

    @media (max-width: 768px) {
      .container {
        padding: 40px 24px;
      }

      h2 {
        font-size: 28px;
      }

      .otp-input {
        width: 48px;
        height: 48px;
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <a href="login.php" class="back-link" onclick="clearForgotPasswordSession()">←</a>

    <div class="logo">
      <img src="images/loginlogo.png" alt="Logo">
      <div class="logo-text">
        <span class="name">EVERGREEN</span>
        <span class="tagline">Secure. Invest. Achieve</span>
      </div>
    </div>

    <div class="icon-container">
      <img src="images/padlock.png" alt="lock_icon" class="padlock">
    </div>

    <?php if ($step == 1): ?>
      <h2>Forgot Password?</h2>
      <p class="subtitle">Enter your email and Bank ID to receive a verification code</p>

      <div class="step-indicator">
        <div class="step active">1</div>
        <div class="step-line"></div>
        <div class="step">2</div>
        <div class="step-line"></div>
        <div class="step">3</div>
      </div>

      <?php if ($error): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" id="verifyForm">
        <div class="input-wrapper">
          <label class="input-label">Bank ID</label>
          <input type="text" name="bank_id" id="bank_id" placeholder="Enter your Bank ID" required>
        </div>

        <div class="input-wrapper">
          <label class="input-label">Email Address</label>
          <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
        </div>

        <button type="submit" name="verify_email" class="submit-btn">SEND VERIFICATION CODE</button>
      </form>

    <?php elseif ($step == 2): ?>
      <h2>Verify Email</h2>
      <p class="subtitle">We've sent a 6-digit code to<br><strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

      <div class="step-indicator">
        <div class="step completed">1</div>
        <div class="step-line completed"></div>
        <div class="step active">2</div>
        <div class="step-line"></div>
        <div class="step">3</div>
      </div>

      <?php if ($error): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" id="otpForm">
        <div class="otp-container">
          <input type="text" class="otp-input" maxlength="1" id="otp1" autocomplete="off">
          <input type="text" class="otp-input" maxlength="1" id="otp2" autocomplete="off">
          <input type="text" class="otp-input" maxlength="1" id="otp3" autocomplete="off">
          <input type="text" class="otp-input" maxlength="1" id="otp4" autocomplete="off">
          <input type="text" class="otp-input" maxlength="1" id="otp5" autocomplete="off">
          <input type="text" class="otp-input" maxlength="1" id="otp6" autocomplete="off">
        </div>
        <input type="hidden" name="otp" id="otp_hidden">

        <button type="submit" name="verify_otp" class="submit-btn">VERIFY CODE</button>
      </form>

      <div class="resend-link">
        Didn't receive the code? 
        <form method="POST" style="display: inline;" id="resendForm">
          <button type="submit" name="resend_otp" id="resendBtn">Resend Code</button>
        </form>
        <span id="timer"></span>
      </div>

    <?php else: ?>
      <h2>Reset Password</h2>
      <p class="subtitle">Create your new secure password</p>

      <div class="step-indicator">
        <div class="step completed">1</div>
        <div class="step-line completed"></div>
        <div class="step completed">2</div>
        <div class="step-line completed"></div>
        <div class="step active">3</div>
      </div>

      <?php if ($error): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" id="resetForm">
        <div class="input-wrapper">
          <label class="input-label">New Password</label>
          <div class="password-container">
            <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
            <button type="button" class="eye-icon" onclick="togglePassword('new_password')"></button>
          </div>
          <div class="password-strength">
            <div class="strength-bar">
              <div class="strength-fill" id="strength-fill"></div>
            </div>
            <span id="strength-text" style="color: #666; font-size: 12px; margin-top: 4px; display: block;"></span>
          </div>
        </div>

        <div class="input-wrapper">
          <label class="input-label">Confirm Password</label>
          <div class="password-container">
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
            <button type="button" class="eye-icon" onclick="togglePassword('confirm_password')"></button>
          </div>
        </div>

        <button type="submit" name="reset_password" class="submit-btn">RESET PASSWORD</button>
      </form>
    <?php endif; ?>

    <div class="back-to-login">
        Remember your password? <a href="login.php" onclick="clearForgotPasswordSession()">Log In</a>
    </div>
  </div>

  <script>
    function togglePassword(id) {
      const input = document.getElementById(id);
      const btn = input.nextElementSibling;
      if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '';
      } else {
        input.type = 'password';
        btn.textContent = '';
      }
    }

    // OTP Input handling
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        if (this.value.length === 1) {
          if (index < otpInputs.length - 1) {
            otpInputs[index + 1].focus();
          }
        }
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value) {
          if (index > 0) {
            otpInputs[index - 1].focus();
          }
        }
      });

      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text');
        const digits = pastedData.replace(/\D/g, '').slice(0, 6);
        digits.split('').forEach((digit, i) => {
          if (otpInputs[i]) {
            otpInputs[i].value = digit;
          }
        });
        if (digits.length === 6) {
          otpInputs[5].focus();
        }
      });
    });

    // OTP Form submission
    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
      otpForm.addEventListener('submit', function(e) {
        let otp = '';
        otpInputs.forEach(input => {
          otp += input.value;
        });
        document.getElementById('otp_hidden').value = otp;
      });

      // Countdown timer for resend
      let countdown = 60;
      const timerElement = document.getElementById('timer');
      const resendBtn = document.getElementById('resendBtn');
      
      function updateTimer() {
        if (countdown > 0) {
          timerElement.innerHTML = `<span class="timer">(${countdown}s)</span>`;
          resendBtn.disabled = true;
          resendBtn.style.opacity = '0.5';
          resendBtn.style.cursor = 'not-allowed';
          countdown--;
          setTimeout(updateTimer, 1000);
        } else {
          timerElement.textContent = '';
          resendBtn.disabled = false;
          resendBtn.style.opacity = '1';
          resendBtn.style.cursor = 'pointer';
        }
      }
      
      updateTimer();
    }

    // Password strength checker
    const newPassword = document.getElementById('new_password');
    if (newPassword) {
      newPassword.addEventListener('input', function() {
        const password = this.value;
        const strengthFill = document.getElementById('strength-fill');
        const strengthText = document.getElementById('strength-text');
        
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        strengthFill.className = 'strength-fill';
        if (strength <= 1) {
          strengthFill.classList.add('weak');
          strengthText.textContent = 'Weak password';
          strengthText.style.color = '#dc3545';
        } else if (strength <= 3) {
          strengthFill.classList.add('medium');
          strengthText.textContent = 'Medium password';
          strengthText.style.color = '#ffc107';
        } else {
          strengthFill.classList.add('strong');
          strengthText.textContent = 'Strong password';
          strengthText.style.color = '#28a745';
        }
      });
    }

    // Show success modal
    <?php if ($success): ?>
      showSuccessModal();
    <?php endif; ?>

    function showSuccessModal() {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 54, 49, 0.85);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        animation: fadeIn 0.3s ease;
      `;
      
      modal.innerHTML = `
        <style>
          @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          @keyframes slideUp {
            from { 
              opacity: 0;
              transform: translateY(30px) scale(0.9);
            }
            to { 
              opacity: 1;
              transform: translateY(0) scale(1);
            }
          }
          @keyframes checkmark {
            0% { transform: scale(0) rotate(0deg); }
            50% { transform: scale(1.2) rotate(180deg); }
            100% { transform: scale(1) rotate(360deg); }
          }
        </style>
        <div style="
          background: white;
          padding: 3rem;
          border-radius: 24px;
          box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
          max-width: 440px;
          width: 90%;
          text-align: center;
          animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <div style="
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3.5rem;
            animation: checkmark 0.6s ease 0.2s backwards;
          ">✓</div>
          
          <h3 style="
            color: #003631;
            margin-bottom: 0.75rem;
            font-size: 2rem;
            font-weight: 600;
          ">Success!</h3>
          
          <p style="
            color: #666;
            margin-bottom: 0;
            font-size: 1rem;
            line-height: 1.6;
          ">Your password has been reset successfully.<br>Redirecting to login page...</p>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 2500);
    }

    // Form validation
    const verifyForm = document.getElementById('verifyForm');
    if (verifyForm) {
      verifyForm.addEventListener('submit', function(e) {
        const bankId = document.getElementById('bank_id');
        const email = document.getElementById('email');
        
        if (!bankId.value.trim() || !email.value.trim()) {
          e.preventDefault();
          if (!bankId.value.trim()) bankId.classList.add('error');
          if (!email.value.trim()) email.classList.add('error');
        }
      });
    }

    const resetForm = document.getElementById('resetForm');
    if (resetForm) {
      resetForm.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword.value.length < 8) {
          e.preventDefault();
          newPassword.classList.add('error');
        }
        
        if (confirmPassword.value !== newPassword.value) {
          e.preventDefault();
          confirmPassword.classList.add('error');
        }
      });
    }

    // Remove error styling on input
    document.querySelectorAll('input').forEach(input => {
      input.addEventListener('input', function() {
        this.classList.remove('error');
      });
    });

    function clearForgotPasswordSession() {
  // Make a quick request to clear the session
  fetch('forgotpassword.php?clear_session=1')
    .then(() => {
      window.location.href = 'login.php';
    })
    .catch(() => {
      window.location.href = 'login.php';
    });
  return false;
}
  </script>
</body>
</html>