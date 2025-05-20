<?php
/**
 * Restaurant Settings Page
 * Modern UI Implementation - Redesigned
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Get form data
        $restaurantName = sanitizeInput($_POST['restaurant_name'] ?? '');
        $themeColor = sanitizeInput($_POST['theme_color'] ?? 'Light Red & White');
        
        // Validate restaurant name
        if (empty($restaurantName)) {
            throw new Exception('Restaurant name is required');
        }
        
        // Handle logo upload if provided
        $currentLogo = null;
        $stmt = $pdo->query("SELECT restaurant_logo FROM settings WHERE id = 1");
        if ($row = $stmt->fetch()) {
            $currentLogo = $row['restaurant_logo'];
        }
        
        $logoFilename = $currentLogo; // Default to current logo
        
        if (!empty($_FILES['logo']['name'])) {
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Upload and compress the logo
            $logoFilename = handleImageUpload($_FILES['logo']);
            
            if (!$logoFilename) {
                throw new Exception('Failed to upload logo image');
            }
        }
        
        // Update settings
        $stmt = $pdo->prepare("
            UPDATE settings SET 
            restaurant_name = ?, 
            restaurant_logo = ?, 
            theme_color = ?
            WHERE id = 1
        ");
        
        $result = $stmt->execute([
            $restaurantName,
            $logoFilename,
            $themeColor
        ]);
        
        if ($result) {
            $message = 'Settings updated successfully';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to update settings');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get current settings
$settings = getThemeSettings();
$themeColors = [
    'Light Red & White',
    'Deep Blue & Yellow',
    'Forest Green & Cream',
    'Black & Orange'
];

// Include header template
require_once 'templates/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Restaurant Settings</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">General Settings</h2>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" id="settings-form" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="restaurant_name" class="form-label">Restaurant Name</label>
                <input type="text" id="restaurant_name" name="restaurant_name" class="form-control" 
                    value="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" required>
                <div class="invalid-feedback">Restaurant name is required</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Restaurant Logo</label>
                <div class="image-upload-container">
                    <div class="image-preview">
                        <?php if ($settings['restaurant_logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($settings['restaurant_logo']); ?>" alt="Restaurant Logo">
                        <?php else: ?>
                            <div class="image-preview-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <label class="custom-file-upload btn btn-outline-primary">
                        <input type="file" id="logo" name="logo" accept="image/*">
                        <i class="fas fa-upload"></i> Select New Logo
                    </label>
                    <small class="form-text">Maximum file size: 500KB. Supported formats: JPG, PNG, GIF</small>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Theme Color</label>
                <input type="hidden" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                
                <div class="theme-options">
                    <?php foreach ($themeColors as $theme): 
                        // Get theme color values for preview
                        $colors = getThemeColors($theme);
                    ?>
                        <div class="theme-option <?php echo ($settings['theme_color'] === $theme) ? 'active' : ''; ?>" data-theme="<?php echo htmlspecialchars($theme); ?>">
                            <div class="theme-preview">
                                <div class="theme-preview-primary" style="background-color: <?php echo htmlspecialchars($colors['primary']); ?>;"></div>
                                <div class="theme-preview-secondary" style="background-color: <?php echo htmlspecialchars($colors['secondary']); ?>;"></div>
                            </div>
                            <div class="theme-name"><?php echo htmlspecialchars($theme); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title">Change Password</h2>
    </div>
    <div class="card-body">
        <form method="post" action="change_password.php" id="password-form" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="password-field">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="invalid-feedback">Current password is required</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <div class="password-field">
                    <input type="password" id="new_password" name="new_password" class="form-control" 
                           pattern=".{8,}" title="Password must be at least 8 characters long" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="invalid-feedback">Password must be at least 8 characters long</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <span class="password-toggle">
                        <i class="fas fa-eye"></i>
                    </span>
                    <div class="invalid-feedback">Please confirm your new password</div>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title">System Information</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tbody>
                        <tr>
                            <td><strong>PHP Version</strong></td>
                            <td><?php echo phpversion(); ?></td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Version</strong></td>
                            <td>
                                <?php 
                                try {
                                    $pdo = getDbConnection();
                                    echo $pdo->query('SELECT VERSION()')->fetchColumn();
                                } catch (Exception $e) {
                                    echo 'Unknown';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Server Software</strong></td>
                            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table">
                    <tbody>
                        <tr>
                            <td><strong>System Version</strong></td>
                            <td>v2.0</td>
                        </tr>
                        <tr>
                            <td><strong>Last Settings Update</strong></td>
                            <td>
                                <?php 
                                try {
                                    $pdo = getDbConnection();
                                    $stmt = $pdo->query("SELECT updated_at FROM settings WHERE id = 1");
                                    $date = $stmt->fetchColumn();
                                    if ($date) {
                                        $dateObj = new DateTime($date);
                                        echo $dateObj->format('M d, Y - H:i');
                                    } else {
                                        echo 'Unknown';
                                    }
                                } catch (Exception $e) {
                                    echo 'Unknown';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Database Status</strong></td>
                            <td><span class="badge badge-success">Connected</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Alert container is already included in header.php -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password form validation
    const passwordForm = document.getElementById('password-form');
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
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
            
            if (!passwordForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            passwordForm.classList.add('was-validated');
        });
    }
    
    // Settings form validation
    const settingsForm = document.getElementById('settings-form');
    
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            if (!settingsForm.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            settingsForm.classList.add('was-validated');
        });
    }
    
    // Image upload preview enhancement
    const logoInput = document.getElementById('logo');
    const imagePreview = document.querySelector('.image-preview');
    
    if (logoInput && imagePreview) {
        logoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Check file size
                const maxSize = 500 * 1024; // 500KB
                if (this.files[0].size > maxSize) {
                    if (typeof showAlert === 'function') {
                        showAlert('Image size is larger than 500KB. It will be compressed upon upload.', 'warning');
                    } else {
                        alert('Image size is larger than 500KB. It will be compressed upon upload.');
                    }
                }
                
                // Update preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Clear existing content
                    imagePreview.innerHTML = '';
                    
                    // Create image element
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    imagePreview.appendChild(img);
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 