<?php
session_start();

require_once '../config/database.php';
require_once '../includes/auth.php';

// Require employee login
requireEmployee();

$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['employee_name'] ?? 'Employee';

// Fetch employee's attendance records
$attendanceSql = "SELECT attendance_id, date, time_in, time_out, total_hours, status, remarks
                  FROM attendance
                  WHERE employee_id = ?
                  ORDER BY date DESC
                  LIMIT 100";

$attendanceRecords = fetchAll($conn, $attendanceSql, [$employee_id]);

// Fetch employee's leave requests
$leaveRequestsSql = "SELECT lr.*, lt.leave_name
                     FROM leave_request lr
                     LEFT JOIN leave_type lt ON lr.leave_type_id = lt.leave_type_id
                     WHERE lr.employee_id = ?
                     ORDER BY lr.start_date DESC";

$leaveRequests = fetchAll($conn, $leaveRequestsSql, [$employee_id]);

// Fetch recruitment events (job postings) from admin calendar
$recruitmentEventsSql = "SELECT r.recruitment_id, 
                        r.job_title, 
                        r.date_posted,
                        d.department_name
                        FROM recruitment r
                        LEFT JOIN department d ON r.department_id = d.department_id
                        WHERE LOWER(r.status) = 'open'
                        ORDER BY r.date_posted DESC";

$recruitmentEvents = fetchAll($conn, $recruitmentEventsSql);

// Function to calculate hours worked from time_in and time_out
function calculateWorkHours($time_in, $time_out) {
    if (!$time_in) {
        return 0.00;
    }
    
    if (!$time_out) {
        // If no time out, calculate from time_in to now
        $start = new DateTime($time_in);
        $end = new DateTime();
    } else {
        $start = new DateTime($time_in);
        $end = new DateTime($time_out);
    }
    
    $interval = $start->diff($end);
    $hours = $interval->h + ($interval->days * 24) + ($interval->i / 60) + ($interval->s / 3600);
    
    return round($hours, 2);
}

// Prepare calendar events
$events = [];

// Add attendance records
foreach ($attendanceRecords as $attendance) {
    $date = $attendance['date'];
    $timeIn = $attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : 'N/A';
    $timeOut = $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : 'N/A';
    $hours = $attendance['total_hours'] ?? 0;
    
    $events[] = [
        'id' => 'att_' . $attendance['attendance_id'],
        'date' => $date,
        'title' => "Time In: $timeIn | Time Out: $timeOut",
        'type' => 'attendance',
        'status' => $attendance['status'] ?? 'Present',
        'hours' => $hours,
        'color' => '#0d9488'
    ];
}

// Add leave requests
foreach ($leaveRequests as $leave) {
    $startDate = $leave['start_date'];
    $endDate = $leave['end_date'];
    $status = $leave['status'] ?? 'Pending';
    $leaveName = $leave['leave_name'] ?? 'Leave';
    
    // Determine color based on status
    $color = '#f59e0b'; // Pending - yellow
    if ($status === 'Approved') {
        $color = '#10b981'; // Approved - green
    } elseif ($status === 'Declined' || $status === 'Rejected') {
        $color = '#ef4444'; // Declined - red
    }
    
    // Create event for each day of leave
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start, $interval, $end->modify('+1 day'));
    
    foreach ($period as $day) {
        $dayStr = $day->format('Y-m-d');
        $events[] = [
            'id' => 'leave_' . $leave['leave_request_id'] . '_' . $dayStr,
            'date' => $dayStr,
            'title' => "$leaveName - $status",
            'type' => 'leave',
            'status' => $status,
            'days' => $leave['total_days'] ?? 0,
            'color' => $color
        ];
    }
}

// Add recruitment events (job postings)
foreach ($recruitmentEvents as $recruitment) {
    $datePosted = $recruitment['date_posted'];
    $jobTitle = $recruitment['job_title'];
    $department = $recruitment['department_name'] ?? '';
    
    $events[] = [
        'id' => 'recruitment_' . $recruitment['recruitment_id'],
        'date' => $datePosted,
        'title' => $jobTitle . ($department ? " ($department)" : ''),
        'type' => 'recruitment',
        'color' => '#0d9488' // Teal color matching admin calendar
    ];
}

// Group events by date
$eventsByDate = [];
foreach ($events as $event) {
    $date = $event['date'];
    if (!isset($eventsByDate[$date])) {
        $eventsByDate[$date] = [];
    }
    $eventsByDate[$date][] = $event;
}

// Get current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Calculate previous and next month/year
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Get first day of month and number of days
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay); // 0 = Sunday, 6 = Saturday

// Adjust for Monday as first day (0 = Monday)
$dayOfWeek = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../assets/evergreen.svg">
    <title>HRIS - My Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/employee_calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
    </style>
