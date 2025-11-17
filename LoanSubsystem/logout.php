<?php
// Start the session (so we can destroy it)
session_start();

// Destroy all session data
session_destroy();

// Optional: Clear cookies if you set any (not needed for basic use)
// setcookie(session_name(), '', time() - 3600, '/');

// Redirect to login page with a success message
header('Location: login.php?message=logged_out');
exit(); // Always exit after redirect!
?>