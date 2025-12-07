<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<!------------------------------- BACKGROUND IMAGE --------------------------------------------------------------------------------->
    <?php if (!empty($data['from_account_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['from_account_error']; ?></div>
    <?php endif; ?>

    <?php if (!empty($data['recipient_number_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['recipient_number_error']; ?></div>
    <?php endif; ?>

    <?php if (!empty($data['recipient_name_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['recipient_name_error']; ?></div>
    <?php endif; ?>

    <?php if (!empty($data['amount_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['amount_error']; ?></div>
    <?php endif; ?>

    <?php if (!empty($data['message_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['message_error']; ?></div>
    <?php endif; ?>

    <?php if (!empty($data['other_error'])): ?>
        <div class="alert alert-danger alert-message"><?= $data['other_error']; ?></div>
    <?php endif; ?>
<div class="min-vh-100 d-flex align-items-center justify-content-center"
    style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('../img/trees_background.jpg'); 
    background-size: cover; 
    background-position: center;">
    <div class="container-fluid p-5" style="background-color: #ffffff5e;">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card border-0 shadow-lg" style="background-color: #f5f5f0; border-radius: 20px;">
                    <div class="card-body p-4 p-md-5">
                        
                        <!------- LOGO AND TITLE ----------------------------------------------------------------------------------->
                        <div class=" mb-4">
                            <div class="d-flex text-center align-items-center justify-content-center mb-3">
                                <div class="bg-dark rounded-circle me-2" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                    <img src="../img/logo.png" class="img-fluid" alt="Logo" style="max-height: 100%;">
                                </div>
                                <div class="text-start">
                                    <h5 class="fw-bold fs-5 mb-0">EVERGREEN</h5>
                                    <small class="text-muted">Secure. Invest. Achieve</small>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0" style="color: #003631;">Fund Transfer</h4>
                        </div>

                        <!------- FORM ------------------------------------------------------------------------------------------->
                        <form action="<?= URLROOT ."/customer/fund_transfer"?>" method="POST">

                            <!------- TRANSFER TYPE SELECTION ------------------------------------------------------------------->
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #003631;">Transfer Type:</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transfer_type" id="own_account" value="own_account" checked>
                                        <label class="form-check-label" for="own_account">
                                            Own Account
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="transfer_type" id="another_account" value="another_account">
                                        <label class="form-check-label" for="another_account">
                                            Another Evergreen Account
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($data['low_balance_confirm_required'])): ?>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> This transfer will bring your account balance below the required maintaining balance of
                                    <strong>PHP <?= number_format($data['maintaining_required'] ?? 500.00, 2); ?></strong>.
                                    Please confirm to proceed. By confirming you acknowledge the account may incur monthly service fees and possible restrictions.
                                </div>
                                <input type="hidden" name="confirm_low_balance" value="1">
                            <?php endif; ?>
                            
                            <!-- Sender Number with Balance Display -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold mb-0" style="color: #003631;">From Account:</label>
                                    <small class="text-muted">
                                        Available Balance: <span id="account_balance" class="fw-bold text-success">₱0.00</span>
                                    </small>
                                </div>
                                <select name="from_account" id="from_account" class="form-select" style="background-color: #e8e8df; border: none; border-radius: 8px;" required>
                                    <?php foreach($data['accounts'] as $account): ?>
                                        <?php 
                                        // Only allow Savings (1), Checking (2), and Loan (4) accounts to send money
                                        if (in_array($account->account_type_id, [1, 2, 4])): 
                                        ?>
                                        <option value="<?= $account->account_number?>" 
                                                data-balance="<?= number_format($account->ending_balance, 2, '.', '') ?>" 
                                                data-type="<?= $account->account_type_id ?>" 
                                                data-type-name="<?= $account->type_name ?>">
                                            <?= $account->account_number ?> (<?= $account->type_name ?>)
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!--- TO ACCOUNT (for own account transfers only) ------------------------------------------------>
                            <div class="mb-3" id="to_account_section" style="display: none;">
                                <label class="form-label fw-semibold" style="color: #003631;">To Account:</label>
                                <select name="to_account" id="to_account" class="form-select" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                                    <option value="">Select destination account</option>
                                    <?php foreach($data['accounts'] as $account): ?>
                                        <?php 
                                        // Only allow Savings (1) and Checking (2) to receive money
                                        // Loan accounts (4) cannot receive transfers
                                        if (in_array($account->account_type_id, [1, 2])): 
                                        ?>
                                        <option value="<?= $account->account_number?>" 
                                                data-type="<?= $account->account_type_id ?>" 
                                                data-type-name="<?= $account->type_name ?>">
                                            <?= $account->account_number ?> (<?= $account->type_name ?>)
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!--- ANOTHER ACCOUNT SECTION (for transfers to other customers) -------------------------------->
                            <div id="another_account_section">
                                <!--- RECIPIENT NUMBER ----------------------------------------------------------------------------->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #003631;">Recipient Account:</label>
                                    <input type="text" name="recipient_number" id="recipient_number" class="form-control" placeholder="ex. CHA-123-456" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                                </div>

                                <!--- RECIPIENT NAME ------------------------------------------------------------------------------->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: #003631;">Recipient Name:</label>
                                    <input type="text" name="recipient_name" id="recipient_name" class="form-control" placeholder="ex. Maria Allan Reviles" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                                </div>
                            </div>

                            <!--- AMOUNT ------------------------------------------------------------------------------------------->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between ">
                                    <label class="form-label fw-semibold" style="color: #003631;">Amount:</label>
                                    <div>
                                        <small id="insufficient_balance" class="text-danger d-none">
                                            <i class="bi bi-exclamation-circle"></i> Insufficient balance
                                        </small>
                                        <small id="remaining_text" class="text-muted d-none">
                                            Remaining after transfer: <span id="remaining_balance" class="fw-bold text-success">₱0.00</span>
                                        </small>
                                    </div>
                                </div>
                                <input type="number" id="transfer_amount" name="amount" class="form-control" placeholder="600" style="background-color: #e8e8df; border: none; border-radius: 8px;" step="0.01" min="0">
                            </div>

                            <!--- MESSAGE (only for another account transfers) -------------------------------------------------->
                            <div class="mb-4" id="message_section">
                                <label class="form-label fw-semibold" style="color: #003631;">Message:</label>
                                <textarea class="form-control" name="message" id="message" rows="2" placeholder="Optional" style="background-color: #e8e8df; border: none; border-radius: 8px;"></textarea>
                            </div>

                            <!--- TRANSACTION DETAILS ------------------------------------------------------------------------------>
                            <!-- <div class="mb-3 ms-3 me-2">
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Fee:</small>
                                    <small class="text-muted">+15.00</small>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Total Payment:</small>
                                    <small class="text-muted">615.00</small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-muted">Transaction ID:</small>
                                    <small class="text-muted">24DDUX82947SDA2</small>
                                </div>
                            </div> -->

                            <!--- CONTINUE BUTTON ---------------------------------------------------------------------------------->
                            <button type="submit" class="btn w-75 mx-auto d-block fw-semibold" style="background-color: #F1B24A; border-radius: 8px; padding: 12px;">
                                Continue
                            </button>

                            <!--- TERMS -------------------------------------------------------------------------------------------->
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    By clicking the continue, I agree with <a href="#Terms" class="text-decoration-none" style="color: #003631;">terms and terminologies</a>
                                </small>
                            </div>
                        </form>

                        <!------- LOW BALANCE CONFIRMATION MODAL ------------------------------------------------------------------>
                        <div class="modal fade" id="lowBalanceConfirmModal" tabindex="-1" aria-labelledby="lowBalanceModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="background-color: #f5f5f0; border-radius: 15px;">
                                    <div class="modal-header border-0" style="background-color: #003631;">
                                        <h5 class="modal-title text-white" id="lowBalanceModalLabel">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Low Balance Warning
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p class="mb-3">
                                            <strong>After this transfer, your account balance will be:</strong>
                                        </p>
                                        <div class="alert alert-warning mb-3" style="background-color: #fff3cd; border-color: #ffc107;">
                                            <h6 class="mb-0" style="color: #856404;">
                                                <span id="modalRemainingBalance" class="fw-bold">₱0.00</span>
                                            </h6>
                                        </div>
                                        <p class="mb-3">
                                            This is <strong>below the maintaining balance requirement</strong>. Consequences may include:
                                        </p>
                                        <ul class="small" style="color: #666;">
                                            <li>Monthly service fees will be charged</li>
                                            <li>Service interruptions or transaction restrictions</li>
                                            <li>Possible overdraft or additional charges</li>
                                            <li>Account may be flagged for closure if balance reaches zero</li>
                                        </ul>
                                        <p class="mt-3 mb-0">
                                            <strong style="color: #003631;">Do you want to proceed with this transfer?</strong>
                                        </p>
                                    </div>
                                    <div class="modal-footer border-0 p-3">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background-color: #6c757d;">
                                            Cancel
                                        </button>
                                        <button type="button" class="btn fw-semibold" id="confirmLowBalanceBtn" style="background-color: #F1B24A; color: #000;">
                                            Yes, Continue Transfer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
<script>
    const FEE = 15.00;
    // Minimum required remaining balance after transfer for confirmation modal
    const MIN_REQUIRED_BALANCE = 500.00;

  // Get current fee based on transfer type
  function getCurrentFee() {
    const transferType = document.querySelector('input[name="transfer_type"]:checked').value;
    return transferType === 'own_account' ? 0 : FEE;
  }

  // Toggle form fields based on transfer type
  function toggleTransferType() {
    const transferType = document.querySelector('input[name="transfer_type"]:checked').value;
    const toAccountSection = document.getElementById('to_account_section');
    const anotherAccountSection = document.getElementById('another_account_section');
    const messageSection = document.getElementById('message_section');
    const toAccountSelect = document.getElementById('to_account');
    const recipientNumber = document.getElementById('recipient_number');
    const recipientName = document.getElementById('recipient_name');
    const messageField = document.getElementById('message');
    
    if (transferType === 'own_account') {
      // Show own account fields
      toAccountSection.style.display = 'block';
      toAccountSelect.required = true;
      
      // Hide another account fields
      anotherAccountSection.style.display = 'none';
      messageSection.style.display = 'none';
      recipientNumber.required = false;
      recipientName.required = false;
      
      // Clear another account fields
      recipientNumber.value = '';
      recipientName.value = '';
      messageField.value = '';
      
      // Update to_account dropdown to exclude selected from_account
      updateToAccountOptions();
    } else {
      // Hide own account fields
      toAccountSection.style.display = 'none';
      toAccountSelect.required = false;
      toAccountSelect.value = '';
      
      // Show another account fields
      anotherAccountSection.style.display = 'block';
      messageSection.style.display = 'block';
      recipientNumber.required = true;
      recipientName.required = true;
    }
    
    // Update remaining balance calculation with new fee
    updateRemainingBalance();
  }

  // Update to_account dropdown options based on selected from_account
  function updateToAccountOptions() {
    const fromAccount = document.getElementById('from_account');
    const toAccount = document.getElementById('to_account');
    const selectedFromAccount = fromAccount.value;
    
    // Reset and rebuild options
    Array.from(toAccount.options).forEach(option => {
      if (option.value === '') return; // Keep the placeholder
      
      // Hide the option if it's the same as from_account
      if (option.value === selectedFromAccount) {
        option.style.display = 'none';
        option.disabled = true;
      } else {
        option.style.display = 'block';
        option.disabled = false;
      }
    });
    
    // Reset selection if current selection is now disabled
    if (toAccount.value === selectedFromAccount) {
      toAccount.value = '';
    }
  }

  // Initialize balance on page load
  function initializeBalance() {
    const selectElement = document.getElementById('from_account');
    updateBalance();
  }

  // Update balance when account selection changes
  function updateBalance() {
    const selectElement = document.getElementById('from_account');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
    
    const balanceDisplay = document.getElementById('account_balance');
    balanceDisplay.textContent = '₱' + balance.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    // Update to_account options if in own_account mode
    const transferType = document.querySelector('input[name="transfer_type"]:checked').value;
    if (transferType === 'own_account') {
      updateToAccountOptions();
    }
    
    // Update remaining balance calculation
    updateRemainingBalance();
  }

  // Update remaining balance when amount changes
  function updateRemainingBalance() {
    const selectElement = document.getElementById('from_account');
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
    
    const amountInput = document.getElementById('transfer_amount');
    const amount = parseFloat(amountInput.value) || 0;
    const currentFee = getCurrentFee();
    const total = amount + currentFee;
    const remaining = balance - total;
    
    const insufficientAlert = document.getElementById('insufficient_balance');
    const remainingText = document.getElementById('remaining_text');
    const remainingBalanceSpan = document.getElementById('remaining_balance');
    
    if (amount > 0) {
      if (balance < total) {
        insufficientAlert.classList.remove('d-none');
        remainingText.classList.add('d-none');
      } else {
        insufficientAlert.classList.add('d-none');
        remainingText.classList.remove('d-none');
        remainingBalanceSpan.textContent = '₱' + remaining.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
    } else {
      insufficientAlert.classList.add('d-none');
      remainingText.classList.add('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize balance on page load
    initializeBalance();
    toggleTransferType();
    
    // Listen for transfer type changes
    const transferTypeRadios = document.querySelectorAll('input[name="transfer_type"]');
    transferTypeRadios.forEach(radio => {
      radio.addEventListener('change', toggleTransferType);
    });
    
    // Listen for account selection changes
    const selectElement = document.getElementById('from_account');
    if (selectElement) {
      selectElement.addEventListener('change', updateBalance);
    }
    
    // Listen for amount input changes
    const amountInput = document.getElementById('transfer_amount');
    if (amountInput) {
      amountInput.addEventListener('input', updateRemainingBalance);
    }

        // Intercept form submit to warn if remaining balance will fall below minimum
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const selectElement = document.getElementById('from_account');
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                const balance = parseFloat(selectedOption.getAttribute('data-balance')) || 0;
                const amount = parseFloat(document.getElementById('transfer_amount').value) || 0;
                const currentFee = getCurrentFee();
                const total = amount + currentFee;
                const remaining = balance - total;

                // If insufficient funds, let server validation handle it (we still prevent form submit here)
                if (remaining < 0) {
                    e.preventDefault();
                    alert('Insufficient funds. Please enter a smaller amount or choose another account.');
                    return false;
                }

                // If remaining is below 500 but not negative, show modal confirmation
                if (remaining < MIN_REQUIRED_BALANCE) {
                    e.preventDefault();
                    // Update modal content with remaining balance
                    const modalRemainingElement = document.getElementById('modalRemainingBalance');
                    if (modalRemainingElement) {
                        modalRemainingElement.textContent = '₱' + remaining.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
                    }
                    // Show modal
                    const modalElement = document.getElementById('lowBalanceConfirmModal');
                    if (modalElement) {
                        const confirmModal = new bootstrap.Modal(modalElement);
                        confirmModal.show();
                        // Set up confirm button to submit form
                        const confirmBtn = document.getElementById('confirmLowBalanceBtn');
                        if (confirmBtn) {
                            confirmBtn.onclick = function() {
                                // Mark confirmation and resubmit
                                let hidden = form.querySelector('input[name="confirm_low_balance"]');
                                if (!hidden) {
                                    hidden = document.createElement('input');
                                    hidden.type = 'hidden';
                                    hidden.name = 'confirm_low_balance';
                                    hidden.value = '1';
                                    form.appendChild(hidden);
                                } else {
                                    hidden.value = '1';
                                }
                                confirmModal.hide();
                                form.submit();
                            };
                        }
                    }
                    return false;
                }
                // otherwise allow submit
            });
        }
    
    // --- Alert Message Handling ---
    const alerts = document.querySelectorAll('.alert-message');
    if (alerts.length > 0) {
      setTimeout(() => {
        alerts.forEach(alert => {
          alert.style.transition = 'opacity 0.5s ease';
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 500);
        });
      }, 5000);
    }
  });
</script>