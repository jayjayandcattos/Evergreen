<?php
/**
 * Database Migration System
 * Automatically detects and applies missing schema changes
 * Makes the system adaptable when implemented on other devices
 */

class DatabaseMigration {
    private $conn;
    private $currentVersion;
    private $targetVersion = '1.2.0'; // Current system version
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->currentVersion = $this->getCurrentVersion();
    }
    
    /**
     * Get current database version
     */
    private function getCurrentVersion() {
        try {
            // Check if migrations table exists
            $result = $this->conn->query("SHOW TABLES LIKE 'database_migrations'");
            if ($result === false) {
                // Database might not exist or connection issue
                return '0.0.0';
            }
            
            if ($result->num_rows == 0) {
                return '0.0.0'; // Fresh installation
            }
            
            // Get latest migration version
            $result = $this->conn->query("SELECT version FROM database_migrations ORDER BY applied_at DESC LIMIT 1");
            if ($result === false) {
                return '0.0.0';
            }
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['version'];
            }
            
            return '0.0.0';
        } catch (Exception $e) {
            // If there's any error, assume fresh installation
            return '0.0.0';
        }
    }
    
    /**
     * Check if migration is needed
     */
    public function needsMigration() {
        return version_compare($this->currentVersion, $this->targetVersion, '<');
    }
    
    /**
     * Check if database is completely empty
     */
    private function isDatabaseEmpty() {
        try {
            $result = $this->conn->query("SHOW TABLES");
            if ($result === false) {
                // If query fails, assume database is empty or doesn't exist
                return true;
            }
            return $result->num_rows === 0;
        } catch (Exception $e) {
            // If there's any error, assume database is empty
            return true;
        }
    }
    
    /**
     * Run all pending migrations
     */
    public function migrate() {
        // Check if database is completely empty
        if ($this->isDatabaseEmpty()) {
            return [
                'success' => false,
                'error' => 'Database is empty. Please run schema.sql first to create the base database structure.',
                'instructions' => '1. Import schema.sql into your database first\n2. Then run migrations to add additional features'
            ];
        }
        
        if (!$this->needsMigration()) {
            return [
                'success' => true,
                'message' => 'Database is up to date',
                'current_version' => $this->currentVersion
            ];
        }
        
        $this->createMigrationsTable();
        
        $migrations = $this->getPendingMigrations();
        $appliedMigrations = [];
        $errors = [];
        
        foreach ($migrations as $migration) {
            try {
                $this->applyMigration($migration);
                $appliedMigrations[] = $migration['version'];
            } catch (Exception $e) {
                $errors[] = "Migration {$migration['version']} failed: " . $e->getMessage();
            }
        }
        
        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Migration completed successfully' : 'Migration completed with errors',
            'applied_migrations' => $appliedMigrations,
            'errors' => $errors,
            'current_version' => $this->targetVersion
        ];
    }
    
    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS database_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(20) NOT NULL UNIQUE,
            description TEXT NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_version (version)
        )";
        
        if (!$this->conn->query($sql)) {
            throw new Exception("Failed to create migrations table: " . $this->conn->error);
        }
    }
    
    /**
     * Get pending migrations
     */
    private function getPendingMigrations() {
        $allMigrations = $this->getAllMigrations();
        $pendingMigrations = [];
        
        foreach ($allMigrations as $migration) {
            if (version_compare($this->currentVersion, $migration['version'], '<')) {
                $pendingMigrations[] = $migration;
            }
        }
        
        return $pendingMigrations;
    }
    
    /**
     * Get all available migrations
     */
    private function getAllMigrations() {
        return [
            [
                'version' => '1.0.0',
                'description' => 'Initial database setup',
                'sql' => $this->getInitialSetupSQL()
            ],
            [
                'version' => '1.1.0',
                'description' => 'Add soft delete functionality',
                'sql' => $this->getSoftDeleteSQL()
            ],
            [
                'version' => '1.2.0',
                'description' => 'Add tax reports and report settings',
                'sql' => $this->getTaxReportsSQL()
            ]
        ];
    }
    
    /**
     * Apply a single migration
     */
    private function applyMigration($migration) {
        $this->conn->begin_transaction();
        
        try {
            // Execute migration SQL
            $this->executeMigrationSQL($migration['sql']);
            
            // Record migration - ensure table exists first
            $this->createMigrationsTable();
            
            $stmt = $this->conn->prepare("INSERT INTO database_migrations (version, description) VALUES (?, ?)");
            if ($stmt === false) {
                throw new Exception("Failed to prepare migration record statement: " . $this->conn->error);
            }
            
            $stmt->bind_param('ss', $migration['version'], $migration['description']);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute migration record: " . $stmt->error);
            }
            
            $stmt->close();
            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    /**
     * Execute migration SQL
     */
    private function executeMigrationSQL($sql) {
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                // Skip comments and empty statements
                if (strpos($statement, '--') === 0) {
                    continue;
                }
                
                $result = $this->conn->query($statement);
                if ($result === false) {
                    throw new Exception("SQL Error executing statement: " . $this->conn->error . "\nStatement: " . $statement);
                }
            }
        }
    }
    
    /**
     * Get initial setup SQL
     */
    private function getInitialSetupSQL() {
        return "
        -- This migration handles the initial database setup
        -- Most tables should already exist from schema.sql
        -- This is a placeholder for any initial setup needed
        ";
    }
    
    /**
     * Get soft delete functionality SQL
     */
    private function getSoftDeleteSQL() {
        return "
        -- Add soft delete columns to compliance_reports
        ALTER TABLE compliance_reports ADD COLUMN deleted_at DATETIME NULL;
        
        -- Add soft delete columns to journal_entries
        ALTER TABLE journal_entries ADD COLUMN deleted_at DATETIME NULL;
        ALTER TABLE journal_entries ADD COLUMN deleted_by INT NULL;
        ALTER TABLE journal_entries ADD COLUMN restored_at DATETIME NULL;
        ALTER TABLE journal_entries ADD COLUMN restored_by INT NULL;
        
        -- Update journal_entries status enum to include 'deleted'
        ALTER TABLE journal_entries MODIFY COLUMN status ENUM('draft','posted','reversed','voided','deleted') DEFAULT 'draft';
        ";
    }
    
    /**
     * Get tax reports and settings SQL
     */
    private function getTaxReportsSQL() {
        return "
        -- Create tax_reports table
        CREATE TABLE IF NOT EXISTS tax_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_type VARCHAR(50) NOT NULL,
            period_start DATE NOT NULL,
            period_end DATE NOT NULL,
            generated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            generated_by INT NOT NULL,
            status ENUM('generating','completed','failed') DEFAULT 'generating',
            file_path VARCHAR(255) NULL,
            report_data JSON NULL,
            tax_amount DECIMAL(18,2) DEFAULT 0.00,
            compliance_score DECIMAL(5,2) DEFAULT 0.00,
            issues_found TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            FOREIGN KEY (generated_by) REFERENCES users(id)
        );
        
        -- Create report_settings table
        CREATE TABLE IF NOT EXISTS report_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            setting_type ENUM('string','number','boolean','json') DEFAULT 'string',
            description TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        -- Insert default report settings
        INSERT IGNORE INTO report_settings (setting_key, setting_value, setting_type, description) VALUES 
        ('default_period', 'Monthly', 'string', 'Default report period'),
        ('default_format', 'PDF', 'string', 'Default report format'),
        ('company_name', 'Evergreen Accounting & Finance', 'string', 'Company name for reports'),
        ('fiscal_year_end', '2025-12-31', 'string', 'Fiscal year end date'),
        ('footer_text', 'This report was generated by Evergreen Accounting System', 'string', 'Custom footer text'),
        ('auto_monthly', 'true', 'boolean', 'Enable monthly automated reports'),
        ('auto_quarterly', 'false', 'boolean', 'Enable quarterly automated reports'),
        ('auto_yearend', 'true', 'boolean', 'Enable year-end automated reports');
        ";
    }
    
    /**
     * Check database health
     */
    public function checkDatabaseHealth() {
        $issues = [];
        
        try {
            // Check required tables based on schema.sql
            $requiredTables = [
                'users', 'roles', 'user_roles', 'employee_refs', 'accounts', 
                'journal_types', 'journal_entries', 'journal_lines', 'fiscal_periods',
                'expense_categories', 'expense_claims', 'compliance_reports', 
                'payments', 'bank_accounts', 'salary_components', 'payslips', 
                'payroll_runs', 'loans', 'loan_types', 'account_types', 
                'account_balances', 'payroll_periods', 'loan_payments', 
                'audit_logs', 'integration_logs'
            ];
            
            foreach ($requiredTables as $table) {
                $result = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($result === false) {
                    $issues[] = "Error checking table: $table - " . $this->conn->error;
                } elseif ($result->num_rows == 0) {
                    $issues[] = "Missing table: $table";
                }
            }
            
            // Check required columns only for tables that exist
            $requiredColumns = [
                // No additional columns required beyond what's in schema.sql
            ];
            
            foreach ($requiredColumns as $table => $columns) {
                // First check if table exists
                $tableCheck = $this->conn->query("SHOW TABLES LIKE '$table'");
                if ($tableCheck && $tableCheck->num_rows > 0) {
                    foreach ($columns as $column) {
                        $result = $this->conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
                        if ($result === false) {
                            $issues[] = "Error checking column: $table.$column";
                        } elseif ($result->num_rows == 0) {
                            $issues[] = "Missing column: $table.$column";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $issues[] = "Database health check error: " . $e->getMessage();
        }
        
        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'current_version' => $this->currentVersion,
            'target_version' => $this->targetVersion
        ];
    }
    
    /**
     * Get migration status
     */
    public function getStatus() {
        return [
            'current_version' => $this->currentVersion,
            'target_version' => $this->targetVersion,
            'needs_migration' => $this->needsMigration(),
            'health_check' => $this->checkDatabaseHealth()
        ];
    }
}
?>
