<?php
session_start();
require_once '../include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_number = isset($_GET['order']) ? trim($_GET['order']) : '';

if (empty($order_number)) {
    header('Location: orders.php');
    exit();
}

// Get order details
try {
    $database->query('SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id');
    $database->bind(':order_number', $order_number);
    $database->bind(':user_id', $user_id);
    $order = $database->single();
    
    if (!$order) {
        header('Location: orders.php');
        exit();
    }
    
    // Get order items
    $database->query('SELECT * FROM order_items WHERE order_id = :order_id');
    $database->bind(':order_id', $order['id']);
    $order_items = $database->resultSet();
    
} catch (Exception $e) {
    header('Location: orders.php');
    exit();
}

require_once '../include/header.php';
?>

<div class="container py-5">
    <div class="confirmation-page">
        <!-- Success Header -->
        <div class="success-header text-center mb-5">
            <div class="success-icon mb-3">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Order Confirmed!</h1>
            <p class="success-message">Thank you for your order. We'll start preparing your delicious meal right away!</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Order Summary Card -->
                <div class="order-summary-card card mb-4">
                    <div class="card-header summary-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="summary-title mb-0">
                                <i class="fas fa-receipt me-2"></i>Order Summary
                            </h5>
                            <span class="order-number-badge">
                                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Order Status -->
                        <div class="status-section mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="status-info">
                                    <h6 class="status-label">Current Status</h6>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Order Received',
                                            'confirmed' => 'Confirmed',
                                            'preparing' => 'Being Prepared',
                                            'out_for_delivery' => 'Out for Delivery',
                                            'delivered' => 'Delivered',
                                            'cancelled' => 'Cancelled'
                                        ];
                                        echo $status_labels[$order['order_status']];
                                        ?>
                                    </span>
                                </div>
                                <div class="estimated-time">
                                    <h6 class="time-label">Estimated Delivery</h6>
                                    <span class="time-value">
                                        <i class="fas fa-clock me-1"></i>30-45 minutes
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Items -->
                        <div class="items-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-utensils me-2"></i>Your Items
                            </h6>
                            <div class="items-list">
                                <?php foreach ($order_items as $item): ?>
                                    <div class="item-row">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="item-details">
                                                <span class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></span>
                                                <span class="item-qty"> × <?php echo $item['quantity']; ?></span>
                                            </div>
                                            <span class="item-price">KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Delivery Information -->
                        <div class="delivery-section mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-map-marker-alt me-2"></i>Delivery Details
                            </h6>
                            <div class="delivery-details">
                                <div class="detail-row mb-2">
                                    <i class="fas fa-home text-muted me-2"></i>
                                    <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                </div>
                                <div class="detail-row mb-2">
                                    <i class="fas fa-phone text-muted me-2"></i>
                                    <span><?php echo htmlspecialchars($order['phone_number']); ?></span>
                                </div>
                                <?php if ($order['notes']): ?>
                                <div class="detail-row">
                                    <i class="fas fa-sticky-note text-muted me-2"></i>
                                    <span><?php echo htmlspecialchars($order['notes']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Payment Summary -->
                        <div class="payment-section">
                            <h6 class="section-title">
                                <i class="fas fa-credit-card me-2"></i>Payment Summary
                            </h6>
                            <div class="payment-breakdown">
                                <div class="breakdown-row">
                                    <span>Subtotal:</span>
                                    <span>KSh <?php echo number_format($order['total_amount'] - $order['delivery_fee'] - $order['tax_amount'], 2); ?></span>
                                </div>
                                <div class="breakdown-row">
                                    <span>Delivery Fee:</span>
                                    <span>KSh <?php echo number_format($order['delivery_fee'], 2); ?></span>
                                </div>
                                <div class="breakdown-row">
                                    <span>Tax (16%):</span>
                                    <span>KSh <?php echo number_format($order['tax_amount'], 2); ?></span>
                                </div>
                                <div class="breakdown-total">
                                    <strong>
                                        <span>Total:</span>
                                        <span>KSh <?php echo number_format($order['total_amount'], 2); ?></span>
                                    </strong>
                                </div>
                            </div>
                            
                            <div class="payment-method-info mt-3">
                                <div class="method-card">
                                    <i class="fas fa-<?php echo $order['payment_method'] == 'cash' ? 'money-bill-wave' : ($order['payment_method'] == 'mpesa' ? 'mobile-alt' : 'credit-card'); ?> me-2"></i>
                                    <span class="method-name"><?php echo ucfirst($order['payment_method']); ?></span>
                                    <?php if ($order['payment_method'] == 'cash'): ?>
                                        <small class="method-note">on Delivery</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons text-center">
                    <a href="orders.php" class="btn btn-outline-primary action-btn me-3">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                    <a href="../index.php" class="btn btn-primary action-btn">
                        <i class="fas fa-utensils me-2"></i>Order More Food
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Confirmation Page Styles */
.confirmation-page {
    max-width: 900px;
    margin: 0 auto;
}

/* Success Header */
.success-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 3rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
}

