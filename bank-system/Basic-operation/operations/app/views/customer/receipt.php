<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="container py-5">
    <div class="card shadow-xl border-0 rounded-4 p-4 p-md-5 mx-auto max-w-lg" style="background-color: #ffffff; border: 1px solid #e0e0e0;">
        
        <div class="d-flex justify-content-between align-items-start mb-4 border-bottom pb-3">
            <div>
                <small class="text-muted d-block">Reviewing Transfer To:</small>
                <h4 class="fw-bold" style="color: #003631;"><?= htmlspecialchars($data['recipient_name']); ?></h4>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">Status: 
                    <span class="ms-1 fw-bold text-warning" style="color: #F1B24A;">● Pending Confirmation</span> 
                </small>
                <small class="text-muted d-block">Reference (Preview): 
                    <span class="text-muted small"><?= htmlspecialchars($data['temp_transaction_ref'] ?? 'N/A'); ?></span>
                </small>
            </div>
        </div>

        <div class="card border-0 mb-4 rounded-4 p-4" style="background-color: #0036311A;">
            <p class="fw-semibold text-muted mb-2">Total Amount to be Withdrawn:</p>
            <h1 class="fw-bold fs-2 mb-2" style="color: #003631;">₱ <?= number_format($data['total_payment'] ?? 0.00, 2); ?></h1>
            <div class="d-flex justify-content-between text-muted small mt-2">
                <span>Transfer Amount:</span>
                <span>₱ <?= number_format($data['amount'] ?? 0.00, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between text-muted small">
                <span>Service Charge:</span>
                <span>₱ <?= number_format($data['fee'] ?? 0.00, 2); ?></span>
            </div>
        </div>

        <div class="mt-4">
            <p class="fw-semibold mb-3 text-secondary border-bottom pb-2">Transaction Details</p>

            <div class="d-flex justify-content-between mb-4">
                <small class="text-muted fw-semibold">Projected Remaining Balance:</small>
                <small class="fs-6 fw-bold" style="color: #003631;">₱ <?= number_format($data['remaining_balance'] ?? 0.00, 2); ?></small>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <small class="text-muted">Transferred From:</small>
                <div class="text-end">
                    <small class="fs-6 d-block fw-semibold" style="color: #003631;"><?= htmlspecialchars($data['sender_name'] ?? 'Your Account'); ?></small>
                    <small class="text-muted"><?= htmlspecialchars($data['from_account']); ?></small>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-3">
                <small class="text-muted">Transferred To (Account):</small>
                <div class="text-end">
                    <small class="fs-6 d-block fw-semibold" style="color: #003631;"><?= htmlspecialchars($data['recipient_name']); ?></small>
                    <small class="text-muted"><?= htmlspecialchars($data['recipient_number']); ?></small>
                </div>
            </div>

            <div class="d-flex justify-content-between mb-4">
                <small class="text-muted">Message:</small>
                <small class="text-muted fst-italic"><?= empty($data['message']) ? 'None' : htmlspecialchars($data['message']); ?></small>
            </div>

            <div class="d-flex justify-content-between mt-3 border-top pt-2">
                <small class="text-muted">Review Date and Time:</small>
                <small class="text-muted"><?= date('F d, Y – h:i A'); ?></small>
            </div>
        </div>

        <div class="d-flex justify-content-between text-end mt-5">
            <a href="<?= URLROOT . "/customer/fund_transfer" ?>" class="btn px-4 py-2 fw-semibold" 
                     style="background-color: #bba27bff; border-radius: 8px; color: white; transition: background-color 0.3s ease;">
                Cancel / Edit
            </a>

            <form action="<?= URLROOT ."/customer/receipt"?>" method="POST" class="d-inline">
    
                <input type="hidden" name="from_account" value="<?= htmlspecialchars($data['from_account']); ?>">
                <input type="hidden" name="recipient_number" value="<?= htmlspecialchars($data['recipient_number']); ?>">
                <input type="hidden" name="recipient_name" value="<?= htmlspecialchars($data['recipient_name']); ?>"> 
                <input type="hidden" name="amount" value="<?= htmlspecialchars($data['amount']); ?>">
                <input type="hidden" name="message" value="<?= htmlspecialchars($data['message']); ?>">

                <button type="submit" class="btn px-4 py-2 fw-bold shadow-lg" 
                        style="background-color: #003631; color: white; border-radius: 8px; transition: background-color 0.3s ease, transform 0.1s ease;">
                    Confirm & Send
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
