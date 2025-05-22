<?php
/**
 * AJAX handler for updating menu item vegetarian status
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
    if (!isset($_POST['id']) || !isset($_POST['is_vegetarian'])) {
        throw new Exception('Missing required parameters');
    }
    
    // Get and validate parameters
    $itemId = (int) $_POST['id'];
    $isVegetarian = (int) $_POST['is_vegetarian'];
    
    // Validate item ID
    if ($itemId <= 0) {
        throw new Exception('Invalid item ID');
    }
    
    // Validate is_vegetarian (should be either 0 or 1)
    if ($isVegetarian !== 0 && $isVegetarian !== 1) {
        throw new Exception('Invalid vegetarian value');
    }
    
    // Get database connection
    $pdo = getDbConnection();
    
    // Check if item exists
    $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE id = ?");
    $stmt->execute([$itemId]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Menu item not found');
    }
    
    // Update the item's vegetarian status
    $stmt = $pdo->prepare("UPDATE menu_items SET is_vegetarian = ? WHERE id = ?");
    $result = $stmt->execute([$isVegetarian, $itemId]);
    
    if (!$result) {
        throw new Exception('Failed to update menu item vegetarian status');
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Vegetarian status updated successfully';
    
} catch (Exception $e) {
    // Error response
    $response['message'] = $e->getMessage();
    
    // Log the error
    error_log('Update vegetarian status error: ' . $e->getMessage());
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 