-- Create Employment Status Table
CREATE TABLE IF NOT EXISTS employment_statuses (
    employment_status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default employment statuses
INSERT INTO employment_statuses (status_name, description) VALUES
('Employed', 'Regular employee working for a company or organization'),
('Self-Employed', 'Individual running their own business or working as a freelancer'),
('Unemployed', 'Currently not employed'),
('Retired', 'No longer in active employment due to retirement'),
('Student', 'Currently pursuing education'),
('Homemaker', 'Managing household responsibilities');

-- Create Source of Funds Table
CREATE TABLE IF NOT EXISTS source_of_funds (
    source_id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    requires_proof TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default source of funds
INSERT INTO source_of_funds (source_name, description, requires_proof) VALUES
('Employment', 'Income from regular employment or salary', 1),
('Business', 'Income from business operations or entrepreneurship', 1),
('Investment', 'Returns from investments, stocks, or securities', 1),
('Savings', 'Personal savings accumulated over time', 0),
('Inheritance', 'Funds received through inheritance', 1),
('Gift', 'Monetary gifts from family or friends', 1),
('Pension', 'Retirement pension or benefits', 1),
('Remittance', 'Money sent from abroad by family members', 0),
('Other', 'Other legitimate sources of funds', 1);
