<?php
/**
 * Get Transactions API
 * Retrieves all transactions with optional filtering
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

try {
    // Get filter parameters
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $fromDate = isset($_GET['from']) ? trim($_GET['from']) : '';
    $toDate = isset($_GET['to']) ? trim($_GET['to']) : '';
    $dateFilter = isset($_GET['dateFilter']) ? trim($_GET['dateFilter']) : '';

    // Connect to database
    $db = getDBConnection();
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }

    // Build base query, using unified_schema table names:
    // bank_transactions (bt), customer_accounts (ca), bank_customers (bc), bank_employees (be), transaction_types (tt)
    $query = "
        SELECT 
            bt.transaction_id,
            bt.transaction_ref,
            bt.created_at,
            bt.amount,
            bt.description,
            ca.account_number,
            CONCAT(bc.first_name, ' ', 
                CASE WHEN bc.middle_name IS NOT NULL THEN CONCAT(bc.middle_name, ' ') ELSE '' END,
                bc.last_name) as customer_name,
            tt.type_name as transaction_type,
            be.employee_name
        FROM bank_transactions bt
        INNER JOIN customer_accounts ca ON bt.account_id = ca.account_id
        INNER JOIN bank_customers bc ON ca.customer_id = bc.customer_id
        INNER JOIN transaction_types tt ON bt.transaction_type_id = tt.transaction_type_id
        LEFT JOIN bank_employees be ON bt.employee_id = be.employee_id
        WHERE 1=1
    ";

    $params = [];
    
    // Add type filter
    if (!empty($type) && $type !== 'all') {
        // Convert input types to match potential type_name values in the DB
        if ($type === 'withdrawal') {
            $query .= " AND tt.type_name = :type_name";
            $params[':type_name'] = 'Withdrawal';
        } elseif ($type === 'deposit') {
            $query .= " AND tt.type_name = :type_name";
            $params[':type_name'] = 'Deposit';
        } elseif ($type === 'transfer') {
            // Match both Transfer In and Transfer Out (unified schema has both)
            $query .= " AND (tt.type_name = 'Transfer In' OR tt.type_name = 'Transfer Out')";
            // No parameter binding needed for this case
        } else {
            // Use the provided type directly if it's not one of the common aliases
            $query .= " AND tt.type_name = :type_name";
            $params[':type_name'] = ucfirst($type);
        }
    }

    // Add date filter
    if (!empty($dateFilter)) {
        $today = date('Y-m-d');
        
        if ($dateFilter === 'last30days') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $query .= " AND DATE(bt.created_at) >= :startDate";
            $params[':startDate'] = $startDate;
        } elseif ($dateFilter === 'lastmonth') {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
            $query .= " AND DATE(bt.created_at) BETWEEN :startDate AND :endDate";
            $params[':startDate'] = $startDate;
            $params[':endDate'] = $endDate;
        } elseif ($dateFilter === 'thisyear') {
            $startDate = date('Y-01-01');
            $query .= " AND DATE(bt.created_at) >= :startDate";
            $params[':startDate'] = $startDate;
        }
    }

    // Add custom date range filter
    if (!empty($fromDate)) {
        $query .= " AND DATE(bt.created_at) >= :fromDate";
        $params[':fromDate'] = $fromDate;
    }
    
    if (!empty($toDate)) {
        $query .= " AND DATE(bt.created_at) <= :toDate";
        $params[':toDate'] = $toDate;
    }

    // Order by most recent first
    $query .= " ORDER BY bt.created_at DESC";

    // Execute query
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format transactions for frontend
    $formattedTransactions = [];
    foreach ($transactions as $transaction) {
        // Use the transaction_ref field from the database, if available
        $reference = $transaction['transaction_ref'] ?? 'N/A';
        
        // If transaction_ref is not explicitly selected/available, fallback to parsing description
        if ($reference === 'N/A') {
             $description = $transaction['description'];
             $referenceMatch = [];
             preg_match('/Ref:\s*([A-Z0-9]+)/', $description, $referenceMatch);
             $reference = $referenceMatch[1] ?? 'N/A';
        }

        // Format date - better readable format
        $date = new DateTime($transaction['created_at']);
        $formattedDate = $date->format('M d, Y h:i A'); // e.g., "Jan 15, 2024 02:30 PM"

        // Determine method based on description or type
        $method = 'Cash'; // Default
        if (strpos($transaction['transaction_type'], 'Transfer') !== false) {
             $method = 'Electronic';
        } elseif (strpos($transaction['transaction_type'], 'Loan') !== false || 
                  strpos($transaction['transaction_type'], 'Interest') !== false || 
                  strpos($transaction['transaction_type'], 'Charge') !== false) {
             $method = 'System';
        } elseif (strpos($transaction['description'], 'Teller') !== false) {
             $method = 'Teller';
        }
        // If not set, it remains 'Cash' for typical Deposit/Withdrawal

        $formattedTransactions[] = [
            'transaction_id' => $transaction['transaction_id'],
            'reference' => $reference,
            'date' => $formattedDate,
            'account_number' => $transaction['account_number'],
            'title' => $transaction['description'] ?? $transaction['transaction_type'], // Add title field for HTML table
            'customer_name' => $transaction['customer_name'],
            'type' => $transaction['transaction_type'], // Use type_name from transaction_types
            'method' => $method,
            'amount' => number_format($transaction['amount'], 2),
            'status' => 'Completed', // All transactions in DB are completed
            'employee' => $transaction['employee_name'] ?? 'System',
            'raw_date' => $transaction['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedTransactions,
        'count' => count($formattedTransactions)
    ]);

} catch (Exception $e) {
    error_log("Get transactions error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve transactions',
        'error' => $e->getMessage()
    ]);
}
?>