<?php
// Check if customer_id column exists in account_applications table

require_once '../Basic-operation/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Checking account_applications table...\n";
    
    // Check if column already exists
    $checkStmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'BankingDB' 
        AND TABLE_NAME = 'account_applications' 
        AND COLUMN_NAME = 'customer_id'
    ");
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "✓ Column 'customer_id' already exists in account_applications table.\n";
    } else {
        echo "✗ Column 'customer_id' does NOT exist in account_applications table.\n";
        echo "  This column should have been added in a previous migration.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
