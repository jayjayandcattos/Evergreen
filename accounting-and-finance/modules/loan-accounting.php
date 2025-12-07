<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Ensure soft delete columns exist in loans table
function ensureSoftDeleteColumnsExist($conn) {
    try {
        // Check if deleted_at column exists
        $checkSql = "SHOW COLUMNS FROM loans LIKE 'deleted_at'";
        $result = $conn->query($checkSql);
        
        if (!$result || $result->num_rows === 0) {
            // Add deleted_at column
            $alterSql = "ALTER TABLE loans ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL";
            if (!$conn->query($alterSql)) {
                error_log("Failed to add deleted_at column: " . $conn->error);
            }
        }
        
        // Check if deleted_by column exists
        $checkSql = "SHOW COLUMNS FROM loans LIKE 'deleted_by'";
        $result = $conn->query($checkSql);
        
        if (!$result || $result->num_rows === 0) {
            // Add deleted_by column
            $alterSql = "ALTER TABLE loans ADD COLUMN deleted_by INT NULL DEFAULT NULL";
            if (!$conn->query($alterSql)) {
                error_log("Failed to add deleted_by column: " . $conn->error);
            }
        }
    } catch (Exception $e) {
        error_log("Error ensuring soft delete columns exist: " . $e->getMessage());
    }
}

// Ensure columns exist before querying
ensureSoftDeleteColumnsExist($conn);

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$transactionType = $_GET['transaction_type'] ?? '';
$status = $_GET['status'] ?? '';
$accountNumber = $_GET['account_number'] ?? '';
$applyFilters = isset($_GET['apply_filters']);

// Build query to combine both loans and loan_applications
// This ensures loan applications from the loan subsystem are visible in loan-accounting
// Show only applications for now (since loans table might be empty)
// Set to false to include both loans and applications
$showOnlyApplications = true;

if ($showOnlyApplications) {
    $sql = "SELECT 
            la.id,
            CONCAT('APP-', la.id) as loan_number,
            la.full_name as borrower_name,
            la.loan_amount as loan_amount,
            0 as interest_rate,
            0 as loan_term,
            la.created_at as start_date,
            la.due_date as maturity_date,
            0.00 as outstanding_balance,
            la.status,
            'application' as record_type,
            la.loan_type as loan_type_name,
            la.account_number,
            la.created_at,
            la.approved_by as created_by_name,
            la.contact_number,
            la.email,
            la.job,
            la.monthly_salary,
            la.user_email,
            la.purpose,
            la.monthly_payment,
            la.due_date,
            la.next_payment_due,
            la.approved_by,
            la.approved_at,
            la.rejected_by,
            la.rejected_at,
            la.rejection_remarks,
            la.remarks,
            la.file_name,
            la.proof_of_income,
            la.coe_document,
            la.pdf_path,
            la.id as application_id
        FROM loan_applications la
        WHERE 1=1
        ORDER BY la.id DESC";
} else {
$sql = "SELECT 
            l.id,
            l.loan_no as loan_number,
            l.borrower_external_no as borrower_name,
            l.principal_amount as loan_amount,
            l.interest_rate,
            l.term_months as loan_term,
            l.start_date,
            DATE_ADD(l.start_date, INTERVAL l.term_months MONTH) as maturity_date,
            l.current_balance as outstanding_balance,
            l.status,
            'loan' as record_type,
            lt.name as loan_type_name,
            NULL as account_number,
            l.created_at,
            u.full_name as created_by_name,
            NULL as contact_number,
            NULL as email,
            NULL as job,
            NULL as monthly_salary,
            NULL as user_email,
            NULL as purpose,
            NULL as application_id
        FROM loans l
        LEFT JOIN loan_types lt ON l.loan_type_id = lt.id
        LEFT JOIN users u ON l.created_by = u.id
        WHERE (l.deleted_at IS NULL OR l.deleted_at = '') 
          AND l.status != 'cancelled'
        
        UNION ALL
        
        SELECT 
            la.id + 1000000 as id, -- Offset to avoid ID conflicts
            CONCAT('APP-', la.id) as loan_number,
            COALESCE(la.full_name, la.user_email) as borrower_name,
            COALESCE(la.loan_amount, 0) as loan_amount,
            COALESCE(lt_app.interest_rate, 0) as interest_rate,
            CASE 
                WHEN la.loan_terms LIKE '%6%' OR la.loan_terms LIKE '%6 Months%' THEN 6
                WHEN la.loan_terms LIKE '%12%' OR la.loan_terms LIKE '%12 Months%' THEN 12
                WHEN la.loan_terms LIKE '%24%' OR la.loan_terms LIKE '%24 Months%' THEN 24
                WHEN la.loan_terms LIKE '%30%' OR la.loan_terms LIKE '%30 Months%' THEN 30
                WHEN la.loan_terms LIKE '%36%' OR la.loan_terms LIKE '%36 Months%' THEN 36
                ELSE 0
            END as loan_term,
            la.created_at as start_date,
            la.due_date as maturity_date,
            0.00 as outstanding_balance,
            la.status,
            'application' as record_type,
            COALESCE(lt_app.name, la.loan_type, 'N/A') as loan_type_name,
            la.account_number,
            la.created_at,
            COALESCE(u_app.full_name, la.approved_by) as created_by_name,
            la.contact_number,
            la.email,
            la.job,
            la.monthly_salary,
            la.user_email,
            la.purpose,
            la.monthly_payment,
            la.due_date,
            la.next_payment_due,
            la.approved_by,
            la.approved_at,
            la.rejected_by,
            la.rejected_at,
            la.rejection_remarks,
            la.remarks,
            la.file_name,
            la.proof_of_income,
            la.coe_document,
            la.pdf_path,
            la.id as application_id
        FROM loan_applications la
        LEFT JOIN loan_types lt_app ON la.loan_type_id = lt_app.id
        LEFT JOIN users u_app ON la.approved_by_user_id = u_app.id
        WHERE 1=1"; // Show all loan applications (matching adminindex.php)
}

