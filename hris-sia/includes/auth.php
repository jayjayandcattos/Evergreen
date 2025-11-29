<?php

// Set Philippine timezone
date_default_timezone_set('Asia/Manila');

function loginUser($conn, $username, $password) {
    try {
       
        $sql = "SELECT ua.user_id, ua.username, ua.password_hash, ua.role, ua.employee_id,
                       e.first_name, e.last_name
                FROM user_account ua
                LEFT JOIN employee e ON ua.employee_id = e.employee_id
                WHERE ua.username = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        if (!$user) {
            logLoginAttempt($conn, $username, false, 'User not found');
            return [
                "success" => false, 
                "message" => "Invalid username or password"
            ];
        }
        
        
        if (!password_verify($password, $user['password_hash'])) {
            logLoginAttempt($conn, $username, false, 'Invalid password');
            return [
                "success" => false, 
                "message" => "Invalid username or password"
            ];
        }
        
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['employee_id'] = $user['employee_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = trim(($user['first_name'] ?? 'Justin') . ' ' . ($user['last_name'] ?? 'Rivera'));
        $_SESSION['is_admin'] = ($user['role'] === 'Admin');
        $_SESSION['logged_in'] = true;
        
        session_regenerate_id(true);
        
        
        $updateSql = "UPDATE user_account SET last_login = NOW() WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([$user['user_id']]);
        
        logLoginAttempt($conn, $username, true);
        
        return [
            "success" => true, 
            "user" => $user
        ];
        
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        return [
            "success" => false, 
            "message" => "System error occurred"
        ];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function logoutUser() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
    header('Location: index.php');
    exit;
}

function recordTimeIn($conn, $employee_id) {
    try {
        // Check if employee already has a time-in today without time-out
        $checkSql = "SELECT attendance_id FROM attendance 
                     WHERE employee_id = ? 
                     AND DATE(time_in) = CURDATE() 
                     AND time_out IS NULL";
        
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([$employee_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            return [
                "success" => false, 
                "message" => "You are already clocked in. Please clock out first."
            ];
        }
        
        // Get current Philippine time
        $current_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
        
        // Insert new time-in record
        $sql = "INSERT INTO attendance (employee_id, date, time_in, status) 
                VALUES (?, ?, ?, 'Present')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $employee_id,
            $current_time->format('Y-m-d'),
            $current_time->format('Y-m-d H:i:s')
        ]);
        
        return [
            "success" => true, 
            "message" => "Clocked in successfully at " . $current_time->format('h:i A')
        ];
        
    } catch (Exception $e) {
        error_log("Time-in Error: " . $e->getMessage());
        return [
            "success" => false, 
            "message" => "Failed to record time-in"
        ];
    }
}

function recordTimeOut($conn, $employee_id) {
    try {
        // Find the active time-in record (no time-out yet)
        $checkSql = "SELECT attendance_id, time_in 
                     FROM attendance 
                     WHERE employee_id = ? 
                     AND time_out IS NULL 
                     ORDER BY time_in DESC 
                     LIMIT 1";
        
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([$employee_id]);
        $active_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$active_attendance) {
            return [
                "success" => false, 
                "message" => "No active clock-in found. Please clock in first."
            ];
        }
        
        // Get current Philippine time
        $current_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
        
        // Update the record with time-out
        $updateSql = "UPDATE attendance SET time_out = ? WHERE attendance_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([
            $current_time->format('Y-m-d H:i:s'),
            $active_attendance['attendance_id']
        ]);
        
        // Calculate hours worked - FIXED: Use actual time_in from database
        $time_in = new DateTime($active_attendance['time_in'], new DateTimeZone('Asia/Manila'));
        $time_out = clone $current_time;
        
        // Get total seconds difference
        $total_seconds = $time_out->getTimestamp() - $time_in->getTimestamp();
        $total_minutes = floor($total_seconds / 60);
        $hours = floor($total_minutes / 60);
        $minutes = $total_minutes % 60;
        
        // Handle edge case for very short duration
        if ($total_seconds < 60) {
            return [
                "success" => true, 
                "message" => sprintf(
                    "Clocked out at %s. You worked for less than a minute.",
                    $current_time->format('h:i A')
                ),
                "hours_worked" => 0
            ];
        }
        
        return [
            "success" => true, 
            "message" => sprintf(
                "Clocked out at %s. You worked for %d hour%s and %d minute%s.",
                $current_time->format('h:i A'),
                $hours,
                $hours != 1 ? 's' : '',
                $minutes,
                $minutes != 1 ? 's' : ''
            ),
            "hours_worked" => round($total_minutes / 60, 2)
        ];
        
    } catch (Exception $e) {
        error_log("Time-out Error: " . $e->getMessage());
        return [
            "success" => false, 
            "message" => "Failed to record time-out"
        ];
    }
}

function logLoginAttempt($conn, $username, $success, $reason = null) {
    try {
        $sql = "INSERT INTO login_attempts (username, ip_address, success, failure_reason) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $username,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $success ? 1 : 0,
            $reason
        ]);
    } catch (Exception $e) {
        error_log("Log Attempt Error: " . $e->getMessage());
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}

function isHRManager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'HR Manager';
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function canEdit() {
    return isAdmin();
}

function canManageEmployees() {
    return isAdmin() || isHRManager();
}

function canManageLeaves() {
    return isAdmin() || isHRManager();
}

function canManageRecruitment() {
    return isAdmin() || isHRManager();
}

function canViewLogs() {
    return isAdmin();
}
?>