<?php
/**
 * Check for New Orders
 * AJAX handler for checking new and updated orders
 */
require_once '../includes/config.php';

// Kitchen staff authentication
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

try {
    $pdo = getDbConnection();
    
    // Get counts of pending, preparing, and all active orders
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_count,
            COUNT(*) as total_count
        FROM orders 
        WHERE status IN ('pending', 'preparing')
    ");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get any orders that were updated in the last minute
    $stmt = $pdo->query("
        SELECT id, status, updated_at
        FROM orders
        WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $recentUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pendingCount' => (int) $counts['pending_count'],
        'preparingCount' => (int) $counts['preparing_count'],
        'totalCount' => (int) $counts['total_count'],
        'orderUpdates' => $recentUpdates
    ]);
} catch (PDOException $e) {
    error_log('Error checking for new orders: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} 