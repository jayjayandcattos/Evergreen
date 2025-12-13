<?php
/**
 * Get All Account Applications
 * Retrieves all account applications with optional filtering
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    // Optional: Check if employee is logged in
    // if (!isset($_SESSION['employee_id'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Unauthorized. Please login as an employee.'
    //     ]);
    //     exit();
    // }

    // Get filter parameters from query string
    $status = $_GET['status'] ?? '';
    $accountType = $_GET['account_type'] ?? '';
    
    // Build query
    $query = "
        SELECT 
            aa.application_id,
            aa.application_number,
            aa.application_status,
            aa.first_name,
            aa.middle_name,
            aa.last_name,
            aa.email,
            aa.phone_number,
            aa.account_type,
            aa.submitted_at
        FROM account_applications aa
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add status filter
    if (!empty($status)) {
        $query .= " AND aa.application_status = :status";
        $params[':status'] = $status;
    }
    
    // Add account type filter
    if (!empty($accountType)) {
        $query .= " AND aa.account_type = :account_type";
        $params[':account_type'] = $accountType;
    }
    
    // Order by submitted date (newest first)
    $query .= " ORDER BY aa.submitted_at DESC";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'count' => count($applications)
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-applications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get-applications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching applications',
        'error' => $e->getMessage()
    ]);
}
