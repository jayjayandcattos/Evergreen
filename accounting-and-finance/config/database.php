<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty
define('DB_NAME', 'BankingDB');
define('DB_PORT', '3306'); // Default MySQL port

// Create global connection with error handling
try {
    // First, try to connect to MySQL server (without database)
    $temp_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
    
    if ($temp_conn->connect_error) {
        throw new Exception("Connection to MySQL server failed: " . $temp_conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    
    if ($temp_conn->query($create_db_sql) !== TRUE) {
        $temp_conn->close();
        throw new Exception("Error creating database: " . $temp_conn->error);
    }
    
    // Close temporary connection
    $temp_conn->close();
    
    // Now connect to the specific database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // Check if connection to database succeeded
    if ($conn->connect_error) {
        throw new Exception("Connection to database failed: " . $conn->connect_error);
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

// Auto-run database migrations if needed (with error handling)
try {
    if (file_exists(__DIR__ . '/../database/AutoMigration.php')) {
        require_once __DIR__ . '/../database/AutoMigration.php';
        AutoMigration::runIfNeeded($conn);
    }
} catch (Exception $e) {
    // Log migration errors but don't break the application
    error_log("AutoMigration error: " . $e->getMessage());
}

// Create connection function (for backward compatibility)
function getDBConnection() {
    global $conn;
    
    // Check if connection exists and is valid
    if (!isset($conn) || !$conn || $conn->connect_error) {
        // Try to reconnect if connection is invalid
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            if ($conn->connect_error) {
                error_log("Database reconnection failed: " . $conn->connect_error);
                return null;
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database reconnection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
