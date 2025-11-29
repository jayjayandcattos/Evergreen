-- Create bank_employees table for employee authentication
CREATE TABLE IF NOT EXISTS `bank_employees` (
  `employee_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `role` ENUM('admin', 'teller', 'manager') DEFAULT 'teller',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Username: admin
-- Password: admin123
INSERT INTO `bank_employees` (`username`, `password_hash`, `email`, `first_name`, `last_name`, `role`, `is_active`)
VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@evergreenbank.com', 'System', 'Administrator', 'admin', 1),
('teller1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teller1@evergreenbank.com', 'John', 'Doe', 'teller', 1)
ON DUPLICATE KEY UPDATE username=username;

-- Note: Default password for all users is "password"
-- Hash generated using: password_hash('password', PASSWORD_DEFAULT)
