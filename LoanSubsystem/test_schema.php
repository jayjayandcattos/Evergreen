<?php
require_once __DIR__ . '/config/database.php';

$conn = getDBConnection();
$result = $conn->query('DESCRIBE bank_customers');

echo "bank_customers columns:\n";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
