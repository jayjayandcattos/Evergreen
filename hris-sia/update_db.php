<?php
require_once 'config/database.php';

try {
    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM applicant LIKE 'offer_token'";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Add column
        $sql = "ALTER TABLE applicant ADD COLUMN offer_token VARCHAR(64) NULL AFTER offer_status";
        $conn->exec($sql);
        echo "Successfully added offer_token column to applicant table.\n";
    } else {
        echo "Column offer_token already exists.\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
