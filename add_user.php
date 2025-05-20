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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .security-notice {
            margin-top: 20px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: var(--border-radius);
            color: #c62828;
            font-size: 0.9rem;
        }
        
        .security-notice p {
            margin: 5px 0;
        }
        
        .links {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo"><?php echo htmlspecialchars($restaurantName); ?></div>
                <div class="auth-subtitle">Add Admin User</div>
            </div>
            <div class="auth-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                            placeholder="Enter username" required autofocus
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" 
                                placeholder="Enter password (min 8 characters)" required minlength="8">
                            <span class="password-toggle" onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                placeholder="Confirm password" required minlength="8">
                            <span class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-user-plus"></i> Create Admin User
                    </button>
                </form>
                
                <div class="security-notice">
                    <p><strong><i class="fas fa-exclamation-triangle"></i> Security Warning:</strong></p>
                    <p>This page allows creating admin users without authentication.</p>
                    <p>For security reasons, delete or rename this file after creating your admin user.</p>
                </div>
            </div>
            <div class="auth-footer">
                <div class="links">
                    <a href="admin/login.php" class="btn btn-sm btn-info">
                        <i class="fas fa-sign-in-alt"></i> Admin Login
                    </a>
                    <a href="customer/" class="btn btn-sm btn-secondary">
                        <i class="fas fa-utensils"></i> View Menu
                    </a>
                </div>
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