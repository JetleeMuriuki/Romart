<?php
session_start();
require_once 'include/db_connect.php';

// Get filter parameters from URL
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Build WHERE clause for filtering
$where_conditions = ['fi.is_available = 1'];
$bind_params = [];

if ($category_filter > 0) {
    $where_conditions[] = 'fi.category_id = :category_id';
    $bind_params[':category_id'] = $category_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = '(fi.name LIKE :search OR fi.description LIKE :search)';
    $bind_params[':search'] = '%' . $search_query . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Build ORDER BY clause
$order_clauses = [
    'name_asc' => 'fi.name ASC',
    'name_desc' => 'fi.name DESC', 
    'price_low' => 'fi.price ASC',
    'price_high' => 'fi.price DESC',
    'newest' => 'fi.created_at DESC'
];
$order_by = $order_clauses[$sort_by] ?? $order_clauses['name_asc'];

try {
    // Get categories for filter dropdown
    $database->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC');
    $categories = $database->resultSet();
    
    // Get food items with filters applied
    $database->query("
        SELECT fi.*, c.name as category_name 
        FROM food_items fi 
        LEFT JOIN categories c ON fi.category_id = c.id 
        $where_clause 
        ORDER BY $order_by
    ");
    
    foreach ($bind_params as $param => $value) {
        $database->bind($param, $value);
    }
    
    $food_items = $database->resultSet();
    
} catch (Exception $e) {
    $categories = [];
    $food_items = [];
    $error_message = 'Error loading menu items';
}

require_once 'include/header.php';
?>

<div class="container py-5">
    <div class="menu-page-wrapper">
        
        <!-- Header -->
        <div class="menu-page-header text-center mb-5">
            <h1 class="main-menu-title">Our Complete Menu</h1>
            <p class="main-menu-subtitle">Explore our full collection of delicious dishes</p>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar-container card mb-4">
            <div class="card-body">
                <form method="GET" class="menu-filter-form" id="menuFilterForm">
                    <div class="row align-items-end">
                        
                        <!-- Search Box -->
                        <div class="col-lg-4 col-md-6 mb-3">
                            <label class="filter-form-label">Search Dishes</label>
                            <div class="search-box-wrapper">
                                <input type="text" name="search" class="form-control search-box-input" 
                                       placeholder="Search for food items..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit" class="search-submit-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Category Dropdown -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="filter-form-label">Category</label>
                            <select name="category" class="form-select category-dropdown">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <label class="filter-form-label">Sort By</label>
                            <select name="sort" class="form-select sort-dropdown">
                                <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price (Low to High)</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price (High to Low)</option>
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="col-lg-2 col-md-6 mb-3">
                            <div class="filter-button-group">
                                <button type="submit" class="btn btn-primary apply-filter-btn">
                                    <i class="fas fa-filter me-1"></i>Apply
                                </button>
                                <a href="menu.php" class="btn btn-outline-secondary clear-filter-btn">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                        
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Information -->
        <div class="results-info-bar mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="results-count-text">
                    <span class="items-found-text">
                        Found <strong><?php echo count($food_items); ?></strong> 
                        <?php echo count($food_items) == 1 ? 'dish' : 'dishes'; ?>
                        <?php if ($category_filter > 0 || !empty($search_query)): ?>
                            <?php if ($category_filter > 0): ?>
                                in <strong><?php 
                                    $selected_category = array_filter($categories, function($c) use ($category_filter) {
                                        return $c['id'] == $category_filter;
                                    });
                                    if ($selected_category) {
                                        echo htmlspecialchars(current($selected_category)['name']);
                                    }
                                ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($search_query)): ?>
                                matching <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                            <?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <!-- Quick Sort Links -->
                <div class="quick-sort-links d-none d-md-flex">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_asc'])); ?>" 
                       class="quick-sort-link <?php echo $sort_by == 'name_asc' ? 'active' : ''; ?>">A-Z</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_low'])); ?>" 
                       class="quick-sort-link <?php echo $sort_by == 'price_low' ? 'active' : ''; ?>">Price ↑</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_high'])); ?>" 
                       class="quick-sort-link <?php echo $sort_by == 'price_high' ? 'active' : ''; ?>">Price ↓</a>
                </div>
            </div>
        </div>
        
        <!-- Menu Items Display -->
        <?php if (empty($food_items)): ?>
            <div class="no-items-found text-center py-5">
                <div class="no-items-icon mb-3">
                    <i class="fas fa-search text-muted"></i>
                </div>
                <h4 class="no-items-title">No dishes found</h4>
                <p class="no-items-message">
                    <?php if (!empty($search_query) || $category_filter > 0): ?>
                        Try changing your search terms or filters to find what you're looking for.
                    <?php else: ?>
                        Sorry, no menu items are available right now.
                    <?php endif; ?>
                </p>
                <a href="menu.php" class="btn btn-primary view-all-btn">
                    <i class="fas fa-utensils me-2"></i>View All Dishes
                </a>
            </div>
        <?php else: ?>
            <div class="menu-items-grid">
                <?php foreach ($food_items as $item): ?>
                    <div class="food-item-card" data-category="<?php echo $item['category_id']; ?>">
                        
                        <!-- Item Image Section -->
                        <div class="food-item-image-section">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="food-item-image">
                            <?php else: ?>
                                <div class="food-item-placeholder">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Hover Overlay -->
                            <div class="food-item-overlay">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-primary cart-add-button" 
                                            onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-primary login-first-button">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login to Order
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Item Details Section -->
                        <div class="food-item-details">
                            <div class="food-item-header">
                                <h5 class="food-item-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <div class="food-item-price">KSh <?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            
                            <?php if ($item['category_name']): ?>
                                <div class="food-item-category">
                                    <span class="category-tag"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <p class="food-item-description">
                                <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                <?php if (strlen($item['description']) > 100) echo '...'; ?>
                            </p>
                            
                            <!-- Item Action Buttons -->
                            <div class="food-item-actions">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-outline-primary quick-add-button" 
                                            onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-secondary details-button" 
                                        onclick="showFoodDetails(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-info-circle me-1"></i>Details
                                </button>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- Food Item Details Modal -->
<div class="modal fade" id="foodDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content food-modal-content">
            <div class="modal-header food-modal-header">
                <h5 class="modal-title food-modal-title" id="modalFoodName">Food Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body food-modal-body" id="modalFoodContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Menu Page Main Styles */
.menu-page-wrapper {
    max-width: 1400px;
    margin: 0 auto;
}

/* Page Header */
.menu-page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
}

