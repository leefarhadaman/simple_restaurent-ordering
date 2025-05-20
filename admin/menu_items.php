<?php
/**
 * Menu Items Management
 * Modern UI Implementation - Redesigned with Tailwind CSS
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize variables
$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Get form data
        $itemId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $categoryId = (int) $_POST['category_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = (float) $_POST['price'];
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        // Validate data
        if (empty($name) || $categoryId <= 0 || $price <= 0) {
            throw new Exception('Please fill in all required fields with valid data');
        }
        
        // Handle image upload if provided
        $imageFilename = null;
        
        if (!empty($_FILES['image']['name'])) {
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
            }
            
            // Upload and compress image
            $imageFilename = handleImageUpload($_FILES['image']);
            
            if (!$imageFilename) {
                throw new Exception('Failed to upload image');
            }
        }
        
        // Insert or update menu item
        if ($itemId > 0) {
            // Get existing image if no new one uploaded
            if (!$imageFilename) {
                $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
                $stmt->execute([$itemId]);
                $item = $stmt->fetch();
                $imageFilename = $item['image'];
            }
            
            // Update existing item
            $stmt = $pdo->prepare("
                UPDATE menu_items SET 
                category_id = ?,
                name = ?,
                description = ?,
                price = ?,
                image = ?,
                is_available = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $categoryId,
                $name,
                $description,
                $price,
                $imageFilename,
                $isAvailable,
                $itemId
            ]);
            
            if ($result) {
                $message = 'Menu item updated successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to update menu item');
            }
        } else {
            // Insert new item
            $stmt = $pdo->prepare("
                INSERT INTO menu_items (category_id, name, description, price, image, is_available)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $categoryId,
                $name,
                $description,
                $price,
                $imageFilename,
                $isAvailable
            ]);
            
            if ($result) {
                $message = 'Menu item added successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to add menu item');
            }
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Delete menu item
if (isset($_GET['delete'])) {
    $itemId = (int) $_GET['delete'];
    
    try {
        $pdo = getDbConnection();
        
        // Check if item exists
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            throw new Exception('Menu item not found');
        }
        
        // Delete the item
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $result = $stmt->execute([$itemId]);
        
        if ($result) {
            $message = 'Menu item deleted successfully';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to delete menu item');
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all menu items with category names
try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("
        SELECT m.*, c.name as category_name
        FROM menu_items m
        JOIN categories c ON m.category_id = c.id
        ORDER BY c.display_order, m.name
    ");
    $menuItems = $stmt->fetchAll();
    
    // Get all categories for the form
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log('Menu items error: ' . $e->getMessage());
    $menuItems = [];
    $categories = [];
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-800">Menu Items</h1>
        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors" data-toggle="modal" data-target="#addItemModal">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>

    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-md flex items-start gap-3">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mt-0.5"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <!-- Menu Items Table -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">All Menu Items</h2>
        </div>
        <div class="p-6">
            <?php if (empty($menuItems)): ?>
                <div class="text-center p-8">
                    <i class="fas fa-utensils text-5xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500">No menu items found. Add some items to get started.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Image</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Price</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Available</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($menuItems as $item): ?>
                                <tr class="hover:bg-violet-50 transition-colors">
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($item['image']): ?>
                                            <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-12 h-12 object-cover rounded-md">
                                        <?php else: ?>
                                            <img src="<?php echo DEFAULT_IMAGE; ?>" alt="Default" class="w-12 h-12 object-cover rounded-md">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo $item['name']; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $item['category_name']; ?></td>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo formatPrice($item['price']); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer availability-toggle" 
                                                <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                                data-id="<?php echo $item['id']; ?>">
                                            <div class="relative w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-violet-600"></div>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                            <button class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded edit-item" 
                                                data-id="<?php echo $item['id']; ?>"
                                                data-category="<?php echo $item['category_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                                data-price="<?php echo $item['price']; ?>"
                                                data-available="<?php echo $item['is_available']; ?>"
                                                data-image="<?php echo $item['image']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $item['id']; ?>" class="inline-flex items-center justify-center w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded delete-item" 
                                                onclick="return confirm('Are you sure you want to delete this item?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="addItemModal">
    <div class="max-w-2xl w-full bg-white rounded-lg shadow-xl">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Add New Menu Item</h3>
            <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="" id="menuItemForm" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="id" id="item_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Item Name *</label>
                        <input type="text" id="name" name="name" required 
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                    </div>
                    
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1">Category *</label>
                        <select id="category_id" name="category_id" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="price" class="block text-sm font-medium text-slate-700 mb-1">Price *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                            <input type="number" id="price" name="price" min="0.01" step="0.01" required
                                class="w-full pl-8 px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="image" class="block text-sm font-medium text-slate-700 mb-1">Image</label>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                        <div id="current_image_container" class="hidden mt-2">
                            <p class="text-xs text-slate-500">Current Image:</p>
                            <div class="flex items-center mt-1">
                                <img id="current_image" src="" alt="Current" class="w-12 h-12 object-cover rounded-md">
                                <span id="image_name" class="ml-2 text-xs text-slate-500"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"></textarea>
                </div>
                
                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="is_available" name="is_available" value="1" class="sr-only peer" checked>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-700">Available for Order</span>
                    </label>
                </div>
                
                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> <span id="submit_text">Add Menu Item</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit item functionality
    const editButtons = document.querySelectorAll('.edit-item');
    const itemForm = document.getElementById('menuItemForm');
    const itemId = document.getElementById('item_id');
    const nameInput = document.getElementById('name');
    const categorySelect = document.getElementById('category_id');
    const priceInput = document.getElementById('price');
    const descriptionInput = document.getElementById('description');
    const availableCheck = document.getElementById('is_available');
    const submitText = document.getElementById('submit_text');
    const currentImageContainer = document.getElementById('current_image_container');
    const currentImage = document.getElementById('current_image');
    const imageName = document.getElementById('image_name');
    const uploadModal = document.getElementById('addItemModal');
    
    // Edit item click handler
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const category = this.dataset.category;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const price = this.dataset.price;
            const available = parseInt(this.dataset.available);
            const image = this.dataset.image;
            
            // Set form values
            itemId.value = id;
            nameInput.value = name;
            categorySelect.value = category;
            priceInput.value = price;
            descriptionInput.value = description;
            availableCheck.checked = available === 1;
            
            // Show current image if exists
            if (image) {
                currentImageContainer.classList.remove('hidden');
                currentImage.src = '../uploads/' + image;
                imageName.textContent = image;
            } else {
                currentImageContainer.classList.add('hidden');
            }
            
            // Change submit button text
            submitText.textContent = 'Update Menu Item';
            
            // Open the modal
            if (uploadModal) {
                uploadModal.classList.remove('hidden');
            }
        });
    });
    
    // Reset form when adding new item
    document.querySelector('[data-target="#addItemModal"]').addEventListener('click', function() {
        itemForm.reset();
        itemId.value = '';
        submitText.textContent = 'Add Menu Item';
        currentImageContainer.classList.add('hidden');
    });
    
    // Availability toggle
    const availabilityToggles = document.querySelectorAll('.availability-toggle');
    availabilityToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const itemId = this.dataset.id;
            const isAvailable = this.checked ? 1 : 0;
            
            // Send AJAX request to update availability
            fetch('update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'item_id=' + itemId + '&is_available=' + isAvailable
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert toggle if failed
                    this.checked = !this.checked;
                    alert('Failed to update availability: ' + data.message);
                }
            })
            .catch(error => {
                // Revert toggle if failed
                this.checked = !this.checked;
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
    
    // Confirm delete
    const deleteLinks = document.querySelectorAll('.delete-item');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 