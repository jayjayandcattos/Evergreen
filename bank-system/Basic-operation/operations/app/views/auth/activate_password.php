<?php
session_start();

// Redirect if OTP not verified
if (!isset($_SESSION['otp_activation_verified']) || !isset($_SESSION['activation_customer_id'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $activatePath = '/Evergreen/bank-system/Basic-operation/operations/app/views/auth/activate.php';
    header('Location: ' . $protocol . '://' . $host . $activatePath);
    exit();
}

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/Evergreen/bank-system/evergreen-marketing/db_connect.php';

// Define URLROOT if not defined
if (!defined('URLROOT')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('URLROOT', $protocol . '://' . $host . '/Evergreen/bank-system/Basic-operation/operations/public');
}

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Update customer password
        $sql = "UPDATE bank_customers SET password_hash = ? WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $password_hash, $_SESSION['activation_customer_id']);
        
        if ($stmt->execute()) {
            $stmt->close();
            // Clear activation session
            unset($_SESSION['activation_otp']);
            unset($_SESSION['activation_account']);
            unset($_SESSION['activation_email']);
            unset($_SESSION['activation_customer_id']);
            unset($_SESSION['otp_activation_time']);
            unset($_SESSION['otp_activation_verified']);
            
            $success = true;
            
            // Redirect to login after 3 seconds (direct path to avoid router)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $loginPath = '/Evergreen/bank-system/evergreen-marketing/login.php';
            header("refresh:3;url=" . $protocol . '://' . $host . $loginPath);
        } else {
            $error = "Failed to set password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - Evergreen Bank</title>
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
        .btn-complete {
            background: #003631;
            border: none;
            color: white;
            padding: 10px;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-complete:hover {
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
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 8px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .success-icon {
            font-size: 60px;
            color: #28a745;
            animation: scaleIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
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
        <?php if ($success): ?>
            <!-- Success View -->
            <div class="card-header-custom">
                <i class="bi bi-check-circle-fill success-icon"></i>
                <h2 class="mt-3">Activation Complete!</h2>
                <p class="subtitle">Your online banking is now active</p>
            </div>
            
            <div class="card-body-custom text-center">
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Password set successfully!
                </div>
                <p class="text-muted mb-3" style="font-size: 14px;">Redirecting you to login page...</p>
                <div class="spinner-border text-success" role="status" style="width: 2rem; height: 2rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">
                    <a href="../../../../../evergreen-marketing/login.php" class="btn btn-outline-success btn-sm">
                        Go to Login Now
                    </a>
                </p>
            </div>
        <?php else: ?>
            <!-- Password Setup Form -->
            <div class="card-header-custom">
                <div class="icon-circle">
                    <i class="bi bi-key-fill" style="font-size: 28px; color: #003631;"></i>
                </div>
                <h2>Set Your Password</h2>
                <p class="subtitle">Create a secure password for your account</p>
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
                        <label for="password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password"
                                   minlength="8"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small class="form-text text-muted" id="strengthText">Minimum 8 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Confirm Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Re-enter your password"
                                   minlength="8"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted" id="matchText"></small>
                    </div>
                    
                    <div class="alert alert-info py-2" style="font-size: 13px;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Requirements:</strong> At least 8 characters, mix of letters and numbers recommended
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="set_password" class="btn btn-complete">
                            <i class="bi bi-check-circle-fill me-2"></i>Complete Activation
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmInput.type === 'password') {
                confirmInput.type = 'text';
                icon.classList.remove('bi-eye-fill');
                icon.classList.add('bi-eye-slash-fill');
            } else {
                confirmInput.type = 'password';
                icon.classList.remove('bi-eye-slash-fill');
                icon.classList.add('bi-eye-fill');
            }
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength === 0 || password.length < 8) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak password';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Medium strength';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong password';
                strengthText.style.color = '#28a745';
            }
        });
        
        // Password match checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
            } else if (password === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#dc3545';
            }
        });
    </script>
</body>
</html>
