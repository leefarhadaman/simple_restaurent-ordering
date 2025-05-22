<?php
/**
 * Admin User Management
 * Modern UI Implementation with Tailwind CSS (No Templates)
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Process form submissions
$message = '';
$messageType = '';

// Add new admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $message = 'Username and password are required';
        $messageType = 'danger';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long';
        $messageType = 'danger';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $message = 'Username already exists';
                $messageType = 'danger';
            } else {
                // Add new admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                
                if ($stmt->execute([$username, $hashedPassword])) {
                    $message = 'Admin user added successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add admin user';
                    $messageType = 'danger';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Delete admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = (int)$_POST['user_id'];
    $currentUserId = (int)$_SESSION['admin_id'];
    
    if ($userId === $currentUserId) {
        $message = 'You cannot delete your own account';
        $messageType = 'danger';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            
            if ($stmt->execute([$userId])) {
                $message = 'Admin user deleted successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete admin user';
                $messageType = 'danger';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get all admin users
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY username");
    $adminUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Failed to retrieve admin users: ' . $e->getMessage();
    $messageType = 'danger';
    $adminUsers = [];
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
    <title>Manage Users - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
                       class="flex items-center px-3 py-2.5 text-sm font-medium text-white rounded-lg bg-brand-600 hover:bg-brand-700">
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
                        <h1 class="text-lg font-medium text-gray-800">Manage Users</h1>
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
                <!-- Page Header -->
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold text-slate-800">Manage Admin Users</h1>
                    <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors" data-toggle="modal" data-target="#addUserModal">
                        <i class="fas fa-plus"></i> Add New Admin
                    </button>
                </div>

                <!-- Alert Messages -->
                <?php if ($message): ?>
                    <div class="<?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-md flex items-start gap-3">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mt-0.5"></i>
                        <span><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        
                <!-- Admin Users List -->
                <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-lg text-slate-800">Admin Users</h2>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Username</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Created</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php if (empty($adminUsers)): ?>
                                        <tr>
                                            <td colspan="3" class="px-4 py-5 text-center text-slate-500">No admin users found</td>
                                        </tr>
                                    <?php else: ?>
                    <?php foreach ($adminUsers as $user): ?>
                                            <tr class="hover:bg-violet-50 transition-colors">
                                                <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td class="px-4 py-3 text-sm text-slate-700">
                                <?php 
                                $createdDate = new DateTime($user['created_at']);
                                echo $createdDate->format('M d, Y'); 
                                ?>
                            </td>
                                                <td class="px-4 py-3 text-sm">
                                <?php if ((int)$user['id'] !== (int)$_SESSION['admin_id']): ?>
                                                        <form method="post" action="" class="inline-block delete-user-form">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                            <button type="button" class="inline-flex items-center justify-center w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded delete-user-btn"
                                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                                        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-brand-100 text-brand-800 text-xs font-medium">
                                                            Current User
                                                        </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
            </div>
        </div>
    </main>

<!-- Add User Modal -->
    <div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="addUserModal">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800">Add New Admin User</h3>
                <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form method="post" action="" id="addUserForm" class="space-y-4">
                    <input type="hidden" name="action" value="add_user">
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-slate-400"></i>
                            </div>
                            <input type="text" id="username" name="username" 
                                class="w-full pl-10 py-2 px-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                required>
                        </div>
                        <div class="hidden text-sm text-red-600 mt-1">Username is required</div>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="w-full pl-10 py-2 px-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                required minlength="8">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Password must be at least 8 characters long</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                class="w-full pl-10 py-2 px-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                                required>
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="hidden text-sm text-red-600 mt-1">Passwords do not match</div>
                    </div>
                    
                    <div class="flex gap-3 justify-end pt-4">
                        <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                            Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="deleteConfirmModal">
        <div class="max-w-md w-full bg-white rounded-lg shadow-xl">
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-slate-800">Confirm Delete</h3>
                <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-slate-700 mb-6">Are you sure you want to delete the user <span id="delete-username" class="font-semibold"></span>? This action cannot be undone.</p>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors">
                        Delete User
                    </button>
            </div>
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
        
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
        
        // Form validation
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
                const confirmError = document.getElementById('confirm_password').nextElementSibling.nextElementSibling;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                    confirmError.classList.remove('hidden');
                } else {
                    confirmError.classList.add('hidden');
                }
            });
        }
        
        // Delete user functionality
        const deleteButtons = document.querySelectorAll('.delete-user-btn');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const usernameSpan = document.getElementById('delete-username');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                const form = this.closest('form');
                
                usernameSpan.textContent = username;
                deleteConfirmModal.classList.remove('hidden');
                
                confirmDeleteBtn.onclick = function() {
                    form.submit();
                };
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