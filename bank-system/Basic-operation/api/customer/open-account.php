<?php
/**
 * Open New Account API
 * Opens a new Savings or Checking account for an existing customer
 */

// Start session before any output
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
require_once '../../includes/functions.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to open an account. Please log in first.'
        ]);
        exit();
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request data'
        ]);
        exit();
    }

    // Validate required fields
    $errors = [];
    
    // Validate existing account number
    if (empty($input['existing_account_number'])) {
        $errors['existing_account_number'] = 'Existing account number is required';
    }
    
    if (empty($input['account_type'])) {
        $errors['account_type'] = 'Account type is required';
    } elseif (!in_array($input['account_type'], ['Savings', 'Checking'])) {
        $errors['account_type'] = 'Invalid account type. Must be Savings or Checking';
    }

    // Validate initial deposit if provided
    $initialDeposit = null;
    $depositSource = null;
    $sourceAccountNumber = null;
    
    if (isset($input['initial_deposit']) && $input['initial_deposit'] !== null) {
        $initialDeposit = floatval($input['initial_deposit']);
        if ($initialDeposit < 0) {
            $errors['initial_deposit'] = 'Initial deposit cannot be negative';
        }
        
        // If deposit amount is provided, validate deposit source
        if ($initialDeposit > 0) {
            $depositSource = $input['deposit_source'] ?? null;
            
            if (empty($depositSource)) {
                $errors['deposit_source'] = 'Please select a deposit source (Cash or Transfer)';
            } elseif ($depositSource === 'transfer') {
                $sourceAccountNumber = $input['source_account_number'] ?? null;
                if (empty($sourceAccountNumber)) {
                    $errors['source_account_number'] = 'Please select a source account for transfer';
                }
            }
        }
    }

    // Return validation errors if any
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit();
    }

    // Get customer ID from session
    $customerId = $_SESSION['customer_id'];
    $accountTypeName = $input['account_type'];

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Verify customer exists
    $stmt = $db->prepare("SELECT customer_id FROM bank_customers WHERE customer_id = :customer_id");
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found. Please log in again.'
        ]);
        exit();
    }

    // Verify existing account number belongs to the customer
    if (!empty($input['existing_account_number'])) {
        $existingAccountNumber = trim($input['existing_account_number']);
        
        $stmt = $db->prepare("
            SELECT ca.account_id, ca.account_number, ca.customer_id, ca.is_locked
            FROM customer_accounts ca
            INNER JOIN customer_linked_accounts cla ON ca.account_id = cla.account_id
            WHERE ca.account_number = :account_number 
            AND cla.customer_id = :customer_id 
            AND cla.is_active = 1
            LIMIT 1
        ");
        $stmt->bindParam(':account_number', $existingAccountNumber);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        $existingAccount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingAccount) {
            echo json_encode([
                'success' => false,
                'message' => 'The existing account number does not belong to you. Please enter a valid account number.',
                'errors' => [
                    'existing_account_number' => 'This account number does not belong to you or does not exist.'
                ]
            ]);
            exit();
        }
        
        // Check if existing account is locked
        if ($existingAccount['is_locked']) {
            echo json_encode([
                'success' => false,
                'message' => 'Your existing account is locked. Please contact customer service.',
                'errors' => [
                    'existing_account_number' => 'This account is locked and cannot be used for verification.'
                ]
            ]);
            exit();
        }
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Get account type ID
        $stmt = $db->prepare("SELECT account_type_id FROM bank_account_types WHERE type_name = :type_name LIMIT 1");
        $stmt->bindParam(':type_name', $accountTypeName);
        $stmt->execute();
        $accountType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If account type doesn't exist, create it
        if (!$accountType) {
            $stmt = $db->prepare("INSERT INTO bank_account_types (type_name, description) VALUES (:type_name, :description)");
            $description = $accountTypeName . ' Account';
            $stmt->bindParam(':type_name', $accountTypeName);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
            $accountTypeId = $db->lastInsertId();
        } else {
            $accountTypeId = $accountType['account_type_id'];
        }
        
        // Set interest rate: 0.5% (0.50) for Savings accounts, NULL for Checking accounts
        $interestRate = (strtolower($accountTypeName) === 'savings') ? 0.50 : NULL;
        
        // Generate unique account number
        $accountNumber = generateAccountNumber($db, $accountTypeName);
        
        // Get employee ID from session (if available) or default to 1
        $employeeId = $_SESSION['employee_id'] ?? 1;
        
        // Insert new account
        $stmt = $db->prepare("
            INSERT INTO customer_accounts (
                customer_id, 
                account_number, 
                account_type_id, 
                interest_rate, 
                created_by_employee_id,
                created_at
            ) VALUES (
                :customer_id, 
                :account_number, 
                :account_type_id, 
                :interest_rate, 
                :employee_id,
                NOW()
            )
        ");
        
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':account_type_id', $accountTypeId);
        $stmt->bindParam(':interest_rate', $interestRate);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        $accountId = $db->lastInsertId();
        
        // If initial deposit is provided, handle it based on deposit source
        if ($initialDeposit && $initialDeposit > 0) {
            if ($depositSource === 'transfer' && $sourceAccountNumber) {
                // Transfer from existing account
                // 1. Get source account details
                $stmt = $db->prepare("
                    SELECT account_id, customer_id 
                    FROM customer_accounts 
                    WHERE account_number = :account_number
                    FOR UPDATE
                ");
                $stmt->bindParam(':account_number', $sourceAccountNumber);
                $stmt->execute();
                $sourceAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$sourceAccount) {
                    throw new Exception('Source account not found');
                }
                
                // Verify source account belongs to the same customer
                if ($sourceAccount['customer_id'] != $customerId) {
                    throw new Exception('Source account does not belong to you');
                }
                
                // Check if source account is locked
                $stmt = $db->prepare("SELECT is_locked FROM customer_accounts WHERE account_id = :account_id");
                $stmt->bindParam(':account_id', $sourceAccount['account_id']);
                $stmt->execute();
                $locked = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($locked && $locked['is_locked']) {
                    throw new Exception('Source account is locked');
                }
                
                // Calculate source account balance
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
                $stmt->bindParam(':account_id', $sourceAccount['account_id']);
                $stmt->execute();
                $balanceResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $sourceBalance = (float) ($balanceResult['current_balance'] ?? 0.00);
                
                // Check sufficient balance
                if ($sourceBalance < $initialDeposit) {
                    throw new Exception('Insufficient balance in source account. Available: ' . number_format($sourceBalance, 2));
                }
                
                // Get transaction types
                $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Transfer Out' LIMIT 1");
                $stmt->execute();
                $transferOutType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Transfer In' LIMIT 1");
                $stmt->execute();
                $transferInType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$transferOutType || !$transferInType) {
                    throw new Exception('Transfer transaction types not found in database');
                }
                
                $transferOutTypeId = $transferOutType['transaction_type_id'];
                $transferInTypeId = $transferInType['transaction_type_id'];
                
                // Generate transaction references
                $transferRef = 'TF' . date('Ymd') . str_pad($sourceAccount['account_id'], 6, '0', STR_PAD_LEFT) . rand(1000, 9999);
                
                // 2. Create Transfer Out transaction on source account
                $stmt = $db->prepare("
                    INSERT INTO bank_transactions (
                        transaction_ref,
                        account_id,
                        transaction_type_id,
                        amount,
                        related_account_id,
                        description,
                        employee_id,
                        created_at
                    ) VALUES (
                        :transaction_ref,
                        :account_id,
                        :transaction_type_id,
                        :amount,
                        :related_account_id,
                        :description,
                        :employee_id,
                        NOW()
                    )
                ");
                
                $description = 'Transfer to new account ' . $accountNumber;
                $stmt->bindParam(':transaction_ref', $transferRef);
                $stmt->bindParam(':account_id', $sourceAccount['account_id']);
                $stmt->bindParam(':transaction_type_id', $transferOutTypeId);
                $stmt->bindParam(':amount', $initialDeposit);
                $stmt->bindParam(':related_account_id', $accountId);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':employee_id', $employeeId);
                $stmt->execute();
                
                // 3. Create Transfer In transaction on new account
                $stmt = $db->prepare("
                    INSERT INTO bank_transactions (
                        transaction_ref,
                        account_id,
                        transaction_type_id,
                        amount,
                        related_account_id,
                        description,
                        employee_id,
                        created_at
                    ) VALUES (
                        :transaction_ref,
                        :account_id,
                        :transaction_type_id,
                        :amount,
                        :related_account_id,
                        :description,
                        :employee_id,
                        NOW()
                    )
                ");
                
                $description = 'Initial deposit - Transfer from ' . $sourceAccountNumber;
                $stmt->bindParam(':transaction_ref', $transferRef);
                $stmt->bindParam(':account_id', $accountId);
                $stmt->bindParam(':transaction_type_id', $transferInTypeId);
                $stmt->bindParam(':amount', $initialDeposit);
                $stmt->bindParam(':related_account_id', $sourceAccount['account_id']);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':employee_id', $employeeId);
                $stmt->execute();
                
            } else {
                // Cash deposit
                // Get 'Deposit' transaction type ID
                $stmt = $db->prepare("SELECT transaction_type_id FROM transaction_types WHERE type_name = 'Deposit' LIMIT 1");
                $stmt->execute();
                $depositType = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($depositType) {
                    $depositTypeId = $depositType['transaction_type_id'];
                    
                    // Generate transaction reference
                    $transactionRef = 'DP' . date('Ymd') . str_pad($accountId, 6, '0', STR_PAD_LEFT) . rand(1000, 9999);
                    
                    // Insert deposit transaction
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
                    
                    $description = 'Initial deposit - Account opening';
                    $stmt->bindParam(':transaction_ref', $transactionRef);
                    $stmt->bindParam(':account_id', $accountId);
                    $stmt->bindParam(':transaction_type_id', $depositTypeId);
                    $stmt->bindParam(':amount', $initialDeposit);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':employee_id', $employeeId);
                    $stmt->execute();
                }
            }
        }
        
        // Link account to customer if not already linked
        $stmt = $db->prepare("
            SELECT link_id FROM customer_linked_accounts 
            WHERE customer_id = :customer_id AND account_id = :account_id
        ");
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_id', $accountId);
        $stmt->execute();
        $link = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$link) {
            $stmt = $db->prepare("
                INSERT INTO customer_linked_accounts (customer_id, account_id, is_active, linked_at)
                VALUES (:customer_id, :account_id, 1, NOW())
            ");
            $stmt->bindParam(':customer_id', $customerId);
            $stmt->bindParam(':account_id', $accountId);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        
        // Return success with account number
        echo json_encode([
            'success' => true,
            'message' => 'Account opened successfully',
            'account_number' => $accountNumber,
            'account_type' => $accountTypeName,
            'account_id' => $accountId,
            'initial_deposit' => $initialDeposit
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Open account error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while opening your account. Please try again.',
        'debug' => $e->getMessage()
    ]);
}
?>

