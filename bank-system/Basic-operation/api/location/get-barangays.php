<?php
/**
 * API Endpoint: Get barangays by city
 * Returns list of barangays for a specific city
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
    // Get city_id from query parameter
    $city_id = isset($_GET['city_id']) ? intval($_GET['city_id']) : 0;
    
    if ($city_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid city ID'
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
    
    // Fetch barangays for the specified city
    $stmt = $pdo->prepare("
        SELECT barangay_id, barangay_name, zip_code 
        FROM barangays 
        WHERE city_id = :city_id 
        ORDER BY barangay_name ASC
    ");
    
    $stmt->bindParam(':city_id', $city_id, PDO::PARAM_INT);
    $stmt->execute();
    $barangays = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $barangays
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
