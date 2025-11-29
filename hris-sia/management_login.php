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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/evergreen.svg">
    <title>HRIS - Management Login</title>
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
                <p class="branding-welcome">Welcome back! Please login to access the management dashboard.</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="login-section">
            <div class="login-card">
                <div class="login-header">
                    <div class="flex justify-center mb-3 sm:mb-4">
                        <img src="assets/evergreen.svg" alt="HRIS Logo" class="login-logo" loading="lazy">
                    </div>
                    <h2 class="login-title">Management Login</h2>
                    <p class="login-subtitle">Access the HRIS management dashboard</p>
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

                <form method="POST" action="management_login.php" class="login-form">
                    <input type="hidden" name="admin_login" value="1">

                    <div class="form-group">
                        <label for="username" class="form-label">
                            Username <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            aria-label="Management Username"
                            aria-required="true"
                            aria-describedby="username_help"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            class="form-input"
                            required
                            autofocus
                            autocomplete="username">
                        <p id="username_help" class="form-help">Enter your management username</p>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            Password <span class="text-red-500" aria-label="required">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            aria-label="Management Password"
                            aria-required="true"
                            aria-describedby="password_help"
                            class="form-input"
                            required
                            autocomplete="current-password">
                        <p id="password_help" class="form-help">Enter your management password</p>
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

