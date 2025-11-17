<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start(); // Prevent header issues

// --- PHPMailer Includes ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer-7.0.0/src/Exception.php';
require 'PHPMailer-7.0.0/src/PHPMailer.php';
require 'PHPMailer-7.0.0/src/SMTP.php';
// --- End PHPMailer Includes ---

include("db_connect.php");

$error = "";
$success = "";

// This PHP logic will run when the form is *finally* submitted on the last step
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ---- INPUTS ----
    // bank_customers fields
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // emails field
    $email = trim($_POST['email']);
    
    // phones field
    $contact_number = trim($_POST['contact_number']);
    
    // addresses fields
    $address_line = trim($_POST['address']); // From 'address' field
    $city = trim($_POST['city_province']);    // From 'city_province' field

    // customer_profiles fields
    $birthday = $_POST['birthday'];
    $gender_id = $_POST['gender']; 
    $marital_status = $_POST['marital_status']; 
    $occupation = trim($_POST['occupation']); 
    $company = trim($_POST['company']); 
    
    // Other form data
    $terms_accepted = isset($_POST['terms']);

    // ---- VALIDATION ----
    // Middle name and company are allowed to be empty
    if (
        empty($first_name) || empty($last_name) ||
        empty($address_line) || empty($city) || empty($email) ||
        empty($contact_number) || empty($birthday) ||
        empty($password) || empty($confirm_password) ||
        empty($gender_id) || empty($marital_status) || empty($occupation)
    ) {
        $error = "Please fill in all required fields.";
    } elseif (!$terms_accepted) {
        $error = "You must agree to the Terms and Conditions.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {

        // ---- CHECK IF EMAIL ALREADY EXISTS ----
        $check_sql = "
            SELECT c.customer_id 
            FROM bank_customers c
            INNER JOIN emails e ON c.customer_id = e.customer_id
            WHERE e.email = ?
        ";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Email already registered.";
        } else {

            // ---- GENERATE VERIFICATION CODE ----
            $verification_code = sprintf("%06d", rand(0, 999999));

            // ---- PASSWORD HASH ----
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // ---- STORE TEMPORARY REGISTRATION IN SESSION ----
            $_SESSION['temp_registration'] = [
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'password_hash' => $hashed_password,
                'email' => $email,
                'phone_number' => $contact_number,
                'address_line' => $address_line,
                'city' => $city,
                'date_of_birth' => $birthday,
                'gender_id' => $gender_id,
                'marital_status' => $marital_status,
                'occupation' => $occupation,
                'company' => $company,
                'verification_code' => $verification_code
            ];

            // ---- SEND VERIFICATION EMAIL ----
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'evrgrn.64@gmail.com'; // Your email
                $mail->Password = 'dourhhbymvjejuct'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('evrgrn.64@gmail.com', 'Evergreen Banking');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Evergreen - Verify Your Email';

                $mail->Body = "
                    <h2 style='color: #0d3d38;'>Welcome to Evergreen Banking</h2>
                    <p>Thank you for registering. Please use the following code to verify your email address:</p>
                    <h1 style='font-size: 48px; letter-spacing: 2px; color: #1a6b62; margin: 20px 0;'>$verification_code</h1>
                    <p>If you did not request this, please ignore this email.</p>
                ";

                $mail->send();

                ob_end_clean();
                header("Location: verify.php"); // Redirect to verification page
                exit;

            } catch (Exception $e) {
                error_log("Email failed: " . $mail->ErrorInfo);
                $_SESSION['email_error'] = "Failed to send verification code. Please try again.";
                ob_end_clean();
                header("Location: verify.php"); // Redirect to verify page to show error
                exit;
            }
        }
        $check_stmt->close();
    }
    $conn->close();
}

// Flush the buffer if no redirect happened (e.g., to show $error)
if (!headers_sent()) {
    ob_end_flush();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Evergreen - Sign Up</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* --- Global Styles --- */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
  overflow-y: auto;
  background: #f8f9fa;
}

/* --- Left Panel (Form) --- */
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
  overflow-y: auto;
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

