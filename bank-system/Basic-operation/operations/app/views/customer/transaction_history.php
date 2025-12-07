<?php 
require_once ROOT_PATH . '/app/views/layouts/header.php'; 

function formatCurrency($amount, $currency = 'PHP') {
    return $currency . ' ' . number_format(abs($amount), 2);
}

function buildFilterQuery($filters, $page = null) {
    $query = http_build_query(array_filter($filters, function($v) {
        return $v !== 'all' && $v !== 'All' && $v !== '';
    }));
    if ($page !== null) {
        $query .= ($query ? '&' : '') . 'page=' . $page;
    }
    return $query;
}

$filters = $data['filters'];
$queryBase = buildFilterQuery($filters);

?>
<style>
.dropdown-menu {
  z-index: 2000 !important;
}
</style>

<div class="container-fluid px-4 py-4" style="background-color: #f5f5f0; min-height: 100vh;">
    
    <!--------------------------- PAGE TITLE --------------------------------------------------------------------------------------->
    <h2 class="fw-bold mb-4" style="color: #003631;">Transaction History</h2>

    <!--------------------------- FILTERS SECTION (Wrapped in a form for submission) ------------------------------------------------->
    <form method="GET" action="<?php echo URLROOT; ?>/customer/transaction_history">
        <div class="row mb-4 g-3">
            
            <!----------------------- ACCOUNT FILTER -------------------------------------------------------------------------------->
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="input-group rounded-2 shadow-sm">
                    <span class="input-group-text bg-white border-0 text-muted">Account</span>
                    <select class="form-select border-0 bg-white" name="account_id">
                        <option value="all" <?php echo $filters['account_id'] === 'all' ? 'selected' : ''; ?>>All Accounts</option>
                        <?php foreach ($data['accounts'] as $account): ?>
                            <option 
                                value="<?php echo $account->account_id; ?>" 
                                <?php echo $filters['account_id'] == $account->account_id ? 'selected' : ''; ?>
                            >
                                <?php echo $account->account_type . ' (...' . substr($account->account_number, -4) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!----------------------- TRANSACTION TYPE FILTER -------------------------------------------------------------------------->
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="input-group rounded-2 shadow-sm">
                    <span class="input-group-text bg-white border-0 text-muted">Type</span>
                    <select class="form-select border-0 bg-white" name="type_name">
                        <?php foreach ($data['transaction_types'] as $type): ?>
                            <option 
                                value="<?php echo $type; ?>" 
                                <?php echo $filters['type_name'] === $type ? 'selected' : ''; ?>
                            >
                                <?php echo $type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!----------------------- CUSTOM DATE RANGES -------------------------------------------------------------------------------->
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="input-group rounded-2 shadow-sm">
                    <input type="date" class="form-control border-0 bg-white" placeholder="From Date" name="start_date" 
                        value="<?php echo $filters['start_date']; ?>">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-calendar3"></i>
                    </span>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="input-group rounded-2 shadow-sm">
                    <input type="date" class="form-control border-0 bg-white" placeholder="To Date" name="end_date"
                        value="<?php echo $filters['end_date']; ?>">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-calendar3"></i>
                    </span>
                </div>
            </div>
            
            <!----------------------- APPLY/RESET BUTTONS ------------------------------------------------------------------------------->
            <div class="col-lg-2 col-md-4 col-sm-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-dark w-50 me-2" style="background-color: #003631; border-color: #003631;">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="<?php echo URLROOT; ?>/customer/transaction_history" class="btn btn-outline-secondary w-50">
                    Reset
                </a>
            </div>

        </div>
    </form>
    
    <!--------------------------- EXPORT BUTTON & STATS ------------------------------------------------------------------------------------>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="text-muted mb-0">
            <?php echo $data['pagination']['total_transactions']; ?> transaction(s) found.
        </p>
        <div class="dropdown">
            <a class="text-decoration-none fw-semibold dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false" style="color: #003631;z-index: 2000">
                <i class="bi bi-download me-1"></i> Export
            </a>

            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/customer/export_transactions?type=csv&<?php echo $queryBase; ?>">CSV (.csv)</a></li>
                <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/customer/export_transactions?type=pdf&<?php echo $queryBase; ?>">PDF (.pdf)</a></li>
            </ul>
        </div>
    </div>
    <!--------------------------- TRANSACTION TABLE -------------------------------------------------------------------------------->
    <div class="card border-1 shadow-sm px-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 table-borderless " style="border-collapse: separate; border-spacing: 0 12px;">
                    <thead class="bg-light sticky-top shadow-sm">
                        <tr>
                            <th class="py-3 px-4 fw-semibold" style="color: #003631; width: 10%;">Status</th>
                            <th class="py-3 px-4 fw-semibold" style="color: #003631; width: 25%;">Date and Time</th>
                            <th class="py-3 px-4 fw-semibold" style="color: #003631; width: 25%;">Details</th>
                            <th class="py-3 px-4 fw-semibold" style="color: #003631; width: 20%;">Account & Type</th>
                            <th class="py-3 px-4 fw-semibold text-end" style="color: #003631; width: 20%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['transactions'])): ?>
                            <tr class="align-middle">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    No transactions found matching your filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data['transactions'] as $t): 
                                // Determine sign and color
                                $isDebit = $t->signed_amount < 0;
                                $colorClass = $isDebit ? 'text-danger' : 'text-success';
                                $iconClass = $isDebit ? 'bi-arrow-up-right' : 'bi-arrow-down-left'; // Assuming outward transfer/debit is danger (up-right) and inward/credit is success (down-left)
                            ?>
                            <tr class="align-middle table-secondary mb-3 rounded-4 shadow-sm">
                                <td class="px-4 my-3 rounded-start-4">
                                    <i class="bi <?php echo $iconClass; ?> <?php echo $colorClass; ?> fs-4"></i>
                                </td>
                                <td class="px-4">
                                    <?php echo date('d M Y, h:i A', strtotime($t->created_at)); ?>
                                </td>
                                <td class="px-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($t->description); ?></div>
                                    <small class="text-muted">Ref: <?php echo htmlspecialchars($t->transaction_ref); ?></small>
                                </td>
                                <td class="px-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($t->account_number); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($t->transaction_type); ?></small>
                                </td>
                                <td class="px-4 text-end <?php echo $colorClass; ?> rounded-end-4 fw-bold">
                                    <?php 
                                        // Display the signed amount
                                        echo ($isDebit ? '-' : '+') . formatCurrency($t->signed_amount);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!--------------------------- PAGINATION -------------------------------------------------------------------------------->
    <?php
