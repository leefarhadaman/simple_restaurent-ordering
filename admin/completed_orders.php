<?php
/**
 * Completed Orders Page
 * Modern UI Implementation - Redesigned with Tailwind CSS
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

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-800">Completed Orders</h1>
        <a href="active_orders.php" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
            <i class="fas fa-clipboard-list"></i> View Active Orders
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">Filter by Date</h2>
        </div>
        <div class="p-6">
            <form id="date-filter-form" method="get" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label for="start_date" class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                        value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="end_date" class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                        value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">Summary for Selected Period</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-violet-50 rounded-lg p-4 border border-violet-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-1"><?php echo $totalOrders; ?> Orders</h3>
                    <p class="text-sm text-slate-600">Total completed orders</p>
                </div>
                <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-1"><?php echo formatPrice($totalRevenue); ?></h3>
                    <p class="text-sm text-slate-600">Total revenue generated</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                    <h3 class="text-xl font-bold text-slate-800 mb-1">
                        <?php if ($totalOrders > 0): ?>
                            <?php echo formatPrice($totalRevenue / $totalOrders); ?>
                        <?php else: ?>
                            $0.00
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-slate-600">Average order value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">Completed Orders</h2>
        </div>
        <div class="p-6">
            <?php if (empty($completedOrders)): ?>
                <div class="text-center p-8">
                    <i class="fas fa-clipboard-check text-5xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500">No completed orders found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Order #</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Table</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Items</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Order Time</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Completed Time</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Processing Time</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
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
                                <tr class="hover:bg-violet-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700">#<?php echo $order['id']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700">Table <?php echo $order['table_number']; ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <button class="inline-flex items-center justify-center px-2.5 py-1 rounded border border-violet-300 bg-violet-50 text-violet-700 text-xs font-medium hover:bg-violet-100 transition-colors view-items" type="button" data-items="<?php echo htmlspecialchars(json_encode($orderItems)); ?>">
                                            <?php echo $order['item_count']; ?> items
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $orderTime->format('H:i:s'); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $completedTime->format('H:i:s'); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $processingTime; ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded" title="View Order">
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
</div>

<!-- Order Items Modal -->
<div id="itemsModal" class="hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Order Items</h3>
            <button type="button" id="closeItemsModal" class="text-slate-400 hover:text-slate-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                        <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Price</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody" class="divide-y divide-slate-200">
                    <!-- Will be filled by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal for order items
    const viewItemsButtons = document.querySelectorAll('.view-items');
    const itemsModal = document.getElementById('itemsModal');
    const itemsTableBody = document.getElementById('itemsTableBody');
    const closeItemsModal = document.getElementById('closeItemsModal');
    
    // Open modal when clicking on items button
    viewItemsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemsData = JSON.parse(this.getAttribute('data-items'));
            
            // Clear existing content
            itemsTableBody.innerHTML = '';
            
            // Add items to table
            itemsData.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50';
                tr.innerHTML = `
                    <td class="px-4 py-3 text-sm text-slate-700">${item.name}</td>
                    <td class="px-4 py-3 text-sm text-slate-700">${item.quantity}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-700">$${parseFloat(item.price).toFixed(2)}</td>
                `;
                itemsTableBody.appendChild(tr);
            });
            
            // Show modal
            itemsModal.classList.remove('hidden');
        });
    });
    
    // Close modal when clicking close button
    closeItemsModal.addEventListener('click', function() {
        itemsModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    itemsModal.addEventListener('click', function(e) {
        if (e.target === itemsModal) {
            itemsModal.classList.add('hidden');
        }
    });
    
    // Validate date range
    const dateForm = document.getElementById('date-filter-form');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    dateForm.addEventListener('submit', function(e) {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (endDate < startDate) {
            e.preventDefault();
            alert('End date cannot be earlier than start date');
        }
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 