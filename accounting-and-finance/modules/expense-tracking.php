<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$transactionType = $_GET['transaction_type'] ?? '';
$status = $_GET['status'] ?? '';
$accountNumber = $_GET['account_number'] ?? '';
$applyFilters = isset($_GET['apply_filters']);

// Build query for expense claims
$sql = "SELECT 
            ec.id,
            ec.claim_no as transaction_number,
            ec.employee_external_no as employee_name,
            ec.expense_date as transaction_date,
            ec.amount,
            ec.description,
            ec.status,
            'expense_claim' as transaction_type,
            ecat.name as category_name,
            ecat.code as category_code,
            a.code as account_code,
            a.name as account_name,
            ec.created_at,
            'System' as created_by_name,
            approver.full_name as approved_by_name,
            ec.approved_at
        FROM expense_claims ec
        LEFT JOIN expense_categories ecat ON ec.category_id = ecat.id
        LEFT JOIN accounts a ON ecat.account_id = a.id
        LEFT JOIN users approver ON ec.approved_by = approver.id
        WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if ($applyFilters) {
    if (!empty($dateFrom)) {
        $sql .= " AND ec.expense_date >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND ec.expense_date <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    if (!empty($transactionType)) {
        $sql .= " AND 'expense_claim' = ?";
        $params[] = $transactionType;
        $types .= 's';
    }
    
    if (!empty($status)) {
        $sql .= " AND ec.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($accountNumber)) {
        $sql .= " AND a.code LIKE ?";
        $params[] = '%' . $accountNumber . '%';
        $types .= 's';
    }
}

$sql .= " ORDER BY ec.expense_date DESC, ec.created_at DESC";

// Execute query
$expenses = [];
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Query preparation failed - likely tables don't exist yet
    error_log("SQL Error: " . $conn->error);
    $expenses = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $expenses = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("SQL Execution Error: " . $stmt->error);
        $expenses = [];
    }
}

// Get filter options
$statusOptions = ['draft', 'submitted', 'approved', 'rejected', 'paid'];
$transactionTypeOptions = ['expense_claim'];

// Get account codes for filter
$accountOptions = [];
$accountStmt = $conn->prepare("SELECT DISTINCT a.code, a.name FROM accounts a 
                              JOIN expense_categories ec ON a.id = ec.account_id 
                              WHERE a.is_active = 1 ORDER BY a.code");
if ($accountStmt !== false) {
    if ($accountStmt->execute()) {
        $accountResult = $accountStmt->get_result();
        $accountOptions = $accountResult->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracking - Accounting and Finance System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/image/LOGO.png">
    <link rel="shortcut icon" type="image/png" href="../assets/image/LOGO.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/financial-reporting.css">
    <link rel="stylesheet" href="../assets/css/expense-tracking.css">
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
                        <a class="nav-link dropdown-toggle active" href="#" id="modulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-th-large me-1"></i>Modules
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="modulesDropdown">
                            <li><a class="dropdown-item" href="general-ledger.php"><i class="fas fa-book me-2"></i>General Ledger</a></li>
                            <li><a class="dropdown-item" href="financial-reporting.php"><i class="fas fa-chart-line me-2"></i>Financial Reporting</a></li>
                            <li><a class="dropdown-item" href="loan-accounting.php"><i class="fas fa-hand-holding-usd me-2"></i>Loan Accounting</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="transaction-reading.php"><i class="fas fa-exchange-alt me-2"></i>Transaction Reading</a></li>
                            <li><a class="dropdown-item active" href="expense-tracking.php"><i class="fas fa-receipt me-2"></i>Expense Tracking</a></li>
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
    
    <!-- Main Content -->
    <main class="container-fluid py-4">
        <!-- Beautiful Page Header -->
        <div class="beautiful-page-header mb-5">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="header-content">
                            <h1 class="page-title-beautiful">
                                <i class="fas fa-receipt me-3"></i>
                                Expense Tracking
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Monitor and manage all business expenses
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

        <div class="module-content">
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Filter Options</h3>
                    <button class="btn-toggle-filters" onclick="toggleFilters()">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <form class="filter-form" id="filterForm" method="GET">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="date_from">Date From:</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Date To:</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="transaction_type">Transaction Type:</label>
                            <select id="transaction_type" name="transaction_type">
                                <option value="">All Types</option>
                                <?php foreach ($transactionTypeOptions as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $transactionType === $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <?php foreach ($statusOptions as $statusOpt): ?>
                                    <option value="<?php echo $statusOpt; ?>" <?php echo $status === $statusOpt ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($statusOpt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="account_number">Account Number:</label>
                            <select id="account_number" name="account_number">
                                <option value="">All Accounts</option>
                                <?php foreach ($accountOptions as $account): ?>
                                    <option value="<?php echo $account['code']; ?>" <?php echo $accountNumber === $account['code'] ? 'selected' : ''; ?>>
                                        <?php echo $account['code'] . ' - ' . $account['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" name="apply_filters" class="btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="expense-tracking.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Section -->
            <div class="results-section">
                <div class="results-header">
                    <div class="results-info">
                        <h3><i class="fas fa-list"></i> Expense History</h3>
                        <span class="results-count">
                            <?php echo count($expenses); ?> record(s) found
                            <?php if ($applyFilters): ?>
                                <span class="filtered-indicator">(Filtered)</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="results-actions">
                        <?php if (!empty($expenses)): ?>
                            <button class="btn-export" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button class="btn-print" onclick="printReport()">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                            <button class="btn-audit" onclick="showAuditTrail()">
                                <i class="fas fa-history"></i> Audit Trail
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($expenses)): ?>
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>No Expense Records Found</h4>
                        <p>
                            <?php if ($applyFilters): ?>
                                No expenses match your current filter criteria. Try adjusting your filters or clear them to see all records.
                            <?php else: ?>
                                No expense records are available in the system.
                            <?php endif; ?>
                        </p>
                        <?php if ($applyFilters): ?>
                            <a href="expense-tracking.php" class="btn-primary">
                                <i class="fas fa-refresh"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="expense-table" id="expenseTable">
                            <thead>
                                <tr>
                                    <th>Transaction #</th>
                                    <th>Date</th>
                                    <th>Employee</th>
                                    <th>Category</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td>
                                            <span class="transaction-number"><?php echo htmlspecialchars($expense['transaction_number']); ?></span>
                                        </td>
                                        <td>
                                            <span class="transaction-date"><?php echo date('M d, Y', strtotime($expense['transaction_date'])); ?></span>
                                        </td>
                                        <td>
                                            <span class="employee-name"><?php echo htmlspecialchars($expense['employee_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="category-name"><?php echo htmlspecialchars($expense['category_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="account-info">
                                                <strong><?php echo htmlspecialchars($expense['account_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($expense['account_name']); ?></small>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount">₱<?php echo number_format($expense['amount'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $expense['status']; ?>">
                                                <?php echo ucfirst($expense['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="description"><?php echo htmlspecialchars(substr($expense['description'], 0, 50)) . (strlen($expense['description']) > 50 ? '...' : ''); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-view" onclick="viewExpense(<?php echo $expense['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-audit-item" onclick="viewAuditTrail(<?php echo $expense['id']; ?>)" title="Audit Trail">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Modal for Expense Details -->
    <div id="expenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Expense Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="expenseModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Modal for Audit Trail -->
    <div id="auditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Audit Trail</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="auditModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Evergreen Accounting & Finance. All rights reserved.</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/expense-tracking.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>

