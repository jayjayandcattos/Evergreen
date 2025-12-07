<?php
/**
 * Migration Script: Add customer_id to account_applications
 */

try {
    $db = new PDO('mysql:host=localhost;dbname=BankingDB', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Running migration: Add customer_id to account_applications\n";
    echo "==========================================================\n\n";
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM account_applications LIKE 'customer_id'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'customer_id' already exists. Skipping...\n";
    } else {
        // Add customer_id column
        $db->exec("
            ALTER TABLE account_applications 
            ADD COLUMN customer_id INT DEFAULT NULL COMMENT 'Reference to existing customer opening the account' 
            AFTER application_status
        ");
        echo "✓ Added customer_id column\n";
        
        // Add index
        $db->exec("
            ALTER TABLE account_applications 
            ADD INDEX idx_customer_id (customer_id)
        ");
        echo "✓ Added index on customer_id\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