.success-icon i {
    font-size: 4rem;
    color: rgba(255,255,255,0.9);
    animation: successPulse 2s infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.success-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.success-message {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Order Summary Card */
.order-summary-card {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.summary-header {
    background: #f8f9fa;
    border-radius: 15px 15px 0 0 !important;
    border-bottom: 2px solid #e9ecef;
    padding: 1.5rem;
}

.summary-title {
    color: #2c3e50;
    font-weight: 600;
}

.order-number-badge {
    background: linear-gradient(45deg, #e74c3c, #c0392b);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}

/* Status Section */
.status-section {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.status-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cce5ff; color: #0066cc; }
.status-preparing { background: #e1f5fe; color: #0288d1; }

.time-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.time-value {
    color: #28a745;
    font-weight: 600;
}

/* Section Styles */
.section-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

/* Items Section */
.items-section {
    padding: 1rem 0;
}

.items-list {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

.item-row {
    padding: 0.75rem 0;
    border-bottom: 1px solid #dee2e6;
}

.item-row:last-child {
    border-bottom: none;
}

.item-name {
    font-weight: 500;
    color: #2c3e50;
}

.item-qty {
    color: #6c757d;
    font-size: 0.9rem;
}

.item-price {
    font-weight: 600;
    color: #e74c3c;
}

/* Delivery Section */
.delivery-section {
    padding: 1rem 0;
}

.delivery-details {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

.detail-row {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.detail-row:last-child {
    margin-bottom: 0;
}

/* Payment Section */
.payment-section {
    padding: 1rem 0;
}

.payment-breakdown {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
}

.breakdown-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    color: #6c757d;
}

.breakdown-total {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #dee2e6;
    font-size: 1.1rem;
    color: #2c3e50;
}

.method-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    display: flex;
    align-items: center;
}

.method-name {
    font-weight: 500;
    text-transform: capitalize;
}

.method-note {
    margin-left: 0.5rem;
    color: #6c757d;
    font-style: italic;
}

/* Action Buttons */
.action-buttons {
    margin-top: 2rem;
}

.action-btn {
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 500;
    margin-bottom: 1rem;
    min-width: 180px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .success-header {
        padding: 2rem 1rem;
        margin: 0 1rem 2rem 1rem;
    }
    
    .success-title {
        font-size: 2rem;
    }
    
    .success-message {
        font-size: 1rem;
    }
    
    .status-section {
        padding: 1rem;
    }
    
    .status-section .d-flex {
        flex-direction: column;
        text-align: center;
    }
    
    .estimated-time {
        margin-top: 1rem;
    }
    
    .action-buttons .action-btn {
        display: block;
        width: 100%;
        max-width: 300px;
        margin: 0 auto 1rem auto;
    }
    
    .order-number-badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}

/* Loading Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.order-summary-card {
    animation: fadeInUp 0.6s ease-out;
}
</style>

<script>
// element animation
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animations
    const card = document.querySelector('.order-summary-card');
    const buttons = document.querySelector('.action-buttons');
    
    // Animate card
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.6s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 300);
    
    // Animate buttons
    buttons.style.opacity = '0';
    buttons.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        buttons.style.transition = 'all 0.6s ease';
        buttons.style.opacity = '1';
        buttons.style.transform = 'translateY(0)';
    }, 600);
    
    // Auto-refresh order status (optional)
    // Uncomment if you want to implement real-time status updates
    /*
    setInterval(function() {
        // Check for status updates via AJAX
        fetch('check_order_status.php?order=' + '<?php echo $order['order_number']; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.status !== '<?php echo $order['order_status']; ?>') {
                    location.reload(); // Reload page if status changed
                }
            })
            .catch(error => console.error('Status check failed:', error));
    }, 30000); // Check every 30 seconds
    */
});
</script>

<?php require_once '../include/footer.php'; ?>