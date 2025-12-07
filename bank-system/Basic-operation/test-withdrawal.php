<?php
require_once 'config/database.php';

$db = getDBConnection();

if (!$db) {
    die("Database connection failed");
}

echo "<h2>Checking Triggers on bank_transactions table:</h2>";

try {
    $stmt = $db->query("SHOW TRIGGERS WHERE `Table` = 'bank_transactions'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($triggers)) {
        echo "<p>No triggers found on bank_transactions table</p>";
    } else {
        echo "<pre>";
        print_r($triggers);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Withdrawal Query:</h2>";

// Test the exact query from process-withdrawal.php
try {
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.customer_id,
            ca.is_locked,
            ca.maintaining_balance_required,
            ca.monthly_service_fee,
            ca.account_status,
            ca.below_maintaining_since,
            bat.type_name as account_type,
            aa.first_name,
            aa.middle_name,
            aa.last_name
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        INNER JOIN account_applications aa ON bc.application_id = aa.application_id
        INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        WHERE ca.account_number = :account_number
    ");
    
    $stmt->execute(['account_number' => 'SA-6837-2025']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Query Result:</h3><pre>";
    print_r($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Query Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Transaction Insert:</h2>";

// Test inserting a transaction
try {
    $db->beginTransaction();
    
    $transactionRef = 'TEST' . time();
    $stmt = $db->prepare("
        INSERT INTO bank_transactions (
            transaction_ref,
            account_id,
            transaction_type_id,
            amount,
            description,
            employee_id,
            created_at
        ) VALUES (
            :transaction_ref,
            :account_id,
            :transaction_type_id,
            :amount,
            :description,
            :employee_id,
            NOW()
        )
    ");
    
    // Get withdrawal transaction type
    $typeStmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Withdrawal'");
    $typeStmt->execute();
    $typeResult = $typeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$typeResult) {
        echo "<p style='color: red;'>Transaction type 'Withdrawal' not found!</p>";
        $db->rollBack();
    } else {
        $stmt->execute([
            'transaction_ref' => $transactionRef,
            'account_id' => 1,
            'transaction_type_id' => $typeResult['transaction_type_id'],
            'amount' => 100.00,
            'description' => 'Test withdrawal',
            'employee_id' => 1
        ]);
        
        echo "<p style='color: green;'>Test transaction inserted successfully!</p>";
        echo "<p>Transaction Ref: " . $transactionRef . "</p>";
        
        // Rollback so we don't actually save the test transaction
        $db->rollBack();
        echo "<p>(Transaction rolled back - not saved)</p>";
    }
} catch (Exception $e) {
    $db->rollBack();
    echo "<p style='color: red;'>Transaction Insert Error: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}
?>
