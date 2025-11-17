-- ========================================
-- SYSTEM NOTIFICATIONS TABLE
-- ========================================
-- This table stores notifications from external systems (banking, payments, etc.)

CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('banking', 'payment', 'reconciliation', 'alert', 'system', 'transaction') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('unread', 'read', 'archived') DEFAULT 'unread',
    related_module VARCHAR(100),
    related_id VARCHAR(100),
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL,
    INDEX idx_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_related (related_module, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample system notifications for testing
INSERT INTO system_notifications (notification_type, title, message, priority, related_module) VALUES
('banking', 'Incoming Fund Transfer Received', 'Transfer of ₱50,000.00 received from Account #1234567890', 'high', 'banking'),
('banking', 'Bank Reconciliation Required', 'Reconciliation needed for account ending in 7890', 'medium', 'banking'),
('payment', 'Payment Processed', 'Outgoing payment of ₱25,000.00 processed successfully', 'medium', 'payments'),
('alert', 'Low Account Balance Alert', 'Account balance is below threshold: ₱5,000.00', 'high', 'banking'),
('transaction', 'Failed Transaction', 'Transaction #TXN-2024-001 failed due to insufficient funds', 'urgent', 'transactions'),
('banking', 'New Bank Statement Available', 'Monthly statement for account #1234567890 is ready', 'low', 'banking'),
('system', 'Interest Posted', 'Interest of ₱1,250.00 posted to account #1234567890', 'medium', 'banking'),
('banking', 'Check Deposit Cleared', 'Check #12345 has been cleared and funds are available', 'medium', 'banking');

