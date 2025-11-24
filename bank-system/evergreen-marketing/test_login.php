<?php
/**
 * Test Login - Check if account exists in database
 * Access this file to verify your account was created correctly
 */

include("db_connect.php");

// Get the email from URL parameter (e.g., test_login.php?email=your@email.com)
$test_email = isset($_GET['email']) ? trim($_GET['email']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login - Evergreen Bank</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0d3d38;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background: #0d3d38;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        button:hover {
            background: #1a6b62;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #0d3d38;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Login - Account Verification</h1>
        <p>Enter your email to check if your account exists in the database.</p>
        
        <form method="GET">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($test_email) ?>" placeholder="your@email.com" required>
            </div>
            <button type="submit">Check Account</button>
        </form>

        <?php if (!empty($test_email)): ?>
            <div class="result">
                <?php
                // Check in bank_customers table
                $sql = "SELECT customer_id, first_name, last_name, middle_name, email, created_at 
                        FROM bank_customers 
                        WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $test_email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo '<div class="success">';
                    echo '<h3>✅ Account Found in bank_customers!</h3>';
                    echo '<table>';
                    echo '<tr><th>Field</th><th>Value</th></tr>';
                    echo '<tr><td>Customer ID</td><td>' . $row['customer_id'] . '</td></tr>';
                    echo '<tr><td>Name</td><td>' . $row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name'] . '</td></tr>';
                    echo '<tr><td>Email</td><td>' . $row['email'] . '</td></tr>';
                    echo '<tr><td>Created At</td><td>' . $row['created_at'] . '</td></tr>';
                    echo '</table>';
                    echo '</div>';

                    // Check for customer accounts
                    $sql2 = "SELECT account_id, account_number, account_type_id, created_at 
                            FROM customer_accounts 
                            WHERE customer_id = ?";
                    $stmt2 = $conn->prepare($sql2);
                    $stmt2->bind_param("i", $row['customer_id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();

                    if ($result2 && $result2->num_rows > 0) {
                        echo '<div class="info" style="margin-top: 20px;">';
                        echo '<h3>💳 Bank Accounts Found:</h3>';
                        echo '<table>';
                        echo '<tr><th>Account Number</th><th>Type ID</th><th>Created</th></tr>';
                        while ($acc = $result2->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $acc['account_number'] . '</td>';
                            echo '<td>' . $acc['account_type_id'] . '</td>';
                            echo '<td>' . $acc['created_at'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        echo '</div>';
                    }
                    $stmt2->close();

                    echo '<div class="success" style="margin-top: 20px;">';
                    echo '<h3>✅ You can now login!</h3>';
                    echo '<p>Use your email and password to login at:</p>';
                    echo '<p><strong><a href="login.php" style="color: #0d3d38;">Login Page</a></strong></p>';
                    echo '<p><small>Note: Bank ID field is optional for accounts created through Basic-operation system.</small></p>';
                    echo '</div>';

                } else {
                    echo '<div class="error">';
                    echo '<h3>❌ Account Not Found</h3>';
                    echo '<p>No account found with email: <strong>' . htmlspecialchars($test_email) . '</strong></p>';
                    echo '<p>Please check:</p>';
                    echo '<ul>';
                    echo '<li>Email spelling is correct</li>';
                    echo '<li>Account was successfully created</li>';
                    echo '<li>You completed all 3 steps of registration</li>';
                    echo '</ul>';
                    echo '</div>';
                }
                $stmt->close();
                ?>
            </div>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
