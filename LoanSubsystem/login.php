<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// 🔒 If already logged in, redirect to correct dashboard
if (isset($_SESSION['user_email'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: adminindex.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'client';

    $user = null;
    
    // Check based on selected role
    if ($selectedRole === 'admin') {
        // Try to authenticate as admin using BankingDB.users table
        $user = verifyAdminPassword($email, $password);
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'] ?? $user['display_name'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['loan_officer_id'] = $user['loan_officer_id'] ?? ('LO-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT));
            $_SESSION['customer_id'] = null; // Admin doesn't have customer_id
            
            header('Location: adminindex.php');
            exit();
        } else {
            $error = "Invalid admin credentials or insufficient permissions.";
        }
    } else {
        // Try to authenticate as customer using BankingDB.bank_customers table
        $user = verifyUserPassword($email, $password);
        if ($user) {
            // Get additional customer info (already in $user from verifyUserPassword)
            if (isset($user['id'])) {
                $_SESSION['customer_id'] = $user['id'];
                $_SESSION['account_number'] = $user['account_number'] ?? null;
                $_SESSION['contact_number'] = $user['contact_number'] ?? null;
            }
            
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'] ?? $user['display_name'];
            $_SESSION['user_role'] = 'client';
            
            header('Location: index.php');
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login – Evergreen Trust and Savings</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f9f5f0;
      color: #2d4a3e;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .header {
      background-color: #0a3b2f;
      padding: 16px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo {
      height: 40px;
      width: auto;
    }

    .logo-text {
      color: white;
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .login-container {
      max-width: 400px;
      width: 90%;
      margin: 60px auto;
      padding: 32px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .login-container h2 {
      text-align: center;
      margin-bottom: 24px;
      color: #0a3b2f;
    }

    .error {
      color: #d32f2f;
      background-color: #ffebee;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      font-size: 14px;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
      transition: border-color 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #0a3b2f;
    }

    .role-selector {
      margin-bottom: 24px;
    }

    .role-selector label {
      display: inline-flex;
      align-items: center;
      margin-right: 20px;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
    }

    .role-selector input[type="radio"] {
      width: auto;
      margin-right: 8px;
      accent-color: #0a3b2f;
    }

    .btn-login {
      width: 100%;
      padding: 12px;
      background-color: #0a3b2f;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn-login:hover {
      background-color: #082e24;
    }
  </style>
</head>
<body>
  <div class="header">
    <img src="logo.png" alt="Evergreen Logo" class="logo">
    <div class="logo-text">EVERGREEN</div>
  </div>

  <div class="login-container">
    <h2>Login to Your Account</h2>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="email">Email address:</label>
        <input type="email" id="email" name="email" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" />
      </div>

      <div class="role-selector">
        <label>
          <input type="radio" name="role" value="client" <?= (($_POST['role'] ?? 'client') === 'client') ? 'checked' : '' ?>> Client
        </label>
        <label>
          <input type="radio" name="role" value="admin" <?= (($_POST['role'] ?? 'client') === 'admin') ? 'checked' : '' ?>> Admin
        </label>
      </div>

      <button type="submit" class="btn-login">Login</button>
    </form>
  </div>
</body>
</html>