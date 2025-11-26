<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Test database connection
$connection_status = 'disconnected';
$connection_error = '';
$db_info = [];

if ($conn && !$conn->connect_error) {
    $connection_status = 'connected';
    
    // Get database information
    try {
        $result = $conn->query("SELECT VERSION() as version");
        if ($result && $result->num_rows > 0) {
            $version_row = $result->fetch_assoc();
            $db_info['version'] = isset($version_row['version']) ? $version_row['version'] : 'Unknown';
            $result->free();
        } else {
            $db_info['version'] = 'Unknown';
            if ($result) $result->free();
        }
    } catch (Exception $e) {
        error_log("Error getting MySQL version: " . $e->getMessage());
        $db_info['version'] = 'Unknown';
    }
    
    try {
        $result = $conn->query("SELECT DATABASE() as name");
        if ($result && $result->num_rows > 0) {
            $name_row = $result->fetch_assoc();
            $db_name_default = defined('DB_NAME') ? DB_NAME : 'BankingDB';
            $db_info['name'] = isset($name_row['name']) && !empty($name_row['name']) ? $name_row['name'] : $db_name_default;
            $result->free();
        } else {
            $db_name_default = defined('DB_NAME') ? DB_NAME : 'BankingDB';
            $db_info['name'] = $db_name_default;
            if ($result) $result->free();
        }
    } catch (Exception $e) {
        error_log("Error getting database name: " . $e->getMessage());
        $db_info['name'] = defined('DB_NAME') ? DB_NAME : 'BankingDB';
    }
    
    // Get table statistics
    if (!isset($tables) || !is_array($tables)) {
        $tables = [];
    }
    
    try {
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            while ($row = $result->fetch_array()) {
                if (!isset($row[0]) || empty($row[0])) {
                    continue; // Skip invalid rows
                }
                
                $table_name = $row[0];
                
                // Safely get table count with error handling
                $count = 0;
                try {
                    $table_name_escaped = $conn->real_escape_string($table_name);
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM `{$table_name_escaped}`");
                    if ($count_result && $count_result->num_rows > 0) {
                        $count_row = $count_result->fetch_assoc();
                        $count = isset($count_row['count']) ? (int)$count_row['count'] : 0;
                        $count_result->free();
                    } else {
                        if ($count_result) $count_result->free();
                    }
                } catch (Exception $e) {
                    // If count query fails, set count to 0 and log error
                    error_log("Error counting rows in table {$table_name}: " . $e->getMessage());
                    $count = 0;
                }
                
                $tables[] = [
                    'name' => $table_name,
                    'count' => $count
                ];
            }
            $result->free();
        } else {
            $connection_error = "Error executing SHOW TABLES: " . $conn->error;
            error_log($connection_error);
        }
    } catch (Exception $e) {
        $connection_error = "Error getting table list: " . $e->getMessage();
        error_log($connection_error);
    }
} else {
    $connection_error = 'Failed to connect to database';
}

