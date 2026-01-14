<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        try {
            $database->query('INSERT INTO categories (name, description, is_active) VALUES (:name, :description, 1)');
            $database->bind(':name', $name);
            $database->bind(':description', $description);
            
            if ($database->execute()) {
                $message = 'Category added successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error adding category';
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        try {
            $database->query('UPDATE categories SET name = :name, description = :description WHERE id = :id');
            $database->bind(':name', $name);
            $database->bind(':description', $description);
            $database->bind(':id', $id);
            
            if ($database->execute()) {
                $message = 'Category updated successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error updating category';
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $id = intval($_POST['category_id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status == 1 ? 0 : 1;
        
        try {
            $database->query('UPDATE categories SET is_active = :status WHERE id = :id');
            $database->bind(':status', $new_status);
            $database->bind(':id', $id);
            $database->execute();
            $message = 'Category status updated!';
        } catch (Exception $e) {
            $error = 'Error updating status';
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = intval($_POST['category_id']);
        
        // Check if category has food items
        try {
            $database->query('SELECT COUNT(*) as count FROM food_items WHERE category_id = :id');
            $database->bind(':id', $id);
            $result = $database->single();
            
            if ($result['count'] > 0) {
                $error = 'Cannot delete category. It has ' . $result['count'] . ' food items. Remove or reassign the items first.';
            } else {
                $database->query('DELETE FROM categories WHERE id = :id');
                $database->bind(':id', $id);
                $database->execute();
                $message = 'Category deleted successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error deleting category';
        }
    }
}

// Get all categories with food count
try {
    $database->query('
        SELECT c.*, COUNT(fi.id) as food_count 
        FROM categories c 
        LEFT JOIN food_items fi ON c.id = fi.category_id 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ');
    $categories = $database->resultSet();
} catch (Exception $e) {
    $categories = [];
}

include 'admin_header.php';
?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="page-title">
                <i class="fas fa-list me-2"></i>Manage Categories
            </h2>
            <p class="page-subtitle">Organize your menu into categories</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus me-2"></i>Add New Category
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Categories Grid -->
<div class="content-card">
    <div class="card-header-custom mb-3">
        <h3 class="card-title">
            <i class="fas fa-folder me-2"></i>Categories (<?php echo count($categories); ?>)
        </h3>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="text-center py-5">
            <i class="fas fa-folder-open text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="mt-3 text-muted">No categories yet</h4>
            <p class="text-muted">Click "Add New Category" to create your first category</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Food Items</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td><span class="badge bg-info"><?php echo $category['food_count']; ?> items</span></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $category['is_active']; ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $category['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editCategory(<?php echo json_encode($category); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}
</script>

<?php include 'admin_footer.php'; ?>