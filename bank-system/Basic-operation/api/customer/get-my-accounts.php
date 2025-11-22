<?php
/**
 * Get My Accounts API
 * Retrieves all accounts for the logged-in customer
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
 */
function calculateCurrentBalance($db, $accountId) {
    $stmt = $db->prepare("
        SELECT
            SUM(
                CASE tt.type_name
                    WHEN 'Deposit' THEN t.amount
                    WHEN 'Transfer In' THEN t.amount
                    WHEN 'Interest Payment' THEN t.amount
                    WHEN 'Loan Disbursement' THEN t.amount
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
    return (float) ($result['current_balance'] ?? 0.00);
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to view your accounts.'
        ]);
        exit();
    }

    $customerId = $_SESSION['customer_id'];
    
    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Get all accounts for the customer
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.account_number,
            ca.is_locked,
            bat.type_name as account_type
        FROM customer_accounts ca
        INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        INNER JOIN customer_linked_accounts cla ON ca.account_id = cla.account_id
        WHERE cla.customer_id = :customer_id 
        AND cla.is_active = 1
        AND ca.is_locked = 0
        ORDER BY ca.created_at DESC
    ");
    
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate balance for each account
    foreach ($accounts as &$account) {
        $account['balance'] = calculateCurrentBalance($db, $account['account_id']);
        $account['balance_formatted'] = number_format($account['balance'], 2);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $accounts
    ]);
    
} catch (Exception $e) {
    error_log("Get my accounts error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving your accounts.'
    ]);
}
?>

