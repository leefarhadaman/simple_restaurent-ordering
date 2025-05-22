USE restaurant_ordering;

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending', 'preparing', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` text,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `payment_method` enum('cash', 'card', 'upi', 'pending') DEFAULT 'pending',
  `payment_status` enum('paid', 'unpaid') DEFAULT 'unpaid',
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_completed_at` (`completed_at`),
  KEY `idx_table_number` (`table_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items Table (related)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample orders for testing
INSERT INTO `orders` (`table_number`, `total_amount`, `status`, `created_at`, `completed_at`, `payment_method`, `payment_status`)
VALUES 
(1, 299.50, 'completed', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 'cash', 'paid'),
(2, 449.75, 'pending', NOW(), NULL, 'pending', 'unpaid'),
(3, 650.00, 'preparing', DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL, 'pending', 'unpaid'),
(4, 825.25, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 23 HOUR), 'card', 'paid');

-- To modify an existing orders table, run these commands:
-- 
-- ALTER TABLE `orders` 
--   ADD COLUMN `notes` text AFTER `updated_at`,
--   ADD COLUMN `customer_name` varchar(100) DEFAULT NULL AFTER `notes`,
--   ADD COLUMN `customer_phone` varchar(20) DEFAULT NULL AFTER `customer_name`,
--   ADD COLUMN `payment_method` enum('cash', 'card', 'upi', 'pending') DEFAULT 'pending' AFTER `customer_phone`,
--   ADD COLUMN `payment_status` enum('paid', 'unpaid') DEFAULT 'unpaid' AFTER `payment_method`,
--   ADD COLUMN `discount_amount` decimal(10,2) DEFAULT 0.00 AFTER `payment_status`,
--   ADD COLUMN `tax_amount` decimal(10,2) DEFAULT 0.00 AFTER `discount_amount`,
--   ADD KEY `idx_status` (`status`),
--   ADD KEY `idx_created_at` (`created_at`),
--   ADD KEY `idx_completed_at` (`completed_at`),
--   ADD KEY `idx_table_number` (`table_number`); 