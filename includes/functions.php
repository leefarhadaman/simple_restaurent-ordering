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
function handleImageUpload($file, $destination = '../uploads/') {
    // Create directory if it doesn't exist
    if (!file_exists($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Check file size (max 300KB)
    $maxSize = 300 * 1024; // 300KB in bytes
    if ($file['size'] > $maxSize) {
        // Need to compress the image
        $needCompression = true;
    } else {
        $needCompression = false;
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $targetFile = $destination . $filename;
    
    // Get image info
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        return false; // Not a valid image
    }
    
    $mimeType = $imageInfo['mime'];
    
    if ($needCompression) {
        // Compress image based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
                imagejpeg($image, $targetFile, 75); // 75% quality
                break;
            case 'image/png':
                $image = imagecreatefrompng($file['tmp_name']);
                imagepng($image, $targetFile, 6); // Compression level 6 (0-9)
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file['tmp_name']);
                imagegif($image, $targetFile);
                break;
            default:
                return false; // Unsupported format
        }
        
        if (isset($image)) {
            imagedestroy($image);
        }
    } else {
        // Just move the file
        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return false;
        }
    }
    
    return $filename;
}

/**
 * Format price with currency symbol
 * @param float $price Price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
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