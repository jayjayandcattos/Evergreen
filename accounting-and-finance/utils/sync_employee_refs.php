<?php
/**
 * Employee Refs Sync Utility
 * 
 * This script ensures all HRIS employees have corresponding employee_refs records
 * in the accounting system. It will:
 * 1. Check for missing employee_refs records
 * 2. Auto-create missing records with proper external_employee_no format (EMP001, EMP002, etc.)
 * 3. Update existing records if needed
 * 
 * Usage: Run this script via browser or command line to sync employee records
 */

require_once __DIR__ . '/../config/database.php';

// Set execution time limit for large datasets
set_time_limit(300);

echo "<h2>Employee Refs Sync Utility</h2>\n";
echo "<pre>\n";

$synced_count = 0;
$created_count = 0;
$updated_count = 0;
$errors = [];

// Get all employees from HRIS employee table
$employees_query = "SELECT 
                        e.employee_id,
                        e.first_name,
                        e.middle_name,
                        e.last_name,
                        e.hire_date,
                        d.department_name,
                        p.position_title,
                        c.salary as contract_salary,
                        c.contract_type,
                        e.employment_status
                    FROM employee e
                    LEFT JOIN department d ON e.department_id = d.department_id
                    LEFT JOIN `position` p ON e.position_id = p.position_id
                    LEFT JOIN contract c ON e.contract_id = c.contract_id
                    WHERE e.employee_id IS NOT NULL
                    ORDER BY e.employee_id";

$employees_result = $conn->query($employees_query);

if (!$employees_result) {
    die("Error fetching employees: " . $conn->error . "\n");
}

echo "Found " . $employees_result->num_rows . " employees in HRIS system.\n\n";

while ($employee = $employees_result->fetch_assoc()) {
    $employee_id = intval($employee['employee_id']);
    $external_employee_no = 'EMP' . str_pad($employee_id, 3, '0', STR_PAD_LEFT);
    
    // Build full name
    $full_name = trim(($employee['first_name'] ?? '') . ' ' . 
                     ($employee['middle_name'] ?? '') . ' ' . 
                     ($employee['last_name'] ?? ''));
    $full_name = preg_replace('/\s+/', ' ', $full_name); // Clean up multiple spaces
    
    $department = $employee['department_name'] ?? '';
    $position = $employee['position_title'] ?? '';
    $base_salary = floatval($employee['contract_salary'] ?? 0);
    $employment_type = strtolower($employee['contract_type'] ?? 'regular');
    
    // Normalize employment type
    if (!in_array($employment_type, ['regular', 'contract', 'part-time'])) {
        $employment_type = 'regular';
    }
    
    // Check if employee_refs record exists
    $check_query = "SELECT id, external_employee_no, name, department, position, base_monthly_salary, employment_type 
                    FROM employee_refs 
                    WHERE external_employee_no = ? 
                    LIMIT 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $external_employee_no);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($existing) {
        // Update existing record if information has changed
        $needs_update = false;
        $update_fields = [];
        $update_values = [];
        $update_types = "";
        
        if ($existing['name'] !== $full_name) {
            $update_fields[] = "name = ?";
            $update_values[] = $full_name;
            $update_types .= "s";
            $needs_update = true;
        }
        
        if ($existing['department'] !== $department) {
            $update_fields[] = "department = ?";
            $update_values[] = $department;
            $update_types .= "s";
            $needs_update = true;
        }
        
        if ($existing['position'] !== $position) {
            $update_fields[] = "position = ?";
            $update_values[] = $position;
            $update_types .= "s";
            $needs_update = true;
        }
        
        if (abs(floatval($existing['base_monthly_salary']) - $base_salary) > 0.01) {
            $update_fields[] = "base_monthly_salary = ?";
            $update_values[] = $base_salary;
            $update_types .= "d";
            $needs_update = true;
        }
        
        if ($existing['employment_type'] !== $employment_type) {
            $update_fields[] = "employment_type = ?";
            $update_values[] = $employment_type;
            $update_types .= "s";
            $needs_update = true;
        }
        
        if ($needs_update) {
            $update_query = "UPDATE employee_refs SET " . implode(", ", $update_fields) . " WHERE external_employee_no = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_values[] = $external_employee_no;
            $update_types .= "s";
            $update_stmt->bind_param($update_types, ...$update_values);
            
            if ($update_stmt->execute()) {
                $updated_count++;
                echo "✓ Updated: $external_employee_no - $full_name\n";
            } else {
                $errors[] = "Failed to update $external_employee_no: " . $update_stmt->error;
                echo "✗ Error updating $external_employee_no: " . $update_stmt->error . "\n";
            }
            $update_stmt->close();
        } else {
            echo "✓ Already synced: $external_employee_no - $full_name\n";
        }
    } else {
        // Create new employee_refs record
        $insert_query = "INSERT INTO employee_refs 
                        (external_employee_no, name, department, position, base_monthly_salary, employment_type, external_source, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 'HRIS', ?)";
        $insert_stmt = $conn->prepare($insert_query);
        
        $created_at = $employee['hire_date'] ?? date('Y-m-d H:i:s');
        $insert_stmt->bind_param("ssssdss", 
            $external_employee_no, 
            $full_name, 
            $department, 
            $position, 
            $base_salary, 
            $employment_type,
            $created_at
        );
        
        if ($insert_stmt->execute()) {
            $created_count++;
            echo "✓ Created: $external_employee_no - $full_name\n";
        } else {
            $errors[] = "Failed to create $external_employee_no: " . $insert_stmt->error;
            echo "✗ Error creating $external_employee_no: " . $insert_stmt->error . "\n";
        }
        $insert_stmt->close();
    }
    
    $synced_count++;
}

echo "\n";
echo "========================================\n";
echo "Sync Summary:\n";
echo "========================================\n";
echo "Total employees processed: $synced_count\n";
echo "New records created: $created_count\n";
echo "Records updated: $updated_count\n";
echo "Already synced: " . ($synced_count - $created_count - $updated_count) . "\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
} else {
    echo "\n✓ All employees synced successfully!\n";
}

echo "</pre>\n";

// Verify sync by checking for any missing records
echo "<h3>Verification</h3>\n";
echo "<pre>\n";

$verify_query = "SELECT COUNT(*) as missing_count
                 FROM employee e
                 LEFT JOIN employee_refs er ON er.external_employee_no = CONCAT('EMP', LPAD(e.employee_id, 3, '0'))
                 WHERE er.id IS NULL";
$verify_result = $conn->query($verify_query);
$verify_row = $verify_result->fetch_assoc();

if ($verify_row['missing_count'] > 0) {
    echo "⚠ Warning: " . $verify_row['missing_count'] . " employees still missing employee_refs records.\n";
    echo "Please run this script again.\n";
} else {
    echo "✓ Verification passed: All employees have employee_refs records.\n";
}

echo "</pre>\n";
?>

