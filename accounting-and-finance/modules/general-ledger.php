<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger - Accounting and Finance System</title>
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
    <link rel="stylesheet" href="../assets/css/general-ledger.css">
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
                            <li><a class="dropdown-item" href="transaction-reading.php"><i class="fas fa-exchange-alt me-2"></i>Transaction Reading</a></li>
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
    
    <!-- Main Content -->
    <main class="container-fluid py-4">
            <!-- Beautiful Page Header -->
            <div class="beautiful-page-header mb-5">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="header-content">
                                <h1 class="page-title-beautiful">
                                    <i class="fas fa-book me-3"></i>
                                    General Ledger
                                </h1>
                                <p class="page-subtitle-beautiful">
                                    Monitor ledger health, track movements, and stay audit-ready with a curated snapshot of every critical activity.
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

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-accounts">Loading...</h3>
                                <p>Total Accounts</p>
                                <a href="#accounts" class="stat-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-transactions">Loading...</h3>
                                <p>Posted Transactions</p>
                                <a href="#transactions" class="stat-link">View Details</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="stat-content">
                                <h3 id="total-audit">Loading...</h3>
                                <p>Total Audit Entries</p>
                                <a href="#audit" class="stat-link">View Details</a>
                            </div>
                        </div>
                    </div>

            <!-- Charts Overview -->
            <div class="gl-section">
                <div class="section-header">
                    <h2>Charts Overview</h2>
                    <p>Visual representation of your financial data</p>
                </div>
                <div class="chart-grid">
                    <div class="chart-container">
                        <h4>Account Type Distribution</h4>
                        <div class="chart-wrapper">
                            <canvas id="accountTypesChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-container">
                        <h4>Transaction Summary by Category</h4>
                        <div class="chart-wrapper">
                            <canvas id="transactionSummaryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="chart-actions">
                    <button class="btn-chart" onclick="applyChartFilters()">Apply Filters</button>
                    <button class="btn-chart-outline" onclick="viewDrillDown()">View Drill-Down Details</button>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="gl-section" id="accounts">
                <div class="section-header">
                    <h2>Accounts Table</h2>
                    <p>List of financial accounts with balances</p>
                </div>
                <div class="gl-toolbar" role="search" aria-label="Account search and filters">
                    <div class="gl-toolbar__field">
                        <label for="account-search" class="visually-hidden">Search accounts</label>
                        <span class="gl-toolbar__icon"><i class="fas fa-search"></i></span>
                        <input type="text" class="search-input" placeholder="Search accounts" id="account-search" autocomplete="off">
                    </div>
                    <div class="gl-toolbar__actions">
                        <button class="btn-filter" type="button" onclick="applyAccountFilter()">
                            <i class="fas fa-filter"></i>
                            <span>Apply filter</span>
                        </button>
                        <button class="btn-reset" type="button" onclick="resetAccountFilter()">
                            <i class="fas fa-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="gl-table" id="accounts-table">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th>Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="loading-spinner"></div>
                                    <p>Loading accounts...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-actions-row">
                    <span class="table-actions-hint">Showing the latest 10 active accounts</span>
                    <div class="table-actions">
                        <button class="btn-action btn-action-outline" type="button" onclick="exportAccounts()">Export</button>
                        <button class="btn-action" type="button" onclick="loadAccountsTable()">Refresh</button>
                    </div>
                </div>
            </div>

            <!-- Transaction Records -->
            <div class="gl-section" id="transactions">
                <div class="section-header">
                    <h2>Transaction Records</h2>
                    <p>Detailed transaction history and filters</p>
                </div>
                <div class="gl-toolbar gl-toolbar--split" role="search" aria-label="Transaction filters">
                    <div class="gl-toolbar__group">
                        <div class="gl-toolbar__field gl-toolbar__field--compact">
                            <label for="transaction-from">From</label>
                            <input type="date" id="transaction-from" class="gl-input">
                        </div>
                        <div class="gl-toolbar__field gl-toolbar__field--compact">
                            <label for="transaction-to">To</label>
                            <input type="date" id="transaction-to" class="gl-input">
                        </div>
                        <div class="gl-toolbar__field gl-toolbar__field--compact">
                            <label for="transaction-type">Type</label>
                            <select id="transaction-type" class="gl-select">
                                <option value="">All types</option>
                                <option value="sale">Sales</option>
                                <option value="purchase">Purchases</option>
                                <option value="payment">Payments</option>
                                <option value="receipt">Receipts</option>
                            </select>
                        </div>
                    </div>
                    <div class="gl-toolbar__actions">
                        <button class="btn-filter" type="button" onclick="applyTransactionFilter()">
                            <i class="fas fa-sliders-h"></i>
                            <span>Apply</span>
                        </button>
                        <button class="btn-reset" type="button" onclick="resetTransactionFilter()">
                            <i class="fas fa-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="gl-table" id="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="loading-spinner"></div>
                                    <p>Loading transactions...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-actions-row">
                    <span class="table-actions-hint">Latest journal entries posted to the ledger</span>
                    <div class="table-actions">
                        <button class="btn-action btn-action-outline" type="button" onclick="exportTransactions()">Export</button>
                        <button class="btn-action btn-action-outline" type="button" onclick="printTransactions()">Print</button>
                        <button class="btn-action" type="button" onclick="refreshTransactions()">Refresh</button>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="gl-section" id="audit">
                <div class="section-header">
                    <h2>Audit Trail</h2>
                    <p>Track all system activities and changes</p>
                </div>
                <div class="gl-toolbar" role="search" aria-label="Audit trail filters">
                    <div class="gl-toolbar__field">
                        <label for="audit-date-from" class="visually-hidden">From date</label>
                        <input type="date" id="audit-date-from" class="gl-input" placeholder="From date">
                    </div>
                    <div class="gl-toolbar__field">
                        <label for="audit-date-to" class="visually-hidden">To date</label>
                        <input type="date" id="audit-date-to" class="gl-input" placeholder="To date">
                    </div>
                    <div class="gl-toolbar__actions">
                        <button class="btn-filter" type="button" onclick="loadAuditTrail()">
                            <i class="fas fa-filter"></i>
                            <span>Apply filter</span>
                        </button>
                        <button class="btn-reset" type="button" onclick="resetAuditFilter()">
                            <i class="fas fa-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="gl-table" id="audit-trail-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Object Type</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="loading-spinner"></div>
                                    <p>Loading audit trail...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-actions-row">
                    <span class="table-actions-hint">Showing last 100 audit log entries</span>
                    <div class="table-actions">
                        <button class="btn-action btn-action-outline" type="button" onclick="exportAuditTrail()">Export</button>
                        <button class="btn-action" type="button" onclick="loadAuditTrail()">Refresh</button>
                    </div>
                </div>
            </div>

            <!-- Trial Balance Report -->
            <div class="gl-section" id="trial-balance">
                <div class="section-header">
                    <h2>Trial Balance</h2>
                    <p>Account balances summary for a specific date range</p>
                </div>
                <div class="gl-toolbar" role="search" aria-label="Trial balance filters">
                    <div class="gl-toolbar__group">
                        <div class="gl-toolbar__field gl-toolbar__field--compact">
                            <label for="trial-balance-from">From</label>
                            <input type="date" id="trial-balance-from" class="gl-input">
                        </div>
                        <div class="gl-toolbar__field gl-toolbar__field--compact">
                            <label for="trial-balance-to">To</label>
                            <input type="date" id="trial-balance-to" class="gl-input">
                        </div>
                    </div>
                    <div class="gl-toolbar__actions">
                        <button class="btn-filter" type="button" onclick="generateTrialBalance()">
                            <i class="fas fa-calculator"></i>
                            <span>Generate Report</span>
                        </button>
                        <button class="btn-reset" type="button" onclick="resetTrialBalanceFilter()">
                            <i class="fas fa-rotate-left"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="gl-table" id="trial-balance-table">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th>Type</th>
                                <th class="text-end">Debit Balance</th>
                                <th class="text-end">Credit Balance</th>
                                <th class="text-end">Net Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <p class="text-muted">Select date range and click "Generate Report" to view trial balance</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot id="trial-balance-footer" style="display: none;">
                            <!-- Totals will be added here -->
                        </tfoot>
                    </table>
                </div>
                <div class="table-actions-row">
                    <span class="table-actions-hint" id="trial-balance-hint">Trial balance for selected period</span>
                    <div class="table-actions">
                        <button class="btn-action btn-action-outline" type="button" onclick="exportTrialBalance()" id="exportTrialBalanceBtn" style="display: none;">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                        <button class="btn-action btn-action-outline" type="button" onclick="printTrialBalance()" id="printTrialBalanceBtn" style="display: none;">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>

    </main>

    <!-- Journal Entry Detail Modal -->
    <div class="modal fade" id="journalEntryDetailModal" tabindex="-1" aria-labelledby="journalEntryDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="journalEntryDetailModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Journal Entry Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="journalEntryDetailBody">
                    <p class="text-center text-muted">Loading details...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success d-none" id="postJournalEntryBtn" onclick="postJournalEntry()">
                        <i class="fas fa-check me-1"></i>Post
                    </button>
                    <button type="button" class="btn btn-danger d-none" id="voidJournalEntryBtn" onclick="voidJournalEntry()">
                        <i class="fas fa-times me-1"></i>Void
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="gl-footer">
        <p>&copy; 2025 Evergreen Accounting & Finance. All rights reserved.</p>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/general-ledger.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>