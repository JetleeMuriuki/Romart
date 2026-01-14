<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

//cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                $cart_item_id = intval($_POST['cart_item_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($quantity > 0) {
                    $database->query('UPDATE cart_items SET quantity = :quantity WHERE id = :id AND user_id = :user_id');
                    $database->bind(':quantity', $quantity);
                    $database->bind(':id', $cart_item_id);
                    $database->bind(':user_id', $user_id);
                    $database->execute();
                } else {
                    $database->query('DELETE FROM cart_items WHERE id = :id AND user_id = :user_id');
                    $database->bind(':id', $cart_item_id);
                    $database->bind(':user_id', $user_id);
                    $database->execute();
                }
                break;
                
            case 'remove_item':
                $cart_item_id = intval($_POST['cart_item_id']);
                $database->query('DELETE FROM cart_items WHERE id = :id AND user_id = :user_id');
                $database->bind(':id', $cart_item_id);
                $database->bind(':user_id', $user_id);
                $database->execute();
                break;
                
            case 'clear_cart':
                $database->query('DELETE FROM cart_items WHERE user_id = :user_id');
                $database->bind(':user_id', $user_id);
                $database->execute();
                break;
        }
        
        // Redirect to avoid form resubmission
        header('Location: cart.php');
        exit();
    }
}

// Get cart items
try {
    $database->query('
        SELECT ci.*, fi.name, fi.description, fi.image_url 
        FROM cart_items ci 
        JOIN food_items fi ON ci.food_id = fi.id 
        WHERE ci.user_id = :user_id 
        ORDER BY ci.created_at DESC
    ');
    $database->bind(':user_id', $user_id);
    $cart_items = $database->resultSet();
} catch (Exception $e) {
    $cart_items = [];
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = 200; // KSh 200 delivery fee
$tax = $subtotal * 0.16; // 16% VAT
$total = $subtotal + $delivery_fee + $tax;

require_once '../include/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                </h2>
                <a href="../index.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
            
            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-shopping-cart text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                    </div>
                    <h4 class="mb-3">Your cart is empty</h4>
                    <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                    <a href="../menu.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-utensils me-2"></i>Browse Menu
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Clear Cart Button -->
                        <div class="d-flex justify-content-end mb-3">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                        onclick="return confirm('Are you sure you want to clear your cart?')">
                                    <i class="fas fa-trash me-1"></i>Clear Cart
                                </button>
                            </form>
                        </div>
                        
                        <!-- Cart Items -->
                        <div class="card">
                            <div class="card-body p-0">
                                <?php foreach ($cart_items as $index => $item): ?>
                                    <div class="cart-item p-4 <?php echo $index > 0 ? 'border-top' : ''; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 col-4 text-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="img-fluid rounded" style="max-height: 80px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                         style="height: 80px; width: 80px;">
                                                        <i class="fas fa-utensils text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4 col-8">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="text-muted small mb-1">
                                                    <?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>
                                                    <?php if (strlen($item['description']) > 60) echo '...'; ?>
                                                </p>
                                                <p class="text-primary mb-0 fw-bold">
                                                    KSh <?php echo number_format($item['price'], 2); ?>
                                                </p>
                                            </div>
                                            
                                            <div class="col-md-3 col-6">
                                                <div class="quantity-controls d-flex align-items-center">
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_quantity">
                                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm" 
                                                                <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </form>
                                                    
                                                    <span class="mx-3 fw-bold"><?php echo $item['quantity']; ?></span>
                                                    
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="update_quantity">
                                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-2 col-4 text-end">
                                                <p class="fw-bold mb-2">
                                                    KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                </p>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="remove_item">
                                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Remove this item from cart?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Order Summary -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>Order Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Delivery Fee:</span>
                                    <span>KSh <?php echo number_format($delivery_fee, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Tax (16%):</span>
                                    <span>KSh <?php echo number_format($tax, 2); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-4">
                                    <span class="fw-bold h5">Total:</span>
                                    <span class="fw-bold h5 text-primary">KSh <?php echo number_format($total, 2); ?></span>
                                </div>
                                
                                <a href="checkout.php" class="btn btn-primary w-100 btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </a>
                                
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Secure checkout with SSL encryption
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Promo Code -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-tag me-2"></i>Promo Code
                                </h6>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Enter promo code">
                                        <button class="btn btn-outline-secondary" type="button">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.cart-item {
    transition: background-color 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.quantity-controls button {
    width: 35px;
    height: 35px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .cart-item .row > div {
        margin-bottom: 1rem;
    }
    
    .cart-item .row > div:last-child {
        margin-bottom: 0;
    }
}
</style>

<?php require_once '../include/footer.php'; ?>
        