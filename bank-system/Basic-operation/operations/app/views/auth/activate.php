<?php
session_start();

// Clear any existing activation session
if (!isset($_POST['verify_account']) && !isset($_GET['continue'])) {
    unset($_SESSION['activation_otp']);
    unset($_SESSION['activation_account']);
    unset($_SESSION['activation_email']);
    unset($_SESSION['activation_customer_id']);
    unset($_SESSION['otp_activation_time']);
    unset($_SESSION['otp_activation_verified']);
}

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

$error = "";
$step = 1; // Step 1: Verify account and email, send OTP

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_account'])) {
    $account_number = trim($_POST['account_number']);
    $email = trim($_POST['email']);
    
    if (empty($account_number) || empty($email)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if account exists and belongs to customer with this email
        $sql = "SELECT 
                    bc.customer_id,
                    bc.first_name,
                    bc.email,
                    bc.password_hash,
                    ca.account_number
                FROM bank_customers bc
                INNER JOIN customer_accounts ca ON bc.customer_id = ca.customer_id
                WHERE ca.account_number = ? 
                AND bc.email = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $account_number, $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_object();
        
        $stmt->close();
        
        if (!$result) {
            $error = "Account number and email do not match our records.";
        } elseif (!empty($result->password_hash)) {
            // User already has a password, redirect to forgot password
            $error = "This account already has online banking activated. Please use <a href='" . URLROOT . "/auth/login' class='text-decoration-none fw-bold' style='color: #bba27bff;'>Login</a> or <a href='../../../../../evergreen-marketing/forgotpassword.php' class='text-decoration-none fw-bold' style='color: #bba27bff;'>Forgot Password</a>.";
        } else {
            // Generate 6-digit OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $_SESSION['activation_otp'] = $otp;
            $_SESSION['activation_customer_id'] = $result->customer_id;
            $_SESSION['activation_email'] = $email;
            $_SESSION['activation_account'] = $account_number;
            $_SESSION['otp_activation_time'] = time();
            
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
                $mail->addAddress($email, $result->first_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Activate Online Banking - Verification Code';
                $mail->Body    = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                    <div style="background: linear-gradient(135deg, #003631 0%, #1a6b62 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                        <h1 style="color: white; margin: 0;">Activate Online Banking</h1>
                    </div>
                    <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
                        <p style="font-size: 16px; color: #333;">Hello <strong>' . htmlspecialchars($result->first_name) . '</strong>,</p>
                        <p style="font-size: 14px; color: #666;">You are activating online banking for your account. Use the verification code below:</p>
                        <div style="background: white; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; border: 2px dashed #003631;">
                            <h2 style="color: #003631; font-size: 32px; letter-spacing: 8px; margin: 0;">' . $otp . '</h2>
                        </div>
                        <p style="font-size: 13px; color: #666;">This code will expire in <strong>5 minutes</strong>.</p>
                        <p style="font-size: 13px; color: #666;">If you didn\'t request this, please contact us immediately.</p>
                        <hr style="border: none; border-top: 1px solid #dee2e6; margin: 20px 0;">
                        <p style="font-size: 12px; color: #999; text-align: center;">Best regards,<br>Evergreen Bank Team</p>
                    </div>
                </div>
                ';
                $mail->AltBody = "Hello " . $result->first_name . ",\n\nYour online banking activation code is: " . $otp . "\n\nThis code will expire in 5 minutes.\n\nIf you didn't request this, please contact us immediately.\n\nBest regards,\nEvergreen Bank";
                
                $mail->send();
                
                // Redirect to OTP verification page (direct file path, not through router)
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $verifyPath = '/Evergreen/bank-system/Basic-operation/operations/app/views/auth/activate_verify.php';
                header('Location: ' . $protocol . '://' . $host . $verifyPath);
                exit();
                
            } catch (Exception $e) {
                $error = "Failed to send verification code. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Online Banking - Evergreen Bank</title>
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
        .btn-activate {
            background: #003631;
            border: none;
            color: white;
            padding: 10px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-activate:hover {
            background: #004d45;
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
        .link-custom {
            color: #003631;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .link-custom:hover {
            color: #004d45;
            text-decoration: underline;
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
        .form-text {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="activation-card">
        <div class="card-header-custom">
            <div class="icon-circle">
                <i class="bi bi-shield-lock-fill" style="font-size: 28px; color: #003631;"></i>
            </div>
            <h2>Activate Online Banking</h2>
            <p class="subtitle">Secure access to your accounts</p>
        </div>
        
        <div class="card-body-custom">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="account_number" class="form-label">
                        <i class="bi bi-credit-card-2-front me-1"></i>Account Number
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="account_number" 
                           name="account_number" 
                           placeholder="e.g., SA-1234-2025 or CHA-1234-2025"
                           value="<?= isset($_POST['account_number']) ? htmlspecialchars($_POST['account_number']) : ''; ?>"
                           required>
                    <small class="form-text text-muted">Format: SA-XXXX-YYYY or CHA-XXXX-YYYY</small>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope-fill me-1"></i>Registered Email
                    </label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email" 
                           placeholder="your.email@example.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                    <small class="form-text text-muted">Email associated with your account</small>
                </div>
                
                <div class="d-grid mb-3">
                    <button type="submit" name="verify_account" class="btn btn-activate">
                        <i class="bi bi-send-fill me-2"></i>Send Verification Code
                    </button>
                </div>
            </form>
            
            <div class="text-center mt-3 pt-3 border-top">
                <p class="mb-2 text-muted" style="font-size: 13px;">Already activated?</p>
                <a href="../../../../../evergreen-marketing/login.php" class="link-custom">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login to your account
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
