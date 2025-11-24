<?php
/**
 * Generate Password Hash for Finance Admin
 * This script generates the correct bcrypt hash for the password "Finance2025"
 */

$password = "Finance2025";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Password Hash Generator</h2>";
echo "<hr>";
echo "<h3>Password: <strong>$password</strong></h3>";
echo "<h3>Generated Hash:</h3>";
echo "<p style='background: #f0f0f0; padding: 10px; font-family: monospace; word-break: break-all;'>$hash</p>";
echo "<hr>";
echo "<h3>SQL Update Statement:</h3>";
echo "<pre style='background: #f0f0f0; padding: 15px; overflow-x: auto;'>";
echo "-- Update finance.admin password to Finance2025\n";
echo "UPDATE users \n";
echo "SET password_hash = '$hash' \n";
echo "WHERE username = 'finance.admin';\n\n";
echo "UPDATE user_account \n";
echo "SET password_hash = '$hash' \n";
echo "WHERE username = 'finance.admin';\n";
echo "</pre>";
echo "<hr>";
echo "<h3>Verification Test:</h3>";
if (password_verify($password, $hash)) {
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>Hash verification SUCCESS!</strong></p>";
    echo "<p>This hash will work for password: <strong>$password</strong></p>";
} else {
    echo "<p style='color: red;'>❌ Hash verification FAILED!</p>";
}
?>
