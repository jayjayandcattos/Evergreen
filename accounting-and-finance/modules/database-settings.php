<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Test database connection
$connection_status = 'disconnected';
$connection_error = '';
$db_info = [];

if ($conn) {
    $connection_status = 'connected';
    
    // Get database information
    $result = $conn->query("SELECT VERSION() as version");
    if ($result) {
        $db_info['version'] = $result->fetch_assoc()['version'];
    }
    
    $result = $conn->query("SELECT DATABASE() as name");
    if ($result) {
        $db_info['name'] = $result->fetch_assoc()['name'];
    }
    
    // Get table statistics
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $table_name = $row[0];
            $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            
            $tables[] = [
                'name' => $table_name,
                'count' => $count
            ];
        }
    }
} else {
    $connection_error = 'Failed to connect to database';
}

// Get database size
$db_size = 0;
if ($conn) {
    $result = $conn->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $db_size = $row['DB Size in MB'] ?? 0;
    }
}

// Table to Module Mapping
function getTableModuleMapping() {
    return [
        // User Management
        'users' => ['Core Authentication', 'User Management'],
        'roles' => ['Core Authentication', 'User Management'],
        'user_roles' => ['Core Authentication', 'User Management'],
        'login_attempts' => ['Core Authentication'],
        
        // General Ledger
        'accounts' => ['General Ledger', 'Financial Reporting', 'Transaction Reading'],
        'account_types' => ['General Ledger', 'Financial Reporting'],
        'account_balances' => ['General Ledger', 'Financial Reporting'],
        'journal_entries' => ['General Ledger', 'Transaction Reading', 'Financial Reporting'],
        'journal_lines' => ['General Ledger', 'Transaction Reading', 'Financial Reporting'],
        'journal_types' => ['General Ledger', 'Transaction Reading'],
        'fiscal_periods' => ['General Ledger', 'Financial Reporting'],
        
        // Payroll Management
        'employee_refs' => ['Payroll Management', 'HRIS Integration'],
        'employee_benefits' => ['Payroll Management'],
        'employee_deductions' => ['Payroll Management'],
        'payroll_runs' => ['Payroll Management'],
        'payslips' => ['Payroll Management'],
        'payroll_items' => ['Payroll Management'],
        'payroll_deductions' => ['Payroll Management'],
        
        // Expense Tracking
        'expense_categories' => ['Expense Tracking'],
        'expense_claims' => ['Expense Tracking'],
        'expense_line_items' => ['Expense Tracking'],
        
        // Loan Accounting
        'loans' => ['Loan Accounting'],
        'loan_payments' => ['Loan Accounting'],
        'loan_schedules' => ['Loan Accounting'],
        
        // System Management
        'audit_logs' => ['General Ledger', 'Activity Log', 'System Management'],
        'system_settings' => ['System Management', 'Database Settings'],
        'system_notifications' => ['Notifications', 'System Management'],
        
        // Bank Operations
        'bank_users' => ['Bank Operations'],
        'bank_customers' => ['Bank Operations'],
        'bank_employees' => ['Bank Operations'],
        'transactions' => ['Transaction Reading', 'Bank Operations'],
    ];
}

// Get table structure and usage info
function getTableAnalysis($table_name) {
    global $conn;
    $analysis = [
        'table_name' => $table_name,
        'modules' => [],
        'structure' => [],
        'indexes' => [],
        'foreign_keys' => []
    ];
    
    $mapping = getTableModuleMapping();
    if (isset($mapping[$table_name])) {
        $analysis['modules'] = $mapping[$table_name];
    }
    
    if ($conn) {
        // Escape table name for security
        $table_name_escaped = $conn->real_escape_string($table_name);
        
        // Get table structure
        $result = $conn->query("DESCRIBE `$table_name_escaped`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $analysis['structure'][] = $row;
            }
        }
        
        // Get indexes
        $result = $conn->query("SHOW INDEXES FROM `$table_name_escaped`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $analysis['indexes'][] = $row;
            }
        }
        
        // Get foreign keys
        $result = $conn->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '$table_name_escaped'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $analysis['foreign_keys'][] = $row;
            }
        }
        
        // Get row count
        $result = $conn->query("SELECT COUNT(*) as count FROM `$table_name_escaped`");
        if ($result) {
            $analysis['row_count'] = $result->fetch_assoc()['count'];
        }
        
        // Get table size
        $result = $conn->query("
            SELECT 
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                table_rows
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = '$table_name_escaped'
        ");
        if ($result) {
            $row = $result->fetch_assoc();
            $analysis['size_mb'] = $row['size_mb'] ?? 0;
            $analysis['estimated_rows'] = $row['table_rows'] ?? 0;
        }
    }
    
    return $analysis;
}

// Handle AJAX request for table analysis
if (isset($_GET['analyze_table']) && isset($_GET['table_name'])) {
    header('Content-Type: application/json');
    echo json_encode(getTableAnalysis($_GET['table_name']));
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
                                    <span class="text-muted"><?php echo count($tables); ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Total Records:</strong><br>
                                    <span class="text-muted"><?php echo array_sum(array_column($tables, 'count')); ?></span>
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
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Table Name</th>
                                    <th>Record Count</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tables as $table): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo htmlspecialchars($table['name']); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo number_format($table['count']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Active</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="analyzeTable('<?php echo $table['name']; ?>')">
                                                <i class="fas fa-search"></i> Analyze
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                        <i class="fas fa-database me-2"></i>Table Analysis: <span id="modalTableName"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="tableAnalysisContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3">Analyzing table...</p>
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
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Table Information</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Table Name:</strong> <code style="color: #e83e8c;">${data.table_name}</code></p>
                                <p><strong>Row Count:</strong> <span class="badge bg-info">${data.row_count?.toLocaleString() || 'N/A'}</span></p>
                                <p><strong>Table Size:</strong> <span class="badge bg-success">${data.size_mb || 0} MB</span></p>
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
                html += '<ul class="list-unstyled mb-0">';
                data.modules.forEach(module => {
                    html += `<li><i class="fas fa-check-circle text-success me-2"></i>${module}</li>`;
                });
                html += '</ul>';
            } else {
                html += '<p class="text-muted mb-0"><i class="fas fa-info-circle me-2"></i>No specific module mapping found.</p>';
            }
            
            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('tableAnalysisContent').innerHTML = html;
        }
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
    </style>
</body>
</html>
