<?php
/**
 * Process Withdrawal API
 * Processes a withdrawal transaction for a customer account
 * Includes maintaining balance logic and service fee handling
 */

// Suppress all output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
                    -- Debits (will show as negative in the SQL result)
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
    
    // Validate required fields
    $requiredFields = ['account_number', 'amount'];
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'errors' => $errors
        ]);
        exit();
    }

    $accountNumber = trim($input['account_number']);
    $amount = floatval(str_replace(',', '', $input['amount']));

    // Validate amount
    if ($amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid withdrawal amount'
        ]);
        exit();
    }

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // --- 1. Get account information and lock the row ---
        // Join: customer_accounts -> bank_customers
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
                bc.first_name,
                bc.middle_name,
                bc.last_name
            FROM customer_accounts ca
            INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
            INNER JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
            WHERE ca.account_number = :account_number
            FOR UPDATE -- Lock the row
        ");
        
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->execute();
        
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception('Account not found');
        }

        if ($account['is_locked']) {
            throw new Exception('Account is locked');
        }
        
        // Check if account is closed or flagged for removal
        if ($account['account_status'] === 'closed') {
            throw new Exception('Account is closed');
        }
        
        if ($account['account_status'] === 'flagged_for_removal') {
            throw new Exception('Account is flagged for removal. Please contact customer service.');
        }

        // --- 2. Calculate previous balance from transactions (per schema requirements) ---
        $previousBalance = calculateCurrentBalance($db, $account['account_id']);
        
        // --- 3. Check for sufficient balance ---
        if ($previousBalance < $amount) {
            throw new Exception('Insufficient balance. Current balance: PHP ' . number_format($previousBalance, 2));
        }

        // --- 4. Determine New Balance after withdrawal ---
        $newBalance = $previousBalance - $amount;
        
        // --- 5. Check maintaining balance for warnings only (not blocking) ---
        $maintainingBalance = floatval($account['maintaining_balance_required'] ?? 500.00);
        $serviceFee = floatval($account['monthly_service_fee'] ?? 100.00);
        $warnings = [];
        $statusUpdate = null;
        
        // Add warning if withdrawal brings balance below maintaining requirement
        if ($newBalance < $maintainingBalance) {
            $warnings[] = "Warning: This withdrawal will bring the balance below the maintaining balance of PHP " . number_format($maintainingBalance, 2);
            $warnings[] = "Account may be subject to monthly service fees";
            $statusUpdate = 'below_maintaining';
        }
        
        // **IMPORTANT FIX:** Removed the `UPDATE accounts SET balance = :new_balance` query,
        // as the `customer_accounts` table does not have a `balance` column.
        // The balance is calculated on-demand from transactions.
        
        // --- 6. Get 'Withdrawal' transaction_type_id ---
        $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Withdrawal'");
        $stmt->execute();
        $withdrawalType = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$withdrawalType) {
            throw new Exception('Transaction type "Withdrawal" not found in transaction_types table.');
        }
        $withdrawalTypeId = $withdrawalType['transaction_type_id'];

        // --- 6. Generate transaction reference number ---
        $transactionRef = 'WD' . date('Ymd') . str_pad($account['account_id'], 6, '0', STR_PAD_LEFT) . rand(1000, 9999);

        // --- 7. Insert transaction record ---
        // Table: bank_transactions
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
        
        // For now, use session employee ID if available, otherwise use 1 (default)
        $employeeId = $_SESSION['employee_id'] ?? 1;
        $description = 'Cash withdrawal - Ref: ' . $transactionRef;
        
        $stmt->bindParam(':transaction_ref', $transactionRef);
        $stmt->bindParam(':account_id', $account['account_id']);
        $stmt->bindParam(':transaction_type_id', $withdrawalTypeId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();

        // --- 8. Update account status if needed ---
        if ($statusUpdate) {
            $stmt = $db->prepare("
                UPDATE customer_accounts 
                SET account_status = :status,
                    below_maintaining_since = CASE 
                        WHEN :status2 = 'below_maintaining' AND below_maintaining_since IS NULL THEN CURDATE()
                        WHEN :status3 = 'active' THEN NULL
                        ELSE below_maintaining_since
                    END,
                    closure_warning_date = CASE
                        WHEN :status4 = 'flagged_for_removal' THEN CURDATE()
                        ELSE closure_warning_date
                    END
                WHERE account_id = :account_id
            ");
            $stmt->bindParam(':status', $statusUpdate);
            $stmt->bindParam(':status2', $statusUpdate);
            $stmt->bindParam(':status3', $statusUpdate);
            $stmt->bindParam(':status4', $statusUpdate);
            $stmt->bindParam(':account_id', $account['account_id']);
            $stmt->execute();
            
            // Log status change in history
            $stmt = $db->prepare("
                INSERT INTO account_status_history (
                    account_id, previous_status, new_status, balance_at_change, 
                    reason, changed_by
                ) VALUES (
                    :account_id, :prev_status, :new_status, :balance, 
                    :reason, :employee_id
                )
            ");
            $reason = "Withdrawal resulted in balance below maintaining requirement";
            if ($newBalance == 0) {
                $reason = "Account balance reached zero - flagged for removal";
            }
            $stmt->bindParam(':account_id', $account['account_id']);
            $stmt->bindParam(':prev_status', $account['account_status']);
            $stmt->bindParam(':new_status', $statusUpdate);
            $stmt->bindParam(':balance', $newBalance);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':employee_id', $employeeId);
            $stmt->execute();
        }

        // --- 9. Get employee information for the response ---
        // Table: bank_employees
        $stmt = $db->prepare("SELECT employee_name FROM bank_employees WHERE employee_id = :employee_id");
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        $employeeName = $employee ? $employee['employee_name'] : 'System Admin';

        // --- 10. Commit transaction ---
        $db->commit();

        // --- 11. Format response ---
        // Format customer name
        $customerName = trim($account['first_name'] . ' ' . 
                            ($account['middle_name'] ? $account['middle_name'] . ' ' : '') . 
                            $account['last_name']);

        // Return success with transaction details
        $response = [
            'success' => true,
            'message' => 'Withdrawal processed successfully',
            'data' => [
                'transaction_reference' => $transactionRef,
                'account_number' => $accountNumber,
                'customer_name' => $customerName,
                'customer_id' => $account['customer_id'],
                'amount' => number_format($amount, 2),
                'previous_balance' => number_format($previousBalance, 2), 
                'new_balance' => number_format($newBalance, 2),
                'transaction_date' => date('F d, Y h:i A'),
                'transaction_datetime' => date('Y-m-d H:i:s'),
                'account_type' => $account['account_type'],
                'employee_name' => $employeeName,
                'employee_id' => $employeeId,
                'branch' => 'Main Branch',
                'terminal' => 'Teller-01',
                'maintaining_balance' => number_format($maintainingBalance, 2),
                'account_status' => $statusUpdate ?? $account['account_status']
            ]
        ];
        
        // Add warnings if any
        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }
        
        echo json_encode($response);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        error_log("Withdrawal transaction error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Transaction failed: ' . $e->getMessage()
        ]);
        exit(); // Exit to prevent outer catch from executing
    }

} catch (Exception $e) {
    error_log("Withdrawal processing error: " . $e->getMessage());
    
    // Only output if headers not sent and no output yet
    if (!headers_sent()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An unexpected error occurred during processing.'
        ]);
    }
}
?>