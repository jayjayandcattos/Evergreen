<?php
/**
 * Get Application Details
 * Retrieves detailed information for a specific application
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
    // Get application ID from query parameter
    $applicationId = $_GET['id'] ?? null;
    
    if (!$applicationId) {
        echo json_encode([
            'success' => false,
            'message' => 'Application ID is required'
        ]);
        exit();
    }
    
    // Fetch application details with location names, source of funds name, and employment status name
    $stmt = $db->prepare("
        SELECT 
            aa.*,
            sof.source_name as source_of_funds_name,
            es.status_name as employment_status_name,
            p.province_name,
            c.city_name,
            b.barangay_name
        FROM account_applications aa
        LEFT JOIN source_of_funds sof ON aa.source_of_funds = sof.source_id
        LEFT JOIN employment_statuses es ON aa.employment_status = es.employment_status_id
        LEFT JOIN provinces p ON aa.province_id = p.province_id
        LEFT JOIN cities c ON aa.city_id = c.city_id
        LEFT JOIN barangays b ON aa.barangay_id = b.barangay_id
        WHERE aa.application_id = :application_id
    ");
    
    $stmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $stmt->execute();
    
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        echo json_encode([
            'success' => false,
            'message' => 'Application not found'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);

} catch (PDOException $e) {
    error_log("Database error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in get-application-details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching application details',
        'error' => $e->getMessage()
    ]);
}
