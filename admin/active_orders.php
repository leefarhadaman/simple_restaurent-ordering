<?php
/**
 * Active Orders Page
 * Modern UI Implementation - Redesigned with Tailwind CSS
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

<!-- Page Content -->
<div class="w-full">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 sm:mb-5">
        <div class="flex items-center gap-3">
            <h1 class="text-xl md:text-2xl font-bold text-slate-800">Active Orders</h1>
            <span id="active-order-count" class="inline-flex items-center justify-center px-2.5 py-1 text-xs font-medium bg-violet-100 text-violet-800 rounded-full">
                <?php echo count($activeOrders); ?>
            </span>
        </div>
        <a href="completed_orders.php" class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-slate-600 hover:bg-slate-700 text-white font-medium rounded-md transition-colors text-sm sm:text-base">
            <i class="fas fa-history"></i> View Completed Orders
        </a>
    </div>

    <!-- Orders Card -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden mb-2">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200">
            <h2 class="text-base sm:text-lg font-semibold text-slate-800">Orders Waiting to be Completed</h2>
        </div>
        <div class="p-3 sm:p-4 md:p-6">
            <?php if (empty($activeOrders)): ?>
                <div class="text-center p-6 sm:p-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-slate-100 text-slate-400 mb-4">
                        <i class="fas fa-clipboard-list text-3xl sm:text-4xl"></i>
                    </div>
                    <p class="text-slate-500 text-base sm:text-lg">No active orders at the moment.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto -mx-3 sm:-mx-4 md:-mx-6">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Order #</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Table</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Items</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Total</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hidden md:table-cell">Order Time</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hidden lg:table-cell">Wait Time</th>
                                <th class="px-3 sm:px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
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

                                // Calculate urgency class based on wait time
                                $urgencyClass = '';
                                if ($interval->i > 30 || $interval->h > 0) {
                                    $urgencyClass = 'bg-red-50 text-red-700';
                                } elseif ($interval->i > 15) {
                                    $urgencyClass = 'bg-amber-50 text-amber-700';
                                }
                            ?>
                                <tr class="hover:bg-violet-50 transition-colors <?php echo $urgencyClass; ?>">
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm font-medium text-slate-700">#<?php echo $order['id']; ?></td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-slate-700">Table <?php echo $order['table_number']; ?></td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-slate-700">
                                        <button 
                                            class="inline-flex items-center px-2 sm:px-2.5 py-1 sm:py-1.5 text-xs font-medium rounded border border-violet-200 text-violet-700 hover:bg-violet-50 view-items" 
                                            type="button" 
                                            data-items="<?php echo htmlspecialchars(json_encode($orderItems)); ?>"
                                        >
                                            <i class="fas fa-eye mr-1 sm:mr-1.5"></i> <?php echo $order['item_count']; ?> items
                                        </button>
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm font-medium text-slate-700 hidden sm:table-cell">
                                        <?php echo formatPrice($order['total_amount']); ?>
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-slate-700 hidden md:table-cell">
                                        <?php echo $orderTime->format('H:i:s'); ?>
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm text-slate-700 hidden lg:table-cell">
                                        <span class="<?php echo $interval->i > 15 ? 'font-medium' : ''; ?>">
                                            <?php echo $waitTime; ?>
                                        </span>
                                    </td>
                                    <td class="px-3 sm:px-4 py-2 sm:py-3 text-sm">
                                        <div class="flex gap-2">
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 bg-blue-500 hover:bg-blue-600 text-white rounded" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="inline-flex items-center justify-center w-7 h-7 sm:w-8 sm:h-8 bg-emerald-500 hover:bg-emerald-600 text-white rounded complete-order" data-id="<?php echo $order['id']; ?>" title="Mark as Complete">
                                                <i class="fas fa-check"></i>
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
</div>

<!-- Order Items Modal Template -->
<div id="orderItemsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800">Order Items</h3>
            <button type="button" class="text-slate-400 hover:text-slate-500 focus:outline-none close-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Item</th>
                        <th class="text-center pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500 w-16">Qty</th>
                        <th class="text-right pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500 w-20">Price</th>
                    </tr>
                </thead>
                <tbody id="orderItemsList">
                    <!-- Will be filled by JavaScript -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="pt-3 text-right text-sm font-medium text-slate-700">
                            <span id="orderItemsTotal"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 flex justify-end">
            <button type="button" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium rounded-md transition-colors close-modal">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Custom JavaScript for this page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize order items modal
    const viewItemsButtons = document.querySelectorAll('.view-items');
    const orderItemsModal = document.getElementById('orderItemsModal');
    const orderItemsList = document.getElementById('orderItemsList');
    const orderItemsTotal = document.getElementById('orderItemsTotal');
    const closeModalButtons = document.querySelectorAll('.close-modal');
    
    // Function to toggle modal
    function toggleModal(show = true) {
        if(show) {
            orderItemsModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            orderItemsModal.classList.add('hidden');
            document.body.style.overflow = ''; // Re-enable scrolling
        }
    }
    
    // Add event listeners to view items buttons
    viewItemsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const items = JSON.parse(this.getAttribute('data-items'));
            displayOrderItems(items);
            toggleModal(true);
        });
    });
    
    // Close modal when clicking close buttons
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            toggleModal(false);
        });
    });
    
    // Close modal when clicking outside
    orderItemsModal.addEventListener('click', function(e) {
        if (e.target === orderItemsModal) {
            toggleModal(false);
        }
    });
    
    // Display order items in modal
    function displayOrderItems(items) {
        orderItemsList.innerHTML = '';
        let total = 0;
        
        items.forEach(item => {
            const subtotal = parseFloat(item.price) * parseInt(item.quantity);
            total += subtotal;
            
            const row = document.createElement('tr');
            row.className = 'border-b border-slate-100';
            row.innerHTML = `
                <td class="py-2 text-sm text-slate-700">${item.name}</td>
                <td class="py-2 text-sm text-slate-700 text-center">${item.quantity}</td>
                <td class="py-2 text-sm text-slate-700 text-right">${formatPrice(subtotal)}</td>
            `;
            orderItemsList.appendChild(row);
        });
        
        orderItemsTotal.textContent = `Total: ${formatPrice(total)}`;
    }
    
    // Format price helper
    function formatPrice(price) {
        return '$' + parseFloat(price).toFixed(2);
    }
    
    // Complete order functionality
    const completeButtons = document.querySelectorAll('.complete-order');
    completeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            if (confirm(`Are you sure you want to mark Order #${orderId} as completed?`)) {
                completeOrder(orderId, this);
            }
        });
    });
    
    // Function to update order status in the database
    function completeOrder(orderId, button) {
        // Show a loading state
        const originalHTML = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=completed`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Remove the row from the table
                button.closest('tr').remove();
                
                // Update the order count
                const countElement = document.getElementById('active-order-count');
                const currentCount = parseInt(countElement.textContent);
                countElement.textContent = currentCount - 1;
                
                // Show success message
                window.showToast('Order #' + orderId + ' marked as completed successfully!', 'success');
                
                // Show "No orders" message if no orders left
                if(currentCount - 1 === 0) {
                    const tableParent = document.querySelector('table').parentNode;
                    tableParent.innerHTML = `
                        <div class="text-center p-6 sm:p-10">
                            <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="fas fa-clipboard-list text-3xl sm:text-4xl"></i>
                            </div>
                            <p class="text-slate-500 text-base sm:text-lg">No active orders at the moment.</p>
                        </div>
                    `;
                }
            } else {
                // Restore button and show error
                button.disabled = false;
                button.innerHTML = originalHTML;
                window.showToast(data.error || 'Failed to update order status.', 'error');
            }
        })
        .catch(error => {
            // Restore button and show error
            button.disabled = false;
            button.innerHTML = originalHTML;
            window.showToast('An error occurred. Please try again.', 'error');
            console.error('Error:', error);
        });
    }
    
    // Auto-refresh the page every minute to get latest orders
    setTimeout(function() {
        location.reload();
    }, 60000);
});
</script>

<?php require_once 'templates/footer.php'; ?> 