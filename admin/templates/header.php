<?php
/**
 * Admin Header Template
 * Modern UI Implementation - Redesigned with Tailwind CSS
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo $settings['restaurant_name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
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
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
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
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }
        
        /* Scrollbar styling */
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
        
        /* Main layout */
        .admin-wrap {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }
        
        /* Sidebar styling */
        .sidebar {
            background-color: white;
            width: 256px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 40;
            border-right: 1px solid #e2e8f0;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }
        
        /* Header styling */
        .main-header {
            background-color: white;
            height: 64px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: fixed;
            top: 0;
            right: 0;
            left: 256px;
            z-index: 30;
        }
        
        /* Content area */
        .main-content {
            margin-left: 256px;
            padding-bottom: 70px; /* Space for footer */
            min-height: 100vh;
            width: calc(100% - 256px);
        }
        
        .content-inner {
            padding: 24px;
            height: 100%;
            max-width: 100%;
            margin: 0 auto;
        }
        
        /* Footer styling */
        .footer {
            background-color: white;
            border-top: 1px solid #e2e8f0;
            position: fixed;
            bottom: 0;
            left: 256px;
            right: 0;
            height: 57px;
            z-index: 30;
            display: flex;
            align-items: center;
        }
        
        /* Mobile styles */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-header, 
            .footer {
                left: 0;
                width: 100%;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 35;
                display: none;
            }
            
            .sidebar-overlay.active {
                display: block;
            }
        }
        
        /* Dropdown styling */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width: 180px;
            z-index: 50;
            display: none;
            margin-top: 8px;
        }
        
        .dropdown-menu.active {
            display: block;
        }
        
        /* Active sidebar item */
        .nav-item.active {
            background-color: #ede9fe;
            color: #6d28d9;
            font-weight: 600;
            position: relative;
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 24px;
            width: 3px;
            background-color: #6d28d9;
            border-top-right-radius: 3px;
            border-bottom-right-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="admin-wrap">
        <!-- Sidebar overlay for mobile -->
        <div class="sidebar-overlay" id="overlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="p-4 md:p-6 border-b border-slate-100 flex flex-col items-center">
                <div class="text-xl md:text-2xl font-bold bg-gradient-to-r from-violet-600 to-violet-800 text-transparent bg-clip-text truncate max-w-full"><?php echo $settings['restaurant_name']; ?></div>
                <div class="text-sm text-slate-500 mt-1">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
                <button id="close-sidebar" class="absolute top-4 right-4 text-slate-500 hover:text-red-500 hover:bg-red-50 rounded-full w-8 h-8 flex items-center justify-center md:hidden">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="py-4">
                <ul>
                    <li class="mb-1">
                        <a href="index.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-gauge-high w-5 text-center mr-4 text-lg"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="menu_items.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'menu_items.php' ? 'active' : ''; ?>">
                            <i class="fas fa-utensils w-5 text-center mr-4 text-lg"></i>
                            <span>Menu Items</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="categories.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list w-5 text-center mr-4 text-lg"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="active_orders.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'active_orders.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-list w-5 text-center mr-4 text-lg"></i>
                            <span>Active Orders</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="completed_orders.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'completed_orders.php' ? 'active' : ''; ?>">
                            <i class="fas fa-clipboard-check w-5 text-center mr-4 text-lg"></i>
                            <span>Completed Orders</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="manage_users.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users-gear w-5 text-center mr-4 text-lg"></i>
                            <span>Admin Users</span>
                        </a>
                    </li>
                    <li class="mb-1">
                        <a href="settings.php" class="nav-item flex items-center py-3 px-6 text-slate-700 hover:bg-violet-50 hover:text-violet-700 rounded-md mx-2 font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-gear w-5 text-center mr-4 text-lg"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="p-4 border-t border-slate-100 absolute bottom-0 w-full bg-white">
                <a href="../customer/" class="flex items-center py-2 px-4 text-slate-700 hover:text-violet-700" target="_blank">
                    <i class="fas fa-store mr-4"></i>
                    <span>View Store</span>
                </a>
            </div>
        </aside>
        
        <!-- Header -->
        <header class="main-header">
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <button id="toggle-sidebar" class="mr-4 text-slate-500 bg-transparent border-0 text-xl cursor-pointer w-10 h-10 flex items-center justify-center rounded hover:bg-slate-100 hover:text-violet-600 md:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="font-semibold text-slate-800 text-base md:text-lg"><?php echo ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?></h1>
                </div>
                <div class="dropdown" id="user-dropdown">
                    <button class="flex items-center gap-2 text-slate-800 font-medium hover:bg-slate-100 rounded p-2 px-3 cursor-pointer">
                        <i class="fas fa-circle-user text-xl text-violet-600"></i>
                        <span class="hidden sm:inline-block"><?php echo $_SESSION['admin_username']; ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="dropdown-menu" id="dropdown-menu">
                        <a href="change_password.php" class="flex items-center py-2 px-4 text-slate-700 hover:bg-slate-100 hover:text-violet-700 text-sm">
                            <i class="fas fa-key w-4 mr-4"></i> Change Password
                        </a>
                        <a href="settings.php" class="flex items-center py-2 px-4 text-slate-700 hover:bg-slate-100 hover:text-violet-700 text-sm">
                            <i class="fas fa-gear w-4 mr-4"></i> Settings
                        </a>
                        <div class="h-px bg-slate-100 my-2"></div>
                        <a href="logout.php" class="flex items-center py-2 px-4 text-slate-700 hover:bg-slate-100 hover:text-violet-700 text-sm">
                            <i class="fas fa-right-from-bracket w-4 mr-4"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main content area -->
        <main class="main-content">
            <div class="content-inner">
                <!-- Alert container -->
                <div id="alert-container" class="mb-4"></div>
                
                <!-- Content will be rendered here -->
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // User dropdown toggle
        const userDropdown = document.getElementById('user-dropdown');
        const userDropdownMenu = document.getElementById('dropdown-menu');
        
        if (userDropdown && userDropdownMenu) {
            userDropdown.addEventListener('click', function() {
                userDropdownMenu.classList.toggle('active');
                const chevron = userDropdown.querySelector('.fa-chevron-down');
                if (chevron) {
                    chevron.classList.toggle('rotate-180');
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target)) {
                    userDropdownMenu.classList.remove('active');
                    const chevron = userDropdown.querySelector('.fa-chevron-down');
                    if (chevron) {
                        chevron.classList.remove('rotate-180');
                    }
                }
            });
        }
        
        // Mobile sidebar toggle
        const mobileToggle = document.getElementById('toggle-sidebar');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('overlay');
        const closeSidebarBtn = document.getElementById('close-sidebar');
        
        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = ''; // Re-enable scrolling
        }
        
        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                openSidebar();
            });
        }
        
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeSidebar();
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                closeSidebar();
            });
        }
        
        // Handle resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && sidebar) {
                sidebar.classList.remove('open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
                document.body.style.overflow = '';
            }
        });
    });
    </script>
</body>
</html> 