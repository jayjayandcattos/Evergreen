<!---Loan_AppForm.php-->
<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../login.php");
    exit();
}

$conn = getDBConnection();
$user_email = $_SESSION['user_email'];

// Fetch user details from bank_customers - construct full_name from first, middle, last
$stmt = $conn->prepare("SELECT 
    CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name) as full_name,
    contact_number,
    email,
    customer_id
FROM bank_customers 
WHERE email = ?");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();

// Get account number from customer_accounts if it exists
$account_number = '';
if ($currentUser) {
    $acc_stmt = $conn->prepare("SELECT account_number FROM customer_accounts WHERE customer_id = ? LIMIT 1");
    if ($acc_stmt) {
        $acc_stmt->bind_param("i", $currentUser['customer_id']);
        $acc_stmt->execute();
        $acc_result = $acc_stmt->get_result();
        if ($acc_row = $acc_result->fetch_assoc()) {
            $account_number = $acc_row['account_number'];
        }
        $acc_stmt->close();
    }
}

if (!$currentUser) {
    // Fallback or error handling
    $currentUser = [
        'full_name' => '',
        'contact_number' => '',
        'email' => $user_email
    ];
}

$currentUser['account_number'] = $account_number;
$stmt->close();

// Fetch loan types - show all active loan types
$loanTypes = [];
$lt_result = $conn->query("SELECT id, name FROM loan_types ORDER BY name");
if ($lt_result) {
    while ($row = $lt_result->fetch_assoc()) {
        $loanTypes[] = $row;
    }
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

<!-- Modal -->
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
// Auto-select loan type from URL
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const loanTypeName = urlParams.get('loanType');
    if (loanTypeName) {
        const loanSelect = document.getElementById('loan_type');
        for (let option of loanSelect.options) {
            if (option.text.trim() === decodeURIComponent(loanTypeName).trim()) {
                option.selected = true;
                break;
            }
        }
    }

    // loan_appform.js
document.getElementById('loanForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = document.querySelector('.btn-submit');
    const originalText = submitBtn.textContent;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    try {
        const response = await fetch('submit_loan.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            alert('✅ ' + result.message + '\n\nRedirecting to dashboard...');
            
            // Redirect to dashboard
            window.location.href = result.redirect || 'index.php?scrollTo=dashboard';
        } else {
            // Show error message
            alert('❌ Error: ' + result.error);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Submission error:', error);
        alert('❌ An error occurred while submitting your application. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
    // File type validation
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