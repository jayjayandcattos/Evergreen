-- ========================================
-- ACTIVITY LOGS TABLE
-- ========================================
-- This table stores user activity logs for notifications and audit purposes

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_module (module),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample activity logs for testing
INSERT INTO activity_logs (user_id, action, module, details, ip_address, created_at) VALUES
(1, 'login', 'authentication', 'User logged in successfully', '127.0.0.1', NOW() - INTERVAL 1 HOUR),
(1, 'create', 'general_ledger', 'Created new journal entry', '127.0.0.1', NOW() - INTERVAL 45 MINUTE),
(1, 'update', 'expense_tracking', 'Updated expense claim status', '127.0.0.1', NOW() - INTERVAL 30 MINUTE),
(1, 'submit', 'payroll', 'Submitted payroll for processing', '127.0.0.1', NOW() - INTERVAL 15 MINUTE),
(1, 'view', 'financial_reporting', 'Generated balance sheet report', '127.0.0.1', NOW() - INTERVAL 10 MINUTE);

