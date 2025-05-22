<?php
/**
 * Script to add the is_vegetarian column to menu_items table
 */
require_once __DIR__ . '/../includes/config.php';

try {
    $pdo = getDbConnection();
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'is_vegetarian'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'is_vegetarian' already exists in menu_items table.";
        exit;
    }
    
    // Add the column
    $sql = "ALTER TABLE menu_items ADD COLUMN is_vegetarian TINYINT(1) NOT NULL DEFAULT 0";
    $result = $pdo->exec($sql);
    
    echo "Column 'is_vegetarian' added successfully to menu_items table.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
} 