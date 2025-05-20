<?php
/**
 * Categories Management
 * Modern UI Implementation - Redesigned
 */
require_once '../includes/config.php';

// Require login
requireLogin();

// Initialize variables
$message = '';
$messageType = '';
$categories = [];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDbConnection();
        
        // Add new category
        if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
            $categoryName = sanitizeInput($_POST['category_name']);
            $displayOrder = (int)$_POST['display_order'];
            
            if (empty($categoryName)) {
                throw new Exception('Category name is required');
            }
            
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$categoryName]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('Category with this name already exists');
            }
            
            // Add new category
            $stmt = $pdo->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
            
            if ($stmt->execute([$categoryName, $displayOrder])) {
                $message = 'Category added successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to add category');
            }
        }
        
        // Update category
        if (isset($_POST['action']) && $_POST['action'] === 'update_category') {
            $categoryId = (int)$_POST['category_id'];
            $categoryName = sanitizeInput($_POST['category_name']);
            $displayOrder = (int)$_POST['display_order'];
            
            if (empty($categoryName)) {
                throw new Exception('Category name is required');
            }
            
            // Check if category with this name already exists but with a different ID
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $stmt->execute([$categoryName, $categoryId]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('Another category with this name already exists');
            }
            
            // Update category
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, display_order = ? WHERE id = ?");
            
            if ($stmt->execute([$categoryName, $displayOrder, $categoryId])) {
                $message = 'Category updated successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to update category');
            }
        }
        
        // Delete category
        if (isset($_POST['action']) && $_POST['action'] === 'delete_category') {
            $categoryId = (int)$_POST['category_id'];
            
            // Check if category has menu items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                throw new Exception("Cannot delete category. It has {$count} menu items. Delete or reassign the menu items first.");
            }
            
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            
            if ($stmt->execute([$categoryId])) {
                $message = 'Category deleted successfully';
                $messageType = 'success';
            } else {
                throw new Exception('Failed to delete category');
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Get all categories
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("
        SELECT c.*, COUNT(m.id) as item_count 
        FROM categories c
        LEFT JOIN menu_items m ON c.id = m.category_id
        GROUP BY c.id
        ORDER BY c.display_order, c.name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = 'Error fetching categories: ' . $e->getMessage();
    $messageType = 'danger';
}

// Get settings
$settings = getThemeSettings();

// Include header template
require_once 'templates/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Manage Categories</h1>
    <button class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Categories List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Food Categories</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Display Order</th>
                        <th>Menu Items</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No categories found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo $category['display_order']; ?></td>
                                <td>
                                    <?php if ($category['item_count'] > 0): ?>
                                        <a href="menu_items.php?category_id=<?php echo $category['id']; ?>" class="badge badge-primary">
                                            <?php echo $category['item_count']; ?> items
                                        </a>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">0 items</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-action">
                                        <button class="btn btn-sm btn-info edit-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-order="<?php echo $category['display_order']; ?>"
                                                data-toggle="modal" data-target="#editCategoryModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($category['item_count'] == 0): ?>
                                            <form method="post" action="" class="d-inline delete-category-form">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="button" class="btn btn-sm btn-danger delete-category-btn"
                                                        data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete category with menu items">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal" id="addCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Category</h3>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="addCategoryForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-group">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                        <div class="invalid-feedback">Category name is required</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" id="display_order" name="display_order" class="form-control" min="1" value="1">
                        <small class="form-text">Lower numbers will appear first in menu</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal" id="editCategoryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Category</h3>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form method="post" action="" id="editCategoryForm" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_category">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="form-group">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" id="edit_category_name" name="category_name" class="form-control" required>
                        <div class="invalid-feedback">Category name is required</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_display_order" class="form-label">Display Order</label>
                        <input type="number" id="edit_display_order" name="display_order" class="form-control" min="1" value="1">
                        <small class="form-text">Lower numbers will appear first in menu</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const addCategoryForm = document.getElementById('addCategoryForm');
    const editCategoryForm = document.getElementById('editCategoryForm');
    
    // Add form validation
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    }
    
    // Edit form validation
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            this.classList.add('was-validated');
        });
    }
    
    // Populate edit modal with category data
    const editButtons = document.querySelectorAll('.edit-category');
    if (editButtons.length) {
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                const categoryName = this.getAttribute('data-name');
                const displayOrder = this.getAttribute('data-order');
                
                document.getElementById('edit_category_id').value = categoryId;
                document.getElementById('edit_category_name').value = categoryName;
                document.getElementById('edit_display_order').value = displayOrder;
            });
        });
    }
    
    // Delete category confirmation
    const deleteButtons = document.querySelectorAll('.delete-category-btn');
    if (deleteButtons.length) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryName = this.getAttribute('data-name');
                
                if (confirm(`Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`)) {
                    this.closest('form').submit();
                }
            });
        });
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 