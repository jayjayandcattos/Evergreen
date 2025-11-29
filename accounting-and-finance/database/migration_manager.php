<?php
/**
 * Database Migration Management Tool
 * Admin interface for managing database migrations
 */

require_once '../config/database.php';
require_once '../database/AutoMigration.php';

// Check if user is admin (you can modify this check)
$isAdmin = true; // For demo purposes - implement proper admin check

if (!$isAdmin) {
    die('Access denied. Admin privileges required.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fas fa-database me-2"></i>Database Migration Management</h2>
                <p class="text-muted">Manage database schema and migrations</p>
                
                <!-- Database Setup Instructions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Database Setup Instructions</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb me-2"></i>First Time Setup</h6>
                            <p>If you're setting up the database for the first time, follow these steps:</p>
                            <ol>
                                <li><strong>Import Base Schema:</strong> Run the <code>database/unified_schema.sql</code> file in your MySQL database</li>
                                <li><strong>Import Sample Data (Optional):</strong> Run the <code>database/sql/Sampled_data.sql</code> file to populate with test data</li>
                                <li><strong>Fix User Passwords:</strong> After importing sample data, run <a href="fix_user_passwords.php" target="_blank">Fix User Passwords</a> to ensure login works</li>
                                <li><strong>Run Migrations:</strong> Use the "Run Migrations" button below to add additional features</li>
                                <li><strong>Verify Setup:</strong> Check the migration status to ensure everything is working</li>
                            </ol>
                            <p class="mb-0"><strong>Note:</strong> The migration system adds features on top of the base schema. Always import schema.sql first!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Migration Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>Migration Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $status = AutoMigration::getStatus($conn);
                        
                        if (isset($status['error'])) {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="fas fa-exclamation-triangle me-2"></i>';
                            echo 'Error: ' . htmlspecialchars($status['error']);
                            if (isset($status['instructions'])) {
                                echo '<br><br><strong>Instructions:</strong><br>';
                                echo '<pre>' . htmlspecialchars($status['instructions']) . '</pre>';
                            }
                            echo '</div>';
                        } else {
                            $healthCheck = $status['health_check'];
                            $needsMigration = $status['needs_migration'];
                            
                            echo '<div class="row">';
                            echo '<div class="col-md-6">';
                            echo '<h6>Version Information</h6>';
                            echo '<p><strong>Current Version:</strong> ' . htmlspecialchars($status['current_version']) . '</p>';
                            echo '<p><strong>Target Version:</strong> ' . htmlspecialchars($status['target_version']) . '</p>';
                            echo '<p><strong>Migration Needed:</strong> ';
                            echo $needsMigration ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-success">No</span>';
                            echo '</p>';
                            echo '</div>';
                            
                            echo '<div class="col-md-6">';
                            echo '<h6>Database Health</h6>';
                            echo '<p><strong>Status:</strong> ';
                            echo $healthCheck['healthy'] ? '<span class="badge bg-success">Healthy</span>' : '<span class="badge bg-danger">Issues Found</span>';
                            echo '</p>';
                            
                            if (!$healthCheck['healthy']) {
                                echo '<h6>Issues Found:</h6>';
                                echo '<ul class="list-unstyled">';
                                foreach ($healthCheck['issues'] as $issue) {
                                    echo '<li><i class="fas fa-times text-danger me-2"></i>' . htmlspecialchars($issue) . '</li>';
                                }
                                echo '</ul>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Migration Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs me-2"></i>Migration Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Run Migrations</h6>
                                <p class="text-muted">Apply all pending database migrations</p>
                                <button class="btn btn-primary" onclick="runMigrations()">
                                    <i class="fas fa-play me-2"></i>Run Migrations
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h6>Refresh Status</h6>
                                <p class="text-muted">Check current migration status</p>
                                <button class="btn btn-secondary" onclick="location.reload()">
                                    <i class="fas fa-refresh me-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Migration History -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history me-2"></i>Migration History</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Check if migrations table exists first
                            $tableCheck = $conn->query("SHOW TABLES LIKE 'database_migrations'");
                            if ($tableCheck && $tableCheck->num_rows > 0) {
                                $result = $conn->query("SELECT * FROM database_migrations ORDER BY applied_at DESC");
                                if ($result && $result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-striped">';
                                    echo '<thead><tr><th>Version</th><th>Description</th><th>Applied At</th></tr></thead>';
                                    echo '<tbody>';
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td><span class="badge bg-primary">' . htmlspecialchars($row['version']) . '</span></td>';
                                        echo '<td>' . htmlspecialchars($row['description']) . '</td>';
                                        echo '<td>' . date('Y-m-d H:i:s', strtotime($row['applied_at'])) . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                    echo '</div>';
                                } else {
                                    echo '<p class="text-muted">No migrations have been applied yet.</p>';
                                }
                            } else {
                                echo '<p class="text-muted">Migration system not initialized. Please set up the database first.</p>';
                            }
                        } catch (Exception $e) {
                            echo '<p class="text-muted">Unable to load migration history: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function runMigrations() {
            if (!confirm('Are you sure you want to run database migrations? This will modify your database schema.')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Running...';
            button.disabled = true;
            
            // Make AJAX call to run migrations
            fetch('run_migration.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=run_migrations'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Migrations completed successfully!');
                    location.reload();
                } else {
                    alert('Migration failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
