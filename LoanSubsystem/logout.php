<?php
// Start the session (so we can destroy it)
session_start();

// Check if user is admin or client before destroying session
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Destroy all session data
session_destroy();

// Optional: Clear cookies if you set any (not needed for basic use)
// setcookie(session_name(), '', time() - 3600, '/');

// Redirect based on user type
if ($isAdmin) {
    // Admin: redirect to loan login (default)
    header('Location: login.php?message=logged_out');
} else {
    // Client: redirect to Basic-operation account page
    header('Location: /Evergreen/bank-system/Basic-operation/operations/public/customer/account');
}
exit(); // Always exit after redirect!
?>