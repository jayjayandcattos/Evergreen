<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';
require_once '../includes/auth.php';

$message = '';
$messageType = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!canManageLeaves()) {
                    header('Location: leave.php');
                    exit;
                }
                try {
                    $employee_id = $_POST['employee_id'] ?? '';
                    $leave_type_id = $_POST['leave_type_id'] ?? '';
                    $start_date = $_POST['start_date'] ?? '';
                    $end_date = $_POST['end_date'] ?? '';
                    $reason = $_POST['reason'] ?? '';

                    // Validate inputs
                    if (empty($employee_id) || empty($leave_type_id) || empty($start_date) || empty($end_date)) {
                        throw new Exception("All fields are required");
                    }

                    // Validate dates
                    if (strtotime($start_date) > strtotime($end_date)) {
                        throw new Exception("End date must be after start date");
                    }

                    // Calculate total days (inclusive of start and end dates)
                    $start = new DateTime($start_date);
                    $end = new DateTime($end_date);
                    $interval = $start->diff($end);
                    $total_days = $interval->days + 1; // +1 to include both start and end dates

                    // Check if employee exists
                    $empCheck = fetchOne($conn, "SELECT employee_id FROM employee WHERE employee_id = ?", [$employee_id]);
                    if (!$empCheck) {
                        throw new Exception("Employee not found");
                    }

                    // Insert leave request
                    $sql = "INSERT INTO leave_request (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, date_requested) 
                            VALUES (?, ?, ?, ?, ?, ?, 'Pending', CURDATE())";

                    $stmt = $conn->prepare($sql);
                    $success = $stmt->execute([
                        $employee_id,
                        $leave_type_id,
                        $start_date,
                        $end_date,
                        $total_days,
                        $reason
                    ]);

                    if ($success) {
                        if (isset($logger)) {
                            $logger->info(
                                'LEAVE',
                                'Leave request created',
                                "Employee ID: $employee_id, Days: $total_days"
                            );
                        }
                        $success_message = "Leave request submitted successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to submit leave request");
                    }
                } catch (Exception $e) {
                    if (isset($logger)) {
                        $logger->error('LEAVE', 'Failed to create leave request', $e->getMessage());
                    }
                    $message = "Error: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

            case 'approve':
                if (!canManageLeaves()) {
                    header('Location: leave.php');
                    exit;
                }
                try {
                    $leave_request_id = $_POST['leave_request_id'] ?? '';
                    $approver_id = $_SESSION['user_id'] ?? null;

                    if (empty($leave_request_id)) {
                        throw new Exception("Leave request ID is required");
                    }

                    $sql = "UPDATE leave_request 
                            SET status = 'Approved', 
                                approver_id = ?, 
                                date_approved = CURDATE() 
                            WHERE leave_request_id = ?";

                    $stmt = $conn->prepare($sql);
                    $success = $stmt->execute([$approver_id, $leave_request_id]);

                    if ($success) {
                        if (isset($logger)) {
                            $logger->info('LEAVE', 'Leave request approved', "Leave Request ID: $leave_request_id");
                        }
                        $success_message = "Leave request approved successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to approve leave request");
                    }
                } catch (Exception $e) {
                    if (isset($logger)) {
                        $logger->error('LEAVE', 'Failed to approve leave request', $e->getMessage());
                    }
                    $message = "Error: " . $e->getMessage();
                    $messageType = "error";
                }
                break;

            case 'reject':
                if (!canManageLeaves()) {
                    header('Location: leave.php');
                    exit;
                }
                try {
                    $leave_request_id = $_POST['leave_request_id'] ?? '';
                    $approver_id = $_SESSION['user_id'] ?? null;

                    if (empty($leave_request_id)) {
                        throw new Exception("Leave request ID is required");
                    }

                    $sql = "UPDATE leave_request 
                            SET status = 'Rejected', 
                                approver_id = ?, 
                                date_approved = CURDATE() 
                            WHERE leave_request_id = ?";

                    $stmt = $conn->prepare($sql);
                    $success = $stmt->execute([$approver_id, $leave_request_id]);

                    if ($success) {
                        if (isset($logger)) {
                            $logger->info('LEAVE', 'Leave request rejected', "Leave Request ID: $leave_request_id");
                        }
                        $success_message = "Leave request rejected successfully!";
                        $messageType = "success";
                    } else {
                        throw new Exception("Failed to reject leave request");
                    }
                } catch (Exception $e) {
                    if (isset($logger)) {
                        $logger->error('LEAVE', 'Failed to reject leave request', $e->getMessage());
                    }
                    $message = "Error: " . $e->getMessage();
                    $messageType = "error";
                }
                break;
        }
    } else {
        // Handle old form submission (without action parameter)
        try {
    $employee_id = $_POST['employee_id'] ?? '';
            $leave_type_name = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

            if (empty($employee_id) || empty($leave_type_name) || empty($start_date) || empty($end_date)) {
                throw new Exception("All fields are required");
            }

            // Find leave_type_id by name
            $leave_type = fetchOne($conn, "SELECT leave_type_id FROM leave_type WHERE leave_name = ?", [$leave_type_name]);
            if (!$leave_type) {
                throw new Exception("Leave type not found");
            }
            $leave_type_id = $leave_type['leave_type_id'];

            // Calculate total days
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $total_days = $interval->days + 1;

            // Check if employee exists
            $empCheck = fetchOne($conn, "SELECT employee_id FROM employee WHERE employee_id = ?", [$employee_id]);
            if (!$empCheck) {
                throw new Exception("Employee not found");
            }

            // Insert leave request
            $sql = "INSERT INTO leave_request (employee_id, leave_type_id, start_date, end_date, total_days, reason, status, date_requested) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending', CURDATE())";

            $stmt = $conn->prepare($sql);
            $success = $stmt->execute([
                $employee_id,
                $leave_type_id,
                $start_date,
                $end_date,
                $total_days,
                $reason
            ]);

            if ($success) {
                if (isset($logger)) {
                    $logger->info('LEAVE', 'Leave request created', "Employee ID: $employee_id");
                }
    $success_message = "Leave request submitted successfully!";
                $messageType = "success";
            } else {
                throw new Exception("Failed to submit leave request");
            }
        } catch (Exception $e) {
            if (isset($logger)) {
                $logger->error('LEAVE', 'Failed to create leave request', $e->getMessage());
            }
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Fetch leave requests from database
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter'] ?? ''; // 'pending', 'approved_month', 'rejected_month', 'total_month'

$sql = "SELECT lr.*, 
        e.first_name, e.last_name, e.employee_id,
        lt.leave_name,
        u.username as approver_name
        FROM leave_request lr
        INNER JOIN employee e ON lr.employee_id = e.employee_id
        LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
        LEFT JOIN user_account u ON lr.approver_id = u.user_id
        WHERE 1=1";

$params = [];

// Handle filter_type from card clicks
if ($filter_type) {
    switch ($filter_type) {
        case 'pending':
            $sql .= " AND lr.status = 'Pending'";
            break;
        case 'approved_month':
            $sql .= " AND lr.status = 'Approved' AND MONTH(lr.date_approved) = MONTH(CURDATE()) AND YEAR(lr.date_approved) = YEAR(CURDATE())";
            break;
        case 'rejected_month':
            $sql .= " AND lr.status = 'Rejected' AND MONTH(lr.date_approved) = MONTH(CURDATE()) AND YEAR(lr.date_approved) = YEAR(CURDATE())";
            break;
        case 'total_month':
            $sql .= " AND MONTH(lr.date_requested) = MONTH(CURDATE()) AND YEAR(lr.date_requested) = YEAR(CURDATE())";
            break;
    }
} elseif ($status_filter) {
    $sql .= " AND lr.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR lt.leave_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY lr.date_requested DESC, lr.leave_request_id DESC";

try {
    $leave_requests = fetchAll($conn, $sql, $params);
} catch (Exception $e) {
    $leave_requests = [];
    if (isset($logger)) {
        $logger->error('LEAVE', 'Failed to fetch leave requests', $e->getMessage());
    }
}

// Calculate statistics
try {
    $stats = [
        'pending' => (int)fetchOne($conn, "SELECT COUNT(*) as total FROM leave_request WHERE status = 'Pending'")['total'],
        'approved_this_month' => (int)fetchOne($conn, "SELECT COUNT(*) as total FROM leave_request WHERE status = 'Approved' AND MONTH(date_approved) = MONTH(CURDATE()) AND YEAR(date_approved) = YEAR(CURDATE())")['total'],
        'rejected_this_month' => (int)fetchOne($conn, "SELECT COUNT(*) as total FROM leave_request WHERE status = 'Rejected' AND MONTH(date_approved) = MONTH(CURDATE()) AND YEAR(date_approved) = YEAR(CURDATE())")['total'],
        'total_this_month' => (int)fetchOne($conn, "SELECT COUNT(*) as total FROM leave_request WHERE MONTH(date_requested) = MONTH(CURDATE()) AND YEAR(date_requested) = YEAR(CURDATE())")['total']
    ];
} catch (Exception $e) {
    $stats = [
        'pending' => 0,
        'approved_this_month' => 0,
        'rejected_this_month' => 0,
        'total_this_month' => 0
    ];
}

// Fetch leave types for dropdown
try {
    $leave_types = fetchAll($conn, "SELECT * FROM leave_type ORDER BY leave_name");
} catch (Exception $e) {
    $leave_types = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../assets/evergreen.svg">
    <title>HRIS - Leave Management</title>
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
        @media (max-width: 768px) {
            .mobile-card {
                display: block;
            }

            .desktop-table {
                display: none;
            }
        }

        @media (min-width: 769px) {
            .mobile-card {
                display: none;
            }

            .desktop-table {
                display: table;
            }
        }

        .stat-card {
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            background: rgba(255, 255, 255, 0.3);
        }

        .stat-card.pending-card {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 50%, #f59e0b 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .stat-card.approved-card {
            background: linear-gradient(135deg, #10b981 0%, #34d399 50%, #10b981 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .stat-card.rejected-card {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 50%, #ef4444 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .stat-card.total-card {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #0d9488 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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
        <header class="header-gradient text-white p-4 lg:p-6 shadow-xl">
            <div class="flex items-center justify-between pl-14 lg:pl-0">
                <?php include '../includes/sidebar.php'; ?>
                <h1 class="text-lg sm:text-xl lg:text-2xl font-bold tracking-tight">
                    <i class="fas fa-calendar-check mr-2"></i>Leave Management
                </h1>
                <button onclick="openLogoutModal()"
                    class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-3 sm:p-4 lg:p-8">
            <?php if ($success_message): ?>
                <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-800">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message && $messageType === 'error'): ?>
                <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-800">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 lg:mb-6">
                <div onclick="filterByCard('pending')" 
                     class="stat-card pending-card text-white rounded-xl shadow-xl p-5 lg:p-6 <?php echo $filter_type === 'pending' ? 'ring-4 ring-yellow-300' : ''; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-semibold mb-2">Pending Requests</h3>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold"><?php echo $stats['pending']; ?></p>
                </div>

                <div onclick="filterByCard('approved_month')" 
                     class="stat-card approved-card text-white rounded-xl shadow-xl p-5 lg:p-6 <?php echo $filter_type === 'approved_month' ? 'ring-4 ring-green-300' : ''; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-semibold mb-2">Approved This Month</h3>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold"><?php echo $stats['approved_this_month']; ?></p>
                </div>

                <div onclick="filterByCard('rejected_month')" 
                     class="stat-card rejected-card text-white rounded-xl shadow-xl p-5 lg:p-6 <?php echo $filter_type === 'rejected_month' ? 'ring-4 ring-red-300' : ''; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-semibold mb-2">Rejected This Month</h3>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold"><?php echo $stats['rejected_this_month']; ?></p>
                </div>

                <div onclick="filterByCard('total_month')" 
                     class="stat-card total-card text-white rounded-xl shadow-xl p-5 lg:p-6 <?php echo $filter_type === 'total_month' ? 'ring-4 ring-teal-300' : ''; ?>">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <h3 class="text-xs sm:text-sm font-semibold mb-2">Total This Month</h3>
                    <p class="text-2xl sm:text-3xl lg:text-4xl font-bold"><?php echo $stats['total_this_month']; ?></p>
                </div>
            </div>

            <!-- Action Buttons and Filters -->
            <div class="card-enhanced p-4 lg:p-6 mb-4 lg:mb-6">
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php if (canManageLeaves()): ?>
                    <button
                        onclick="openModal()"
                        class="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap shadow-md hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-plus mr-2"></i>New Leave Request
                    </button>
                    <?php endif; ?>
                    <?php if ($filter_type): ?>
                        <a href="leave.php" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium text-sm">
                            Clear Filter
                        </a>
                    <?php else: ?>
                        <a href="?status=" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium text-sm <?php echo !$status_filter ? 'bg-teal-700 text-white hover:bg-teal-800' : ''; ?>">
                            All
                        </a>
                        <a href="?status=Pending" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium text-sm <?php echo $status_filter === 'Pending' ? 'bg-teal-700 text-white hover:bg-teal-800' : ''; ?>">
                            Pending
                        </a>
                        <a href="?status=Approved" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium text-sm <?php echo $status_filter === 'Approved' ? 'bg-teal-700 text-white hover:bg-teal-800' : ''; ?>">
                            Approved
                        </a>
                        <a href="?status=Rejected" class="bg-gray-200 text-gray-700 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium text-sm <?php echo $status_filter === 'Rejected' ? 'bg-teal-700 text-white hover:bg-teal-800' : ''; ?>">
                            Rejected
                        </a>
                    <?php endif; ?>
                </div>

                <form method="GET" id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php if ($filter_type): ?>
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter_type); ?>">
                    <?php else: ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search Employee or Leave Type"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 text-sm">
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium text-sm shadow-md hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                        <button type="button" onclick="clearFilters()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium text-sm shadow-md hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Leave Requests Table -->
            <div class="card-enhanced p-4 lg:p-6">
                <div class="overflow-x-auto">
                    <table class="desktop-table w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Employee</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Leave Type</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Duration</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Days</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Reason</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leave_requests)): ?>
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">No leave requests found</td>
                                </tr>
                            <?php else: ?>
                            <?php foreach ($leave_requests as $request): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-3 py-2 text-sm">
                                            <div class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars(trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''))); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($request['employee_id'] ?? 'N/A'); ?></div>
                                    </td>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($request['leave_name'] ?? 'N/A'); ?>
                                    </td>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <?php echo date('M d', strtotime($request['start_date'])); ?> - <?php echo date('M d, Y', strtotime($request['end_date'])); ?>
                                    </td>
                                        <td class="px-3 py-2 text-sm text-gray-900">
                                            <?php echo $request['total_days'] ?? 0; ?> day<?php echo ($request['total_days'] ?? 0) > 1 ? 's' : ''; ?>
                                    </td>
                                        <td class="px-3 py-2 text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($request['reason'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?>
                                    </td>
                                        <td class="px-3 py-2">
                                        <?php
                                            $status = $request['status'] ?? 'Pending';
                                        $status_color = '';
                                            switch ($status) {
                                            case 'Pending':
                                                $status_color = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Approved':
                                                $status_color = 'bg-green-100 text-green-800';
                                                break;
                                            case 'Rejected':
                                                $status_color = 'bg-red-100 text-red-800';
                                                break;
                                                default:
                                                    $status_color = 'bg-gray-100 text-gray-800';
                                        }
                                        ?>
                                        <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                            <?php 
                                            $status_icon = '';
                                            switch ($status) {
                                                case 'Pending':
                                                    $status_icon = '<i class="fas fa-clock mr-1.5"></i>';
                                                    break;
                                                case 'Approved':
                                                    $status_icon = '<i class="fas fa-check-circle mr-1.5"></i>';
                                                    break;
                                                case 'Rejected':
                                                    $status_icon = '<i class="fas fa-times-circle mr-1.5"></i>';
                                                    break;
                                            }
                                            echo $status_icon;
                                            ?>
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                        <td class="px-3 py-2">
                                            <?php if ($status === 'Pending'): ?>
                                                <div class="flex gap-2">
                                                    <button type="button" onclick="showConfirmApprove(<?php echo $request['leave_request_id']; ?>)" 
                                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium shadow-sm hover:shadow-md transition-all duration-200">
                                                        <i class="fas fa-check mr-1"></i>Approve
                                                    </button>
                                                    <button type="button" onclick="showConfirmReject(<?php echo $request['leave_request_id']; ?>)" 
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium shadow-sm hover:shadow-md transition-all duration-200">
                                                        <i class="fas fa-times mr-1"></i>Reject
                                                    </button>
                                                </div>
                                        <?php else: ?>
                                                <div class="text-gray-600 text-xs">
                                                    <?php if ($request['approver_name']): ?>
                                                        <div class="font-medium mb-1">
                                                            <i class="fas fa-user-check mr-1"></i>By: <?php echo htmlspecialchars($request['approver_name']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($request['date_approved']): ?>
                                                        <div>
                                                            <i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($request['date_approved'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-card space-y-3">
                    <?php if (empty($leave_requests)): ?>
                        <div class="text-center text-gray-500 py-8">No leave requests found</div>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $request): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            <?php echo htmlspecialchars(trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''))); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500">ID: <?php echo htmlspecialchars($request['employee_id'] ?? 'N/A'); ?></p>
                                    </div>
                                    <?php
                                    $status = $request['status'] ?? 'Pending';
                                    $status_color = '';
                                    switch ($status) {
                                        case 'Pending':
                                            $status_color = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'Approved':
                                            $status_color = 'bg-green-100 text-green-800';
                                            break;
                                        case 'Rejected':
                                            $status_color = 'bg-red-100 text-red-800';
                                            break;
                                        default:
                                            $status_color = 'bg-gray-100 text-gray-800';
                                    }
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_color; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </div>
                                <div class="space-y-2 mb-3 text-sm">
                                    <div><span class="text-gray-500">Leave Type:</span> <?php echo htmlspecialchars($request['leave_name'] ?? 'N/A'); ?></div>
                                    <div><span class="text-gray-500">Duration:</span> <?php echo date('M d', strtotime($request['start_date'])); ?> - <?php echo date('M d, Y', strtotime($request['end_date'])); ?></div>
                                    <div><span class="text-gray-500">Days:</span> <?php echo $request['total_days'] ?? 0; ?> day<?php echo ($request['total_days'] ?? 0) > 1 ? 's' : ''; ?></div>
                                    <div><span class="text-gray-500">Reason:</span> <?php echo htmlspecialchars($request['reason'] ?? 'N/A'); ?></div>
                                    <?php if ($request['approver_name'] || $request['date_approved']): ?>
                                        <div class="text-xs text-gray-500">
                                            <?php if ($request['approver_name']): ?>
                                                Approved by: <?php echo htmlspecialchars($request['approver_name']); ?>
                                            <?php endif; ?>
                                            <?php if ($request['date_approved']): ?>
                                                on <?php echo date('M d, Y', strtotime($request['date_approved'])); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($status === 'Pending' && canManageLeaves()): ?>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="showConfirmApprove(<?php echo $request['leave_request_id']; ?>)" 
                                                class="flex-1 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                                            Approve
                                        </button>
                                        <button type="button" onclick="showConfirmReject(<?php echo $request['leave_request_id']; ?>)" 
                                                class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm">
                                            Reject
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- New Leave Request Modal -->
    <div id="leaveModal" class="modal">
        <div class="modal-content max-w-2xl w-full mx-4">
            <div class="bg-teal-700 text-white p-4 rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold">New Leave Request</h3>
                    <button onclick="closeModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                </div>
            </div>
            <div class="p-6">

            <form method="POST" action="" id="leaveForm">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID</label>
                        <input
                            type="number"
                            name="employee_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            placeholder="Enter Employee ID"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                        <select
                            name="leave_type_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            required>
                            <option value="">Select Leave Type</option>
                            <?php foreach ($leave_types as $lt): ?>
                                <option value="<?php echo $lt['leave_type_id']; ?>">
                                    <?php echo htmlspecialchars($lt['leave_name']); ?>
                                    <?php if ($lt['paid_unpaid']): ?>
                                        (<?php echo htmlspecialchars($lt['paid_unpaid']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input
                            type="date"
                            name="start_date"
                            min="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input
                            type="date"
                            name="end_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                            required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                    <textarea
                        name="reason"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                        required></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-teal-700 text-white rounded-lg hover:bg-teal-800">
                        Submit Request
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script src="../js/modal.js"></script>
    <script src="../js/leave.js"></script>

    <!-- Alert Modal -->
    <div id="alertModal" class="modal">
        <div class="modal-content max-w-md w-full mx-4">
            <div class="modal-header bg-teal-700 text-white p-4 rounded-t-lg">
                <h2 class="text-xl font-bold" id="alertModalTitle">Information</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6" id="alertModalMessage"></p>
                <div class="flex justify-end">
                    <button onclick="closeAlertModal()" 
                            class="px-4 py-2 bg-teal-700 text-white rounded-lg hover:bg-teal-800 transition">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content max-w-md w-full mx-4">
            <div class="bg-yellow-600 text-white p-4 rounded-t-lg">
                <h2 class="text-xl font-bold">Confirm Action</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6" id="confirmModalMessage"></p>
                <div class="flex gap-3 justify-end">
                    <button onclick="handleCancel()" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button onclick="handleConfirm()" 
                            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="logoutModal" class="modal">
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