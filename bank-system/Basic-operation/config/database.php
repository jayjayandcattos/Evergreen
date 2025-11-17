<?php
/**
 * Database Configuration and Connection
 * For XAMPP/phpMyAdmin setup
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Default XAMPP username
define('DB_PASS', '');               // Default XAMPP password (empty)
define('DB_NAME', 'BankingDB');
define('DB_CHARSET', 'utf8mb4');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Create database connection using PDO
 * @return PDO|null Returns PDO object on success, null on failure
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Test database connection
 * @return array Status information
 */
function testConnection() {
    $pdo = getDBConnection();
    if ($pdo) {
        return [
            'status' => 'success',
            'message' => 'Database connection successful'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to connect to database'
        ];
    }
}

// Create a global connection instance
$db = getDBConnection();

if (!$db) {
    die("Database connection failed. Please check your configuration.");
}