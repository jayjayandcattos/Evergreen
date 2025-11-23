<?php
/**
 * Script to add bank_id column to bank_customers table
 * Run this once to fix the database schema
 */

require_once "db_connect.php";

try {
    // Check if column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM bank_customers LIKE 'bank_id'");
    
    if ($checkColumn->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE bank_customers 
                ADD COLUMN bank_id VARCHAR(10) NULL,
                ADD INDEX idx_bank_id (bank_id)";
        
        if ($conn->query($sql)) {
            echo "SUCCESS: bank_id column added to bank_customers table.\n";
            
            // Try to add UNIQUE constraint (may fail if there are duplicate NULLs, which is fine)
            $sqlUnique = "ALTER TABLE bank_customers ADD UNIQUE INDEX unique_bank_id (bank_id)";
            if ($conn->query($sqlUnique)) {
                echo "SUCCESS: UNIQUE constraint added to bank_id column.\n";
            } else {
                // If UNIQUE fails, we'll add it manually for non-NULL values later
                echo "NOTE: UNIQUE constraint not added (may have NULLs).\n";
            }
        } else {
            throw new Exception("Error adding bank_id column: " . $conn->error);
        }
    } else {
        echo "INFO: bank_id column already exists in bank_customers table.\n";
        
        // Check if index exists
        $checkIndex = $conn->query("SHOW INDEX FROM bank_customers WHERE Key_name = 'idx_bank_id'");
        if ($checkIndex->num_rows == 0) {
            $sqlIndex = "ALTER TABLE bank_customers ADD INDEX idx_bank_id (bank_id)";
            if ($conn->query($sqlIndex)) {
                echo "SUCCESS: Index added to bank_id column.\n";
            }
        }
    }
    
    // Check existing customers without bank_id and generate them
    $result = $conn->query("SELECT customer_id, first_name, last_name FROM bank_customers WHERE bank_id IS NULL OR bank_id = ''");
    
    if ($result && $result->num_rows > 0) {
        echo "\nINFO: Found " . $result->num_rows . " customers without bank_id. Generating bank_ids...\n";
        
        $updateStmt = $conn->prepare("UPDATE bank_customers SET bank_id = ? WHERE customer_id = ?");
        $count = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Generate a unique 4-digit bank_id
            do {
                $bank_id = sprintf("%04d", mt_rand(0, 9999));
                $checkUnique = $conn->prepare("SELECT customer_id FROM bank_customers WHERE bank_id = ?");
                $checkUnique->bind_param("s", $bank_id);
                $checkUnique->execute();
                $checkResult = $checkUnique->get_result();
                $exists = $checkResult->num_rows > 0;
                $checkUnique->close();
            } while ($exists);
            
            $updateStmt->bind_param("si", $bank_id, $row['customer_id']);
            if ($updateStmt->execute()) {
                $count++;
                echo "  - Customer ID {$row['customer_id']} ({$row['first_name']} {$row['last_name']}): bank_id = {$bank_id}\n";
            }
        }
        
        $updateStmt->close();
        echo "\nSUCCESS: Generated bank_ids for {$count} customers.\n";
    } else {
        echo "\nINFO: All customers already have bank_id assigned.\n";
    }
    
    echo "\n=== Database fix completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>

