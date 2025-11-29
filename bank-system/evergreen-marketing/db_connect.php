<?php
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db = "BankingDB";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    // Don't die() here - let the calling script handle the error
    // This prevents HTML error output when API expects JSON
    $db_connection_error = "Connection failed: " . $conn->connect_error;
}
?>

