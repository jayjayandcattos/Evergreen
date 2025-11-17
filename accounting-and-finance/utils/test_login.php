<?php
/**
 * Login Debugging Tool
 * This helps diagnose login issues
 */

require_once '../config/database.php';

echo "<h2>Login Debug Tool</h2>";
echo "<hr>";

$test_username = 'admin';
$test_password = 'admin123';

try {
    $conn = getDBConnection();
    
    echo "<h3>1. Database Connection</h3>";
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    echo "<h3>2. User Lookup</h3>";
    $stmt = $conn->prepare("SELECT id, username, password_hash, email, full_name, is_active FROM users WHERE username = ?");
    $stmt->bind_param("s", $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        echo "<p style='color: green;'>✅ User 'admin' found in database</p>";
        
        $user = $result->fetch_assoc();
        
        echo "<h3>3. User Details</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$user['id']}</li>";
        echo "<li><strong>Username:</strong> {$user['username']}</li>";
        echo "<li><strong>Email:</strong> {$user['email']}</li>";
        echo "<li><strong>Full Name:</strong> {$user['full_name']}</li>";
        echo "<li><strong>Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</li>";
        echo "</ul>";
        
        echo "<h3>4. Password Hash Check</h3>";
        echo "<p><strong>Stored Hash:</strong> <code style='background: #f0f0f0; padding: 5px; font-size: 11px;'>{$user['password_hash']}</code></p>";
        
        echo "<h3>5. Password Verification Test</h3>";
        echo "<p>Testing password: <strong>admin123</strong></p>";
        
        if (password_verify($test_password, $user['password_hash'])) {
            echo "<p style='color: green; font-size: 18px;'>✅ <strong>Password verification SUCCESS!</strong></p>";
            echo "<p>You should be able to login with:</p>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "<p><a href='../core/login.php' style='display: inline-block; padding: 10px 20px; background: #0A3D3D; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Go to Login Page</a></p>";
        } else {
            echo "<p style='color: red; font-size: 18px;'>❌ <strong>Password verification FAILED!</strong></p>";
            echo "<p>The password hash in the database doesn't match 'admin123'</p>";
            echo "<p><strong>Solution:</strong> Run <a href='fix_admin_password.php' style='color: blue; text-decoration: underline;'>fix_admin_password.php</a> to update the password.</p>";
        }
        
        echo "<h3>6. Account Status</h3>";
        if ($user['is_active']) {
            echo "<p style='color: green;'>✅ Account is active</p>";
        } else {
            echo "<p style='color: red;'>❌ Account is inactive - login will fail!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ User 'admin' NOT found in database</p>";
        echo "<p>Please run the insert_admin.sql script to create the admin user.</p>";
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Database connection settings in config/database.php</li>";
    echo "<li>MySQL server not running</li>";
    echo "<li>Database 'BankingDB' doesn't exist</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>⚠️ <strong>Security Note:</strong> Delete this file and fix_admin_password.php after fixing login issues!</p>";
?>

