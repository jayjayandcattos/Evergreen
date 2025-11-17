<?php
/**
 * Monthly Interest Application API Endpoint
 * Apply interest to all Savings accounts
 * 
 * This can be called via HTTP request or cron job
 * 
 * Usage via HTTP:
 *   GET/POST: /bank-system/Basic-operation/api/apply_interest.php
 * 
 * Cron example (runs on 1st of every month at 2 AM):
 *   0 2 1 * * curl http://localhost/bank-system/Basic-operation/api/apply_interest.php
 */

header('Content-Type: application/json');

// Optional: Add authentication/authorization check here
// if (!isset($_SERVER['HTTP_X_API_KEY']) || $_SERVER['HTTP_X_API_KEY'] !== 'your-secret-key') {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
//     exit;
// }

require_once __DIR__ . '/../config/database.php';

// Get database connection
$db = getDBConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// Load Customer model from operations
require_once __DIR__ . '/../operations/core/Database.php';
require_once __DIR__ . '/../operations/app/models/Customer.php';

// Initialize Customer model
$customerModel = new Customer();

// Calculate and apply interest
$result = $customerModel->calculateAndApplyInterest();

// Log results
if ($result['success']) {
    $log_message = sprintf(
        "[%s] Interest applied successfully. Accounts processed: %d, Total interest: PHP %.2f",
        date('Y-m-d H:i:s'),
        $result['accounts_processed'],
        $result['total_interest_applied']
    );
    
    // Log to file
    $log_file = __DIR__ . '/../logs/interest_' . date('Y-m') . '.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
    
    // Optionally send email notification to admin
    // mail('admin@bank.com', 'Monthly Interest Applied', $log_message);
} else {
    error_log("Error applying interest: " . ($result['error'] ?? 'Unknown error'));
}

// Output JSON result
echo json_encode($result, JSON_PRETTY_PRINT);

