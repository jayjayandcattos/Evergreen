<?php
// Start the session (so we can destroy it)
session_start();

// Destroy all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to marketing home page (viewingpage.php will redirect to login if needed)
header('Location: /Evergreen/bank-system/evergreen-marketing/viewingpage.php');
exit();
?>