$params = [];
$types = '';
$loanConditions = [];
$appConditions = [];

// Apply filters - separate conditions for loans and applications
if ($applyFilters) {
    if (!empty($dateFrom)) {
        if (!$showOnlyApplications) {
        $loanConditions[] = "l.start_date >= ?";
        $params[] = $dateFrom;
            $types .= 's';
        }
        $sql = str_replace("WHERE 1=1", "WHERE la.created_at >= '$dateFrom'", $sql);
    }
    
    if (!empty($dateTo)) {
        if (!$showOnlyApplications) {
        $loanConditions[] = "l.start_date <= ?";
        $params[] = $dateTo;
            $types .= 's';
        }
        if (strpos($sql, "WHERE la.created_at >=") !== false) {
            $sql = str_replace("WHERE la.created_at >=", "WHERE la.created_at >= '$dateFrom' AND la.created_at <=", $sql);
            $sql = str_replace("WHERE 1=1", "WHERE 1=1 AND la.created_at <= '$dateTo'", $sql);
        } else {
            $sql = str_replace("WHERE 1=1", "WHERE la.created_at <= '$dateTo'", $sql);
        }
    }
    
    if (!empty($status)) {
        if (!$showOnlyApplications) {
        $loanConditions[] = "l.status = ?";
        $params[] = $status;
            $types .= 's';
        }
        $sql = str_replace("WHERE 1=1", "WHERE la.status = '$status'", $sql);
        $sql = str_replace("ORDER BY", "AND la.status = '$status' ORDER BY", $sql);
    }
    
    if (!empty($accountNumber)) {
        if (!$showOnlyApplications) {
        $loanConditions[] = "l.loan_no LIKE ?";
            $searchTerm = "%{$accountNumber}%";
            $params[] = $searchTerm;
            $types .= 's';
        }
        $searchTerm = "%{$accountNumber}%";
        $sql = str_replace("WHERE 1=1", "WHERE (CONCAT('APP-', la.id) LIKE '$searchTerm' OR la.account_number LIKE '$searchTerm' OR la.full_name LIKE '$searchTerm')", $sql);
    }
}

// Add conditions to loans query
if (!empty($loanConditions)) {
    $sql = str_replace("WHERE (l.deleted_at IS NULL OR l.deleted_at = '')", 
                       "WHERE (l.deleted_at IS NULL OR l.deleted_at = '') AND " . implode(" AND ", $loanConditions), $sql);
}

// Add conditions to applications query (need to find the second WHERE clause in UNION)
if (!$showOnlyApplications && !empty($appConditions)) {
    // Find the position after UNION ALL and before the second SELECT
    $unionPos = strpos($sql, "UNION ALL");
    if ($unionPos !== false) {
        // Find WHERE 1=1 in the applications part (after UNION ALL)
        $appPart = substr($sql, $unionPos);
        $wherePos = strpos($appPart, "WHERE 1=1");
        if ($wherePos !== false) {
            // Replace WHERE 1=1 with WHERE 1=1 AND conditions
            $beforeWhere = substr($sql, 0, $unionPos + $wherePos);
            $afterWhere = substr($sql, $unionPos + $wherePos + 8); // 8 = length of "WHERE 1=1"
            $sql = $beforeWhere . "WHERE 1=1 AND " . implode(" AND ", $appConditions) . $afterWhere;
        }
    }
}

// Wrap in subquery for proper ordering (only if using UNION)
if (!$showOnlyApplications) {
$sql = "SELECT * FROM ($sql) AS combined_results ORDER BY start_date DESC, loan_number DESC";
}

