<?php
/**
 * Logout Handler
 * File: logout.php
 */


session_start();


require_once 'config/database.php';


if (isset($logger) && isset($_SESSION['username'])) {
    $logger->info('AUTH', 'User logged out', 
        "Username: {$_SESSION['username']}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}


$_SESSION = array();


if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}


session_destroy();


header('Location: index.php?logout=success');
exit;