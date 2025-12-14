<?php
/**
 * Approve Account Application
 * Approves a pending application and creates the actual bank account
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

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['application_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Application ID is required'
        ]);
        exit();
    }
    
    $applicationId = $data['application_id'];
    $employeeId = $_SESSION['employee_id'] ?? 1; // Default to employee ID 1 for testing
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // 1. Get application details
        $stmt = $db->prepare("
            SELECT * FROM account_applications 
            WHERE application_id = :application_id 
            AND application_status = 'pending'
        ");
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            throw new Exception('Application not found or already processed');
        }
        
        // 2. Get customer_id - check both application_id link and direct customer_id in account_applications
        $customerId = null;
        
        // First, try to get customer_id directly from account_applications (for existing customers)
        if (!empty($application['customer_id'])) {
            $customerId = $application['customer_id'];
            error_log("Found customer_id directly in application: " . $customerId);
        } else {
            // If not found, look up by application_id in bank_customers (for new customers)
            $stmt = $db->prepare("
                SELECT customer_id 
                FROM bank_customers 
                WHERE application_id = :application_id
            ");
            $stmt->bindParam(':application_id', $applicationId);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                $customerId = $customer['customer_id'];
                error_log("Found customer_id via application_id link: " . $customerId);
            }
        }
        
        if (!$customerId) {
            throw new Exception('Customer record not found for this application');
        }
        
        // 3. Get account type ID
        $accountTypeBase = $application['account_type']; // e.g., 'Savings Account' or 'Checking Account'
        $stmt = $db->prepare("
            SELECT account_type_id, type_name
            FROM bank_account_types 
            WHERE type_name LIKE :type_name
        ");
        // Use LIKE to match 'Savings' with 'Savings Account' or 'Checking' with 'Checking Account'
        $searchPattern = '%' . $accountTypeBase . '%';
        $stmt->bindParam(':type_name', $searchPattern);
        $stmt->execute();
        $accountType = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$accountType) {
            throw new Exception('Account type not found: ' . $accountTypeBase);
        }
        
        $accountTypeId = $accountType['account_type_id'];
        
        // 4. Generate account number in format: SA-XXXX-YYYY or CHA-XXXX-YYYY
        // SA = Savings Account, CHA = Checking Account
        // XXXX = Random 4-digit number
        // YYYY = Current year
        // Check if it's a Savings Account (type_name contains 'Savings')
        $isSavings = stripos($accountType['type_name'], 'Savings') !== false;
        $prefix = $isSavings ? 'SA' : 'CHA';
        $randomNumber = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $year = date('Y');
        $accountNumber = $prefix . '-' . $randomNumber . '-' . $year;
        
        // Check if account number already exists (very unlikely but good practice)
        $maxAttempts = 10;
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM customer_accounts WHERE account_number = :account_number");
            $checkStmt->bindParam(':account_number', $accountNumber);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                break; // Account number is unique
            }
            
            // Generate new random number
            $randomNumber = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $accountNumber = $prefix . '-' . $randomNumber . '-' . $year;
            $attempt++;
        }
        
        // 5. Create customer_accounts record
        $stmt = $db->prepare("
            INSERT INTO customer_accounts (
                account_number,
                customer_id,
                account_type_id,
                is_locked,
                created_at,
                created_by_employee_id,
                account_status
            ) VALUES (
                :account_number,
                :customer_id,
                :account_type_id,
                0,
                NOW(),
                :employee_id,
                'active'
            )
        ");
        
        $stmt->bindParam(':account_number', $accountNumber);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_type_id', $accountTypeId);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->execute();
        
        $accountId = $db->lastInsertId();
        
        // 6. Link account to customer in customer_linked_accounts
        $stmt = $db->prepare("
            INSERT INTO customer_linked_accounts (
                customer_id,
                account_id,
                is_active
            ) VALUES (
                :customer_id,
                :account_id,
                1
            )
        ");
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':account_id', $accountId);
        $stmt->execute();
        
        // 7. Update application status to approved
        $stmt = $db->prepare("
            UPDATE account_applications 
            SET application_status = 'approved',
                reviewed_at = NOW(),
                reviewed_by_employee_id = :employee_id
            WHERE application_id = :application_id
        ");
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->bindParam(':application_id', $applicationId);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application approved successfully',
            'account_number' => $accountNumber,
            'account_id' => $accountId,
            'customer_id' => $customerId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Database error in approve-application.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in approve-application.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
