<!---Loan_AppForm.php--->

<?php
session_start();

// Auto-login bridge: Check if user is logged in via marketing system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        header('Location: login.php');
        exit();
    }
}

// Get user data from bank_customers database
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";
$bankingConn = new mysqli($host, $user, $pass, $db);

$currentUser = null;
if (!$bankingConn->connect_error) {
    $email = $_SESSION['user_email'];
    $sql = "SELECT 
                bc.customer_id,
                bc.first_name,
                bc.middle_name,
                bc.last_name,
                bc.email,
                bc.contact_number,
                TRIM(CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name)) as full_name,
                (SELECT ca.account_number 
                 FROM customer_accounts ca 
                 WHERE ca.customer_id = bc.customer_id 
                 LIMIT 1) as account_number
            FROM bank_customers bc
            WHERE bc.email = ?
            LIMIT 1";
    $stmt = $bankingConn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentUser = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'account_number' => $row['account_number'] ?? 'N/A',
                'contact_number' => $row['contact_number'] ?? 'N/A',
                'job' => 'Not Specified', // Can be updated later if stored in database
                'monthly_salary' => 0 // Can be updated later if stored in database
            ];
        }
        $stmt->close();
    }
    $bankingConn->close();
}

// If user not found in database, redirect to login
if (!$currentUser) {
    header('Location: login.php?error=invalid');
    exit();
}

