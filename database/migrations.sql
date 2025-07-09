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


-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    vendor_id INT NOT NULL,
    image_url VARCHAR(500),
    stock_quantity INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id)
);

-- Insert sample products
INSERT INTO products (name, description, price, category, vendor_id, stock_quantity) VALUES
('Gaming Laptop', 'High-performance laptop for gaming', 1299.99, 'Electronics', 2, 5),
('Wireless Headphones', 'Bluetooth noise-cancelling headphones', 199.99, 'Electronics', 2, 10),
('Coffee Maker', 'Automatic drip coffee maker', 79.99, 'Home & Kitchen', 2, 8),
('Running Shoes', 'Comfortable running shoes for all terrains', 89.99, 'Sports', 2, 15),
('Smartphone', 'Latest model with advanced features', 699.99, 'Electronics', 2, 12);


-- Cart items table
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_cart_item (customer_id, product_id)
);