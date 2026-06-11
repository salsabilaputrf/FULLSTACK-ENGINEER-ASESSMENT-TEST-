CREATE DATABASE IF NOT EXISTS online_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE online_store;

CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)   NOT NULL,
    price       DECIMAL(12, 2) NOT NULL,
    flash_price DECIMAL(12, 2) DEFAULT NULL,
    stock       INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255)   NOT NULL,
    total_price   DECIMAL(12, 2) NOT NULL DEFAULT 0,
    status        ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED   NOT NULL,
    product_id INT UNSIGNED   NOT NULL,
    quantity   INT UNSIGNED   NOT NULL DEFAULT 1,
    price      DECIMAL(12, 2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO products (name, price, flash_price, stock) VALUES
    ('Laptop Gaming XYZ',  15000000.00, 9999000.00, 10),
    ('Mouse Wireless ABC',   350000.00,       NULL, 50),
    ('Keyboard Mechanical',  750000.00,  499000.00,  5);