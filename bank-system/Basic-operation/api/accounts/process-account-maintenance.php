<?php
/**
 * Process Account Maintenance
 * - Update account status to "below_maintaining" when balance reaches 0
 * - Charge 100 pesos monthly fee for accounts below maintaining for 6+ months
 * 
 * This should be run as a scheduled task (daily or monthly)
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

// Set error handler to ensure JSON responses
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
});

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
                    WHEN 'Monthly Maintenance Fee' THEN -t.amount
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

/**
 * Get or create transaction type ID for Monthly Maintenance Fee
 */
function getMonthlyFeeTransactionTypeId($db) {
    // Check if transaction type exists
    $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Monthly Maintenance Fee'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        return $result['transaction_type_id'];
    }
    
    // Create if doesn't exist
    $stmt = $db->prepare("
        INSERT INTO transaction_types (type_name, description, created_at)
        VALUES ('Monthly Maintenance Fee', 'Monthly fee charged for accounts below maintaining balance for 6+ months', NOW())
    ");
    $stmt->execute();
    
    return $db->lastInsertId();
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $db->beginTransaction();

    $results = [
        'updated_to_below_maintaining' => 0,
        'charged_monthly_fee' => 0,
        'errors' => []
    ];

    // Step 1: Get all active or below_maintaining accounts
    $stmt = $db->prepare("
        SELECT account_id, account_number, account_status, below_maintaining_since, last_service_fee_date
        FROM customer_accounts
        WHERE account_status IN ('active', 'below_maintaining')
    ");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($accounts as $account) {
        $accountId = $account['account_id'];
        $accountNumber = $account['account_number'];
        $currentBalance = calculateCurrentBalance($db, $accountId);

        // Step 2: Check if balance is 0 or below
        if ($currentBalance <= 0) {
            // Update status to below_maintaining if not already
            if ($account['account_status'] === 'active') {
                $updateStmt = $db->prepare("
                    UPDATE customer_accounts
                    SET account_status = 'below_maintaining',
                        below_maintaining_since = CURDATE()
                    WHERE account_id = :account_id
                ");
                $updateStmt->bindParam(':account_id', $accountId);
                $updateStmt->execute();
                
                $results['updated_to_below_maintaining']++;
                error_log("Account $accountNumber status updated to below_maintaining (balance: $currentBalance)");
            }

            // Step 3: Check if account has been below maintaining for 6+ months
            if ($account['below_maintaining_since']) {
                $belowSince = new DateTime($account['below_maintaining_since']);
                $now = new DateTime();
                $monthsDiff = $belowSince->diff($now)->m + ($belowSince->diff($now)->y * 12);

                if ($monthsDiff >= 6) {
                    // Check if we already charged this month
                    $lastFeeDate = $account['last_service_fee_date'] ? new DateTime($account['last_service_fee_date']) : null;
                    $shouldCharge = false;

                    if (!$lastFeeDate) {
                        // Never charged before
                        $shouldCharge = true;
                    } else {
                        // Check if at least 1 month has passed since last charge
                        $daysSinceLastCharge = $lastFeeDate->diff($now)->days;
                        if ($daysSinceLastCharge >= 30) {
                            $shouldCharge = true;
                        }
                    }

                    if ($shouldCharge) {
                        // Get transaction type ID
                        $feeTypeId = getMonthlyFeeTransactionTypeId($db);

                        // Create service charge transaction
                        $feeAmount = 100.00;
                        $insertTxn = $db->prepare("
                            INSERT INTO bank_transactions 
                            (account_id, transaction_type_id, amount, transaction_date, description, created_at)
                            VALUES 
                            (:account_id, :type_id, :amount, NOW(), 'Monthly maintenance fee for account below maintaining balance', NOW())
                        ");
                        $insertTxn->bindParam(':account_id', $accountId);
                        $insertTxn->bindParam(':type_id', $feeTypeId);
                        $insertTxn->bindParam(':amount', $feeAmount);
                        $insertTxn->execute();

                        // Update last service fee date
                        $updateFeeDate = $db->prepare("
                            UPDATE customer_accounts
                            SET last_service_fee_date = CURDATE()
                            WHERE account_id = :account_id
                        ");
                        $updateFeeDate->bindParam(':account_id', $accountId);
                        $updateFeeDate->execute();

                        $results['charged_monthly_fee']++;
                        error_log("Charged $feeAmount to account $accountNumber (below maintaining for $monthsDiff months)");
                    }
                }
            }
        } else if ($currentBalance > 0 && $account['account_status'] === 'below_maintaining') {
            // Balance recovered, update status back to active
            $updateStmt = $db->prepare("
                UPDATE customer_accounts
                SET account_status = 'active',
                    below_maintaining_since = NULL
                WHERE account_id = :account_id
            ");
            $updateStmt->bindParam(':account_id', $accountId);
            $updateStmt->execute();
            
            error_log("Account $accountNumber status updated to active (balance recovered: $currentBalance)");
        }
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Account maintenance processed successfully',
        'results' => $results
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log("Account Maintenance Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process account maintenance: ' . $e->getMessage()
    ]);
}
