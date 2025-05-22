USE restaurant_ordering;

-- Kitchen Staff Table
CREATE TABLE IF NOT EXISTS `kitchen_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default kitchen staff (password: kitchen123)
INSERT INTO `kitchen_staff` (`username`, `password`, `name`) 
VALUES ('kitchen', '$2y$10$ZQaKnlHyVUYDB2qEaYuqDeRNPFBJk5JUm1uG8RXrhU0O3BQUz4YqS', 'Kitchen Staff'); 