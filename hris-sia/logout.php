<?php
/**
 * Logout Handler
 * File: logout.php
 * FIXED: Now properly records time-out for employees before logout
 */

session_start();

require_once 'config/database.php';
require_once 'includes/auth.php';

// CRITICAL FIX: Call logoutUser() which records time-out for employees
// This ensures time-out is recorded in attendance table before session is destroyed
logoutUser();

// Note: logoutUser() already handles session destruction and redirect
// This code below won't execute, but kept for safety
exit;