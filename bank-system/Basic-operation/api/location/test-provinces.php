<?php
// Simple test to check if provinces API works
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Province API Test</h2>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'BankingDB';
    $username = 'root';
    $password = '';
    
    echo "<p>Connecting to database...</p>";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✓ Connected successfully</p>";
    
    // Fetch all provinces
    echo "<p>Fetching provinces...</p>";
    $stmt = $pdo->prepare("SELECT province_id, province_name, region FROM provinces ORDER BY province_name ASC");
    $stmt->execute();
    $provinces = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>✓ Found " . count($provinces) . " provinces</p>";
    
    echo "<h3>First 10 Provinces:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Region</th></tr>";
    
    $count = 0;
    foreach ($provinces as $province) {
        if ($count >= 10) break;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($province['province_id']) . "</td>";
        echo "<td>" . htmlspecialchars($province['province_name']) . "</td>";
        echo "<td>" . htmlspecialchars($province['region']) . "</td>";
        echo "</tr>";
        $count++;
    }
    echo "</table>";
    
    echo "<h3>JSON Response (first 5):</h3>";
    echo "<pre>";
    echo json_encode([
        'success' => true,
        'data' => array_slice($provinces, 0, 5)
    ], JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
