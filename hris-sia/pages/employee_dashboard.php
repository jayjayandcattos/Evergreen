<?php
session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

// Require employee login
requireEmployee();

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? 'Employee';

$message = '';
$messageType = '';

// Handle leave request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_leave'])) {
    try {
        $leave_type_id = $_POST['leave_type_id'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $reason = $_POST['reason'] ?? '';

        if (empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
            throw new Exception("All fields are required");
        }

        // Validate dates
        if (strtotime($start_date) > strtotime($end_date)) {
            throw new Exception("End date must be after start date");
        }

        // Calculate total days
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $total_days = $interval->days + 1;

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
            $message = "Leave request submitted successfully!";
            $messageType = "success";
        } else {
            throw new Exception("Failed to submit leave request");
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Fetch employee's leave requests
$leave_requests = fetchAll($conn, 
    "SELECT lr.*, lt.leave_name 
     FROM leave_request lr
     LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
     WHERE lr.employee_id = ?
     ORDER BY lr.date_requested DESC, lr.leave_request_id DESC
     LIMIT 10",
    [$employee_id]
);

// Fetch leave types for dropdown
$leave_types = fetchAll($conn, "SELECT * FROM leave_type ORDER BY leave_name");

// Fetch employee's payslips
$payslips = fetchAll($conn,
    "SELECT * FROM payroll_payslips 
     WHERE employee_id = ?
     ORDER BY pay_period_end DESC, payslip_id DESC
     LIMIT 10",
    [$employee_id]
);

// Get employee info with department and position
$employee = fetchOne($conn, 
    "SELECT e.employee_id, e.first_name, e.last_name, 
            CONCAT('EMP', LPAD(e.employee_id, 3, '0')) as employee_no,
            d.department_name, p.position_title, e.hire_date
     FROM employee e
     LEFT JOIN department d ON e.department_id = d.department_id
     LEFT JOIN position p ON e.position_id = p.position_id
     WHERE e.employee_id = ?",
    [$employee_id]
);

// Get attendance statistics for current month
$currentMonth = date('Y-m');
try {
    $attendanceStats = fetchOne($conn,
        "SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_days,
            AVG(total_hours) as avg_hours
         FROM attendance
         WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?",
        [$employee_id, $currentMonth]
    ) ?: ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0, 'avg_hours' => null];
} catch (Exception $e) {
    $attendanceStats = ['total_days' => 0, 'present_days' => 0, 'absent_days' => 0, 'late_days' => 0, 'avg_hours' => null];
}

// Get today's attendance
try {
    $todayAttendance = fetchOne($conn,
        "SELECT attendance_id, date, time_in, time_out, status, total_hours
         FROM attendance
         WHERE employee_id = ? AND DATE(date) = CURDATE()
         ORDER BY attendance_id DESC
         LIMIT 1",
        [$employee_id]
    ) ?: null;
} catch (Exception $e) {
    $todayAttendance = null;
}

// Get recent attendance (last 5 days)
try {
    $recentAttendance = fetchAll($conn,
        "SELECT date, time_in, time_out, status, total_hours
         FROM attendance
         WHERE employee_id = ?
         ORDER BY date DESC
         LIMIT 5",
        [$employee_id]
    ) ?: [];
} catch (Exception $e) {
    $recentAttendance = [];
}

// Count pending leave requests
try {
    $pendingLeaves = fetchOne($conn,
        "SELECT COUNT(*) as count
         FROM leave_request
         WHERE employee_id = ? AND status = 'Pending'",
        [$employee_id]
    ) ?: ['count' => 0];
} catch (Exception $e) {
    $pendingLeaves = ['count' => 0];
}

// Get approved leave count this year
try {
    $approvedLeaves = fetchOne($conn,
        "SELECT COUNT(*) as count, COALESCE(SUM(total_days), 0) as total_days
         FROM leave_request
         WHERE employee_id = ? AND status = 'Approved' AND YEAR(date_requested) = YEAR(CURDATE())",
        [$employee_id]
    ) ?: ['count' => 0, 'total_days' => 0];
} catch (Exception $e) {
    $approvedLeaves = ['count' => 0, 'total_days' => 0];
}

// Get payslip count
try {
    $payslipCount = fetchOne($conn,
        "SELECT COUNT(*) as count
         FROM payroll_payslips
         WHERE employee_id = ?",
        [$employee_id]
    ) ?: ['count' => 0];
} catch (Exception $e) {
    $payslipCount = ['count' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../assets/evergreen.svg">
    <title>HRIS - Employee Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/employee_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="min-h-screen">
        <header class="header-gradient text-white p-3 lg:p-4 shadow-xl">
            <div class="flex items-center justify-between max-w-7xl mx-auto">
                <div class="flex items-center gap-3">
                    <img src="../assets/LOGO.png" alt="Logo" class="h-8 w-8 sm:h-10 sm:w-10 object-contain">
                    <h1 class="text-lg sm:text-xl font-bold tracking-tight">Employee Dashboard</h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-white/80">Welcome back</p>
                        <p class="text-sm font-semibold"><?php echo htmlspecialchars($employee_name); ?></p>
                    </div>
                    <button onclick="openLogoutModal()" 
                       class="bg-white/90 backdrop-blur-sm px-3 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </header>

        <main class="p-4 lg:p-6 max-w-7xl mx-auto">
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Attendance Card -->
                <div class="stat-card-attendance text-white rounded-xl p-5 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <span class="text-sm opacity-90">This Month</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1"><?php echo $attendanceStats['present_days'] ?? 0; ?>/<?php echo $attendanceStats['total_days'] ?? 0; ?></h3>
                    <p class="text-sm opacity-90">Days Present</p>
                    <?php if (isset($attendanceStats['avg_hours']) && $attendanceStats['avg_hours']): ?>
                        <p class="text-xs opacity-75 mt-2">Avg: <?php echo number_format($attendanceStats['avg_hours'], 1); ?> hrs/day</p>
                    <?php endif; ?>
                </div>

                <!-- Leave Balance Card -->
                <div class="stat-card-leave text-white rounded-xl p-5 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                            <i class="fas fa-umbrella-beach text-2xl"></i>
                        </div>
                        <span class="text-sm opacity-90"><?php echo $pendingLeaves['count'] ?? 0; ?> Pending</span>
                    </div>
                    <h3 class="text-3xl font-bold mb-1"><?php echo $approvedLeaves['total_days'] ?? 0; ?></h3>
                    <p class="text-sm opacity-90">Days Used This Year</p>
                    <p class="text-xs opacity-75 mt-2"><?php echo $approvedLeaves['count'] ?? 0; ?> Approved Requests</p>
                </div>

                <!-- Today's Status Card -->
                <div class="stat-card-today text-white rounded-xl p-5 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                        <span class="text-sm opacity-90"><?php echo date('M d'); ?></span>
                    </div>
                    <?php if ($todayAttendance): ?>
                        <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($todayAttendance['status'] ?? 'N/A'); ?></h3>
                        <p class="text-sm opacity-90">
                            <?php if ($todayAttendance['time_in']): ?>
                                In: <?php echo date('g:i A', strtotime($todayAttendance['time_in'])); ?>
                            <?php else: ?>
                                No time-in yet
                            <?php endif; ?>
                        </p>
                        <?php if ($todayAttendance['time_out']): ?>
                            <p class="text-xs opacity-75 mt-1">Out: <?php echo date('g:i A', strtotime($todayAttendance['time_out'])); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <h3 class="text-lg font-bold mb-1">No Record</h3>
                        <p class="text-sm opacity-90">No attendance today</p>
                    <?php endif; ?>
                </div>

                <!-- Payslips Card -->
                <div class="stat-card-payslip text-white rounded-xl p-5 shadow-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-2xl"></i>
                        </div>
                        <button onclick="showPayslips()" class="text-sm opacity-90 hover:opacity-100 underline">Click to view payslip</button>
                    </div>
                    <p class="text-2xl font-bold mb-1">Payslip</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Left Column - 2/3 width -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Quick Actions -->
                    <div class="card-enhanced p-5">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bolt text-teal-600 mr-2"></i>Quick Actions
                        </h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <button onclick="openLeaveModal()" 
                                    class="quick-action-btn bg-teal-50 hover:bg-teal-100 border-teal-200">
                                <i class="fas fa-calendar-plus text-teal-600 text-xl mb-2"></i>
                                <span class="text-sm font-semibold text-gray-800">Request Leave</span>
                            </button>
                            
                            <button onclick="showPayslips()" 
                                    class="quick-action-btn bg-blue-50 hover:bg-blue-100 border-blue-200">
                                <i class="fas fa-file-invoice-dollar text-blue-600 text-xl mb-2"></i>
                                <span class="text-sm font-semibold text-gray-800">View Payslips</span>
                            </button>
                            
                            <a href="employee_calendar.php" 
                               class="quick-action-btn bg-green-50 hover:bg-green-100 border-green-200">
                                <i class="fas fa-calendar-alt text-green-600 text-xl mb-2"></i>
                                <span class="text-sm font-semibold text-gray-800">Calendar</span>
                            </a>
                        </div>
                    </div>

                    <!-- Leave Requests Table -->
                    <div class="card-enhanced p-5">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-bold text-gray-800 flex items-center">
                                <i class="fas fa-list-ul text-teal-600 mr-2"></i>Recent Leave Requests
                            </h2>
                            <button onclick="openLeaveModal()" class="text-sm text-teal-600 hover:text-teal-700 font-semibold">
                                <i class="fas fa-plus mr-1"></i>New Request
                            </button>
                        </div>
                        <?php if (empty($leave_requests)): ?>
                            <div class="text-center py-8 bg-gray-50 rounded-lg">
                                <i class="fas fa-inbox text-gray-400 text-3xl mb-2"></i>
                                <p class="text-gray-500">No leave requests yet</p>
                                <button onclick="openLeaveModal()" class="mt-3 text-sm text-teal-600 hover:text-teal-700 font-semibold">
                                    Submit your first request
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 border-b border-gray-200">
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Type</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Dates</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Days</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($leave_requests, 0, 5) as $request): ?>
                                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                <td class="px-3 py-2">
                                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($request['leave_name'] ?? 'N/A'); ?></div>
                                                    <div class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($request['date_requested'])); ?></div>
                                                </td>
                                                <td class="px-3 py-2 text-gray-700">
                                                    <?php echo date('M d', strtotime($request['start_date'])); ?> - 
                                                    <?php echo date('M d', strtotime($request['end_date'])); ?>
                                                </td>
                                                <td class="px-3 py-2 text-gray-700"><?php echo $request['total_days'] ?? 0; ?> day<?php echo ($request['total_days'] ?? 0) > 1 ? 's' : ''; ?></td>
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
                                                        case 'Declined':
                                                        case 'Rejected':
                                                            $status_color = 'bg-red-100 text-red-800';
                                                            break;
                                                        default:
                                                            $status_color = 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>
                                                    <span class="px-2 py-1 inline-flex items-center text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                                        <?php echo htmlspecialchars($status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if (count($leave_requests) > 5): ?>
                                    <div class="text-center pt-3">
                                        <a href="leave.php" class="text-sm text-teal-600 hover:text-teal-700 font-semibold">
                                            View all <?php echo count($leave_requests); ?> requests <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column - 1/3 width -->
                <div class="space-y-6">
                    <!-- Employee Info Card -->
                    <div class="card-enhanced p-5">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-circle text-teal-600 mr-2"></i>Employee Info
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Employee ID</p>
                                <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></p>
                            </div>
                            <?php if ($employee['department_name']): ?>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Department</p>
                                <p class="text-gray-700"><?php echo htmlspecialchars($employee['department_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($employee['position_title']): ?>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Position</p>
                                <p class="text-gray-700"><?php echo htmlspecialchars($employee['position_title']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($employee['hire_date']): ?>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Date Hired</p>
                                <p class="text-gray-700"><?php echo date('M d, Y', strtotime($employee['hire_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    <div class="card-enhanced p-5">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-history text-teal-600 mr-2"></i>Recent Attendance
                        </h2>
                        <?php if (empty($recentAttendance)): ?>
                            <p class="text-gray-500 text-sm text-center py-4">No attendance records</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($recentAttendance as $att): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">
                                                <?php echo date('M d, Y', strtotime($att['date'])); ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <?php if ($att['time_in']): ?>
                                                    <?php echo date('g:i A', strtotime($att['time_in'])); ?>
                                                    <?php if ($att['time_out']): ?>
                                                        - <?php echo date('g:i A', strtotime($att['time_out'])); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    No time record
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php
                                                echo $att['status'] === 'Present' ? 'bg-green-100 text-green-800' : 
                                                    ($att['status'] === 'Absent' ? 'bg-red-100 text-red-800' : 
                                                    ($att['status'] === 'Late' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'));
                                            ?>">
                                                <?php echo htmlspecialchars($att['status']); ?>
                                            </span>
                                            <?php if ($att['total_hours']): ?>
                                                <p class="text-xs text-gray-600 mt-1"><?php echo number_format($att['total_hours'], 1); ?>h</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payslips Section (Hidden by default) -->
            <div id="payslipsSection" class="card-enhanced p-4 lg:p-6 mb-6" style="display: none;">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>My Payslips
                    </h2>
                    <button onclick="hidePayslips()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="payslipsLoading" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
                    <p class="text-gray-500">Loading payslip data...</p>
                </div>
                <div id="payslipsContent" style="display: none;">
                    <div id="payslipsList"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Leave Request Modal -->
    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="bg-teal-700 text-white p-4 rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold">Request Leave</h3>
                    <button onclick="closeLeaveModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <form method="POST" action="" id="leaveForm">
                    <input type="hidden" name="submit_leave" value="1">
                    
                    <div class="mb-4">
                        <label for="leave_type_id" class="block text-sm sm:text-base font-medium text-gray-700 mb-2">
                            Leave Type <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <select id="leave_type_id" name="leave_type_id" required
                            aria-label="Select Leave Type"
                            aria-required="true"
                            class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="start_date" class="block text-sm sm:text-base font-medium text-gray-700 mb-2">
                                Start Date <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date" required
                                min="<?php echo date('Y-m-d'); ?>"
                                aria-label="Leave Start Date"
                                aria-required="true"
                                class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                onchange="calculateDays()">
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm sm:text-base font-medium text-gray-700 mb-2">
                                End Date <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" required
                                min="<?php echo date('Y-m-d'); ?>"
                                aria-label="Leave End Date"
                                aria-required="true"
                                class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                                onchange="calculateDays()">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="totalDays" class="block text-sm sm:text-base font-medium text-gray-700 mb-2">Total Days</label>
                        <input type="text" id="totalDays" readonly
                            aria-label="Total Days (auto-calculated)"
                            class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="block text-sm sm:text-base font-medium text-gray-700 mb-2">
                            Reason <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="4" required
                            aria-label="Leave Request Reason"
                            aria-required="true"
                            placeholder="Please provide a reason for your leave request"
                            class="w-full px-3 py-2.5 sm:px-4 sm:py-3 text-sm sm:text-base border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeLeaveModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-teal-700 text-white rounded-lg hover:bg-teal-800">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payslip Privacy Confirmation Modal -->
    <div id="payslipModal" class="modal">
        <div class="modal-content max-w-md w-full mx-4">
            <div class="bg-blue-600 text-white p-4 rounded-t-lg">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold">
                        <i class="fas fa-shield-alt mr-2"></i>Privacy Warning
                    </h2>
                    <button onclick="closePayslipModal()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <div class="flex items-start gap-3 mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mt-1"></i>
                        <div>
                            <p class="text-gray-700 font-semibold mb-2">Sensitive Information</p>
                            <p class="text-gray-600 text-sm">
                                You are about to view your payslip details, which contain confidential financial information including:
                            </p>
                            <ul class="text-gray-600 text-sm mt-2 ml-4 list-disc">
                                <li>Salary and earnings breakdown</li>
                                <li>Deductions and contributions</li>
                                <li>Tax information</li>
                            </ul>
                            <p class="text-gray-600 text-sm mt-3">
                                Please ensure you are in a private location and that no unauthorized persons can view your screen.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button onclick="closePayslipModal()"
                        class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                    <button onclick="confirmViewPayslip()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-check mr-2"></i>I Understand, Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
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
        function openLeaveModal() {
            document.getElementById('leaveModal').classList.add('active');
        }

        function closeLeaveModal() {
            document.getElementById('leaveModal').classList.remove('active');
        }

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        const employeeId = <?php echo json_encode($employee_id); ?>;
        
        // Detect base path dynamically
        function getBasePath() {
            const path = window.location.pathname;
            
            // Try multiple patterns to detect base path
            // Pattern 1: /evergreen/hris-sia/... -> /evergreen
            let match = path.match(/^\/([^\/]+)\//);
            if (match) {
                return '/' + match[1];
            }
            
            // Pattern 2: /hris-sia/... -> empty (root)
            if (path.startsWith('/hris-sia/')) {
                return '';
            }
            
            // Pattern 3: Full path with multiple segments
            // Extract everything before /hris-sia
            match = path.match(/^(.+?)\/hris-sia\//);
            if (match) {
                return match[1];
            }
            
            // Default: try to extract first segment
            match = path.match(/^(\/[^\/]+)/);
            return match ? match[1] : '';
        }
        
        const basePath = getBasePath();
        console.log('Detected base path:', basePath);
        console.log('Current pathname:', window.location.pathname);

        function showPayslips() {
            // Show privacy confirmation modal first
            openPayslipModal();
        }

        function openPayslipModal() {
            document.getElementById('payslipModal').classList.add('active');
        }

        function closePayslipModal() {
            document.getElementById('payslipModal').classList.remove('active');
        }

        function confirmViewPayslip() {
            closePayslipModal();
            fetchPayslipDetails(employeeId);
        }

        function fetchPayslipDetails(employeeId) {
            // Validate employee ID
            if (!employeeId || employeeId === 'undefined' || employeeId === 'null') {
                console.error('Invalid employee ID:', employeeId);
                document.getElementById('payslipsLoading').style.display = 'none';
                document.getElementById('payslipsContent').style.display = 'block';
                document.getElementById('payslipsList').innerHTML = 
                    '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' +
                    '<p class="font-semibold">Invalid Employee ID</p>' +
                    '<p class="text-sm">Unable to fetch payslips: Employee ID is missing or invalid. Please log out and log back in.</p>' +
                    '</div>';
                return;
            }
            
            // Convert to number if it's a string
            const numericEmployeeId = parseInt(employeeId, 10);
            if (isNaN(numericEmployeeId) || numericEmployeeId <= 0) {
                console.error('Invalid employee ID format:', employeeId);
                document.getElementById('payslipsLoading').style.display = 'none';
                document.getElementById('payslipsContent').style.display = 'block';
                document.getElementById('payslipsList').innerHTML = 
                    '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' +
                    '<p class="font-semibold">Invalid Employee ID Format</p>' +
                    '<p class="text-sm">Employee ID must be a valid number. Please contact support.</p>' +
                    '</div>';
                return;
            }
            
            // Show payslips section
            const payslipsSection = document.getElementById('payslipsSection');
            payslipsSection.style.display = 'block';
            payslipsSection.scrollIntoView({ behavior: 'smooth' });

            // Show loading state
            document.getElementById('payslipsLoading').style.display = 'block';
            document.getElementById('payslipsContent').style.display = 'none';

            // Make API call to accounting system
            // Use dynamic base path
            const apiUrl = basePath + '/accounting-and-finance/modules/api/payslip-data.php?action=get_payslips&employee_id=' + numericEmployeeId;
            
            console.log('Fetching payslips from:', apiUrl);
            console.log('Employee ID (numeric):', numericEmployeeId);
            console.log('Employee ID (original):', employeeId);
            
            fetch(apiUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(async response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                // Get response text first to handle both JSON and text errors
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                if (!response.ok) {
                    // Try to parse as JSON, otherwise use as text
                    let errorMessage = responseText;
                    try {
                        const errorJson = JSON.parse(responseText);
                        errorMessage = errorJson.error || errorJson.message || responseText;
                    } catch (e) {
                        // Not JSON, use as-is
                    }
                    throw new Error(`HTTP error! status: ${response.status}, message: ${errorMessage}`);
                }
                
                // Parse JSON response
                try {
                    return JSON.parse(responseText);
                } catch (e) {
                    throw new Error('Invalid JSON response from server');
                }
            })
            .then(data => {
                console.log('Payslip data received:', data);
                document.getElementById('payslipsLoading').style.display = 'none';
                document.getElementById('payslipsContent').style.display = 'block';
                
                // Check if request was successful
                if (!data.success) {
                    let errorMessage = data.error || 'Failed to fetch payslip data';
                    document.getElementById('payslipsList').innerHTML = 
                        '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' +
                        '<p class="font-semibold">Error loading payslips</p>' +
                        '<p class="text-sm">' + errorMessage + '</p>' +
                        '</div>';
                    return;
                }
                
                // Check if we have payslip data
                if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                    displayPayslips(data.data);
                } else {
                    // Handle empty data case - provide more helpful message
                    const employeeNo = data.employee_external_no || 'your account';
                    const employeeId = data.employee_id || employeeId;
                    
                    document.getElementById('payslipsList').innerHTML = 
                        '<div class="bg-blue-50 border border-blue-300 rounded-lg p-6">' +
                        '<div class="flex items-start gap-3">' +
                        '<i class="fas fa-info-circle text-blue-600 text-2xl mt-1"></i>' +
                        '<div class="flex-1">' +
                        '<h3 class="font-semibold text-blue-800 mb-2">No Payslips Available</h3>' +
                        '<p class="text-blue-700 text-sm mb-3">' +
                        'No payslip records were found for <strong>' + employeeNo + '</strong> (Employee ID: ' + employeeId + ').' +
                        '</p>' +
                        '<div class="bg-white rounded p-4 mb-3">' +
                        '<p class="text-blue-600 text-xs font-semibold mb-2">Possible reasons:</p>' +
                        '<ul class="list-disc list-inside text-blue-600 text-xs space-y-1">' +
                        '<li>No payroll has been processed for your account yet</li>' +
                        '<li>Your payslips are still being prepared by the payroll department</li>' +
                        '<li>There may be a delay in the system synchronization</li>' +
                        '<li>Your employee account may not be set up for payroll processing</li>' +
                        '</ul>' +
                        '</div>' +
                        '<div class="bg-blue-100 rounded p-3">' +
                        '<p class="text-blue-700 text-xs">' +
                        '<i class="fas fa-phone-alt mr-1"></i>' +
                        'If you believe this is an error or have questions about your payslips, please contact:' +
                        '</p>' +
                        '<ul class="text-blue-600 text-xs mt-2 ml-4 list-disc">' +
                        '<li>HR Department</li>' +
                        '<li>Payroll Department</li>' +
                        '</ul>' +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching payslips:', error);
                console.error('API URL was:', apiUrl);
                console.error('Employee ID was:', employeeId);
                
                document.getElementById('payslipsLoading').style.display = 'none';
                document.getElementById('payslipsContent').style.display = 'block';
                
                let errorMessage = error.message || 'Unknown error occurred';
                let errorDetails = '';
                
                // Provide more specific error messages
                if (errorMessage.includes('Failed to fetch') || errorMessage.includes('NetworkError')) {
                    errorDetails = 'Unable to connect to the payroll system. Please check your internet connection and try again.';
                } else if (errorMessage.includes('404')) {
                    errorDetails = 'The payslip service endpoint was not found. Please contact the system administrator.';
                } else if (errorMessage.includes('500')) {
                    errorDetails = 'The payroll system encountered an internal error. Please try again later or contact support.';
                } else {
                    errorDetails = errorMessage;
                }
                
                document.getElementById('payslipsList').innerHTML = 
                    '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' +
                    '<div class="flex items-start gap-3">' +
                    '<i class="fas fa-exclamation-triangle text-red-600 text-2xl mt-1"></i>' +
                    '<div class="flex-1">' +
                    '<p class="font-semibold mb-2">Error Loading Payslips</p>' +
                    '<p class="text-sm mb-3">' + errorDetails + '</p>' +
                    '<div class="bg-white rounded p-3 mt-3">' +
                    '<p class="text-red-600 text-xs font-semibold mb-1">Troubleshooting Steps:</p>' +
                    '<ul class="list-disc list-inside text-red-600 text-xs space-y-1">' +
                    '<li>Refresh the page and try again</li>' +
                    '<li>Check your internet connection</li>' +
                    '<li>Clear your browser cache and cookies</li>' +
                    '<li>Contact HR or IT support if the problem persists</li>' +
                    '</ul>' +
                    '</div>' +
                    '<p class="text-xs text-red-600 mt-3">' +
                    '<i class="fas fa-info-circle mr-1"></i>' +
                    'Error details have been logged. Reference: Employee ID ' + employeeId +
                    '</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            });
        }

        function displayPayslips(payslips) {
            let html = '';
            
            payslips.forEach((payslip, index) => {
                const periodStart = payslip.period_start ? new Date(payslip.period_start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'N/A';
                const periodEnd = payslip.period_end ? new Date(payslip.period_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const runAt = payslip.run_at ? new Date(payslip.run_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                
                const isCalculated = payslip.payroll_status === 'calculated' || payslip.is_calculated;
                const statusBadge = isCalculated ? 
                    '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 ml-2">Calculated (Not Finalized)</span>' : 
                    '';
                
                html += `
                    <div class="mb-6 border border-gray-200 rounded-lg overflow-hidden ${index > 0 ? 'mt-6' : ''}">
                        <!-- Payslip Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-bold">${payslip.id > 0 ? 'Payslip #' + payslip.id : 'Calculated Payslip'}</h3>
                                        ${statusBadge}
                                    </div>
                                    <p class="text-sm text-blue-100">${periodStart} - ${periodEnd}</p>
                                    ${isCalculated ? '<p class="text-xs text-blue-200 mt-1">This is calculated payroll data based on attendance. It will be finalized when payroll is processed.</p>' : ''}
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-blue-100">${isCalculated ? 'Calculated On' : 'Payroll Date'}</p>
                                    <p class="text-sm font-semibold">${runAt}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 lg:p-6">
                            <!-- Earnings Section -->
                            <div class="mb-6">
                                <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-arrow-up text-green-600 mr-2"></i>Earnings
                                </h4>
                                <div class="bg-green-50 rounded-lg p-4">
                                    ${payslip.breakdown && payslip.breakdown.earnings && payslip.breakdown.earnings.length > 0 ? 
                                        payslip.breakdown.earnings.map(earning => `
                                            <div class="flex justify-between items-center py-2 border-b border-green-200 last:border-b-0">
                                                <span class="text-gray-700">${earning.name}</span>
                                                <span class="font-semibold text-gray-800">₱${parseFloat(earning.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                            </div>
                                        `).join('') : 
                                        '<p class="text-gray-500 text-sm">No earnings breakdown available</p>'
                                    }
                                    <div class="flex justify-between items-center py-2 mt-2 pt-3 border-t-2 border-green-300">
                                        <span class="font-bold text-gray-800">Total Earnings</span>
                                        <span class="font-bold text-green-700 text-lg">₱${parseFloat(payslip.gross_pay).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Deductions Section -->
                            <div class="mb-6">
                                <h4 class="text-md font-semibold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-arrow-down text-red-600 mr-2"></i>Deductions
                                </h4>
                                <div class="bg-red-50 rounded-lg p-4">
                                    ${payslip.breakdown && payslip.breakdown.deductions && payslip.breakdown.deductions.length > 0 ? 
                                        payslip.breakdown.deductions.map(deduction => `
                                            <div class="flex justify-between items-center py-2 border-b border-red-200 last:border-b-0">
                                                <span class="text-gray-700">${deduction.name}</span>
                                                <span class="font-semibold text-gray-800">₱${parseFloat(deduction.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                            </div>
                                        `).join('') : 
                                        '<p class="text-gray-500 text-sm">No deductions breakdown available</p>'
                                    }
                                    <div class="flex justify-between items-center py-2 mt-2 pt-3 border-t-2 border-red-300">
                                        <span class="font-bold text-gray-800">Total Deductions</span>
                                        <span class="font-bold text-red-700 text-lg">₱${parseFloat(payslip.total_deductions).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Net Pay Section -->
                            <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-800">Net Pay</span>
                                    <span class="text-2xl font-bold text-blue-700">₱${parseFloat(payslip.net_pay).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('payslipsList').innerHTML = html;
        }

        function hidePayslips() {
            document.getElementById('payslipsSection').style.display = 'none';
        }

        function calculateDays() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const totalDaysInput = document.getElementById('totalDays');

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end dates
                totalDaysInput.value = diffDays + ' day' + (diffDays > 1 ? 's' : '');
            } else {
                totalDaysInput.value = '';
            }
        }

        // Close modal when clicking outside
        document.getElementById('leaveModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLeaveModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLeaveModal();
                closeLogoutModal();
                closePayslipModal();
            }
        });

        // Close logout modal when clicking outside
        const logoutModal = document.getElementById('logoutModal');
        if (logoutModal) {
            logoutModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLogoutModal();
                }
            });
        }

        // Close payslip modal when clicking outside
        const payslipModal = document.getElementById('payslipModal');
        if (payslipModal) {
            payslipModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closePayslipModal();
                }
            });
        }
    </script>
</body>
</html>

