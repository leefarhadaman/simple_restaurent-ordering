<?php
/**
 * Reset Kitchen Staff Password
 * Admin tool to reset kitchen staff passwords
 */
require_once '../includes/config.php';

// Require admin login
requireLogin();

// Initialize variables
$success = false;
$error = '';
$staffInfo = null;

// Check for staff ID
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staffId <= 0) {
    header('Location: add_kitchen_user.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get staff information
    $stmt = $pdo->prepare("SELECT id, username, name FROM kitchen_staff WHERE id = ?");
    $stmt->execute([$staffId]);
    $staffInfo = $stmt->fetch();
    
    if (!$staffInfo) {
        header('Location: add_kitchen_user.php');
        exit;
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate passwords
        if (empty($password)) {
            $error = 'Password is required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE kitchen_staff SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $staffId]);
            
            if ($result) {
                $success = true;
            } else {
                $error = 'Failed to update password.';
            }
        }
    }
} catch (PDOException $e) {
    error_log('Reset kitchen password error: ' . $e->getMessage());
    $error = 'A database error occurred. Please try again later.';
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
    <title>Reset Password - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
    </style>
</head>
<body class="min-h-screen pt-16 pb-10">
    <!-- Top navbar -->
    <header class="fixed top-0 right-0 left-0 z-30 bg-white border-b border-gray-200 h-16">
        <div class="max-w-3xl mx-auto flex items-center justify-between h-full px-4 sm:px-6">
            <div class="flex items-center">
                <a href="add_kitchen_user.php" class="text-gray-500 hover:text-gray-600 p-2">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <h1 class="ml-3 text-lg font-medium text-gray-800">Reset Kitchen Staff Password</h1>
            </div>
        </div>
    </header>
    
    <!-- Page content -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center">
                <div class="h-10 w-10 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center mr-3">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">Reset Password for <?php echo htmlspecialchars($staffInfo['name']); ?></h2>
                    <p class="text-sm text-gray-500">Username: <?php echo htmlspecialchars($staffInfo['username']); ?></p>
                </div>
            </div>
            
            <div class="p-6">
                <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-md border border-green-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <div>
                            <p class="font-medium">Password Reset Successful</p>
                            <p class="text-sm mt-1">The password has been updated successfully.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <a href="add_kitchen_user.php" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium">
                            Return to Kitchen Staff
                        </a>
                    </div>
                </div>
                <?php else: ?>
                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-md border border-red-200">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3 mt-0.5"></i>
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="password" name="password" 
                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                    required>
                                <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Minimum 6 characters. Choose a secure password.</p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                    class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500"
                                    required>
                                <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Re-enter the password to confirm.</p>
                        </div>
                        
                        <div class="flex items-center justify-end gap-3">
                            <a href="add_kitchen_user.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
                                Reset Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggles
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const passwordField = this.previousElementSibling;
                    
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        passwordField.type = 'password';
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                });
            });
        });
    </script>
</body>
</html> 