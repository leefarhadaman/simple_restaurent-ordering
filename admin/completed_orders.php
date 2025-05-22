<?php
/**
 * Completed Orders Page
 * Enhanced implementation with improved filtering
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Default to today's date if not specified
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get completed orders within date range
try {
    $pdo = getDbConnection();
    
    // Get orders with improved query
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'completed'
        AND DATE(o.completed_at) BETWEEN ? AND ?
        GROUP BY o.id
        ORDER BY o.completed_at DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $completedOrders = $stmt->fetchAll();
    
    // Get total revenue for the period
    $stmt = $pdo->prepare("
        SELECT 
            SUM(total_amount) as total_revenue, 
            COUNT(*) as total_orders,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_processing_time
        FROM orders
        WHERE status = 'completed'
        AND DATE(completed_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $summary = $stmt->fetch();
    
    $totalRevenue = $summary['total_revenue'] ?? 0;
    $totalOrders = $summary['total_orders'] ?? 0;
    $avgProcessingTime = $summary['avg_processing_time'] ?? 0;
    
} catch (PDOException $e) {
    error_log('Completed orders error: ' . $e->getMessage());
    $completedOrders = [];
    $totalRevenue = 0;
    $totalOrders = 0;
    $avgProcessingTime = 0;
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
    <title>Completed Orders - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-chart-line w-5 h-5 mr-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="active_orders.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bell w-5 h-5 mr-2"></i>
                        <span>Active Orders</span>
                    </a>
                </li>
                <li>
                    <a href="completed_orders.php" 
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
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
                        <h1 class="text-lg font-medium text-gray-800">Completed Orders</h1>
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
        
        <!-- Page Content -->
        <div class="px-4 sm:px-6 py-4">
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold text-gray-800">Completed Orders</h1>
                    <div class="flex gap-2">
                        <a href="active_orders.php" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-md transition-colors">
            <i class="fas fa-clipboard-list"></i> View Active Orders
        </a>
                        <button id="refresh-btn" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
    </div>

    <!-- Date Range Filter -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-lg text-gray-800">Filter by Date</h2>
        </div>
        <div class="p-6">
            <form id="date-filter-form" method="get" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" id="start_date" name="start_date" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-500"
                        value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="flex-1 min-w-[200px]">
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" id="end_date" name="end_date" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-300 focus:border-brand-500"
                        value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                                <button type="button" id="today-btn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                                    Today
                                </button>
                                <button type="button" id="week-btn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                                    This Week
                                </button>
                                <button type="button" id="month-btn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-md transition-colors">
                                    This Month
                                </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-lg text-gray-800">Summary for Selected Period</h2>
        </div>
        <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="bg-brand-50 rounded-lg p-4 border border-brand-100">
                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo $totalOrders; ?></h3>
                                <p class="text-sm text-gray-600">Total Orders</p>
                </div>
                            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                                <h3 class="text-xl font-bold text-gray-800 mb-1"><?php echo formatPrice($totalRevenue); ?></h3>
                                <p class="text-sm text-gray-600">Total Revenue</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                                <h3 class="text-xl font-bold text-gray-800 mb-1">
                        <?php if ($totalOrders > 0): ?>
                            <?php echo formatPrice($totalRevenue / $totalOrders); ?>
                        <?php else: ?>
                                        ₹0.00
                        <?php endif; ?>
                    </h3>
                                <p class="text-sm text-gray-600">Average Order Value</p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                                <h3 class="text-xl font-bold text-gray-800 mb-1">
                                    <?php echo $avgProcessingTime > 0 ? round($avgProcessingTime) . ' min' : 'N/A'; ?>
                                </h3>
                                <p class="text-sm text-gray-600">Avg. Processing Time</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
                <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="font-semibold text-lg text-gray-800">Completed Orders</h2>
                        <?php if (!empty($completedOrders)): ?>
                        <a href="export_orders.php?start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>&status=completed" class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                            <i class="fas fa-file-export mr-1.5"></i> Export
                        </a>
                        <?php endif; ?>
        </div>
        <div class="p-6">
            <?php if (empty($completedOrders)): ?>
                <div class="text-center p-8">
                                <i class="fas fa-clipboard-check text-5xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No completed orders found for the selected date range.</p>
                                <button id="clear-filter" class="mt-4 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Clear Filters
                                </button>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Order #</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Table</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Items</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Order Time</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Completed Time</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Processing Time</th>
                                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                                    <tbody class="divide-y divide-gray-200">
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
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-700">Table <?php echo $order['table_number']; ?></td>
                                    <td class="px-4 py-3 text-sm">
                                                    <button class="inline-flex items-center justify-center px-2.5 py-1 rounded border border-brand-300 bg-brand-50 text-brand-700 text-xs font-medium hover:bg-brand-100 transition-colors view-items" type="button" data-items="<?php echo htmlspecialchars(json_encode($orderItems)); ?>">
                                                        <i class="fas fa-eye mr-1"></i> <?php echo $order['item_count']; ?> items
                                        </button>
                                    </td>
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo formatPrice($order['total_amount']); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $orderTime->format('H:i:s'); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $completedTime->format('H:i:s'); ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $processingTime; ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded" title="View Order">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                                    <button type="button" class="inline-flex items-center justify-center w-8 h-8 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded print-order" title="Print Order" data-id="<?php echo $order['id']; ?>">
                                                        <i class="fas fa-print"></i>
                                                    </button>
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
        </div>
    </main>

<!-- Order Items Modal -->
    <div id="itemsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800">Order Items</h3>
                <button type="button" id="closeItemsModal" class="text-gray-400 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <table class="w-full text-left">
                <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Item</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                            <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Price</th>
                    </tr>
                </thead>
                    <tbody id="itemsTableBody" class="divide-y divide-gray-200">
                    <!-- Will be filled by JavaScript -->
                </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm font-medium text-right text-gray-900">Total:</td>
                            <td id="items-total" class="px-4 py-3 text-sm font-medium text-gray-900"></td>
                        </tr>
                    </tfoot>
            </table>
        </div>
    </div>
</div>
    
    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal for order items
    const viewItemsButtons = document.querySelectorAll('.view-items');
    const itemsModal = document.getElementById('itemsModal');
    const itemsTableBody = document.getElementById('itemsTableBody');
        const itemsTotal = document.getElementById('items-total');
    const closeItemsModal = document.getElementById('closeItemsModal');
    
    // Open modal when clicking on items button
    viewItemsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemsData = JSON.parse(this.getAttribute('data-items'));
            
            // Clear existing content
            itemsTableBody.innerHTML = '';
                
                // Calculate total
                let total = 0;
            
            // Add items to table
            itemsData.forEach(item => {
                    const subtotal = item.price * item.quantity;
                    total += subtotal;
                    
                const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                        <td class="px-4 py-3 text-sm text-gray-700">${item.name}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 text-center">${item.quantity}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-700">₹${parseFloat(subtotal).toFixed(2)}</td>
                `;
                itemsTableBody.appendChild(tr);
            });
                
                // Update total
                itemsTotal.textContent = `₹${parseFloat(total).toFixed(2)}`;
            
            // Show modal
            itemsModal.classList.remove('hidden');
        });
    });
    
        // Close modal
    closeItemsModal.addEventListener('click', function() {
        itemsModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    itemsModal.addEventListener('click', function(e) {
        if (e.target === itemsModal) {
            itemsModal.classList.add('hidden');
        }
    });
    
        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');
        const mainContent = document.getElementById('main-content');
        
        menuToggle.addEventListener('click', function() {
            if (sidebar.classList.contains('sidebar-closed')) {
                // Open sidebar
                sidebar.classList.remove('sidebar-closed');
                mobileOverlay.classList.remove('hidden');
            } else {
                // Close sidebar
                sidebar.classList.add('sidebar-closed');
                mobileOverlay.classList.add('hidden');
            }
        });
        
        // Close sidebar when clicking on overlay
        mobileOverlay.addEventListener('click', function() {
            sidebar.classList.add('sidebar-closed');
            mobileOverlay.classList.add('hidden');
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
        
        // Date filter quick buttons
        document.getElementById('today-btn').addEventListener('click', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
            document.getElementById('end_date').value = today;
            document.getElementById('date-filter-form').submit();
        });
        
        document.getElementById('week-btn').addEventListener('click', function() {
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay());
            
            document.getElementById('start_date').value = startOfWeek.toISOString().split('T')[0];
            document.getElementById('end_date').value = today.toISOString().split('T')[0];
            document.getElementById('date-filter-form').submit();
        });
        
        document.getElementById('month-btn').addEventListener('click', function() {
            const today = new Date();
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            
            document.getElementById('start_date').value = startOfMonth.toISOString().split('T')[0];
            document.getElementById('end_date').value = today.toISOString().split('T')[0];
            document.getElementById('date-filter-form').submit();
        });
        
        // Clear filter button
        if (document.getElementById('clear-filter')) {
            document.getElementById('clear-filter').addEventListener('click', function() {
                window.location.href = 'completed_orders.php';
            });
        }
        
        // Refresh button
        document.getElementById('refresh-btn').addEventListener('click', function() {
            window.location.reload();
        });
        
        // Print order
        document.querySelectorAll('.print-order').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-id');
                window.open(`print_order.php?id=${orderId}`, '_blank', 'width=800,height=600');
            });
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
});
</script>
</body>
</html> 