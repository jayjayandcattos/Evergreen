<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        $conn = getDBConnection();
        
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, username, password_hash, email, full_name, is_active FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if password hash is valid (starts with $2y$ or $2a$ for bcrypt)
            $hash_valid = strpos($user['password_hash'], '$2y$') === 0 || strpos($user['password_hash'], '$2a$') === 0;
            
            if (!$hash_valid) {
                // Password hash format is invalid - likely needs fixing
                $error_message = "Password format error detected. Please run <a href='../database/fix_user_passwords.php' style='color: #0A3D3D; text-decoration: underline;'>Fix User Passwords</a> to resolve this issue.";
            } elseif (password_verify($password, $user['password_hash'])) {
                if ($user['is_active']) {
                    // Set session
                    setUserSession($user);
                    
                    // Update last login
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->bind_param("i", $user['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Log login activity
                    logActivity('login', 'authentication', 'User logged in successfully', $conn);
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Your account has been deactivated. Please contact the administrator.";
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accounting and Finance System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <div class="logo-section">
                <div class="logo-circle">
                    <img src="../assets/image/LOGO.png" alt="Evergreen Logo" class="logo-img">
                </div>
                <div class="logo-text">
                    <h1>EVERGREEN</h1>
                    <p>Secure. Invest. Achieve</p>
                </div>
            </div>
        </div>
        
        <div class="login-box">
            <div class="login-title-section">
                <h2>ACCOUNTING AND FINANCE</h2>
                <h3>Admin Login</h3>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-login">
                    <span>Login</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Evergreen Accounting & Finance. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

