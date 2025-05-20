<?php
/**
 * Database Connection
 * Using PDO for database operations
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_ordering');
define('DB_USER', 'root');           // Default XAMPP username
define('DB_PASS', '');               // Default XAMPP password (empty)
define('DB_CHARSET', 'utf8mb4');

// Error mode
define('ERROR_MODE', PDO::ERRMODE_EXCEPTION);

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => ERROR_MODE,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error but don't expose details to the user
            error_log('Connection error: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }
    
    return $pdo;
} 