// Execute query with fallback
$loans = [];
$hasResults = false;
$queryError = null;

// Debug: Log the query
error_log("Loan Accounting Query: " . $sql);

if ($conn) {
    // Try the UNION query first
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        // Query preparation failed - try simple loans query as fallback
        $queryError = $conn->error;
        error_log("Query preparation failed: " . $queryError);
        // Try a simple loan_applications query as fallback
        $fallbackSql = "SELECT 
            la.id,
            CONCAT('APP-', la.id) as loan_number,
            la.full_name as borrower_name,
            la.loan_amount,
            0 as interest_rate,
            0 as loan_term,
            la.created_at as start_date,
            la.due_date as maturity_date,
            0.00 as outstanding_balance,
            la.status,
            'application' as record_type,
            la.loan_type as loan_type_name,
            la.account_number,
            la.created_at,
            la.approved_by as created_by_name,
            la.contact_number,
            la.email,
            la.job,
            la.monthly_salary,
            la.user_email,
            la.purpose,
            la.monthly_payment,
            la.due_date,
            la.next_payment_due,
            la.approved_by,
            la.approved_at,
            la.rejected_by,
            la.rejected_at,
            la.rejection_remarks,
            la.remarks,
            la.file_name,
            la.proof_of_income,
            la.coe_document,
            la.pdf_path,
            la.id as application_id
        FROM loan_applications la
        ORDER BY la.id DESC";
        
        $stmt = $conn->prepare($fallbackSql);
        if ($stmt && $stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $loans[] = $row;
            }
            $hasResults = count($loans) > 0;
            $queryError = null; // Clear error if fallback worked
            error_log("Fallback query found " . count($loans) . " loan applications");
        } else {
            error_log("Fallback query also failed: " . ($stmt ? $stmt->error : $conn->error));
        }
        if ($stmt) $stmt->close();
    } else {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $loans[] = $row;
            }
            
            $hasResults = count($loans) > 0;
            error_log("Loan Accounting: Found " . count($loans) . " loans/applications");
        } else {
            // Execution failed
            $queryError = $stmt->error;
            error_log("Query execution failed: " . $queryError);
        }
        
        $stmt->close();
    }
}

// Calculate statistics
$totalLoans = count($loans); // Total count of all records
$totalApplications = 0;
$totalAmount = 0;
$totalOutstanding = 0;
$activeLoans = 0;
$pendingApplications = 0;
$actualLoanCount = 0; // Actual loans (not applications)

