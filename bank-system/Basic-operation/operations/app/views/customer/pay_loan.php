<?php require_once ROOT_PATH . '/app/views/layouts/header.php';?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <h2 class="fw-bold mb-4" style="color: #003631;">Loan Repayment Center</h2>
            <p class="text-muted mb-5">Welcome, <?= htmlspecialchars($data['first_name']); ?>. View your active loan applications below and select one to make a payment from your primary account (<?= htmlspecialchars($data['source_account'] ?? 'N/A'); ?>).</p>

            <?= $data['message']; // Display success/error messages ?>

            <?php 
            $displayLoans = array_filter($data['active_loans'], function($loan) {
                return !is_null($loan->application_id);
            });
            ?>

            <?php if (empty($displayLoans)): ?>
                <div class="card p-5 text-center shadow-lg" style="border: 2px dashed #bba27bff; background-color: #f8f9fa;">
                    <h4 class="text-muted">You have no active loan applications with outstanding balances.</h4>
                    <p class="text-secondary">Congratulations! All your loans may be fully settled or you have none yet.</p>
                </div>
            <?php else: ?>
                
                <div class="mb-5">
                    <h4 class="fw-semibold mb-3 text-secondary">Outstanding Loan Applications (<?= count($displayLoans); ?>)</h4>
                    <div class="list-group shadow-sm rounded-4">
                        <?php foreach ($displayLoans as $loan): ?>
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
                    <form action="<?= URLROOT; ?>/loan/pay_loan" method="POST">
                        
                        <div class="mb-4">
                            <label for="loan_id" class="form-label fw-semibold">Select Loan Application to Pay</label>
                            <select class="form-select form-select-lg shadow-sm" id="loan_id" name="loan_id" required>
                                <option value="" disabled selected>Choose a loan application</option>
                                <?php foreach ($displayLoans as $loan): // 3. Loop over the FILTERED list ?>
                                    <option 
                                        value="<?= htmlspecialchars($loan->application_id); ?>" 
                                        data-balance="<?= htmlspecialchars($loan->remaining_balance); ?>"
                                    >
                                        <?= htmlspecialchars($loan->loan_type); ?> (ID: <?= htmlspecialchars($loan->application_id); ?>) - Balance: ₱<?= number_format($loan->remaining_balance, 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="loanHelp" class="form-text text-muted">The current remaining balance will update below.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="payment_amount" class="form-label fw-semibold">Payment Amount (₱)</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text fw-bold" style="background-color: #003631; color: white;">₱</span>
                                <input type="number" step="0.01" min="1.00" class="form-control" id="payment_amount" name="payment_amount" placeholder="e.g., 500.00" required>
                            </div>
                            <div class="text-end mt-2">
                                <small class="text-muted">Max Payment: <span id="max_payment_display" class="fw-bold" style="color: #bba27bff;">₱0.00</span></small>
                            </div>
                        </div>

                        <input type="hidden" name="source_account" value="<?= htmlspecialchars($data['source_account'] ?? ''); ?>">
                        <div class="mb-5 p-3 rounded-3" style="background-color: #f0f8ff; border: 1px solid #0036311A;">
                            <small class="fw-semibold text-muted d-block mb-1">Payment will be debited from:</small>
                            <p class="mb-0 fw-bold" style="color: #003631;"><?= htmlspecialchars($data['source_account'] ?? 'Account Missing'); ?></p>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-lg fw-bold shadow-lg" 
                                        style="background-color: #003631; color: white; border-radius: 8px; transition: background-color 0.3s ease, transform 0.1s ease;">
                                Complete Loan Payment
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
        const maxPaymentDisplay = document.getElementById('max_payment_display');
        const paymentInput = document.getElementById('payment_amount');

        function updateMaxPayment() {
            const selectedOption = loanSelect.options[loanSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const balance = parseFloat(selectedOption.getAttribute('data-balance'));
                if (!isNaN(balance)) {
                    // Display max payment as the current balance
                    maxPaymentDisplay.textContent = '₱' + balance.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    // Optionally set the max attribute on the input field (though server-side validation is mandatory)
                    paymentInput.setAttribute('max', balance.toFixed(2));
                }
            } else {
                maxPaymentDisplay.textContent = '₱0.00';
                paymentInput.removeAttribute('max');
            }
        }

        loanSelect.addEventListener('change', updateMaxPayment);

        // Initial call to set the max payment if an option is pre-selected (though we don't pre-select here)
        updateMaxPayment();
    });
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>