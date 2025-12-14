<?php
session_start();

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/Evergreen/bank-system/evergreen-marketing/db_connect.php';

// Define URLROOT if not defined
if (!defined('URLROOT')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('URLROOT', $protocol . '://' . $host . '/Evergreen/bank-system/Basic-operation/operations/public');
}

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once $_SERVER['DOCUMENT_ROOT'] . '/Evergreen/bank-system/evergreen-marketing/PHPMailer-7.0.0/src/Exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Evergreen/bank-system/evergreen-marketing/PHPMailer-7.0.0/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Evergreen/bank-system/evergreen-marketing/PHPMailer-7.0.0/src/SMTP.php';

// Redirect if no activation session exists
if (!isset($_SESSION['activation_otp']) || !isset($_SESSION['activation_customer_id'])) {
    header('Location: ' . (defined('URLROOT') ? URLROOT : '') . '/auth/activate');
    exit();
}

$error = "";
$otp_expired = false;

// Check if OTP has expired (5 minutes = 300 seconds)
if (isset($_SESSION['otp_activation_time'])) {
    $elapsed_time = time() - $_SESSION['otp_activation_time'];
    if ($elapsed_time > 300) {
        $otp_expired = true;
        $error = "Your verification code has expired. Please request a new one.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp']);
        
        if (empty($entered_otp)) {
            $error = "Please enter the verification code.";
        } elseif ($otp_expired) {
            $error = "Your verification code has expired. Please request a new one.";
        } elseif ($entered_otp !== $_SESSION['activation_otp']) {
            $error = "Invalid verification code. Please try again.";
        } else {
            // OTP verified successfully
            $_SESSION['otp_activation_verified'] = true;
            
            // Redirect to password setup (direct file path, not through router)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $passwordSetupPath = '/Evergreen/bank-system/Basic-operation/operations/app/views/auth/activate_password.php';
            header('Location: ' . $protocol . '://' . $host . $passwordSetupPath);
            exit();
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Resend OTP logic
        
        // Get customer details
        $sql = "SELECT first_name FROM bank_customers WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $_SESSION['activation_customer_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_object();
        $stmt->close();
        
        // Generate new OTP
        $new_otp = sprintf("%06d", mt_rand(0, 999999));
        $_SESSION['activation_otp'] = $new_otp;
        $_SESSION['otp_activation_time'] = time();
        
        // Send new OTP
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'evrgrn.64@gmail.com';
            $mail->Password   = 'dourhhbymvjejuct';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
            $mail->addAddress($_SESSION['activation_email'], $customer->first_name);
            
            $mail->isHTML(true);
            $mail->Subject = 'Activate Online Banking - New Verification Code';
            $mail->Body    = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">New Verification Code</h1>
                </div>
                <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                    <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($customer->first_name) . '</strong>,</p>
                    <p style="font-size: 14px; color: #666;">You requested a new verification code. Here it is:</p>
                    <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                        <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $new_otp . '</h2>
                    </div>
                    <p style="font-size: 13px; color: #666;">This code will expire in <strong>5 minutes</strong>.</p>
                </div>
            </div>
            ';
            
            $mail->send();
            $error = "";
            $otp_expired = false;
            
        } catch (Exception $e) {
            $error = "Failed to resend code. Please try again.";
        }
    }
}

