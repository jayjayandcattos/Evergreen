<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="min-vh-100 d-flex align-items-center justify-content-center"
    style="background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('../img/trees_background.jpg'); 
    background-size: cover; 
    background-position: center;" >

    <div class="container-fluid p-5" style="background-color: #ffffff5e;">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                <!------- PROFILE AVATAR ------------------------------------------------------------------------------------>
                <div class="text-center mb-4">
                    <div class="bg-white rounded-circle mx-auto d-flex align-items-center justify-content-center shadow-lg" 
                         style="width: 120px; height: 120px;">
                        <i class="bi bi-person-fill text-secondary" style="font-size: 4rem;"></i>
                    </div>
                </div>

                <!------- HEADING ---------------------------------------------------------------------------------------------->
                <h2 class="text-center fw-bold mb-5">
                    Refer Friends. Earn Points. Win Together.
                </h2>

                <!------- SUCCESS/ERROR MESSAGES ------------------------------------------------------------------------------>
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!------- REFERRAL STATS -------------------------------------------------------------------------------------->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card border-0 shadow-sm rounded-4" style="background-color: #ffffff;">
                            <div class="card-body text-center p-4">
                                <h3 class="text-primary mb-2"><?php echo number_format($total_points, 2); ?></h3>
                                <p class="text-muted mb-0">Total Points</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4" style="background-color: #ffffff;">
                            <div class="card-body text-center p-4">
                                <h3 class="text-success mb-2"><?php echo $referral_count; ?></h3>
                                <p class="text-muted mb-0">Friends Referred</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!------- REFERRAL CARD ---------------------------------------------------------------------------------------->
                <div class="card border-0 shadow-lg rounded-4" style="background-color: #f5f5f0;">
                    <div class="card-body p-4 p-md-5">
                        <div class="row">
                            
                            <!------- FORM ----------------------------------------------------------------------------------->
                            <div class="col-12 col-md-7 mb-4 mb-md-0">
                                <form method="POST" action="">
                                    
                                    <!--- USER REFERRAL CODE --------------------------------------------------------------------------->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold mb-3 fs-5">
                                            Your referral code:
                                        </label>
                                        <div class="input-group">
                                            <input type="text" 
                                                id="referral_code"
                                                class="form-control border-0 rounded-4 py-3" 
                                                value="<?php echo htmlspecialchars($referral_code); ?>" 
                                                readonly
                                                style="font-size: 1.2rem; letter-spacing: 0.2em; background-color: #D9D9D94D;">
                                            <button type="button" 
                                                    class="btn border-0 rounded-4" 
                                                    style="background-color: #F1B24A;"
                                                    onclick="copyReferralCode()"
                                                    title="Copy to clipboard">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Share this code with friends to earn 50 points when they sign up!</small>
                                    </div>

                                    <!--- FRIEND'S CODE --------------------------------------------------------------------->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold mb-3 fs-5">
                                            Enter your friend's code:
                                        </label>
                                        <input type="text" 
                                            name="friend_code"
                                            id="friend_code"
                                            class="form-control border-0 rounded-4 py-3 <?php echo !empty($friend_code_error) ? 'is-invalid' : ''; ?>" 
                                            placeholder="Enter referral code (e.g., EVG000001123)" 
                                            value="<?php echo htmlspecialchars($friend_code); ?>"
                                            style="font-size: 1.2rem; letter-spacing: 0.1em; background-color: #D9D9D94D; text-transform: uppercase;"
                                            maxlength="20">
                                        <?php if (!empty($friend_code_error)): ?>
                                            <div class="invalid-feedback d-block">
                                                <?php echo htmlspecialchars($friend_code_error); ?>
                                            </div>
                                        <?php endif; ?>
                                        <small class="text-muted">Enter a friend's referral code to earn 25 bonus points!</small>
                                    </div>

                                    <!--- CONFIRM BUTTON ------------------------------------------------------------------------->
                                    <div class="float-end">
                                        <button type="submit" 
                                                class="btn text-white border-0 rounded-3 px-5 py-2 fw-bold" 
                                                style="background-color: #F1B24A;">
                                            <i class="bi bi-check-circle me-2"></i>Confirm
                                        </button>
                                    </div>

                                </form>
                            </div>

                            <!--- GIFT IMAGE --------------------------------------------------------------------------->
                            <div class="col-12 col-md-5 d-flex align-items-center justify-content-center">
                                <img src="../img/gift.png" 
                                     alt="Gift Boxes" 
                                     class="img-fluid" 
                                     style="max-width: 300px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!------- HOW IT WORKS --------------------------------------------------------------------------------------->
                <div class="card border-0 shadow-sm rounded-4 mt-4" style="background-color: #ffffff;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3">How It Works</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Share your referral code with friends</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>When they sign up and use your code, you both earn points</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>You earn <strong>50 points</strong> for each successful referral</li>
                            <li class="mb-0"><i class="bi bi-check-circle-fill text-success me-2"></i>Your friend earns <strong>25 points</strong> when they use your code</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyReferralCode() {
    const codeInput = document.getElementById('referral_code');
    codeInput.select();
    codeInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show feedback
        const button = event.target.closest('button');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i>';
        button.style.backgroundColor = '#28a745';
        
        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.style.backgroundColor = '#F1B24A';
        }, 2000);
        
        // Fallback notification
        if (navigator.clipboard) {
            navigator.clipboard.writeText(codeInput.value).then(() => {
                console.log('Copied to clipboard');
            });
        }
    } catch (err) {
        console.error('Failed to copy:', err);
        alert('Failed to copy. Please copy manually: ' + codeInput.value);
    }
}

// Auto-uppercase friend code input
document.getElementById('friend_code')?.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>