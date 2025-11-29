<?php
// Suppress error output to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Clean any output that might have been generated
ob_clean();

header('Content-Type: application/json');

// Auto-login bridge: Check if user is logged in via marketing system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        exit(json_encode(['error' => 'Not authenticated']));
    }
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "loan_system";

// Try to connect to loan_system database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    // Database doesn't exist, try to create it
    $conn_no_db = new mysqli($host, $user, $pass);
    if ($conn_no_db->connect_error) {
        exit(json_encode(['error' => 'Cannot connect to MySQL server. Error: ' . $conn_no_db->connect_error]));
    }
    
    // Create database
    if ($conn_no_db->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci") === TRUE) {
        $conn_no_db->close();
        // Try connecting again
        $conn = new mysqli($host, $user, $pass, $db);
        if ($conn->connect_error) {
            exit(json_encode(['error' => 'Database created but connection failed: ' . $conn->connect_error]));
        }
    } else {
        exit(json_encode(['error' => 'Failed to create database: ' . $conn_no_db->error]));
    }
}

// Check if loan_applications table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'loan_applications'");
if (!$table_check || $table_check->num_rows == 0) {
    // Create loan_applications table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `loan_applications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `loan_type_id` int(11) DEFAULT NULL,
        `full_name` varchar(100) DEFAULT NULL,
        `account_number` varchar(50) DEFAULT NULL,
        `contact_number` varchar(20) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `job` varchar(255) DEFAULT NULL,
        `monthly_salary` decimal(10,2) DEFAULT NULL,
        `user_email` varchar(255) NOT NULL,
        `loan_terms` varchar(50) DEFAULT NULL,
        `loan_amount` decimal(12,2) DEFAULT NULL,
        `purpose` text DEFAULT NULL,
        `monthly_payment` decimal(10,2) DEFAULT NULL,
        `due_date` date DEFAULT NULL,
        `status` varchar(50) DEFAULT 'Pending',
        `remarks` text DEFAULT NULL,
        `file_name` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `approved_by` varchar(100) DEFAULT NULL,
        `approved_at` datetime DEFAULT NULL,
        `next_payment_due` date DEFAULT NULL,
        `rejected_by` varchar(255) DEFAULT NULL,
        `rejected_at` datetime DEFAULT NULL,
        `rejection_remarks` text DEFAULT NULL,
        `proof_of_income` varchar(255) DEFAULT NULL,
        `coe_document` varchar(255) DEFAULT NULL,
        `pdf_path` varchar(255) DEFAULT NULL,
        `pdf_approved` varchar(255) DEFAULT NULL,
        `pdf_active` varchar(255) DEFAULT NULL,
        `pdf_rejected` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (!$conn->query($create_table_sql)) {
        exit(json_encode(['error' => 'Failed to create loan_applications table: ' . $conn->error]));
    }
    
    // Create loan_types table if it doesn't exist
    $loan_types_check = $conn->query("SHOW TABLES LIKE 'loan_types'");
    if (!$loan_types_check || $loan_types_check->num_rows == 0) {
        $create_loan_types_sql = "CREATE TABLE IF NOT EXISTS `loan_types` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if (!$conn->query($create_loan_types_sql)) {
            exit(json_encode(['error' => 'Failed to create loan_types table: ' . $conn->error]));
        }
        
        // Insert default loan types
        $conn->query("INSERT IGNORE INTO `loan_types` (`id`, `name`) VALUES 
            (1, 'Personal Loan'),
            (2, 'Car Loan'),
            (3, 'Home Loan'),
            (4, 'Multi-Purpose Loan')");
    }
}

// ✅ Ensure new PDF columns exist (check first to avoid errors)
$columns_to_add = [
    'pdf_approved' => 'VARCHAR(255) DEFAULT NULL',
    'pdf_active' => 'VARCHAR(255) DEFAULT NULL',
    'pdf_rejected' => 'VARCHAR(255) DEFAULT NULL'
];

foreach ($columns_to_add as $column => $definition) {
    // Check if column exists (suppress errors)
    @$check_column = $conn->query("SHOW COLUMNS FROM loan_applications LIKE '$column'");
    if ($check_column && $check_column->num_rows == 0) {
        // Column doesn't exist, add it (suppress errors if column already exists)
        @$conn->query("ALTER TABLE loan_applications ADD COLUMN $column $definition");
    }
}

$email = $_SESSION['user_email'];

// ✅ Fetch all PDF columns separately
$stmt = $conn->prepare("
    SELECT 
        la.id,
        la.full_name,
        la.email,
        la.contact_number,
        la.loan_amount,
        la.loan_terms,
        la.monthly_payment,
        la.status,
        la.next_payment_due,
        la.due_date,
        la.created_at,
        la.approved_at,
        la.rejected_at,
        la.remarks,
        la.rejection_remarks,
        la.pdf_approved,
        la.pdf_active,
        la.pdf_rejected,
        COALESCE(lt.name, 'Unknown') AS loan_type
    FROM loan_applications la
    LEFT JOIN loan_types lt ON la.loan_type_id = lt.id
    WHERE la.email = ?
    ORDER BY la.id DESC
");

if (!$stmt) {
    $conn->close();
    exit(json_encode(['error' => 'Database query failed: ' . $conn->error]));
}

$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    $error_msg = $stmt->error;
    $stmt->close();
    $conn->close();
    exit(json_encode(['error' => 'Query execution failed: ' . $error_msg]));
}

$result = $stmt->get_result();

$loans = [];
while ($row = $result->fetch_assoc()) {
    $loans[] = $row;
}

$stmt->close();
$conn->close();

// Clean any trailing output and send JSON
ob_clean();
echo json_encode($loans);
ob_end_flush();
?>