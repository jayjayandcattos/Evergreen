<?php
/**
 * Report Settings API Endpoint
 * Handles AJAX requests for managing report settings
 */

// Start output buffering to catch any errors
ob_start();

try {
    require_once '../../config/database.php';
    
    // Set JSON header first
    header('Content-Type: application/json');

    // For testing, use default user ID (in production, check session)
    $current_user_id = 1;
    
    // Try to get session if available
    if (file_exists('../../includes/session.php')) {
        require_once '../../includes/session.php';
        if (function_exists('isLoggedIn') && isLoggedIn()) {
            $current_user_id = $_SESSION['user_id'] ?? 1;
        }
    }

    // Get request parameters
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    // Clear any output that might have been generated
    ob_clean();

    switch ($action) {
        case 'get_settings':
            $response = getReportSettings($conn);
            break;
            
        case 'save_settings':
            $settings = $_POST['settings'] ?? [];
            $response = saveReportSettings($conn, $settings, $current_user_id);
            break;
            
        case 'reset_settings':
            $response = resetReportSettings($conn, $current_user_id);
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clear any output
    ob_clean();
    
    // Return JSON error
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} catch (Error $e) {
    // Clear any output
    ob_clean();
    
    // Return JSON error
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

// End output buffering
ob_end_flush();

/**
 * Get all report settings
 */
function getReportSettings($conn) {
    $sql = "SELECT setting_key, setting_value, setting_type, description FROM report_settings ORDER BY setting_key";
    $result = $conn->query($sql);
    
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $value = $row['setting_value'];
        
        // Convert value based on type
        switch ($row['setting_type']) {
            case 'boolean':
                $value = $value === 'true' || $value === '1';
                break;
            case 'number':
                $value = (float) $value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
        }
        
        $settings[$row['setting_key']] = [
            'value' => $value,
            'type' => $row['setting_type'],
            'description' => $row['description']
        ];
    }
    
    return [
        'success' => true,
        'settings' => $settings
    ];
}

/**
 * Save report settings
 */
function saveReportSettings($conn, $settings, $user_id) {
    $conn->begin_transaction();
    
    try {
        foreach ($settings as $key => $value) {
            // Convert value to string for storage
            $stringValue = '';
            switch (gettype($value)) {
                case 'boolean':
                    $stringValue = $value ? 'true' : 'false';
                    break;
                case 'array':
                    $stringValue = json_encode($value);
                    break;
                default:
                    $stringValue = (string) $value;
            }
            
            // Update or insert setting
            $sql = "INSERT INTO report_settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value), 
                    updated_at = NOW()";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            $stmt->bind_param('ss', $key, $stringValue);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
            $stmt->close();
        }
        
        // Log the action
        logSettingsAction($conn, 'save_settings', $user_id, $settings);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Settings saved successfully',
            'saved_count' => count($settings)
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Reset settings to defaults
 */
function resetReportSettings($conn, $user_id) {
    $conn->begin_transaction();
    
    try {
        // Delete all current settings
        $conn->query("DELETE FROM report_settings");
        
        // Re-insert default settings
        $defaultSettings = [
            ['default_period', 'Monthly', 'string', 'Default report period (Monthly, Quarterly, Yearly)'],
            ['default_format', 'PDF', 'string', 'Default report format (PDF, Excel, CSV)'],
            ['company_name', 'Evergreen', 'string', 'Company name for reports'],
            ['fiscal_year_end', '2025-12-31', 'string', 'Fiscal year end date'],
            ['footer_text', 'This report was generated by Evergreen Accounting System', 'string', 'Custom footer text for reports'],
            ['auto_monthly', 'true', 'boolean', 'Enable monthly automated reports'],
            ['auto_quarterly', 'false', 'boolean', 'Enable quarterly automated reports'],
            ['auto_yearend', 'true', 'boolean', 'Enable year-end automated reports'],
            ['email_notifications', 'true', 'boolean', 'Enable email notifications for automated reports'],
            ['report_retention_days', '365', 'number', 'Number of days to retain generated reports']
        ];
        
        foreach ($defaultSettings as $setting) {
            $insertSql = "INSERT INTO report_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param('ssss', $setting[0], $setting[1], $setting[2], $setting[3]);
            $stmt->execute();
            $stmt->close();
        }
        
        // Log the action
        logSettingsAction($conn, 'reset_settings', $user_id, []);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Settings reset to defaults successfully'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Log settings actions
 */
function logSettingsAction($conn, $action, $user_id, $data) {
    try {
        // Check if audit_logs table exists
        $result = $conn->query("SHOW TABLES LIKE 'audit_logs'");
        if ($result->num_rows === 0) {
            // Table doesn't exist, skip logging
            return;
        }
        
        $sql = "INSERT INTO audit_logs (user_id, action, object_type, object_id, details, ip_address) VALUES (?, ?, 'report_settings', 0, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // If prepare fails, skip logging
            return;
        }
        
        $details = json_encode([
            'action' => $action,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->bind_param('isss', $user_id, $action, $details, $ip_address);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // If logging fails, don't throw error - just skip it
        error_log("Failed to log settings action: " . $e->getMessage());
    }
}
?>
