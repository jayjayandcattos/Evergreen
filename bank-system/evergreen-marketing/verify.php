<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-7.0.0/src/Exception.php';
require 'PHPMailer-7.0.0/src/PHPMailer.php';
require 'PHPMailer-7.0.0/src/SMTP.php';

session_start();
include("db_connect.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has registration data in session OR if account was just created
if (!isset($_SESSION['temp_registration']) && !isset($_SESSION['show_success_modal'])) {
    header("Location: signup.php");
    exit;
}

$error = "";
$success = "";

// Check if there was an email error from signup
if (isset($_SESSION['email_error'])) {
    $error = $_SESSION['email_error'];
    unset($_SESSION['email_error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify'])) {
        $entered_code = trim($_POST['code1'] . $_POST['code2'] . $_POST['code3'] . $_POST['code4'] . $_POST['code5'] . $_POST['code6']);
        $registration_data = $_SESSION['temp_registration'];
        
        // Debug logging
        error_log("Entered code: " . $entered_code);
        error_log("Stored code: " . $registration_data['verification_code']);
        
        // Check if entered code matches the stored verification code
        if ($entered_code === $registration_data['verification_code']) {
            error_log("About to insert user with referral code: " . ($registration_data['referral_code'] ?? 'NOT SET'));
    
           if (!isset($registration_data['referral_code']) || empty($registration_data['referral_code'])) {
              error_log("WARNING: Referral code is missing from registration data!");
              $error = "System error: Referral code not generated. Please try again.";
          } else 
            // NOW insert the user into the database
                $sql = "INSERT INTO bank_customers (first_name, middle_name, last_name, address, city_province, email, contact_number, birthday, password, verification_code, bank_id, referral_code, total_points, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1)";

                $stmt = $conn->prepare($sql);
        if ($stmt) {
              $stmt->bind_param("ssssssssssss",  // Changed from 11 's' to 12 's'
        $registration_data['first_name'],
       $registration_data['middle_name'],
              $registration_data['last_name'],
              $registration_data['address'],
              $registration_data['city_province'],
              $registration_data['email'],
              $registration_data['contact_number'],
              $registration_data['birthday'],
              $registration_data['password'],
              $registration_data['verification_code'],
              $registration_data['bank_id'],
              $registration_data['referral_code']  // ⭐ ADD THIS LINE
              );
                
                if ($stmt->execute()) {
                    error_log("Account created successfully for: " . $registration_data['email']);
                    
                    // Set success flag BEFORE clearing temp data
                    $_SESSION['account_created'] = true;
                    $_SESSION['show_success_modal'] = true;
                    $_SESSION['created_account_email'] = $registration_data['email'];
                    
                    // Clear temp session data
                    unset($_SESSION['temp_registration']);
                    
                    // Set flag to show modal on page reload
                    $show_success = true;
                } else {
                    $error = "Failed to create account: " . $stmt->error;
                    error_log("Database error: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $error = "Database preparation error: " . $conn->error;
                error_log("Prepare error: " . $conn->error);
            }
        } else {
            $error = "Invalid verification code. Please try again.";
        }
    } elseif (isset($_POST['resend'])) {
        // Generate new verification code
        $new_code = sprintf("%06d", rand(0, 999999));
        
        // Update the verification code in session
        $_SESSION['temp_registration']['verification_code'] = $new_code;
        $registration_data = $_SESSION['temp_registration'];
        
        // Send new verification code via email
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
            $mail->addAddress($registration_data['email'], $registration_data['first_name'] . ' ' . $registration_data['last_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Evergreen - New Verification Code';
            $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2 style='color: #0d3d38;'>New Verification Code</h2>
                    <p>You requested a new verification code.</p>
                    
                    <div style='background: #f5f5f5; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                        <h3 style='color: #0d3d38; margin-bottom: 10px;'>Bank ID: <span style='color: #1a6b62;'>{$registration_data['bank_id']}</span></h3>
                        <h3 style='color: #0d3d38;'>Verification Code:</h3>
                        <h1 style='color: #0d3d38; letter-spacing: 5px; font-size: 36px;'>{$new_code}</h1>
                    </div>
                    
                    <p>This code will expire in 10 minutes.</p>
                    <p style='color: #666; font-size: 12px;'>If you didn't request this code, please ignore this email.</p>
                </body>
                </html>
            ";
            
            error_log("Attempting to send email to: " . $registration_data['email']);
            $mail->send();
            $success = "A new verification code has been sent to your email.";
            error_log("Email sent successfully to: " . $registration_data['email']);
        } catch (Exception $e) {
            $error = "Could not send email. Mailer Error: {$mail->ErrorInfo}";
            error_log("Failed to send email: " . $mail->ErrorInfo);
        }
    }
}

$email = $_SESSION['temp_registration']['email'] ?? '';

// Check if we should show success modal
$show_success = false;
if (isset($_SESSION['show_success_modal'])) {
    $show_success = true;
    $email = $_SESSION['created_account_email'] ?? '';
    unset($_SESSION['show_success_modal']);
    unset($_SESSION['created_account_email']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evergreen - Verification</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
      background-image: url(images/bg-image.png);
        background-repeat: no-repeat;
        background-size: cover;
        background-attachment: fixed;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 60px;
    }

    .logo-container img {
      width: 48px;
      height: 48px;
    }

    .logo-text {
      display: flex;
      flex-direction: column;
    }

    .logo-text .name {
      font-size: 16px;
      font-weight: 700;
      color: #0d3d38;
      letter-spacing: 0.3px;
    }

    .logo-text .tagline {
      font-size: 10px;
      color: #666;
      letter-spacing: 0.2px;
    }

    .verify-container {
      background: white;
      padding: 50px 60px;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      max-width: 600px;
      width: 100%;
      text-align: center;
      position: relative;
      align-content: center;
      margin-top: 5%;
    }

    .back-to-login {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 24px;
      text-decoration: none;
      color: #0d3d38;
      transition: all 0.2s ease;
      padding: 8px 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
    }

    .back-to-login:hover {
      background: rgba(13, 61, 56, 0.1);
      transform: translateX(2px);
    }

    h2 {
      color: #0d3d38;
      font-size: 32px;
      font-weight: 600;
      margin-bottom: 40px;
      letter-spacing: -0.5px;
    }

    .code-inputs {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 30px;
    }

    .code-input {
      width: 70px;
      height: 80px;
      border: 1.5px solid #c5c1b3;
      border-radius: 12px;
      font-size: 32px;
      font-weight: 600;
      text-align: center;
      color: #0d3d38;
      transition: all 0.2s;
      background: #faf9f6;
    }

    .code-input:focus {
      outline: none;
      border-color: #0d3d38;
      background: white;
    }

    .info-text {
      font-size: 13px;
      color: #666;
      margin-bottom: 35px;
      line-height: 1.6;
    }

    .info-text .email {
      color: #0d3d38;
      font-weight: 600;
    }

    .alert {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 13px;
      text-align: center;
    }

    .alert.error {
      background: #fee;
      color: #c33;
      border: 1px solid #fcc;
    }

    .alert.success {
      background: #efe;
      color: #3c3;
      border: 1px solid #cfc;
    }

    .confirm-btn {
      width: 100%;
      background: #0d3d38;
      color: white;
      padding: 15px;
      border: none;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: all 0.2s;
      margin-bottom: 25px;
    }

    .confirm-btn:hover {
      background: #0a2d29;
    }

    .confirm-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .resend-section {
      font-size: 12px;
      color: #666;
    }

    .resend-section .resend-link {
      color: #0d3d38;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      border: none;
      background: none;
      padding: 0;
      font-family: inherit;
      font-size: inherit;
    }

    .resend-section .resend-link:hover {
      text-decoration: underline;
    }

    .timer {
      color: #999;
      font-size: 11px;
      margin-top: 5px;
    }

    @media (max-width: 768px) {
      .verify-container {
        padding: 40px 30px;
      }

      h2 {
        font-size: 28px;
      }

      .code-inputs {
        gap: 10px;
      }

      .code-input {
        width: 50px;
        height: 60px;
        font-size: 24px;
      }
    }
  </style>
</head>
<body>
  <div class="logo-container">
    <img src="images/loginlogo.png" alt="Logo">
    <div class="logo-text">
      <span class="name">EVERGREEN</span>
      <span class="tagline">Secure. Invest. Achieve</span>
    </div>
  </div>

  <div class="verify-container">
    <a href="login.php" class="back-to-login" title="Back to Login">←</a>
    
    <h2>Verification Code</h2>

    <?php if (!empty($error)): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!$show_success): ?>
    <form method="POST" id="verifyForm">
      <div class="code-inputs">
        <input type="text" class="code-input" name="code1" maxlength="1" pattern="[0-9]" required autocomplete="off">
        <input type="text" class="code-input" name="code2" maxlength="1" pattern="[0-9]" required autocomplete="off">
        <input type="text" class="code-input" name="code3" maxlength="1" pattern="[0-9]" required autocomplete="off">
        <input type="text" class="code-input" name="code4" maxlength="1" pattern="[0-9]" required autocomplete="off">
        <input type="text" class="code-input" name="code5" maxlength="1" pattern="[0-9]" required autocomplete="off">
        <input type="text" class="code-input" name="code6" maxlength="1" pattern="[0-9]" required autocomplete="off">
      </div>

      <p class="info-text">
        We've sent a 6-digit verification code to <span class="email"><?= htmlspecialchars($email) ?></span>.<br>
        Please enter the code above to continue.
      </p>

      <button type="submit" name="verify" class="confirm-btn">CONFIRM</button>
    </form>

    <div class="resend-section">
      Didn't receive the code?
      <form method="POST" style="display: inline;">
        <button type="submit" name="resend" class="resend-link" id="resendBtn">Resend Code</button>
      </form>
      <span id="timerText"></span>
      <div class="timer" id="timer"></div>
    </div>
    <?php else: ?>
    <p style="font-size: 14px; color: #666;">Processing your account...</p>
    <?php endif; ?>
  </div>

  <script>
    // Show success modal if account was created
    <?php if ($show_success): ?>
      console.log("Showing success modal");
      showSuccessModal();
    <?php endif; ?>

    function showSuccessModal() {
      console.log("showSuccessModal called");
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
        animation: fadeIn 0.4s ease;
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
            0% {
              transform: scale(0);
              opacity: 0;
            }
            50% {
              transform: scale(1.2);
            }
            100% {
              transform: scale(1);
              opacity: 1;
            }
          }
          @keyframes pulse {
            0%, 100% {
              box-shadow: 0 0 0 0 rgba(13, 61, 56, 0.4);
            }
            50% {
              box-shadow: 0 0 0 20px rgba(13, 61, 56, 0);
            }
          }
        </style>
        <div style="
          background: white;
          padding: 3rem 2.5rem;
          border-radius: 20px;
          box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
          max-width: 480px;
          width: 90%;
          text-align: center;
          animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        ">
          <div style="
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: checkmark 0.6s ease 0.3s backwards, pulse 2s ease 0.9s infinite;
            box-shadow: 0 10px 30px rgba(13, 61, 56, 0.3);
          ">
            <svg width="50" height="50" viewBox="0 0 50 50" style="animation: checkmark 0.6s ease 0.5s backwards;">
              <path d="M 10 25 L 20 35 L 40 15" stroke="white" stroke-width="4" fill="none" 
                    stroke-linecap="round" stroke-linejoin="round"
                    style="stroke-dasharray: 50; stroke-dashoffset: 50; animation: draw 0.5s ease 0.7s forwards;"/>
            </svg>
          </div>
          
          <style>
            @keyframes draw {
              to {
                stroke-dashoffset: 0;
              }
            }
          </style>
          
          <h3 style="
            color: #0d3d38;
            margin-bottom: 1rem;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
          ">Account Created Successfully!</h3>
          
          <p style="
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.05rem;
            line-height: 1.6;
          ">Welcome to <strong style="color: #0d3d38;">EVERGREEN Banking</strong>!<br>Your account has been verified and is ready to use.</p>
          
          <div style="
            background: #f0f9f8;
            border-left: 4px solid #1a6b62;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: left;
          ">
            <p style="
              color: #0d3d38;
              font-size: 0.9rem;
              margin: 0;
              line-height: 1.5;
            ">
              <strong>📧 Check your email</strong><br>
              <span style="color: #666;">Your Bank ID and login credentials have been sent to your email address.</span>
            </p>
          </div>
          
          <p style="
            color: #999;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
          ">Redirecting to login page in <span id="countdown" style="color: #0d3d38; font-weight: 600;">3</span> seconds...</p>
          
          <button onclick="window.location.href='login.php'" style="
            background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(13, 61, 56, 0.3);
            letter-spacing: 0.5px;
          " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(13, 61, 56, 0.4)'" 
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(13, 61, 56, 0.3)'">
            Go to Login Now
          </button>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      // Countdown timer
      let seconds = 3;
      const countdownElement = modal.querySelector('#countdown');
      const countdownInterval = setInterval(() => {
        seconds--;
        if (countdownElement) {
          countdownElement.textContent = seconds;
        }
        if (seconds <= 0) {
          clearInterval(countdownInterval);
        }
      }, 1000);
      
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 3000);
    }

    // Auto-focus and move to next input
    <?php if (!$show_success): ?>
    const inputs = document.querySelectorAll('.code-input');
    
    inputs.forEach((input, index) => {
      input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      });

      input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && index > 0) {
          inputs[index - 1].focus();
        }
      });

      // Only allow numbers
      input.addEventListener('keypress', (e) => {
        if (!/[0-9]/.test(e.key)) {
          e.preventDefault();
        }
      });

      // Handle paste
      input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').slice(0, 6);
        const digits = pastedData.match(/\d/g);
        
        if (digits) {
          digits.forEach((digit, i) => {
            if (inputs[i]) {
              inputs[i].value = digit;
            }
          });
          if (digits.length < 6) {
            inputs[digits.length].focus();
          }
        }
      });
    });

    // Auto-focus first input
    inputs[0].focus();

    // Resend timer
    let timeLeft = 30;
    const resendBtn = document.getElementById('resendBtn');
    const timer = document.getElementById('timer');
    const timerText = document.getElementById('timerText');

    function startTimer() {
      resendBtn.disabled = true;
      resendBtn.style.color = '#ccc';
      resendBtn.style.cursor = 'not-allowed';
      
      const interval = setInterval(() => {
        timeLeft--;
        timer.textContent = `(You can request a new code in ${timeLeft} seconds)`;
        timerText.textContent = '';
        
        if (timeLeft <= 0) {
          clearInterval(interval);
          resendBtn.disabled = false;
          resendBtn.style.color = '#0d3d38';
          resendBtn.style.cursor = 'pointer';
          timer.textContent = '';
          timeLeft = 30;
        }
      }, 1000);
    }

    // Start timer on page load
    startTimer();

    // Restart timer when resend is clicked
    resendBtn.addEventListener('click', () => {
      if (!resendBtn.disabled) {
        startTimer();
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>