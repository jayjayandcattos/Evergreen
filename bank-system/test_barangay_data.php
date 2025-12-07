<?php
// Test script to check if barangay data exists in the database

// Include database config
require_once __DIR__ . '/Basic-operation/operations/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check barangays count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM barangays");
    $barangayCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Database Test Results</h3>";
    echo "<p><strong>Total barangays:</strong> " . $barangayCount['total'] . "</p>";
    
    // Check cities count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM cities");
    $cityCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total cities:</strong> " . $cityCount['total'] . "</p>";
    
    // Check provinces count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM provinces");
    $provinceCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Total provinces:</strong> " . $provinceCount['total'] . "</p>";
    
    // Sample barangays from first city
    echo "<h3>Sample Barangays (first 10):</h3>";
    $stmt = $pdo->query("
        SELECT b.barangay_id, b.barangay_name, c.city_id, c.city_name, p.province_id, p.province_name
        FROM barangays b
        LEFT JOIN cities c ON b.city_id = c.city_id
        LEFT JOIN provinces p ON c.province_id = p.province_id
        LIMIT 10
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Barangay ID</th><th>Barangay Name</th><th>City ID</th><th>City Name</th><th>Province</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>" . $row['barangay_id'] . "</td>";
            echo "<td>" . $row['barangay_name'] . "</td>";
            echo "<td>" . $row['city_id'] . "</td>";
            echo "<td>" . $row['city_name'] . "</td>";
            echo "<td>" . $row['province_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>No barangay data found!</strong></p>";
    }
    
    // Check a specific customer's address
    echo "<h3>Sample Customer Address:</h3>";
    $stmt = $pdo->query("
        SELECT c.customer_id, c.first_name, c.last_name, 
               a.address_line, a.city_id, a.barangay_id, a.province_id,
               ct.city_name, b.barangay_name, p.province_name
        FROM bank_customers c
        LEFT JOIN addresses a ON c.customer_id = a.customer_id AND a.is_primary = 1 AND a.address_type = 'home'
        LEFT JOIN cities ct ON a.city_id = ct.city_id
        LEFT JOIN barangays b ON a.barangay_id = b.barangay_id
        LEFT JOIN provinces p ON a.province_id = p.province_id
        WHERE c.customer_id = 1
        LIMIT 1
    ");
    
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        echo "<pre>";
        print_r($customer);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'><strong>No customer data found!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
