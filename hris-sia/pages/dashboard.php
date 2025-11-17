<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

require_once '../config/database.php';

if (isset($logger)) {
    $logger->debug('PAGE', 'Dashboard accessed', 'User: ' . ($_SESSION['username'] ?? 'unknown'));
}

function getCount($conn, $table, $whereClause = '') {
    $sql = "SELECT COUNT(*) as total FROM {$table}";
    if ($whereClause) {
        $sql .= " WHERE {$whereClause}";
    }
    $result = fetchOne($conn, $sql);
    return ($result && !isset($result['error'])) ? (int)$result['total'] : 0;
}

function getMonthlyData($conn, $table, $dateField) {
    if ($table === 'applicant' && $dateField === 'created_at') {
        try {
            $sql = "SELECT DATE_FORMAT(r.date_posted, '%b') as month, COUNT(*) as count 
                    FROM applicant a
                    LEFT JOIN recruitment r ON a.recruitment_id = r.recruitment_id
                    WHERE a.application_status != 'Archived'
                    AND r.date_posted IS NOT NULL
                    AND YEAR(r.date_posted) = YEAR(CURDATE())
                    GROUP BY MONTH(r.date_posted)
                    ORDER BY MONTH(r.date_posted)";
            $result = fetchAll($conn, $sql);
            return ($result && !isset($result['error'])) ? $result : [];
        } catch (Exception $e) {
            // If that fails, return empty array - we'll show 0 for all months
            return [];
        }
    }
    
    $sql = "SELECT DATE_FORMAT({$dateField}, '%b') as month, COUNT(*) as count 
            FROM {$table} 
            WHERE {$dateField} IS NOT NULL
            AND YEAR({$dateField}) = YEAR(CURDATE())
            GROUP BY MONTH({$dateField})
            ORDER BY MONTH({$dateField})";
    
    $result = fetchAll($conn, $sql);
    return ($result && !isset($result['error'])) ? $result : [];
}

function processMonthlyData($data, $months) {
    $counts = array_fill(0, 12, 0);
    foreach ($data as $row) {
        $monthIndex = array_search($row['month'], $months);
        if ($monthIndex !== false) {
            $counts[$monthIndex] = (int)$row['count'];
        }
    }
    return $counts;
}

function getYearlyData($conn, $table, $dateField, $yearsBack = 10, $whereClause = '') {
    $currentYear = (int)date('Y');
    $startYear = $currentYear - $yearsBack;
    
    $sql = "SELECT YEAR({$dateField}) as year, COUNT(*) as count 
            FROM {$table} 
            WHERE {$dateField} IS NOT NULL
            AND YEAR({$dateField}) >= ?";
    
    if ($whereClause) {
        $sql .= " AND {$whereClause}";
    }
    
    $sql .= " GROUP BY YEAR({$dateField})
              ORDER BY YEAR({$dateField})";
    
    try {
        $result = fetchAll($conn, $sql, [$startYear]);
        return ($result && !isset($result['error'])) ? $result : [];
    } catch (Exception $e) {
        return [];
    }
}

function processYearlyData($data, $years) {
    $counts = array_fill(0, count($years), 0);
    foreach ($data as $row) {
        $yearIndex = array_search((int)$row['year'], $years);
        if ($yearIndex !== false) {
            $counts[$yearIndex] = (int)$row['count'];
        }
    }
    return $counts;
}

// Get total employees count (only ACTIVE employees)
$stats = [
    'employees' => getCount($conn, 'employee', "employment_status = 'Active'"),
    'applicants' => getCount($conn, 'applicant', "application_status != 'Archived'"),
    'events' => getCount($conn, 'recruitment', "MONTH(date_posted) = MONTH(CURDATE()) AND YEAR(date_posted) = YEAR(CURDATE())")
];

$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Get yearly data for employees (last 10 years)
$currentYear = (int)date('Y');
$years = range($currentYear - 9, $currentYear);
$yearsLabels = array_map('strval', $years);

// Get yearly data for employees (only ACTIVE employees)
$employeeYearlyData = getYearlyData($conn, 'employee', 'hire_date', 10, "employment_status = 'Active'");
$employeeYearlyCounts = processYearlyData($employeeYearlyData, $years);