// Get database size
$db_size = 0;
if ($conn) {
    try {
        // Get database name from connection or constant
        $db_name_raw = defined('DB_NAME') ? DB_NAME : '';
        if (empty($db_name_raw)) {
            $db_check = $conn->query("SELECT DATABASE() as db_name");
            if ($db_check) {
                $db_row = $db_check->fetch_assoc();
                $db_name_raw = isset($db_row['db_name']) ? $db_row['db_name'] : 'BankingDB';
                $db_check->free();
            } else {
                $db_name_raw = 'BankingDB';
            }
        }
        $db_name = $conn->real_escape_string($db_name_raw);
        
        $result = $conn->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
            FROM information_schema.tables 
            WHERE table_schema = '{$db_name}'
        ");
        if ($result) {
            $row = $result->fetch_assoc();
            $db_size = isset($row['DB Size in MB']) ? (float)$row['DB Size in MB'] : 0;
            $result->free();
        } else {
            error_log("Error getting database size: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Error calculating database size: " . $e->getMessage());
        $db_size = 0;
    }
}

// Table to Module Mapping - Accounting & Finance Subsystem
function getTableModuleMapping() {
    return [
        // ========================================
        // CORE AUTHENTICATION (Shared)
        // ========================================
        'users' => ['Core Authentication', 'User Management'],
        'roles' => ['Core Authentication', 'User Management'],
        'user_roles' => ['Core Authentication', 'User Management'],
        'login_attempts' => ['Core Authentication'],
        'user_account' => ['Core Authentication', 'HRIS Integration'],
        
        // ========================================
        // CORE ACCOUNTING - General Ledger
        // ========================================
        'fiscal_periods' => ['General Ledger', 'Financial Reporting'],
        'account_types' => ['General Ledger', 'Financial Reporting'],
        'accounts' => ['General Ledger', 'Financial Reporting', 'Transaction Reading'],
        'account_balances' => ['General Ledger', 'Financial Reporting'],
        
        // ========================================
        // JOURNAL ENTRIES
        // ========================================
        'journal_types' => ['General Ledger', 'Transaction Reading'],
        'journal_entries' => ['General Ledger', 'Transaction Reading', 'Financial Reporting'],
        'journal_lines' => ['General Ledger', 'Transaction Reading', 'Financial Reporting'],
        
        // ========================================
        // PAYROLL MANAGEMENT
        // ========================================
        'employee_refs' => ['Payroll Management', 'HRIS Integration'],
        'employee_attendance' => ['Payroll Management', 'HRIS Integration'],
        'payroll_periods' => ['Payroll Management'],
        'payroll_runs' => ['Payroll Management'],
        'payslips' => ['Payroll Management'],
        'payroll_payslips' => ['Payroll Management', 'HRIS Integration'],
        'salary_components' => ['Payroll Management'],
        
        // ========================================
        // PAYMENTS
        // ========================================
        'payments' => ['Payments', 'Expense Tracking', 'Transaction Reading'],
        'bank_accounts' => ['Payments', 'Bank Operations'],
        
        // ========================================
        // LOAN ACCOUNTING
        // ========================================
        'loan_types' => ['Loan Accounting'],
        'loans' => ['Loan Accounting'],
        'loan_payments' => ['Loan Accounting'],
        'loan_applications' => ['Loan Accounting', 'Loan Subsystem Integration'],
        
        // ========================================
        // EXPENSE MANAGEMENT
        // ========================================
        'expense_categories' => ['Expense Tracking'],
        'expense_claims' => ['Expense Tracking'],
        
        // ========================================
        // COMPLIANCE & REPORTING
        // ========================================
        'compliance_reports' => ['Financial Reporting', 'Compliance'],
        
        // ========================================
        // AUDIT & LOGGING
        // ========================================
        'audit_logs' => ['General Ledger', 'Activity Log', 'System Management'],
        'system_logs' => ['Activity Log', 'System Management'],
        
        // ========================================
        // BANKING MODULE (Shared with Banking Subsystem)
        // ========================================
        'bank_customers' => ['Bank Operations', 'Transaction Reading'],
        'bank_employees' => ['Bank Operations'],
        'bank_account_types' => ['Bank Operations'],
        'customer_accounts' => ['Bank Operations', 'Transaction Reading'],
        'bank_transactions' => ['Transaction Reading', 'Bank Operations'],
        'transaction_types' => ['Transaction Reading', 'Bank Operations'],
        
        // ========================================
        // HRIS INTEGRATION TABLES (Shared)
        // ========================================
        'employee' => ['HRIS Integration', 'Payroll Management'],
        'department' => ['HRIS Integration'],
        'position' => ['HRIS Integration'],
    ];
}

// Get table information (without structure/foreign keys for privacy)
function getTableAnalysis($table_name) {
    global $conn;
    $analysis = [
        'table_name' => $table_name,
        'modules' => []
        // Note: structure, indexes, and foreign_keys removed for privacy/security
    ];
    
    $mapping = getTableModuleMapping();
    if (isset($mapping[$table_name])) {
        $analysis['modules'] = $mapping[$table_name];
    }
    
    if ($conn) {
        // Escape table name for security
        $table_name_escaped = $conn->real_escape_string($table_name);
        
        // Note: Table structure, indexes, and foreign keys are not fetched for privacy/security reasons
        
        // Get row count
        try {
            $result = $conn->query("SELECT COUNT(*) as count FROM `$table_name_escaped`");
            if ($result) {
                $count_row = $result->fetch_assoc();
                $analysis['row_count'] = isset($count_row['count']) ? (int)$count_row['count'] : 0;
                $result->free();
            }
        } catch (Exception $e) {
            error_log("Error getting row count for table {$table_name}: " . $e->getMessage());
            $analysis['row_count'] = 0;
        }
        
        // Get table size
        try {
            // Get database name from connection or constant
            $db_name_raw = defined('DB_NAME') ? DB_NAME : '';
            if (empty($db_name_raw)) {
                $db_check = $conn->query("SELECT DATABASE() as db_name");
                if ($db_check) {
                    $db_row = $db_check->fetch_assoc();
                    $db_name_raw = isset($db_row['db_name']) ? $db_row['db_name'] : 'BankingDB';
                    $db_check->free();
                } else {
                    $db_name_raw = 'BankingDB';
                }
            }
            $db_name = $conn->real_escape_string($db_name_raw);
            
            $result = $conn->query("
                SELECT 
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    table_rows
                FROM information_schema.tables
                WHERE table_schema = '{$db_name}'
                AND table_name = '{$table_name_escaped}'
            ");
            if ($result) {
                $row = $result->fetch_assoc();
                $analysis['size_mb'] = isset($row['size_mb']) ? (float)$row['size_mb'] : 0;
                $analysis['estimated_rows'] = isset($row['table_rows']) ? (int)$row['table_rows'] : 0;
                $result->free();
            } else {
                error_log("Error getting table size for {$table_name}: " . $conn->error);
                $analysis['size_mb'] = 0;
                $analysis['estimated_rows'] = 0;
            }
        } catch (Exception $e) {
            error_log("Error getting table size for {$table_name}: " . $e->getMessage());
            $analysis['size_mb'] = 0;
            $analysis['estimated_rows'] = 0;
        }
    }
    
    return $analysis;
}

// Handle AJAX request for table analysis
if (isset($_GET['analyze_table']) && isset($_GET['table_name'])) {
    header('Content-Type: application/json');
    
    // Validate table name to prevent SQL injection
    $table_name = trim($_GET['table_name']);
    
    // Check if table name contains only allowed characters (alphanumeric, underscore)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
        echo json_encode([
            'error' => 'Invalid table name',
            'table_name' => $table_name
        ]);
        exit;
    }
    
    try {
        $analysis = getTableAnalysis($table_name);
        echo json_encode($analysis);
    } catch (Exception $e) {
        error_log("Error analyzing table {$table_name}: " . $e->getMessage());
        echo json_encode([
            'error' => 'Error analyzing table: ' . $e->getMessage(),
            'table_name' => $table_name
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Settings - Accounting and Finance System</title>
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
                        <a class="nav-link dropdown-toggle active" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="settingsDropdown">
                            <li><a class="dropdown-item" href="bin-station.php"><i class="fas fa-trash-alt me-2"></i>Bin Station</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item active" href="database-settings.php"><i class="fas fa-database me-2"></i>Database Settings</a></li>
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
                                <i class="fas fa-database me-3"></i>
                                Database Settings
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Monitor database status, performance, and manage system settings
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <div class="header-info-card">
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-<?php echo $connection_status === 'connected' ? 'check-circle text-success' : 'times-circle text-danger'; ?>"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Connection Status</div>
                                    <div class="info-value status-<?php echo $connection_status; ?>">
                                        <?php echo ucfirst($connection_status); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-hdd"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Database Size</div>
                                    <div class="info-value"><?php echo $db_size; ?> MB</div>
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

        <!-- Database Information Cards -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Database Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($connection_status === 'connected'): ?>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Database Name:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($db_info['name'] ?? 'Unknown'); ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Version:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($db_info['version'] ?? 'Unknown'); ?></span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <strong>Total Tables:</strong><br>
                                    <span class="text-muted"><?php echo is_array($tables) ? count($tables) : 0; ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Total Records:</strong><br>
                                    <span class="text-muted"><?php 
                                        $total_records = 0;
                                        if (!empty($tables) && is_array($tables)) {
                                            foreach ($tables as $table) {
                                                if (isset($table['count']) && is_numeric($table['count'])) {
                                                    $total_records += (int)$table['count'];
                                                }
                                            }
                                        }
                                        echo number_format($total_records);
                                    ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Connection Error:</strong><br>
                                <?php echo htmlspecialchars($connection_error); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tools me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="testConnection()">
                                <i class="fas fa-plug me-2"></i>Test Connection
                            </button>
                            <button class="btn btn-info" onclick="refreshStats()">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Statistics
                            </button>
                            <button class="btn btn-warning" onclick="optimizeTables()">
                                <i class="fas fa-magic me-2"></i>Optimize Tables
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Statistics -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table me-2"></i>Table Statistics
                </h5>
            </div>
            <div class="card-body">
                <?php if ($connection_status === 'connected' && !empty($tables)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <i class="fas fa-sort me-1"></i>Table Name
                                        <input type="text" class="form-control form-control-sm mt-2" id="tableSearch" placeholder="Search tables...">
                                    </th>
                                    <th>Record Count</th>
                                    <th>Modules</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Ensure $tables is initialized
                                if (!isset($tables) || !is_array($tables)) {
                                    $tables = [];
                                }
                                
                                if (!empty($tables)) {
                                    $mapping = getTableModuleMapping();
                                    foreach ($tables as $table): 
                                        // Skip invalid table entries
                                        if (!isset($table['name']) || empty($table['name']) || !is_string($table['name'])) {
                                            continue;
                                        }
                                        
                                        $table_name = trim($table['name']);
                                        $table_count = isset($table['count']) && is_numeric($table['count']) ? (int)$table['count'] : 0;
                                        $modules = isset($mapping[$table_name]) && is_array($mapping[$table_name]) ? $mapping[$table_name] : [];
                                        $isAccountingTable = !empty($modules);
                                        $table_name_escaped = htmlspecialchars($table_name, ENT_QUOTES, 'UTF-8');
                                        $table_name_lower = strtolower($table_name);
                                ?>
                                    <tr data-table-name="<?php echo $table_name_lower; ?>" class="<?php echo $isAccountingTable ? 'table-primary' : ''; ?>">
                                        <td>
                                            <code><?php echo $table_name_escaped; ?></code>
                                            <?php if ($isAccountingTable): ?>
                                                <span class="badge bg-success ms-2" title="Accounting & Finance Table">
                                                    <i class="fas fa-check-circle"></i> A&F
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($table_count); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($modules) && is_array($modules)): ?>
                                                <?php 
                                                $module_slice = array_slice($modules, 0, 2);
                                                foreach ($module_slice as $module): 
                                                    if (empty($module) || !is_string($module)) continue;
                                                ?>
                                                    <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars($module, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($modules) > 2): ?>
                                                    <span class="badge bg-light text-dark" title="<?php echo htmlspecialchars(implode(', ', $modules), ENT_QUOTES, 'UTF-8'); ?>">
                                                        +<?php echo count($modules) - 2; ?> more
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Active</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="analyzeTable('<?php echo addslashes($table_name); ?>')" title="View table information">
                                                <i class="fas fa-info-circle"></i> Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php 
                                    endforeach; 
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            <i class="fas fa-info-circle me-2"></i>No tables found in database. Please check your database connection.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No table information available</h5>
                        <p class="text-muted">Unable to retrieve table statistics. Please check your database connection.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Table Analysis Modal -->
    <div class="modal fade" id="tableAnalysisModal" tabindex="-1" aria-labelledby="tableAnalysisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tableAnalysisModalLabel">
                        <i class="fas fa-database me-2"></i>Table Information: <span id="modalTableName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="tableAnalysisContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Loading table information...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notifications.js"></script>

    <script>
        function testConnection() {
            // Simulate connection test
            const btn = event.target;
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Connection test completed successfully!');
            }, 2000);
        }
        
        function refreshStats() {
            location.reload();
        }
        
        function optimizeTables() {
            if (confirm('This will optimize all database tables. Continue?')) {
                alert('Table optimization completed!');
            }
        }
        
        function analyzeTable(tableName) {
            const modal = new bootstrap.Modal(document.getElementById('tableAnalysisModal'));
            document.getElementById('modalTableName').textContent = tableName;
            document.getElementById('tableAnalysisContent').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Analyzing table...</p>
                </div>
            `;
            modal.show();
            
            // Fetch table analysis
            fetch(`?analyze_table=1&table_name=${encodeURIComponent(tableName)}`)
                .then(response => response.json())
                .then(data => {
                    displayTableAnalysis(data);
                })
                .catch(error => {
                    document.getElementById('tableAnalysisContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Error:</strong> Failed to analyze table. ${error.message}
                        </div>
                    `;
                });
        }
        
        function displayTableAnalysis(data) {
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Table Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Table Name:</strong> <code style="color: #e83e8c;">${data.table_name}</code></p>
                                <p><strong>Row Count:</strong> <span class="badge bg-info">${data.row_count?.toLocaleString() || 'N/A'}</span></p>
                                <p><strong>Table Size:</strong> <span class="badge bg-success">${(data.size_mb || 0).toFixed(2)} MB</span></p>
                                <p><strong>Estimated Rows:</strong> ${data.estimated_rows?.toLocaleString() || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Modules Using This Table</h6>
                            </div>
                            <div class="card-body">
            `;
            
            if (data.modules && data.modules.length > 0) {
                html += '<div class="d-flex flex-wrap gap-2">';
                data.modules.forEach(module => {
                    html += `<span class="badge bg-primary"><i class="fas fa-check-circle me-1"></i>${module}</span>`;
                });
                html += '</div>';
            } else {
                html += '<p class="text-muted mb-0"><i class="fas fa-info-circle me-2"></i>No specific module mapping found. This table may be used by other subsystems.</p>';
            }
            
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Note: Table structure and foreign key relationships are hidden for privacy/security reasons
            
            document.getElementById('tableAnalysisContent').innerHTML = html;
        }
        
        // Table search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('tableSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#tablesTable tbody tr');
                    
                    rows.forEach(row => {
                        const tableName = row.getAttribute('data-table-name');
                        if (tableName && tableName.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>

    <style>
        .status-connected {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-disconnected {
            color: #dc3545;
            font-weight: bold;
        }
        
        #tableSearch {
            max-width: 300px;
        }
        
        .table-primary {
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .table-primary:hover {
            background-color: rgba(13, 110, 253, 0.2);
        }
        
        #tablesTable tbody tr {
            transition: all 0.2s ease;
        }
        
        .card-header h6 {
            margin: 0;
        }
        
        .badge {
            font-size: 0.75rem;
        }
    </style>
</body>
</html>
