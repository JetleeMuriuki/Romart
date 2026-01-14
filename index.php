<?php
require_once 'include/header.php';

// Get featured/popular food items
try {
    $database->query('SELECT * FROM food_items WHERE is_available = 1 ORDER BY created_at DESC LIMIT 8');
    $food_items = $database->resultSet();
} catch (Exception $e) {
    $food_items = [];
}

// Get categories
try {
    $database->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC');
    $categories = $database->resultSet();
} catch (Exception $e) {
    $categories = [];
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    Delicious Food <br>
                    <span class="text-warning">Delivered Fast</span>
                </h1>
                <p class="lead mb-4">
                    Experience the finest cuisines delivered to your doorstep. 
                    Fresh ingredients, expert preparation, lightning-fast delivery.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="menu.php" class="btn btn-warning btn-lg px-4">
                        <i class="fas fa-utensils me-2"></i>View Menu
                    </a>
                    <a href="#about" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-info-circle me-2"></i>Learn More
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image mt-5 mt-lg-0">
                    <i class="fas fa-pizza-slice" style="font-size: 15rem; opacity: 0.1;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-lg-4 mb-4">
                <div class="feature-box p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-shipping-fast text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Fast Delivery</h5>
                    <p class="text-muted">Get your food delivered within 30 minutes or it's free!</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="feature-box p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-leaf text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Fresh Ingredients</h5>
                    <p class="text-muted">We use only the freshest, locally sourced ingredients.</p>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="feature-box p-4">
                    <div class="feature-icon mb-3">
                        <i class="fas fa-star text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h5>Quality Assured</h5>
                    <p class="text-muted">Every dish is prepared by expert chefs with love and care.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Menu Section -->
<section id="menu" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Our <span class="text-primary">Menu</span></h2>
                <p class="lead text-muted">Discover our delicious selection of dishes</p>
            </div>
        </div>
        
        <!-- Category Filter -->
        <?php if ($categories): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-outline-primary category-filter active" data-category="all">
                        All Items
                    </button>
                    <?php foreach ($categories as $category): ?>
                        <button class="btn btn-outline-primary category-filter" data-category="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Food Items -->
        <div class="row" id="foodItemsContainer">
            <?php if ($food_items): ?>
                <?php foreach ($food_items as $item): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4 food-item" data-category="<?php echo $item['category_id']; ?>">
                        <div class="card food-card h-100">
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="img-fluid" style="max-height: 180px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-utensils text-muted" style="font-size: 3rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                    <?php if (strlen($item['description']) > 100) echo '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="h5 text-primary mb-0">
                                        KSh <?php echo number_format($item['price'], 2); ?>
                                    </span>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="auth/login.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-sign-in-alt"></i> Login
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <h5>No food items available at the moment</h5>
                        <p class="mb-0">Please check back later for delicious options!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <div class="col-12 text-center">
                <a href="menu.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-eye me-2"></i>View Full Menu
                </a>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="display-5 fw-bold mb-4">About <span class="text-primary">Romart</span></h2>
                <p class="lead mb-4">
                    We are passionate about delivering exceptional culinary experiences right to your doorstep. 
                    Since our establishment, we have been committed to using the finest ingredients and 
                    traditional cooking methods to create memorable meals.
                </p>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">500+</h6>
                                <small class="text-muted">Happy Customers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">24/7</h6>
                                <small class="text-muted">Service Available</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="bg-primary text-white rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                     style="width: 300px; height: 300px;">
                    <i class="fas fa-chef-hat" style="font-size: 8rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Get In <span class="text-primary">Touch</span></h2>
                <p class="lead text-muted">We'd love to hear from you</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-map-marker-alt text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h5>Visit Us</h5>
                    <p class="text-muted">123 Food Street<br>Karen, Nairobi, Kenya</p>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-phone text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h5>Call Us</h5>
                    <p class="text-muted">+254 700 123 456<br>Available 24/7</p>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-envelope text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <h5>Email Us</h5>
                    <p class="text-muted">info@romartprime.com<br>support@romartprime.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Category filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const categoryButtons = document.querySelectorAll('.category-filter');
    const foodItems = document.querySelectorAll('.food-item');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active button
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter food items
            foodItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                    // fade in animation
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.style.opacity = '1';
                    }, 100);
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once 'include/footer.php'; ?>