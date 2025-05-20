<?php
// Display all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP Test Page</h2>";

// Test 1: Basic PHP functionality
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test 2: Database connection
try {
    $dsn = "mysql:host=localhost;dbname=restaurant_ordering;charset=utf8mb4";
    $pdo = new PDO($dsn, "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>Database connection successful</p>";
    
    // Test 3: Check if required tables exist
    $tables = ["admins", "categories", "menu_items", "orders", "order_items", "settings"];
    echo "<h3>Checking database tables:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "<li style='color:green'>Table '$table' exists</li>";
        } else {
            echo "<li style='color:red'>Table '$table' does not exist</li>";
        }
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}
?> 