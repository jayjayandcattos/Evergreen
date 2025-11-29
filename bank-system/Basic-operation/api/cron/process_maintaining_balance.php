<?php
/**
 * Monthly Maintaining Balance Check and Service Fee Processor
 * This script should be run daily via cron job or task scheduler
 * 
 * Schedule: Daily at 00:01 AM
 * 
 * Functions:
 * 1. Check accounts below maintaining balance
 * 2. Charge monthly service fee (once per month)
 * 3. Flag accounts at zero balance for removal
 * 4. Close accounts that remain at zero for 30 days
 */

require_once __DIR__ . '/../../config/database.php';

// Suppress output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/maintaining_balance.log');

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    echo "=== Maintaining Balance Check - " . date('Y-m-d H:i:s') . " ===\n";
    
    // Get Service Charge transaction type ID
    $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Service Charge'");
    $stmt->execute();
    $serviceChargeType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$serviceChargeType) {
        throw new Exception('Service Charge transaction type not found');
    }
    $serviceChargeTypeId = $serviceChargeType['transaction_type_id'];
    
    // === 1. Process Monthly Service Fees ===
    echo "\n1. Checking accounts below maintaining balance...\n";
    
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.account_number,
            ca.maintaining_balance_required,
            ca.monthly_service_fee,
            ca.account_status,
            ca.below_maintaining_since,
            ca.last_service_fee_date,
            bc.first_name,
            bc.last_name
        FROM customer_accounts ca
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        WHERE ca.account_status IN ('below_maintaining', 'active')
        AND ca.is_locked = 0
    ");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $feesCharged = 0;
    $accountsProcessed = 0;
    
    foreach ($accounts as $account) {
        $db->beginTransaction();
        
        try {
            // Calculate current balance
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
            $stmt->bindParam(':account_id', $account['account_id']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentBalance = floatval($result['current_balance'] ?? 0);
            
            $maintainingBalance = floatval($account['maintaining_balance_required']);
            $serviceFee = floatval($account['monthly_service_fee']);
            
            // Check if balance is below maintaining
            if ($currentBalance < $maintainingBalance && $currentBalance > 0) {
                // Update status to below_maintaining if not already
                if ($account['account_status'] !== 'below_maintaining') {
                    $stmt = $db->prepare("
                        UPDATE customer_accounts 
                        SET account_status = 'below_maintaining',
                            below_maintaining_since = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->execute();
                    echo "  - Account {$account['account_number']} marked as below maintaining\n";
                }
                
                // Check if we should charge monthly fee (once per month)
                $lastFeeDate = $account['last_service_fee_date'];
                $shouldChargeFee = false;
                
                if ($lastFeeDate === null) {
                    // Never charged, charge if below maintaining for at least 1 day
                    $shouldChargeFee = true;
                } else {
                    // Charge if it's been at least 30 days since last charge
                    $daysSinceLastFee = (strtotime(date('Y-m-d')) - strtotime($lastFeeDate)) / 86400;
                    if ($daysSinceLastFee >= 30) {
                        $shouldChargeFee = true;
                    }
                }
                
                if ($shouldChargeFee) {
                    // Generate transaction reference
                    $transactionRef = 'SF' . date('Ymd') . str_pad($account['account_id'], 6, '0', STR_PAD_LEFT) . rand(1000, 9999);
                    
                    // Insert service charge transaction
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
                            NULL,
                            NOW()
                        )
                    ");
                    
                    $description = "Monthly service fee - Below maintaining balance";
                    $stmt->bindParam(':transaction_ref', $transactionRef);
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->bindParam(':transaction_type_id', $serviceChargeTypeId);
                    $stmt->bindParam(':amount', $serviceFee);
                    $stmt->bindParam(':description', $description);
                    $stmt->execute();
                    
                    $transactionId = $db->lastInsertId();
                    $newBalance = $currentBalance - $serviceFee;
                    
                    // Record in service_fee_charges table
                    $stmt = $db->prepare("
                        INSERT INTO service_fee_charges (
                            account_id, transaction_id, fee_amount, 
                            balance_before, balance_after, charge_date, fee_type
                        ) VALUES (
                            :account_id, :transaction_id, :fee_amount,
                            :balance_before, :balance_after, CURDATE(), 'monthly_service_fee'
                        )
                    ");
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->bindParam(':transaction_id', $transactionId);
                    $stmt->bindParam(':fee_amount', $serviceFee);
                    $stmt->bindParam(':balance_before', $currentBalance);
                    $stmt->bindParam(':balance_after', $newBalance);
                    $stmt->execute();
                    
                    // Update last service fee date
                    $stmt = $db->prepare("
                        UPDATE customer_accounts 
                        SET last_service_fee_date = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->execute();
                    
                    echo "  - Charged PHP {$serviceFee} service fee to account {$account['account_number']} (Balance: {$currentBalance} -> {$newBalance})\n";
                    $feesCharged++;
                    
                    // Check if account reached zero after fee
                    if ($newBalance <= 0) {
                        $stmt = $db->prepare("
                            UPDATE customer_accounts 
                            SET account_status = 'flagged_for_removal',
                                closure_warning_date = CURDATE()
                            WHERE account_id = :account_id
                        ");
                        $stmt->bindParam(':account_id', $account['account_id']);
                        $stmt->execute();
                        
                        echo "  - Account {$account['account_number']} FLAGGED FOR REMOVAL (balance reached zero)\n";
                    }
                }
            } elseif ($currentBalance >= $maintainingBalance) {
                // Account is back above maintaining balance
                if ($account['account_status'] === 'below_maintaining') {
                    $stmt = $db->prepare("
                        UPDATE customer_accounts 
                        SET account_status = 'active',
                            below_maintaining_since = NULL
                        WHERE account_id = :account_id
                    ");
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->execute();
                    echo "  - Account {$account['account_number']} restored to active status\n";
                }
            } elseif ($currentBalance == 0) {
                // Account at zero
                if ($account['account_status'] !== 'flagged_for_removal') {
                    $stmt = $db->prepare("
                        UPDATE customer_accounts 
                        SET account_status = 'flagged_for_removal',
                            closure_warning_date = CURDATE()
                        WHERE account_id = :account_id
                    ");
                    $stmt->bindParam(':account_id', $account['account_id']);
                    $stmt->execute();
                    echo "  - Account {$account['account_number']} FLAGGED FOR REMOVAL (zero balance)\n";
                }
            }
            
            $db->commit();
            $accountsProcessed++;
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "  - ERROR processing account {$account['account_number']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nProcessed {$accountsProcessed} accounts, charged {$feesCharged} service fees\n";
    
    // === 2. Close accounts flagged for removal for 30+ days ===
    echo "\n2. Checking accounts for closure...\n";
    
    $stmt = $db->prepare("
        SELECT 
            ca.account_id,
            ca.account_number,
            ca.closure_warning_date,
            DATEDIFF(CURDATE(), ca.closure_warning_date) as days_flagged
        FROM customer_accounts ca
        WHERE ca.account_status = 'flagged_for_removal'
        AND ca.closure_warning_date IS NOT NULL
        AND DATEDIFF(CURDATE(), ca.closure_warning_date) >= 30
    ");
    $stmt->execute();
    $accountsToClose = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $closedCount = 0;
    foreach ($accountsToClose as $account) {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE customer_accounts 
                SET account_status = 'closed',
                    is_locked = 1,
                    closure_date = CURDATE()
                WHERE account_id = :account_id
            ");
            $stmt->bindParam(':account_id', $account['account_id']);
            $stmt->execute();
            
            echo "  - CLOSED account {$account['account_number']} (flagged for {$account['days_flagged']} days)\n";
            $db->commit();
            $closedCount++;
        } catch (Exception $e) {
            $db->rollBack();
            echo "  - ERROR closing account {$account['account_number']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nClosed {$closedCount} accounts\n";
    echo "\n=== Process Complete ===\n\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    error_log("Maintaining balance check error: " . $e->getMessage());
}
?>
