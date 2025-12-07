<?php 
require_once ROOT_PATH . '/app/views/layouts/header.php'; 
?>

<style>
.status-badge {
    padding: 0.35rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
}
.status-approved {
    background-color: #d1e7dd;
    color: #0f5132;
}
.status-rejected {
    background-color: #f8d7da;
    color: #842029;
}
.application-card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}
.application-card:hover {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}
.info-label {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}
.info-value {
    color: #212529;
    font-size: 0.875rem;
}
</style>

<div class="container-fluid px-4 py-4" style="background-color: #f5f5f0; min-height: 100vh;">
    
    <!--------------------------- PAGE TITLE --------------------------------------------------------------------------------------->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-2" style="color: #003631;">Account Applications</h2>
            <p class="text-muted mb-0">View the status of your account applications</p>
        </div>
    </div>

    <!--------------------------- STATISTICS CARDS --------------------------------------------------------------------------------->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Applications</p>
                            <h3 class="mb-0 fw-bold"><?= $data['total_applications'] ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-file-earmark-text text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Pending</p>
                            <h3 class="mb-0 fw-bold text-warning"><?= $data['pending_count'] ?></h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-clock-history text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Approved</p>
                            <h3 class="mb-0 fw-bold text-success"><?= $data['approved_count'] ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--------------------------- APPLICATIONS LIST -------------------------------------------------------------------------------->
    <?php if (empty($data['applications'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #adb5bd;"></i>
                <h5 class="mt-3 text-muted">No Applications Found</h5>
                <p class="text-muted">You haven't submitted any account applications yet.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($data['applications'] as $app): ?>
                <div class="col-12">
                    <div class="card application-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <!----------------------- LEFT COLUMN: Application Info ------------------------------------------------->
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($app['application_number']) ?></h5>
                                            <p class="text-muted mb-0 small">Submitted: <?= htmlspecialchars($app['submitted_at']) ?></p>
                                        </div>
                                        <span class="status-badge status-<?= strtolower($app['application_status']) ?>">
                                            <?= htmlspecialchars($app['application_status']) ?>
                                        </span>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Applicant Name:</span>
                                                <span class="info-value d-block"><?= htmlspecialchars($app['full_name']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Account Type:</span>
                                                <span class="info-value d-block"><?= htmlspecialchars($app['account_type']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Email:</span>
                                                <span class="info-value d-block"><?= htmlspecialchars($app['email']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <span class="info-label">Phone:</span>
                                                <span class="info-value d-block"><?= htmlspecialchars($app['phone_number']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!empty($app['selected_cards'])): ?>
                                        <div class="mb-2">
                                            <span class="info-label">Selected Cards:</span>
                                            <div class="mt-1">
                                                <?php foreach ($app['selected_cards'] as $card): ?>
                                                    <span class="badge bg-secondary me-1"><?= htmlspecialchars(ucfirst(trim($card))) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($app['additional_services'])): ?>
                                        <div class="mb-2">
                                            <span class="info-label">Additional Services:</span>
                                            <div class="mt-1">
                                                <?php foreach ($app['additional_services'] as $service): ?>
                                                    <span class="badge bg-info me-1"><?= htmlspecialchars(ucfirst(trim($service))) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!----------------------- RIGHT COLUMN: Additional Details ----------------------------------------->
                                <div class="col-lg-4 border-start">
                                    <h6 class="fw-bold mb-3">Additional Information</h6>
                                    
                                    <div class="mb-2">
                                        <span class="info-label">Date of Birth:</span>
                                        <span class="info-value d-block"><?= htmlspecialchars($app['date_of_birth']) ?></span>
                                    </div>

                                    <div class="mb-2">
                                        <span class="info-label">Address:</span>
                                        <span class="info-value d-block small"><?= htmlspecialchars($app['full_address']) ?></span>
                                    </div>

                                    <div class="mb-2">
                                        <span class="info-label">ID Type:</span>
                                        <span class="info-value d-block"><?= htmlspecialchars($app['id_type']) ?></span>
                                    </div>

                                    <div class="mb-2">
                                        <span class="info-label">Employment Status:</span>
                                        <span class="info-value d-block"><?= htmlspecialchars($app['employment_status']) ?></span>
                                    </div>

                                    <?php if ($app['employer_name'] !== 'N/A'): ?>
                                        <div class="mb-2">
                                            <span class="info-label">Employer:</span>
                                            <span class="info-value d-block"><?= htmlspecialchars($app['employer_name']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mb-2">
                                        <span class="info-label">Annual Income:</span>
                                        <span class="info-value d-block"><?= htmlspecialchars($app['annual_income']) ?></span>
                                    </div>

                                    <?php if ($app['reviewed_at']): ?>
                                        <div class="mb-2">
                                            <span class="info-label">Reviewed At:</span>
                                            <span class="info-value d-block small"><?= htmlspecialchars($app['reviewed_at']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>

