<?php
/**
 * Fix Finance Admin Password
 * This script updates the finance.admin password to "Finance2025"
 */

require_once '../config/database.php';

echo "<h2>Finance Admin Password Fix</h2>";
echo "<hr>";

$password = "Finance2025";
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn = getDBConnection();
    
    echo "<h3>1. Database Connection</h3>";
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if finance.admin exists
    echo "<h3>2. Checking for finance.admin user</h3>";
    $check_stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = 'finance.admin'");
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ User 'finance.admin' found (ID: {$user['id']})</p>";
        
        // Update password in users table
        echo "<h3>3. Updating password in users table</h3>";
        $update_users = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = 'finance.admin'");
        $update_users->bind_param("s", $hash);
        
        if ($update_users->execute()) {
            echo "<p style='color: green;'>✅ Password updated in users table</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update users table: " . $update_users->error . "</p>";
        }
        $update_users->close();
        
        // Update password in user_account table
        echo "<h3>4. Updating password in user_account table</h3>";
        $update_account = $conn->prepare("UPDATE user_account SET password_hash = ? WHERE username = 'finance.admin'");
        $update_account->bind_param("s", $hash);
        
        if ($update_account->execute()) {
            if ($update_account->affected_rows > 0) {
                echo "<p style='color: green;'>✅ Password updated in user_account table</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ No rows updated in user_account table (user may not exist there yet)</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Failed to update user_account table: " . $update_account->error . "</p>";
        }
        $update_account->close();
        
        // Verify the update
        echo "<h3>5. Verification</h3>";
        $verify_stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = 'finance.admin'");
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_user = $verify_result->fetch_assoc();
        
        if (password_verify($password, $verify_user['password_hash'])) {
            echo "<p style='color: green; font-size: 18px;'>✅ <strong>Password verification SUCCESS!</strong></p>";
            echo "<div style='background: #e8f5e9; padding: 20px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
            echo "<h3 style='margin-top: 0;'>✅ Password Updated Successfully!</h3>";
            echo "<p><strong>You can now login with:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> finance.admin@evergreen.com</li>";
            echo "<li><strong>Username:</strong> finance.admin</li>";
            echo "<li><strong>Password:</strong> Finance2025</li>";
            echo "</ul>";
            echo "<p><a href='../core/login.php' style='display: inline-block; padding: 10px 20px; background: #0A3D3D; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Login Page</a></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Password verification FAILED after update!</p>";
        }
        $verify_stmt->close();
        
    } else {
        echo "<p style='color: red;'>❌ User 'finance.admin' NOT found in database</p>";
        echo "<p><strong>Solution:</strong> Run the SQL script to create the user first:</p>";
        echo "<pre style='background: #f0f0f0; padding: 15px;'>";
        echo "mysql -u root -p BankingDB < accounting-and-finance/database/sql/create_accounting_admin.sql\n";
        echo "</pre>";
    }
    
    $check_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>⚠️ <strong>Security Note:</strong> Delete this file after fixing the password!</p>";
?>
