<?php
session_start();

// Auto-login bridge: Check if user is logged in via marketing system
if (!isset($_SESSION['user_email'])) {
    // Check for marketing session variables (from evergreen-marketing)
    if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
        $_SESSION['user_email'] = $_SESSION['email'];
        $_SESSION['user_name'] = $_SESSION['full_name'] ?? ($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
        $_SESSION['user_role'] = 'client';
    } else {
        die("Error: Not authenticated. Please log in.");
    }
}

// Get user data from bank_customers database
$host = "localhost";
$user = "root";
$pass = "";
$db = "BankingDB";
$bankingConn = new mysqli($host, $user, $pass, $db);

$currentUser = null;
if (!$bankingConn->connect_error) {
    $email = $_SESSION['user_email'];
    $sql = "SELECT 
                bc.customer_id,
                bc.first_name,
                bc.middle_name,
                bc.last_name,
                bc.email,
                bc.contact_number,
                TRIM(CONCAT(bc.first_name, ' ', IFNULL(bc.middle_name, ''), ' ', bc.last_name)) as full_name,
                (SELECT ca.account_number 
                 FROM customer_accounts ca 
                 WHERE ca.customer_id = bc.customer_id 
                 LIMIT 1) as account_number
            FROM bank_customers bc
            WHERE bc.email = ?
            LIMIT 1";
    $stmt = $bankingConn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentUser = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'account_number' => $row['account_number'] ?? 'N/A',
                'contact_number' => $row['contact_number'] ?? 'N/A',
                'job' => $_POST['job'] ?? 'Not Specified',
                'monthly_salary' => isset($_POST['monthly_salary']) ? (float)$_POST['monthly_salary'] : 0
            ];
        }
        $stmt->close();
    }
    $bankingConn->close();
    // Redirect to dashboard with success message
    header("Location: index.php?scrollTo=dashboard&success=1");
    exit();
} else {
    $error_message = $stmt->error;
    $stmt->close();
    $conn->close();
    die("Database error: " . $error_message);
}
?>