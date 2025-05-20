<?php
/**
 * Admin Login Page
 * Modern UI Implementation - Redesigned
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
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="shortcut icon" href="../favicon.ico">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo"><?php echo $settings['restaurant_name']; ?></div>
                <div class="auth-subtitle">Admin Dashboard</div>
            </div>
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" id="loginForm" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                            placeholder="Enter your username" required autofocus
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <div class="invalid-feedback">Please enter your username</div>
                    </div>
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" 
                                placeholder="Enter your password" required>
                            <span class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </span>
                            <div class="invalid-feedback">Please enter your password</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Login
                    </button>
                </form>
                
                <div class="security-notice mt-4">
                    <p><i class="fas fa-shield-alt"></i> Secure login area. Unauthorized access is prohibited.</p>
                    <p>All login attempts are monitored and recorded.</p>
                </div>
            </div>
            <div class="auth-footer">
                <div class="links">
                    <a href="../customer/" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-store"></i> View Store
                    </a>
                    <a href="forgot_password.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-key"></i> Forgot Password
                    </a>
                </div>
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
                    }
                    
                    form.classList.add('was-validated');
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