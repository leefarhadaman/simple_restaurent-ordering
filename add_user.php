<?php
/**
 * External User Creation Tool
 * 
 * WARNING: This file allows adding admin and kitchen staff users without authentication.
 * For security reasons, delete or rename this file after use.
 */

// Include configuration files
require_once 'includes/config.php';

// Initialize variables
$message = '';
$error = '';
$userType = isset($_POST['user_type']) ? $_POST['user_type'] : 'admin';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $name = sanitizeInput($_POST['name'] ?? '');
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($userType === 'kitchen' && empty($name)) {
        $error = 'Full name is required for kitchen staff';
    } else {
        try {
            $pdo = getDbConnection();
            
            if ($userType === 'admin') {
                // Check if admin username already exists
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->rowCount() > 0) {
                    $error = 'Admin username already exists';
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
            } else {
                // Check if kitchen staff username already exists
                $stmt = $pdo->prepare("SELECT id FROM kitchen_staff WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->rowCount() > 0) {
                    $error = 'Kitchen staff username already exists';
                } else {
                    // Add new kitchen staff
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO kitchen_staff (username, password, name) VALUES (?, ?, ?)");
                    
                    if ($stmt->execute([$username, $hashedPassword, $name])) {
                        $message = 'Kitchen staff user added successfully. You can now log in using these credentials.';
                    } else {
                        $error = 'Failed to add kitchen staff user';
                    }
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
    <title>Add User - <?php echo htmlspecialchars($restaurantName); ?></title>
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
                        kitchen: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
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
                <div class="text-sm mt-1 opacity-90">Add User</div>
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
                
                <!-- User Type Selection Tabs -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button type="button" 
                        class="user-type-tab py-2 px-4 border-b-2 <?php echo $userType === 'admin' ? 'border-violet-500 text-violet-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> font-medium text-sm" 
                        data-type="admin">
                        <i class="fas fa-user-shield mr-2"></i>Admin User
                    </button>
                    <button type="button" 
                        class="user-type-tab py-2 px-4 border-b-2 <?php echo $userType === 'kitchen' ? 'border-kitchen-500 text-kitchen-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> font-medium text-sm" 
                        data-type="kitchen">
                        <i class="fas fa-utensils mr-2"></i>Kitchen Staff
                    </button>
                </div>
                
                <form method="post" action="">
                    <input type="hidden" id="user_type" name="user_type" value="<?php echo $userType; ?>">
                    
                    <div class="mb-6">
                        <label for="username" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-user text-violet-600"></i> Username
                        </label>
                        <input type="text" id="username" name="username" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors"
                            placeholder="Enter username" required autofocus
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div id="name-field" class="mb-6 <?php echo $userType === 'admin' ? 'hidden' : ''; ?>">
                        <label for="name" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-id-card text-kitchen-600"></i> Full Name
                        </label>
                        <input type="text" id="name" name="name" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-kitchen-300 focus:border-kitchen-500 transition-colors"
                            placeholder="Enter full name" <?php echo $userType === 'kitchen' ? 'required' : ''; ?>
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        <p class="text-xs text-gray-500 mt-1">This name will be displayed in the kitchen interface</p>
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
                    <button type="submit" id="submit-button" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-medium py-3 px-4 rounded-md flex justify-center items-center gap-2 transition-colors">
                        <i class="fas fa-user-plus"></i> Create Admin User
                    </button>
                </form>
                
                <div class="mt-6 bg-red-50 text-red-800 p-4 rounded-md border border-red-100">
                    <p class="flex items-center gap-2 font-semibold mb-2">
                        <i class="fas fa-exclamation-triangle text-red-600"></i> Security Warning:
                    </p>
                    <p class="text-sm mb-1">This page allows creating users without authentication.</p>
                    <p class="text-sm">For security reasons, delete or rename this file after creating your users.</p>
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-center gap-4">
                <a href="admin/login.php" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md text-sm flex items-center gap-2 transition-colors">
                    <i class="fas fa-sign-in-alt"></i> Admin Login
                </a>
                <a href="kitchen/index.php" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-md text-sm flex items-center gap-2 transition-colors">
                    <i class="fas fa-utensils"></i> Kitchen Login
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

        // User type tab switching
        document.querySelectorAll('.user-type-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const userType = this.getAttribute('data-type');
                
                // Update hidden input
                document.getElementById('user_type').value = userType;
                
                // Update tabs styling
                document.querySelectorAll('.user-type-tab').forEach(t => {
                    if (t.getAttribute('data-type') === userType) {
                        t.classList.add(userType === 'admin' ? 'border-violet-500' : 'border-kitchen-500');
                        t.classList.add(userType === 'admin' ? 'text-violet-600' : 'text-kitchen-600');
                        t.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        t.classList.remove('border-violet-500', 'border-kitchen-500', 'text-violet-600', 'text-kitchen-600');
                        t.classList.add('border-transparent', 'text-gray-500');
                    }
                });
                
                // Show/hide name field
                const nameField = document.getElementById('name-field');
                if (userType === 'kitchen') {
                    nameField.classList.remove('hidden');
                    document.getElementById('name').required = true;
                    document.getElementById('submit-button').innerHTML = '<i class="fas fa-user-plus"></i> Create Kitchen Staff';
                    document.getElementById('submit-button').classList.remove('bg-violet-600', 'hover:bg-violet-700');
                    document.getElementById('submit-button').classList.add('bg-kitchen-600', 'hover:bg-kitchen-700');
                } else {
                    nameField.classList.add('hidden');
                    document.getElementById('name').required = false;
                    document.getElementById('submit-button').innerHTML = '<i class="fas fa-user-plus"></i> Create Admin User';
                    document.getElementById('submit-button').classList.remove('bg-kitchen-600', 'hover:bg-kitchen-700');
                    document.getElementById('submit-button').classList.add('bg-violet-600', 'hover:bg-violet-700');
                }
            });
        });

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