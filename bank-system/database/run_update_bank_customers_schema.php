<?php
// Run migration to update bank_customers table schema

require_once '../Basic-operation/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Starting migration: Update bank_customers table schema...\n\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/update_bank_customers_schema.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolons to execute each statement separately
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && stripos($stmt, 'USE ') !== 0;
        }
    );
    
    $count = 0;
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $db->exec($statement);
            $count++;
            
            // Extract what we're doing for user feedback
            if (stripos($statement, 'ADD COLUMN') !== false) {
                preg_match('/ADD COLUMN (\w+)/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Added column: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'ADD INDEX') !== false) {
                preg_match('/ADD INDEX (\w+)/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Added index: {$matches[1]}\n";
                }
            } elseif (stripos($statement, 'ADD CONSTRAINT') !== false) {
                preg_match('/ADD CONSTRAINT (\w+)/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✓ Added foreign key: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            // Check if error is "Duplicate column name" which we can ignore
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                preg_match('/Duplicate column name \'(\w+)\'/', $e->getMessage(), $matches);
                if (isset($matches[1])) {
                    echo "⊙ Column already exists: {$matches[1]}\n";
                }
            } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                preg_match('/Duplicate key name \'(\w+)\'/', $e->getMessage(), $matches);
                if (isset($matches[1])) {
                    echo "⊙ Index already exists: {$matches[1]}\n";
                }
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✓ Migration completed! Executed {$count} statements.\n";
    
    // Show final structure
    echo "\nFinal table structure:\n";
    echo str_repeat("-", 80) . "\n";
    
    $stmt = $db->query("DESCRIBE bank_customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    printf("%-30s %-20s %-10s\n", "Field", "Type", "Null");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $col) {
        printf("%-30s %-20s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null']
        );
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
