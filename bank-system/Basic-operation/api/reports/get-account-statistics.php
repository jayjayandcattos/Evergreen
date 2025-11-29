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
 * Update account statuses based on current balance
 * - Below maintaining: 0 months (balance = 0)
 * - Flagged for removal: 5 months below maintaining
 * - Closed and archived: 6 months below maintaining
 */
function updateAccountStatuses($db) {
    try {
        // Get all non-closed accounts
        $stmt = $db->prepare("
            SELECT account_id, account_number, account_status, below_maintaining_since, last_service_fee_date, closure_warning_date
            FROM customer_accounts
            WHERE account_status IN ('active', 'below_maintaining', 'flagged_for_removal')
        ");
        $stmt->execute();
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($accounts as $account) {
            $accountId = $account['account_id'];
            $currentBalance = calculateCurrentBalance($db, $accountId);

            // Check if balance is 0 or below
            if ($currentBalance <= 0 && $account['account_status'] === 'active') {
                // Update status to below_maintaining
                $updateStmt = $db->prepare("
                    UPDATE customer_accounts
                    SET account_status = 'below_maintaining',
                        below_maintaining_since = CURDATE()
                    WHERE account_id = :account_id
                ");
                $updateStmt->bindParam(':account_id', $accountId);
                $updateStmt->execute();
                
                error_log("Account {$account['account_number']} marked as below_maintaining (balance: $currentBalance)");
                
            } else if ($currentBalance > 0 && in_array($account['account_status'], ['below_maintaining', 'flagged_for_removal'])) {
                // Balance recovered, update status back to active
                $updateStmt = $db->prepare("
                    UPDATE customer_accounts
                    SET account_status = 'active',
                        below_maintaining_since = NULL,
                        closure_warning_date = NULL
                    WHERE account_id = :account_id
                ");
                $updateStmt->bindParam(':account_id', $accountId);
                $updateStmt->execute();
                
                error_log("Account {$account['account_number']} recovered to active status (balance: $currentBalance)");
            }

            // Check timeline for accounts below maintaining
            if ($currentBalance <= 0 && $account['below_maintaining_since']) {
                $belowSince = new DateTime($account['below_maintaining_since']);
                $now = new DateTime();
                $interval = $belowSince->diff($now);
                $monthsDiff = $interval->m + ($interval->y * 12);

                // STAGE 1: Flag for removal after 5 months
                if ($monthsDiff >= 5 && $monthsDiff < 6 && $account['account_status'] === 'below_maintaining') {
                    $updateStmt = $db->prepare("
                        UPDATE customer_accounts
                        SET account_status = 'flagged_for_removal',
                            closure_warning_date = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $updateStmt->bindParam(':account_id', $accountId);
                    $updateStmt->execute();
                    
                    error_log("Account {$account['account_number']} FLAGGED FOR REMOVAL (below maintaining for $monthsDiff months)");
                }

                // STAGE 2: Archive and close after 6 months
                if ($monthsDiff >= 6 && $account['account_status'] !== 'closed') {
                    // Archive the account
                    archiveAccount($db, $accountId, $currentBalance);
                    
                    error_log("Account {$account['account_number']} ARCHIVED AND CLOSED (below maintaining for $monthsDiff months)");
                }

                // Continue charging monthly fees for flagged accounts (5-6 months)
                if ($monthsDiff >= 5 && $monthsDiff < 6) {
                    $lastFeeDate = $account['last_service_fee_date'] ? new DateTime($account['last_service_fee_date']) : null;
                    $shouldCharge = false;

                    if (!$lastFeeDate) {
                        $shouldCharge = true;
                    } else {
                        $daysSinceLastCharge = $lastFeeDate->diff($now)->days;
                        if ($daysSinceLastCharge >= 30) {
                            $shouldCharge = true;
                        }
                    }

                    if ($shouldCharge) {
                        // Get or create Monthly Maintenance Fee transaction type
                        $typeStmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Monthly Maintenance Fee'");
                        $typeStmt->execute();
                        $typeResult = $typeStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$typeResult) {
                            $insertType = $db->prepare("
                                INSERT INTO transaction_types (type_name, description, created_at)
                                VALUES ('Monthly Maintenance Fee', 'Monthly fee for accounts below maintaining balance', NOW())
                            ");
                            $insertType->execute();
                            $feeTypeId = $db->lastInsertId();
                        } else {
                            $feeTypeId = $typeResult['transaction_type_id'];
                        }

                        // Create service charge transaction
                        $feeAmount = 100.00;
                        $insertTxn = $db->prepare("
                            INSERT INTO bank_transactions 
                            (account_id, transaction_type_id, amount, transaction_date, description, created_at)
                            VALUES 
                            (:account_id, :type_id, :amount, NOW(), 'Monthly maintenance fee for flagged account', NOW())
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
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Failed to update account statuses: " . $e->getMessage());
    }
}

/**
 * Archive an account before closing it
 */
function archiveAccount($db, $accountId, $finalBalance) {
    // Get full account details
    $stmt = $db->prepare("
        SELECT * FROM customer_accounts WHERE account_id = :account_id
    ");
    $stmt->bindParam(':account_id', $accountId);
    $stmt->execute();
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        return false;
    }
    
    // Insert into archive table
    $archiveStmt = $db->prepare("
        INSERT INTO archived_customer_accounts (
            account_id, customer_id, account_number, account_type_id,
            interest_rate, last_interest_date, is_locked, created_at,
            created_by_employee_id, maintaining_balance_required, monthly_service_fee,
            final_balance, below_maintaining_since, last_service_fee_date,
            closure_warning_date, flagged_for_removal_date, original_status,
            archive_reason, archived_at
        ) VALUES (
            :account_id, :customer_id, :account_number, :account_type_id,
            :interest_rate, :last_interest_date, :is_locked, :created_at,
            :created_by_employee_id, :maintaining_balance_required, :monthly_service_fee,
            :final_balance, :below_maintaining_since, :last_service_fee_date,
            :closure_warning_date, :closure_warning_date, :account_status,
            'Below maintaining balance for 6+ months - automatically archived', NOW()
        )
    ");
    
    $archiveStmt->bindParam(':account_id', $account['account_id']);
    $archiveStmt->bindParam(':customer_id', $account['customer_id']);
    $archiveStmt->bindParam(':account_number', $account['account_number']);
    $archiveStmt->bindParam(':account_type_id', $account['account_type_id']);
    $archiveStmt->bindParam(':interest_rate', $account['interest_rate']);
    $archiveStmt->bindParam(':last_interest_date', $account['last_interest_date']);
    $archiveStmt->bindParam(':is_locked', $account['is_locked']);
    $archiveStmt->bindParam(':created_at', $account['created_at']);
    $archiveStmt->bindParam(':created_by_employee_id', $account['created_by_employee_id']);
    $archiveStmt->bindParam(':maintaining_balance_required', $account['maintaining_balance_required']);
    $archiveStmt->bindParam(':monthly_service_fee', $account['monthly_service_fee']);
    $archiveStmt->bindParam(':final_balance', $finalBalance);
    $archiveStmt->bindParam(':below_maintaining_since', $account['below_maintaining_since']);
    $archiveStmt->bindParam(':last_service_fee_date', $account['last_service_fee_date']);
    $archiveStmt->bindParam(':closure_warning_date', $account['closure_warning_date']);
    $archiveStmt->bindParam(':account_status', $account['account_status']);
    $archiveStmt->execute();
    
    // Mark account as closed and locked (but don't delete yet)
    $closeStmt = $db->prepare("
        UPDATE customer_accounts
        SET account_status = 'closed',
            is_locked = 1
        WHERE account_id = :account_id
    ");
    $closeStmt->bindParam(':account_id', $accountId);
    $closeStmt->execute();
    
    return true;
}

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

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Update account statuses based on current balance (check for 0 balance and 6+ months)
    updateAccountStatuses($db);

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
