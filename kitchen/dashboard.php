<?php
/**
 * Kitchen Dashboard
 * Shows pending orders for kitchen staff to prepare
 */
require_once '../includes/config.php';

// Kitchen staff authentication
function requireKitchenLogin() {
    if (!isset($_SESSION['kitchen_id']) || empty($_SESSION['kitchen_username'])) {
        header('Location: index.php');
        exit;
    }
}

// Require login
requireKitchenLogin();

// Get all pending orders
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'pending' OR o.status = 'preparing'
        GROUP BY o.id
        ORDER BY FIELD(o.status, 'pending', 'preparing'), o.created_at ASC
    ");
    $pendingOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Kitchen dashboard error: ' . $e->getMessage());
    $pendingOrders = [];
}

// Get settings
$settings = getThemeSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Dashboard - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                        kitchen: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
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
        
        /* Status badges */
        .status-badge {
            @apply inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-medium rounded-full;
        }
        .status-pending {
            @apply bg-amber-100 text-amber-800;
        }
        .status-preparing {
            @apply bg-blue-100 text-blue-800;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top navigation -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="<?php echo $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png'; ?>" 
                            alt="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" 
                            class="h-10 w-auto">
                        <h1 class="ml-3 text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($settings['restaurant_name']); ?> - Kitchen</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center text-sm">
                        <span class="mr-2 text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['kitchen_name'] ?? $_SESSION['kitchen_username']); ?></span>
                        <a href="logout.php" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Orders Queue</h2>
            <p class="mt-1 text-sm text-gray-600">Manage pending orders and prepare them for service</p>
        </div>
        
        <!-- Order cards grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($pendingOrders)): ?>
                <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fas fa-utensils text-3xl"></i>
                    </div>
                    <p class="text-gray-500">No pending orders at the moment.</p>
                    <p class="mt-2 text-gray-400 text-sm">New orders will appear here automatically.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingOrders as $order): 
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
                ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <span class="font-medium text-gray-800">Order #<?php echo $order['id']; ?></span>
                                <div class="text-sm text-gray-500">Table <?php echo $order['table_number']; ?></div>
                            </div>
                            <div>
                                <span class="status-badge <?php echo $order['status'] === 'pending' ? 'status-pending' : 'status-preparing'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-sm font-medium text-gray-600 mb-2">Order Items:</h3>
                                <ul class="space-y-1 text-sm">
                                    <?php foreach ($orderItems as $item): ?>
                                        <li class="flex justify-between">
                                            <span class="font-medium"><?php echo htmlspecialchars($item['name']); ?></span>
                                            <span class="text-gray-600">x<?php echo $item['quantity']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div class="border-t border-gray-100 pt-4 flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    <i class="far fa-clock mr-1"></i> <?php echo $orderTime->format('H:i:s'); ?>
                                </div>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="button" class="prepare-button inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-kitchen-600 hover:bg-kitchen-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-kitchen-500" data-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-utensils mr-1.5"></i> Start Preparing
                                    </button>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500 italic">In preparation</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    
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
            
            // Handle prepare button clicks
            const prepareButtons = document.querySelectorAll('.prepare-button');
            
            prepareButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-id');
                    const cardElement = this.closest('.bg-white');
                    
                    // Disable button to prevent multiple clicks
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Processing...';
                    
                    // Send AJAX request
                    fetch('update_order_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `order_id=${orderId}&status=preparing`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            showToast(`Order #${orderId} is now being prepared`, 'success');
                            
                            // Update the card status instead of reloading the page
                            const statusBadge = cardElement.querySelector('.status-badge');
                            statusBadge.classList.remove('status-pending');
                            statusBadge.classList.add('status-preparing');
                            statusBadge.textContent = 'Preparing';
                            
                            // Replace the button with "In preparation" text
                            const buttonContainer = this.parentElement;
                            buttonContainer.innerHTML = '<span class="text-sm text-gray-500 italic">In preparation</span>';
                        } else {
                            // Re-enable button on error
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-utensils mr-1.5"></i> Start Preparing';
                            
                            // Show error message
                            showToast(data.message || 'An error occurred', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Re-enable button
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-utensils mr-1.5"></i> Start Preparing';
                        
                        // Show error message
                        showToast('Failed to update order status', 'error');
                    });
                });
            });
            
            // Function to check for new orders without full page reload
            function checkForNewOrders() {
                fetch('check_new_orders.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const currentOrdersCount = document.querySelectorAll('.bg-white.rounded-lg').length - 
                                                      (document.querySelector('.col-span-full') ? 1 : 0);
                            
                            // If we have new orders, refresh the page
                            if (data.pendingCount > 0 && data.totalCount > currentOrdersCount) {
                                showToast('New orders received! Refreshing...', 'info');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            }
                            
                            // If any orders were marked as completed by admin, update the UI
                            if (data.orderUpdates && data.orderUpdates.length > 0) {
                                data.orderUpdates.forEach(update => {
                                    const orderCard = findOrderCard(update.id);
                                    if (orderCard && update.status === 'completed') {
                                        // Fade out and remove the completed order card
                                        orderCard.style.transition = 'opacity 1s ease, transform 1s ease';
                                        orderCard.style.opacity = '0';
                                        orderCard.style.transform = 'translateY(-20px)';
                                        setTimeout(() => {
                                            orderCard.remove();
                                            
                                            // If no more orders, show empty state
                                            const remainingCards = document.querySelectorAll('.bg-white.rounded-lg:not(.col-span-full)');
                                            if (remainingCards.length === 0) {
                                                const orderGrid = document.querySelector('.grid');
                                                orderGrid.innerHTML = `
                                                    <div class="col-span-full bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                                            <i class="fas fa-utensils text-3xl"></i>
                                                        </div>
                                                        <p class="text-gray-500">No pending orders at the moment.</p>
                                                        <p class="mt-2 text-gray-400 text-sm">New orders will appear here automatically.</p>
                                                    </div>
                                                `;
                                            }
                                        }, 1000);
                                    }
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for new orders:', error);
                    });
            }
            
            // Helper function to find an order card by ID
            function findOrderCard(orderId) {
                const cards = document.querySelectorAll('.bg-white.rounded-lg:not(.col-span-full)');
                for (let card of cards) {
                    const idText = card.querySelector('.font-medium.text-gray-800').textContent;
                    const cardOrderId = idText.replace('Order #', '');
                    if (cardOrderId == orderId) {
                        return card;
                    }
                }
                return null;
            }
            
            // Check for new orders every 5 seconds
            setInterval(checkForNewOrders, 5000);
            
            // Initial check after 3 seconds
            setTimeout(checkForNewOrders, 3000);
        });
    </script>
</body>
</html> 