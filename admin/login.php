<?php
/**
 * Admin Login Page
 * Modern UI Implementation - Redesigned with Tailwind CSS
 */
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Attempt login
        if (login($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}

// Get settings for header
$settings = getThemeSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo $settings['restaurant_name']; ?></title>
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
                }
            }
        }
    </script>
    <link rel="shortcut icon" href="../favicon.ico">
</head>
<body class="bg-slate-50 font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-violet-600 to-violet-800 p-6 text-center text-white">
                <div class="text-2xl font-bold"><?php echo $settings['restaurant_name']; ?></div>
                <div class="text-sm mt-1 opacity-90">Admin Dashboard</div>
            </div>
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-700 p-4 flex items-center gap-3 rounded-md mb-4 border border-red-100">
                        <i class="fas fa-exclamation-circle text-red-500"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" id="loginForm" class="needs-validation" novalidate>
                    <div class="mb-6">
                        <label for="username" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-user text-violet-600"></i> Username
                        </label>
                        <input type="text" id="username" name="username" 
                            class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors"
                            placeholder="Enter your username" required autofocus
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <div class="invalid-feedback text-sm text-red-600 mt-1 hidden">Please enter your username</div>
                    </div>
                    <div class="mb-6">
                        <label for="password" class="flex items-center gap-2 text-slate-700 font-medium text-sm mb-2">
                            <i class="fas fa-lock text-violet-600"></i> Password
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" 
                                class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 transition-colors pr-10"
                                placeholder="Enter your password" required>
                            <span class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 cursor-pointer hover:text-violet-600">
                                <i class="fas fa-eye"></i>
                            </span>
                            <div class="invalid-feedback text-sm text-red-600 mt-1 hidden">Please enter your password</div>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-medium py-3 px-4 rounded-md flex justify-center items-center gap-2 transition-colors">
                        <i class="fas fa-right-to-bracket"></i> Login
                    </button>
                </form>
                
                <div class="bg-slate-50 p-4 rounded-md border border-slate-100 mt-6">
                    <p class="flex items-center gap-2 text-sm text-slate-600 mb-1">
                        <i class="fas fa-shield-alt text-violet-500"></i> Secure login area. Unauthorized access is prohibited.
                    </p>
                    <p class="text-sm text-slate-600">All login attempts are monitored and recorded.</p>
                </div>
            </div>
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-center gap-4">
                <a href="../customer/" class="px-4 py-2 border border-violet-300 text-violet-700 rounded-md text-sm hover:bg-violet-50 flex items-center gap-2 transition-colors">
                    <i class="fas fa-store"></i> View Store
                </a>
                <a href="forgot_password.php" class="px-4 py-2 border border-violet-300 text-violet-700 rounded-md text-sm hover:bg-violet-50 flex items-center gap-2 transition-colors">
                    <i class="fas fa-key"></i> Forgot Password
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle visibility
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordField = document.getElementById('password');
            
            if (passwordToggle && passwordField) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    
                    // Toggle icon
                    if (type === 'password') {
                        this.innerHTML = '<i class="fas fa-eye"></i>';
                    } else {
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    }
                });
            }
            
            // Form validation
            const form = document.getElementById('loginForm');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Show validation messages
                        const invalidInputs = form.querySelectorAll(':invalid');
                        invalidInputs.forEach(input => {
                            const feedback = input.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.classList.remove('hidden');
                            }
                        });
                    }
                });
                
                // Hide validation message when user types
                const inputs = form.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const feedback = this.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.classList.add('hidden');
                        }
                    });
                });
            }
            
            // Focus first field with error or username field
            const firstError = document.querySelector('.form-control:invalid');
            if (firstError) {
                firstError.focus();
            } else if (document.getElementById('username')) {
                document.getElementById('username').focus();
            }
        });
    </script>
</body>
</html> 