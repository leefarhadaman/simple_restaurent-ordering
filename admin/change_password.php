<?php
/**
 * Change Admin Password
 * Modern UI Implementation
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize response
$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = 'All fields are required';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 8) {
        $message = 'New password must be at least 8 characters long';
        $messageType = 'danger';
    } else {
        // Attempt to change password
        $result = changePassword($_SESSION['admin_id'], $currentPassword, $newPassword);
        
        if ($result) {
            $message = 'Password changed successfully';
            $messageType = 'success';
        } else {
            $message = 'Current password is incorrect';
            $messageType = 'danger';
        }
    }
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Change Password</h1>
    <div>
        <a href="settings.php" class="btn btn-sm btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Settings
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Change Your Password</h2>
    </div>
    <div class="card-body">
        <form method="post" action="" id="password-form">
            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <small class="form-text">Password must be at least 8 characters long</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('password-form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                
                // Use the showAlert function from admin.js if available
                if (typeof showAlert === 'function') {
                    showAlert('Passwords do not match', 'danger');
                } else {
                    alert('Passwords do not match');
                }
                
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                
                if (typeof showAlert === 'function') {
                    showAlert('Password must be at least 8 characters long', 'danger');
                } else {
                    alert('Password must be at least 8 characters long');
                }
                
                return false;
            }
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 