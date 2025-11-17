<?php
session_start();
include("db_connect.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// If already logged in, redirect to dashboard
if (isset($_SESSION['customer_id'])) {
    header("Location: ../basic-operations/public/index.php");
    exit;
}


$error = "";
$success = false;
$login_failed = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We now use 'email' as the identifier, not 'bank_id'
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
        $login_failed = true; // Show a generic error on the modal if JS is off
    } else {
        // Query to find the customer by email
        // We join bank_customers with emails to find the matching user
        $sql = "
            SELECT 
                c.customer_id, 
                c.first_name, 
                c.last_name, 
                c.password_hash,
                e.email
            FROM bank_customers c
            JOIN emails e ON c.customer_id = e.customer_id
            WHERE e.email = ? AND e.is_primary = 1
        ";
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $row['password_hash'])) {
                // Set session variables based on the correct schema
                $_SESSION['customer_id'] = $row['customer_id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['customer_first_name'] = $row['first_name'];
                $_SESSION['customer_last_name'] = $row['last_name'];
                $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];

                $success = true; // Trigger success modal
            } else {
                // Invalid password
                $login_failed = true;
            }
        } else {
            // No user found with that email
            $login_failed = true;
        }
        $stmt->close();
    }
    $conn->close();
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
      /* Adjusted padding-top for better centering */
      padding-top: 15vh; 
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

    .back-link {
      font-size: 24px;
      text-decoration: none;
      color: #003631;
      transition: all 0.3s ease;
      padding: 10px 14px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(13, 61, 56, 0.05);
      backdrop-filter: blur(10px);
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
    
    .form-container {
        width: 100%;
        max-width: 380px;
    }

    .subtitle {
      text-align: center;
      color: #777;
      font-size: 14px;
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
      width: 100%;
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
      width: 100%; /* Changed from fixed 350px */
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
      padding-right: 50px; /* Space for eye icon */
    }

    .eye-icon {
      position: absolute;
      right: 16px;
      top: 50%;
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
      width: 100%;
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
        padding-top: 10vh;
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
        min-height: 100vh;
      }
      
      .form-container {
        max-width: 450px;
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
      
      .form-container {
        max-width: 100%;
      }

      h2 {
        font-size: 28px;
        margin-bottom: 8px;
      }

      .subtitle {
        font-size: 13px;
        margin-bottom: 30px;
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
  </style>
</head>
<body>
  <div class="left">
    <div class="logo">
      <img src="images/loginlogo.png" alt="Logo"> <!-- Make sure this path is correct -->
      <div class="logo-text">
        <span class="name">EVERGREEN</span>
        <span class="tagline">Secure. Invest. Achieve</span>
      </div>
    </div>

    <!-- This back link seems to go to a viewing page, you can change the href -->
    <div class="back-container">
      <a href="viewing.php" class="back-link">←</a> <!-- Changed to index.php -->
    </div>

    <div class="form-container">
        <h2>Log In</h2>
        <p class="subtitle">Welcome back! Please enter your details.</p>

        <form method="POST" id="loginForm" novalidate>
          <!-- REMOVED Bank ID field -->
          
          <div class="input-wrapper">
            <label class="input-label" for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="example@gmail.com" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <span class="error-message" id="email_error">A valid email is required</span>
          </div>

          <div class="input-wrapper">
            <label class="input-label" for="password">Password</label>
            <div class="password-container">
              <input type="password" id="password" name="password" placeholder="Password" required>
              <button type="button" class="eye-icon" id="toggle-password-btn"></button>
            </div>
            <span class="error-message" id="password_error">Password is required</span>
          </div>

          <div class="forgot-link">
            <a href="forgotpassword.php">Forgot Password?</a>
          </div>

          <button type="submit" class="signin-btn" id="signin-btn">SIGN IN</button>
        </form>

        <div class="signup-text">
          Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
  </div>

  <div class="right">
    <div class="circle-bg circle-1"></div>
    <div class="circle-bg circle-2"></div>
    
    <div class="right-content">
      <p class="welcome-text">Welcome to</p>
      <h1>EVERGREEN</h1>
      <p class="subtitle-right">Log in to access your account!</p>
      <img src="images/laptop.png" alt="Laptop" class="laptop-img"> <!-- Make sure this path is correct -->
    </div>
  </div>

  <script>
    function togglePassword(inputId = 'password') {
      const passwordInput = document.getElementById(inputId);
      const toggleBtn = passwordInput.nextElementSibling; // Assumes eye icon is next sibling
      const type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      
      if (type === 'text') {
        toggleBtn.innerHTML = '<i class="fa-solid fa-eye"></i>';
      } else {
        toggleBtn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const eyeIcon = document.getElementById('toggle-password-btn');
      if (eyeIcon) {
        eyeIcon.innerHTML = '<i class="fa-solid fa-eye-slash"></i>';
        eyeIcon.addEventListener('click', () => togglePassword('password'));
      }
    });

    // Show success modal if login successful
    <?php if ($success): ?>
      showSuccessModal();
    <?php endif; ?>

    // Show error modal if login failed
    <?php if ($login_failed): ?>
      showErrorModal("<?php echo !empty($error) ? $error : 'Invalid Email or Password. Please try again.'; ?>");
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
          @keyframes draw {
             to {
               stroke-dashoffset: 0;
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
            box-shadow: 0 10px 30px rgba(13, 61, 56, 0.3);
          ">
            <svg width="40" height="40" viewBox="0 0 50 50">
              <path d="M 10 25 L 20 35 L 40 15" stroke="white" stroke-width="5" fill="none" 
                    stroke-linecap="round" stroke-linejoin="round"
                    style="stroke-dasharray: 50; stroke-dashoffset: 50; animation: draw 0.5s ease 0.3s forwards;"/>
            </svg>
          </div>
          
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
          ">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!<br>Redirecting to your account...</p>
        </div>
      `;
      
      document.body.appendChild(modal);
      
      setTimeout(() => {
        window.location.href = 'cardrewards.php'; // Redirect to dashboard
      }, 2000);
    }

    function showErrorModal(message) {
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
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
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
            font-weight: 600;
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
          ">${message}</p>
          
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
      const btn = document.getElementById('signin-btn');
      
      const email = document.getElementById('email');
      const emailError = document.getElementById('email_error');
      if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        email.classList.add('error');
        emailError.textContent = 'A valid email is required';
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
        passwordError.textContent = 'Password is required';
        passwordError.classList.add('show');
        isValid = false;
      } else {
        password.classList.remove('error');
        passwordError.classList.remove('show');
      }
      
      if (!isValid) {
        e.preventDefault();
      } else {
        // If valid, show loading spinner
        btn.classList.add('loading');
        btn.disabled = true;
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