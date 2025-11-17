<?php
/**
 * Database Configuration - FIXED
 * File: config/database.php
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'BankingDB');

// Create PDO connection
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Connection failed. Please contact administrator.");
}

/**
 * Helper function to execute queries
 */
function executeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to fetch all results
 */
function fetchAll($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch All Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Helper function to fetch single row
 */
function fetchOne($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Fetch One Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input data
 */
function sanitize($conn, $data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Initialize global logger variable (set to null if Logger doesn't exist)
$logger = null;

// Optional: Initialize Logger (disable if causing issues)
if (file_exists(__DIR__ . '/Logger.php')) {
    try {
        require_once __DIR__ . '/Logger.php';
        $logger = new Logger($conn);
    } catch (Exception $e) {
        error_log("Logger init failed: " . $e->getMessage());
        $logger = null;
    }
}
?>