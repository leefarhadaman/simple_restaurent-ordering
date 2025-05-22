<?php
/**
 * Kitchen Staff Login
 * Separate login for kitchen staff to manage order preparation
 */
require_once '../includes/config.php';

// Initialize variables
$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDbConnection();
            
            // Check credentials against kitchen_staff table
            $stmt = $pdo->prepare("SELECT id, username, password, name FROM kitchen_staff WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    session_regenerate_id(true);
                    $_SESSION['kitchen_id'] = $user['id'];
                    $_SESSION['kitchen_username'] = $user['username'];
                    $_SESSION['kitchen_name'] = $user['name'];
                    
                    // Update last login
                    $stmt = $pdo->prepare("UPDATE kitchen_staff SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        }
    }
}

// Get restaurant settings
$settings = getThemeSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Login - <?php echo htmlspecialchars($settings['restaurant_name']); ?></title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and header -->
        <div class="text-center mb-6">
            <img src="<?php echo $settings['restaurant_logo'] ? '../uploads/' . $settings['restaurant_logo'] : '../assets/images/restaurant-logo.png'; ?>" 
                 alt="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" 
                 class="h-16 mx-auto mb-2">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($settings['restaurant_name']); ?></h1>
            <p class="text-gray-600">Kitchen Staff Portal</p>
        </div>
        
        <!-- Login card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-kitchen-700 text-white">
                <h2 class="text-xl font-semibold">Kitchen Staff Login</h2>
            </div>
            
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-md border border-red-200">
                        <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-4">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" 
                                class="pl-10 block w-full border-gray-300 focus:border-kitchen-500 focus:ring focus:ring-kitchen-500 focus:ring-opacity-50 rounded-md shadow-sm py-2 px-3 border"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                required>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="pl-10 block w-full border-gray-300 focus:border-kitchen-500 focus:ring focus:ring-kitchen-500 focus:ring-opacity-50 rounded-md shadow-sm py-2 px-3 border"
                                required>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full py-2 px-4 bg-kitchen-600 hover:bg-kitchen-700 focus:ring-4 focus:ring-kitchen-500 focus:ring-opacity-50 text-white font-medium rounded-md transition-colors">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-6 text-center">
            <a href="../" class="text-kitchen-600 hover:text-kitchen-700 text-sm flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Restaurant
            </a>
        </div>
    </div>
</body>
</html> 