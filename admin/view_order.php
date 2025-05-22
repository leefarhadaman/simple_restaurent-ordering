<?php
/**
 * View Order Details
 * Detailed view of a specific order
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: active_orders.php');
    exit;
}

$orderId = (int) $_GET['id'];

// Get order details
try {
    $pdo = getDbConnection();
    
    // Get the order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        // Order not found
        header('Location: active_orders.php');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, m.name, m.description 
        FROM order_items oi
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('View order error: ' . $e->getMessage());
    header('Location: active_orders.php');
    exit;
}

// Format times
$orderTime = new DateTime($order['created_at']);
$completedTime = $order['completed_at'] ? new DateTime($order['completed_at']) : null;

// Get settings
$settings = getThemeSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $orderId; ?> - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <img src="<?php echo $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png'; ?>" 
                             alt="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" 
                             class="h-8 w-auto">
                        <span class="ml-3 text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($settings['restaurant_name']); ?></span>
                    </a>
                    <nav class="hidden md:ml-10 md:flex space-x-8">
                        <a href="index.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Dashboard</a>
                        <a href="active_orders.php" class="text-brand-600 hover:text-brand-700 px-3 py-2 text-sm font-medium">Orders</a>
                        <a href="menu_items.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Menu</a>
                        <a href="settings.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm font-medium">Settings</a>
                    </nav>
                </div>
                <div class="flex items-center">
                    <div class="hidden md:ml-4 md:flex-shrink-0 md:flex md:items-center">
                        <a href="logout.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumbs -->
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
                <a href="index.php" class="hover:text-gray-700">Dashboard</a>
                <span><i class="fas fa-chevron-right text-xs"></i></span>
                <a href="active_orders.php" class="hover:text-gray-700">Orders</a>
                <span><i class="fas fa-chevron-right text-xs"></i></span>
                <span class="text-gray-700 font-medium">Order #<?php echo $orderId; ?></span>
            </div>
            
            <!-- Order details card -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <!-- Header -->
                <div class="border-b border-gray-200 px-6 py-4 flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Order #<?php echo $orderId; ?></h1>
                        <p class="text-sm text-gray-500">
                            Table <?php echo $order['table_number']; ?> • 
                            <?php echo $orderTime->format('d M Y, H:i:s'); ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if ($order['status'] === 'pending'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                <i class="fas fa-clock mr-1.5"></i> Pending
                            </span>
                        <?php elseif ($order['status'] === 'preparing'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-utensils mr-1.5"></i> Preparing
                            </span>
                            <button type="button" class="complete-order inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none" data-id="<?php echo $orderId; ?>">
                                <i class="fas fa-check mr-1.5"></i> Mark as Completed
                            </button>
                        <?php elseif ($order['status'] === 'completed'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1.5"></i> Completed
                            </span>
                        <?php endif; ?>
                        <a href="active_orders.php" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                            <i class="fas fa-arrow-left mr-1.5"></i> Back to Orders
                        </a>
                    </div>
                </div>
                
                <!-- Order items -->
                <div class="px-6 py-5">
                    <h2 class="text-base font-medium text-gray-900 mb-4">Order Items</h2>
                    <div class="flow-root">
                        <div class="-mx-4 -my-2 overflow-x-auto">
                            <div class="inline-block min-w-full py-2 align-middle px-4">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Item</th>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 hidden sm:table-cell">Description</th>
                                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Quantity</th>
                                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </td>
                                                <td class="px-3 py-4 text-sm text-gray-500 hidden sm:table-cell">
                                                    <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                                                </td>
                                                <td class="px-3 py-4 text-sm text-gray-500 text-center">
                                                    <?php echo $item['quantity']; ?>
                                                </td>
                                                <td class="px-3 py-4 text-sm text-gray-500 text-right">
                                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th scope="row" colspan="3" class="hidden pl-4 pr-3 pt-4 text-right text-sm font-semibold text-gray-900 sm:table-cell">Total</th>
                                            <th scope="row" class="pl-4 pr-3 pt-4 text-left text-sm font-semibold text-gray-900 sm:hidden">Total</th>
                                            <td class="pl-3 pr-4 pt-4 text-right text-sm font-semibold text-gray-900">
                                                <?php echo formatPrice($order['total_amount']); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order timeline -->
                <div class="px-6 py-5 border-t border-gray-200">
                    <h2 class="text-base font-medium text-gray-900 mb-4">Order Timeline</h2>
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <li>
                                <div class="relative pb-8">
                                    <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex items-start space-x-3">
                                        <div class="relative">
                                            <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                <i class="fas fa-receipt text-white"></i>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">Order Placed</span>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    <?php echo $orderTime->format('d M Y, H:i:s'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            
                            <?php if ($order['status'] === 'preparing' || $order['status'] === 'completed'): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if ($order['status'] === 'completed'): ?>
                                    <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="relative flex items-start space-x-3">
                                        <div class="relative">
                                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                <i class="fas fa-utensils text-white"></i>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">Preparing</span>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    <?php echo $order['updated_at'] ? (new DateTime($order['updated_at']))->format('d M Y, H:i:s') : 'Time not recorded'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($order['status'] === 'completed'): ?>
                            <li>
                                <div class="relative">
                                    <div class="relative flex items-start space-x-3">
                                        <div class="relative">
                                            <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                <i class="fas fa-check text-white"></i>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">Completed</span>
                                                </div>
                                                <p class="mt-0.5 text-sm text-gray-500">
                                                    <?php echo $completedTime ? $completedTime->format('d M Y, H:i:s') : 'Time not recorded'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Handle complete order button if it exists
            const completeOrderButton = document.querySelector('.complete-order');
            if (completeOrderButton) {
                completeOrderButton.addEventListener('click', function() {
                    if (confirm('Mark this order as completed?')) {
                        const orderId = this.getAttribute('data-id');
                        
                        // Disable button to prevent multiple clicks
                        this.disabled = true;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Processing...';
                        
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
                                
                                // Redirect to active orders after a brief delay
                                setTimeout(() => {
                                    window.location.href = 'active_orders.php';
                                }, 2000);
                            } else {
                                // Re-enable button on error
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-check mr-1.5"></i> Mark as Completed';
                                
                                // Show error message
                                showToast(data.message || 'An error occurred', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Re-enable button
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-check mr-1.5"></i> Mark as Completed';
                            
                            // Show error message
                            showToast('Failed to update order status', 'error');
                        });
                    }
                });
            }
        });
    </script>
</body>
</html> 