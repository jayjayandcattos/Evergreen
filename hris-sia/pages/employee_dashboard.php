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

// Get employee info
$employee = fetchOne($conn, 
    "SELECT employee_id, first_name, last_name, 
            CONCAT('EMP', LPAD(employee_id, 3, '0')) as employee_no
     FROM employee 
     WHERE employee_id = ?",
    [$employee_id]
);
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
        <header class="header-gradient text-white p-4 lg:p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="../assets/LOGO.png" alt="Logo" class="h-8 w-8 sm:h-10 sm:w-10 object-contain">
                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold tracking-tight">Employee Dashboard</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm sm:text-base">Welcome, <strong><?php echo htmlspecialchars($employee_name); ?></strong></span>
                    <a href="../logout.php" 
                       class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>Time Out
                    </a>
                </div>
            </div>
        </header>

        <main class="p-4 lg:p-8">
            <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <button onclick="openLeaveModal()" 
                        class="card-enhanced p-6 text-left hover:scale-105 transition-transform">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-plus text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Request Leave</h3>
                            <p class="text-sm text-gray-500">Submit a leave request</p>
                        </div>
                    </div>
                </button>

                <button onclick="showPayslips()" 
                        class="card-enhanced p-6 text-left hover:scale-105 transition-transform">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">View My Payslip</h3>
                            <p class="text-sm text-gray-500">View your payslips</p>
                        </div>
                    </div>
                </button>

                <a href="employee_calendar.php" 
                   class="card-enhanced p-6 text-left hover:scale-105 transition-transform block">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">View Calendar</h3>
                            <p class="text-sm text-gray-500">Attendance & schedule</p>
                        </div>
                    </div>
                </a>

                <div class="card-enhanced p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-id-badge text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Employee ID</h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['employee_no'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Section -->
            <div class="card-enhanced p-4 lg:p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-list mr-2"></i>My Leave Requests
                </h2>
                <?php if (empty($leave_requests)): ?>
                    <p class="text-gray-500 text-center py-8">No leave requests found</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Leave Type</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Duration</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Days</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Reason</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Date Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_requests as $request): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-3 py-2 text-sm"><?php echo htmlspecialchars($request['leave_name'] ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2 text-sm">
                                            <?php echo date('M d', strtotime($request['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($request['end_date'])); ?>
                                        </td>
                                        <td class="px-3 py-2 text-sm"><?php echo $request['total_days'] ?? 0; ?> day<?php echo ($request['total_days'] ?? 0) > 1 ? 's' : ''; ?></td>
                                        <td class="px-3 py-2 text-sm max-w-xs truncate" title="<?php echo htmlspecialchars($request['reason'] ?? ''); ?>">
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
                                                case 'Declined':
                                                case 'Rejected':
                                                    $status_color = 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    $status_color = 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                            <span class="px-3 py-1.5 inline-flex items-center text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-sm"><?php echo $request['date_requested'] ? date('M d, Y', strtotime($request['date_requested'])) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
                <?php if (empty($payslips)): ?>
                    <p class="text-gray-500 text-center py-8">No payslips found</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Pay Period</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Gross Salary</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Deductions</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Net Pay</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase">Release Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payslips as $payslip): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-3 py-2 text-sm">
                                            <?php 
                                            echo $payslip['pay_period_start'] ? date('M d', strtotime($payslip['pay_period_start'])) : 'N/A';
                                            echo ' - ';
                                            echo $payslip['pay_period_end'] ? date('M d, Y', strtotime($payslip['pay_period_end'])) : 'N/A';
                                            ?>
                                        </td>
                                        <td class="px-3 py-2 text-sm">₱<?php echo number_format($payslip['gross_salary'] ?? 0, 2); ?></td>
                                        <td class="px-3 py-2 text-sm">₱<?php echo number_format($payslip['deduction'] ?? 0, 2); ?></td>
                                        <td class="px-3 py-2 text-sm font-semibold">₱<?php echo number_format($payslip['net_pay'] ?? 0, 2); ?></td>
                                        <td class="px-3 py-2 text-sm"><?php echo $payslip['release_date'] ? date('M d, Y', strtotime($payslip['release_date'])) : 'N/A'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

    <script>
        function openLeaveModal() {
            document.getElementById('leaveModal').classList.add('active');
        }

        function closeLeaveModal() {
            document.getElementById('leaveModal').classList.remove('active');
        }

        function showPayslips() {
            document.getElementById('payslipsSection').style.display = 'block';
            document.getElementById('payslipsSection').scrollIntoView({ behavior: 'smooth' });
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
            }
        });
    </script>
</body>
</html>

