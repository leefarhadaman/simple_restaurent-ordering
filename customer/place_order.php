<?php
/**
 * Process order placement
 * Receives JSON data from the client, validates it, and inserts into database
 */
require_once '../includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data from the request
$jsonData = file_get_contents('php://input');
if (!isValidJson($jsonData)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

$data = json_decode($jsonData, true);

// Validate required fields
if (!isset($data['table_number']) || !isset($data['items']) || !isset($data['total'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate table number
$tableNumber = (int) $data['table_number'];
if ($tableNumber <= 0 || $tableNumber > 99) {
    echo json_encode(['success' => false, 'message' => 'Invalid table number']);
    exit;
}

// Validate items array
if (!is_array($data['items']) || count($data['items']) === 0) {
    echo json_encode(['success' => false, 'message' => 'No items in cart']);
    exit;
}

// Validate total
$total = (float) $data['total'];
if ($total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid total amount']);
    exit;
}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();
    
    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (table_number, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$tableNumber, $total]);
    $orderId = $pdo->lastInsertId();
    
    // Insert order items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($data['items'] as $item) {
        // Validate item fields
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception('Invalid item format');
        }
        
        // Get menu item to ensure it exists and get current price
        $menuItem = getMenuItem($item['id']);
        if (!$menuItem) {
            throw new Exception('Menu item not found');
        }
        
        // Use current menu price (not the one sent from client)
        $stmt->execute([
            $orderId,
            $item['id'],
            $item['quantity'],
            $menuItem['price']
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order placed successfully', 'orderId' => $orderId]);
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error placing order: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()]);
} 