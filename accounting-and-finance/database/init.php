<?php
/**
 * Database Initialization Script
 * Automated setup for Accounting & Finance System
 * This script creates the database, runs schema, and inserts admin user
 */

// Set content type to HTML for better display
header('Content-Type: text/html; charset=UTF-8');

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Default XAMPP password is empty
$db_name = 'BankingDB';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Initialization - Accounting & Finance System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Database Initialization</h1>
        <h2>Accounting & Finance System Setup</h2>
        
        <?php
        $errors = [];
        $successes = [];
        
        try {
            // Step 1: Connect to MySQL server (without database)
            echo "<div class='step'>";
            echo "<h3>Step 1: Connecting to MySQL Server</h3>";
            
            $conn = new mysqli($db_host, $db_user, $db_pass);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            echo "<p class='success'>✅ Connected to MySQL server successfully</p>";
            echo "</div>";
            
            // Step 2: Create database if it doesn't exist
            echo "<div class='step'>";
            echo "<h3>Step 2: Creating Database</h3>";
            
            $create_db_sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            
            if ($conn->query($create_db_sql) === TRUE) {
                echo "<p class='success'>✅ Database '$db_name' created/verified successfully</p>";
            } else {
                throw new Exception("Error creating database: " . $conn->error);
            }
            
            // Select the database
            $conn->select_db($db_name);
            echo "<p class='success'>✅ Database selected successfully</p>";
            echo "</div>";
            
            // Step 3: Check if tables already exist
            echo "<div class='step'>";
            echo "<h3>Step 3: Checking Database Schema</h3>";
            
            $tables_check = $conn->query("SHOW TABLES");
            $table_count = $tables_check ? $tables_check->num_rows : 0;
            
            if ($table_count > 0) {
                echo "<p class='warning'>⚠️ Database already contains $table_count tables</p>";
                echo "<p class='info'>ℹ️ Skipping schema creation (database already initialized)</p>";
            } else {
                // Step 4: Run schema.sql
                echo "<h3>Step 4: Creating Database Schema</h3>";
                
                $schema_file = __DIR__ . '/schema.sql';
                
                if (!file_exists($schema_file)) {
                    throw new Exception("Schema file not found: $schema_file");
                }
                
                $schema_sql = file_get_contents($schema_file);
                
                if ($schema_sql === false) {
                    throw new Exception("Could not read schema file");
                }
                
                // Split SQL into individual statements
                $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
                
                $successful_statements = 0;
                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^--/', $statement)) {
                        if ($conn->query($statement)) {
                            $successful_statements++;
                        } else {
                            echo "<p class='error'>❌ Error executing statement: " . $conn->error . "</p>";
                        }
                    }
                }
                
                echo "<p class='success'>✅ Schema created successfully ($successful_statements statements executed)</p>";
            }
            echo "</div>";
            
            // Step 5: Check if admin user exists
            echo "<div class='step'>";
            echo "<h3>Step 5: Checking Admin User</h3>";
            
            $admin_check = $conn->query("SELECT id, username, email FROM users WHERE username = 'admin'");
            
            if ($admin_check && $admin_check->num_rows > 0) {
                $admin = $admin_check->fetch_assoc();
                echo "<p class='success'>✅ Admin user already exists</p>";
                echo "<p class='info'>ℹ️ Username: {$admin['username']}, Email: {$admin['email']}</p>";
            } else {
                // Step 6: Insert admin user
                echo "<h3>Step 6: Creating Admin User</h3>";
                
                $admin_sql_file = __DIR__ . '/insert_admin.sql';
                
                if (file_exists($admin_sql_file)) {
                    $admin_sql = file_get_contents($admin_sql_file);
                    
                    if ($admin_sql !== false) {
                        // Extract just the INSERT statement for users table
                        if (preg_match('/INSERT INTO users[^;]+;/i', $admin_sql, $matches)) {
                            $insert_sql = $matches[0];
                            
                            if ($conn->query($insert_sql)) {
                                echo "<p class='success'>✅ Admin user created successfully</p>";
                            } else {
                                echo "<p class='error'>❌ Error creating admin user: " . $conn->error . "</p>";
                            }
                        } else {
                            // Fallback: Create admin user manually
                            $username = 'admin';
                            $password = 'admin123';
                            $email = 'admin@system.com';
                            $full_name = 'System Administrator';
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            
                            $insert_stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name, is_active, created_at) VALUES (?, ?, ?, ?, TRUE, NOW())");
                            $insert_stmt->bind_param("ssss", $username, $password_hash, $email, $full_name);
                            
                            if ($insert_stmt->execute()) {
                                echo "<p class='success'>✅ Admin user created successfully</p>";
                            } else {
                                echo "<p class='error'>❌ Error creating admin user: " . $conn->error . "</p>";
                            }
                            $insert_stmt->close();
                        }
                    } else {
                        throw new Exception("Could not read admin SQL file");
                    }
                } else {
                    throw new Exception("Admin SQL file not found: $admin_sql_file");
                }
            }
            echo "</div>";
            
            // Step 7: Final verification
            echo "<div class='step'>";
            echo "<h3>Step 7: Final Verification</h3>";
            
            // Check tables
            $final_tables = $conn->query("SHOW TABLES");
            $final_table_count = $final_tables ? $final_tables->num_rows : 0;
            echo "<p class='success'>✅ Database contains $final_table_count tables</p>";
            
            // Check admin user
            $final_admin = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
            if ($final_admin) {
                $admin_count = $final_admin->fetch_assoc()['count'];
                if ($admin_count > 0) {
                    echo "<p class='success'>✅ Admin user verified</p>";
                } else {
                    echo "<p class='error'>❌ Admin user not found</p>";
                }
            }
            
            echo "</div>";
            
            // Success message
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 class='success'>🎉 Database Initialized Successfully!</h2>";
            echo "<p>Your Accounting & Finance System is ready to use.</p>";
            echo "<h3>Login Credentials:</h3>";
            echo "<ul>";
            echo "<li><strong>Username:</strong> admin</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "<li><strong>Email:</strong> admin@system.com</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin: 30px 0;'>";
            echo "<a href='../core/login.php' class='btn btn-success'>🚀 Go to Login Page</a>";
            echo "<a href='../test_db_connection.php' class='btn'>🔍 Test Database Connection</a>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2 class='error'>❌ Initialization Failed</h2>";
            echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<h3>Troubleshooting Steps:</h3>";
            echo "<ol>";
            echo "<li>Ensure XAMPP MySQL service is running</li>";
            echo "<li>Check that database files exist in the database/ folder</li>";
            echo "<li>Verify file permissions</li>";
            echo "<li>Try running the setup manually using phpMyAdmin</li>";
            echo "</ol>";
            echo "</div>";
            
            echo "<div style='text-align: center; margin: 30px 0;'>";
            echo "<a href='../test_db_connection.php' class='btn'>🔍 Test Database Connection</a>";
            echo "<a href='../docs/SETUP.md' class='btn'>📖 View Setup Guide</a>";
            echo "</div>";
        }
        
        if (isset($conn)) {
            $conn->close();
        }
        ?>
        
        <hr>
        <div style='text-align: center; color: #666; font-size: 12px;'>
            <p>Accounting & Finance System - Database Initialization Script</p>
            <p>If you encounter issues, please refer to the documentation in the docs/ folder</p>
        </div>
    </div>
</body>
</html>
