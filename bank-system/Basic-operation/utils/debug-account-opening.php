<?php
/**
 * Debug file to test account opening API
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Account Opening API Debug</h1>";

// Test database connection
require_once '../config/database.php';

echo "<h2>1. Database Connection Test</h2>";
$db = getDBConnection();
if ($db) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}

// Test if account exists
echo "<h2>2. Test Account Lookup</h2>";
$testAccountNumber = 'SA-6837-2025'; // Replace with your test account number
$stmt = $db->prepare("
    SELECT ca.account_id, ca.account_number, ca.customer_id, ca.is_locked
    FROM customer_accounts ca
    WHERE ca.account_number = :account_number
    LIMIT 1
");
$stmt->bindParam(':account_number', $testAccountNumber);
$stmt->execute();
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($account) {
    echo "<p style='color: green;'>✅ Account found</p>";
    echo "<pre>" . print_r($account, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Account not found</p>";
}

// Test getting application data
if ($account) {
    echo "<h2>3. Test Application Data Lookup</h2>";
    $stmt = $db->prepare("
        SELECT aa.*
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        INNER JOIN account_applications aa ON bc.application_id = aa.application_id
        WHERE ca.account_number = :account_number
        LIMIT 1
    ");
    $stmt->bindParam(':account_number', $testAccountNumber);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        echo "<p style='color: green;'>✅ Application data found</p>";
        echo "<pre>";
        print_r(array_keys($application));
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Application data not found</p>";
        echo "<p>This might be the issue - checking joins...</p>";
        
        // Check bank_customers
        $stmt = $db->prepare("SELECT * FROM bank_customers WHERE customer_id = :customer_id");
        $stmt->bindParam(':customer_id', $account['customer_id']);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo "<p>Customer record exists:</p>";
            echo "<pre>" . print_r($customer, true) . "</pre>";
            
            if (isset($customer['application_id'])) {
                echo "<p>Application ID: " . $customer['application_id'] . "</p>";
                
                // Check if application exists
                $stmt = $db->prepare("SELECT * FROM account_applications WHERE application_id = :app_id");
                $stmt->bindParam(':app_id', $customer['application_id']);
                $stmt->execute();
                $app = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($app) {
                    echo "<p style='color: green;'>✅ Application record exists</p>";
                } else {
                    echo "<p style='color: red;'>❌ Application record NOT found for ID: " . $customer['application_id'] . "</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Customer has no application_id</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Customer record not found</p>";
        }
    }
}

// Test upload directory
echo "<h2>4. Upload Directory Test</h2>";
$uploadDir = '../uploads/id_images/';
if (is_dir($uploadDir)) {
    echo "<p style='color: green;'>✅ Upload directory exists</p>";
    if (is_writable($uploadDir)) {
        echo "<p style='color: green;'>✅ Upload directory is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ Upload directory is NOT writable</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Upload directory does not exist (will be created automatically)</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests above pass, try submitting the form again and check:</p>";
echo "<ul>";
echo "<li>Browser console (F12) for JavaScript errors</li>";
echo "<li>Network tab to see the actual API response</li>";
echo "<li>PHP error logs in XAMPP</li>";
echo "</ul>";
?>
