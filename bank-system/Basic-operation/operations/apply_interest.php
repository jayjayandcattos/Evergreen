<?php
/**
 * Monthly Interest Application Script
 * Run this script monthly (via cron job) to apply interest to all Savings accounts
 * 
 * Usage:
 *   php apply_interest.php
 * 
 * Cron example (runs on 1st of every month at 2 AM):
 *   0 2 1 * * /usr/bin/php /path/to/Evergreen/bank-system/Basic-operation/operations/apply_interest.php
 * 
 * Windows Task Scheduler example:
 *   php C:\xampp\htdocs\Evergreen\bank-system\Basic-operation\operations\apply_interest.php
 */

// Set the root path
define('ROOT_PATH', __DIR__);

// Load required files
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/app/models/Customer.php';

// Initialize database connection
try {
    $db = new Database();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT));
}

// Initialize Customer model
$customerModel = new Customer();

// Calculate and apply interest
$result = $customerModel->calculateAndApplyInterest();

// Log results
if ($result['success']) {
    $log_message = sprintf(
        "[%s] Interest applied successfully. Accounts processed: %d, Total interest: PHP %.2f\n",
        date('Y-m-d H:i:s'),
        $result['accounts_processed'],
        $result['total_interest_applied']
    );
    
    // Log to file
    $log_file = ROOT_PATH . '/logs/interest_' . date('Y-m') . '.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Also output to console
    echo $log_message;
    
    // Optionally send email notification to admin
    // mail('admin@bank.com', 'Monthly Interest Applied', $log_message);
} else {
    $error_message = sprintf(
        "[%s] Error applying interest: %s\n",
        date('Y-m-d H:i:s'),
        $result['error'] ?? 'Unknown error'
    );
    
    error_log($error_message);
    echo $error_message;
}

// Output JSON result
echo "\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";

