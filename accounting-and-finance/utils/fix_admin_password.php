<?php
/**
 * Admin Password Reset Tool
 * This file will update the admin password in the database
 */

require_once '../config/database.php';

// Set the new password
$new_password = 'admin123';
$username = 'admin';

// Generate password hash
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "<h2>Password Reset Tool</h2>";
echo "<hr>";

try {
    $conn = getDBConnection();
    
    // Check if user exists
    $check_stmt = $conn->prepare("SELECT id, username, email, full_name FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ User '$username' not found in database!</p>";
        echo "<p>Please run the insert_admin.sql script first.</p>";
        exit();
    }
    
    $user = $result->fetch_assoc();
    echo "<p>✅ User found:</p>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$user['id']}</li>";
    echo "<li><strong>Username:</strong> {$user['username']}</li>";
    echo "<li><strong>Email:</strong> {$user['email']}</li>";
    echo "<li><strong>Full Name:</strong> {$user['full_name']}</li>";
    echo "</ul>";
    
    $check_stmt->close();
    
    // Update password
    $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $update_stmt->bind_param("ss", $password_hash, $username);
    
    if ($update_stmt->execute()) {
        echo "<hr>";
        echo "<h3 style='color: green;'>✅ Password Updated Successfully!</h3>";
        echo "<p><strong>New Password Hash:</strong> $password_hash</p>";
        echo "<hr>";
        echo "<h3>Login Credentials:</h3>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
        echo "<p><a href='../core/login.php' style='display: inline-block; padding: 10px 20px; background: #0A3D3D; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Go to Login Page</a></p>";
        echo "<hr>";
        echo "<p style='color: #666; font-size: 12px;'>⚠️ <strong>Security Note:</strong> Delete this file after fixing the password!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error updating password: " . $conn->error . "</p>";
    }
    
    $update_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

