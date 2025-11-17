<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'BankingDB');
define('DB_PORT', '3306'); // Default MySQL port

// Create global connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        // Try alternative connection methods
        if ($conn->connect_error) {
            // Try connecting without database first
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
            if ($conn->query($sql) === TRUE) {
                $conn->select_db(DB_NAME);
            } else {
                throw new Exception("Error creating database: " . $conn->error);
            }
        }
    }
} catch (Exception $e) {
    // Show user-friendly error message
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;'>
        <h2 style='color: #d32f2f;'>Database Connection Error</h2>
        <p><strong>Error:</strong> " . $e->getMessage() . "</p>
        <h3>To fix this issue:</h3>
        <ol>
            <li>Open <strong>XAMPP Control Panel</strong> (as Administrator)</li>
            <li>Start <strong>MySQL</strong> service</li>
            <li>Start <strong>Apache</strong> service</li>
            <li>Refresh this page</li>
        </ol>
        <p><strong>Alternative:</strong> If MySQL won't start, check the XAMPP logs for port conflicts.</p>
        <p style='margin-top: 20px; font-size: 12px; color: #666;'>
            If the problem persists, please check your XAMPP installation and ensure no other applications are using port 3306.
        </p>
    </div>
    ");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Auto-run database migrations if needed
require_once __DIR__ . '/../database/AutoMigration.php';
AutoMigration::runIfNeeded($conn);

// Create connection function (for backward compatibility)
function getDBConnection() {
    global $conn;
    return $conn;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
