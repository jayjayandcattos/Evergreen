<?php
/**
 * Test Admin Login - Debug Script
 * This script helps diagnose admin login issues
 */

require_once 'config/database.php';

echo "<h2>Admin Login Debug Test</h2>";
echo "<pre>";

$conn = getDBConnection();
if (!$conn) {
    die("❌ Database connection failed!\n");
}
echo "✅ Database connection successful\n\n";

// Test 1: Check if admin user exists
echo "=== Test 1: Check Admin User ===\n";
$stmt = $conn->prepare("SELECT id, username, email, full_name, is_active FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$email = 'admin@system.com';
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    echo "✅ Admin user found:\n";
    echo "   ID: {$user['id']}\n";
    echo "   Username: {$user['username']}\n";
    echo "   Email: {$user['email']}\n";
    echo "   Full Name: {$user['full_name']}\n";
    echo "   Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n\n";
} else {
    echo "❌ Admin user NOT found with email: admin@system.com\n\n";
}

// Test 2: Check roles table
echo "=== Test 2: Check Roles ===\n";
$roles_result = $conn->query("SELECT id, name, description FROM roles");
if ($roles_result && $roles_result->num_rows > 0) {
    echo "✅ Roles found:\n";
    while ($role = $roles_result->fetch_assoc()) {
        echo "   ID: {$role['id']}, Name: {$role['name']}, Description: {$role['description']}\n";
    }
    echo "\n";
} else {
    echo "❌ No roles found in database\n\n";
}

// Test 3: Check user_roles assignment
if ($user) {
    echo "=== Test 3: Check User Roles Assignment ===\n";
    $stmt = $conn->prepare("
        SELECT r.id, r.name, r.description 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result && $result->num_rows > 0) {
        echo "✅ User has roles assigned:\n";
        while ($role = $result->fetch_assoc()) {
            echo "   Role ID: {$role['id']}, Name: {$role['name']}\n";
        }
        echo "\n";
    } else {
        echo "❌ User has NO roles assigned!\n";
        echo "   You need to assign a role to user_id: {$user['id']}\n\n";
    }
}

// Test 4: Test getAdminByEmail function
echo "=== Test 4: Test getAdminByEmail() Function ===\n";
$admin = getAdminByEmail('admin@system.com');
if ($admin) {
    echo "✅ getAdminByEmail() returned user:\n";
    echo "   ID: {$admin['id']}\n";
    echo "   Email: {$admin['email']}\n";
    echo "   Full Name: {$admin['full_name']}\n";
    echo "   Roles: " . ($admin['roles'] ?? 'None') . "\n\n";
} else {
    echo "❌ getAdminByEmail() returned NULL\n";
    echo "   This means the user either doesn't exist or doesn't have admin role\n\n";
}

// Test 5: Test password verification
if ($user) {
    echo "=== Test 5: Test Password Verification ===\n";
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pwd_row = $result->fetch_assoc();
    $stmt->close();
    
    $test_password = 'admin123';
    if ($pwd_row && password_verify($test_password, $pwd_row['password_hash'])) {
        echo "✅ Password 'admin123' is CORRECT\n\n";
    } else {
        echo "❌ Password 'admin123' is INCORRECT\n";
        echo "   Current hash: " . substr($pwd_row['password_hash'], 0, 20) . "...\n\n";
    }
}

// Test 6: Full login test
echo "=== Test 6: Full Login Test ===\n";
$test_email = 'admin@system.com';
$test_password = 'admin123';
$admin_user = verifyAdminPassword($test_email, $test_password);
if ($admin_user) {
    echo "✅ verifyAdminPassword() SUCCESS!\n";
    echo "   User can login with:\n";
    echo "   Email: $test_email\n";
    echo "   Password: $test_password\n";
    echo "   Role: {$admin_user['role']}\n";
    echo "   Loan Officer ID: " . ($admin_user['loan_officer_id'] ?? 'Not set') . "\n";
} else {
    echo "❌ verifyAdminPassword() FAILED!\n";
    echo "   Check error logs for details\n";
}

echo "</pre>";

// Provide SQL fix if needed
if ($user && !$admin_user) {
    echo "<h3>🔧 SQL Fix (if user has no admin role):</h3>";
    echo "<pre>";
    echo "-- First, ensure 'Administrator' role exists:\n";
    echo "INSERT INTO roles (id, name, description) VALUES (1, 'Administrator', 'Full system access')\n";
    echo "ON DUPLICATE KEY UPDATE name = VALUES(name);\n\n";
    echo "-- Then assign role to admin user:\n";
    echo "INSERT INTO user_roles (user_id, role_id) VALUES ({$user['id']}, 1)\n";
    echo "ON DUPLICATE KEY UPDATE user_id = VALUES(user_id);\n";
    echo "</pre>";
}

