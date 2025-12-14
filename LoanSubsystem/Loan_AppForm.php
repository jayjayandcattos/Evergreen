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

// Get Savings/Checking accounts only for loan disbursement
$customerAccounts = [];
if ($currentUser) {
    $acc_stmt = $conn->prepare("
        SELECT ca.account_number, ca.account_id, bat.type_name 
        FROM customer_accounts ca
        INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        WHERE ca.customer_id = ? 
        AND ca.is_locked = 0
        AND (ca.account_status = 'active' OR ca.account_status IS NULL)
        AND bat.type_name IN ('Savings Account', 'Checking Account')
        ORDER BY bat.type_name, ca.account_number
    ");
    if ($acc_stmt) {
        $acc_stmt->bind_param("i", $currentUser['customer_id']);
        $acc_stmt->execute();
        $acc_result = $acc_stmt->get_result();
        while ($acc_row = $acc_result->fetch_assoc()) {
            $customerAccounts[] = $acc_row;
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

$stmt->close();

// Fetch loan types - show all active loan types
$loanTypes = [];
$lt_result = $conn->query("SELECT id, name FROM loan_types ORDER BY name");
if ($lt_result) {
    while ($row = $lt_result->fetch_assoc()) {
        $loanTypes[] = $row;
    }
}

// ✅ FIXED: Fetch valid ID types from loan_valid_id table (correct column name is 'valid_id_type')
$validIdTypes = [];
$id_result = $conn->query("SELECT id, valid_id_type FROM loan_valid_id ORDER BY valid_id_type");
if ($id_result) {
    while ($row = $id_result->fetch_assoc()) {
        $validIdTypes[] = $row;
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
              <label for="account_number">Disbursement Account <span class="required">*</span></label>
              <?php if (empty($customerAccounts)): ?>
                <div class="alert alert-warning" style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 4px; color: #856404; margin-top: 8px;">
                  <strong>No Active Account Found!</strong><br>
                  You need to have an active Savings or Checking account to apply for a loan. 
                  Please <a href="../bank-system/evergreen-marketing/evergreen_form.php" style="color: #856404; text-decoration: underline;">apply for an account</a> first.
                </div>
                <input type="hidden" name="account_number" value="">
              <?php else: ?>
                <select name="account_number" id="account_number" required>
                  <option value="">Select account to receive loan</option>
                  <?php foreach ($customerAccounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['account_number']) ?>">
                      <?= htmlspecialchars($acc['account_number']) ?> - <?= htmlspecialchars($acc['type_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
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
                     placeholder="Loan Amount (Min ₱5,000)" min="5000" step="0.01" required />
              <span class="validation-message" id="amount-error"></span>
            </div>

            <div class="input-container">
              <label for="purpose">Purpose of Loan <span class="required">*</span></label>
              <textarea name="purpose" id="purpose" placeholder="Describe the purpose of your loan" required></textarea>
              <span class="validation-message" id="purpose-error"></span>
            </div>
          </div>
        </section>

        <section id="step-supporting-details">
          <h2>Supporting Details</h2>
          <div class="input-group">
            <!-- ✅ FIXED: Valid ID Type dropdown now populated from loan_valid_id table -->
            <div class="input-container">
              <label for="loan_valid_id_type">Valid ID Type <span class="required">*</span></label>
              <select name="loan_valid_id_type" id="loan_valid_id_type" required>
                <option value="">Select Valid ID</option>
                <?php foreach ($validIdTypes as $idType): ?>
                  <option value="<?= (int)$idType['id'] ?>"><?= htmlspecialchars($idType['valid_id_type']) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="validation-message" id="valid-id-type-error"></span>
            </div>

            <!-- ✅ FIXED: ID Number input field (text type for alphanumeric IDs) -->
            <div class="input-container">
              <label for="valid_id_number">ID Number <span class="required">*</span></label>
              <input type="text" name="valid_id_number" id="valid_id_number" 
                     placeholder="Enter your ID number" maxlength="150" required />
              <span class="validation-message" id="valid-id-number-error"></span>
            </div>

            <div class="input-container">
              <label for="attachment">Upload Valid ID <span class="required">*</span></label>
              <small>Accepted: JPG, JPEG, PNG, PDF, DOC, DOCX (Max 5MB)</small>
              <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required />
              <span class="validation-message" id="attachment-error"></span>
            </div>

            <div class="input-container">
              <label for="proof_of_income">Upload Proof of Income / Payslip <span class="required">*</span></label>
              <small>Accepted: JPG, JPEG, PNG, PDF, DOC, DOCX (Max 5MB)</small>
              <input type="file" name="proof_of_income" id="proof_of_income" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required />
              <span class="validation-message" id="proof-income-error"></span>
            </div>

            <div class="input-container">
              <label for="coe_document">Upload Certificate of Employment (COE) <span class="required">*</span></label>
              <small>Accepted: PDF, DOC, DOCX only (Max 5MB)</small>
              <input type="file" name="coe_document" id="coe_document" accept=".pdf,.doc,.docx" required />
              <span class="validation-message" id="coe-error"></span>
            </div>
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

    // File type and size validation
    const validIdInput = document.getElementById('attachment');
    const proofInput = document.getElementById('proof_of_income');
    const coeInput = document.getElementById('coe_document');

    const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
    const validIdTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    const coeTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    function validateFile(input, allowedTypes, errorId) {
        const file = input.files[0];
        const errorSpan = document.getElementById(errorId);
        if (file) {
            // Check file type
            if (!allowedTypes.includes(file.type)) {
                errorSpan.textContent = 'Invalid file type. Please upload an allowed format.';
                input.value = '';
                return false;
            }
            // Check file size
            if (file.size > maxFileSize) {
                errorSpan.textContent = 'File size exceeds 5MB. Please upload a smaller file.';
                input.value = '';
                return false;
            }
            errorSpan.textContent = '';
            return true;
        }
    }

    validIdInput.addEventListener('change', () => validateFile(validIdInput, validIdTypes, 'attachment-error'));
    proofInput.addEventListener('change', () => validateFile(proofInput, validIdTypes, 'proof-income-error'));
    coeInput.addEventListener('change', () => validateFile(coeInput, coeTypes, 'coe-error'));
});

// Show terms modal on form submit
document.getElementById('loanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if user has selected an account
    const accountSelect = document.getElementById('account_number');
    if (accountSelect && accountSelect.tagName === 'SELECT' && !accountSelect.value) {
        alert('Please select an account to receive the loan disbursement.');
        accountSelect.focus();
        return false;
    }
    
    // Check if no accounts available (hidden input case)
    if (accountSelect && accountSelect.type === 'hidden' && !accountSelect.value) {
        alert('You need to have an active Savings or Checking account to apply for a loan. Please apply for an account first.');
        return false;
    }
    
    // Validate form
    if (this.checkValidity()) {
        // Show terms and agreement modal
        const modal = document.getElementById('combined-modal');
        const applicationContent = document.querySelector('.page-content');
        const termsView = document.getElementById('terms-view');
        const confirmationView = document.getElementById('confirmation-view');
        
        // Show terms view, hide confirmation
        termsView.classList.remove('hidden');
        confirmationView.classList.add('hidden');
        
        modal.classList.remove('hidden');
        applicationContent.classList.add('blur-background');
        document.body.style.overflow = 'hidden';
    } else {
        // Let browser show validation messages
        this.reportValidity();
    }
});

// Accept terms and submit form
async function acceptTerms() {
    const form = document.getElementById('loanForm');
    const formData = new FormData(form);
    const acceptBtn = document.querySelector('.btn-accept');
    const originalText = acceptBtn.textContent;
    
    // Disable button and show loading
    acceptBtn.disabled = true;
    acceptBtn.textContent = 'Submitting...';
    
    try {
        const response = await fetch('submit_loan.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Hide terms view and show confirmation view
            document.getElementById('terms-view').classList.add('hidden');
            document.getElementById('confirmation-view').classList.remove('hidden');
            
            // Update reference details
            if (result.loan_id) {
                document.getElementById('ref-number').textContent = 'LOAN-' + String(result.loan_id).padStart(6, '0');
            }
            const today = new Date().toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            document.getElementById('ref-date').textContent = today;
        } else {
            // Show error message
            alert('❌ Error: ' + result.error);
            acceptBtn.disabled = false;
            acceptBtn.textContent = originalText;
        }
    } catch (error) {
        console.error('Submission error:', error);
        alert('❌ An error occurred while submitting your application. Please try again.');
        acceptBtn.disabled = false;
        acceptBtn.textContent = originalText;
    }
}

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