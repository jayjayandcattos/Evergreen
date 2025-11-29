<?php
/**
 * API Endpoint: Check for Duplicate Customer Name
 * Real-time validation for first name + last name combination uniqueness
 */

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['first_name']) || !isset($input['last_name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'First name and last name are required'
        ]);
        exit();
    }
    
    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    
    if (empty($firstName) || empty($lastName)) {
        echo json_encode([
            'success' => true,
            'exists' => false
        ]);
        exit();
    }
    
    // Check if customer with this name combination already exists (using PDO)
    $query = "SELECT customer_id, first_name, last_name 
              FROM bank_customers 
              WHERE LOWER(TRIM(first_name)) = LOWER(:first_name) 
              AND LOWER(TRIM(last_name)) = LOWER(:last_name)
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
    $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
    $stmt->execute();
    
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo json_encode([
            'success' => true,
            'exists' => true,
            'message' => 'A customer with this name already exists',
            'customer' => [
                'id' => $customer['customer_id'],
                'name' => $customer['first_name'] . ' ' . $customer['last_name']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Name is available'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in check-duplicate-name.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in check-duplicate-name.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>
