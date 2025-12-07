<?php
// Check bank_customers table structure

require_once '../Basic-operation/config/database.php';

try {
    $db = getDBConnection();
    
    echo "Checking bank_customers table structure...\n\n";
    
    // Get table structure
    $stmt = $db->query("DESCRIBE bank_customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current columns:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-30s %-20s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $col) {
        printf("%-30s %-20s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    
    echo "\n\nChecking for required schema fields from your specification:\n";
    echo str_repeat("-", 80) . "\n";
    
    $requiredFields = [
        'customer_id',
        'last_name',
        'first_name',
        'middle_name',
        'address',
        'city_province',
        'email',
        'contact_number',
        'birthday',
        'password_hash',
        'verification_code',
        'bank_id',
        'referral_code',
        'referred_by_customer_id',
        'total_points',
        'is_verified',
        'created_at',
        'created_by_employee_id',
        'application_id'
    ];
    
    $existingFields = array_column($columns, 'Field');
    
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $existingFields);
        $status = $exists ? "✓ EXISTS" : "✗ MISSING";
        printf("%-30s %s\n", $field, $status);
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
