<?php
session_start();

echo "<h1>Points System Debug</h1>";
echo "<h2>Session Variables:</h2>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "customer_id: " . ($_SESSION['customer_id'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "first_name: " . ($_SESSION['first_name'] ?? 'NOT SET') . "\n";
echo "last_name: " . ($_SESSION['last_name'] ?? 'NOT SET') . "\n";
echo "</pre>";

if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'];
    
    echo "<h2>Testing API Calls (Direct PHP Include):</h2>";
    
    // Test 1: Get User Points - Direct call
    echo "<h3>1. Get User Points (Direct)</h3>";
    $_GET['action'] = 'get_user_points';
    ob_start();
    include("points_api.php");
    $response = ob_get_clean();
    echo "<pre>$response</pre>";
    
    // Test 2: Get Missions - Direct call
    echo "<h3>2. Get Missions (Direct)</h3>";
    $_GET['action'] = 'get_missions';
    ob_start();
    include("points_api.php");
    $response = ob_get_clean();
    echo "<pre>$response</pre>";
    
    // Test 3: Database Check
    echo "<h3>3. Database Check</h3>";
    include("db_connect.php");
    
    $sql = "SELECT customer_id, email, total_points FROM bank_customers WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>Customer not found in bank_customers table!</p>";
    }
    
    // Test 4: Check missions table
    echo "<h3>4. Missions Table Check</h3>";
    $sql = "SELECT COUNT(*) as count FROM missions";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    echo "<p>Missions in database: " . $row['count'] . "</p>";
    
    // Test 5: Check user_missions table
    echo "<h3>5. User Missions Check</h3>";
    $sql = "SELECT * FROM user_missions WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p>Collected missions: " . $result->num_rows . "</p>";
    if ($result->num_rows > 0) {
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    }
    
} else {
    echo "<p style='color:red;'>NOT LOGGED IN! Please <a href='login.php'>login first</a></p>";
}
?>
