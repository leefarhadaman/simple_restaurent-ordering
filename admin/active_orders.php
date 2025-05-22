<?php
/**
 * Active Orders Page
 * Modern UI Implementation with Tailwind CSS (No Templates)
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
        WHERE o.status = 'pending' OR o.status = 'preparing'
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

// Get current user info
$adminInfo = getAdminInfo($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Orders - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
                        <i class="fas fa-bell w-5 h-5 mr-2"></i>
                        <span>Active Orders</span>
                        <?php if (count($activeOrders) > 0): ?>
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-red-100 text-red-500"><?php echo count($activeOrders); ?></span>
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
                        <h1 class="text-lg font-medium text-gray-800">Active Orders</h1>
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
                <h1 class="text-2xl font-semibold text-gray-900">Active Orders</h1>
            </div>

    <!-- Page Header -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold">Current Orders</h2>
                    <span id="active-order-count" class="inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-medium bg-brand-100 text-brand-800 rounded-full">
                <?php echo count($activeOrders); ?>
            </span>
        </div>
                <a href="completed_orders.php" class="inline-flex items-center gap-2 px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition-colors text-sm">
            <i class="fas fa-history"></i> View Completed Orders
        </a>
    </div>

            <!-- Orders Table Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">Orders Waiting to be Completed</h2>
        </div>
                <div class="p-4 sm:p-6">
            <?php if (empty($activeOrders)): ?>
                <div class="text-center p-6 sm:p-10">
                            <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fas fa-clipboard-list text-3xl sm:text-4xl"></i>
                    </div>
                            <p class="text-gray-500 text-base sm:text-lg">No active orders at the moment.</p>
                </div>
            <?php else: ?>
                        <div class="overflow-x-auto -mx-4 sm:-mx-6">
                    <table class="w-full text-left">
                        <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Order #</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Table</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Items</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hidden sm:table-cell">Total</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hidden md:table-cell">Order Time</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                                <tbody class="divide-y divide-gray-200">
                            <?php foreach ($activeOrders as $order): 
                                $orderTime = new DateTime($order['created_at']);
                                
                                // Get items for this order
                                $stmt = $pdo->prepare("
                                    SELECT oi.*, m.name 
                                    FROM order_items oi
                                    JOIN menu_items m ON oi.menu_item_id = m.id
                                    WHERE oi.order_id = ?
                                ");
                                $stmt->execute([$order['id']]);
                                $orderItems = $stmt->fetchAll();

                                        // Get status class
                                        $statusClass = $order['status'] === 'preparing' ? 'bg-blue-50' : '';
                            ?>
                                        <tr class="hover:bg-gray-50 transition-colors <?php echo $statusClass; ?>">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">Table <?php echo $order['table_number']; ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                        <button 
                                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded border border-brand-200 text-brand-700 hover:bg-brand-50 view-items" 
                                            type="button" 
                                            data-items="<?php echo htmlspecialchars(json_encode($orderItems)); ?>"
                                        >
                                                    <i class="fas fa-eye mr-1.5"></i> <?php echo $order['item_count']; ?> items
                                        </button>
                                    </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 hidden sm:table-cell">
                                        <?php echo formatPrice($order['total_amount']); ?>
                                    </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 hidden md:table-cell">
                                        <?php echo $orderTime->format('H:i:s'); ?>
                                    </td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php if ($order['status'] === 'preparing'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-medium">
                                                        <i class="fas fa-utensils mr-1"></i> Preparing
                                                    </span>
                                                <?php elseif ($order['status'] === 'pending'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-medium">
                                                        <i class="fas fa-clock mr-1"></i> Pending
                                        </span>
                                                <?php endif; ?>
                                    </td>
                                            <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                                    <?php if ($order['status'] === 'preparing'): ?>
                                                        <button type="button" class="complete-order inline-flex items-center justify-center w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded" title="Mark as Completed" data-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                                    <?php endif; ?>
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
    </main>

<!-- Order Items Modal Template -->
<div id="orderItemsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Order Items</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none close-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
            <table class="w-full">
                <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Item</th>
                            <th class="text-center pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 w-16">Qty</th>
                            <th class="text-right pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 w-20">Price</th>
                    </tr>
                </thead>
                <tbody id="orderItemsList">
                    <!-- Will be filled by JavaScript -->
                </tbody>
                <tfoot>
                    <tr>
                            <td colspan="3" class="pt-3 text-right text-sm font-medium text-gray-700">
                            <span id="orderItemsTotal"></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
            <div class="p-4 border-t border-gray-200 flex justify-end">
                <button type="button" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-md transition-colors close-modal">
                Close
            </button>
        </div>
    </div>
</div>

    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3"></div>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                    row.className = 'border-b border-gray-100';
            row.innerHTML = `
                        <td class="py-2 text-sm text-gray-700">${item.name}</td>
                        <td class="py-2 text-sm text-gray-700 text-center">${item.quantity}</td>
                        <td class="py-2 text-sm text-gray-700 text-right">${formatPrice(subtotal)}</td>
            `;
            orderItemsList.appendChild(row);
        });
        
        orderItemsTotal.textContent = `Total: ${formatPrice(total)}`;
    }
    
            // Format price
    function formatPrice(price) {
                return '₹' + parseFloat(price).toFixed(2);
    }
    
            // Handle order completion
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
                                
                                // Remove order from active orders list with animation
                                const orderRow = this.closest('tr');
                                orderRow.style.backgroundColor = '#f0fff4'; // Light green background
                                orderRow.style.transition = 'opacity 1s ease';
                                
                                setTimeout(() => {
                                    orderRow.style.opacity = '0';
                                    setTimeout(() => {
                                        orderRow.remove();
                
                                        // Update order count
                const countElement = document.getElementById('active-order-count');
                                        const currentCount = parseInt(countElement.textContent.trim());
                countElement.textContent = currentCount - 1;
                
                                        // If no more orders, refresh page
                                        if (currentCount - 1 <= 0) {
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 1000);
                                        }
                                    }, 1000);
                                }, 500);
            } else {
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

// Custom selector function to find elements containing specific text
function querySelectorContains(baseSelector, text) {
    // Get all elements matching the base selector
    const elements = document.querySelectorAll(baseSelector);
    
    // Filter to only those containing the text
    return Array.from(elements).filter(el => 
        el.textContent.includes(text)
    );
}

// Define the complete order handler function
function completeOrderHandler() {
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
                
                // Remove order from active orders list with animation
                const orderRow = this.closest('tr');
                orderRow.style.backgroundColor = '#f0fff4'; // Light green background
                orderRow.style.transition = 'opacity 1s ease';
                
                setTimeout(() => {
                    orderRow.style.opacity = '0';
                    setTimeout(() => {
                        orderRow.remove();
        
                        // Update order count
                        const countElement = document.getElementById('active-order-count');
                        const currentCount = parseInt(countElement.textContent.trim());
                        countElement.textContent = currentCount - 1;
        
                        // If no more orders, refresh page
                        if (currentCount - 1 <= 0) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    }, 1000);
                }, 500);
            } else {
                showToast(data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to update order status', 'error');
        });
    }
}

// Fix the checkOrderStatusUpdates function to use the custom selector
function checkOrderStatusUpdates() {
    // Get all orders currently displayed in the table
    const orderRows = document.querySelectorAll('tbody tr');
    const orderIds = Array.from(orderRows).map(row => {
        const orderIdCell = row.querySelector('td:first-child');
        return orderIdCell ? orderIdCell.textContent.replace('#', '') : null;
    }).filter(id => id !== null);
    
    if (orderIds.length === 0) return;
    
    // Send AJAX request to get current order statuses
    fetch('get_order_statuses.php?ids=' + orderIds.join(','))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.orders) {
                // Loop through returned orders and update UI accordingly
                data.orders.forEach(order => {
                    // Find the row containing this order ID
                    const matchingCells = querySelectorContains('tbody tr td:first-child', `#${order.id}`);
                    if (!matchingCells.length) return;
                    
                    const orderRow = matchingCells[0].closest('tr');
                    if (!orderRow) return;
                    
                    const statusCell = orderRow.querySelector('td:nth-child(6)');
                    const actionsCell = orderRow.querySelector('td:nth-child(7)');
                    
                    // Update status badge if changed
                    if (order.status === 'preparing' && 
                        !statusCell.innerHTML.includes('Preparing')) {
                        
                        // Update status badge
                        statusCell.innerHTML = `
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-medium">
                                <i class="fas fa-utensils mr-1"></i> Preparing
                            </span>
                        `;
                        
                        // Update row styling
                        orderRow.classList.add('bg-blue-50');
                        
                        // Update action buttons - add complete button if not present
                        if (!actionsCell.innerHTML.includes('complete-order')) {
                            const actionButtons = actionsCell.querySelector('.flex');
                            actionButtons.innerHTML += `
                                <button type="button" class="complete-order inline-flex items-center justify-center w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded" title="Mark as Completed" data-id="${order.id}">
                                    <i class="fas fa-check"></i>
                                </button>
                            `;
                            
                            // Add event listener to the new complete button
                            const newCompleteButton = actionsCell.querySelector('.complete-order');
                            if (newCompleteButton) {
                                newCompleteButton.addEventListener('click', completeOrderHandler);
                            }
                            
                            // Show toast notification
                            showToast(`Order #${order.id} is now being prepared by kitchen staff`, 'info');
                        }
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error checking order status updates:', error);
        });
}

// Check for status updates every 15 seconds
setInterval(checkOrderStatusUpdates, 15000);

// Initial check when page loads
setTimeout(checkOrderStatusUpdates, 1000);
});
</script>
</body>
</html> 