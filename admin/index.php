<?php
/**
 * Admin Dashboard
 * Modern UI Implementation - Redesigned with Tailwind CSS
 */

// Wrap all code in try-catch for better error handling
try {
    require_once '../includes/config.php';

    // Require login
    requireLogin();

    // Get counts for dashboard widgets
    try {
        $pdo = getDbConnection();
        
        // Count active orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $activeOrders = $stmt->fetch()['count'];
        
        // Count menu items
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM menu_items");
        $menuItems = $stmt->fetch()['count'];
        
        // Count categories
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $categories = $stmt->fetch()['count'];
        
        // Total orders today
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
        $todayOrders = $stmt->fetch()['count'];
        
        // Revenue today
        $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE()");
        $todayRevenue = $stmt->fetch()['total'] ?? 0;
        
        // Get recent orders
        $stmt = $pdo->query("
            SELECT o.*, COUNT(oi.id) as item_count 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'pending'
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");
        $recentOrders = $stmt->fetchAll();
        
        // Get admin user count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $adminCount = $stmt->fetch()['count'];
        
    } catch (PDOException $e) {
        error_log('Dashboard error: ' . $e->getMessage());
    }

    // Get settings
    $settings = getThemeSettings();

    // Get current user info
    $adminInfo = getAdminInfo($_SESSION['admin_id']);

    // Include header template
    require_once 'templates/header.php';
?>

<!-- Dashboard Content -->
<div class="w-full">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h1 class="text-xl md:text-2xl font-bold text-slate-800">Dashboard</h1>
        <a href="active_orders.php" class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors text-sm sm:text-base">
            <i class="fas fa-clipboard-list"></i> View All Orders
        </a>
    </div>

    <!-- Welcome Card -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden mb-5">
        <div class="p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-4xl sm:text-5xl text-violet-600">
                    <i class="fas fa-circle-user"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-semibold text-slate-800 mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                    <p class="text-slate-500 text-sm sm:text-base">
                        Last login: 
                        <?php 
                        if (!empty($adminInfo['last_login'])) {
                            $lastLogin = new DateTime($adminInfo['last_login']);
                            echo $lastLogin->format('M d, Y - H:i');
                        } else {
                            echo 'First login';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Widgets -->
    <div class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 md:gap-6 mb-5">
        <div class="bg-white rounded-lg shadow border border-slate-200 p-4 sm:p-6 flex items-center hover:shadow-md transition-shadow">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 mr-3 sm:mr-4">
                <i class="fas fa-clipboard-list text-base sm:text-lg"></i>
            </div>
            <div>
                <div class="text-xs sm:text-sm text-slate-500 font-medium">Active Orders</div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800" id="active-order-count"><?php echo $activeOrders; ?></div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border border-slate-200 p-4 sm:p-6 flex items-center hover:shadow-md transition-shadow">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mr-3 sm:mr-4">
                <i class="fas fa-utensils text-base sm:text-lg"></i>
            </div>
            <div>
                <div class="text-xs sm:text-sm text-slate-500 font-medium">Menu Items</div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800"><?php echo $menuItems; ?></div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border border-slate-200 p-4 sm:p-6 flex items-center hover:shadow-md transition-shadow">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 mr-3 sm:mr-4">
                <i class="fas fa-list text-base sm:text-lg"></i>
            </div>
            <div>
                <div class="text-xs sm:text-sm text-slate-500 font-medium">Categories</div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800"><?php echo $categories; ?></div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border border-slate-200 p-4 sm:p-6 flex items-center hover:shadow-md transition-shadow">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 mr-3 sm:mr-4">
                <i class="fas fa-chart-line text-base sm:text-lg"></i>
            </div>
            <div>
                <div class="text-xs sm:text-sm text-slate-500 font-medium">Today's Revenue</div>
                <div class="text-xl sm:text-2xl font-bold text-slate-800"><?php echo formatPrice($todayRevenue); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden mb-5">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-base sm:text-lg text-slate-800">Performance Overview</h2>
            <div class="flex gap-2">
                <button class="px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium bg-violet-100 text-violet-700 rounded border border-violet-200 hover:bg-violet-200 transition-colors">Weekly</button>
                <button class="px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium bg-white text-slate-600 rounded border border-slate-200 hover:bg-slate-50 transition-colors">Monthly</button>
            </div>
        </div>
        <div class="p-3 sm:p-6">
            <div class="h-[250px] md:h-[300px]">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 mb-2">
        <!-- Main Column -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden h-full">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-base sm:text-lg text-slate-800">Recent Active Orders</h2>
                    <a href="active_orders.php" class="inline-flex items-center gap-1 px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium border border-violet-300 text-violet-700 rounded hover:bg-violet-50 transition-colors">
                        <i class="fas fa-arrow-right"></i> View All
                    </a>
                </div>
                <div class="p-3 sm:p-6">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center p-4 sm:p-8">
                            <i class="fas fa-clipboard-list text-4xl sm:text-5xl text-slate-300 mb-3 sm:mb-4"></i>
                            <p class="text-slate-500 text-sm sm:text-base">No active orders at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto -mx-3 sm:-mx-6">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Order #</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Table</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hidden xs:table-cell">Items</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Total</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 hidden sm:table-cell">Time</th>
                                        <th class="px-3 sm:px-4 py-2 sm:py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr class="hover:bg-violet-50 transition-colors">
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium text-slate-700">#<?php echo $order['id']; ?></td>
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-slate-700">Table <?php echo $order['table_number']; ?></td>
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-slate-700 hidden xs:table-cell"><?php echo $order['item_count']; ?> items</td>
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm font-medium text-slate-700"><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-slate-700 hidden sm:table-cell">
                                                <?php 
                                                $orderTime = new DateTime($order['created_at']);
                                                echo $orderTime->format('H:i:s');
                                                ?>
                                            </td>
                                            <td class="px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm">
                                                <div class="flex gap-1 sm:gap-2">
                                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center w-6 h-6 sm:w-8 sm:h-8 bg-blue-500 hover:bg-blue-600 text-white rounded" title="View Order">
                                                        <i class="fas fa-eye text-xs sm:text-sm"></i>
                                                    </a>
                                                    <button class="inline-flex items-center justify-center w-6 h-6 sm:w-8 sm:h-8 bg-emerald-500 hover:bg-emerald-600 text-white rounded complete-order" data-id="<?php echo $order['id']; ?>" title="Mark as Complete">
                                                        <i class="fas fa-check text-xs sm:text-sm"></i>
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

        <!-- Side Column -->
        <div class="lg:col-span-1">
            <!-- Today's Summary Card -->
            <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200">
                    <h2 class="font-semibold text-base sm:text-lg text-slate-800">Today's Summary</h2>
                </div>
                <div class="p-4 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <p class="text-xs sm:text-sm text-slate-500 mb-1">Orders</p>
                            <h3 class="text-lg sm:text-xl font-bold text-slate-800"><?php echo $todayOrders; ?></h3>
                        </div>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-violet-600 flex items-center justify-center text-white">
                            <i class="fas fa-shopping-cart text-xs sm:text-base"></i>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs sm:text-sm text-slate-500 mb-1">Revenue</p>
                            <h3 class="text-lg sm:text-xl font-bold text-slate-800"><?php echo formatPrice($todayRevenue); ?></h3>
                        </div>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white">
                            <i class="fas fa-dollar-sign text-xs sm:text-base"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Admin Stats Card -->
            <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden mt-4 sm:mt-6">
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200">
                    <h2 class="font-semibold text-base sm:text-lg text-slate-800">System Stats</h2>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs sm:text-sm text-slate-500 mb-1">Admin Users</p>
                            <h3 class="text-lg sm:text-xl font-bold text-slate-800"><?php echo $adminCount; ?></h3>
                        </div>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                            <i class="fas fa-user-shield text-xs sm:text-base"></i>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-2 sm:gap-3 mt-4 sm:mt-6">
                        <a href="menu_items.php" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm text-center font-medium rounded-md bg-violet-100 text-violet-700 hover:bg-violet-200 transition-colors">
                            <i class="fas fa-utensils mr-1"></i> Menu
                        </a>
                        <a href="settings.php" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm text-center font-medium rounded-md bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                            <i class="fas fa-gear mr-1"></i> Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sample data for chart
    const ctx = document.getElementById('ordersChart').getContext('2d');
    
    // Sample data
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const orderData = [15, 22, 18, 24, 32, 38, 29];
    const revenueData = [150, 220, 180, 240, 320, 380, 290];
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Orders',
                    data: orderData,
                    borderColor: '#7c3aed',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Revenue (x10)',
                    data: revenueData,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            },
        }
    });
    
    // Handle order completion
    const completeButtons = document.querySelectorAll('.complete-order');
    completeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            if (confirm('Mark this order as completed?')) {
                // Send AJAX request to complete order
                fetch('complete_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row or update UI as needed
                        this.closest('tr').remove();
                        
                        // Update active order count
                        const countElement = document.getElementById('active-order-count');
                        countElement.textContent = parseInt(countElement.textContent) - 1;
                        
                        // Show success message
                        alert('Order #' + orderId + ' marked as completed');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
});
</script>
<?php
    // Include footer
    require_once 'templates/footer.php';
} catch (Exception $e) {
    // Log error and show message
    error_log('Dashboard error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
}
?> 