// Get monthly data for other charts
$chartData = [
    'employees' => $employeeYearlyCounts,
    'applicants' => processMonthlyData(getMonthlyData($conn, 'applicant', 'created_at'), $months),
    'events' => processMonthlyData(getMonthlyData($conn, 'recruitment', 'date_posted'), $months)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2f1 50%, #f8fafc 100%);
            background-attachment: fixed;
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

        .card-active {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 25px 50px rgba(13, 148, 136, 0.4), 0 0 0 2px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .card-active::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
            pointer-events: none;
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

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin: 8px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .stat-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .chart-container {
            opacity: 0;
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .chart-card:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
            position: relative;
            padding-bottom: 12px;
        }

        .chart-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #0d9488, #14b8a6);
            border-radius: 2px;
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
            max-width: 400px;
            width: 90%;
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

        .stat-card.employees-card {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #0d9488 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .stat-card.applicants-card {
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 50%, #0891b2 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .stat-card.events-card {
            background: linear-gradient(135deg, #059669 0%, #10b981 50%, #059669 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        @media (max-width: 640px) {
            .stat-number {
                font-size: 2rem;
            }
            .stat-icon {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen lg:ml-64">
        <header class="header-gradient text-white p-4 lg:p-6 shadow-xl">
            <div class="flex items-center justify-between pl-14 lg:pl-0">
                <?php include '../includes/sidebar.php'; ?>
                <div class="flex items-center gap-3">
                    <img src="../assets/LOGO.png" alt="Logo" class="h-8 w-8 sm:h-10 sm:w-10 object-contain">
                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold tracking-tight">Dashboard</h1>
                </div>
                <button onclick="openLogoutModal()" 
                   class="bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-semibold text-red-600 hover:text-red-700 hover:bg-white transition-all duration-200 text-xs sm:text-sm shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </button>
            </div>
        </header>

        <main class="p-4 lg:p-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-6 lg:mb-8">
                <div class="stat-card employees-card text-white rounded-xl p-5 lg:p-6 shadow-xl" 
                     data-chart="employees"
                     onclick="switchChart('employees')">
                    <div class="stat-icon">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="stat-title">Employees</h3>
                    <p class="stat-number"><?php echo $stats['employees']; ?></p>
                    <p class="stat-label">Total Employees</p>
                </div>

                <div class="stat-card applicants-card text-white rounded-xl p-5 lg:p-6 shadow-xl" 
                     data-chart="applicants"
                     onclick="switchChart('applicants')">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                    <h3 class="stat-title">Applicants</h3>
                    <p class="stat-number"><?php echo $stats['applicants']; ?></p>
                    <p class="stat-label">Total Applicants</p>
                </div>

                <div class="stat-card events-card text-white rounded-xl p-5 lg:p-6 shadow-xl sm:col-span-2 lg:col-span-1" 
                     data-chart="events"
                     onclick="switchChart('events')">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <h3 class="stat-title">Events</h3>
                    <p class="stat-number"><?php echo $stats['events']; ?></p>
                    <p class="stat-label">Upcoming This Month</p>
                </div>
            </div>

            <div class="chart-card p-6 lg:p-8">
                <h3 class="chart-title" id="chartTitle">NUMBER OF EMPLOYEES</h3>
                <div class="w-full overflow-x-auto">
                    <div class="min-w-[500px] chart-container">
                        <canvas id="mainChart" class="w-full"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-sign-out-alt text-xl"></i>
                    </div>
                    <h2 class="text-xl font-bold">Confirm Logout</h2>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6 text-center">Are you sure you want to logout?</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeLogoutModal()" 
                            class="px-5 py-2.5 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-all duration-200 font-medium shadow-sm hover:shadow">
                        Cancel
                    </button>
                    <a href="../logout.php" 
                       class="px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 font-medium shadow-md hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chartConfig = {
            months: <?php echo json_encode($months); ?>,
            years: <?php echo json_encode($yearsLabels); ?>,
            data: {
                employees: {
                    data: <?php echo json_encode($chartData['employees']); ?>,
                    labels: <?php echo json_encode($yearsLabels); ?>,
                    label: 'Employees Hired',
                    title: 'NUMBER OF EMPLOYEES (YEARLY)',
                    color: '#0d9488',
                    isYearly: true
                },
                applicants: {
                    data: <?php echo json_encode($chartData['applicants']); ?>,
                    labels: <?php echo json_encode($months); ?>,
                    label: 'Applicants',
                    title: 'NUMBER OF APPLICANTS',
                    color: '#0891b2',
                    isYearly: false
                },
                events: {
                    data: <?php echo json_encode($chartData['events']); ?>,
                    labels: <?php echo json_encode($months); ?>,
                    label: 'Recruitment Events',
                    title: 'NUMBER OF EVENTS',
                    color: '#059669',
                    isYearly: false
                }
            }
        };

        let currentChart = null;
        let currentChartType = 'employees';

        const getChartOptions = () => ({
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: '#64748b'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12,
                            weight: '500'
                        },
                        color: '#64748b'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        size: 13
                    },
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' ' + (context.parsed.y === 1 ? 'item' : 'items');
                        }
                    }
                }
            }
        });

        function createChart(type) {
            const ctx = document.getElementById('mainChart').getContext('2d');
            const config = chartConfig.data[type];
            
            if (currentChart) {
                currentChart.destroy();
            }

            const chartContainer = document.querySelector('.chart-container');
            chartContainer.style.opacity = '0';
            
            setTimeout(() => {
                // Use yearly labels for employees, monthly for others
                const labels = config.isYearly ? config.labels : chartConfig.months;
                
                // Create gradient for chart bars
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, config.color + 'CC');
                gradient.addColorStop(1, config.color + '66');
                
                currentChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: config.label,
                            data: config.data,
                            backgroundColor: gradient,
                            borderColor: config.color,
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                            barThickness: 'flex',
                            maxBarThickness: 50
                        }]
                    },
                    options: getChartOptions()
                });
                
                chartContainer.style.opacity = '1';
            }, 100);

            document.getElementById('chartTitle').textContent = config.title;
        }

        function switchChart(type) {
            if (type === currentChartType) return;
            
            currentChartType = type;
            
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('card-active');
            });
            document.querySelector(`[data-chart="${type}"]`).classList.add('card-active');
            
            createChart(type);
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

        document.addEventListener('DOMContentLoaded', () => {
            createChart('employees');
            document.querySelector('[data-chart="employees"]').classList.add('card-active');
        });
    </script>
</body>
</html>