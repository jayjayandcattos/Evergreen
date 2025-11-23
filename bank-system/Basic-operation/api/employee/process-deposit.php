<?php
/**
 * Process Deposit API
 * Processes a deposit transaction for a customer account
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
    // NOTE: This logic uses the transaction-based calculation guide provided:
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
    // Use number_format to handle potential scientific notation issue, then convert to float.
    $amount = floatval(str_replace(',', '', $input['amount']));

    // Validate amount
    if ($amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid deposit amount'
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
        // Tables: customer_accounts (ca), bank_customers (bc), bank_account_types (bat)
        $stmt = $db->prepare("
            SELECT 
                ca.account_id,
                ca.customer_id,
                ca.is_locked,
                bat.account_type_id,
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
        
        // --- 2. Calculate previous balance ---
        $previousBalance = calculateCurrentBalance($db, $account['account_id']);

        // --- 3. Determine New Balance ---
        $newBalance = $previousBalance + $amount;
        
        // --- 4. Get 'Deposit' transaction_type_id ---
        $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Deposit'");
        $stmt->execute();
        $depositType = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$depositType) {
            throw new Exception('Transaction type "Deposit" not found in transaction_types table.');
        }
        $depositTypeId = $depositType['transaction_type_id'];
        
        // --- 5. Generate transaction reference number ---
        $transactionRef = 'DP' . date('Ymd') . str_pad($account['account_id'], 6, '0', STR_PAD_LEFT) . rand(1000, 9999);

        // --- 6. Insert transaction record ---
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
        // Ensure you have an employee with ID 1 in bank_employees or adjust this logic.
        $employeeId = $_SESSION['employee_id'] ?? 1; 
        $description = 'Cash deposit - Ref: ' . $transactionRef;
        
        $stmt->bindParam(':transaction_ref', $transactionRef);
        $stmt->bindParam(':account_id', $account['account_id']);
        $stmt->bindParam(':transaction_type_id', $depositTypeId);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();

        // --- 7. Get employee information for the response ---
        // Table: bank_employees
        $stmt = $db->prepare("SELECT employee_name FROM bank_employees WHERE employee_id = :employee_id");
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        $employeeName = $employee ? $employee['employee_name'] : 'System Admin';

        // --- 8. Commit transaction ---
        $db->commit();

        // --- 9. Format response ---
        // Format customer name
        $customerName = trim($account['first_name'] . ' ' . 
                            ($account['middle_name'] ? $account['middle_name'] . ' ' : '') . 
                            $account['last_name']);

        // Return success with transaction details
        echo json_encode([
            'success' => true,
            'message' => 'Deposit processed successfully',
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
                'terminal' => 'Teller-01'
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Transaction failed: ' . $e->getMessage()
        ]);
        // Re-throw for logging in the outer catch block
        throw $e; 
    }

} catch (PDOException $e) {
    error_log("Deposit processing PDO error: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("SQL State: " . $e->getCode());
    
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
        
        // Show detailed error for debugging
        $errorMessage = 'Transaction failed: ' . $e->getMessage();
        
        // Check if it's the account_name error from the trigger
        if (strpos($e->getMessage(), 'account_name') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
            $errorMessage = 'Database trigger error detected. Please run fix_database_trigger.sql in your database to fix this. Original error: ' . $e->getMessage();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage,
            'error_code' => $e->getCode(),
            'error_info' => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    error_log("Deposit processing error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Transaction failed: ' . $e->getMessage()
        ]);
    }
}
?>