foreach ($loans as $loan) {
    $totalAmount += floatval($loan['loan_amount']);
    
    if ($loan['record_type'] === 'loan') {
        $actualLoanCount++;
        $totalOutstanding += floatval($loan['outstanding_balance']);
        if (strtolower($loan['status']) === 'active') {
            $activeLoans++;
        }
    } else {
        // This is an application
        $totalApplications++;
        if (strtolower($loan['status']) === 'pending') {
            $pendingApplications++;
        }
        // Count approved/active applications as active loans
        if (in_array(strtolower($loan['status']), ['approved', 'active'])) {
            $activeLoans++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Accounting - Accounting and Finance System</title>
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
    <link rel="stylesheet" href="../assets/css/loan-accounting.css">
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
                            <li><a class="dropdown-item active" href="loan-accounting.php"><i class="fas fa-hand-holding-usd me-2"></i>Loan Accounting</a></li>
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
                                <i class="fas fa-hand-holding-usd me-3"></i>
                                Loan Accounting
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Monitor and manage loan records and calculations
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
        
        <div class="container">
        
        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $totalLoans; ?></h3>
                        <p>Total Loans</p>
                        <?php if ($totalLoans > 0): ?>
                            <small class="text-muted">₱<?php echo number_format($totalAmount, 2); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $totalApplications; ?></h3>
                        <p>Loan Applications</p>
                        <?php if ($pendingApplications > 0): ?>
                            <small class="text-warning"><i class="fas fa-clock me-1"></i><?php echo $pendingApplications; ?> Pending</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $activeLoans; ?></h3>
                        <p>Active Loans</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-content">
                        <h3>₱<?php echo number_format($totalOutstanding, 2); ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <button type="button" class="btn btn-primary" id="btnShowFilters">
                            <i class="fas fa-filter me-1"></i>Apply Filters
                        </button>
                        <?php if ($applyFilters): ?>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="viewAuditTrail()">
                            <i class="fas fa-history me-1"></i>Audit Trail
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportToExcel()">
                            <i class="fas fa-file-excel me-1"></i>Export
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="printTable()">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Panel -->
        <div class="card mb-4" id="filterPanel" style="display: <?php echo $applyFilters ? 'block' : 'none'; ?>;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Loan Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="apply_filters" value="1">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending (Application)</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending (Loan)</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active (Application)</option>
                                <option value="Approved" <?php echo $status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="defaulted" <?php echo $status === 'defaulted' ? 'selected' : ''; ?>>Defaulted</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loan Number</label>
                            <input type="text" class="form-control" name="account_number" 
                                   placeholder="Search by loan number..." 
                                   value="<?php echo htmlspecialchars($accountNumber); ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Loan History Table -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Loan History</h5>
            </div>
            <!-- Print Title - Only visible when printing -->
            <div class="print-title d-none">
                <h2 class="text-center mb-3">LOAN HISTORY REPORT</h2>
                <p class="text-center text-muted mb-4">Generated on <?php echo date('F d, Y'); ?></p>
            </div>
            <div class="card-body">
                <?php if ($queryError): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Database Query Error:</strong> <?php echo htmlspecialchars($queryError); ?>
                    <br><small>Please check that the loans and loan_applications tables exist and have the correct structure.</small>
                    <details class="mt-2">
                        <summary>Debug Info</summary>
                        <pre style="font-size: 10px; max-height: 300px; overflow: auto;"><?php echo htmlspecialchars($sql); ?></pre>
                    </details>
                </div>
                <?php elseif (!$hasResults): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h4><?php echo $applyFilters ? 'No Existing Information Found' : 'No Loan Data Available'; ?></h4>
                    <p><?php echo $applyFilters ? 'No loans match your filter criteria. Try adjusting your filters.' : 'Start by creating loan records or applying filters to view existing loans.'; ?></p>
                    <details class="mt-2">
                        <summary>Debug Info</summary>
                        <p>Total results found: <?php echo count($loans); ?></p>
                        <p>Filters applied: <?php echo $applyFilters ? 'Yes' : 'No'; ?></p>
                        <p>Query error: <?php echo $queryError ? htmlspecialchars($queryError) : 'None'; ?></p>
                    </details>
                    <?php if ($applyFilters): ?>
                    <button class="btn btn-primary mt-3" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table id="loanTable" class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Type</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Loan No.</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Borrower/Applicant</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Loan Type</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Start/Applied Date</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Maturity Date</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Loan Amount</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Interest Rate</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Outstanding</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Status</th>
                                <th style="background-color: #f8f9fa; color: #0A3D3D; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>
                                    <?php if ($loan['record_type'] === 'application'): ?>
                                        <span class="badge bg-info"><i class="fas fa-file-alt me-1"></i>Application</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><i class="fas fa-hand-holding-usd me-1"></i>Loan</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($loan['borrower_name']); ?>
                                    <?php if ($loan['record_type'] === 'application' && !empty($loan['contact_number'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($loan['contact_number']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($loan['record_type'] === 'application' && !empty($loan['email'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($loan['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($loan['loan_type_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($loan['start_date'])); ?></td>
                                <td>
                                    <?php if (!empty($loan['maturity_date'])): ?>
                                        <?php echo date('M d, Y', strtotime($loan['maturity_date'])); ?>
                                    <?php elseif ($loan['record_type'] === 'application' && !empty($loan['due_date'])): ?>
                                        <?php echo date('M d, Y', strtotime($loan['due_date'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">₱<?php echo number_format($loan['loan_amount'], 2); ?></td>
                                <td class="text-center"><?php echo number_format($loan['interest_rate'], 2); ?>%</td>
                                <td class="text-end">
                                    <?php if ($loan['record_type'] === 'loan'): ?>
                                        ₱<?php echo number_format($loan['outstanding_balance'], 2); ?>
                                    <?php elseif ($loan['record_type'] === 'application' && !empty($loan['monthly_payment'])): ?>
                                        <small class="text-muted">Monthly: ₱<?php echo number_format($loan['monthly_payment'], 2); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo strtolower($loan['status']); ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info btn-action" onclick="<?php echo $loan['record_type'] === 'application' ? 'viewApplicationDetails(' . $loan['application_id'] . ')' : 'viewLoanDetails(' . $loan['id'] . ')'; ?>" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($loan['record_type'] === 'loan'): ?>
                                            <button class="btn btn-danger btn-action" onclick="deleteLoan(<?php echo $loan['id']; ?>)" title="Move to Bin">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <!-- Applications can also be deleted if needed -->
                                            <button class="btn btn-danger btn-action" onclick="deleteApplication(<?php echo $loan['application_id']; ?>)" title="Delete Application">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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
        
        </div>
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Evergreen Accounting & Finance. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Loan Details Modal -->
    <div class="modal fade" id="loanDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Loan Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="loanDetailsBody">
                    <!-- Content loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Audit Trail Modal -->
    <div class="modal fade" id="auditTrailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Loan Audit Trail</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Loan No.</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody id="auditTrailBody">
                                <!-- Content loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="exportAuditTrail()">
                        <i class="fas fa-file-excel me-1"></i>Export Audit Trail
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/loan-accounting.js"></script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>