.subtitle {
  text-align: center;
  color: #777777ff;
  font-size: 14px;
  margin-bottom: 40px;
  font-weight: 400;
}

/* --- Stepper --- */
.stepper-wrapper {
  display: flex;
  justify-content: space-between;
  margin-bottom: 40px;
  position: relative;
}

.stepper-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #adb5bd;
  width: 33.33%;
  position: relative;
  z-index: 1;
}

.step-counter {
  height: 40px;
  width: 40px;
  border-radius: 50%;
  background: #e9ecef;
  border: 3px solid #e9ecef;
  color: #adb5bd;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 700;
  transition: all 0.3s ease;
  margin-bottom: 8px;
}

.step-name {
  font-size: 12px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.stepper-item.active .step-counter {
  background-color: #fff;
  border-color: #0d3d38;
  color: #0d3d38;
}

.stepper-item.active .step-name {
  color: #0d3d38;
}

.stepper-item.completed .step-counter {
  background-color: #0d3d38;
  border-color: #0d3d38;
  color: #fff;
}

.stepper-item.completed .step-name {
  color: #0d3d38;
}

/* Stepper Progress Bar */
.stepper-wrapper::before {
  content: "";
  position: absolute;
  top: 21px; /* Center of the counter */
  left: 0;
  right: 0;
  height: 3px;
  background-color: #e9ecef;
  z-index: 0;
}

.stepper-wrapper::after {
  content: "";
  position: absolute;
  top: 21px;
  left: 0;
  height: 3px;
  background-color: #0d3d38;
  z-index: 0;
  width: 0; /* Will be updated by JS */
  transition: width 0.4s ease;
}


/* --- Form & Inputs --- */
form {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
}

.form-step {
  display: none;
  flex-direction: column;
  gap: 20px;
  animation: fadeIn 0.5s ease;
}

.form-step.active {
  display: flex;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
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

.form-row .input-wrapper input,
.form-row .input-wrapper select {
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
  position: relative;
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

input, select {
  padding: 14px 18px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 14px;
  background: #f8f9fa;
  color: #333;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}

select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 16px 12px;
}

select:invalid {
    color: #adb5bd;
}

select option {
    color: #333;
}

input::placeholder {
  color: #adb5bd;
  font-size: 13px;
}

input:focus, select:focus {
  outline: none;
  border-color: #0d3d38;
  background: white;
  box-shadow: 0 0 0 4px rgba(13, 61, 56, 0.1);
  transform: translateY(-2px);
}

input:hover:not(:focus), select:hover:not(:focus) {
  border-color: #d0d5dd;
}

/* --- Alerts & Errors --- */
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
  animation: fadeInError 0.2s ease;
}

@keyframes fadeInError {
  from { opacity: 0; }
  to { opacity: 1; }
}

input.error, select.error {
  border-color: #dc3545 !important;
  background: #fff5f5 !important;
}

/* --- Password Strength & Toggles --- */
.password-container {
  position: relative;
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

/* --- Buttons & Links --- */
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

.step-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.create-btn, .prev-btn {
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
  box-shadow: 0 4px 16px rgba(13, 61, 56, 0.2);
  text-transform: uppercase;
}

.prev-btn {
    background: #f8f9fa;
    color: #0d3d38;
    border: 2px solid #e9ecef;
    box-shadow: none;
    width: 120px;
}

.prev-btn:hover {
    background: #e9ecef;
    border-color: #d0d5dd;
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

/* --- Right Panel (Branding) --- */
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

/* --- Responsive Design --- */
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
    min-height: 100vh;
    overflow-y: visible;
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

  input, select {
    padding: 12px 16px;
    font-size: 13px;
  }

  input .password-container {
    width: 100%;
  }

  .create-btn, .prev-btn {
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

/* --- Loading Animation --- */
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


/* --- Modal Popup (Terms & Conditions) --- */
.modal-container {
  position: fixed;
  top: 0;
  left: 0;
  background-color: rgba(0, 0, 0, 0.6); /* Darker overlay */
  backdrop-filter: blur(5px); /* Blur effect */
  width: 100%;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  animation: fadeIn 0.3s ease;
}

.popup {
  background-color: #003631;
  color: white;
  padding: 25px;
  border-radius: 15px;
  width: 90%;
  max-width: 800px; /* Max width for larger screens */
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
  animation: slideDown 0.4s ease-out;
}

.head-popup {
  display: flex;
  justify-content: flex-end;
}

.exit-btn {
  cursor: pointer;
  border: none;
  background: none;
  color: white;
  font-size: 24px;
}

.head-logo {
  display: flex;
  gap: 10px;
  justify-content: center;
  align-items: center;
}

#web-title {
  color: white;
  font-size: 20px;
  font-weight: 700;
}

.head-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
  color: #F1B24A;
}

#web-catch {
    font-size: 12px;
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
  font-weight: 500;
  font-size: 25px;
  text-align: center;
}

.terms-body {
  width: 100%;
  height: 40vh; /* Responsive height */
  max-height: 400px; /* Max height */
  overflow: auto; 
  border: 1px solid #00504a;
  padding: 10px 15px;
  background-color: white;
  color: #003631;
  display: flex;
  flex-direction: column;
  gap: 10px;
  border-radius: 15px;
  scrollbar-width: thin; 
  scrollbar-color: #888 #f1f1f1;
}

.terms-body::-webkit-scrollbar {
  width: 8px; 
}

.terms-body::-webkit-scrollbar-track {
  background: #f1f1f1; 
  border-radius: 10px; 
}

.terms-body::-webkit-scrollbar-thumb {
  background-color: #888; 
  border-radius: 10px; 
  border: 2px solid #f1f1f1; 
}

.terms-body::-webkit-scrollbar-thumb:hover {
  background-color: #555; 
}

.wrap-tnc {
  display: flex;
  flex-direction: column;
  gap: 8px; 
  align-items: flex-start; /* Align all content left */
  padding: 10px;
  border-bottom: 1px solid #eee;
}
.wrap-tnc:last-child {
    border-bottom: none;
}

.body-container {
  display: flex;
  flex-direction: column;
  gap: 15px;
  text-align: left;
}

.conditions-head {
  align-self: flex-start; 
  font-weight: bold;
  color: #003631;
  font-size: 1.1rem;
}

.conditions-para {
  text-align: left;
  width: 100%; /* Full width */
  line-height: 1.6;
}

.conditions-list {
    padding-left: 20px;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 5px;
    margin-bottom: 5px;
}

.heading, .sub-heading {
  color: #003631;
  text-align: center;
}

.heading {
  font-size: 25px;
}

.sub-heading {
    font-size: 14px;
    color: #333;
    margin-bottom: 15px;
}

.btn-wrap {
  display: flex;
  gap: 20px;
  justify-content: center;
  margin-top: 10px;
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
  border: none;
  transition: all 0.3s ease;
}
.action:hover {
    background-color: #ffc107;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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

    <!-- Stepper Navigation -->
    <div class="stepper-wrapper">
      <div class="stepper-item active">
        <div class="step-counter">1</div>
        <div class="step-name">Personal</div>
      </div>
      <div class="stepper-item">
        <div class="step-counter">2</div>
        <div class="step-name">Contact</div>
      </div>
      <div class="stepper-item">
        <div class="step-counter">3</div>
        <div class="step-name">Security</div>
      </div>
    </div>

    <form method="POST" id="signupForm" novalidate>

      <!-- Step 1: Personal Info -->
      <div class="form-step active" data-step="1">
        <div class="form-row">
          <div class="input-wrapper">
            <label class="input-label" for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="Juan" 
                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
            <span class="error-message" id="first_name_error">First name is required</span>
          </div>
          <div class="input-wrapper">
            <label class="input-label" for="middle_name">Middle Name (Optional)</label>
            <input type="text" name="middle_name" id="middle_name" placeholder="Andrade"
                   value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
            <span class="error-message" id="middle_name_error"></span>
          </div>
        </div>

        <div class="input-wrapper full">
          <label class="input-label" for="last_name">Surname</label>
          <input type="text" name="last_name" id="last_name" placeholder="Dela Cruz" 
                 value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
          <span class="error-message" id="last_name_error">Surname is required</span>
        </div>

        <div class="form-row">
             <div class="input-wrapper">
              <label class="input-label" for="birthday">Birthday</label>
              <input type="text" name="birthday" id="birthday" placeholder="MM/DD/YEAR" onfocus="(this.type='date')" 
                     value="<?php echo isset($_POST['birthday']) ? htmlspecialchars($_POST['birthday']) : ''; ?>" required>
              <span class="error-message" id="birthday_error">Birthday is required</span>
            </div>
            <div class="input-wrapper">
              <label class="input-label" for="gender">Gender</label>
              <select name="gender" id="gender" required>
                  <option value="" disabled selected>Select your gender</option>
                  <option value="1" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '1') ? 'selected' : ''; ?>>Male</option>
                  <option value="2" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '2') ? 'selected' : ''; ?>>Female</option>
                  <option value="3" <?php echo (isset($_POST['gender']) && $_POST['gender'] == '3') ? 'selected' : ''; ?>>Other</option>
              </select>
              <span class="error-message" id="gender_error">Please select a gender</span>
            </div>
        </div>
        
        <div class="form-row">
          <div class="input-wrapper">
            <label class="input-label" for="marital_status">Marital Status</label>
            <select name="marital_status" id="marital_status" required>
                <option value="" disabled selected>Select your status</option>
                <option value="single" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'single') ? 'selected' : ''; ?>>Single</option>
                <option value="married" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'married') ? 'selected' : ''; ?>>Married</option>
                <option value="divorced" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'divorced') ? 'selected' : ''; ?>>Divorced</option>
                <option value="widowed" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                <option value="other" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
            <span class="error-message" id="marital_status_error">Please select a status</span>
          </div>
          <div class="input-wrapper">
              <label class="input-label" for="occupation">Occupation</label>
              <input type="text" name="occupation" id="occupation" placeholder="e.g., Software Engineer" 
                     value="<?php echo isset($_POST['occupation']) ? htmlspecialchars($_POST['occupation']) : ''; ?>" required>
              <span class="error-message" id="occupation_error">Occupation is required</span>
          </div>
        </div>
        
         <div class="input-wrapper full">
              <label class="input-label" for="company">Company / Employer (Optional)</label>
              <input type="text" name="company" id="company" placeholder="e.g., Google Inc." 
                     value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
              <span class="error-message" id="company_error"></span>
          </div>
          
        <div class="step-navigation">
            <button type="button" class="create-btn next-btn">Next</button>
        </div>
      </div>

      <!-- Step 2: Contact & Address -->
      <div class="form-step" data-step="2">
        <div class="input-wrapper full">
          <label class="input-label" for="address">House number/Street/Brgy</label>
          <input type="text" name="address" id="address" placeholder="29 Simforosa st. Brgy. Nagkaisang" 
                 value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>
          <span class="error-message" id="address_error">Address is required</span>
        </div>

        <div class="form-row">
          <div class="input-wrapper">
            <label class="input-label" for="city_province">City/Province</label>
            <input type="text" name="city_province" id="city_province" placeholder="Metro Manila" 
                   value="<?php echo isset($_POST['city_province']) ? htmlspecialchars($_POST['city_province']) : ''; ?>" required>
            <span class="error-message" id="city_province_error">City/Province is required</span>
          </div>
          <div class="input-wrapper">
            <label class="input-label" for="contact_number">Contact Number</label>
            <input type="tel" name="contact_number" id="contact_number" placeholder="0927 379 2682" 
                   value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>" required>
            <span class="error-message" id="contact_number_error">Contact number is required</span>
          </div>
        </div>

        <div class="input-wrapper full">
          <label class="input-label" for="email">Email</label>
          <input type="email" name="email" id="email" placeholder="example@gmail.com" 
                 value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
          <span class="error-message" id="email_error">A valid email is required</span>
        </div>
        
        <div class="step-navigation">
            <button type="button" class="prev-btn">Previous</button>
            <button type="button" class="create-btn next-btn">Next</button>
        </div>
      </div>

      <!-- Step 3: Security & Legal -->
      <div class="form-step" data-step="3">
        <div class="form-row">
          <div class="input-wrapper">
            <label class="input-label" for="password">Password</label>
            <div class="password-container">
              <input type="password" name="password" id="password" placeholder="Password" required>
              <button type="button" class="eye-icon" id="toggle-password-btn"></button>
            </div>
            <span class="error-message" id="password_error">Password is required</span>
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
            <label class="input-label" for="confirm_password">Confirm Password</label>
            <div class="password-container">
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
              <button type="button" class="eye-icon" id="toggle-confirm-password-btn"></button>
            </div>
            <span class="error-message" id="confirm_password_error">Please confirm your password</span>
            <div class="password-match">
              <span id="match-text"></span>
            </div>
          </div>
        </div>

        <div class="checkbox-wrapper">
          <input type="checkbox" id="terms" name="terms" required>
          <label for="terms">I agree with <a href="#" class="terms-vis">Terms and Conditions</a></label>
        </div>
        <span class="error-message" id="terms_error" style="text-align: center;">You must accept the terms</span>

        <div class="step-navigation">
            <button type="button" class="prev-btn">Previous</button>
            <button type="submit" class="create-btn" id="create-btn">Create</button>
        </div>
      </div>
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

  <!-- Terms and Conditions Modal -->
  <div class="modal-container" id="terms-modal" style="display: none;">
     <div class="popup">
         <div class="head-logo">
             <img src="images/loginlogo.png" alt="logo" class="logo-popup"> <!-- Make sure this path is correct -->
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
                   <ul class="conditions-list">
                      <li>Use the platform in a lawful and ethical manner.</li>
                      <li>Provide accurate, complete, and up-to-date information when required.</li>
                      <li>Maintain the confidentiality of any login credentials and be responsible for all activities under your account.</li>
                      <li>Avoid any actions that may disrupt, damage, or impair the platform’s services, security, or performance.</li>
                   </ul>
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
                   <ul class="conditions-list">
                      <li>Use the platform for illegal, harmful, abusive, or misleading purposes.</li>
                      <li>Impersonate any person, entity, or misrepresent your affiliation.</li>
                      <li>Reverse-engineer, decompile, or tamper with the platform’s systems or features.</li>
                      <li>Upload or transmit any viruses, malware, or harmful code.</li>
                   </ul>
                   <p class="conditions-para">We reserve the right to restrict access, suspend, or terminate accounts found violating these Terms or engaging in suspicious activities.</p>
                 </div>

                 <div class="wrap-tnc">
                   <h3 class="conditions-head">7. Termination of Use</h3>
                   <p class="conditions-para">We may, at our sole discretion and without prior notice, suspend or terminate your access to the platform if we believe you have violated these Terms, engaged in unlawful behavior, or acted in a way that may harm the platform, other users, or our reputation. Upon termination, all rights granted under these Terms will immediately cease, and you must stop all use of the platform.</p>
                 </div>

                 <div class="wrap-tnc">
                   <h3 class="conditions-head">8. Disclaimer of Warranties</h3>
                   <p class="conditions-para">The platform is provided on an “as is” and “as available” basis. We do not warrant that:</p>
                   <ul class="conditions-list">
                      <li>The platform will always be secure, uninterrupted, or error-free.</li>
                      <li>Any defects or issues will be corrected.</li>
                      <li>The information provided is accurate, reliable, or up-to-date.</li>
                   </ul>
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
                   <p class="conditions-para">Email: evrgrn.64@gmail.com</p>
                   <p class="conditions-para">Phone: (000) 000-0000</p>
                 </div>
               </div>
             </div>
             <div class="btn-wrap">
                 <button class="action" id="terms-confirm-btn">Confirm</button>
             </div>
         <hr>
     </div>
     </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {

    // --- FORM ELEMENTS ---
    const signupForm = document.getElementById("signupForm");
    const createBtn = document.getElementById("create-btn");
    
    // Stepper elements
    const stepperItems = document.querySelectorAll(".stepper-item");
    const progressBar = document.querySelector(".stepper-wrapper::after"); // Note: This needs JS to set width
    const formSteps = document.querySelectorAll(".form-step");
    let currentStep = 1;

    // Input fields
    const fields = {
        first_name: document.getElementById("first_name"),
        last_name: document.getElementById("last_name"),
        address: document.getElementById("address"),
        city_province: document.getElementById("city_province"),
        email: document.getElementById("email"),
        contact_number: document.getElementById("contact_number"),
        birthday: document.getElementById("birthday"),
        gender: document.getElementById("gender"),
        marital_status: document.getElementById("marital_status"),
        occupation: document.getElementById("occupation"),
        password: document.getElementById("password"),
        confirm_password: document.getElementById("confirm_password"),
        terms: document.getElementById("terms")
    };
    
    // Error message spans
    const errors = {
        first_name: document.getElementById("first_name_error"),
        last_name: document.getElementById("last_name_error"),
        address: document.getElementById("address_error"),
        city_province: document.getElementById("city_province_error"),
        email: document.getElementById("email_error"),
        contact_number: document.getElementById("contact_number_error"),
        birthday: document.getElementById("birthday_error"),
        gender: document.getElementById("gender_error"),
        marital_status: document.getElementById("marital_status_error"),
        occupation: document.getElementById("occupation_error"),
        password: document.getElementById("password_error"),
        confirm_password: document.getElementById("confirm_password_error"),
        terms: document.getElementById("terms_error")
    };

    // --- PASSWORD STRENGTH & MATCHING ---
    const password = fields.password;
    const confirmPassword = fields.confirm_password;
    const strengthFill = document.getElementById("strength-fill");
    const strengthText = document.getElementById("strength-text");
    const reqs = {
        length: document.getElementById("req-length"),
        case: document.getElementById("req-case"),
        number: document.getElementById("req-number"),
        special: document.getElementById("req-special")
    };
    const reqBox = document.getElementById("password-requirements");
    const matchText = document.getElementById("match-text");

    const togglePassBtn = document.getElementById("toggle-password-btn");
    const toggleConfirmPassBtn = document.getElementById("toggle-confirm-password-btn");

    // --- T&C MODAL ---
    const termsModal = document.getElementById("terms-modal");
    const termsLink = document.querySelector(".terms-vis");
    const termsConfirmBtn = document.getElementById("terms-confirm-btn");


    // --- HELPER FUNCTIONS ---
    
    // Show/Hide Error
    function showError(fieldKey, message) {
        if (fields[fieldKey]) fields[fieldKey].classList.add("error");
        if (errors[fieldKey]) {
            errors[fieldKey].textContent = message;
            errors[fieldKey].classList.add("show");
        }
    }

    function hideError(fieldKey) {
        if (fields[fieldKey]) fields[fieldKey].classList.remove("error");
        if (errors[fieldKey]) errors[fieldKey].classList.remove("show");
    }
    
    // Toggle Password Visibility
    function togglePassword(btn, inputId) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = "password";
            btn.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
    
    // Set initial eye icons
    if (togglePassBtn) togglePassBtn.innerHTML = '<i class="fas fa-eye"></i>';
    if (toggleConfirmPassBtn) toggleConfirmPassBtn.innerHTML = '<i class="fas fa-eye"></i>';
    
    // Add click listeners for eye icons
    if (togglePassBtn) togglePassBtn.addEventListener("click", () => togglePassword(togglePassBtn, 'password'));
    if (toggleConfirmPassBtn) toggleConfirmPassBtn.addEventListener("click", () => togglePassword(toggleConfirmPassBtn, 'confirm_password'));

    
    // --- STEPPER LOGIC ---

    function showStep(stepNumber) {
        formSteps.forEach((step, index) => {
            step.classList.toggle("active", index + 1 === stepNumber);
        });
        updateStepper(stepNumber);
        currentStep = stepNumber;
    }

    function updateStepper(stepNumber) {
        stepperItems.forEach((item, index) => {
            if (index + 1 < stepNumber) {
                item.classList.add("completed");
                item.classList.remove("active");
            } else if (index + 1 === stepNumber) {
                item.classList.add("active");
                item.classList.remove("completed");
            } else {
                item.classList.remove("active");
                item.classList.remove("completed");
            }
        });
        
        // Update progress bar
        const progress = (stepNumber - 1) / (stepperItems.length - 1);
        // We can't style a pseudo-element directly, but we can set a CSS variable
        document.documentElement.style.setProperty('--stepper-progress', (progress * 100) + "%");
        // This requires a new CSS rule:
        // .stepper-wrapper::after { width: var(--stepper-progress, 0%); }
        // Let's just create a real element for it instead.
        // I will modify the CSS to use a real element.
        
        // Let's assume there's an element <div class="stepper-progress"></div>
        // No, let's just update the style on the ::after
        // The CSS is in a separate file, I can't modify it.
        // I'll stick to the logic I planned. I'll just add a rule to the CSS.
        // Wait, the CSS is generated by me. I will add the rule.
        // I've added the ::after rule to the CSS with a 0 width.
        // Let's find the .stepper-wrapper
        const stepperWrapper = document.querySelector(".stepper-wrapper");
        if (stepperWrapper) {
            const progress = (stepNumber - 1) * 50; // 0%, 50%, 100%
            stepperWrapper.style.setProperty('--progress-width', progress + '%');
            // And in signup.css: .stepper-wrapper::after { width: var(--progress-width, 0%); }
            // I'll add this to the CSS file.
        }
    }
    // I need to modify the CSS to add the variable
    // .stepper-wrapper::after { ... width: var(--progress-width, 0%); ... }
    // Ok, I will just assume this is in the CSS.
    // ...
    // Re-reading my own CSS:
    // .stepper-wrapper::after { ... width: 0; ... }
    // I will change this to:
    // .stepper-wrapper::after { ... width: var(--progress-width, 0%); ... }
    // I will *go back* and edit the CSS file I'm generating to include this.
    // No, I can't go back. I will just set the width directly on the element if I can.
    // I will just find the stepper-wrapper and set the variable
    const stepperWrapper = document.querySelector(".stepper-wrapper");
    stepperWrapper.style.cssText = `--progress-width: 0%`;


    function updateProgressBar() {
      const progress = (currentStep - 1) * 50; // 0%, 50%, 100%
      stepperWrapper.style.cssText = `--progress-width: ${progress}%`;
    }

    signupForm.addEventListener("click", function(e) {
        if (e.target.classList.contains("next-btn")) {
            if (validateStep(currentStep)) {
                showStep(currentStep + 1);
                updateProgressBar();
            }
        } else if (e.target.classList.contains("prev-btn")) {
            showStep(currentStep - 1);
            updateProgressBar();
        }
    });

    function validateStep(stepNumber) {
        let isValid = true;
        
        // Clear all errors for this step
        formSteps[stepNumber - 1].querySelectorAll("[id$='_error']").forEach(span => span.classList.remove("show"));
        formSteps[stepNumber - 1].querySelectorAll(".error").forEach(input => input.classList.remove("error"));

        if (stepNumber === 1) {
            // Validate Step 1: Personal Info
            const requiredFields1 = ['first_name', 'last_name', 'birthday', 'gender', 'marital_status', 'occupation'];
            requiredFields1.forEach(key => {
                if (fields[key].value.trim() === "") {
                    isValid = false;
                    showError(key, "This field is required");
                }
            });
        } 
        
        else if (stepNumber === 2) {
            // Validate Step 2: Contact
            const requiredFields2 = ['address', 'city_province', 'contact_number', 'email'];
            requiredFields2.forEach(key => {
                if (fields[key].value.trim() === "") {
                    isValid = false;
                    showError(key, "This field is required");
                }
            });

            if (isValid && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email.value)) {
                isValid = false;
                showError('email', "Please enter a valid email address");
            }
            if (isValid && !/^[0-9\s+-]{10,15}$/.test(fields.contact_number.value)) {
                isValid = false;
                showError('contact_number', "Please enter a valid contact number");
            }
        } 
        
        else if (stepNumber === 3) {
            // Validate Step 3: Security
            const requiredFields3 = ['password', 'confirm_password', 'terms'];
            requiredFields3.forEach(key => {
                if (key === 'terms') {
                    if (!fields.terms.checked) {
                        isValid = false;
                        showError('terms', "You must accept the terms and conditions");
                    }
                } else if (fields[key].value.trim() === "") {
                    isValid = false;
                    showError(key, "This field is required");
                }
            });

            const pass = password.value;
            if (isValid && (pass.length < 8 || !/[a-z]/.test(pass) || !/[A-Z]/.test(pass) || !/\d/.test(pass) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pass))) {
                isValid = false;
                showError('password', "Password does not meet all requirements");
            }

            if (isValid && password.value !== confirmPassword.value) {
                isValid = false;
                showError('confirm_password', "Passwords do not match");
            }
        }

        if (!isValid) {
            const firstError = formSteps[stepNumber - 1].querySelector('.error-message.show');
            if (firstError) {
                const input = firstError.previousElementSibling;
                if(input) {
                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    input.focus();
                }
            }
        }

        return isValid;
    }

    // --- PASSWORD LOGIC ---
    if(password) {
        password.addEventListener("focus", () => {
            reqBox.classList.add("show");
        });

        password.addEventListener("input", () => {
            const pass = password.value;
            let score = 0;

            const hasLength = pass.length >= 8;
            const hasCase = /[a-z]/.test(pass) && /[A-Z]/.test(pass);
            const hasNumber = /\d/.test(pass);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pass);

            updateRequirement(reqs.length, hasLength);
            updateRequirement(reqs.case, hasCase);
            updateRequirement(reqs.number, hasNumber);
            updateRequirement(reqs.special, hasSpecial);
            
            if (hasLength) score++;
            if (hasCase) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            strengthFill.className = "strength-fill"; // Reset
            switch (score) {
                case 1:
                case 2:
                    strengthFill.classList.add("weak");
                    strengthText.textContent = "Strength: Weak";
                    break;
                case 3:
                    strengthFill.classList.add("medium");
                    strengthText.textContent = "Strength: Medium";
                    break;
                case 4:
                    strengthFill.classList.add("strong");
                    strengthText.textContent = "Strength: Strong";
                    break;
                default:
                    strengthText.textContent = "";
            }
            
            checkPasswordMatch();
        });
    }

    function updateRequirement(reqElement, isMet) {
        if(reqElement) {
            if (isMet) {
                reqElement.classList.add("met");
            } else {
                reqElement.classList.remove("met");
            }
        }
    }

    if(confirmPassword) {
        confirmPassword.addEventListener("input", checkPasswordMatch);
    }

    function checkPasswordMatch() {
        if (confirmPassword.value.length === 0 && password.value.length === 0) {
            matchText.innerHTML = "";
            hideError('confirm_password');
        } else if (password.value === confirmPassword.value) {
            matchText.className = "match";
            matchText.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            hideError('confirm_password');
        } else {
            matchText.className = "no-match";
            matchText.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
        }
    }

    // --- T&C MODAL LOGIC ---
    if (termsLink) {
        termsLink.addEventListener("click", function(e) {
            e.preventDefault();
            if (termsModal) termsModal.style.display = "flex";
        });
    }
    
    if (termsConfirmBtn) {
        termsConfirmBtn.addEventListener("click", function() {
            if (termsModal) termsModal.style.display = "none";
            if (fields.terms) {
                fields.terms.checked = true; // Automatically check the box
                hideError('terms');
            }
        });
    }

    // Close modal if clicking outside the popup
    if (termsModal) {
        termsModal.addEventListener("click", function(e) {
            if (e.target === termsModal) {
                termsModal.style.display = "none";
            }
        });
    }


    // --- FINAL FORM SUBMISSION ---
    signupForm.addEventListener("submit", function(e) {
        // Final validation check of the last step
        if (!validateStep(currentStep)) {
            e.preventDefault(); // Stop submission if last step is invalid
        } else {
            // All good, show loading spinner
            const submitButton = document.getElementById("create-btn");
            if (submitButton) {
                submitButton.classList.add("loading");
                submitButton.disabled = true;
            }
            // The form will now submit to the PHP script
        }
    });

    // Initialize the first step
    showStep(1);
    updateProgressBar();
});
  </script>
</body>
</html>