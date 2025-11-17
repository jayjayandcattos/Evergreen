<?php
/**
 * Notifications API Endpoint
 * Fetches recent activity logs and system notifications formatted as notifications
 */

require_once '../../config/database.php';
require_once '../../includes/session.php';

// Require login
requireLogin();

// Set JSON header
header('Content-Type: application/json');

// Get current user
$current_user = getCurrentUser();
$user_id = $current_user['id'];

// Get limit parameter (default 15 to include both types)
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

try {
    // Ensure tables exist
    ensureTablesExist();
    
    $notifications = [];
    
    // Get activity logs (user actions)
    $activity_logs = getActivityLogs($limit);
    foreach ($activity_logs as $log) {
        $notification = formatActivityLogNotification($log);
        if ($notification) {
            $notifications[] = $notification;
        }
    }
    
    // Get system notifications (external system alerts)
    $system_notifications = getSystemNotifications($limit);
    foreach ($system_notifications as $notif) {
        $notification = formatSystemNotification($notif);
        if ($notification) {
            $notifications[] = $notification;
        }
    }
    
    // Sort by timestamp (most recent first)
    usort($notifications, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Limit to requested number
    $notifications = array_slice($notifications, 0, $limit);
    
    // Return response
    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Notifications API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'count' => 0,
        'notifications' => [],
        'message' => 'Error loading notifications: ' . $e->getMessage()
    ]);
}

/**
 * Ensure required tables exist
 */
function ensureTablesExist() {
    global $conn;
    
    // Create activity_logs table if it doesn't exist
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
            throw new Exception("Failed to create activity_logs table: " . $conn->error);
        }
    }
    
    // Create system_notifications table if it doesn't exist
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
            throw new Exception("Failed to create system_notifications table: " . $conn->error);
        }
    }
}

/**
 * Get activity logs
 */
function getActivityLogs($limit) {
    global $conn;
    
    $sql = "SELECT 
                al.id,
                al.user_id,
                al.action,
                al.module,
                al.details,
                al.ip_address,
                al.created_at,
                u.full_name as user_name,
                u.username
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    $stmt->close();
    return $logs;
}

/**
 * Get system notifications
 */
function getSystemNotifications($limit) {
    global $conn;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'system_notifications'");
    if ($table_check->num_rows == 0) {
        return [];
    }
    
    $sql = "SELECT 
                id,
                notification_type,
                title,
                message,
                priority,
                status,
                related_module,
                related_id,
                created_at
            FROM system_notifications
            WHERE status != 'archived'
            ORDER BY 
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    return $notifications;
}

/**
 * Format activity log entry as notification
 */
function formatActivityLogNotification($log) {
    if (empty($log['module']) || empty($log['action'])) {
        return null;
    }
    
    $action = strtolower($log['action']);
    $module = strtolower($log['module']);
    
    // Determine icon and color based on action
    $icon_class = 'fa-info-circle';
    $color_class = 'text-info';
    
    switch ($action) {
        case 'create':
        case 'insert':
            $icon_class = 'fa-plus-circle';
            $color_class = 'text-success';
            break;
        case 'update':
        case 'edit':
            $icon_class = 'fa-edit';
            $color_class = 'text-info';
            break;
        case 'delete':
        case 'remove':
            $icon_class = 'fa-trash-alt';
            $color_class = 'text-danger';
            break;
        case 'approve':
        case 'approved':
            $icon_class = 'fa-check-circle';
            $color_class = 'text-success';
            break;
        case 'reject':
        case 'rejected':
            $icon_class = 'fa-times-circle';
            $color_class = 'text-danger';
            break;
        case 'submit':
        case 'submitted':
            $icon_class = 'fa-paper-plane';
            $color_class = 'text-info';
            break;
        case 'pending':
            $icon_class = 'fa-clock';
            $color_class = 'text-warning';
            break;
        case 'warning':
        case 'alert':
            $icon_class = 'fa-exclamation-triangle';
            $color_class = 'text-warning';
            break;
        default:
            $icon_class = 'fa-info-circle';
            $color_class = 'text-info';
    }
    
    // Format title based on module and action
    $title = ucfirst($action);
    if ($module) {
        $module_display = ucfirst(str_replace('_', ' ', $module));
        $title = $module_display . ' - ' . ucfirst($action);
    }
    
    // Format details
    $details = $log['details'] ?? 'No details available';
    if (strlen($details) > 60) {
        $details = substr($details, 0, 60) . '...';
    }
    
    // Format time
    $time_ago = getTimeAgo($log['created_at']);
    
    return [
        'id' => 'activity_' . $log['id'],
        'type' => 'activity',
        'icon' => $icon_class,
        'color' => $color_class,
        'title' => $title,
        'details' => $details,
        'time_ago' => $time_ago,
        'timestamp' => $log['created_at'],
        'user' => $log['user_name'] ?? 'System',
        'module' => $module,
        'action' => $action
    ];
}

/**
 * Format system notification
 */
function formatSystemNotification($notif) {
    $type = strtolower($notif['notification_type']);
    $priority = strtolower($notif['priority']);
    
    // Determine icon and color based on notification type and priority
    $icon_class = 'fa-info-circle';
    $color_class = 'text-info';
    
    switch ($type) {
        case 'banking':
            $icon_class = 'fa-university';
            $color_class = $priority === 'urgent' ? 'text-danger' : ($priority === 'high' ? 'text-warning' : 'text-info');
            break;
        case 'payment':
            $icon_class = 'fa-money-bill-wave';
            $color_class = 'text-success';
            break;
        case 'reconciliation':
            $icon_class = 'fa-balance-scale';
            $color_class = 'text-warning';
            break;
        case 'alert':
            $icon_class = 'fa-exclamation-triangle';
            $color_class = $priority === 'urgent' ? 'text-danger' : 'text-warning';
            break;
        case 'system':
            $icon_class = 'fa-cog';
            $color_class = 'text-info';
            break;
        case 'transaction':
            $icon_class = 'fa-exchange-alt';
            $color_class = $priority === 'urgent' ? 'text-danger' : 'text-info';
            break;
        default:
            $icon_class = 'fa-bell';
            $color_class = 'text-info';
    }
    
    // Format time
    $time_ago = getTimeAgo($notif['created_at']);
    
    return [
        'id' => 'system_' . $notif['id'],
        'type' => 'system',
        'icon' => $icon_class,
        'color' => $color_class,
        'title' => $notif['title'],
        'details' => $notif['message'],
        'time_ago' => $time_ago,
        'timestamp' => $notif['created_at'],
        'priority' => $priority,
        'notification_type' => $type,
        'related_module' => $notif['related_module'] ?? null
    ];
}

/**
 * Get time ago string
 */
function getTimeAgo($datetime) {
    if (empty($datetime)) {
        return 'Recently';
    }
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}
?>

