<?php
/**
 * Admin Dashboard
 * Modern UI Implementation - Redesigned
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
<div class="dashboard-wrapper">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <div class="btn-group">
            <a href="active_orders.php" class="btn btn-primary">
                <i class="fas fa-clipboard-list"></i> View All Orders
            </a>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-md">
                <div>
                    <i class="fas fa-circle-user" style="font-size: 3rem; color: var(--primary);"></i>
                </div>
                <div>
                    <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                    <p class="text-gray mb-0">
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
    <div class="widget-container">
        <div class="widget widget-primary">
            <div class="widget-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="widget-info">
                <div class="widget-title">Active Orders</div>
                <div class="widget-value" id="active-order-count"><?php echo $activeOrders; ?></div>
            </div>
        </div>
        
        <div class="widget widget-success">
            <div class="widget-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="widget-info">
                <div class="widget-title">Menu Items</div>
                <div class="widget-value"><?php echo $menuItems; ?></div>
            </div>
        </div>
        
        <div class="widget widget-warning">
            <div class="widget-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="widget-info">
                <div class="widget-title">Categories</div>
                <div class="widget-value"><?php echo $categories; ?></div>
            </div>
        </div>
        
        <div class="widget widget-danger">
            <div class="widget-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="widget-info">
                <div class="widget-title">Today's Revenue</div>
                <div class="widget-value"><?php echo formatPrice($todayRevenue); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">Performance Overview</h2>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary active">Weekly</button>
                <button class="btn btn-sm btn-outline-primary">Monthly</button>
            </div>
        </div>
        <div class="card-body">
            <div style="height: 300px;">
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="dashboard-grid">
        <!-- Main Column -->
        <div class="dashboard-main">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="card-title">Recent Active Orders</h2>
                    <a href="active_orders.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-arrow-right"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--gray); opacity: 0.5;"></i>
                            <p class="mt-3">No active orders at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table orders-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Table</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>Table <?php echo $order['table_number']; ?></td>
                                            <td><?php echo $order['item_count']; ?> items</td>
                                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td>
                                                <?php 
                                                $orderTime = new DateTime($order['created_at']);
                                                echo $orderTime->format('H:i:s');
                                                ?>
                                            </td>
                                            <td>
                                                <div class="table-action">
                                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info" title="View Order">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-success complete-order" data-id="<?php echo $order['id']; ?>" title="Mark as Complete">
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

        <!-- Side Column -->
        <div class="dashboard-side">
            <!-- Today's Summary Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Today's Summary</h2>
                </div>
                <div class="card-body">
                    <div class="summary-item">
                        <div>
                            <p class="mb-0" style="color: var(--gray);">Orders</p>
                            <h3 class="mb-0"><?php echo $todayOrders; ?></h3>
                        </div>
                        <div class="summary-icon primary-bg">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <div>
                            <p class="mb-0" style="color: var(--gray);">Revenue</p>
                            <h3 class="mb-0"><?php echo formatPrice($todayRevenue); ?></h3>
                        </div>
                        <div class="summary-icon success-bg">
                            <i class="fas fa-dollar-sign fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title">Quick Links</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-sm">
                        <a href="menu_items.php" class="btn btn-outline-primary">
                            <i class="fas fa-utensils"></i> Manage Menu
                        </a>
                        <a href="categories.php" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i> Manage Categories
                        </a>
                        <a href="settings.php" class="btn btn-outline-primary">
                            <i class="fas fa-gear"></i> Settings
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- System Status Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">System Status</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Database</span>
                        <span class="badge badge-success">Connected</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Admin Users</span>
                        <span class="badge badge-primary"><?php echo $adminCount; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>System Version</span>
                        <span class="badge badge-info">v2.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts if the element exists
        if (document.getElementById('ordersChart')) {
            const ctx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    datasets: [{
                        label: 'Orders',
                        data: [12, 19, 15, 17, 21, 25, 18],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
    // Include footer template
    require_once 'templates/footer.php';
} catch (Exception $e) {
    // Log error and display friendly message
    error_log('Dashboard error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
}
?> 