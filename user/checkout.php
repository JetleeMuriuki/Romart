<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = isset($_SESSION['checkout_error']) ? $_SESSION['checkout_error'] : '';
unset($_SESSION['checkout_error']);

// Get user info
try {
    $database->query('SELECT * FROM users WHERE id = :user_id');
    $database->bind(':user_id', $user_id);
    $user = $database->single();
} catch (Exception $e) {
    $error = 'Error fetching user information';
}

// Get cart items
try {
    $database->query('
        SELECT ci.*, fi.name, fi.description 
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

// Redirect if cart is empty
if (empty($cart_items)) {
    $_SESSION['checkout_error'] = 'Your cart is empty';
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$delivery_fee = 200;
$tax = $subtotal * 0.16;
$total = $subtotal + $delivery_fee + $tax;

require_once '../include/header.php';
?>

<div class="container py-5">
    <div class="checkout-page-container">
        <div class="checkout-header mb-4">
            <h2 class="checkout-title">
                <i class="fas fa-credit-card me-2"></i>Checkout
            </h2>
            <p class="checkout-subtitle">Complete your order</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <form method="POST" action="../actions/place_order.php" id="checkoutForm">
                    <!-- Delivery Information -->
                    <div class="card checkout-card mb-4">
                        <div class="card-header checkout-card-header">
                            <h5 class="card-header-title mb-0">
                                <i class="fas fa-truck me-2"></i>Delivery Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone_number" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                           placeholder="+254 700 000 000"
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address *</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" 
                                          placeholder="Please provide detailed delivery address including building name, floor, apartment number, etc." 
                                          required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-0">
                                <label for="notes" class="form-label">Special Instructions (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" 
                                          placeholder="Any special instructions for preparation or delivery..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="card checkout-card mb-4">
                        <div class="card-header checkout-card-header">
                            <h5 class="card-header-title mb-0">
                                <i class="fas fa-credit-card me-2"></i>Payment Method
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                        <label class="payment-label" for="cash">
                                            <div class="payment-icon">
                                                <i class="fas fa-money-bill-wave text-success"></i>
                                            </div>
                                            <div class="payment-text">
                                                <strong>Cash on Delivery</strong>
                                                <small class="d-block text-muted">Pay when you receive</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" id="mpesa" value="mpesa">
                                        <label class="payment-label" for="mpesa">
                                            <div class="payment-icon">
                                                <i class="fas fa-mobile-alt text-success"></i>
                                            </div>
                                            <div class="payment-text">
                                                <strong>M-Pesa</strong>
                                                <small class="d-block text-muted">Mobile payment</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <div class="payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" id="card" value="card">
                                        <label class="payment-label" for="card">
                                            <div class="payment-icon">
                                                <i class="fas fa-credit-card text-primary"></i>
                                            </div>
                                            <div class="payment-text">
                                                <strong>Card Payment</strong>
                                                <small class="d-block text-muted">Visa, Mastercard</small>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Payment Note:</strong> For M-Pesa and Card payments, you will receive payment instructions after placing your order.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="checkout-actions d-flex gap-3">
                        <a href="cart.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cart
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                            <i class="fas fa-check me-2"></i>Place Order
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card checkout-summary-card sticky-top" style="top: 20px;">
                    <div class="card-header checkout-card-header">
                        <h5 class="card-header-title mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="summary-items mb-3">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="summary-item d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div class="item-details flex-grow-1">
                                        <h6 class="item-name mb-0 small"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × KSh <?php echo number_format($item['price'], 2); ?></small>
                                    </div>
                                    <span class="item-total fw-bold">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Price Breakdown -->
                        <div class="price-breakdown">
                            <div class="breakdown-row d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>KSh <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="breakdown-row d-flex justify-content-between mb-2">
                                <span>Delivery Fee:</span>
                                <span>KSh <?php echo number_format($delivery_fee, 2); ?></span>
                            </div>
                            <div class="breakdown-row d-flex justify-content-between mb-3">
                                <span>Tax (16%):</span>
                                <span>KSh <?php echo number_format($tax, 2); ?></span>
                            </div>
                            <hr>
                            <div class="breakdown-total d-flex justify-content-between mb-3">
                                <span class="fw-bold h5">Total:</span>
                                <span class="fw-bold h5 text-primary">KSh <?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="delivery-estimate text-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Estimated delivery: 30-45 minutes
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Security Notice -->
                <div class="card mt-4">
                    <div class="card-body text-center security-notice">
                        <i class="fas fa-shield-alt text-success mb-2" style="font-size: 2rem;"></i>
                        <h6>Secure Checkout</h6>
                        <p class="small text-muted mb-0">
                            Your personal and payment information is protected with industry-standard SSL encryption.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Checkout Page Styles */
.checkout-page-container {
    max-width: 1200px;
    margin: 0 auto;
}

.checkout-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
}

.checkout-title {
    color: #2c3e50;
    font-weight: 600;
}

.checkout-subtitle {
    color: #7f8c8d;
    margin-bottom: 0;
}

.checkout-card {
    border: none;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border-radius: 12px;
}

.checkout-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.25rem;
}

.card-header-title {
    color: #495057;
    font-weight: 600;
}

/* Payment Options */
.payment-option {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 100%;
}

.payment-option:hover {
    border-color: #e74c3c;
    background: #fff5f5;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-option input[type="radio"]:checked + .payment-label {
    color: #e74c3c;
}

.payment-option:has(input:checked) {
    border-color: #e74c3c;
    background: #fff5f5;
}

.payment-label {
    cursor: pointer;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.payment-icon {
    font-size: 2rem;
}

.payment-text strong {
    display: block;
    margin-bottom: 0.25rem;
}

/* Order Summary */
.checkout-summary-card {
    border: none;
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    border-radius: 12px;
}

.summary-items {
    max-height: 300px;
    overflow-y: auto;
}

.summary-item {
    padding: 0.75rem 0;
}

.item-name {
    font-weight: 500;
    color: #2c3e50;
}

.item-total {
    color: #e74c3c;
}

.breakdown-row {
    color: #6c757d;
}

.breakdown-total {
    color: #2c3e50;
}

/* Checkout Actions */
.checkout-actions {
    margin-top: 2rem;
}

.checkout-actions .btn-lg {
    padding: 0.75rem 2rem;
    border-radius: 25px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .checkout-actions {
        flex-direction: column;
    }
    
    .checkout-actions .btn {
        width: 100%;
    }
    
    .payment-option {
        margin-bottom: 0.5rem;
    }
}
</style>

<script>
// Form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const deliveryAddress = document.getElementById('delivery_address').value.trim();
    const phoneNumber = document.getElementById('phone_number').value.trim();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (!deliveryAddress || !phoneNumber || !paymentMethod) {
        e.preventDefault();
        alert('Please fill in all required fields');
        return false;
    }
    
    // Phone number validation (Kenyan format)
    const phoneRegex = /^(\+254|254|0)?[17]\d{8}$/;
    if (!phoneRegex.test(phoneNumber.replace(/\s/g, ''))) {
        e.preventDefault();
        alert('Please enter a valid Kenyan phone number');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    
    return true;
});

// Payment method selection visual feedback
document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
        
        // Remove checked class from all
        document.querySelectorAll('.payment-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Add checked class to selected
        this.classList.add('selected');
    });
});
</script>

<?php require_once '../include/footer.php'; ?>