.main-menu-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.main-menu-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Filter Bar */
.filter-bar-container {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-radius: 15px;
}

.filter-form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    display: block;
}

.search-box-wrapper {
    position: relative;
}

.search-box-input {
    padding-right: 50px;
    border-radius: 25px;
    border: 2px solid #e9ecef;
}

.search-box-input:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.15);
}

.search-submit-btn {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: #e74c3c;
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-dropdown, .sort-dropdown {
    border-radius: 10px;
    border: 2px solid #e9ecef;
}

.category-dropdown:focus, .sort-dropdown:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.15);
}

.filter-button-group {
    display: flex;
    gap: 0.5rem;
}

.apply-filter-btn {
    background: #e74c3c;
    border: none;
    border-radius: 10px;
    padding: 0.5rem 1rem;
}

.clear-filter-btn {
    border-radius: 10px;
    padding: 0.5rem 1rem;
}

/* Results Info */
.results-info-bar {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-radius: 10px;
}

.items-found-text {
    color: #6c757d;
    font-size: 1rem;
}

.quick-sort-links {
    gap: 0.5rem;
}

.quick-sort-link {
    padding: 0.4rem 0.8rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    text-decoration: none;
    color: #6c757d;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.quick-sort-link:hover, .quick-sort-link.active {
    background: #e74c3c;
    color: white;
    border-color: #e74c3c;
}

/* Menu Items Grid */
.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.food-item-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.food-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Food Item Image */
.food-item-image-section {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.food-item-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.food-item-card:hover .food-item-image {
    transform: scale(1.05);
}

.food-item-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #adb5bd;
}

.food-item-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.food-item-card:hover .food-item-overlay {
    opacity: 1;
}

.cart-add-button, .login-first-button {
    border-radius: 25px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transform: translateY(10px);
    transition: transform 0.3s ease;
}

.food-item-card:hover .cart-add-button,
.food-item-card:hover .login-first-button {
    transform: translateY(0);
}

/* Food Item Details */
.food-item-details {
    padding: 1.5rem;
}

.food-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.food-item-name {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
    flex-grow: 1;
    margin-right: 1rem;
}

.food-item-price {
    font-size: 1.3rem;
    font-weight: 700;
    color: #e74c3c;
    white-space: nowrap;
}

.food-item-category {
    margin-bottom: 0.5rem;
}

.category-tag {
    background: #e9ecef;
    color: #6c757d;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.food-item-description {
    color: #6c757d;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.food-item-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quick-add-button {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.details-button {
    border-radius: 20px;
    padding: 0.5rem 1rem;
}

/* No Items Found */
.no-items-found {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 3rem 2rem;
}

.no-items-icon i {
    font-size: 4rem;
    opacity: 0.3;
}

.no-items-title {
    color: #6c757d;
    margin-bottom: 1rem;
}

.no-items-message {
    color: #6c757d;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.view-all-btn {
    padding: 0.75rem 2rem;
    border-radius: 25px;
}

