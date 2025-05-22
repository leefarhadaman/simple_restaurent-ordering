<?php
/**
 * Utility functions for the Restaurant Ordering System
 */

/**
 * Clean input data to prevent XSS
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Get current theme settings from database
 * @return array Theme settings
 */
function getThemeSettings() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching theme settings: ' . $e->getMessage());
        return [
            'restaurant_name' => 'Restaurant Ordering System',
            'restaurant_logo' => null,
            'theme_color' => 'Light Red & White'
        ];
    }
}

/**
 * Get CSS variables based on selected theme
 * @param string $theme Theme name
 * @return array CSS variables
 */
function getThemeColors($theme) {
    $themes = [
        'Light Red & White' => [
            'primary' => '#ff5252',
            'secondary' => '#ffffff',
            'text' => '#333333',
            'accent' => '#ff8a8a'
        ],
        'Deep Blue & Yellow' => [
            'primary' => '#1a237e',
            'secondary' => '#ffeb3b',
            'text' => '#ffffff',
            'accent' => '#283593'
        ],
        'Forest Green & Cream' => [
            'primary' => '#2e7d32',
            'secondary' => '#fff8e1',
            'text' => '#1b5e20',
            'accent' => '#4caf50'
        ],
        'Black & Orange' => [
            'primary' => '#212121',
            'secondary' => '#ff9800',
            'text' => '#fafafa',
            'accent' => '#f57c00'
        ]
    ];
    
    return $themes[$theme] ?? $themes['Light Red & White'];
}

/**
 * Handle image upload with compression
 * @param array $file $_FILES array element
 * @param string $destination Directory to save image
 * @return string|false Filename if success, false on failure
 */
function handleImageUpload($file, $destination = null) {
    // Use UPLOAD_DIR constant if destination not specified
    if ($destination === null) {
        $destination = UPLOAD_DIR;
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0755, true)) {
            error_log("Failed to create directory: " . $destination);
            return false;
        }
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $targetFile = $destination . $filename;
    
    // Get image info
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        error_log("Invalid image file: " . $file['name']);
        return false; // Not a valid image
    }
    
    // Check mime type
    $mimeType = $imageInfo['mime'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mimeType, $allowedTypes)) {
        error_log("Invalid file type: " . $mimeType);
        return false; // Unsupported format
    }
    
    // Log upload attempt
    error_log("Attempting to upload file: " . $file['name'] . " to " . $targetFile);
    
    // Move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        error_log("Failed to move uploaded file: " . $file['tmp_name'] . " to " . $targetFile);
        return false;
    }
    
    error_log("File uploaded successfully: " . $targetFile);
    return $filename;
}

/**
 * Format price with currency symbol
 * @param float $price Price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Display alert message
 * @param string $message Message to display
 * @param string $type Type of alert (success, danger, warning, info)
 * @return string HTML alert
 */
function alert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

/**
 * Check if a string is valid JSON
 * @param string $string String to check
 * @return bool True if valid JSON
 */
function isValidJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Get all menu categories
 * @return array Categories
 */
function getAllCategories() {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching categories: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get menu items by category
 * @param int $categoryId Category ID
 * @return array Menu items
 */
function getMenuItemsByCategory($categoryId) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY id ASC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching menu items: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single menu item by ID
 * @param int $id Menu item ID
 * @return array|false Menu item or false
 */
function getMenuItem($id) {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching menu item: ' . $e->getMessage());
        return false;
    }
} 