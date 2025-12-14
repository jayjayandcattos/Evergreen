<?php
/**
 * Reject Account Application
 * Rejects a pending application with a reason
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['application_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Application ID is required'
        ]);
        exit();
    }
    
    if (!isset($data['rejection_reason']) || empty(trim($data['rejection_reason']))) {
        echo json_encode([
            'success' => false,
            'message' => 'Rejection reason is required'
        ]);
        exit();
    }
    
    $applicationId = $data['application_id'];
    $rejectionReason = trim($data['rejection_reason']);
    $employeeId = $_SESSION['employee_id'] ?? 1; // Default to employee ID 1 for testing
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // 1. Verify application exists and is pending
        $stmt = $db->prepare("
            SELECT application_id 
            FROM account_applications 
            WHERE application_id = :application_id 
            AND application_status = 'pending'
        ");
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            throw new Exception('Application not found or already processed');
        }
        
        // 2. Update application status to rejected
        $stmt = $db->prepare("
            UPDATE account_applications 
            SET application_status = 'rejected',
                rejection_reason = :rejection_reason,
                reviewed_at = NOW(),
                reviewed_by_employee_id = :employee_id
            WHERE application_id = :application_id
        ");
        
        $stmt->bindParam(':rejection_reason', $rejectionReason);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application rejected successfully',
            'rejection_reason' => $rejectionReason
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error in reject-application.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in reject-application.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
