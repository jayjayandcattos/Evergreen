<?php
require_once 'config/database.php';

$db = getDBConnection();

if (!$db) {
    die("Database connection failed");
}

echo "<h2>Testing JOIN for account SA-6837-2025:</h2>";

// Check each step of the join
echo "<h3>Step 1: customer_accounts</h3>";
$stmt = $db->query("SELECT * FROM customer_accounts WHERE account_number = 'SA-6837-2025'");
$ca = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($ca);
echo "</pre>";

if ($ca) {
    echo "<h3>Step 2: bank_customers (customer_id = " . $ca['customer_id'] . ")</h3>";
    $stmt = $db->prepare("SELECT * FROM bank_customers WHERE customer_id = :customer_id");
    $stmt->execute(['customer_id' => $ca['customer_id']]);
    $bc = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($bc);
    echo "</pre>";
    
    if ($bc && $bc['application_id']) {
        echo "<h3>Step 3: account_applications (application_id = " . $bc['application_id'] . ")</h3>";
        $stmt = $db->prepare("SELECT * FROM account_applications WHERE application_id = :application_id");
        $stmt->execute(['application_id' => $bc['application_id']]);
        $aa = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($aa);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>bank_customers has no application_id!</p>";
    }
    
    echo "<h3>Step 4: bank_account_types (account_type_id = " . $ca['account_type_id'] . ")</h3>";
    $stmt = $db->prepare("SELECT * FROM bank_account_types WHERE account_type_id = :account_type_id");
    $stmt->execute(['account_type_id' => $ca['account_type_id']]);
    $bat = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($bat);
    echo "</pre>";
}

echo "<h2>Full JOIN Test:</h2>";
try {
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.customer_id,
            ca.account_number,
            bc.application_id,
            aa.first_name,
            aa.last_name,
            bat.type_name
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        LEFT JOIN account_applications aa ON bc.application_id = aa.application_id
        LEFT JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        WHERE ca.account_number = :account_number
    ");
    
    $stmt->execute(['account_number' => 'SA-6837-2025']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
