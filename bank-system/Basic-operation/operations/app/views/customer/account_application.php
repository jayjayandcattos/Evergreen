<?php 
require_once ROOT_PATH . '/app/views/layouts/header.php'; 
?>

<style>
.status-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0.25rem 0;
}
.status-pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
}
.status-approved {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #198754;
}
.status-rejected {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #dc3545;
}
.app-number-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    font-weight: 600;
    color: #495057;
}
.rejection-note {
    border-left: 4px solid #dc3545;
    background: #f8d7da;
    color: #842029;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-top: 1rem;
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
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Application Number</h6>
                                    <div class="app-number-chip">
                                        <i class="bi bi-hash"></i>
                                        <span><?= htmlspecialchars($app['application_number']) ?></span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge status-<?= strtolower($app['application_status']) ?>">
                                        <?= htmlspecialchars($app['application_status']) ?>
                                    </span>
                                    <p class="text-muted mb-0 small mt-2"><?= htmlspecialchars($app['submitted_at']) ?></p>
                                </div>
                            </div>

                            <?php if (strtolower($app['application_status']) === 'rejected' && !empty($app['rejection_reason'])): ?>
                                <div class="rejection-note mt-3">
                                    <div class="fw-bold mb-2">
                                        <i class="bi bi-exclamation-circle"></i> Rejection Reason
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($app['rejection_reason'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>
