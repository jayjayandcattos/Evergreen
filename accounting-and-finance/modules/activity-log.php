<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();
$current_user = getCurrentUser();

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? '';

// Build query to fetch activity logs
$sql = "SELECT 
            al.id,
            al.user_id,
            al.action,
            al.module,
            al.details,
            al.ip_address,
            al.created_at,
            u.full_name as user_name,
            u.username
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";

$params = [];
$types = '';

// Apply filters
if (!empty($date_from)) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($module)) {
    $sql .= " AND al.module = ?";
    $params[] = $module;
    $types .= 's';
}

if (!empty($action)) {
    $sql .= " AND al.action = ?";
    $params[] = $action;
    $types .= 's';
}

if (!empty($user_id)) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_id;
    $types .= 'i';
}

$sql .= " ORDER BY al.created_at DESC LIMIT 1000";

// Execute query
$activity_logs = [];
$hasResults = false;

if ($conn) {
    $stmt = $conn->prepare($sql);
    
    if ($stmt !== false) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $activity_logs[] = $row;
        }
        
        $hasResults = count($activity_logs) > 0;
        $stmt->close();
    }
}

// Get unique modules and actions for filter dropdowns
$modules = [];
$actions = [];
$users = [];

if ($conn) {
    // Get modules
    $module_sql = "SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL ORDER BY module";
    $result = $conn->query($module_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row['module'];
        }
    }
    
    // Get actions
    $action_sql = "SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL ORDER BY action";
    $result = $conn->query($action_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $actions[] = $row['action'];
        }
    }
    
    // Get users
    $user_sql = "SELECT DISTINCT u.id, u.full_name, u.username 
                 FROM activity_logs al 
                 LEFT JOIN users u ON al.user_id = u.id 
                 WHERE u.id IS NOT NULL 
                 ORDER BY u.full_name";
    $result = $conn->query($user_sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Accounting and Finance System</title>
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
                        <li><a class="dropdown-item active" href="activity-log.php"><i class="fas fa-history me-2"></i>Activity Log</a></li>
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
                                <i class="fas fa-history me-3"></i>
                                Activity Log
                            </h1>
                            <p class="page-subtitle-beautiful">
                                Track user activities and system events across all modules
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
                                    <div class="info-label">Total Activities</div>
                                    <div class="info-value"><?php echo count($activity_logs); ?></div>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Last Activity</div>
                                    <div class="info-value"><?php echo !empty($activity_logs) ? date('M d, Y', strtotime($activity_logs[0]['created_at'])) : 'N/A'; ?></div>
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

        <!-- Filtering Section -->
        <div class="filtering-section-modern">
            <div class="section-header-simple mb-4">
                <h2 class="section-title-simple">
                    <i class="fas fa-filter me-2" style="color: var(--primary-teal);"></i>Filter Activities
                </h2>
                <p class="section-subtitle-simple">Filter and search activity logs by various criteria</p>
            </div>
            <div class="filtering-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="module" class="form-label">Module</label>
                        <select class="form-select" id="module" name="module">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?php echo htmlspecialchars($mod); ?>" <?php echo $module === $mod ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($mod)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="action" class="form-label">Action</label>
                        <select class="form-select" id="action" name="action">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $act): ?>
                                <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action === $act ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($act)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-lg me-3 px-4">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <a href="activity-log.php" class="btn btn-outline-secondary btn-lg px-3">
                                <i class="fas fa-times"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activity Log Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Activity Logs
                    <span class="badge bg-primary ms-2"><?php echo count($activity_logs); ?> records</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($hasResults): ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="activityTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted small">
                                                <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($log['user_name'] ?? 'Unknown'); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['username'] ?? ''); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars(ucfirst($log['module'] ?? 'Unknown')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo htmlspecialchars(ucfirst($log['action'] ?? 'Unknown')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                <?php echo htmlspecialchars($log['details'] ?? 'No details'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code class="small"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No activity logs found</h5>
                        <p class="text-muted">Try adjusting your filter criteria or check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/notifications.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#activityTable').DataTable({
                "pageLength": 25,
                "order": [[ 0, "desc" ]],
                "columnDefs": [
                    { "orderable": false, "targets": [4, 5] }
                ],
                "language": {
                    "search": "Search activities:",
                    "lengthMenu": "Show _MENU_ activities per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ activities",
                    "infoEmpty": "No activities found",
                    "infoFiltered": "(filtered from _MAX_ total activities)"
                }
            });
        });
    </script>
</body>
</html>