</head>
<body>
    <div class="min-h-screen">
        <header class="header-gradient text-white p-4 lg:p-6 shadow-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="employee_dashboard.php" class="text-white hover:text-gray-200">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold tracking-tight">My Calendar</h1>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm sm:text-base"><?php echo htmlspecialchars($employee_name); ?></span>
                    <button onclick="openLogoutModal()" 
                       class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>Time Out
                    </button>
                </div>
            </div>
        </header>

        <main class="p-4 lg:p-8">
            <!-- Calendar Navigation -->
            <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <?php echo date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)); ?>
                    </h2>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-1">
                    <!-- Day Headers -->
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Mon</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Tue</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Wed</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Thu</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Fri</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Sat</div>
                    <div class="p-2 text-center font-semibold text-gray-700 bg-gray-100">Sun</div>

                    <!-- Empty cells for days before month starts -->
                    <?php for ($i = 0; $i < $dayOfWeek; $i++): ?>
                        <div class="calendar-day p-2 bg-gray-50"></div>
                    <?php endfor; ?>

                    <!-- Calendar Days -->
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php
                        $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                        $isToday = ($dateStr === date('Y-m-d'));
                        $dayEvents = $eventsByDate[$dateStr] ?? [];
                        ?>
                        <div class="calendar-day p-2 <?php echo $isToday ? 'today' : ''; ?>">
                            <div class="font-semibold text-gray-800 mb-1"><?php echo $day; ?></div>
                            <div class="space-y-1">
                                <?php foreach ($dayEvents as $event): ?>
                                    <div class="text-xs p-1 rounded" style="background-color: <?php echo $event['color']; ?>20; border-left: 3px solid <?php echo $event['color']; ?>;">
                                        <span class="event-dot" style="background-color: <?php echo $event['color']; ?>;"></span>
                                        <span class="text-gray-700"><?php echo htmlspecialchars($event['title']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Legend -->
            <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Legend</h3>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <span class="event-dot" style="background-color: #0d9488;"></span>
                        <span class="text-sm text-gray-700">Attendance</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="event-dot" style="background-color: #0d9488;"></span>
                        <span class="text-sm text-gray-700">Events</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="event-dot" style="background-color: #f59e0b;"></span>
                        <span class="text-sm text-gray-700">Pending Leave</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="event-dot" style="background-color: #10b981;"></span>
                        <span class="text-sm text-gray-700">Approved Leave</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="event-dot" style="background-color: #ef4444;"></span>
                        <span class="text-sm text-gray-700">Declined Leave</span>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance Summary -->
            <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-800 mb-3">Recent Attendance</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Time In</th>
                                <th class="px-3 py-2 text-left">Time Out</th>
                                <th class="px-3 py-2 text-left">Hours</th>
                                <th class="px-3 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendanceRecords)): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-gray-500">No attendance records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($attendanceRecords, 0, 10) as $attendance): ?>
                                    <?php
                                    // Calculate hours if not set or is 0
                                    $calculatedHours = $attendance['total_hours'] ?? 0;
                                    if ($calculatedHours == 0 && $attendance['time_in']) {
                                        $calculatedHours = calculateWorkHours($attendance['time_in'], $attendance['time_out']);
                                    }
                                    ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo $attendance['date'] ? date('M d, Y', strtotime($attendance['date'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo number_format($calculatedHours, 2); ?> hrs</td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-1 text-xs rounded-full bg-teal-100 text-teal-800">
                                                <?php echo htmlspecialchars($attendance['status'] ?? 'Present'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave Requests Summary -->
            <div class="bg-white rounded-lg shadow-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-3">My Leave Requests</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-3 py-2 text-left">Leave Type</th>
                                <th class="px-3 py-2 text-left">Start Date</th>
                                <th class="px-3 py-2 text-left">End Date</th>
                                <th class="px-3 py-2 text-left">Days</th>
                                <th class="px-3 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaveRequests)): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-gray-500">No leave requests found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($leaveRequests as $leave): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($leave['leave_name'] ?? 'N/A'); ?></td>
                                        <td class="px-3 py-2"><?php echo $leave['start_date'] ? date('M d, Y', strtotime($leave['start_date'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $leave['end_date'] ? date('M d, Y', strtotime($leave['end_date'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $leave['total_days'] ?? 0; ?> day<?php echo ($leave['total_days'] ?? 0) > 1 ? 's' : ''; ?></td>
                                        <td class="px-3 py-2">
                                            <?php
                                            $status = $leave['status'] ?? 'Pending';
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
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $status_color; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
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

    <script src="../js/modal.js"></script>
    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeLogoutModal();
                    }
                });
            }

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                }
            });
        });
    </script>
</body>
</html>

