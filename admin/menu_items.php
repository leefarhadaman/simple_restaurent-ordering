<?php
/**
 * Menu Items Management
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

<div class="page-header">
    <h1 class="page-title">Menu Items</h1>
    <div>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addItemModal">
            <i class="fas fa-plus"></i> Add New Item
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Menu Items Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Menu Items</h2>
    </div>
    <div class="card-body">
        <?php if (empty($menuItems)): ?>
            <p>No menu items found. Add some items to get started.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                            <tr>
                                <td style="width: 80px;">
                                    <?php if ($item['image']): ?>
                                        <img src="../uploads/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <img src="<?php echo DEFAULT_IMAGE; ?>" alt="Default" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['name']; ?></td>
                                <td><?php echo $item['category_name']; ?></td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input availability-toggle" 
                                            <?php echo $item['is_available'] ? 'checked' : ''; ?> 
                                            data-id="<?php echo $item['id']; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="table-action">
                                        <button class="btn btn-sm btn-info edit-item" 
                                            data-id="<?php echo $item['id']; ?>"
                                            data-category="<?php echo $item['category_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                            data-price="<?php echo $item['price']; ?>"
                                            data-available="<?php echo $item['is_available']; ?>"
                                            data-image="<?php echo $item['image']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger delete-item">
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

<!-- Add/Edit Item Modal -->
<div class="modal" id="itemModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Menu Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="item_id" name="id" value="0">
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Item Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Image</label>
                        <div class="image-preview">
                            <img id="image_preview" src="<?php echo DEFAULT_IMAGE; ?>" alt="Preview" style="max-width: 100%; max-height: 200px; display: none;">
                            <div id="image_placeholder" class="image-preview-placeholder">
                                <i class="fas fa-image"></i>
                                <p>No image selected</p>
                            </div>
                        </div>
                        <input type="file" id="image" name="image" class="form-control-file" accept="image/*">
                        <small class="form-text text-muted">Maximum file size: 300KB. Larger images will be compressed.</small>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="is_available" name="is_available" class="form-check-input" checked>
                        <label for="is_available" class="form-check-label">Item is available</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="alert-container"></div>

<!-- Custom JavaScript for this page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show modal when Add New Item button is clicked
    document.querySelector('.page-header .btn-primary').addEventListener('click', function() {
        openItemModal();
    });
    
    // Handle edit item button clicks
    document.querySelectorAll('.edit-item').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const categoryId = this.getAttribute('data-category');
            const name = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');
            const price = this.getAttribute('data-price');
            const available = this.getAttribute('data-available') === '1';
            const image = this.getAttribute('data-image');
            
            openItemModal(id, categoryId, name, description, price, available, image);
        });
    });
    
    // Image preview
    document.getElementById('image').addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('image_preview');
        const placeholder = document.getElementById('image_placeholder');
        
        if (file) {
            const reader = new FileReader();
            
            reader.addEventListener('load', function() {
                preview.src = this.result;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            });
            
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'block';
        }
    });
    
    // Function to open modal with item data
    function openItemModal(id = 0, categoryId = '', name = '', description = '', price = '', available = true, image = '') {
        // Reset form
        document.querySelector('#itemModal form').reset();
        
        // Set modal title
        document.getElementById('modalTitle').textContent = id > 0 ? 'Edit Menu Item' : 'Add New Menu Item';
        
        // Set form values
        document.getElementById('item_id').value = id;
        document.getElementById('category_id').value = categoryId;
        document.getElementById('name').value = name;
        document.getElementById('description').value = description;
        document.getElementById('price').value = price;
        document.getElementById('is_available').checked = available;
        
        // Image preview
        const preview = document.getElementById('image_preview');
        const placeholder = document.getElementById('image_placeholder');
        
        if (image) {
            preview.src = '../uploads/' + image;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            preview.style.display = 'none';
            placeholder.style.display = 'block';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('itemModal'));
        modal.show();
    }
});
</script>

<!-- Bootstrap JS for Modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php require_once 'templates/footer.php'; ?> 