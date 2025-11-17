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

                        <!------- FORM --------------------------------------------------------------------------------------------->
                        <form action="<?= URLROOT ."/customer/fund_transfer"?>" method="POST">
                            
                            <!-- Sender Number-->
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="color: #003631;">From Account:</label>
                                <select name="from_account" class="form-select" style="background-color: #e8e8df; border: none; border-radius: 8px;" required>
                                    <?php foreach($data['accounts'] as $account): ?>
                                        <option value="<?= $account->account_number?>">
                                            <?= $account->account_number ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!--- RECIPIENT NUMBER --------------------------------------------------------------------------------->
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="color: #003631;">Recipient Account:</label>
                                <input type="text" name="recipient_number" class="form-control" placeholder="ex. CHA-123-456" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                            </div>

                            <!--- RECIPIENT NAME ----------------------------------------------------------------------------------->
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="color: #003631;">Recipient Name:</label>
                                <input type="text" name="recipient_name" class="form-control" placeholder="ex. Maria Allan Reviles" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                            </div>

                            <!--- AMOUNT ------------------------------------------------------------------------------------------->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between ">
                                    <label class="form-label fw-semibold" style="color: #003631;">Amount:</label>
                                    <!-- WILL ONLY SHOW IF NOT SUFFICIENT BALANCE(remove the comment below to be visible) -------------------------->
                                    <!--- <label class="text-danger">No sufficient balance</label> -------------------------------------------------> 
                                </div>
                                <input type="number" name="amount" class="form-control" placeholder="600" style="background-color: #e8e8df; border: none; border-radius: 8px;">
                            </div>

                            <!--- MESSAGE ------------------------------------------------------------------------------------------>
                            <div class="mb-4">
                                <label class="form-label fw-semibold" style="color: #003631;">Message:</label>
                                <textarea class="form-control" name="message" rows="2" placeholder="Optional" style="background-color: #e8e8df; border: none; border-radius: 8px;"></textarea>
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

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
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
      })
</script>