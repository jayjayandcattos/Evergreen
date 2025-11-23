<?php
session_start();
include("db_connect.php");

echo "<h2>Session Debug Information</h2>";
echo "<h3>Current Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    
    echo "<h3>Bank Users Table (Marketing System):</h3>";
    $sql1 = "SELECT id, first_name, last_name, email, bank_id FROM bank_users WHERE email = ?";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("s", $email);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    if ($row1 = $result1->fetch_assoc()) {
        echo "<pre>";
        print_r($row1);
        echo "</pre>";
    } else {
        echo "<p>No record found in bank_users</p>";
    }
    
    echo "<h3>Bank Customers Table (Basic-operation System):</h3>";
    $sql2 = "SELECT customer_id, first_name, last_name, email FROM bank_customers WHERE email = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($row2 = $result2->fetch_assoc()) {
        echo "<pre>";
        print_r($row2);
        echo "</pre>";
    } else {
        echo "<p>No record found in bank_customers</p>";
    }
    
    if (isset($_SESSION['customer_id'])) {
        echo "<h3>Accounts for customer_id = " . $_SESSION['customer_id'] . ":</h3>";
        $sql3 = "SELECT ca.account_id, ca.account_number, bat.type_name, bc.first_name, bc.last_name
                 FROM customer_linked_accounts cla
                 JOIN customer_accounts ca ON cla.account_id = ca.account_id
                 JOIN bank_customers bc ON cla.customer_id = bc.customer_id
                 LEFT JOIN bank_account_types bat ON ca.account_type_id = bat.account_type_id
                 WHERE cla.customer_id = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("i", $_SESSION['customer_id']);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        echo "<pre>";
        while ($row3 = $result3->fetch_assoc()) {
            print_r($row3);
        }
        echo "</pre>";
    }
} else {
    echo "<p>Not logged in - no email in session</p>";
}

echo "<hr>";
echo "<a href='login.php'>Go to Login</a> | ";
echo "<a href='viewingpage.php'>Go to Viewing Page</a> | ";
echo "<a href='../Basic-operation/operations/public/customer/account'>Go to Basic-operation Account</a>";
?>
