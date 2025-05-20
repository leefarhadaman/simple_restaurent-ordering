<?php
/**
 * Admin User Management
 * Modern UI Implementation - Redesigned
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Process form submissions
$message = '';
$error = '';

// Add new admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
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
                    $message = 'Admin user added successfully';
                } else {
                    $error = 'Failed to add admin user';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Delete admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $userId = (int)$_POST['user_id'];
    $currentUserId = (int)$_SESSION['admin_id'];
    
    if ($userId === $currentUserId) {
        $error = 'You cannot delete your own account';
    } else {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            
            if ($stmt->execute([$userId])) {
                $message = 'Admin user deleted successfully';
            } else {
                $error = 'Failed to delete admin user';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all admin users
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT id, username, created_at FROM admins ORDER BY username");
    $adminUsers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to retrieve admin users: ' . $e->getMessage();
    $adminUsers = [];
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Manage Admin Users</h1>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
        <i class="fas fa-plus"></i> Add New Admin
    </button>
</div>

<!-- Admin Users List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Admin Users</h2>
    </div>
    <div class="card-body">
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
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($adminUsers as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <?php 
                                $createdDate = new DateTime($user['created_at']);
                                echo $createdDate->format('M d, Y'); 
                                ?>
                            </td>
                            <td>
                                <?php if ((int)$user['id'] !== (int)$_SESSION['admin_id']): ?>
                                    <form method="post" action="" class="d-inline delete-user-form">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-danger delete-user-btn" 
                                            data-confirm="Are you sure you want to delete user '<?php echo htmlspecialchars($user['username']); ?>'? This action cannot be undone.">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-primary">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($adminUsers)): ?>
                        <tr>
                            <td colspan="3" class="text-center">No admin users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Admin User</h3>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addUserForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="add_user">
                    
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" id="username" name="username" class="form-control" required>
                        <div class="invalid-feedback">Username is required</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" class="form-control" 
                                   required minlength="8" pattern=".{8,}" title="Password must be at least 8 characters long">
                            <span class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </span>
                            <div class="invalid-feedback">Password must be at least 8 characters long</div>
                        </div>
                        <small class="form-text">Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <span class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </span>
                            <div class="invalid-feedback">Please confirm your password</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete user confirmation - already handled by admin.js
    
    // Password validation
    const addUserForm = document.getElementById('addUserForm');
    
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show custom validation message
                const confirmField = document.getElementById('confirm_password');
                confirmField.setCustomValidity('Passwords do not match');
                
                // Use the showAlert function from admin.js if available
                if (typeof showAlert === 'function') {
                    showAlert('Passwords do not match', 'danger');
                }
            } else {
                document.getElementById('confirm_password').setCustomValidity('');
            }
            
            if (!addUserForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            addUserForm.classList.add('was-validated');
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 