<?php
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();

echo "Recent loan applications:\n";
echo str_repeat("-", 80) . "\n";

$result = $conn->query('SELECT id, full_name, loan_amount, status, created_at FROM loan_applications ORDER BY id DESC LIMIT 5');

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo sprintf("ID: %d | Name: %s | Amount: %.2f | Status: %s | Date: %s\n", 
            $row['id'], 
            $row['full_name'], 
            $row['loan_amount'], 
            $row['status'], 
            $row['created_at']
        );
    }
} else {
    echo "No loan applications found.\n";
}

echo "\nChecking if loan_applications table exists in BankingDB...\n";
$tables = $conn->query("SHOW TABLES LIKE 'loan_applications'");
if ($tables->num_rows > 0) {
    echo "✓ loan_applications table exists\n";
} else {
    echo "✗ loan_applications table NOT found\n";
}
?>
