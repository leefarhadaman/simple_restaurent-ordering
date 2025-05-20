<?php
/**
 * Completed Orders Page
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Default to today's date if not specified
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get completed orders within date range
try {
    $pdo = getDbConnection();
    
    // Get orders
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'completed'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY o.id
        ORDER BY o.completed_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $completedOrders = $stmt->fetchAll();
    
    // Get total revenue for the period
    $stmt = $pdo->prepare("
        SELECT SUM(total_amount) as total_revenue, COUNT(*) as total_orders
        FROM orders
        WHERE status = 'completed'
        AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $summary = $stmt->fetch();
    
    $totalRevenue = $summary['total_revenue'] ?? 0;
    $totalOrders = $summary['total_orders'] ?? 0;
    
} catch (PDOException $e) {
    error_log('Completed orders error: ' . $e->getMessage());
    $completedOrders = [];
    $totalRevenue = 0;
    $totalOrders = 0;
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Completed Orders</h1>
    <div>
        <a href="active_orders.php" class="btn btn-sm btn-primary">
            View Active Orders
        </a>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Filter by Date</h2>
    </div>
    <div class="card-body">
        <form id="date-filter-form" method="get" class="row" style="display: flex; gap: 15px; align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group" style="flex: 0 0 auto;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Card -->
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Summary for Selected Period</h2>
    </div>
    <div class="card-body">
        <div class="row" style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <h3><?php echo $totalOrders; ?> Orders</h3>
                <p>Total completed orders</p>
            </div>
            <div style="flex: 1;">
                <h3><?php echo formatPrice($totalRevenue); ?></h3>
                <p>Total revenue generated</p>
            </div>
            <div style="flex: 1;">
                <?php if ($totalOrders > 0): ?>
                    <h3><?php echo formatPrice($totalRevenue / $totalOrders); ?></h3>
                    <p>Average order value</p>
                <?php else: ?>
                    <h3>$0.00</h3>
                    <p>Average order value</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Completed Orders</h2>
    </div>
    <div class="card-body">
        <?php if (empty($completedOrders)): ?>
            <p>No completed orders found for the selected date range.</p>
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
                            <th>Completed Time</th>
                            <th>Processing Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedOrders as $order): 
                            $orderTime = new DateTime($order['created_at']);
                            $completedTime = new DateTime($order['completed_at']);
                            $interval = $orderTime->diff($completedTime);
                            
                            // Format processing time
                            if ($interval->h > 0) {
                                $processingTime = $interval->format('%h hr %i min');
                            } else {
                                $processingTime = $interval->format('%i min');
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
                                <td><?php echo $completedTime->format('H:i:s'); ?></td>
                                <td><?php echo $processingTime; ?></td>
                                <td>
                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
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

<!-- Custom JavaScript for this page - reusing the same popover code from active_orders.php -->
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

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.mb-4 {
    margin-bottom: 1.5rem;
}
</style>

<?php require_once 'templates/footer.php'; ?> 