<?php
/**
 * External Admin User Creation Tool
 * 
 * WARNING: This file allows adding admin users without authentication.
 * For security reasons, delete or rename this file after use.
 */

// Include configuration files
require_once 'includes/config.php';

// Initialize variables
$message = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username already exists';
            } else {
                // Add new admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                
                if ($stmt->execute([$username, $hashedPassword])) {
                    $message = 'Admin user added successfully. You can now log in using these credentials.';
                } else {
                    $error = 'Failed to add admin user';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get restaurant name for display
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT restaurant_name FROM settings LIMIT 1");
    $restaurantName = $stmt->fetch()['restaurant_name'] ?? 'Restaurant Ordering System';
} catch (PDOException $e) {
    $restaurantName = 'Restaurant Ordering System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin User - <?php echo htmlspecialchars($restaurantName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Replace admin.css with Tailwind CSS -->
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
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-violet-800 p-6 text-center text-white">
                <div class="text-2xl font-bold"><?php echo htmlspecialchars($restaurantName); ?></div>
                <div class="text-sm mt-1 opacity-90">Add Admin User</div>
            </div>
            <div class="p-6">
                <?php if ($message): ?>
                    <div class="bg-green-50 text-green-700 p-4 flex items-center gap-3 rounded-md mb-4 border border-green-100">
                        <i class="fas fa-check-circle text-green-500"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-700 p-4 flex items-center gap-3 rounded-md mb-4 border border-red-100">
                        <i class="fas fa-exclamation-circle text-red-500"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-6">
                        <label for="username" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-user text-violet-600"></i> Username
                        </label>
                        <input type="text" id="username" name="username" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors"
                            placeholder="Enter username" required autofocus
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="mb-6">
                        <label for="password" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-lock text-violet-600"></i> Password
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" 
                                class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors pr-10"
                                placeholder="Enter password (min 8 characters)" required minlength="8">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-violet-600" onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label for="confirm_password" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-lock text-violet-600"></i> Confirm Password
                        </label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors pr-10"
                                placeholder="Confirm password" required minlength="8">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-violet-600" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-medium py-3 px-4 rounded-md flex justify-center items-center gap-2 transition-colors">
                        <i class="fas fa-user-plus"></i> Create Admin User
                    </button>
                </form>
                
                <div class="mt-6 bg-red-50 text-red-800 p-4 rounded-md border border-red-100">
                    <p class="flex items-center gap-2 font-semibold mb-2">
                        <i class="fas fa-exclamation-triangle text-red-600"></i> Security Warning:
                    </p>
                    <p class="text-sm mb-1">This page allows creating admin users without authentication.</p>
                    <p class="text-sm">For security reasons, delete or rename this file after creating your admin user.</p>
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-center gap-4">
                <a href="admin/login.php" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm flex items-center gap-2 transition-colors">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
                <a href="customer/" class="px-4 py-2 bg-slate-500 hover:bg-slate-600 text-white rounded-md text-sm flex items-center gap-2 transition-colors">
                    <i class="fas fa-utensils"></i> View Menu
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggle = passwordField.nextElementSibling.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }

        // Validate passwords match
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
            }
        });
    </script>
</body>
</html> 