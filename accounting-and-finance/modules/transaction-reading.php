<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Initialize filter variables
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_account = $_GET['account'] ?? '';
$apply_filters = isset($_GET['apply_filters']);

// Fetch transactions from database
$transactions = [];
$hasFilters = false;

if ($apply_filters) {
    $hasFilters = !empty($filter_date_from) || !empty($filter_date_to) || 
                  !empty($filter_type) || !empty($filter_status) || !empty($filter_account);
}

// Build query to fetch transactions from BOTH journal entries AND bank transactions
$sql = "SELECT * FROM (
            -- Journal Entries from Accounting System
            SELECT 
                CONCAT('JE-', je.id) as id,
                je.journal_no as journal_no,
                je.entry_date as entry_date,
                jt.code as type_code,
                jt.name as type_name,
                je.description,
                je.reference_no,
                je.total_debit,
                je.total_credit,
                je.status,
                u.username as created_by,
                u.full_name as created_by_name,
                je.created_at,
                je.posted_at,
                fp.period_name as fiscal_period,
                'journal' as source
            FROM journal_entries je
            INNER JOIN journal_types jt ON je.journal_type_id = jt.id
            INNER JOIN users u ON je.created_by = u.id
            LEFT JOIN fiscal_periods fp ON je.fiscal_period_id = fp.id
            WHERE je.status NOT IN ('deleted', 'voided')
            
            UNION ALL
            
            -- Bank Transactions from Bank System
            SELECT 
                CONCAT('BT-', bt.transaction_id) as id,
                bt.transaction_ref as journal_no,
                DATE(bt.created_at) as entry_date,
                tt.type_name as type_code,
                tt.type_name as type_name,
                COALESCE(bt.description, 'Bank Transaction') as description,
                bt.transaction_ref as reference_no,
                CASE WHEN bt.amount > 0 THEN bt.amount ELSE 0 END as total_debit,
                CASE WHEN bt.amount < 0 THEN ABS(bt.amount) ELSE 0 END as total_credit,
                'posted' as status,
                COALESCE(be.employee_name, 'System') as created_by,
                COALESCE(be.employee_name, 'System') as created_by_name,
                bt.created_at,
                bt.created_at as posted_at,
                DATE_FORMAT(bt.created_at, '%Y-%m') as fiscal_period,
                'bank' as source
            FROM bank_transactions bt
            INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
            LEFT JOIN bank_employees be ON bt.employee_id = be.employee_id
            INNER JOIN customer_accounts ca ON bt.account_id = ca.account_id
        ) combined_transactions
        WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if (!empty($filter_date_from)) {
    $sql .= " AND entry_date >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if (!empty($filter_date_to)) {
    $sql .= " AND entry_date <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

if (!empty($filter_type)) {
    $sql .= " AND type_code = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if (!empty($filter_status)) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if (!empty($filter_account)) {
    $sql .= " AND (reference_no LIKE ? OR description LIKE ?)";
    $params[] = "%{$filter_account}%";
    $params[] = "%{$filter_account}%";
    $types .= 'ss';
}

$sql .= " ORDER BY entry_date DESC, created_at DESC";

// Execute query
try {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    // If database error, transactions will remain empty array
    error_log("Transaction query error: " . $e->getMessage());
}

// Get statistics
$stats = [
    'total_transactions' => 0,
    'posted_count' => 0,
    'draft_count' => 0,
    'today_count' => 0
];

try {
    $stats_sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN DATE(entry_date) = CURDATE() THEN 1 ELSE 0 END) as today_count
                  FROM journal_entries 
                  WHERE status NOT IN ('deleted', 'voided')";
    
    $result = $conn->query($stats_sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Statistics query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Recording - Accounting and Finance System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="../assets/image/LOGO.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/financial-reporting.css">
    <link rel="stylesheet" href="../assets/css/transaction-reading.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid px-4">
            <div class="logo-section">
            <div class="logo-circle">
                <img src="../assets/image/LOGO.png" alt="Evergreen Logo" class="logo-img">
            </div>
            <div class="logo-text">
                <h1>EVERGREEN</h1>
                <p>Secure. Invest. Achieve</p>
            </div>
        </div>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../core/dashboard.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="modulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-th-large me-1"></i>Modules
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="modulesDropdown">
                            <li><a class="dropdown-item" href="general-ledger.php"><i class="fas fa-book me-2"></i>General Ledger</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-line me-2"></i>Financial Reporting</a></li>
                            <li><a class="dropdown-item" href="loan-accounting.php"><i class="fas fa-hand-holding-usd me-2"></i>Loan Accounting</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item active" href="transaction-reading.php"><i class="fas fa-exchange-alt me-2"></i>Transaction Reading</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-receipt me-2"></i>Expense Tracking</a></li>
                            <li><a class="dropdown-item" href="payroll-management.php"><i class="fas fa-users me-2"></i>Payroll Management</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-alt me-1"></i>Reports
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="reportsDropdown">
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-bar me-2"></i>Financial Statements</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-money-bill-wave me-2"></i>Cash Flow Report</a></li>
                            <li><a class="dropdown-item" href="expense-tracking.php"><i class="fas fa-clipboard-list me-2"></i>Expense Summary</a></li>
                            <li><a class="dropdown-item" href="payroll-management.php"><i class="fas fa-wallet me-2"></i>Payroll Report</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="bin-station.php"><i class="fas fa-trash-alt me-2"></i>Bin Station</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="database-settings.php"><i class="fas fa-database me-2"></i>Database Settings</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown d-none d-md-block">
                    <a class="nav-icon-btn" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom notifications-dropdown" aria-labelledby="notificationsDropdown">
                        <li class="dropdown-header">Notifications</li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="dropdown-item text-center text-muted"><small>Loading notifications...</small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="activity-log.php">View All Notifications</a></li>
                    </ul>
                </div>
                
                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <a class="user-profile-btn" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <span class="d-none d-lg-inline"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                        <i class="fas fa-chevron-down ms-2 d-none d-lg-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="user-dropdown-header">
                                <i class="fas fa-user-circle fa-2x"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($current_user['full_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($current_user['username']); ?></small>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="activity-log.php"><i class="fas fa-history me-2"></i>Activity Log</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../core/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Beautiful Page Header -->
    <div class="beautiful-page-header mb-5">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="header-content">
                        <h1 class="page-title-beautiful">
                            <i class="fas fa-exchange-alt me-3"></i>
                            Transaction Recording
                        </h1>
                        <p class="page-subtitle-beautiful">
                            Record, view, and manage all journal entries and accounting transactions
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="header-info-card">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Database Status</div>
                                <div class="info-value status-connected">Connected</div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Current Period</div>
                                <div class="info-value"><?php echo date('F Y'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions mt-3">
                <a href="../core/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="container-fluid py-4">
        <!-- Action Buttons -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="fas fa-list-alt me-2 text-primary"></i>Transaction History</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-primary me-2" id="btnShowFilters">
                            <i class="fas fa-filter me-1"></i>Apply Filters
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-1"></i>Export Excel
                            </button>
                            <button type="button" class="btn btn-info" onclick="printTable()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="card shadow-sm mb-4" id="filterPanel" style="display: none;">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Transactions</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Transaction Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="GJ" <?php echo $filter_type === 'GJ' ? 'selected' : ''; ?>>General Journal</option>
                                <option value="CR" <?php echo $filter_type === 'CR' ? 'selected' : ''; ?>>Cash Receipt</option>
                                <option value="CD" <?php echo $filter_type === 'CD' ? 'selected' : ''; ?>>Cash Disbursement</option>
                                <option value="PR" <?php echo $filter_type === 'PR' ? 'selected' : ''; ?>>Payroll</option>
                                <option value="AP" <?php echo $filter_type === 'AP' ? 'selected' : ''; ?>>Accounts Payable</option>
                                <option value="AR" <?php echo $filter_type === 'AR' ? 'selected' : ''; ?>>Accounts Receivable</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="posted" <?php echo $filter_status === 'posted' ? 'selected' : ''; ?>>Posted</option>
                                <option value="reversed" <?php echo $filter_status === 'reversed' ? 'selected' : ''; ?>>Reversed</option>
                                <option value="voided" <?php echo $filter_status === 'voided' ? 'selected' : ''; ?>>Voided</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="account" class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="account" name="account" placeholder="e.g., 1001" value="<?php echo htmlspecialchars($filter_account); ?>">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </button>
                            <button type="submit" name="apply_filters" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- No Results Message -->
        <?php if ($apply_filters && $hasFilters && empty($transactions)): ?>
        <div class="alert alert-warning shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>No Existing Information Found</strong><br>
            No transactions match your filter criteria. Please adjust your filters and try again.
        </div>
        <?php endif; ?>
        
        <!-- Transaction Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-0">
                            <?php echo $apply_filters && $hasFilters ? 'Filtered Transaction History' : 'All Transaction Records'; ?>
                        </h6>
                    </div>
                    <?php if ($apply_filters && $hasFilters): ?>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-info">
                            <i class="fas fa-filter me-1"></i>Filters Applied
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="transactionTable" class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Journal No.</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <div class="py-5">
                                        <i class="fas fa-database fa-3x mb-3 d-block text-secondary"></i>
                                        <p class="mb-0">No transaction data available yet.</p>
                                        <small>Add sample data using the SQL queries provided in the documentation.</small>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php 
                                $total_debit = 0;
                                $total_credit = 0;
                                foreach ($transactions as $trans): 
                                    $total_debit += $trans['total_debit'];
                                    $total_credit += $trans['total_credit'];
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($trans['journal_no']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($trans['entry_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($trans['type_code']); ?></span>
                                        <?php echo htmlspecialchars($trans['type_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($trans['description'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['reference_no'] ?? '-'); ?></td>
                                    <td class="amount-debit text-end"><?php echo number_format($trans['total_debit'], 2); ?></td>
                                    <td class="amount-credit text-end"><?php echo number_format($trans['total_credit'], 2); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'draft' => 'status-draft',
                                            'posted' => 'status-posted',
                                            'reversed' => 'status-reversed',
                                            'voided' => 'status-voided'
                                        ];
                                        $class = $status_class[$trans['status']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $class; ?>"><?php echo ucfirst($trans['status']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($trans['created_by_name']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info btn-action" onclick="viewTransactionDetails(<?php echo $trans['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary btn-action" onclick="viewAuditTrail(<?php echo $trans['id']; ?>)" title="Audit Trail">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action" onclick="deleteTransaction(<?php echo $trans['id']; ?>)" title="Delete Transaction">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end">Total:</th>
                                <th class="text-end"><?php echo number_format($total_debit ?? 0, 2); ?></th>
                                <th class="text-end"><?php echo number_format($total_credit ?? 0, 2); ?></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalTransactions"><?php echo number_format($stats['total_transactions'] ?? 0); ?></h3>
                        <p>Total Transactions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="postedTransactions"><?php echo number_format($stats['posted_count'] ?? 0); ?></h3>
                        <p>Posted</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="draftTransactions"><?php echo number_format($stats['draft_count'] ?? 0); ?></h3>
                        <p>Draft</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="todayTransactions"><?php echo number_format($stats['today_count'] ?? 0); ?></h3>
                        <p>Today's Transactions</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Audit Trail Modal -->
    <div class="modal fade" id="auditTrailModal" tabindex="-1" aria-labelledby="auditTrailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="auditTrailModalLabel">
                        <i class="fas fa-history me-2"></i>Audit Trail
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Object Type</th>
                                    <th>Object ID</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="auditTrailBody">
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No audit trail data available.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="exportAuditTrail()">
                        <i class="fas fa-download me-1"></i>Export Audit Trail
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal" tabindex="-1" aria-labelledby="transactionDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="transactionDetailsModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Transaction Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="transactionDetailsBody">
                    <p class="text-center text-muted">Loading transaction details...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" onclick="viewAuditTrail(currentTransactionId)">
                        <i class="fas fa-history me-1"></i>View Audit Trail
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="mt-5">
        <div class="container-fluid">
            <p class="mb-0 text-center">&copy; <?php echo date('Y'); ?> Evergreen Accounting & Finance. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/transaction-reading.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
