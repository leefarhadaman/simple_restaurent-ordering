<?php
/**
 * Admin Dashboard
 * Modern UI Implementation with Tailwind CSS (No Templates)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                    },
                    screens: {
                        'xs': '475px',
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f9fafb;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Sidebar animation */
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        /* When sidebar is closed */
        .sidebar-closed {
            transform: translateX(-100%);
        }
        
        /* Main content transition */
        .main-content {
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Responsive main content when sidebar closed */
        @media (min-width: 1024px) {
            .sidebar-open {
                margin-left: 280px;
            }
        }
        
        /* Toast animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .toast-anim {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden lg:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-0 z-40 h-screen w-[280px] bg-white border-r border-gray-200 pt-4 pb-10 overflow-y-auto">
        <!-- Logo -->
        <div class="px-6 mb-8">
            <a href="index.php" class="flex items-center">
                <img src="<?php echo $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png'; ?>" 
                     alt="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" 
                     class="h-10 w-auto mr-3">
                <span class="text-xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($settings['restaurant_name']); ?></span>
            </a>
        </div>
        
        <!-- Navigation -->
        <nav class="px-4">
            <span class="text-xs font-semibold text-gray-400 px-2 uppercase tracking-wider">Main</span>
            
            <ul class="mt-3 space-y-1">
                <li>
                    <a href="index.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
                        <i class="fas fa-chart-line w-5 h-5 mr-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="active_orders.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bell w-5 h-5 mr-2"></i>
                        <span>Active Orders</span>
                        <?php if ($activeOrders > 0): ?>
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-red-100 text-red-500"><?php echo $activeOrders; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="completed_orders.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-check-circle w-5 h-5 mr-2"></i>
                        <span>Completed Orders</span>
                    </a>
                </li>
            </ul>
            
            <span class="mt-8 block text-xs font-semibold text-gray-400 px-2 uppercase tracking-wider">Menu Management</span>
            
            <ul class="mt-3 space-y-1">
                <li>
                    <a href="categories.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-tag w-5 h-5 mr-2"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="menu_items.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-utensils w-5 h-5 mr-2"></i>
                        <span>Menu Items</span>
                    </a>
                </li>
            </ul>
            
            <span class="mt-8 block text-xs font-semibold text-gray-400 px-2 uppercase tracking-wider">System</span>
            
            <ul class="mt-3 space-y-1">
                <li>
                    <a href="manage_users.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-users w-5 h-5 mr-2"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-cog w-5 h-5 mr-2"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt w-5 h-5 mr-2"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main id="main-content" class="min-h-screen pt-16 pb-10 sidebar-open">
        <!-- Top navbar -->
        <header class="fixed top-0 right-0 left-0 lg:left-[280px] z-30 bg-white border-b border-gray-200 h-16">
            <div class="flex items-center justify-between h-full px-4 sm:px-6">
                <!-- Left side - Toggle button & breadcrumb -->
                <div class="flex items-center">
                    <button id="menu-toggle" type="button" class="lg:hidden text-gray-500 hover:text-gray-600 p-2">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="ml-3 hidden sm:block">
                        <h1 class="text-lg font-medium text-gray-800">Dashboard</h1>
                    </div>
                </div>
                
                <!-- Right side - User dropdown -->
                <div class="relative">
                    <button id="user-dropdown-button" type="button" class="flex items-center space-x-3 focus:outline-none">
                        <div class="flex flex-col items-end">
                            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                            <span class="text-xs text-gray-500">Administrator</span>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-brand-600 flex items-center justify-center text-white">
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </button>
                    
                    <!-- User dropdown menu -->
                    <div id="user-dropdown" class="absolute right-0 mt-2 w-48 rounded-md bg-white shadow-lg border border-gray-200 py-1 z-50 hidden">
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog w-4 h-4 mr-2"></i> Settings
                        </a>
                        <a href="change_password.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-key w-4 h-4 mr-2"></i> Change Password
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt w-4 h-4 mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page content -->
        <div class="px-4 sm:px-6 lg:px-8">
            <!-- Mobile page title -->
            <div class="pt-2 pb-6 md:hidden">
                <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    </div>

    <!-- Welcome Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                            <i class="fas fa-user-circle text-xl"></i>
                </div>
                <div>
                            <h2 class="text-xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
                            <p class="text-gray-500">
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
                        <div class="ml-auto hidden sm:block">
                            <a href="active_orders.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none">
                                <i class="fas fa-clipboard-list mr-2"></i> View Active Orders
                            </a>
            </div>
        </div>
            </div>
        </div>
        
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
                <!-- Active Orders Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full p-3 bg-red-100 text-red-600">
                            <i class="fas fa-clipboard-list text-lg"></i>
            </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Active Orders</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $activeOrders; ?></h3>
            </div>
        </div>
    </div>

                <!-- Menu Items Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full p-3 bg-green-100 text-green-600">
                            <i class="fas fa-utensils text-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Menu Items</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $menuItems; ?></h3>
                </div>
            </div>
        </div>

                <!-- Categories Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full p-3 bg-amber-100 text-amber-600">
                            <i class="fas fa-tag text-lg"></i>
                </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Categories</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo $categories; ?></h3>
                        </div>
                        </div>
                    </div>
                    
                <!-- Revenue Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-full p-3 bg-indigo-100 text-indigo-600">
                            <i class="fas fa-dollar-sign text-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Today's Revenue</p>
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo formatPrice($todayRevenue); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart Section -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800">Sales Overview</h2>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 text-xs font-medium rounded bg-brand-100 text-brand-700 hover:bg-brand-200 transition-colors">Weekly</button>
                            <button class="px-3 py-1 text-xs font-medium rounded bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">Monthly</button>
                        </div>
                </div>
                    <div class="p-4">
                        <div class="h-64 lg:h-80">
                            <canvas id="salesChart"></canvas>
                        </div>
                        </div>
                    </div>
                    
                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800">Recent Orders</h2>
                        <a href="active_orders.php" class="text-xs font-medium text-brand-600 hover:text-brand-700">View All</a>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($recentOrders)): ?>
                            <div class="p-4 text-center">
                                <div class="text-gray-400 mb-2">
                                    <i class="fas fa-clipboard-list text-3xl"></i>
                                </div>
                                <p class="text-gray-500 text-sm">No active orders</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="px-4 py-3 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-sm font-medium">Order #<?php echo $order['id']; ?></span>
                                            <p class="text-xs text-gray-500">Table <?php echo $order['table_number']; ?></p>
                                        </div>
                                        <span class="text-sm font-semibold"><?php echo formatPrice($order['total_amount']); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3"></div>
    
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Sales Chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Example data - would be replaced with real data in production
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'This Week',
                        data: [12, 19, 15, 8, 22, 14, 11],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.05)',
                        tension: 0.3,
                        fill: true,
                    borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#0ea5e9',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }, {
                        label: 'Last Week',
                        data: [8, 12, 10, 9, 15, 10, 7],
                        borderColor: '#cbd5e1',
                        backgroundColor: 'rgba(203, 213, 225, 0.05)',
                    tension: 0.3,
                        fill: true,
                    borderWidth: 2,
                        borderDash: [5, 5],
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#cbd5e1',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                            labels: {
                                boxWidth: 10,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                },
                tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            padding: 12,
                            bodyFont: {
                                size: 12
                            },
                            titleFont: {
                                size: 13,
                                weight: 'bold'
                            },
                            cornerRadius: 6,
                            boxPadding: 6
                }
            },
            scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.5)'
                            }
                        }
                    }
                }
            });
            
            // Toggle mobile sidebar
            const menuToggle = document.getElementById('menu-toggle');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const sidebar = document.getElementById('sidebar');
            
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-closed');
                mobileOverlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            });
            
            mobileOverlay.addEventListener('click', function() {
                sidebar.classList.add('sidebar-closed');
                mobileOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
            
            // User dropdown
            const userDropdownButton = document.getElementById('user-dropdown-button');
            const userDropdown = document.getElementById('user-dropdown');
            
            userDropdownButton.addEventListener('click', function() {
                userDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userDropdownButton.contains(event.target) && !userDropdown.contains(event.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
            
            // Toast notification function
            window.showToast = function(message, type = 'success') {
                const toastContainer = document.getElementById('toast-container');
                
                const toast = document.createElement('div');
                toast.className = `px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 toast-anim ${
                    type === 'success' ? 'bg-green-50 text-green-800 border-l-4 border-green-500' : 
                    type === 'error' ? 'bg-red-50 text-red-800 border-l-4 border-red-500' : 
                    'bg-blue-50 text-blue-800 border-l-4 border-blue-500'
                }`;
                
                const icon = document.createElement('span');
                icon.className = 'text-lg';
                icon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle"></i>' : 
                                type === 'error' ? '<i class="fas fa-times-circle"></i>' : 
                                '<i class="fas fa-info-circle"></i>';
                
                const text = document.createElement('span');
                text.className = 'flex-1 text-sm';
                text.textContent = message;
                
                const closeBtn = document.createElement('button');
                closeBtn.className = 'text-gray-400 hover:text-gray-600';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.onclick = function() {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                };
                
                toast.appendChild(icon);
                toast.appendChild(text);
                toast.appendChild(closeBtn);
                
                toastContainer.appendChild(toast);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    if (toast) {
                        toast.style.opacity = '0';
                        setTimeout(() => toast.remove(), 300);
                    }
                }, 5000);
            };
            
            // Handle complete order buttons from recent orders section
            const completeOrderButtons = document.querySelectorAll('.complete-order');
            
            completeOrderButtons.forEach(button => {
        button.addEventListener('click', function() {
                    if (confirm('Mark this order as completed?')) {
            const orderId = this.getAttribute('data-id');
                        
                        // Send AJAX request
                        fetch('update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                            body: `order_id=${orderId}&status=completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                                // Show success message
                                showToast(`Order #${orderId} marked as completed`, 'success');
                                
                                // Remove order from table or update UI
                                const orderElement = this.closest('tr, .order-card');
                                if (orderElement) {
                                    orderElement.style.backgroundColor = '#f0fff4'; // Light green background
                                    orderElement.style.transition = 'opacity 1s ease';
                                    
                                    setTimeout(() => {
                                        orderElement.style.opacity = '0';
                                        setTimeout(() => {
                                            orderElement.remove();
                                        }, 1000);
                                    }, 500);
                                }
                    } else {
                                // Show error message
                                showToast(data.message || 'An error occurred', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                            showToast('Failed to update order status', 'error');
                });
            }
        });
    });
            
            // Example toast (can be commented out in production)
            // showToast('Welcome to your dashboard!', 'success');
});
</script>
</body>
</html>
<?php
} catch (Exception $e) {
    // Display error message
    echo '<div style="color: red; padding: 20px; background-color: #ffe6e6; border: 1px solid #ff0000; margin: 20px;">';
    echo '<h2>Error:</h2>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '</div>';
}
?> 