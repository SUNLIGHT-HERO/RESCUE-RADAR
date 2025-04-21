-- Create the database
CREATE DATABASE IF NOT EXISTS crcp_db;
USE crcp_db;

-- Agencies table
CREATE TABLE IF NOT EXISTS agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    agency_type ENUM('rescue', 'medical', 'logistics', 'other') NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Resources table
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    resource_type ENUM('vehicle', 'personnel', 'equipment', 'supplies') NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    status ENUM('available', 'in_use', 'maintenance', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

-- Alerts table
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    status ENUM('active', 'resolved', 'cancelled') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES agencies(id)
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    agency_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES alerts(id) ON DELETE CASCADE,
    FOREIGN KEY (agency_id) REFERENCES agencies(id) ON DELETE CASCADE
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agency_id) REFERENCES agencies(id)
);

-- Insert sample data
INSERT INTO agencies (name, email, password, agency_type, contact_number, address, city, state, country, is_admin)
VALUES 
('Admin Agency', 'admin@crcp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'other', '+1234567890', '123 Admin St', 'Admin City', 'Admin State', 'Admin Country', TRUE),
('Rescue Team Alpha', 'alpha@rescue.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'rescue', '+1987654321', '456 Rescue Ave', 'Rescue City', 'Rescue State', 'Rescue Country', FALSE);

INSERT INTO resources (agency_id, resource_type, name, description, quantity, status)
VALUES 
(2, 'vehicle', 'Ambulance', 'Emergency medical vehicle', 2, 'available'),
(2, 'personnel', 'Medical Team', 'Trained medical professionals', 5, 'available'),
(2, 'equipment', 'First Aid Kits', 'Basic medical supplies', 10, 'available');

INSERT INTO alerts (title, description, severity, created_by)
VALUES 
('Flood Warning', 'Heavy rainfall expected in the northern region', 'high', 1);

-- Insert a test admin user (password: admin123)
INSERT INTO agencies (name, email, password, is_admin, is_active)
VALUES ('Admin User', 'admin@example.com', '$2y$10$8KzW5Z5z5Z5z5Z5z5Z5z5e5z5z5z5z5z5z5z5z5z5z5z5z5z5z5z5', TRUE, TRUE)
ON DUPLICATE KEY UPDATE id=id; 