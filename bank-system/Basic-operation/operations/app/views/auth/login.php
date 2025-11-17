<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="container mt-5" style="max-width: 400px;">
    <h3 class="text-center mb-4">Customer Login</h3>

    <?php if (!empty($login_error)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form action="<?= URLROOT; ?>/auth/login" method="POST">
        <div class="mb-3">
            <label for="identifier" class="form-label">Email or Account Number</label>
            <input 
                type="text" 
                name="identifier" 
                id="identifier" 
                class="form-control <?= (!empty($identifier_error)) ? 'is-invalid' : ''; ?>" 
                placeholder="Enter email or account number" 
                value="<?= htmlspecialchars($identifier ?? ''); ?>"
                required
            >
            <div class="invalid-feedback">
                <?= htmlspecialchars($identifier_error ?? ''); ?>
            </div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input 
                type="password" 
                name="password" 
                id="password" 
                class="form-control <?= (!empty($password_error)) ? 'is-invalid' : ''; ?>" 
                placeholder="Enter password" 
                required
            >
            <div class="invalid-feedback">
                <?= htmlspecialchars($password_error ?? ''); ?>
            </div>
        </div>
        <div class="mb-3 form-check">
            <input 
                type="checkbox" 
                class="form-check-input" 
                id="remember_me" 
                name="remember_me"
                <?= (!empty($remember_me)) ? 'checked' : ''; ?>
            >
            <label class="form-check-label" for="remember_me">
                Remember me
            </label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>