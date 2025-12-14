<?php
/**
 * Test script to verify loan submission is working
 * Access: http://localhost/Evergreen/LoanSubsystem/test_submit.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Loan Submission Test</h1>";

// Set test session
$_SESSION['user_email'] = 'test@example.com'; // Change this to a real email in bank_customers

require_once __DIR__ . '/config/database.php';
$conn = getDBConnection();

if (!$conn || $conn->connect_error) {
    die("<p style='color:red'>Database connection failed</p>");
}

echo "<p style='color:green'>✓ Database connected: " . DB_NAME . "</p>";

// Check session
echo "<h2>Session Info:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check bank_customers
echo "<h2>Bank Customers (sample):</h2>";
$result = $conn->query("SELECT customer_id, first_name, last_name, email FROM bank_customers LIMIT 5");
echo "<table border='1'><tr><th>ID</th><th>First</th><th>Last</th><th>Email</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['customer_id']}</td><td>{$row['first_name']}</td><td>{$row['last_name']}</td><td>{$row['email']}</td></tr>";
}
echo "</table>";

// Check loan_types
echo "<h2>Loan Types:</h2>";
$result = $conn->query("SELECT id, name FROM loan_types WHERE is_active = 1");
echo "<table border='1'><tr><th>ID</th><th>Name</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td></tr>";
}
echo "</table>";

// Test form
echo "<h2>Test Loan Submission:</h2>";
echo "<form method='POST' action='submit_loan.php' enctype='multipart/form-data'>";
echo "<input type='hidden' name='loan_type_id' value='8'>";
echo "<input type='hidden' name='loan_terms' value='12 Months'>";
echo "<input type='hidden' name='loan_amount' value='10000'>";
echo "<input type='hidden' name='purpose' value='Test Purpose'>";
echo "<p>Attachment: <input type='file' name='attachment'></p>";
echo "<p>Proof of Income: <input type='file' name='proof_of_income'></p>";
echo "<p>COE: <input type='file' name='coe_document'></p>";
echo "<button type='submit'>Submit Test Loan</button>";
echo "</form>";

// Recent applications
echo "<h2>Recent Loan Applications:</h2>";
$result = $conn->query("SELECT id, full_name, email, loan_amount, status, created_at FROM loan_applications ORDER BY id DESC LIMIT 10");
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Amount</th><th>Status</th><th>Created</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['full_name']}</td><td>{$row['email']}</td><td>₱" . number_format($row['loan_amount'], 2) . "</td><td>{$row['status']}</td><td>{$row['created_at']}</td></tr>";
}
echo "</table>";

$conn->close();
?>

