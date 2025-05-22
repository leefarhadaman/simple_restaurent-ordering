-- Restaurant Ordering System Database Schema

CREATE DATABASE IF NOT EXISTS restaurant_ordering;
USE restaurant_ordering;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `admins` (`username`, `password`) 
VALUES ('admin', '$2y$10$ZQaKnlHyVUYDB2qEaYuqDeRNPFBJk5JUm1uG8RXrhU0O3BQUz4YqS');

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default categories
INSERT INTO `categories` (`name`, `display_order`) VALUES 
('Starters', 1),
('Main Course', 2),
('Desserts', 3),
('Beverages', 4);

-- Menu Items Table
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample menu items
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `is_available`) VALUES
(1, 'Garlic Bread', 'Freshly baked bread with garlic butter', 4.99, 1),
(1, 'Mozzarella Sticks', 'Deep-fried mozzarella sticks served with marinara sauce', 6.99, 1),
(2, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 12.99, 1),
(2, 'Spaghetti Bolognese', 'Spaghetti with rich meat sauce', 11.99, 1),
(3, 'Chocolate Brownie', 'Warm chocolate brownie with vanilla ice cream', 5.99, 1),
(3, 'Cheesecake', 'New York style cheesecake with berry compote', 6.99, 1),
(4, 'Soft Drinks', 'Various soft drinks available', 2.99, 1),
(4, 'Fresh Juice', 'Freshly squeezed juice of your choice', 3.99, 1);

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending', 'preparing', 'completed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_name` varchar(100) DEFAULT 'My Restaurant',
  `restaurant_logo` varchar(255) DEFAULT NULL,
  `theme_color` varchar(50) DEFAULT 'Light Red & White',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`restaurant_name`, `theme_color`) 
VALUES ('Restaurant Ordering System', 'Light Red & White'); 