-- Maison Ungod Database Schema
-- Web Development 1 Project

CREATE DATABASE IF NOT EXISTS `maison_ungod` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `maison_ungod`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `full_name`  VARCHAR(100)  NOT NULL,
    `email`      VARCHAR(255)  NOT NULL UNIQUE,
    `password`   VARCHAR(255)  NOT NULL,
    `phone`      VARCHAR(20)   DEFAULT NULL,
    `address`    TEXT          DEFAULT NULL,
    `gender`     VARCHAR(20)   DEFAULT NULL,
    `dob`        DATE          DEFAULT NULL,
    `role`       VARCHAR(20)   DEFAULT 'customer',
    `created_at` TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Addresses Table
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT NOT NULL,
    `label`        VARCHAR(50) DEFAULT 'Home',
    `full_name`    VARCHAR(100) NOT NULL,
    `phone`        VARCHAR(20) NOT NULL,
    `address_line` TEXT NOT NULL,
    `postal_code`  VARCHAR(20) DEFAULT NULL,
    `is_default`   BOOLEAN DEFAULT FALSE,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(255)  NOT NULL,
    `description` TEXT          DEFAULT NULL,
    `brand`       VARCHAR(100)  DEFAULT 'Maison Ungod',
    `price`       DECIMAL(10,2) NOT NULL,
    `image`       VARCHAR(255)  DEFAULT NULL,
    `stock`       INT           DEFAULT 0,
    `category`    VARCHAR(100)  DEFAULT 'Uncategorized',
    `status`      VARCHAR(20)   DEFAULT 'Active',
    `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carts Table
CREATE TABLE IF NOT EXISTS `carts` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Items Table
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `cart_id`    INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity`   INT NOT NULL DEFAULT 1,
    FOREIGN KEY (`cart_id`)    REFERENCES `carts`(`id`)    ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_cart_product` (`cart_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `order_number`     VARCHAR(50)   DEFAULT NULL,
    `user_id`          INT           NOT NULL,
    `total_amount`     DECIMAL(10,2) NOT NULL,
    `shipping_address` TEXT          NOT NULL,
    `payment_method`   VARCHAR(50)   NOT NULL,
    `status`           VARCHAR(50)   DEFAULT 'Pending',
    `discount_amount`  DECIMAL(10,2) DEFAULT 0.00,
    `shipping_fee`     DECIMAL(10,2) DEFAULT 0.00,
    `card_last4`       VARCHAR(4)    DEFAULT NULL,
    `card_brand`       VARCHAR(20)   DEFAULT NULL,
    `card_name`        VARCHAR(100)  DEFAULT NULL,
    `transaction_id`   VARCHAR(50)   DEFAULT NULL,
    `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`   INT           NOT NULL,
    `product_id` INT           NOT NULL,
    `quantity`   INT           NOT NULL,
    `price`      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
