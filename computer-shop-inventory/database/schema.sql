-- ==========================================================
-- Computer Shop Inventory Management System
-- Database: computer_shop_inventory
-- Prepared by: Bryan Cahayag Cole | Instructor: Noel Montecillo
-- Date: September 2026
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `computer_shop_inventory` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `computer_shop_inventory`;

-- Drop tables in reverse order of foreign key dependencies
DROP TABLE IF EXISTS `stock_transactions`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- Table: users
-- Roles: admin, staff, auditor
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'staff', 'auditor') NOT NULL DEFAULT 'staff',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: categories
-- Hardware component classifications
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-cpu',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: suppliers
-- Computer component distributors and suppliers
-- --------------------------------------------------------
CREATE TABLE `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(100) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: products
-- Hardware components, peripherals, laptops, accessories
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(200) NOT NULL,
  `category_id` INT NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `specifications` TEXT NULL,
  `unit_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `quantity` INT NOT NULL DEFAULT 0,
  `min_threshold` INT NOT NULL DEFAULT 5,
  `location` VARCHAR(100) DEFAULT 'Shelf A1',
  `status` ENUM('active', 'discontinued') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: stock_transactions
-- Audit log of all stock movements (Stock-In and Stock-Out)
-- --------------------------------------------------------
CREATE TABLE `stock_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `type` ENUM('IN', 'OUT') NOT NULL,
  `purpose` ENUM('Supplier Delivery', 'Customer Sale', 'Defective/RMA', 'Shop Build', 'Internal Use', 'Stock Adjustment') NOT NULL,
  `reference_no` VARCHAR(100) NULL,
  `quantity` INT NOT NULL,
  `unit_cost` DECIMAL(12, 2) NULL,
  `unit_price` DECIMAL(12, 2) NULL,
  `supplier_id` INT NULL,
  `recipient` VARCHAR(150) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_trans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trans_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for performance & reporting
CREATE INDEX `idx_product_category` ON `products` (`category_id`);
CREATE INDEX `idx_product_sku` ON `products` (`sku`);
CREATE INDEX `idx_product_qty` ON `products` (`quantity`);
CREATE INDEX `idx_trans_type` ON `stock_transactions` (`type`);
CREATE INDEX `idx_trans_created` ON `stock_transactions` (`created_at`);
