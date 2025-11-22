<?php
session_start();
include("db_connect.php");

$error = "";
$success = false;
$login_failed = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bank_id = trim($_POST['bank_id']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($bank_id) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Join bank_customers with emails table to get email and verify bank_id
        $sql = "SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    c.middle_name,
                    c.password_hash,
                    c.bank_id,
                    e.email
                FROM bank_customers c
                INNER JOIN emails e ON c.customer_id = e.customer_id AND e.is_primary = 1
                WHERE e.email = ? AND c.bank_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $bank_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Use password_hash column (not password)
            if (password_verify($password, $row['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $row['customer_id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['bank_id'] = $row['bank_id'];
                $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];

                // Redirect to viewing page after successful login
                header("Location: viewingpage.php");
                exit();
            } else {
                $login_failed = true;
                $error = "Invalid email, Bank ID, or password.";
            }
        } else {
            $login_failed = true;
            $error = "Invalid email, Bank ID, or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evergreen - Login</title>
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
      padding-top: 230px;
      position: relative;
      box-shadow: 2px 0 30px rgba(0, 0, 0, 0.05);
      z-index: 2;
      align-items: center;
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
      color: #ffffff77;
      font-size: 18px;
      margin-bottom: 40px;
      font-weight: 400;
    }

    .error-message {
      color: #dc3545;
      font-size: 12px;
      margin-top: 6px;
      display: none;
      font-weight: 500;
    }

    .error-message.show {
      display: block;
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

    .input-wrapper {
      margin-bottom: 24px;
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
      width: 350px;
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

    .password-container {
      position: relative;
    }

    .password-container input {
      padding-right: 20px;
    }

    .eye-icon {
      position: absolute;
      right: 22px;
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

    .forgot-link {
      text-align: right;
      margin-top: -14px;
      margin-bottom: 32px;
    }

    .forgot-link a {
      font-size: 12px;
      color: #0d3d38;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .forgot-link a:hover {
      color: #1a6b62;
      text-decoration: underline;
    }

    .signin-btn {
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
      margin-bottom: 32px;
      box-shadow: 0 4px 16px rgba(13, 61, 56, 0.2);
      text-transform: uppercase;
    }

    .signin-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(13, 61, 56, 0.3);
    }

    .signin-btn:active {
      transform: translateY(0);
    }

    .signup-text {
      text-align: center;
      font-size: 13px;
      color: #666;
      padding: 16px;
      background: #f8f9fa;
      border-radius: 12px;
    }

    .signup-text a {
      color: #0d3d38;
      text-decoration: none;
      font-weight: 700;
      transition: color 0.3s;
    }

    .signup-text a:hover {
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

    .subtitle-right {
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

    /* Loading animation */
    .signin-btn.loading {
      position: relative;
      color: transparent;
    }

    .signin-btn.loading::after {
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

      h2 {
        font-size: 32px;
      }

      .welcome-text {
        font-size: 40px;
      }

      h1 {
        font-size: 48px;
      }

      .subtitle-right {
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

      .subtitle {
        font-size: 13px;
        margin-bottom: 40px;
      }

      input {
        padding: 12px 16px;
        font-size: 13px;
      }

      .signin-btn {
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

    html {
      scroll-behavior: smooth;
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
      <a href="viewing.php" class="back-link">←</a>
    </div>

    <h2>Log In</h2>

    <form method="POST" id="loginForm" novalidate>
      <div class="input-wrapper">
        <label class="input-label">Bank ID</label>
        <input type="text" name="bank_id" id="bank_id" placeholder="Bank ID" required>
        <span class="error-message" id="bank_id_error">This field is required</span>
      </div>

      <div class="input-wrapper">
        <label class="input-label">Email</label>
        <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
        <span class="error-message" id="email_error">This field is required</span>
      </div>

      <div class="input-wrapper">
        <label class="input-label">Password</label>
        <div class="password-container">
          <input type="password" id="password" name="password" placeholder="Password" required>
          <button type="button" class="eye-icon" onclick="togglePassword()">👁</button>
        </div>
        <span class="error-message" id="password_error">This field is required</span>
      </div>

      <div class="forgot-link">
        <a href="forgotpassword.php">Forgot Password?</a>
      </div>

      <button type="submit" class="signin-btn">SIGN IN</button>
    </form>

    <div class="signup-text">
      Don't have an account? <a href="signup.php">Sign Up</a>
    </div>
  </div>

  <div class="right">
    <div class="circle-bg circle-1"></div>
    <div class="circle-bg circle-2"></div>
    
    <div class="right-content">
      <p class="welcome-text">Welcome to</p>
      <h1>EVERGREEN</h1>
      <p class="subtitle">Log in to access your account!</p>
      <img src="images/laptop.png" alt="Laptop" class="laptop-img">
    </div>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleBtn = document.querySelector('.eye-icon');
      const type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      
      // Use Font Awesome icons
      if (type === 'text') {
        toggleBtn.innerHTML = '<i class="fa-solid fa-eye"></i>';
      } else {
        toggleBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
      }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      const eyeIcon = document.querySelector('.eye-icon');
      if (eyeIcon) {
        eyeIcon.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
      }
    });

    // Show success modal if login successful
    <?php if ($success): ?>
      showSuccessModal();
    <?php endif; ?>

    // Show error modal if login failed
    <?php if ($login_failed): ?>
      showErrorModal();
    <?php endif; ?>

    function showSuccessModal() {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 54, 49, 0.8);
        backdrop-filter: blur(4px);
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
              transform: translateY(20px);
            }
            to { 
              opacity: 1;
              transform: translateY(0);
            }
          }
        </style>
        <div style="
          background: white;
          padding: 2.5rem;
          border-radius: 15px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          max-width: 420px;
          width: 90%;
          text-align: center;
          animation: slideUp 0.4s ease;
        ">
          <div style="
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0d3d38 0%, #1a6b62 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
          ">✓</div>
          
          <h3 style="
            color: #0d3d38;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
            font-weight: 600;
          ">Login Successful!</h3>
          
          <p style="
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.6;
          ">Welcome to EVERGREEN Bank!</p>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      setTimeout(() => {
        window.location.href = 'viewingpage.php';
      }, 2000);
    }

    function showErrorModal() {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
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
              transform: translateY(20px);
            }
            to { 
              opacity: 1;
              transform: translateY(0);
            }
          }
          @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
          }
        </style>
        <div style="
          background: white;
          padding: 2.5rem;
          border-radius: 15px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          max-width: 420px;
          width: 90%;
          text-align: center;
          animation: slideUp 0.4s ease, shake 0.5s ease 0.2s;
        ">
          <div style="
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
          ">✕</div>
          
          <h3 style="
            color: #dc3545;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
            font-weight: 600;
          ">Login Failed!</h3>
          
          <p style="
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
            line-height: 1.6;
          ">Invalid Bank ID, Email, or Password.<br>Please try again.</p>
          
          <button onclick="this.parentElement.parentElement.remove()" style="
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
          " onmouseover="this.style.background='#c82333'" onmouseout="this.style.background='#dc3545'">
            Try Again
          </button>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.remove();
        }
      });
    }

    // Login Form Validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      let isValid = true;
      
      const bankId = document.getElementById('bank_id');
      const bankIdError = document.getElementById('bank_id_error');
      if (!bankId.value.trim()) {
        bankId.classList.add('error');
        bankIdError.classList.add('show');
        isValid = false;
      } else {
        bankId.classList.remove('error');
        bankIdError.classList.remove('show');
      }
      
      const email = document.getElementById('email');
      const emailError = document.getElementById('email_error');
      if (!email.value.trim()) {
        email.classList.add('error');
        emailError.classList.add('show');
        isValid = false;
      } else {
        email.classList.remove('error');
        emailError.classList.remove('show');
      }
      
      const password = document.getElementById('password');
      const passwordError = document.getElementById('password_error');
      if (!password.value.trim()) {
        password.classList.add('error');
        passwordError.classList.add('show');
        isValid = false;
      } else {
        password.classList.remove('error');
        passwordError.classList.remove('show');
      }
      
      if (!isValid) {
        e.preventDefault();
      }
    });

    // Remove error styling on input
    document.querySelectorAll('input').forEach(input => {
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

  </script>
</body>
</html>