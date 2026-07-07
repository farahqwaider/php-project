CREATE DATABASE IF NOT EXISTS micro_store
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE micro_store;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO users (name, email, password, role) VALUES
('System Admin', 'admin@store.com', '$2y$12$cgZAg24ySlRdgCT.v2/p5OojZyD1OQe9knlzfgv1TRMyODXkQMzr2', 'admin');

INSERT IGNORE INTO users (name, email, password, role) VALUES
('Test Customer', 'user@store.com', '$2y$12$VP1DzqiOGCo1qBrYzEvSsetJlTkuvEDaqrUQ52NrcDfWSnbquw6su', 'customer');

INSERT IGNORE INTO categories (name, description) VALUES
('Electronics', 'Gadgets, phones, laptops and accessories'),
('Clothing', 'Men and women fashion items'),
('Books', 'Physical books and educational materials'),
('Home & Kitchen', 'Cookware, decor and home essentials');

INSERT IGNORE INTO products (category_id, name, description, price, stock, image) VALUES
(1, 'Wireless Headphones', 'Bluetooth over-ear headphones with noise cancellation', 59.99, 20, NULL),
(1, 'USB-C Cable', 'Durable braided USB-C charging cable 2m', 12.50, 50, NULL),
(2, 'Cotton T-Shirt', 'Comfortable plain cotton t-shirt available in multiple sizes', 19.99, 30, NULL),
(3, 'PHP Programming Book', 'A beginner-friendly guide to PHP and MySQL development', 34.99, 15, NULL);
