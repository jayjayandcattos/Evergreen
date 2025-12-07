<?php
// Run migration to add application_id to bank_customers table

require_once '../Basic-operation/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Starting migration: Add application_id to bank_customers...\n";
    
    // Check if column already exists
    $checkStmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = 'BankingDB' 
        AND TABLE_NAME = 'bank_customers' 
        AND COLUMN_NAME = 'application_id'
    ");
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "Column 'application_id' already exists in bank_customers table.\n";
        exit(0);
    }
    
    // Add application_id column
    echo "Adding application_id column to bank_customers...\n";
    $db->exec("
        ALTER TABLE bank_customers 
        ADD COLUMN application_id INT NULL AFTER customer_id
    ");
    echo "✓ Column added successfully\n";
    
    // Add index
    echo "Adding index on application_id...\n";
    $db->exec("
        ALTER TABLE bank_customers 
        ADD INDEX idx_application_id (application_id)
    ");
    echo "✓ Index added successfully\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
