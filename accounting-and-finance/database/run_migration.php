<?php
/**
 * Migration Runner API
 * Handles AJAX requests for running database migrations
 */

require_once '../config/database.php';
require_once '../database/AutoMigration.php';

header('Content-Type: application/json');

// Check if user is admin (implement proper admin check)
$isAdmin = true; // For demo purposes

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. Admin privileges required.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'run_migrations':
            $result = AutoMigration::forceRun($conn);
            echo json_encode($result);
            break;
            
        case 'get_status':
            $status = AutoMigration::getStatus($conn);
            echo json_encode($status);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
