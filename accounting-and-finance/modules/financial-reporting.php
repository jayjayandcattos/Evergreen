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
    <title>Financial Reporting & Compliance - Accounting and Finance System</title>
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
                                <i class="fas fa-chart-line me-3"></i>
                                Financial Reporting & Compliance
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Generate comprehensive financial reports and analyze your business performance
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

        <!-- Reports Section -->
        <div class="reports-section">
            <div class="section-header-simple mb-4">
                <h2 class="section-title-simple">Financial Reports</h2>
                <p class="section-subtitle-simple">Select a report type to generate detailed financial analysis</p>
                </div>


            <!-- Report Cards Grid -->
            <div class="row g-4 mb-5">
                <!-- Balance Sheet Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="report-card-modern h-100">
                        <div class="card-header-modern">
                            <div class="report-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <div class="report-meta">
                                <h5 class="report-title">Balance Sheet</h5>
                                <p class="report-subtitle">Assets, Liabilities, and Equity</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="report-summary">
                                <?php 
                                // Get asset total
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0) as total_assets
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'asset' AND je.status = 'posted'
                                ");
                                $assets = $result->fetch_assoc()['total_assets'];
                                
                                // Get liability total
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0) as total_liabilities
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'liability' AND je.status = 'posted'
                                ");
                                $liabilities = abs($result->fetch_assoc()['total_liabilities']);
                                
                                // Get equity total
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0) as total_equity
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'equity' AND je.status = 'posted'
                                ");
                                $equity = abs($result->fetch_assoc()['total_equity']);
                                ?>
                                <div class="summary-item">
                                    <span class="summary-label">Assets</span>
                                    <span class="summary-value text-primary">₱<?php echo number_format($assets, 0); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Liabilities</span>
                                    <span class="summary-value text-warning">₱<?php echo number_format($liabilities, 0); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Equity</span>
                                    <span class="summary-value text-success">₱<?php echo number_format($equity, 0); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer-modern">
                            <button class="btn btn-primary btn-generate-modern" onclick="openReportModal('balance-sheet')">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                    </div>

                    <!-- Income Statement Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="report-card-modern h-100">
                        <div class="card-header-modern">
                            <div class="report-icon">
                            <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="report-meta">
                                <h5 class="report-title">Income Statement</h5>
                                <p class="report-subtitle">Revenue, expenses, and Net income</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="report-summary">
                                <?php 
                                // Get revenue total
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.credit - jl.debit), 0) as total_revenue
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'revenue' AND je.status = 'posted'
                                ");
                                $revenue = $result->fetch_assoc()['total_revenue'];
                                
                                // Get expense total
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0) as total_expenses
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'expense' AND je.status = 'posted'
                                ");
                                $expenses = $result->fetch_assoc()['total_expenses'];
                                
                                $net_income = $revenue - $expenses;
                                ?>
                                <div class="summary-item">
                                    <span class="summary-label">Revenue</span>
                                    <span class="summary-value text-success">₱<?php echo number_format($revenue, 0); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Expenses</span>
                                    <span class="summary-value text-danger">₱<?php echo number_format($expenses, 0); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Net Income</span>
                                    <span class="summary-value <?php echo $net_income >= 0 ? 'text-success' : 'text-danger'; ?>">₱<?php echo number_format($net_income, 0); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer-modern">
                            <button class="btn btn-primary btn-generate-modern" onclick="openReportModal('income-statement')">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                    </div>

                    <!-- Cash Flow Statement Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="report-card-modern h-100">
                        <div class="card-header-modern">
                            <div class="report-icon">
                            <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="report-meta">
                                <h5 class="report-title">Cash Flow Statement</h5>
                                <p class="report-subtitle">Operating, Investing, and Financing Activities</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="report-summary">
                                <?php 
                                // Get cash accounts balance
                                $result = $conn->query("
                                    SELECT COALESCE(SUM(jl.debit - jl.credit), 0) as cash_balance
                                    FROM accounts a
                                    INNER JOIN account_types at ON a.type_id = at.id
                                    LEFT JOIN journal_lines jl ON a.id = jl.account_id
                                    LEFT JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE a.is_active = 1 AND at.category = 'asset' 
                                    AND (a.name LIKE '%cash%' OR a.name LIKE '%bank%') AND je.status = 'posted'
                                ");
                                $cash_balance = $result->fetch_assoc()['cash_balance'];
                                ?>
                                <div class="summary-item">
                                    <span class="summary-label">Cash Balance</span>
                                    <span class="summary-value text-info">₱<?php echo number_format($cash_balance, 0); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Operating</span>
                                    <span class="summary-value text-muted">Revenue - Expenses</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Investing</span>
                                    <span class="summary-value text-muted">Asset purchases</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer-modern">
                            <button class="btn btn-primary btn-generate-modern" onclick="openReportModal('cash-flow')">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                    </div>

                    <!-- Trial Balance Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="report-card-modern h-100">
                        <div class="card-header-modern">
                            <div class="report-icon">
                            <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="report-meta">
                                <h5 class="report-title">Trial Balance</h5>
                                <p class="report-subtitle">Account Balances and Totals</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="report-summary">
                                <?php 
                                // Get trial balance totals
                                $result = $conn->query("
                                    SELECT 
                                        SUM(jl.debit) as total_debits,
                                        SUM(jl.credit) as total_credits
                                    FROM journal_lines jl
                                    INNER JOIN journal_entries je ON jl.journal_entry_id = je.id
                                    WHERE je.status = 'posted'
                                ");
                                $totals = $result->fetch_assoc();
                                $is_balanced = abs($totals['total_debits'] - $totals['total_credits']) < 0.01;
                                ?>
                                <div class="summary-item">
                                    <span class="summary-label">Total Debits</span>
                                    <span class="summary-value text-danger">₱<?php echo number_format($totals['total_debits'], 0); ?></span>
                        </div>
                                <div class="summary-item">
                                    <span class="summary-label">Total Credits</span>
                                    <span class="summary-value text-success">₱<?php echo number_format($totals['total_credits'], 0); ?></span>
                    </div>
                                <div class="summary-item">
                                    <span class="summary-label">Status</span>
                                    <span class="summary-value <?php echo $is_balanced ? 'text-success' : 'text-warning'; ?>">
                                        <?php echo $is_balanced ? "✓ Balanced" : "⚠ Unbalanced"; ?>
                                    </span>
                </div>
            </div>
                        </div>
                        <div class="card-footer-modern">
                            <button class="btn btn-primary btn-generate-modern" onclick="openReportModal('trial-balance')">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Regulatory Reports Card -->
                <div class="col-lg-4 col-md-6">
                    <div class="report-card-modern h-100">
                        <div class="card-header-modern">
                            <div class="report-icon">
                                <i class="fas fa-shield-alt"></i>
                    </div>
                            <div class="report-meta">
                                <h5 class="report-title">Regulatory Reports</h5>
                                <p class="report-subtitle">BSP, SEC, or internal compliance templates</p>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="report-summary">
                                <div class="summary-item">
                                    <span class="summary-label">BSP Reports</span>
                                    <span class="summary-value text-primary">Available</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">SEC Filings</span>
                                    <span class="summary-value text-success">Available</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Internal Compliance</span>
                                    <span class="summary-value text-warning">Available</span>
                    </div>
                            </div>
                        </div>
                        <div class="card-footer-modern">
                            <button class="btn btn-primary btn-generate-modern" onclick="openReportModal('regulatory-reports')">
                                <i class="fas fa-file-alt me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>
                            </div>
                        </div>

        <!-- Filtering Section -->
        <div class="filtering-section-modern">
            <div class="section-header-simple mb-4">
                <h2 class="section-title-simple">
                    <i class="fas fa-filter me-2" style="color: var(--primary-teal);"></i>Data Filtering & Search
                </h2>
                <p class="section-subtitle-simple">Filter and search financial data across all reports</p>
            </div>

            <div class="filtering-card">
                <div class="row g-4 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-calendar-alt me-2" style="color: var(--primary-teal);"></i>Date From
                            </label>
                            <input type="date" class="form-control form-control-modern" id="filter-date-from">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-calendar-alt me-2" style="color: var(--primary-teal);"></i>Date To
                            </label>
                            <input type="date" class="form-control form-control-modern" id="filter-date-to">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-cogs me-2" style="color: var(--primary-teal);"></i>Subsystem
                            </label>
                            <select class="form-select form-select-modern" id="filter-subsystem">
                                <option value="">All Subsystems</option>
                                <option value="general-ledger">General Ledger</option>
                                <option value="payroll">Payroll</option>
                                <option value="expense">Expense Tracking</option>
                                <option value="loan">Loan Accounting</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-tags me-2" style="color: var(--primary-teal);"></i>Account Type
                            </label>
                            <select class="form-select form-select-modern" id="filter-account-type">
                                <option value="">All Types</option>
                                <option value="asset">Assets</option>
                                <option value="liability">Liabilities</option>
                                <option value="equity">Equity</option>
                                <option value="revenue">Revenue</option>
                                <option value="expense">Expenses</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="filter-group">
                            <label class="filter-label">
                                <i class="fas fa-search me-2" style="color: var(--primary-teal);"></i>Custom Search
                            </label>
                            <input type="text" class="form-control form-control-modern" id="filter-custom-search" 
                                   placeholder="Search by account name, description, or reference number...">
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex align-items-end">
                        <div class="filter-actions">
                            <button class="btn btn-primary btn-lg me-3 px-4" onclick="applyFilters()">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <button class="btn btn-outline-secondary btn-lg px-3" onclick="clearFilters()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
                </div>

        <!-- Filtered Results Section -->
        <div class="filtered-results-modern" id="filtered-results" style="display: none;">
            <div class="section-header-simple mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="section-title-simple mb-2">
                            <i class="fas fa-table me-2" style="color: var(--primary-teal);"></i>Filtered Results
                        </h2>
                        <p class="section-subtitle-simple mb-2" id="results-summary">Showing filtered results</p>
                        <span class="badge bg-success text-white fs-6 px-3 py-2" id="filter-status">No filters applied</span>
                    </div>
                    <!-- Action Buttons -->
                    <div class="results-actions-simple mt-2">
                        <button class="btn btn-success btn-lg me-2" onclick="exportFilteredData('excel')">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                        <button class="btn btn-danger btn-lg me-2" onclick="exportFilteredData('pdf')">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <button class="btn btn-secondary btn-lg" onclick="printFilteredData()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div class="pagination-controls mb-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label for="entries-per-page" class="form-label me-2 mb-0">Show</label>
                            <select class="form-select form-select-sm" id="entries-per-page" style="width: auto;" onchange="changeEntriesPerPage()">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="ms-2">entries per page</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center">
                            <span class="text-muted me-3" id="pagination-info">Showing 0 to 0 of 0 entries</span>
                            <nav aria-label="Pagination">
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" onclick="goToPage(1)">First</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" onclick="goToPreviousPage()">Previous</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" onclick="goToNextPage()">Next</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" onclick="goToLastPage()">Last</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="results-table-container">
                <div class="table-responsive">
                    <table class="table table-modern" id="filtered-results-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar me-1" style="color: var(--primary-teal);"></i>Date</th>
                                <th><i class="fas fa-hashtag me-1" style="color: var(--primary-teal);"></i>Account Code</th>
                                <th><i class="fas fa-tag me-1" style="color: var(--primary-teal);"></i>Account Name</th>
                                <th><i class="fas fa-align-left me-1" style="color: var(--primary-teal);"></i>Description</th>
                                <th class="text-end"><i class="fas fa-arrow-up me-1" style="color: var(--accent-gold);"></i>Debit</th>
                                <th class="text-end"><i class="fas fa-arrow-down me-1" style="color: var(--primary-teal);"></i>Credit</th>
                                <th class="text-end"><i class="fas fa-balance-scale me-1" style="color: var(--primary-teal);"></i>Balance</th>
                            </tr>
                        </thead>
                        <tbody id="filtered-results-tbody">
                            <!-- Filtered results will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- No Results Message -->
            <div class="no-results-modern" id="no-results-message" style="display: none;">
                <div class="text-center py-5">
                    <div class="no-results-icon mb-4">
                        <i class="fas fa-search fa-4x text-muted opacity-50"></i>
                    </div>
                    <h3 class="text-muted mb-3">No Results Found</h3>
                    <p class="text-muted mb-4">No records match your current filter criteria. Try adjusting your filters.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button class="btn btn-outline-primary" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </button>
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fas fa-search me-1"></i>Try Different Filters
                            </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle">Generate Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="reportModalContent"></div>
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
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/financial-reporting.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
