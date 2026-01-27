-- Setup script for PhpDb benchmark tests
-- Creates a test database with sample tables for benchmarking

CREATE DATABASE IF NOT EXISTS phpdb;
USE phpdb;

-- Users table
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    role ENUM('user', 'admin', 'moderator') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_total (total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO users (name, email, status, role) VALUES
    ('John Doe', 'john@example.com', 'active', 'admin'),
    ('Jane Smith', 'jane@example.com', 'active', 'user'),
    ('Bob Wilson', 'bob@example.com', 'active', 'user'),
    ('Alice Brown', 'alice@example.com', 'inactive', 'user'),
    ('Charlie Davis', 'charlie@example.com', 'pending', 'moderator');

INSERT INTO orders (user_id, status, total) VALUES
    (1, 'completed', 150.00),
    (1, 'completed', 75.50),
    (2, 'completed', 200.00),
    (2, 'pending', 50.00),
    (3, 'processing', 125.75);

INSERT INTO order_items (order_id, product_name, quantity, price) VALUES
    (1, 'Widget A', 2, 25.00),
    (1, 'Widget B', 1, 100.00),
    (2, 'Gadget X', 3, 25.17),
    (3, 'Widget A', 4, 50.00),
    (4, 'Gadget Y', 1, 50.00),
    (5, 'Widget C', 5, 25.15);