// Define the gold style as a variable for the active page link
$goldStyle = 'style="background-color: #003631 !important; border-color: #003631 !important; color: #DAA520 !important;"';
?>

<?php if ($data['pagination']['total_pages'] > 1): ?>
    <div class="d-flex justify-content-center mt-4">
        <nav>
            <ul class="pagination shadow-sm rounded-3 overflow-hidden">
                
                <!-- Previous Button -->
                <li class="page-item <?php echo $data['pagination']['current_page'] <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo $queryBase; ?>&page=<?php echo $data['pagination']['current_page'] - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php for($i = 1; $i <= $data['pagination']['total_pages']; $i++): ?>
                    <?php if ($i == 1 || $i == $data['pagination']['total_pages'] || ($i >= $data['pagination']['current_page'] - 2 && $i <= $data['pagination']['current_page'] + 2)): ?>
                        <?php 
                        // Check if current page is active to apply the gold style
                        $isActive = $i == $data['pagination']['current_page']; 
                        ?>
                        <li class="page-item <?php echo $isActive ? 'active' : ''; ?>">
                            <a class="page-link" 
                               <?php echo $isActive ? $goldStyle : ''; ?>
                               href="?<?php echo $queryBase; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php elseif ($i == 2 && $data['pagination']['current_page'] > 4): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php elseif ($i == $data['pagination']['total_pages'] - 1 && $data['pagination']['current_page'] < $data['pagination']['total_pages'] - 3): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>

                <!-- Next Button -->
                <li class="page-item <?php echo $data['pagination']['current_page'] >= $data['pagination']['total_pages'] ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?<?php echo $queryBase; ?>&page=<?php echo $data['pagination']['current_page'] + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/app/views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromDateInput = document.querySelector('input[name="start_date"]');
    const toDateInput = document.querySelector('input[name="end_date"]');
    const filterForm = document.querySelector('form');

    document.querySelectorAll('.bi-calendar3').forEach(icon => {
        icon.closest('.input-group').querySelector('input[type="date"]').onclick = function() {
            this.showPicker();
        };
    });
});
</script>
