<?php
require_once '../config/database.php';
require_once '../includes/session.php';

// Log logout activity before destroying session
if (isLoggedIn()) {
    $current_user = getCurrentUser();
    logActivity('logout', 'authentication', 'User logged out successfully', $conn);
}

// Destroy session and redirect to login
destroyUserSession();
header("Location: login.php");
exit();
?>

