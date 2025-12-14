<?php require_once ROOT_PATH . '/app/views/layouts/header.php';?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <h2 class="fw-bold mb-4" style="color: #003631;">Loan Repayment Center</h2>
            <p class="text-muted mb-5">Welcome, <?= htmlspecialchars($data['first_name']); ?>. View your active loan applications below and select one to make a payment from your account.</p>

            <?php 
            // Display session messages
            if (isset($_SESSION['payment_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['payment_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['payment_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['payment_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['payment_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['payment_error']); ?>
            <?php endif; ?>

            <?= $data['message']; // Display inline messages ?>

            <?php if (empty($data['active_loans'])): ?>
                <div class="card p-5 text-center shadow-lg" style="border: 2px dashed #bba27bff; background-color: #f8f9fa;">
                    <h4 class="text-muted">You have no active loan applications with outstanding balances.</h4>
                    <p class="text-secondary">Congratulations! All your loans may be fully settled or you have none yet.</p>
                </div>
            <?php else: ?>
                
                <div class="mb-5">
                    <h4 class="fw-semibold mb-3 text-secondary">Outstanding Loan Applications (<?= count($data['active_loans']); ?>)</h4>
                    <div class="list-group shadow-sm rounded-4">
                        <?php foreach ($data['active_loans'] as $loan): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-4">
                                <div>
                                    <h5 class="mb-1 fw-bold" style="color: #003631;"><?= htmlspecialchars($loan->loan_type); ?> (ID: <?= htmlspecialchars($loan->application_id); ?>)</h5>
                                    <small class="text-muted d-block">Account: <?= htmlspecialchars($loan->account_number); ?> | Applied: <?= htmlspecialchars($loan->application_date); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="d-block fw-bold fs-5" style="color: #bba27bff;">₱<?= number_format($loan->remaining_balance, 2); ?></span>
                                    <small class="text-muted">Remaining Balance</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card p-4 p-md-5 shadow-lg rounded-4" style="background-color: #ffffff; border: 1px solid #0036311A;">
                    <h4 class="fw-bold mb-4 border-bottom pb-3" style="color: #003631;">Make a Payment</h4>
                    <form action="<?= URLROOT; ?>/customer/pay_loan" method="POST">
                        
                        <!-- Select Loan -->
                        <div class="mb-4">
                            <label for="loan_id" class="form-label fw-semibold">Select Loan Application to Pay</label>
                            <select class="form-select form-select-lg shadow-sm <?= !empty($data['loan_id_error']) ? 'is-invalid' : ''; ?>" 
                                    id="loan_id" name="loan_id" required>
                                <option value="" disabled selected>Choose a loan application</option>
                                <?php foreach ($data['active_loans'] as $loan): ?>
                                    <option 
                                        value="<?= htmlspecialchars($loan->application_id); ?>" 
                                        data-balance="<?= htmlspecialchars($loan->remaining_balance); ?>"
                                        <?= isset($data['loan_id']) && $data['loan_id'] == $loan->application_id ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($loan->loan_type); ?> (ID: <?= htmlspecialchars($loan->application_id); ?>) - Balance: ₱<?= number_format($loan->remaining_balance, 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($data['loan_id_error'])): ?>
                                <div class="invalid-feedback d-block"><?= $data['loan_id_error']; ?></div>
                            <?php endif; ?>
                            <small id="loanHelp" class="form-text text-muted">The current remaining balance will update below.</small>
                        </div>
                        
                        <!-- Select Payment Account -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="source_account" class="form-label fw-semibold mb-0">Payment Account:</label>
                                <small class="text-muted">
                                    Available Balance: <span id="account_balance" class="fw-bold text-success">₱0.00</span>
                                </small>
                            </div>
                            <select class="form-select form-select-lg shadow-sm <?= !empty($data['source_account_error']) ? 'is-invalid' : ''; ?>" 
                                    id="source_account" name="source_account" required>
                                <option value="" disabled selected>Choose account to pay from</option>
                                <?php foreach ($data['accounts'] as $account): ?>
                                    <option 
                                        value="<?= htmlspecialchars($account->account_number); ?>" 
                                        data-balance="<?= number_format($account->ending_balance, 2, '.', ''); ?>"
                                        <?= isset($data['source_account']) && $data['source_account'] == $account->account_number ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($account->account_number); ?> (<?= htmlspecialchars($account->type_name); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($data['source_account_error'])): ?>
                                <div class="invalid-feedback d-block"><?= $data['source_account_error']; ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Select which account to deduct the payment from.</small>
                        </div>
                        
                        <!-- Payment Amount -->
                        <div class="mb-4">
                            <label for="payment_amount" class="form-label fw-semibold">Payment Amount (₱)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text fw-bold" style="background-color: #003631; color: white;">₱</span>
                                <input type="number" step="0.01" min="1.00" 
                                       class="form-control <?= !empty($data['payment_amount_error']) ? 'is-invalid' : ''; ?>" 
                                       id="payment_amount" name="payment_amount" placeholder="e.g., 500.00" 
                                       value="<?= isset($data['payment_amount']) ? htmlspecialchars($data['payment_amount']) : ''; ?>" required>
                                <?php if (!empty($data['payment_amount_error'])): ?>
                                    <div class="invalid-feedback"><?= $data['payment_amount_error']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="text-end mt-2">
                                <small class="text-muted">Max Payment: <span id="max_payment_display" class="fw-bold" style="color: #bba27bff;">₱0.00</span></small>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-lg fw-bold shadow-lg" 
                                        style="background-color: #003631; color: white; border-radius: 8px; transition: background-color 0.3s ease, transform 0.1s ease;">
                                Review Payment
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loanSelect = document.getElementById('loan_id');
        const accountSelect = document.getElementById('source_account');
        const maxPaymentDisplay = document.getElementById('max_payment_display');
        const accountBalanceDisplay = document.getElementById('account_balance');
        const paymentInput = document.getElementById('payment_amount');

        // Update max payment based on selected loan
        function updateMaxPayment() {
            const selectedOption = loanSelect.options[loanSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const balance = parseFloat(selectedOption.getAttribute('data-balance'));
                if (!isNaN(balance)) {
                    maxPaymentDisplay.textContent = '₱' + balance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    paymentInput.setAttribute('max', balance.toFixed(2));
                }
            } else {
                maxPaymentDisplay.textContent = '₱0.00';
                paymentInput.removeAttribute('max');
            }
        }

        // Update account balance display
        function updateAccountBalance() {
            const selectedOption = accountSelect.options[accountSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const balance = parseFloat(selectedOption.getAttribute('data-balance'));
                if (!isNaN(balance)) {
                    accountBalanceDisplay.textContent = '₱' + balance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
            } else {
                accountBalanceDisplay.textContent = '₱0.00';
            }
        }

        // Initialize on load
        updateMaxPayment();
        updateAccountBalance();

        // Event listeners
        loanSelect.addEventListener('change', updateMaxPayment);
        accountSelect.addEventListener('change', updateAccountBalance);
    });
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>