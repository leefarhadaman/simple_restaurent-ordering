<?php
/**
 * Admin Header Template
 * Modern UI Implementation - Redesigned
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="shortcut icon" href="../favicon.ico">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo"><?php echo $settings['restaurant_name']; ?></div>
            <div class="sidebar-user">Welcome, <?php echo $_SESSION['admin_username']; ?></div>
            <button class="close-sidebar d-md-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gauge-high"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="menu_items.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'menu_items.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i>
                    <span class="nav-text">Menu Items</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span class="nav-text">Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="active_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'active_orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span class="nav-text">Active Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="completed_orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'completed_orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span class="nav-text">Completed Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-gear"></i>
                    <span class="nav-text">Admin Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-gear"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="../customer/" class="nav-link" target="_blank">
                <i class="fas fa-store"></i>
                <span class="nav-text">View Store</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-toggle d-md-none" id="mobile-toggle" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="top-bar-title">
                    <?php echo ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="user-dropdown" id="userDropdown">
                    <button class="user-dropdown-toggle" aria-label="User menu">
                        <i class="fas fa-circle-user"></i>
                        <span class="d-none d-md-inline-block"><?php echo $_SESSION['admin_username']; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <a href="change_password.php" class="dropdown-item">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-gear"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content container -->
        <div class="content-container">
            <!-- Alert container for JavaScript notifications -->
            <div id="alert-container"></div>
        </div>
    </div>
</body>
</html> 