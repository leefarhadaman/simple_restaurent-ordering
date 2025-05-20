<?php
/**
 * Categories Management
 * Modern UI Implementation - Redesigned with Tailwind CSS
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

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-slate-800">Manage Categories</h1>
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors" data-toggle="modal" data-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> px-4 py-3 rounded-md flex items-start gap-3">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mt-0.5"></i>
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <!-- Categories List -->
    <div class="bg-white rounded-lg shadow border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-lg text-slate-800">Food Categories</h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">#</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Name</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Display Order</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Menu Items</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-5 text-center text-slate-500">No categories found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr class="hover:bg-violet-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-medium text-slate-700"><?php echo $index + 1; ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-slate-700"><?php echo $category['display_order']; ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if ($category['item_count'] > 0): ?>
                                            <a href="menu_items.php?category_id=<?php echo $category['id']; ?>" class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-800 text-xs font-medium">
                                                <?php echo $category['item_count']; ?> items
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                                                0 items
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                            <button class="inline-flex items-center justify-center w-8 h-8 bg-blue-500 hover:bg-blue-600 text-white rounded edit-category" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-order="<?php echo $category['display_order']; ?>"
                                                    data-toggle="modal" data-target="#editCategoryModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($category['item_count'] == 0): ?>
                                                <form method="post" action="" class="inline-block delete-category-form">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                    <button type="button" class="inline-flex items-center justify-center w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded delete-category-btn"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="inline-flex items-center justify-center w-8 h-8 bg-slate-300 text-slate-500 rounded cursor-not-allowed" disabled title="Cannot delete category with menu items">
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
</div>

<!-- Add Category Modal -->
<div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="addCategoryModal">
    <div class="max-w-md w-full bg-white rounded-lg shadow-xl">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Add New Category</h3>
            <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="" id="addCategoryForm" class="space-y-4">
                <input type="hidden" name="action" value="add_category">
                
                <div>
                    <label for="category_name" class="block text-sm font-medium text-slate-700 mb-1">Category Name</label>
                    <input type="text" id="category_name" name="category_name" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                        required>
                    <div class="hidden text-sm text-red-600 mt-1">Category name is required</div>
                </div>
                
                <div>
                    <label for="display_order" class="block text-sm font-medium text-slate-700 mb-1">Display Order</label>
                    <input type="number" id="display_order" name="display_order" min="1" value="1"
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                    <p class="text-xs text-slate-500 mt-1">Lower numbers will appear first in menu</p>
                </div>
                
                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal hidden fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" id="editCategoryModal">
    <div class="max-w-md w-full bg-white rounded-lg shadow-xl">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Edit Category</h3>
            <button type="button" class="text-slate-400 hover:text-slate-700" data-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="post" action="" id="editCategoryForm" class="space-y-4">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" id="edit_category_id" name="category_id" value="">
                
                <div>
                    <label for="edit_category_name" class="block text-sm font-medium text-slate-700 mb-1">Category Name</label>
                    <input type="text" id="edit_category_name" name="category_name" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500"
                        required>
                    <div class="hidden text-sm text-red-600 mt-1">Category name is required</div>
                </div>
                
                <div>
                    <label for="edit_display_order" class="block text-sm font-medium text-slate-700 mb-1">Display Order</label>
                    <input type="number" id="edit_display_order" name="display_order" min="1" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-500">
                    <p class="text-xs text-slate-500 mt-1">Lower numbers will appear first in menu</p>
                </div>
                
                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium rounded-md transition-colors" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-md transition-colors">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit category
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const order = this.dataset.order;
            
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_display_order').value = order;
        });
    });
    
    // Delete category confirmation
    document.querySelectorAll('.delete-category-btn').forEach(button => {
        button.addEventListener('click', function() {
            const name = this.dataset.name;
            
            if (confirm(`Are you sure you want to delete the category "${name}"?`)) {
                this.closest('form').submit();
            }
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                
                // Show validation errors
                const invalidInputs = form.querySelectorAll('input:invalid');
                invalidInputs.forEach(input => {
                    const feedback = input.nextElementSibling;
                    if (feedback && feedback.classList.contains('hidden')) {
                        feedback.classList.remove('hidden');
                    }
                });
            }
        });
        
        // Clear validation errors on input
        form.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const feedback = this.nextElementSibling;
                if (feedback && !feedback.classList.contains('hidden')) {
                    feedback.classList.add('hidden');
                }
            });
        });
    });
});
</script>

<?php
// Include footer
require_once 'templates/footer.php';
?> 