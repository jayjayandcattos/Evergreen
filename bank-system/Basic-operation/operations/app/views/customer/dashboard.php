<?php require_once ROOT_PATH . '/app/views/layouts/header.php'; ?>

<div class="jumbotron text-center">
    <h1 class="display-4">Welcome, <?= htmlspecialchars($data['first_name'] ?? 'Customer'); ?> <?= htmlspecialchars($data['last_name'] ?? 'Customer'); ?>!</h1>
    <p class="lead">This is your customer dashboard.</p>
    <p class="lead"><?= htmlspecialchars($data['customer_id']) ?></p>
    <hr class="my-4">
    <p>Here you can view your accounts, transactions, and manage your profile.</p>
    <a class="btn btn-danger btn-lg" href="<?= URLROOT . '/auth/logout'; ?>" role="button">Logout</a>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>