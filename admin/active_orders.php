<?php
/**
 * Active Orders Page
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Get all active orders
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'pending'
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $activeOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Active orders error: ' . $e->getMessage());
    $activeOrders = [];
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Active Orders</h1>
    <div>
        <span id="active-order-count" class="badge badge-primary"><?php echo count($activeOrders); ?></span>
        <a href="completed_orders.php" class="btn btn-sm btn-info">
            View Completed Orders
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Orders Waiting to be Completed</h2>
    </div>
    <div class="card-body">
        <?php if (empty($activeOrders)): ?>
            <p>No active orders at the moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Table</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Order Time</th>
                            <th>Wait Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeOrders as $order): 
                            $orderTime = new DateTime($order['created_at']);
                            $now = new DateTime();
                            $interval = $now->diff($orderTime);
                            
                            // Format wait time
                            if ($interval->h > 0) {
                                $waitTime = $interval->format('%h hr %i min');
                            } else {
                                $waitTime = $interval->format('%i min');
                            }
                            
                            // Get items for this order
                            $stmt = $pdo->prepare("
                                SELECT oi.*, m.name 
                                FROM order_items oi
                                JOIN menu_items m ON oi.menu_item_id = m.id
                                WHERE oi.order_id = ?
                            ");
                            $stmt->execute([$order['id']]);
                            $orderItems = $stmt->fetchAll();
                        ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>Table <?php echo $order['table_number']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary view-items" type="button" data-toggle="popover" title="Order Items" data-items="<?php echo htmlspecialchars(json_encode($orderItems)); ?>">
                                        <?php echo $order['item_count']; ?> items
                                    </button>
                                </td>
                                <td><?php echo formatPrice($order['total_amount']); ?></td>
                                <td><?php echo $orderTime->format('H:i:s'); ?></td>
                                <td><?php echo $waitTime; ?></td>
                                <td>
                                    <div class="table-action">
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-success complete-order" data-id="<?php echo $order['id']; ?>">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Items Popover Content Template -->
<div id="popover-content-template" style="display: none;">
    <table class="popover-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <!-- Will be filled by JavaScript -->
        </tbody>
    </table>
</div>

<div id="alert-container"></div>

<!-- Custom JavaScript for this page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize popovers for order items
    const viewItemsButtons = document.querySelectorAll('.view-items');
    
    viewItemsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemsData = JSON.parse(this.getAttribute('data-items'));
            
            // Create popover content
            const contentTemplate = document.getElementById('popover-content-template').cloneNode(true);
            contentTemplate.style.display = 'block';
            const tbody = contentTemplate.querySelector('tbody');
            
            // Clear existing content
            tbody.innerHTML = '';
            
            // Add items to table
            itemsData.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                `;
                tbody.appendChild(tr);
            });
            
            // Create and show the popover
            const popover = document.createElement('div');
            popover.className = 'custom-popover';
            popover.innerHTML = `
                <div class="custom-popover-header">
                    <h4>Order Items</h4>
                    <button type="button" class="close-popover">&times;</button>
                </div>
                <div class="custom-popover-body">
                    ${contentTemplate.outerHTML}
                </div>
            `;
            
            document.body.appendChild(popover);
            
            // Position the popover
            const buttonRect = this.getBoundingClientRect();
            popover.style.top = (buttonRect.bottom + window.scrollY + 10) + 'px';
            popover.style.left = (buttonRect.left + window.scrollX) + 'px';
            
            // Add close button event
            popover.querySelector('.close-popover').addEventListener('click', function() {
                popover.remove();
            });
            
            // Close when clicking outside
            document.addEventListener('click', function closePopover(e) {
                if (!popover.contains(e.target) && e.target !== button) {
                    popover.remove();
                    document.removeEventListener('click', closePopover);
                }
            });
        });
    });
});
</script>

<style>
.custom-popover {
    position: absolute;
    z-index: 1000;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    min-width: 300px;
}

.custom-popover-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid #ddd;
    background-color: #f8f9fa;
}

.custom-popover-header h4 {
    margin: 0;
    font-size: 1rem;
}

.close-popover {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    line-height: 1;
}

.custom-popover-body {
    padding: 1rem;
}

.popover-table {
    width: 100%;
    border-collapse: collapse;
}

.popover-table th,
.popover-table td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    margin-right: 0.5rem;
}

.badge-primary {
    background-color: var(--primary);
    color: white;
}
</style>

<?php require_once 'templates/footer.php'; ?> 