/* Modal Styles */
.food-modal-content {
    border-radius: 15px;
    overflow: hidden;
}

.food-modal-header {
    background: linear-gradient(45deg, #e74c3c, #c0392b);
    color: white;
}

.food-modal-title {
    font-weight: 600;
}

.food-modal-body {
    padding: 2rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .menu-page-header {
        padding: 2rem 1rem;
        margin: 0 1rem 2rem 1rem;
    }
    
    .main-menu-title {
        font-size: 2rem;
    }
    
    .menu-items-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 0 1rem;
    }
    
    .filter-button-group {
        flex-direction: column;
    }
    
    .filter-button-group .btn {
        margin-bottom: 0.5rem;
    }
    
    .quick-sort-links {
        display: none !important;
    }
    
    .results-info-bar .d-flex {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Menu page JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when dropdown changes
    const categorySelect = document.querySelector('.category-dropdown');
    const sortSelect = document.querySelector('.sort-dropdown');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            document.getElementById('menuFilterForm').submit();
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            document.getElementById('menuFilterForm').submit();
        });
    }
    
    // Animate menu items when page loads
    const menuItems = document.querySelectorAll('.food-item-card');
    menuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.6s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Search on Enter key
    const searchInput = document.querySelector('.search-box-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('menuFilterForm').submit();
            }
        });
    }
});

// Show food item details in modal
function showFoodDetails(itemId) {
    fetch('get_item_details.php?id=' + itemId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = data.item;
                document.getElementById('modalFoodName').textContent = item.name;
                
                const modalContent = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="modal-food-image">
                                ${item.image_url ? 
                                    `<img src="${item.image_url}" alt="${item.name}" class="img-fluid rounded">` :
                                    `<div class="modal-image-placeholder"><i class="fas fa-utensils"></i></div>`
                                }
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="modal-food-info">
                                <h5 class="modal-food-price">KSh ${parseFloat(item.price).toLocaleString()}</h5>
                                ${item.category_name ? `<span class="badge bg-secondary mb-3">${item.category_name}</span>` : ''}
                                <p class="modal-food-description">${item.description}</p>
                                
                                ${isUserLoggedIn() ? `
                                    <div class="modal-food-actions mt-4">
                                        <div class="quantity-selector-wrapper mb-3">
                                            <label class="form-label">Quantity:</label>
                                            <div class="input-group" style="max-width: 150px;">
                                                <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(-1)">-</button>
                                                <input type="number" class="form-control text-center" id="modalQuantityInput" value="1" min="1">
                                                <button class="btn btn-outline-secondary" type="button" onclick="changeQuantity(1)">+</button>
                                            </div>
                                        </div>
                                        <button class="btn btn-primary btn-lg" onclick="addToCartFromModal(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.price})">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    </div>
                                ` : `
                                    <div class="modal-food-actions mt-4">
                                        <a href="auth/login.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Order
                                        </a>
                                    </div>
                                `}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('modalFoodContent').innerHTML = modalContent;
                
                const modal = new bootstrap.Modal(document.getElementById('foodDetailsModal'));
                modal.show();
            } else {
                alert('Could not load food details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading food details');
        });
}

// Check if user is logged in
function isUserLoggedIn() {
    return <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
}

// Change quantity in modal
function changeQuantity(change) {
    const quantityInput = document.getElementById('modalQuantityInput');
    const currentValue = parseInt(quantityInput.value);
    const newValue = Math.max(1, currentValue + change);
    quantityInput.value = newValue;
}

// Add to cart from modal with quantity
function addToCartFromModal(foodId, foodName, price) {
    const quantity = parseInt(document.getElementById('modalQuantityInput').value);
    
    fetch('actions/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            food_id: foodId,
            food_name: foodName,
            price: price,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cartCount);
            showAlert('Added to cart successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('foodDetailsModal'));
            modal.hide();
        } else {
            showAlert(data.message || 'Failed to add item to cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error adding item to cart', 'danger');
    });
}
</script>

<style>
/* Additional Modal Styles */
.modal-food-image {
    max-height: 300px;
    overflow: hidden;
    border-radius: 10px;
}

.modal-image-placeholder {
    height: 300px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #adb5bd;
    border-radius: 10px;
}

.modal-food-price {
    font-size: 1.8rem;
    font-weight: 700;
    color: #e74c3c;
    margin-bottom: 1rem;
}

.modal-food-description {
    line-height: 1.7;
    color: #6c757d;
}

.quantity-selector-wrapper .input-group {
    border-radius: 8px;
    overflow: hidden;
}

.quantity-selector-wrapper .form-control {
    border-left: none;
    border-right: none;
}

.quantity-selector-wrapper button {
    border-radius: 0;
}
</style>

<?php require_once 'include/footer.php'; ?>