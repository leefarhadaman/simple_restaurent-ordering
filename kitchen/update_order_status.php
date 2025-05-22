<?php
/**
 * Kitchen Order Status Update
 * AJAX handler for updating order status to 'preparing'
 */
require_once '../includes/config.php';

// Kitchen staff authentication check
function requireKitchenLogin() {
    if (!isset($_SESSION['kitchen_id']) || empty($_SESSION['kitchen_username'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
}

// Require login
requireKitchenLogin();

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate order ID and status
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Kitchen staff can only set status to 'preparing'
if ($orderId <= 0 || $status !== 'preparing') {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if order exists and is in pending state
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'pending'");
    $stmt->execute([$orderId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or already in process']);
        exit;
    }
    
    // Update order status to preparing
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, completed_at = NULL WHERE id = ?");
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