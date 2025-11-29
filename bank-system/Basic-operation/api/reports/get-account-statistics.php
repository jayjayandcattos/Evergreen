<?php
/**
 * Get Account Statistics and List
 * Returns overall statistics and detailed account list
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

/**
 * Calculate current balance from transaction history
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
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get overall statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_accounts,
            SUM(CASE WHEN account_status = 'active' THEN 1 ELSE 0 END) as active_accounts,
            SUM(CASE WHEN account_status = 'below_maintaining' THEN 1 ELSE 0 END) as below_maintaining,
            SUM(CASE WHEN account_status = 'flagged_for_removal' THEN 1 ELSE 0 END) as flagged_for_removal,
            SUM(CASE WHEN account_status = 'closed' THEN 1 ELSE 0 END) as closed_accounts
        FROM customer_accounts
    ";
    
    $statsStmt = $db->query($statsQuery);
    $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Get loan approvals count (from loan_applications table if exists)
    try {
        $loanQuery = "SELECT COUNT(*) as loan_approvals FROM loan_applications WHERE status = 'approved'";
        $loanStmt = $db->query($loanQuery);
        $loanResult = $loanStmt->fetch(PDO::FETCH_ASSOC);
        $statistics['loan_approvals'] = $loanResult['loan_approvals'] ?? 0;
    } catch (Exception $e) {
        // Table might not exist, set to 0
        $statistics['loan_approvals'] = 0;
    }

    // Get all accounts with details
    $accountsQuery = "
        SELECT 
            ca.account_id,
            ca.account_number,
            ca.account_status,
            ca.below_maintaining_since,
            ca.created_at as last_updated,
            CONCAT(bc.first_name, ' ', 
                   COALESCE(CONCAT(LEFT(bc.middle_name, 1), '. '), ''),
                   bc.last_name) as customer_name,
            bat.type_name as account_type
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
        ORDER BY ca.created_at DESC
    ";
    
    $accountsStmt = $db->query($accountsQuery);
    $accounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate current balance for each account
    foreach ($accounts as &$account) {
        $account['current_balance'] = calculateCurrentBalance($db, $account['account_id']);
    }

    echo json_encode([
        'success' => true,
        'statistics' => $statistics,
        'accounts' => $accounts
    ]);

} catch (Exception $e) {
    error_log("Reports API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch reports data: ' . $e->getMessage()
    ]);
}
