<?php
/**
 * Automatic Database Migration Runner
 * This file should be included in your main application files
 * to automatically run migrations when needed
 */

require_once __DIR__ . '/DatabaseMigration.php';

class AutoMigration {
    private static $migrationRun = false;
    
    /**
     * Run migrations automatically if needed
     * Call this function at the start of your application
     */
    public static function runIfNeeded($connection) {
        if (self::$migrationRun) {
            return; // Already run in this request
        }
        
        try {
            $migration = new DatabaseMigration($connection);
            
            if ($migration->needsMigration()) {
                $result = $migration->migrate();
                
                // Log migration result
                error_log("Database Migration Result: " . json_encode($result));
                
                // If there were errors, you might want to handle them
                if (!$result['success']) {
                    // Handle different error formats
                    if (isset($result['errors']) && is_array($result['errors'])) {
                        error_log("Migration errors: " . implode(', ', $result['errors']));
                    } elseif (isset($result['error'])) {
                        error_log("Migration error: " . $result['error']);
                    } else {
                        error_log("Migration failed: Unknown error");
                    }
                }
            }
            
            self::$migrationRun = true;
            
        } catch (Exception $e) {
            error_log("Migration failed: " . $e->getMessage());
            // Don't throw exception to prevent breaking the application
        }
    }
    
    /**
     * Get migration status
     */
    public static function getStatus($connection) {
        try {
            $migration = new DatabaseMigration($connection);
            return $migration->getStatus();
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'current_version' => 'unknown',
                'target_version' => 'unknown',
                'needs_migration' => true,
                'health_check' => ['healthy' => false, 'issues' => ['Migration system error']]
            ];
        }
    }
    
    /**
     * Force run migrations (for manual execution)
     */
    public static function forceRun($connection) {
        try {
            $migration = new DatabaseMigration($connection);
            return $migration->migrate();
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
