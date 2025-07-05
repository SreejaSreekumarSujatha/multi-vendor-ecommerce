-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'vendor', 'admin') DEFAULT 'customer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Let's add some sample data
INSERT INTO users (name, email, password, user_type) VALUES
('Admin User', 'admin@example.com', 'admin123', 'admin'),
('John Vendor', 'john@example.com', 'password123', 'vendor'),
('Sarah Customer', 'sarah@example.com', 'password123', 'customer');