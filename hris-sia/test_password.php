<?php
// Test password verification
$password = 'password';
$hash_from_db = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h2>Password Verification Test</h2>";
echo "<p><strong>Testing password:</strong> 'password'</p>";
echo "<p><strong>Hash from database:</strong> " . htmlspecialchars($hash_from_db) . "</p>";

if (password_verify($password, $hash_from_db)) {
    echo "<p style='color: green; font-weight: bold;'>✓ Password verification SUCCESSFUL!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Password verification FAILED!</p>";
}

echo "<hr>";
echo "<h3>Generate New Password Hash</h3>";
echo "<p>If you want to set a new password, use this hash:</p>";

$new_password = 'admin123';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "<p><strong>Password:</strong> 'admin123'</p>";
echo "<p><strong>New Hash:</strong> <code>" . htmlspecialchars($new_hash) . "</code></p>";

echo "<hr>";
echo "<h3>Check Database Connection</h3>";

require_once 'config/database.php';

try {
    $sql = "SELECT user_id, username, password_hash, role FROM user_account WHERE username = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p style='color: green;'>✓ Admin user found in database!</p>";
        echo "<pre>";
        echo "User ID: " . $user['user_id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Password Hash: " . $user['password_hash'] . "\n";
        echo "</pre>";
        
        echo "<h4>Test Password Verification with DB Hash:</h4>";
        if (password_verify('password', $user['password_hash'])) {
            echo "<p style='color: green; font-weight: bold;'>✓ Password 'password' works with DB hash!</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>✗ Password 'password' does NOT work with DB hash!</p>";
        }
        
        if (password_verify('admin123', $user['password_hash'])) {
            echo "<p style='color: green; font-weight: bold;'>✓ Password 'admin123' works with DB hash!</p>";
        } else {
            echo "<p style='color: orange; font-weight: bold;'>✗ Password 'admin123' does NOT work with DB hash!</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin user NOT found in database!</p>";
        echo "<p>Run the SQL script to create the admin user.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