// Calculate remaining time
$remaining_seconds = 0;
if (isset($_SESSION['otp_activation_time']) && !$otp_expired) {
    $elapsed = time() - $_SESSION['otp_activation_time'];
    $remaining_seconds = max(0, 300 - $elapsed);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Evergreen Bank</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .activation-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            max-width: 450px;
            width: 100%;
            border: 1px solid #e9ecef;
        }
        .card-header-custom {
            background: white;
            border-bottom: 2px solid #003631;
            padding: 30px 30px 20px;
            text-align: center;
        }
        .card-body-custom {
            padding: 25px 30px 30px;
        }
        .form-control:focus {
            border-color: #003631;
            box-shadow: 0 0 0 0.2rem rgba(0, 54, 49, 0.15);
        }
        .btn-verify {
            background: #003631;
            border: none;
            color: white;
            padding: 10px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-verify:hover {
            background: #004d45;
            color: white;
        }
        .btn-resend {
            background: #6c757d;
            border: none;
            color: white;
            padding: 8px 20px;
            font-weight: 500;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-resend:hover {
            background: #5a6268;
            color: white;
        }
        .icon-circle {
            width: 60px;
            height: 60px;
            background: #e8f5f3;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        .otp-input {
            font-size: 20px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
        }
        .timer {
            font-size: 16px;
            font-weight: 600;
            color: #003631;
        }
        .timer.expired {
            color: #dc3545;
        }
        h2 {
            color: #003631;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 0;
        }
        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }
        .form-control {
            font-size: 14px;
            padding: 10px 12px;
        }
    </style>
</head>
<body>
    <div class="activation-card">
        <div class="card-header-custom">
            <div class="icon-circle">
                <i class="bi bi-shield-check" style="font-size: 28px; color: #003631;"></i>
            </div>
            <h2>Verify Your Identity</h2>
            <p class="subtitle">Enter the code sent to your email</p>
        </div>
        
        <div class="card-body-custom">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="text-center mb-3">
                <p class="text-muted mb-2" style="font-size: 13px;">
                    We sent a 6-digit code to:<br>
                    <strong style="font-size: 14px;"><?= htmlspecialchars($_SESSION['activation_email']); ?></strong>
                </p>
                
                <?php if (!$otp_expired): ?>
                    <p class="timer mb-0" id="timer">
                        <i class="bi bi-clock-fill me-1"></i>
                        <span id="time-remaining"><?= gmdate("i:s", $remaining_seconds); ?></span>
                    </p>
                <?php endif; ?>
            </div>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="otp" class="form-label text-center d-block">Verification Code</label>
                    <input type="text" 
                           class="form-control otp-input" 
                           id="otp" 
                           name="otp" 
                           placeholder="000000"
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           autocomplete="off"
                           <?= $otp_expired ? 'disabled' : ''; ?>
                           required>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" 
                            name="verify_otp" 
                            class="btn btn-verify"
                            <?= $otp_expired ? 'disabled' : ''; ?>>
                        <i class="bi bi-check-circle-fill me-2"></i>Verify Code
                    </button>
                </div>
            </form>
            
            <div class="text-center pt-3 border-top">
                <form method="POST" action="" class="d-inline">
                    <p class="text-muted mb-2" style="font-size: 13px;">Didn't receive the code?</p>
                    <button type="submit" name="resend_otp" class="btn btn-resend btn-sm">
                        <i class="bi bi-arrow-repeat me-1"></i>Resend Code
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus OTP input
        document.getElementById('otp').focus();
        
        // Timer countdown
        let remainingSeconds = <?= $remaining_seconds; ?>;
        const timerElement = document.getElementById('time-remaining');
        const timerContainer = document.getElementById('timer');
        const otpInput = document.getElementById('otp');
        const verifyButton = document.querySelector('button[name="verify_otp"]');
        
        if (remainingSeconds > 0) {
            const countdown = setInterval(() => {
                remainingSeconds--;
                
                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;
                timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                
                if (remainingSeconds <= 0) {
                    clearInterval(countdown);
                    timerContainer.classList.add('expired');
                    timerElement.textContent = 'Code Expired';
                    otpInput.disabled = true;
                    verifyButton.disabled = true;
                    
                    // Show alert
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-warning mt-3';
                    alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Your code has expired. Please request a new one.';
                    document.querySelector('form').insertAdjacentElement('beforebegin', alert);
                }
            }, 1000);
        }
        
        // Only allow numbers in OTP input
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>