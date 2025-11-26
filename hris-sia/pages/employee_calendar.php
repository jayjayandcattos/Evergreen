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

        .calendar-day {
            min-height: 100px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        .calendar-day:hover {
            background-color: #f9fafb;
        }

        .calendar-day.today {
            background-color: #ecfdf5;
            border: 2px solid #10b981;
        }

        .event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
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
                    <a href="../logout.php" 
                       class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>Time Out
                    </a>
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
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2"><?php echo $attendance['date'] ? date('M d, Y', strtotime($attendance['date'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $attendance['time_in'] ? date('h:i A', strtotime($attendance['time_in'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo $attendance['time_out'] ? date('h:i A', strtotime($attendance['time_out'])) : 'N/A'; ?></td>
                                        <td class="px-3 py-2"><?php echo number_format($attendance['total_hours'] ?? 0, 2); ?> hrs</td>
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
</body>
</html>

