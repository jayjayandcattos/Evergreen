<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="container py-5">
    <div class="row">

        <div class="col-md-3">
            <h5 class="fw-bold mb-3 text-dark">My Settings</h5>
            
            <div class="list-group list-group-flush">
                <a href="<?= URLROOT . '/customer/change_password' ?>" class="list-group-item list-group-item-action active fw-bold text-dark border-0 rounded-start" 
                    style="background-color: #f0f0f0; border-left: 3px solid #6c757d; color: #333 !important;">Security</a>
            </div>
        </div>

        <div class="col-md-9 border-start ps-5">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <h4 class="fw-semibold text-dark">Change Password</h4>
                <button type="submit" form="changePasswordForm" class="btn btn-sm btn-light border" style="color: #333;">Save Settings</button>
            </div>

            <p class="text-muted mb-4" style="max-width: 600px;">
                If you'd like to change your password, enter your current and new passwords here. Otherwise, leave them all blank. You can use letters, numbers, spaces, and special characters (like *!@#$&, etc.).
            </p>

            <?php if(!empty($data['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show fw-semibold" role="alert">
                    <?= htmlspecialchars($data['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif(!empty($data['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show fw-semibold" role="alert">
                    <?= htmlspecialchars($data['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?= URLROOT . "/customer/change_password" ?>" method="POST" id="changePasswordForm">
                <div class="row">
                    <div class="col-lg-6">
                        
                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="old_password" class="form-label fw-semibold text-dark">Current Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="old_password" 
                                        id="old_password" 
                                        class="form-control <?= (!empty($data['old_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['old_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('old_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['old_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold text-dark">New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="new_password" 
                                        id="new_password" 
                                        class="form-control <?= (!empty($data['new_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['new_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('new_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['new_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-semibold text-dark">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" 
                                        name="confirm_password" 
                                        id="confirm_password" 
                                        class="form-control <?= (!empty($data['confirm_password_err'])) ? 'is-invalid' : ''; ?>" 
                                        value="<?= htmlspecialchars($data['confirm_password'] ?? ''); ?>"
                                        placeholder="*************">
                                <span class="input-group-text bg-white" 
                                        style="cursor: pointer;" 
                                        onclick="togglePassword('confirm_password', this)">
                                    <i class="fas fa-eye-slash"></i>
                                </span>
                                <div class="invalid-feedback">
                                    <?= $data['confirm_password_err'] ?? ''; ?>
                                </div>
                            </div>
                        </div>


                        </div> <div class="col-lg-6">
                            <div class="mt-4 mt-lg-0 pt-lg-4">
                                <h6 class="fw-semibold text-dark">Password Requirements</h6>
                                <ul class="list-unstyled">
                                    <li id="length" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> At least 10 characters long</li>
                                    <li id="uppercase" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one uppercase character</li>
                                    <li id="lowercase" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one lowercase character</li>
                                    <li id="number" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Contains at least one number</li>
                                    <li id="match" class="mb-2 text-danger"><i class="fas fa-times me-2"></i> Type the password again to confirm</li>
                                </ul>
                            </div>
                        </div>
                    </div>        
                  </div>
                <button type="submit" class="d-none">Hidden Submit</button>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
<script>
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');

    const requirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        lowercase: document.getElementById('lowercase'),
        number: document.getElementById('number'),
        match: document.getElementById('match')
    };

    // Helper to update requirement list item
    function updateRequirement(reqElement, condition, successText, failureText) {
        if (condition) {
            reqElement.classList.replace('text-danger', 'text-success');
            reqElement.innerHTML = `<i class="fas fa-check me-2"></i> ${successText}`;
        } else {
            reqElement.classList.replace('text-success', 'text-danger');
            reqElement.innerHTML = `<i class="fas fa-times me-2"></i> ${failureText}`;
        }
    }

    function checkPasswordRequirements() {
        const pwd = newPassword.value;
        const confirmPwd = confirmPassword.value;

        // Length
        updateRequirement(requirements.length, pwd.length >= 10, 'At least 10 characters long', 'At least 10 characters long');

        // Uppercase
        updateRequirement(requirements.uppercase, /[A-Z]/.test(pwd), 'Contains at least one uppercase character', 'Contains at least one uppercase character');

        // Lowercase
        updateRequirement(requirements.lowercase, /[a-z]/.test(pwd), 'Contains at least one lowercase character', 'Contains at least one lowercase character');

        // Number
        updateRequirement(requirements.number, /\d/.test(pwd), 'Contains at least one number', 'Contains at least one number');

        // Match with confirm password - IMPROVED LOGIC
        if (pwd !== "" && pwd === confirmPwd) {
            updateRequirement(requirements.match, true, 'Passwords match', 'Type the password again to confirm');
        } else if (pwd !== "" && confirmPwd !== "" && pwd !== confirmPwd) {
             updateRequirement(requirements.match, false, 'Passwords match', 'Passwords do not match');
        } else {
            updateRequirement(requirements.match, false, 'Passwords match', 'Type the password again to confirm');
        }
    }

    // Initial check on load in case of PHP validation errors
    document.addEventListener('DOMContentLoaded', checkPasswordRequirements);

    newPassword.addEventListener('input', checkPasswordRequirements);
    confirmPassword.addEventListener('input', checkPasswordRequirements);

    // Toggle visibility function
    function togglePassword(id, icon) {
        const input = document.getElementById(id);
        const iTag = icon.querySelector('i');
        if (input.type === "password") {
            input.type = "text";
            iTag.classList.replace('fa-eye-slash', 'fa-eye');
        } else {
            input.type = "password";
            iTag.classList.replace('fa-eye', 'fa-eye-slash');
        }
    }
</script>