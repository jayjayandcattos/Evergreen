<?php
/**
 * API Endpoint: Get cities by province
 * Returns list of cities/municipalities for a specific province
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get province_id from query parameter
    $province_id = isset($_GET['province_id']) ? intval($_GET['province_id']) : 0;
    
    if ($province_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid province ID'
        ]);
        exit();
    }
    
    // Database connection
    $host = 'localhost';
    $dbname = 'BankingDB';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch cities for the specified province
    $stmt = $pdo->prepare("
        SELECT city_id, city_name, city_type, zip_code 
        FROM cities 
        WHERE province_id = :province_id 
        ORDER BY city_name ASC
    ");
    
    $stmt->bindParam(':province_id', $province_id, PDO::PARAM_INT);
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $cities
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
