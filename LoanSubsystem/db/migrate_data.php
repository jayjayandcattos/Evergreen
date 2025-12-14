<?php
require_once __DIR__ . '/../config/database.php';

$conn = getDBConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to BankingDB successfully.\n";

// Check if loan_types table exists (it should)
$checkTable = $conn->query("SHOW TABLES LIKE 'loan_types'");
if ($checkTable->num_rows == 0) {
    die("Error: loan_types table does not exist. Please run unified_schema.sql first.\n");
}

// Check if loan_types is empty
$result = $conn->query("SELECT COUNT(*) as count FROM loan_types");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "loan_types table is empty. Populating with default loan types...\n";
    
    $loanTypes = [
        ['PL', 'Personal Loan', 500000.00, 36, 0.20, 'Unsecured loan for personal use'],
        ['SL', 'Salary Loan', 100000.00, 24, 0.15, 'Loan based on monthly salary'],
        ['BL', 'Business Loan', 5000000.00, 60, 0.12, 'Loan for business expansion'],
        ['EL', 'Emergency Loan', 50000.00, 12, 0.10, 'Quick loan for emergencies'],
        ['HL', 'Housing Loan', 10000000.00, 240, 0.08, 'Loan for purchasing property'],
        ['AL', 'Auto Loan', 2000000.00, 60, 0.10, 'Loan for purchasing a vehicle']
    ];
    
    $stmt = $conn->prepare("INSERT INTO loan_types (code, name, max_amount, max_term_months, interest_rate, description) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($loanTypes as $type) {
        $stmt->bind_param("ssdids", $type[0], $type[1], $type[2], $type[3], $type[4], $type[5]);
        if ($stmt->execute()) {
            echo "Inserted: " . $type[1] . "\n";
        } else {
            echo "Error inserting " . $type[1] . ": " . $stmt->error . "\n";
        }
    }
    $stmt->close();
    echo "Population complete.\n";
} else {
    echo "loan_types table already has data. Skipping population.\n";
}

// Check if loans table exists
$checkLoans = $conn->query("SHOW TABLES LIKE 'loans'");
if ($checkLoans->num_rows == 0) {
    echo "Warning: loans table does not exist. Please run unified_schema.sql.\n";
} else {
    echo "loans table exists.\n";
}

// Check if loan_applications table exists
$checkApps = $conn->query("SHOW TABLES LIKE 'loan_applications'");
if ($checkApps->num_rows == 0) {
    echo "Warning: loan_applications table does not exist. Please run unified_schema.sql.\n";
} else {
    echo "loan_applications table exists.\n";
}

$conn->close();
?>
