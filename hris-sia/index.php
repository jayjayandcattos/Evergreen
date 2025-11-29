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

if (isset($_SESSION['employee_logged_in']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: pages/employee_dashboard.php');
    exit;
}

require_once 'config/database.php';
require_once 'includes/auth.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['employee_login'])) {
    $employee_id_input = sanitize($conn, $_POST['employee_id'] ?? '');
    $employee_name = sanitize($conn, $_POST['employee_name'] ?? '');

    // Add EMP- prefix if not present (user only enters numbers)
    if (!empty($employee_id_input) && !preg_match('/^EMP-/i', $employee_id_input)) {
        $employee_id = 'EMP-' . $employee_id_input;
    } else {
        $employee_id = $employee_id_input;
    }

    if (!empty($employee_id) && !empty($employee_name)) {
        $result = loginEmployee($conn, $employee_id, $employee_name);
        if ($result['success']) {
            header('Location: pages/employee_dashboard.php');
            exit;
        } else {
            $error_message = $result['message'];
        }
    } else {
        $error_message = "Please enter both Employee ID and Employee Name";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/evergreen.svg">
    <title>HRIS - Employee Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kulim+Park:wght@400;600;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Saira:wght@600&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600&display=swap" />
</head>

<body class="gradient-bg">
    <!-- Split-Screen Layout Container -->
    <div class="split-screen-container min-h-screen">
        <!-- Left Side: Branding Section -->
        <div class="branding-section">
            <div class="branding-content">
                <div class="branding-logo-container">
                    <img src="assets/evergreen.svg" alt="EVERGREEN Logo" class="branding-logo" loading="lazy">
                </div>
                <h1 class="branding-title">EVERGREEN</h1>
                <p class="branding-tagline">Human Resources Information System</p>
                <p class="branding-welcome">Welcome back! Please login to access your employee dashboard.</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-section">
            <div class="login-card">
                <div class="login-header">
                    <div class="flex justify-center mb-2">
                        <img src="assets/evergreen.svg" alt="HRIS Logo" class="login-logo" loading="lazy">
                    </div>
                    <h2 class="login-title">Employee Login</h2>
                    <p class="login-subtitle">Login to access your employee dashboard</p>
                    <div class="login-datetime">
                        <span id="currentDate" class="datetime-date"></span>
                        <span id="currentTime" class="datetime-time"></span>
                    </div>
                </div>

                <?php if ($error_message): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php" class="login-form">
                    <input type="hidden" name="employee_login" value="1">

                    <div class="form-group">
                        <label for="employee_id" class="form-label">
                            Employee ID <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                <span class="text-gray-500 font-medium">EMP-</span>
                            </div>
                            <input
                                type="text"
                                id="employee_id"
                                name="employee_id"
                                pattern="^\d+$"
                                inputmode="numeric"
                                aria-label="Employee ID - Enter numbers only (EMP- prefix is automatic)"
                                aria-required="true"
                                aria-describedby="employee_id_help"
                                placeholder="001"
                                value="<?php 
                                    $emp_id = isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : '';
                                    // Remove EMP- prefix if present for display
                                    $emp_id = preg_replace('/^EMP-?/i', '', $emp_id);
                                    echo $emp_id;
                                ?>"
                                class="form-input pl-16"
                                required
                                autofocus
                                maxlength="3">
                        </div>
                        <p id="employee_id_help" class="form-help">Enter numbers only (e.g., 001). The "EMP-" prefix is automatic.</p>
                    </div>

                    <div class="form-group">
                        <label for="employee_name" class="form-label">
                            Employee Name <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="employee_name"
                            name="employee_name"
                            aria-label="Employee Name - Enter your full name"
                            aria-required="true"
                            aria-describedby="employee_name_help"
                            value="<?php echo isset($_POST['employee_name']) ? htmlspecialchars($_POST['employee_name']) : ''; ?>"
                            class="form-input"
                            required>
                        <p id="employee_name_help" class="form-help">Enter your first name and last name as registered</p>
                    </div>

                    <button type="submit" class="login-button">
                        Login
                    </button>
                </form>
            </div>
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
    </script>
</body>

</html>