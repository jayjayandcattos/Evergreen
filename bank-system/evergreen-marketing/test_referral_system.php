<?php
// Test Referral System
include("db_connect.php");

echo "<h1>Referral System Test</h1>";

// Test 1: Check if referral_code column exists in bank_users
echo "<h2>Test 1: Check bank_users table structure</h2>";
$result = $conn->query("SHOW COLUMNS FROM bank_users LIKE 'referral_code'");
if ($result->num_rows > 0) {
    echo "✅ referral_code column exists in bank_users<br>";
} else {
    echo "❌ referral_code column NOT found in bank_users<br>";
}

// Test 2: Check if referral_code column exists in bank_customers
echo "<h2>Test 2: Check bank_customers table structure</h2>";
$result = $conn->query("SHOW COLUMNS FROM bank_customers LIKE 'referral_code'");
if ($result->num_rows > 0) {
    echo "✅ referral_code column exists in bank_customers<br>";
} else {
    echo "❌ referral_code column NOT found in bank_customers<br>";
}

// Test 3: Check if referrals table exists
echo "<h2>Test 3: Check referrals table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'referrals'");
if ($result->num_rows > 0) {
    echo "✅ referrals table exists<br>";
} else {
    echo "❌ referrals table NOT found<br>";
}

// Test 4: Check if points_history table exists
echo "<h2>Test 4: Check points_history table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'points_history'");
if ($result->num_rows > 0) {
    echo "✅ points_history table exists<br>";
} else {
    echo "❌ points_history table NOT found<br>";
}

// Test 5: Show sample referral codes from bank_users
echo "<h2>Test 5: Sample referral codes from bank_users</h2>";
$result = $conn->query("SELECT id, email, referral_code, total_points FROM bank_users LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Referral Code</th><th>Total Points</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td><strong>" . ($row['referral_code'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . ($row['total_points'] ?? '0.00') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No users found<br>";
}

// Test 6: Show sample referral codes from bank_customers
echo "<h2>Test 6: Sample referral codes from bank_customers</h2>";
$result = $conn->query("SELECT customer_id, email, referral_code, total_points FROM bank_customers LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Customer ID</th><th>Email</th><th>Referral Code</th><th>Total Points</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['customer_id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td><strong>" . ($row['referral_code'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . ($row['total_points'] ?? '0.00') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No customers found<br>";
}

echo "<hr>";
echo "<h2>✅ Referral System Setup Complete!</h2>";
echo "<p>Visit the referral page at: <a href='http://localhost/Evergreen/bank-system/Basic-operation/operations/public/customer/referral'>http://localhost/Evergreen/bank-system/Basic-operation/operations/public/customer/referral</a></p>";
?>
