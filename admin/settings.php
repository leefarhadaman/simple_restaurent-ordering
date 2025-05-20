<?php
/**
 * Restaurant Settings Page
 * Modern UI Implementation - Redesigned with Tailwind CSS
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

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-800">Restaurant Settings</h1>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-md flex items-start gap-3">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mt-0.5"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <!-- General Settings -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">General Settings</h2>
        </div>
        <div class="p-6">
            <form method="post" enctype="multipart/form-data" id="settings-form" class="space-y-6">
                <div>
                    <label for="restaurant_name" class="block text-sm font-medium text-slate-700 mb-1">Restaurant Name</label>
                    <input type="text" id="restaurant_name" name="restaurant_name" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                        value="<?php echo htmlspecialchars($settings['restaurant_name']); ?>" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">Restaurant Logo</label>
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <div class="w-40 h-40 bg-slate-100 rounded-md flex items-center justify-center overflow-hidden border border-slate-200">
                            <?php if ($settings['restaurant_logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($settings['restaurant_logo']); ?>" alt="Restaurant Logo" class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <div class="text-slate-400 text-center">
                                    <i class="fas fa-image text-5xl mb-2"></i>
                                    <p class="text-sm">No logo uploaded</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="space-y-3">
                            <label class="block w-full px-4 py-2 border border-violet-300 bg-violet-50 text-violet-700 rounded-md cursor-pointer hover:bg-violet-100 transition-colors text-center">
                                <input type="file" id="logo" name="logo" accept="image/*" class="hidden">
                                <i class="fas fa-upload mr-2"></i> Select New Logo
                            </label>
                            <p class="text-xs text-slate-500">Maximum file size: 500KB. Supported formats: JPG, PNG, GIF</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-3">Theme Color</label>
                    <input type="hidden" id="theme_color" name="theme_color" value="<?php echo htmlspecialchars($settings['theme_color']); ?>">
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <?php foreach ($themeColors as $theme): 
                            // Get theme color values for preview
                            $colors = getThemeColors($theme);
                        ?>
                            <div class="theme-option cursor-pointer rounded-md border-2 p-3 transition-colors <?php echo ($settings['theme_color'] === $theme) ? 'border-violet-500 bg-violet-50' : 'border-slate-200 hover:border-slate-300'; ?>" data-theme="<?php echo htmlspecialchars($theme); ?>">
                                <div class="flex mb-2">
                                    <div class="w-1/2 h-8 rounded-l" style="background-color: <?php echo htmlspecialchars($colors['primary']); ?>;"></div>
                                    <div class="w-1/2 h-8 rounded-r" style="background-color: <?php echo htmlspecialchars($colors['secondary']); ?>;"></div>
                                </div>
                                <div class="text-center text-sm"><?php echo htmlspecialchars($theme); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-save mr-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">Change Password</h2>
        </div>
        <div class="p-6">
            <form method="post" action="change_password.php" id="password-form" class="space-y-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
                    <div class="relative">
                        <input type="password" id="current_password" name="current_password" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 pr-10"
                            required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-violet-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" id="new_password" name="new_password" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 pr-10"
                            pattern=".{8,}" title="Password must be at least 8 characters long" required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-violet-600">
                            <i class="fas fa-eye"></i>
                        </button>
                        <p class="text-xs text-slate-500 mt-1">Password must be at least 8 characters long</p>
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500 pr-10"
                            required>
                        <button type="button" class="password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-violet-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">System Information</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="overflow-hidden">
                    <table class="min-w-full border-collapse">
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">PHP Version</td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">MySQL Version</td>
                                <td class="px-4 py-3 text-sm text-slate-700">
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
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">Server Software</td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="overflow-hidden">
                    <table class="min-w-full border-collapse">
                        <tbody class="divide-y divide-slate-200">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">System Time</td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo date('Y-m-d H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">PHP Memory Limit</td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo ini_get('memory_limit'); ?></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-50">Upload Max Size</td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo ini_get('upload_max_filesize'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme options selection
    const themeOptions = document.querySelectorAll('.theme-option');
    const themeInput = document.getElementById('theme_color');
    
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            
            // Update hidden input
            themeInput.value = theme;
            
            // Update UI
            themeOptions.forEach(opt => {
                opt.classList.remove('border-violet-500', 'bg-violet-50');
                opt.classList.add('border-slate-200');
            });
            this.classList.remove('border-slate-200');
            this.classList.add('border-violet-500', 'bg-violet-50');
        });
    });
    
    // Image preview
    const logoInput = document.getElementById('logo');
    logoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = logoInput.closest('div').previousElementSibling;
                preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="max-w-full max-h-full object-contain">`;
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Password toggles
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Update icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });
    
    // Form validation
    const passwordForm = document.getElementById('password-form');
    passwordForm.addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirm password do not match.');
        }
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 