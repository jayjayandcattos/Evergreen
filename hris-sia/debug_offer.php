<?php
require_once 'config/database.php';

echo "# Debug Offer Token Report\n\n";

try {
    // 1. Check if column exists
    echo "## 1. Column Check\n";
    $stmt = $conn->query("SHOW COLUMNS FROM applicant LIKE 'offer_token'");
    $column = $stmt->fetch();
    if ($column) {
        echo "- Column 'offer_token' EXISTS. Type: " . $column['Type'] . "\n";
    } else {
        echo "- Column 'offer_token' DOES NOT EXIST!\n";
    }

    // 2. Check recent applicants
    echo "\n## 2. Recent Applicants\n";
    $stmt = $conn->query("SELECT applicant_id, full_name, application_status, offer_status, offer_token, offer_sent_at FROM applicant ORDER BY applicant_id DESC LIMIT 5");
    $applicants = $stmt->fetchAll();

    foreach ($applicants as $a) {
        $tokenLen = $a['offer_token'] ? strlen($a['offer_token']) : 0;
        $tokenDisp = $a['offer_token'] ? substr($a['offer_token'], 0, 10) . "... ($tokenLen)" : "NULL";
        
        echo "### Applicant ID: {$a['applicant_id']}\n";
        echo "- Name: {$a['full_name']}\n";
        echo "- App Status: {$a['application_status']}\n";
        echo "- Offer Status: {$a['offer_status']}\n";
        echo "- Token: $tokenDisp\n";
        echo "- Sent At: {$a['offer_sent_at']}\n\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
