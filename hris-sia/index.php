<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: pages/dashboard.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/auth.php';

$error_message = '';
$success_message = '';
$show_admin_form = false;
$auto_redirect = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['admin_login'])) {
        $show_admin_form = true;
        $username = sanitize($conn, $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            $result = loginUser($conn, $username, $password);
            if ($result['success']) {
                header('Location: pages/dashboard.php');
                exit;
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Please enter both username and password";
        }
    } elseif (isset($_POST['employee_action'])) {
        $employee_number = sanitize($conn, $_POST['employee_number'] ?? '');

        if (!empty($employee_number)) {
            $sql = "SELECT employee_id, first_name, last_name FROM employee WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$employee_number]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                $check_sql = "SELECT attendance_id, time_in FROM attendance 
                             WHERE employee_id = ? AND time_out IS NULL 
                             ORDER BY time_in DESC LIMIT 1";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->execute([$employee['employee_id']]);
                $active_attendance = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($active_attendance) {
                    $result = recordTimeOut($conn, $employee['employee_id']);
                    if ($result['success']) {
                        $success_message = "Goodbye, " . $employee['first_name'] . " " . $employee['last_name'] . "! " . $result['message'];
                        $auto_redirect = true;
                    } else {
                        $error_message = $result['message'];
                    }
                } else {
                    $result = recordTimeIn($conn, $employee['employee_id']);
                    if ($result['success']) {
                        $success_message = "Welcome, " . $employee['first_name'] . " " . $employee['last_name'] . "! " . $result['message'];
                        $auto_redirect = true;
                    } else {
                        $error_message = $result['message'];
                    }
                }
            } else {
                $error_message = "Employee ID not found";
            }
        } else {
            $error_message = "Please enter your employee number";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/evergreen.svg">
    <title>HRIS - Employee Time-In/Out</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Saira:wght@600&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600&display=swap" />
    <style>
        .gradient-bg {
            background: #003631;
        }
    </style>
</head>

<body class="gradient-bg">
    <div class="header fixed top-0 left-0 right-0 z-50">
        <div class="navbar">
            <div class="nav-content flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-4 px-3 py-2 sm:px-6 sm:py-3">
                <div class="nav-left flex items-center w-full sm:w-auto justify-center sm:justify-start">
                    <img class="logo-nav dark w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 lg:w-14 lg:h-14" src="assets/evergreen.svg" alt="EVERGREEN LOGO">
                    </a>
                    <h1 class="text-xl sm md:text-3xl" style="padding: 5px;">EVERGREEN</h1>
                </div>

                <div class="nav-right hidden sm:flex items-center w-full sm:w-auto justify-center sm:justify-end">
                    <div class="date">
                        <div class="date1 text-xs sm:text-sm md:text-base lg:text-lg" id="currentDate">Date</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-center min-h-screen pt-16 sm:pt-20 pb-4 px-3 sm:px-4">
        <div id="employeeForm" class="bg-white rounded-lg shadow-2xl p-4 sm:p-6 md:p-8 w-full max-w-[95%] sm:max-w-md <?php echo $show_admin_form ? 'hidden' : ''; ?>">
            <div class="flex justify-center mb-4 sm:mb-6">
                <img src="assets/evergreen.svg" alt="HRIS Logo" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-full" loading="lazy">
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-1 sm:mb-2">Employee Attendance</h2>
            <p class="text-center text-gray-600 text-xs sm:text-sm mb-6 sm:mb-8 px-2">Enter your employee number to clock in or out</p>

            <?php if ($success_message && !$show_admin_form): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 sm:px-4 sm:py-3 rounded mb-4 text-center text-sm sm:text-base">
                    <?php echo htmlspecialchars($success_message); ?>
                    <div class="mt-2 text-xs sm:text-sm">
                        Returning to form in <span id="countdown" class="font-bold">3</span> seconds...
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message && !$show_admin_form): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 sm:px-4 sm:py-3 rounded mb-4 text-center text-sm sm:text-base">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" id="attendanceForm">
                <input type="hidden" name="employee_action" value="1">

                <div class="mb-4 sm:mb-6">
                    <input
                        type="text"
                        name="employee_number"
                        id="employee_number"
                        placeholder="Employee Number"
                        class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                        required
                        autofocus
                        <?php echo $auto_redirect ? 'disabled' : ''; ?>>
                </div>

                <button
                    type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 sm:py-3 text-sm sm:text-base rounded-lg transition duration-200"
                    <?php echo $auto_redirect ? 'disabled' : ''; ?>>
                    Clock In / Out
                </button>
            </form>

            <p class="text-center text-gray-600 text-xs sm:text-sm mt-4 sm:mt-6">
                Current Time: <span id="currentTime" class="font-semibold"></span>
            </p>

            <div id="secretTrigger" class="text-center mt-2 cursor-pointer select-none opacity-0 hover:opacity-10 transition-opacity">
                <p class="text-xs text-gray-400">v1.0</p>
            </div>
        </div>

        <div id="adminForm" class="form-container w-full max-w-[95%] sm:max-w-md bg-white rounded-lg shadow-2xl p-4 sm:p-6 md:p-8 <?php echo $show_admin_form ? '' : 'hidden'; ?>">
            <div class="flex justify-center mb-4 sm:mb-6">
                <img src="assets/LOGO.png" alt="HRIS Logo" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-full" loading="lazy">
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-center text-gray-800 mb-6 sm:mb-8">Management Login</h2>

            <?php if ($error_message && $show_admin_form): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 sm:px-4 sm:py-3 rounded mb-4 text-center text-sm sm:text-base">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <input type="hidden" name="admin_login" value="1">

                <div class="mb-4 sm:mb-6">
                    <input
                        type="text"
                        name="username"
                        placeholder="Username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                        required
                        autofocus>
                </div>

                <div class="mb-4 sm:mb-6">
                    <input
                        type="password"
                        name="password"
                        placeholder="Password"
                        class="w-full px-3 py-2 sm:px-4 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                        required>
                </div>

                <button
                    type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 sm:py-3 text-sm sm:text-base rounded-lg transition duration-200">
                    Login
                </button>
            </form>

            <button
                onclick="toggleForms()"
                class="mt-4 w-full text-gray-600 text-xs sm:text-sm hover:text-gray-800 transition-colors">
                ← Back to Employee Time-In
            </button>
        </div>
    </div>

    <script src="js/login.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const date = now.toLocaleDateString(undefined, options);
            const time = now.toLocaleTimeString();

            const dateEl = document.getElementById('currentDate');
            const timeEl = document.getElementById('currentTime');
            if (dateEl) dateEl.textContent = date;
            if (timeEl) timeEl.textContent = time;
        }

        setInterval(updateDateTime, 1000);
        updateDateTime();

        <?php if ($auto_redirect): ?>
            let timeLeft = 3;
            const countdownEl = document.getElementById('countdown');

            const countdown = setInterval(() => {
                timeLeft--;
                if (countdownEl) countdownEl.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'index.php';
                }
            }, 1000);
        <?php endif; ?>
    </script>
</body>

</html>