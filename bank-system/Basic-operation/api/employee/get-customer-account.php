<?php
/**
 * Get Customer Account API
 * Retrieves customer account information by account number
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

/**
 * Calculates the current balance of a customer account from transaction history.
 * @param PDO $db Database connection object.
 * @param int $accountId The ID of the customer account.
 * @return float The calculated current balance.
 */
function calculateCurrentBalance($db, $accountId) {
    // This SQL query implements the transaction balance calculation logic:
    // Credits: Deposit, Transfer In, Interest Payment, Loan Disbursement (Positive)
    // Debits: Withdrawal, Transfer Out, Service Charge, Loan Payment (Negative)
    
    $stmt = $db->prepare("
        SELECT
            SUM(
                CASE tt.type_name
                    WHEN 'Deposit' THEN t.amount
                    WHEN 'Transfer In' THEN t.amount
                    WHEN 'Interest Payment' THEN t.amount
                    WHEN 'Loan Disbursement' THEN t.amount
                    -- Debits
                    WHEN 'Withdrawal' THEN -t.amount
                    WHEN 'Transfer Out' THEN -t.amount
                    WHEN 'Service Charge' THEN -t.amount
                    WHEN 'Loan Payment' THEN -t.amount
                    ELSE 0
                END
            ) as current_balance
        FROM bank_transactions t
        INNER JOIN transaction_types tt ON t.transaction_type_id = tt.transaction_type_id
        WHERE t.account_id = :account_id
    ");

    $stmt->bindParam(':account_id', $accountId);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // Return 0.00 if no transactions are found, otherwise return the sum.
    return (float) ($result['current_balance'] ?? 0.00);
}


try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['account_number'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Account number is required'
        ]);
        exit();
    }

    $accountNumber = trim($input['account_number']);

    // // Validate account number format (EVG + 8 digits)
    // if (!preg_match('/^SA-\d{4}-\d{4}$/', $accountNumber)) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Invalid account number format. Expected: SA-1234-5678'
    //     ]);
    //     exit();
    // }

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get account information with customer details
    // Tables: customer_accounts (ca), bank_customers (bc), bank_account_types (bat)
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.account_number,
            ca.is_locked,
            bc.customer_id,
            bc.first_name,
            bc.middle_name,
            bc.last_name,
            bat.type_name as account_type
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        WHERE ca.account_number = :account_number
        LIMIT 1
    ");
    
    $stmt->bindParam(':account_number', $accountNumber);
    $stmt->execute();
    
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        echo json_encode([
            'success' => false,
            'message' => 'Account not found'
        ]);
        exit();
    }

    // Check if account is locked
    if ($account['is_locked']) {
        echo json_encode([
            'success' => false,
            'message' => 'This account is locked. Please contact customer service.'
        ]);
        exit();
    }

    // Calculate the current balance from transaction history
    $currentBalance = calculateCurrentBalance($db, $account['account_id']);

    // Format customer name
    $fullName = trim($account['first_name'] . ' ' . 
                     ($account['middle_name'] ? $account['middle_name'] . ' ' : '') . 
                     $account['last_name']);

    echo json_encode([
        'success' => true,
        'data' => [
            'account_id' => $account['account_id'],
            'account_number' => $account['account_number'],
            'customer_id' => $account['customer_id'],
            'customer_name' => $fullName,
            // Use the calculated balance
            'balance' => number_format($currentBalance, 2), 
            'account_type' => $account['account_type']
        ]
    ]);

} catch (Exception $e) {
    error_log("Get customer account error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving account information'
    ]);
}
?>