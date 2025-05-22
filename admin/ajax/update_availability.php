<?php
/**
 * AJAX handler for updating menu item availability
 */
require_once '../../includes/config.php';

// Require admin login
requireLogin();

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Check for required parameters
    if (!isset($_POST['id']) || !isset($_POST['is_available'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Get and validate parameters
    $itemId = (int) $_POST['id'];
    $isAvailable = (int) $_POST['is_available'];
    
    // Validate item ID
    if ($itemId <= 0) {
        throw new Exception('Invalid item ID');
    }
    
    // Validate is_available (should be either 0 or 1)
    if ($isAvailable !== 0 && $isAvailable !== 1) {
        throw new Exception('Invalid availability value');
    }
    
    // Get database connection
    $pdo = getDbConnection();
    
    // Check if item exists
    $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Menu item not found');
    }
    
    // Update the item's availability
    $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ?");
    $result = $stmt->execute([$isAvailable, $itemId]);
    
    if (!$result) {
        throw new Exception('Failed to update menu item availability');
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Availability updated successfully';
    
} catch (Exception $e) {
    // Error response
    $response['message'] = $e->getMessage();
    
    // Log the error
    error_log('Update availability error: ' . $e->getMessage());
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 