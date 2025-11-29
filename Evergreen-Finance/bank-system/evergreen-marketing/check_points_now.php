<?php
session_start();
include("db_connect.php");

$customer_id = $_SESSION['customer_id'] ?? $_SESSION['user_id'] ?? null;

if (!$customer_id) {
    die("Not logged in!");
}

echo "<h1>Points Check for Customer ID: $customer_id</h1>";

// Check bank_customers table
$sql = "SELECT customer_id, email, total_points FROM bank_customers WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<h2>Bank Customers Table:</h2>";
    echo "<pre>";
    print_r($row);
    echo "</pre>";
    
    echo "<h3>Total Points: " . $row['total_points'] . "</h3>";
} else {
    echo "<p style='color:red;'>Customer not found!</p>";
}

// Check user_missions table
echo "<h2>Collected Missions:</h2>";
$sql = "SELECT * FROM user_missions WHERE user_id = ? ORDER BY completed_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Mission ID</th><th>Points Earned</th><th>Status</th><th>Completed At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['mission_id'] . "</td>";
        echo "<td>" . $row['points_earned'] . "</td>";
        echo "<td>" . ($row['status'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['completed_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No missions collected yet</p>";
}

// Check points_history table
echo "<h2>Points History:</h2>";
$sql = "SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Points</th><th>Description</th><th>Type</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['points'] . "</td>";
        echo "<td>" . $row['description'] . "</td>";
        echo "<td>" . $row['transaction_type'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No points history yet</p>";
}

echo "<hr>";
echo "<p><a href='cards/points.php'>Back to Points Page</a></p>";
?>
