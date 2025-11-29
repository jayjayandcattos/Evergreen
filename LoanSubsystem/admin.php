<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "loanmanagement_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = mysqli_connect($servername, $username, $password, $database);
    echo "Connected successfully!";
} catch (mysqli_sql_exception $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
