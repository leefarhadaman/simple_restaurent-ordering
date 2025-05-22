<?php
/**
 * Kitchen Staff Setup Script
 * Creates the kitchen_staff table and adds a default user
 */
require_once 'includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Create kitchen_staff table
    $pdo->exec("
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
    ");
    
    // Check if default user exists
    $stmt = $pdo->prepare("SELECT id FROM kitchen_staff WHERE username = 'kitchen'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Insert default kitchen staff (password: kitchen123)
        $pdo->exec("
            INSERT INTO `kitchen_staff` (`username`, `password`, `name`) 
            VALUES ('kitchen', '\$2y\$10\$ZQaKnlHyVUYDB2qEaYuqDeRNPFBJk5JUm1uG8RXrhU0O3BQUz4YqS', 'Kitchen Staff')
        ");
        echo "Default kitchen user created.<br>";
    } else {
        echo "Default kitchen user already exists.<br>";
    }
    
    echo "Kitchen staff table setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Error setting up kitchen staff table: " . $e->getMessage();
} 