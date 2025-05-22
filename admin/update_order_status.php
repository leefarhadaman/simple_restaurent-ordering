<?php
/**
 * Update Order Status
 * AJAX handler for marking orders as completed
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate order ID
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($orderId <= 0 || !in_array($status, ['pending', 'preparing', 'completed'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Update order status and set completed time if needed
    if ($status === 'completed') {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, completed_at = NOW() 
            WHERE id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = ?, completed_at = NULL 
            WHERE id = ?
        ");
    }
    
    $result = $stmt->execute([$status, $orderId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
} catch (PDOException $e) {
    error_log('Error updating order status: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 