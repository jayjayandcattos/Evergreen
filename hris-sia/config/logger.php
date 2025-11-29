<?php
/**
 * Logger Class - Central logging system for HRIS
 * File: config/Logger.php
 */

class Logger {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Main logging method
     */
    public function log($level, $type, $action, $details = null, $requestData = null) {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $employeeId = $_SESSION['employee_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $sql = "INSERT INTO system_logs 
                    (log_level, log_type, user_id, employee_id, ip_address, 
                     user_agent, action, details, request_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $requestJson = $requestData ? json_encode($requestData) : null;
            
            $stmt->execute([
                $level, $type, $userId, $employeeId, $ipAddress,
                $userAgent, $action, $details, $requestJson
            ]);
            
            return true;
        } catch (PDOException $e) {
            // Fallback to error log if database fails
            error_log("Logger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Convenience methods for different log levels
     */
    public function debug($type, $action, $details = null, $data = null) {
        return $this->log('DEBUG', $type, $action, $details, $data);
    }
    
    public function info($type, $action, $details = null, $data = null) {
        return $this->log('INFO', $type, $action, $details, $data);
    }
    
    public function warning($type, $action, $details = null, $data = null) {
        return $this->log('WARNING', $type, $action, $details, $data);
    }
    
    public function error($type, $action, $details = null, $data = null) {
        return $this->log('ERROR', $type, $action, $details, $data);
    }
    
    public function critical($type, $action, $details = null, $data = null) {
        return $this->log('CRITICAL', $type, $action, $details, $data);
    }
    
    /**
     * Log login attempts
     */
    public function logLoginAttempt($username, $success, $failureReason = null) {
        try {
            $sql = "INSERT INTO login_attempts 
                    (username, ip_address, success, failure_reason) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $username,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $success ? 1 : 0,
                $failureReason
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Login Attempt Log Error: " . $e->getMessage());
            return false;
        }
    }
}
?>