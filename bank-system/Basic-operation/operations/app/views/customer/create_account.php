<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; // Assumed header include?>

<div class="container py-5">
    <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 mx-auto" style="max-width: 500px; background-color: #ffffff; border: 1px solid #e0e0e0;">
        
        <h2 class="fw-bold mb-3" style="color: #003631;"><?= htmlspecialchars($data['title']); ?></h2>
        <p class="text-muted mb-4 border-bottom pb-3">Choose the type of bank account you wish to open and make your initial deposit.</p>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($data['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($data['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?= URLROOT . '/customer/create_account'; ?>" method="POST">

            <!-- Account Type Selection -->
            <div class="mb-4">
                <label for="account_type" class="form-label fw-semibold" style="color: #003631;">Account Type</label>
                <select 
                    class="form-select rounded-3 py-2" 
                    id="account_type" 
                    name="account_type_id" 
                    required 
                    style="border-color: #00363150;"
                >
                    <option value="" disabled <?= empty($data['account_type_id']) ? 'selected' : ''; ?>>Select a Bank Account Type</option>
                    <?php foreach ($data['account_types'] as $type): ?>
                        <option 
                            value="<?= $type->account_type_id; ?>" 
                            <?= $data['account_type_id'] == $type->account_type_id ? 'selected' : ''; ?>
                        >
                            <?= htmlspecialchars($type->type_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Choose the account type that suits your needs. 
                    <small class="d-block mt-1 text-info fw-medium">Checking accounts will be numbered CHA-***-<?= date('Y'); ?>, and Savings accounts SA-***-<?= date('Y'); ?>.</small>
                </div>
            </div>
            <!-- Terms & Conditions -->
            <div class="mt-4 p-3 border rounded-3" style="background-color: #f8f9fa;">
                <h6 class="fw-bold mb-2" style="color: #003631;">Terms & Conditions</h6>
                <p class="small text-muted mb-2">
                    By opening this account, you acknowledge and agree to the following:
                </p>

                <ul class="small text-muted ps-3">
                    <li>You confirm that all information provided is true and accurate.</li>
                    <li>You authorize the bank to verify your identity and perform necessary background checks.</li>
                    <li>You agree to comply with the bank’s policies, including anti-fraud and anti-money laundering regulations.</li>
                    <li>You understand that your account number will be generated automatically based on the selected account type.</li>
                    <li>You agree that the bank may contact you for verification or updates regarding your account.</li>
                </ul>

                <div class="form-check mt-3">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="agree_terms" 
                        name="agree_terms" 
                        required
                    >
                    <label class="form-check-label small" for="agree_terms">
                        I have read and accept the Terms & Conditions.
                    </label>
                </div>
            </div>


            <!-- Submit Button -->
            <button 
                type="submit" 
                class="btn w-100 py-3 mt-3 fw-bold text-white shadow-lg" 
                style="background-color: #003631; border-radius: 8px; transition: background-color 0.3s ease, transform 0.1s ease;"
            >
                Open Account Now
            </button>
            
            <a href="<?= URLROOT . '/customer/account'; ?>" class="d-block text-center mt-3 text-muted small">
                Cancel and Go Back
            </a>

        </form>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; // Assumed footer include?>