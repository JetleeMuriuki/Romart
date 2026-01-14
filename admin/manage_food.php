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

// Define upload directory
$upload_dir = '../uploads/food_images/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Add Food Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_food'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image_url = '';
    
    // Handle file upload
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] == 0) {
        $file_tmp = $_FILES['food_image']['tmp_name'];
        $file_name = $_FILES['food_image']['name'];
        $file_size = $_FILES['food_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            if ($file_size <= 5000000) { // 5MB max
                // Generate unique filename
                $new_filename = uniqid('food_', true) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $image_url = 'uploads/food_images/' . $new_filename;
                } else {
                    $error = 'Failed to upload image';
                }
            } else {
                $error = 'Image size must be less than 5MB';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed';
        }
    }
    
    if (empty($error)) {
        try {
            $database->query('INSERT INTO food_items (name, description, price, category_id, image_url, is_available) VALUES (:name, :description, :price, :category_id, :image_url, 1)');
            $database->bind(':name', $name);
            $database->bind(':description', $description);
            $database->bind(':price', $price);
            $database->bind(':category_id', $category_id);
            $database->bind(':image_url', $image_url);
            
            if ($database->execute()) {
                $message = 'Food item added successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error adding food item: ' . $e->getMessage();
        }
    }
}

// Handle Edit Food Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_food'])) {
    $id = intval($_POST['food_id']);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $current_image = $_POST['current_image'];
    $image_url = $current_image;
    
    // Handle new file upload
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] == 0) {
        $file_tmp = $_FILES['food_image']['tmp_name'];
        $file_name = $_FILES['food_image']['name'];
        $file_size = $_FILES['food_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            if ($file_size <= 5000000) {
                $new_filename = uniqid('food_', true) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete old image if exists
                    if ($current_image && file_exists('../' . $current_image)) {
                        unlink('../' . $current_image);
                    }
                    $image_url = 'uploads/food_images/' . $new_filename;
                } else {
                    $error = 'Failed to upload new image';
                }
            } else {
                $error = 'Image size must be less than 5MB';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed';
        }
    }
    
    if (empty($error)) {
        try {
            $database->query('UPDATE food_items SET name = :name, description = :description, price = :price, category_id = :category_id, image_url = :image_url WHERE id = :id');
            $database->bind(':name', $name);
            $database->bind(':description', $description);
            $database->bind(':price', $price);
            $database->bind(':category_id', $category_id);
            $database->bind(':image_url', $image_url);
            $database->bind(':id', $id);
            
            if ($database->execute()) {
                $message = 'Food item updated successfully!';
            }
        } catch (Exception $e) {
            $error = 'Error updating food item: ' . $e->getMessage();
        }
    }
}

// Handle Toggle Availability
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_availability'])) {
    $id = intval($_POST['food_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status == 1 ? 0 : 1;
    
    try {
        $database->query('UPDATE food_items SET is_available = :status WHERE id = :id');
        $database->bind(':status', $new_status);
        $database->bind(':id', $id);
        $database->execute();
        $message = 'Food item availability updated!';
    } catch (Exception $e) {
        $error = 'Error updating availability';
    }
}

// Handle Delete Food Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_food'])) {
    $id = intval($_POST['food_id']);
    
    try {
        // Get image path before deleting
        $database->query('SELECT image_url FROM food_items WHERE id = :id');
        $database->bind(':id', $id);
        $food = $database->single();
        
        // Delete from database
        $database->query('DELETE FROM food_items WHERE id = :id');
        $database->bind(':id', $id);
        $database->execute();
        
        // Delete image file if exists
        if ($food && $food['image_url'] && file_exists('../' . $food['image_url'])) {
            unlink('../' . $food['image_url']);
        }
        
        $message = 'Food item deleted successfully!';
    } catch (Exception $e) {
        $error = 'Error deleting food item';
    }
}

// Get categories for dropdown
try {
    $database->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC');
    $categories = $database->resultSet();
} catch (Exception $e) {
    $categories = [];
}

// Get all food items
try {
    $database->query('
        SELECT fi.*, c.name as category_name 
        FROM food_items fi 
        LEFT JOIN categories c ON fi.category_id = c.id 
        ORDER BY fi.created_at DESC
    ');
    $food_items = $database->resultSet();
} catch (Exception $e) {
    $food_items = [];
}

include 'admin_header.php';
?>

<div class="admin-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="page-title">
                <i class="fas fa-hamburger me-2"></i>Manage Food Items
            </h2>
            <p class="page-subtitle">Add, edit, and manage your menu items</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFoodModal">
            <i class="fas fa-plus me-2"></i>Add New Item
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

<!-- Food Items Grid -->
<div class="content-card">
    <div class="card-header-custom mb-3">
        <h3 class="card-title">
            <i class="fas fa-list me-2"></i>Food Items (<?php echo count($food_items); ?>)
        </h3>
    </div>
    
    <?php if (empty($food_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-utensils text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="mt-3 text-muted">No food items yet</h4>
            <p class="text-muted">Click "Add New Item" to create your first menu item</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($food_items as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['image_url']): ?>
                                    <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                         onerror="this.src='../assets/images/placeholder.png'">
                                <?php else: ?>
                                    <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</small>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><strong>KSh <?php echo number_format($item['price'], 2); ?></strong></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $item['is_available']; ?>">
                                    <button type="submit" name="toggle_availability" class="btn btn-sm <?php echo $item['is_available'] ? 'btn-success' : 'btn-secondary'; ?>">
                                        <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick='editFood(<?php echo json_encode($item); ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?')">
                                    <input type="hidden" name="food_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_food" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
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

<!-- Add Food Modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Food Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Food Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (KSh) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Food Image</label>
                        <input type="file" name="food_image" class="form-control" accept="image/*" onchange="previewImage(this, 'addPreview')">
                        <small class="text-muted">Allowed: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
                        <div id="addPreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_food" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Add Food Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Food Modal -->
<div class="modal fade" id="editFoodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Food Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="food_id" id="edit_food_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Food Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (KSh) *</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div id="current_image_preview" class="mb-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Change Image (optional)</label>
                        <input type="file" name="food_image" class="form-control" accept="image/*" onchange="previewImage(this, 'editPreview')">
                        <small class="text-muted">Leave empty to keep current image. Allowed: JPG, JPEG, PNG, GIF, WEBP (Max 5MB)</small>
                        <div id="editPreview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_food" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Food Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.image-preview {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    border: 2px solid #dee2e6;
}
</style>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'image-preview';
            preview.appendChild(img);
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

function editFood(item) {
    document.getElementById('edit_food_id').value = item.id;
    document.getElementById('edit_name').value = item.name;
    document.getElementById('edit_description').value = item.description;
    document.getElementById('edit_price').value = item.price;
    document.getElementById('edit_category_id').value = item.category_id;
    document.getElementById('edit_current_image').value = item.image_url || '';
    
    // Show current image
    const currentImagePreview = document.getElementById('current_image_preview');
    if (item.image_url) {
        currentImagePreview.innerHTML = `<img src="../${item.image_url}" class="image-preview" alt="Current image">`;
    } else {
        currentImagePreview.innerHTML = '<p class="text-muted">No image uploaded</p>';
    }
    
    // Clear new image preview
    document.getElementById('editPreview').innerHTML = '';
    
    const modal = new bootstrap.Modal(document.getElementById('editFoodModal'));
    modal.show();
}
</script>

<?php include 'admin_footer.php'; ?>