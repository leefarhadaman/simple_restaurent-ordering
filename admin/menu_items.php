<?php
/**
 * Menu Items Management
 * Modern UI Implementation with Tailwind CSS (No Templates)
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize variables
$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Get form data
        $itemId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $categoryId = (int) $_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = (float) $_POST['price'];
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        $isVegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
        
        // Validate data
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            throw new Exception('Please fill in all required fields with valid data');
        }
        
        // Handle image upload if provided
        $imageFilename = null;
        
        if (!empty($_FILES['image']['name'])) {
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Upload and compress image
            $imageFilename = handleImageUpload($_FILES['image']);
            
            if (!$imageFilename) {
                throw new Exception('Failed to upload image');
            }
        }
        
        // Insert or update menu item
        if ($itemId > 0) {
            // Get existing image if no new one uploaded
            if (!$imageFilename) {
                $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();
                $imageFilename = $item['image'];
            }
            
            // Update existing item
            $stmt = $pdo->prepare("
                UPDATE menu_items SET 
                category_id = ?,
                name = ?,
                description = ?,
                price = ?,
                image = ?,
                is_available = ?,
                is_vegetarian = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $categoryId,
                $name,
                $description,
                $price,
                $imageFilename,
                $isAvailable,
                $isVegetarian,
                $itemId
            ]);
            
            if ($result) {
                $message = 'Menu item updated successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to update menu item');
            }
        } else {
            // Insert new item
            $stmt = $pdo->prepare("
                INSERT INTO menu_items (category_id, name, description, price, image, is_available, is_vegetarian)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $categoryId,
                $name,
                $description,
                $price,
                $imageFilename,
                $isAvailable,
                $isVegetarian
            ]);
            
            if ($result) {
                $message = 'Menu item added successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to add menu item');
            }
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Delete menu item
if (isset($_GET['delete'])) {
    $itemId = (int) $_GET['delete'];
    
    try {
        $pdo = getDbConnection();
        
        // Check if item exists
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            throw new Exception('Menu item not found');
        }
        
        // Start a transaction to ensure data integrity
        $pdo->beginTransaction();
        
        try {
            // First delete all related order items
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE menu_item_id = ?");
            $stmt->execute([$itemId]);
            
            // Then delete the menu item
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $result = $stmt->execute([$itemId]);
            
            if (!$result) {
                throw new Exception('Failed to delete menu item');
            }
            
            // Commit the transaction
            $pdo->commit();
            
            $message = 'Menu item and all its order references permanently deleted';
            $messageType = 'success';
            
        } catch (Exception $e) {
            // Roll back the transaction if something failed
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all menu items with category names
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT m.*, c.name as category_name
        FROM menu_items m
        JOIN categories c ON m.category_id = c.id
        ORDER BY c.display_order, m.name
    ");
    $menuItems = $stmt->fetchAll();
    
    // Get all categories for the form
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Menu items error: ' . $e->getMessage());
    $menuItems = [];
    $categories = [];
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
    <title>Menu Items - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
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
                        <h1 class="text-lg font-medium text-gray-800">Menu Items</h1>
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
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="px-4 sm:px-6 py-4">
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-800">Menu Items</h1>
        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors" data-toggle="modal" data-target="#addItemModal">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-md flex items-start gap-3">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mt-0.5"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <!-- Menu Items Table -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">All Menu Items</h2>
        </div>
        <div class="p-6">
            <?php if (empty($menuItems)): ?>
                <div class="text-center p-8">
                    <i class="fas fa-utensils text-5xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500">No menu items found. Add some items to get started.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Image</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Price</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Available</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Vegetarian</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($menuItems as $item): ?>
                                <tr class="hover:bg-violet-50 transition-colors">
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($item['image']): ?>
                                            <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-12 h-12 object-cover rounded-md">
                                        <?php else: ?>
                                            <img src="<?php echo DEFAULT_IMAGE; ?>" alt="Default" class="w-12 h-12 object-cover rounded-md">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo $item['name']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $item['category_name']; ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo formatPrice($item['price']); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer availability-toggle" 
                                                <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                                data-id="<?php echo $item['id']; ?>">
                                            <div class="relative w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-violet-600"></div>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer vegetarian-toggle" 
                                                <?php echo $item['is_vegetarian'] ? 'checked' : ''; ?> 
                                                data-id="<?php echo $item['id']; ?>">
                                            <div class="relative w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600"></div>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                            <button class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded edit-item" 
                                                data-id="<?php echo $item['id']; ?>"
                                                data-category="<?php echo $item['category_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                data-price="<?php echo $item['price']; ?>"
                                                data-available="<?php echo $item['is_available']; ?>"
                                                data-vegetarian="<?php echo $item['is_vegetarian']; ?>"
                                                data-image="<?php echo $item['image']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $item['id']; ?>" 
                                               onclick="return confirm('WARNING: This will permanently delete this menu item AND remove it from all customer order history. This action cannot be undone. Are you sure you want to proceed?')" 
                                               class="inline-flex items-center justify-center w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
        </div>
    </main>

    <!-- Add/Edit Menu Item Modal -->
<div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="addItemModal">
        <div class="max-w-lg w-full bg-white rounded-lg shadow-xl">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800" id="itemModalTitle">Add New Menu Item</h3>
            <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
                <form method="post" action="" id="itemForm" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="item_id" value="">
                
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                required>
                    </div>
                    
                    <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <select id="category_id" name="category_id" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                required>
                                <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3"
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                            <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Price <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500">$</span>
                                </div>
                                <input type="number" id="price" name="price" min="0.01" step="0.01"
                                    class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                    required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="image" class="block text-sm font-medium text-slate-700 mb-1">Image</label>
                            <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/gif"
                                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                    </div>
                </div>
                
                <div class="flex items-center">
                        <label for="is_available" class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="is_available" name="is_available" class="sr-only peer" checked>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                            <span class="ml-3 text-sm font-medium text-slate-700">Available for ordering</span>
                    </label>
                </div>
                
                <div class="flex items-center mt-4">
                    <label for="is_vegetarian" class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="is_vegetarian" name="is_vegetarian" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-700">Vegetarian</span>
                    </label>
                </div>
                
                <div class="flex gap-3 justify-end pt-4">
                        <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                            <span id="submitButtonText">Add Menu Item</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
        // Get all modals
        const modals = document.querySelectorAll('.modal');
        const modalDismissButtons = document.querySelectorAll('[data-dismiss="modal"]');
        const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
        
        // Show modal when trigger is clicked
        modalTriggers.forEach(trigger => {
            trigger.addEventListener('click', function() {
                const targetModal = document.querySelector(this.getAttribute('data-target'));
                if (targetModal) {
                    targetModal.classList.remove('hidden');
                }
            });
        });
        
        // Hide modals when dismiss buttons are clicked
        modalDismissButtons.forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.classList.add('hidden');
            }
        });
    });
    
        // Hide modals when clicking outside of modal content
        modals.forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
        
        // Edit menu item button functionality
        const editButtons = document.querySelectorAll('.edit-item');
        const itemForm = document.getElementById('itemForm');
        const itemModalTitle = document.getElementById('itemModalTitle');
        const submitButtonText = document.getElementById('submitButtonText');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Set form values
                document.getElementById('item_id').value = this.dataset.id;
                document.getElementById('name').value = this.dataset.name;
                document.getElementById('category_id').value = this.dataset.category;
                document.getElementById('description').value = this.dataset.description;
                document.getElementById('price').value = this.dataset.price;
                document.getElementById('is_available').checked = this.dataset.available === '1';
                document.getElementById('is_vegetarian').checked = this.dataset.vegetarian === '1';
                
                // Update modal title and button text
                itemModalTitle.textContent = 'Edit Menu Item';
                submitButtonText.textContent = 'Update Menu Item';
                
                // Show modal
                document.getElementById('addItemModal').classList.remove('hidden');
            });
        });
        
        // Reset form when adding a new item
    document.querySelector('[data-target="#addItemModal"]').addEventListener('click', function() {
        itemForm.reset();
            document.getElementById('item_id').value = '';
            itemModalTitle.textContent = 'Add New Menu Item';
            submitButtonText.textContent = 'Add Menu Item';
        });
        
        // Handle availability toggle
        document.querySelectorAll('.availability-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const itemId = this.dataset.id;
            const isAvailable = this.checked ? 1 : 0;
            
            // Send AJAX request to update availability
                fetch('ajax/update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                    body: `id=${itemId}&is_available=${isAvailable}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                        // Revert toggle if update failed
                    this.checked = !this.checked;
                    alert('Failed to update availability: ' + data.message);
                }
            })
            .catch(error => {
                    // Revert toggle on error
                this.checked = !this.checked;
                console.error('Error:', error);
                    alert('An error occurred while updating availability.');
                });
            });
        });
        
        // Handle vegetarian toggle
        document.querySelectorAll('.vegetarian-toggle').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const itemId = this.dataset.id;
                const isVegetarian = this.checked ? 1 : 0;
                
                // Send AJAX request to update vegetarian status
                fetch('ajax/update_vegetarian.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&is_vegetarian=${isVegetarian}`
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Revert toggle if update failed
                        this.checked = !this.checked;
                        alert('Failed to update vegetarian status: ' + data.message);
                    }
                })
                .catch(error => {
                    // Revert toggle on error
                    this.checked = !this.checked;
                    console.error('Error:', error);
                    alert('An error occurred while updating vegetarian status.');
                });
            });
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
        
        userDropdownButton.addEventListener('click', function() {
            // Toggle user dropdown menu (you can extend this)
            alert('User dropdown clicked');
    });
});
</script>
</body>
</html> 