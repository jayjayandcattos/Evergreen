<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/auth.php';

// Restrict access to Admin only
if (!canViewLogs()) {
    header('Location: dashboard.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_level = $_GET['log_level'] ?? '';
$log_type = $_GET['log_type'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "SELECT * FROM system_logs WHERE 1=1";
$params = [];

if ($log_level) {
    $sql .= " AND log_level = ?";
    $params[] = $log_level;
}

if ($log_type) {
    $sql .= " AND log_type LIKE ?";
    $params[] = "%$log_type%";
}

if ($search) {
    $sql .= " AND (action LIKE ? OR details LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($date_from) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY created_at DESC LIMIT 500";

try {
    if (function_exists('fetchAll')) {
        $logs = fetchAll($conn, $sql, $params);
    } else {
        if (empty($params)) {
            $stmt = $conn->query($sql);
            $logs = $stmt->fetch_all(MYSQLI_ASSOC);
        } else {
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
} catch (Exception $e) {
    $logs = [];
}

try {
    $log_levels = fetchAll($conn, "SELECT DISTINCT log_level FROM system_logs WHERE log_level IS NOT NULL ORDER BY log_level", []);
    $log_types = fetchAll($conn, "SELECT DISTINCT log_type FROM system_logs WHERE log_type IS NOT NULL ORDER BY log_type", []);
} catch (Exception $e) {
    $log_levels = [];
    $log_types = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../assets/evergreen.svg">
    <title>HRIS - System Logs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2f1 50%, #f8fafc 100%);
            background-attachment: fixed;
        }

        .header-gradient {
            background: linear-gradient(135deg, #003631 0%, #004d45 50%, #002b27 100%);
            position: relative;
            overflow: hidden;
        }

        .header-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(236, 72, 153, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(14, 165, 233, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .header-gradient > * {
            position: relative;
            z-index: 1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeInModal 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 0;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-30px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .card-enhanced {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-enhanced:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="min-h-screen lg:ml-64">
        <header class="header-gradient text-white p-4 lg:p-6 shadow-xl no-print">
            <div class="flex items-center justify-between pl-14 lg:pl-0">
                <?php include '../includes/sidebar.php'; ?>
                <h1 class="text-lg sm:text-xl lg:text-2xl font-bold tracking-tight">
                    <i class="fas fa-file-alt mr-2"></i>System Logs
                </h1>
                <button onclick="openLogoutModal()" class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </header>

        <main class="p-3 sm:p-4 lg:p-8 print-content">
            <div class="card-enhanced p-4 lg:p-6 mb-4 lg:mb-6 no-print">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Filter Logs</h2>
                    <button onclick="window.print()" class="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 rounded-lg font-medium text-sm flex items-center gap-2 shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                </div>
                
                <form method="GET" id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Log Level</label>
                        <select name="log_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                            <option value="">All Levels</option>
                            <?php foreach ($log_levels as $level): ?>
                                <option value="<?php echo htmlspecialchars($level['log_level']); ?>" <?php echo $log_level === $level['log_level'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($level['log_level']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Log Type</label>
                        <select name="log_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                            <option value="">All Types</option>
                            <?php foreach ($log_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['log_type']); ?>" <?php echo $log_type === $type['log_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['log_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Search</label>
                        <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search action or details"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium text-sm">
                            Apply Filters
                        </button>
                        <button type="button" onclick="clearFilters()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium text-sm">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <div class="hidden print:block mb-6">
                <h1 class="text-2xl font-bold text-gray-800 text-center">System Logs Report</h1>
                <p class="text-center text-gray-600 text-sm mt-2">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                <?php if ($date_from || $date_to): ?>
                    <p class="text-center text-gray-600 text-sm">
                        Period: <?php echo $date_from ? date('M d, Y', strtotime($date_from)) : 'Start'; ?> - <?php echo $date_to ? date('M d, Y', strtotime($date_to)) : 'End'; ?>
                    </p>
                <?php endif; ?>
                <hr class="my-4">
            </div>

            <div class="card-enhanced p-4 lg:p-6">
                <div class="mb-4 flex justify-between items-center no-print">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Log Entries <span class="text-sm text-gray-500">(Showing <?php echo count($logs); ?> entries)</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="desktop-table w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">ID</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Level</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">User</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Action</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Details</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">IP Address</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8" class="px-3 py-8 text-center text-gray-500">No logs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo $log['log_id'] ?? 'N/A'; ?></td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-1 rounded text-xs font-semibold log-level-<?php echo htmlspecialchars($log['log_level'] ?? 'INFO'); ?>">
                                                <?php echo htmlspecialchars($log['log_level'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($log['log_type'] ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($log['user_account'] ?? 'System'); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($log['action'] ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2 max-w-xs truncate" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($log['details'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mobile-card space-y-3">
                    <?php if (empty($logs)): ?>
                        <div class="text-center text-gray-500 py-8">No logs found</div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="px-2 py-1 rounded text-xs font-semibold log-level-<?php echo htmlspecialchars($log['log_level'] ?? 'INFO'); ?>">
                                            <?php echo htmlspecialchars($log['log_level'] ?? 'N/A'); ?>
                                        </span>
                                        <p class="text-xs text-gray-500 mt-1">ID: <?php echo $log['log_id'] ?? 'N/A'; ?></p>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div><span class="font-medium text-gray-700">Type:</span> <?php echo htmlspecialchars($log['log_type'] ?? 'N/A'); ?></div>
                                    <div><span class="font-medium text-gray-700">User:</span> <?php echo htmlspecialchars($log['user_account'] ?? 'System'); ?></div>
                                    <div><span class="font-medium text-gray-700">Action:</span> <?php echo htmlspecialchars($log['action'] ?? 'N/A'); ?></div>
                                    <div><span class="font-medium text-gray-700">Details:</span> <?php echo htmlspecialchars($log['details'] ?? 'N/A'); ?></div>
                                    <div><span class="font-medium text-gray-700">IP:</span> <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="logoutModal" class="modal no-print">
        <div class="modal-content max-w-md w-full mx-4">
            <div class="bg-red-600 text-white p-4 rounded-t-lg">
                <h2 class="text-xl font-bold">Confirm Logout</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6">Are you sure you want to logout?</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeLogoutModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <a href="../logout.php" 
                       class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const value = this.value;
                        if (value.length >= 3 || value.length === 0) {
                            document.getElementById('filterForm').submit();
                        }
                    }, 500);
                });
            }
        });

        function clearFilters() {
            window.location.href = 'logs.php';
        }

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
    </script>
</body>

</html>