// 🔌 Connect to DB to fetch loan types from `loan_types` table
$host = "localhost";
$user = "root";
$pass = "";
$db = "loan_system";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// ✅ Fetch loan types from `loan_types` table
$result = $conn->query("SELECT id, name FROM loan_types ORDER BY name");
$loanTypes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $loanTypes[] = $row;
    }
} else {
    die("No loan types available. Please contact admin.");
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Loan Application Form</title>
  <link rel="stylesheet" href="Loan_AppForm.css" />
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-content">
  <section class="application-container">
    <div class="form-section">
      <h1>Loan Application Form</h1>
      <p class="subtitle">Please review your account and loan details below.</p>

      <form id="loanForm" action="submit_loan.php" method="POST" enctype="multipart/form-data">

        <section id="step-account-info">
          <h2>Account Information</h2>
          <div class="input-group">
            <div class="input-container">
              <input type="text" name="full_name" id="full_name" 
                     value="<?= htmlspecialchars($currentUser['full_name']) ?>" 
                     placeholder="Full Name (e.g., John Doe)" required readonly />
              <span class="validation-message" id="name-error"></span>
            </div>
            <div class="input-container">
              <input type="text" name="account_number" id="account_number" 
                     value="<?= htmlspecialchars($currentUser['account_number']) ?>" 
                     placeholder="Account Number (10 digits)" required readonly />
              <span class="validation-message" id="account-error"></span>
            </div>
            <div class="input-container">
              <input type="tel" name="contact_number" id="contact_number" 
                     value="<?= htmlspecialchars($currentUser['contact_number']) ?>" 
                     placeholder="Contact Number (+63...)" required readonly />
              <span class="validation-message" id="contact-error"></span>
            </div>
            <div class="input-container">
              <input type="email" name="email" id="email" 
                     value="<?= htmlspecialchars($currentUser['email']) ?>" 
                     placeholder="Email Address" required readonly />
              <span class="validation-message" id="email-error"></span>
            </div>
          </div>
        </section>

        <section id="step-loan-details">
          <h2>Loan Details</h2>
          <div class="input-group">
            <div class="input-container">
              <label for="loan_type">Loan Type <span class="required">*</span></label>
              <!-- ✅ FIXED: Now uses dynamic loan types from DB and sends loan_type_id -->
              <select name="loan_type_id" id="loan_type" required>
  <option value="">Select Loan Type</option>
  <?php foreach ($loanTypes as $type): ?>
    <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
  <?php endforeach; ?>
</select>
              <span class="validation-message" id="loan-type-error"></span>
            </div>

            <div class="input-container">
              <label for="loan_terms">Loan Term <span class="required">*</span></label>
              <select name="loan_terms" id="loan_terms" required>
                <option value="">Select Loan Terms</option>
                <option value="6 Months">6 Months</option>
                <option value="12 Months">12 Months</option>
                <option value="18 Months">18 Months</option>
                <option value="24 Months">24 Months</option>
                <option value="30 Months">30 Months</option>
                <option value="36 Months">36 Months</option>
              </select>
              <span class="validation-message" id="loan-terms-error"></span>
            </div>

            <div class="input-container">
              <label for="loan_amount">Loan Amount <span class="required">*</span></label>
              <input type="number" name="loan_amount" id="loan_amount" 
                     placeholder="Loan Amount (Min ₱5,000)" min="5000" required />
              <span class="validation-message" id="amount-error"></span>
            </div>

            <div class="input-container">
              <label for="purpose">Purpose of Loan <span class="required">*</span></label>
              <textarea name="purpose" id="purpose" placeholder="Describe the purpose of your loan" required></textarea>
              <span class="validation-message" id="purpose-error"></span>
            </div>
          </div>

          <!-- ✅ DOCUMENT UPLOADS WITH FULL LABELS -->
          <div class="input-container">
            <label for="attachment">Upload Valid ID <span class="required">*</span></label>
            <small>Accepted: JPG, JPEG, PNG, PDF, DOC, DOCX</small>
            <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required />
            <span class="validation-message" id="attachment-error"></span>
          </div>
          <div class="input-container">
            <label for="proof_of_income">Upload Proof of Income / Payslip <span class="required">*</span></label>
            <small>Accepted: JPG, JPEG, PNG, PDF, DOC, DOCX</small>
            <input type="file" name="proof_of_income" id="proof_of_income" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required />
            <span class="validation-message" id="proof-income-error"></span>
          </div>
          <div class="input-container">
            <label for="coe_document">Upload Certificate of Employment (COE) <span class="required">*</span></label>
            <small>Accepted: PDF, DOC, DOCX only (no images)</small>
            <input type="file" name="coe_document" id="coe_document" accept=".pdf,.doc,.docx" required />
            <span class="validation-message" id="coe-error"></span>
          </div>
        </section>

        <div class="form-actions">
          <button class="btn btn-back" type="button" onclick="location.href='index.php'">Back</button>
          <button type="submit" class="btn btn-submit">Submit Application</button>
        </div>
      </form>
    </div>

    <aside class="progress">
      <h3>Application Progress</h3>
      <div class="progress-step" id="progress-account">
        <span class="circle"></span><span>Account Information</span>
      </div>
      <div class="progress-step" id="progress-loan">
        <span class="circle"></span><span>Loan Details</span>
      </div>
    </aside>
  </section>
</div>

<!-- Modal (unchanged) -->
<div id="combined-modal" class="modal hidden">
  <div class="modal-content">
    <div id="terms-view">
      <div class="modal-header">
        <img src="logo.png" alt="Evergreen Logo" class="logo-small">
        <h2>Terms and Agreement</h2>
      </div>
      <p class="subtitle-text">Please review our terms and conditions carefully before proceeding</p>

      <div class="terms-body" style="max-height: 300px; overflow-y:auto;">
        <h3>1. Overview</h3>
        <p>By using Evergreen Bank services, you agree to these Terms and our Privacy Policy...</p>
        <h3>2. Account Terms</h3>
        <p>You must provide accurate, current, and complete account information...</p>
        <h3>3. Privacy and Data Protection</h3>
        <p>We take privacy seriously and implement reasonable security measures...</p>
        <h3>4. Fees and Charges</h3>
        <p>Fees are deducted automatically as outlined in our Fee Schedule...</p>
        <h3>5. Security Measures</h3>
        <p>We employ strong authentication methods and monitor accounts for suspicious activity...</p>
        <h3>6. Dispute Resolution</h3>
        <p>Any disputes shall be resolved under binding arbitration according to applicable law.</p>
      </div>

      <div class="modal-footer">
        <div class="acceptance-text">
          By clicking "I Accept", you acknowledge that you have read and agree to these Terms.
        </div>
        <div class="modal-actions">
          <button class="btn btn-accept" onclick="acceptTerms()">I Accept</button>
          <button class="btn btn-decline" onclick="closeModal()">I Decline</button>
        </div>
      </div>
    </div>

    <div id="confirmation-view" class="hidden">
      <div class="confirm-modal-content">
        <div class="success-icon">
          <img src="check.png" alt="Success" style="width: 100px; height: 100px;">
        </div>

        <h2>Loan Application Submitted Successfully!</h2>
        <p class="message-text">Your loan request has been received. You will receive an update soon.</p>

        <div class="reference-details">
          Reference No: <span id="ref-number"></span><br>
          Date: <span id="ref-date"></span>
        </div>

        <button class="btn btn-dashboard" onclick="location.href='index.php?scrollTo=dashboard'">Go To Dashboard</button>
      </div>
    </div>
  </div>
</div>

<script src="loan_appform.js"></script>

<script>
// Auto-select loan type from URL — now matches by NAME (not ID) to support ?loanType=Personal%20Loan
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const loanTypeName = urlParams.get('loanType');
    if (loanTypeName) {
        const loanSelect = document.getElementById('loan_type');
        for (let option of loanSelect.options) {
            // Compare option TEXT (not value) to URL parameter
            if (option.text.trim() === decodeURIComponent(loanTypeName).trim()) {
                option.selected = true;
                break;
            }
        }
    }

    // ✅ File type validation
    const validIdInput = document.getElementById('attachment');
    const proofInput = document.getElementById('proof_of_income');
    const coeInput = document.getElementById('coe_document');

    const validIdTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const coeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    function validateFile(input, allowedTypes, errorId) {
        const file = input.files[0];
        const errorSpan = document.getElementById(errorId);
        if (file) {
            if (!allowedTypes.includes(file.type)) {
                errorSpan.textContent = 'Invalid file type. Please upload an allowed format.';
                input.value = '';
            } else {
                errorSpan.textContent = '';
            }
        }
    }

    validIdInput.addEventListener('change', () => validateFile(validIdInput, validIdTypes, 'attachment-error'));
    proofInput.addEventListener('change', () => validateFile(proofInput, validIdTypes, 'proof-income-error'));
    coeInput.addEventListener('change', () => validateFile(coeInput, coeTypes, 'coe-error'));
});

function closeModal() {
    const combinedModal = document.getElementById('combined-modal');
    const applicationContent = document.querySelector('.page-content');
    combinedModal.classList.add("hidden");
    applicationContent.classList.remove('blur-background');
    document.body.style.overflow = 'auto';
}
</script>

</body>
</html>