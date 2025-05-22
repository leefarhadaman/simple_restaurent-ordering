<?php
/**
 * Restaurant Settings Page
 * Modern UI Implementation with Tailwind CSS (No Templates)
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Get form data
        $restaurantName = sanitizeInput($_POST['restaurant_name'] ?? '');
        $themeColor = sanitizeInput($_POST['theme_color'] ?? 'Light Red & White');
        
        // Validate restaurant name
        if (empty($restaurantName)) {
            throw new Exception('Restaurant name is required');
        }
        
        // Handle logo upload if provided
        $currentLogo = null;
        $stmt = $pdo->query("SELECT restaurant_logo FROM settings WHERE id = 1");
        if ($row = $stmt->fetch()) {
            $currentLogo = $row['restaurant_logo'];
        }
        
        $logoFilename = $currentLogo; // Default to current logo
        
        if (!empty($_FILES['logo']['name'])) {
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Delete old logo file if it exists
            if ($currentLogo && file_exists(UPLOAD_DIR . $currentLogo)) {
                if (!unlink(UPLOAD_DIR . $currentLogo)) {
                    error_log('Failed to delete old logo: ' . UPLOAD_DIR . $currentLogo);
                } else {
                    error_log('Successfully deleted old logo: ' . UPLOAD_DIR . $currentLogo);
                }
            }
            
            // Upload and compress the logo
            $logoFilename = handleImageUpload($_FILES['logo']);
            
            if (!$logoFilename) {
                throw new Exception('Failed to upload logo image');
            }
        }
        
        // Update settings
        $stmt = $pdo->prepare("
            UPDATE settings SET 
            restaurant_name = ?, 
            restaurant_logo = ?, 
            theme_color = ?
            WHERE id = 1
        ");
        
        $result = $stmt->execute([
            $restaurantName,
            $logoFilename,
            $themeColor
        ]);
        
        if ($result) {
            $message = 'Settings updated successfully';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to update settings');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current settings
$settings = getThemeSettings();
$themeColors = [
    'Light Red & White',
    'Deep Blue & Yellow',
    'Forest Green & Cream',
    'Black & Orange'
];

// Get admin info
$adminInfo = getAdminInfo($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                <img src="<?php echo $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] . '?v=' . time() : '../assets/images/default-food.jpg'; ?>" 
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
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
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
                        <h1 class="text-lg font-medium text-gray-800">Settings</h1>
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
        <div class="px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
            <!-- Mobile page title -->
            <div class="pt-2 pb-6 md:hidden">
                <h1 class="text-2xl font-semibold text-gray-900">Settings</h1>
    </div>

            <!-- Notification -->
    <?php if ($message): ?>
                <div class="mb-6">
                    <div class="rounded-lg p-4 <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mt-0.5"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo $message; ?></p>
                            </div>
                        </div>
                    </div>
        </div>
    <?php endif; ?>

            <!-- General Settings Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="font-semibold text-lg text-gray-800">General Settings</h2>
        </div>
        <div class="p-6">
                    <form method="post" enctype="multipart/form-data" class="space-y-6">
                        <!-- Restaurant Name -->
                <div>
                            <label for="restaurant_name" class="block text-sm font-medium text-gray-700 mb-1">Restaurant Name</label>
                    <input type="text" id="restaurant_name" name="restaurant_name" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                        value="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" required>
                </div>
                
                        <!-- Restaurant Logo -->
                <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Restaurant Logo</label>
                    <div class="flex flex-col md:flex-row items-start gap-6">
                                <div class="w-40 h-40 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border border-gray-200" id="logo-preview-container">
                            <?php if ($settings['restaurant_logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($settings['restaurant_logo']); ?>?v=<?php echo time(); ?>" alt="Restaurant Logo" class="max-w-full max-h-full object-contain" id="logo-preview-img">
                            <?php else: ?>
                                        <div class="text-gray-400 text-center">
                                    <i class="fas fa-image text-5xl mb-2"></i>
                                    <p class="text-sm">No logo uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                                <div class="space-y-3 flex-1">
                                    <label class="block w-full sm:w-auto px-4 py-2 border border-brand-300 bg-brand-50 text-brand-700 rounded-md cursor-pointer hover:bg-brand-100 transition-colors text-center">
                                <input type="file" id="logo" name="logo" accept="image/*" class="hidden">
                                <i class="fas fa-upload mr-2"></i> Select New Logo
                            </label>
                                    <p class="text-xs text-gray-500">Maximum file size: 500KB. Supported formats: JPG, PNG, GIF</p>
                        </div>
                    </div>
                </div>
                
                        <!-- Theme Color -->
                <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Theme Color</label>
                    <input type="hidden" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach ($themeColors as $theme): 
                            // Get theme color values for preview
                            $colors = getThemeColors($theme);
                        ?>
                                    <div class="theme-option cursor-pointer rounded-lg border-2 p-3 transition-all <?php echo ($settings['theme_color'] === $theme) ? 'border-brand-500 bg-brand-50 shadow-sm' : 'border-gray-200 hover:border-gray-300'; ?>" data-theme="<?php echo htmlspecialchars($theme); ?>">
                                <div class="flex mb-2">
                                    <div class="w-1/2 h-8 rounded-l" style="background-color: <?php echo htmlspecialchars($colors['primary']); ?>;"></div>
                                    <div class="w-1/2 h-8 rounded-r" style="background-color: <?php echo htmlspecialchars($colors['secondary']); ?>;"></div>
                                </div>
                                        <div class="text-center text-sm font-medium"><?php echo htmlspecialchars($theme); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-md transition-colors shadow-sm">
                        <i class="fas fa-save mr-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

            <!-- Password Change Card -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="font-semibold text-lg text-gray-800">Change Password</h2>
        </div>
        <div class="p-6">
                    <form method="post" action="change_password.php" class="space-y-6">
                        <!-- Current Password -->
                <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 pr-10"
                            required>
                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                        <!-- New Password -->
                <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 pr-10"
                            pattern=".{8,}" title="Password must be at least 8 characters long" required>
                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</p>
                </div>
                
                        <!-- Confirm Password -->
                <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 pr-10"
                            required>
                                <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-brand-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                        <!-- Submit Button -->
                <div>
                            <button type="submit" class="px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition-colors shadow-sm">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
        </div>
    </main>
    
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
            
            // Theme selection
    const themeOptions = document.querySelectorAll('.theme-option');
    const themeInput = document.getElementById('theme_color');
    
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
                    // Remove selected class from all options
                    themeOptions.forEach(o => {
                        o.classList.remove('border-brand-500', 'bg-brand-50', 'shadow-sm');
                        o.classList.add('border-gray-200');
                    });
                    
                    // Add selected class to clicked option
                    this.classList.remove('border-gray-200');
                    this.classList.add('border-brand-500', 'bg-brand-50', 'shadow-sm');
            
            // Update hidden input
                    themeInput.value = this.getAttribute('data-theme');
                });
            });
            
            // Password toggle
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const passwordField = this.previousElementSibling;
            const icon = this.querySelector('i');
                    
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
        });
    });
    
            // File input display
            const fileInput = document.getElementById('logo');
            
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const fileName = this.files[0].name;
                    const label = this.closest('label');
                    
                    if (fileName.length > 20) {
                        fileName = fileName.substring(0, 17) + '...';
                    }
                    
                    label.innerHTML = '<i class="fas fa-check mr-2"></i> ' + fileName;
                    
                    // Preview image
                    const previewContainer = document.getElementById('logo-preview-container');
            const reader = new FileReader();
                    
            reader.onload = function(e) {
                        // Clear the container
                        previewContainer.innerHTML = '';
                        
                        // Create image element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.id = 'logo-preview-img';
                        img.className = 'max-w-full max-h-full object-contain';
                        img.alt = 'Restaurant Logo Preview';
                        
                        // Add image to container
                        previewContainer.appendChild(img);
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // Show success message if passed from PHP
            <?php if (!empty($message) && $messageType === 'success'): ?>
                window.showToast('<?php echo $message; ?>', 'success');
                
                // Force a hard reload after 1 second to clear cache and show new logo
                setTimeout(function() {
                    window.location.href = window.location.href.split('?')[0] + '?reload=' + new Date().getTime();
                }, 1000);
            <?php endif; ?>
            
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
                closeBtn.className = 'text-gray-500 hover:text-gray-700 focus:outline-none';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.addEventListener('click', function() {
                    toast.remove();
                });
                
                toast.appendChild(icon);
                toast.appendChild(text);
                toast.appendChild(closeBtn);
                
                toastContainer.appendChild(toast);
                
                // Auto remove after 5 seconds
                setTimeout(function() {
                    toast.classList.add('toast-exit');
                    setTimeout(function() {
                        toast.remove();
                    }, 300);
                }, 5000);
            };
            
            // Show toast for any messages from PHP
            <?php if (!empty($message)): ?>
            showToast('<?php echo $message; ?>', '<?php echo $messageType; ?>');
            <?php endif; ?>
});
</script>
</body>
</html> 