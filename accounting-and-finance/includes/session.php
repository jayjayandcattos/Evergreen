<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? ''
        ];
    }
    return null;
}

// Function to set user session
function setUserSession($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['email'] = $user_data['email'];
    $_SESSION['full_name'] = $user_data['full_name'];
}

// Function to destroy session
function destroyUserSession() {
    session_unset();
    session_destroy();
}

/**
 * Log user activity to activity_logs table
 * @param string $action - The action performed (e.g., 'login', 'create', 'update', 'delete')
 * @param string $module - The module where action occurred (e.g., 'general_ledger', 'expense_tracking')
 * @param string $details - Additional details about the action
 * @param mysqli $conn - Database connection (optional, will use global if not provided)
 * @return bool - True if logged successfully, false otherwise
 */
function logActivity($action, $module, $details = '', $conn = null) {
    global $conn;
    
    // If no connection provided, try to get it
    if (!$conn) {
        // Try to include database config if not already included
        if (!function_exists('getDBConnection')) {
            $db_config_path = __DIR__ . '/../config/database.php';
            if (file_exists($db_config_path)) {
                require_once $db_config_path;
                $conn = $GLOBALS['conn'] ?? null;
            }
        } else {
            $conn = getDBConnection();
        }
    }
    
    if (!$conn) {
        return false;
    }
    
    // Get current user
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        return false;
    }
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    try {
        // Check if table exists, create if not
        $table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
        if ($table_check->num_rows == 0) {
            $create_table_sql = "CREATE TABLE IF NOT EXISTS activity_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                module VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_module (module),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            if (!$conn->query($create_table_sql)) {
                error_log("Failed to create activity_logs table: " . $conn->error);
                return false;
            }
        }
        
        // Insert activity log
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, module, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare activity log statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param('issss', $user_id, $action, $module, $details, $ip_address);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a system notification (for external systems like banking, payments, etc.)
 * @param string $type - Notification type: 'banking', 'payment', 'reconciliation', 'alert', 'system', 'transaction'
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param string $priority - Priority: 'low', 'medium', 'high', 'urgent'
 * @param string $related_module - Related module (optional)
 * @param string $related_id - Related record ID (optional)
 * @param array $metadata - Additional metadata as array (optional)
 * @param mysqli $conn - Database connection (optional)
 * @return bool - True if created successfully, false otherwise
 */
function createSystemNotification($type, $title, $message, $priority = 'medium', $related_module = null, $related_id = null, $metadata = null, $conn = null) {
    global $conn;
    
    // If no connection provided, try to get it
    if (!$conn) {
        $db_config_path = __DIR__ . '/../config/database.php';
        if (file_exists($db_config_path)) {
            require_once $db_config_path;
            $conn = $GLOBALS['conn'] ?? null;
        }
    }
    
    if (!$conn) {
        return false;
    }
    
    try {
        // Check if table exists, create if not
        $table_check = $conn->query("SHOW TABLES LIKE 'system_notifications'");
        if ($table_check->num_rows == 0) {
            $create_table_sql = "CREATE TABLE IF NOT EXISTS system_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notification_type ENUM('banking', 'payment', 'reconciliation', 'alert', 'system', 'transaction') NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                status ENUM('unread', 'read', 'archived') DEFAULT 'unread',
                related_module VARCHAR(100),
                related_id VARCHAR(100),
                metadata JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                read_at DATETIME NULL,
                INDEX idx_type (notification_type),
                INDEX idx_status (status),
                INDEX idx_priority (priority),
                INDEX idx_created_at (created_at),
                INDEX idx_related (related_module, related_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            if (!$conn->query($create_table_sql)) {
                error_log("Failed to create system_notifications table: " . $conn->error);
                return false;
            }
        }
        
        // Validate type
        $valid_types = ['banking', 'payment', 'reconciliation', 'alert', 'system', 'transaction'];
        if (!in_array($type, $valid_types)) {
            $type = 'system';
        }
        
        // Validate priority
        $valid_priorities = ['low', 'medium', 'high', 'urgent'];
        if (!in_array($priority, $valid_priorities)) {
            $priority = 'medium';
        }
        
        // Prepare metadata JSON
        $metadata_json = null;
        if ($metadata !== null && is_array($metadata)) {
            $metadata_json = json_encode($metadata);
        }
        
        // Insert notification
        $stmt = $conn->prepare("INSERT INTO system_notifications (notification_type, title, message, priority, related_module, related_id, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Failed to prepare system notification statement: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param('sssssss', $type, $title, $message, $priority, $related_module, $related_id, $metadata_json);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to create system notification: " . $e->getMessage());
        return false;
    }
}

