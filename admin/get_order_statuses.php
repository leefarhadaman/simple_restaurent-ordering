<?php
/**
 * Get Order Statuses
 * AJAX handler to fetch current order statuses
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Set content type to JSON
header('Content-Type: application/json');

// Check if order IDs are provided
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    echo json_encode(['success' => false, 'message' => 'No order IDs provided']);
    exit;
}

// Parse order IDs
$orderIds = array_filter(array_map('intval', explode(',', $_GET['ids'])));

if (empty($orderIds)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order IDs']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    // Get order statuses
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM orders 
        WHERE id IN ($placeholders)
    ");
    
    $stmt->execute($orderIds);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'orders' => $orders
    ]);

} catch (PDOException $e) {
    error_log('Error fetching order statuses: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 