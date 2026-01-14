<!-- view past orders from customers side -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user orders with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total orders count
    $database->query('SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id');
    $database->bind(':user_id', $user_id);
    $total_orders = $database->single()['count'];
    
    // Get orders for current page
    $database->query('
        SELECT * FROM orders 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ');
    $database->bind(':user_id', $user_id);
    $database->bind(':limit', $per_page);
    $database->bind(':offset', $offset);
    $orders = $database->resultSet();
    
} catch (Exception $e) {
    $orders = [];
    $total_orders = 0;
}

$total_pages = ceil($total_orders / $per_page);

require_once '../include/header.php';
?>

<div class="container py-5">
    <div class="orders-page">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="page-title">
                    <i class="fas fa-receipt me-2"></i>My Orders
                </h2>
                <a href="../index.php" class="btn btn-outline-primary back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Menu
                </a>
            </div>
            <p class="page-subtitle text-muted">Track your order history and current orders</p>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-orders text-center py-5">
                <div class="empty-icon mb-4">
                    <i class="fas fa-receipt text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                </div>
                <h4 class="empty-title mb-3">No Orders Yet</h4>
                <p class="empty-text text-muted mb-4">You haven't placed any orders yet. Start by exploring our delicious menu!</p>
                <a href="../index.php" class="btn btn-primary browse-menu-btn">
                    <i class="fas fa-utensils me-2"></i>Browse Menu
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="orders-container">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card card mb-4">
                        <div class="order-header card-header">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="order-number">
                                        <strong>Order #<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    </div>
                                    <div class="order-date text-muted small">
                                        <?php echo date('M j, Y - g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="order-status">
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'Pending',
                                                'confirmed' => 'Confirmed',
                                                'preparing' => 'Preparing',
                                                'out_for_delivery' => 'Out for Delivery',
                                                'delivered' => 'Delivered',
                                                'cancelled' => 'Cancelled'
                                            ];
                                            echo $status_labels[$order['order_status']];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="order-total">
                                        <strong class="total-amount">KSh <?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </div>
                                    <div class="payment-method text-muted small">
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <button class="btn btn-outline-primary btn-sm view-details-btn" 
                                            onclick="toggleOrderDetails('<?php echo $order['id']; ?>')">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Details (Initially Hidden) -->
                        <div class="order-details card-body d-none" id="details-<?php echo $order['id']; ?>">
                            <?php
                            // Get order items
                            try {
                                $database->query('SELECT * FROM order_items WHERE order_id = :order_id');
                                $database->bind(':order_id', $order['id']);
                                $order_items = $database->resultSet();
                            } catch (Exception $e) {
                                $order_items = [];
                            }
                            ?>
                            
                            <div class="row">
                                <div class="col-lg-8">
                                    <h6 class="details-title">Order Items</h6>
                                    <div class="items-list">
                                        <?php foreach ($order_items as $item): ?>
                                            <div class="item-row d-flex justify-content-between align-items-center py-2 border-bottom">
                                                <div class="item-info">
                                                    <span class="item-name"><?php echo htmlspecialchars($item['food_name']); ?></span>
                                                    <span class="item-quantity text-muted"> × <?php echo $item['quantity']; ?></span>
                                                </div>
                                                <div class="item-price">
                                                    KSh <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <div class="delivery-info">
                                        <h6 class="details-title">Delivery Information</h6>
                                        <div class="info-item mb-2">
                                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                            <small><?php echo htmlspecialchars($order['delivery_address']); ?></small>
                                        </div>
                                        <div class="info-item mb-2">
                                            <i class="fas fa-phone text-muted me-2"></i>
                                            <small><?php echo htmlspecialchars($order['phone_number']); ?></small>
                                        </div>
                                        <?php if ($order['notes']): ?>
                                            <div class="info-item">
                                                <i class="fas fa-sticky-note text-muted me-2"></i>
                                                <small><?php echo htmlspecialchars($order['notes']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-summary mt-3">
                                        <h6 class="details-title">Order Summary</h6>
                                        <div class="summary-row d-flex justify-content-between">
                                            <small>Subtotal:</small>
                                            <small>KSh <?php echo number_format($order['total_amount'] - $order['delivery_fee'] - $order['tax_amount'], 2); ?></small>
                                        </div>
                                        <div class="summary-row d-flex justify-content-between">
                                            <small>Delivery:</small>
                                            <small>KSh <?php echo number_format($order['delivery_fee'], 2); ?></small>
                                        </div>
                                        <div class="summary-row d-flex justify-content-between">
                                            <small>Tax:</small>
                                            <small>KSh <?php echo number_format($order['tax_amount'], 2); ?></small>
                                        </div>
                                        <hr class="my-2">
                                        <div class="summary-row d-flex justify-content-between">
                                            <strong>Total:</strong>
                                            <strong>KSh <?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav class="pagination-nav">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Orders Page Styles */
.orders-page {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 1rem;
}

.page-title {
    color: #2c3e50;
    font-weight: 600;
}

.page-subtitle {
    margin-bottom: 0;
    font-size: 1.1rem;
}

.back-btn {
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
}

/* Empty State */
.empty-orders {
    background: #f8f9fa;
    border-radius: 10px;
    margin: 2rem 0;
}

.empty-title {
    color: #6c757d;
}

.empty-text {
    font-size: 1.1rem;
}

.browse-menu-btn {
    padding: 0.75rem 2rem;
    border-radius: 25px;
}

/* Order Cards */
.order-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.order-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.order-number {
    font-size: 1.1rem;
    color: #2c3e50;
}

.order-date {
    font-size: 0.9rem;
}

/* Status Badges */
.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cce5ff; color: #0066cc; }
.status-preparing { background: #e1f5fe; color: #0288d1; }
.status-out_for_delivery { background: #f3e5f5; color: #7b1fa2; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.total-amount {
    font-size: 1.2rem;
    color: #e74c3c;
}

.payment-method {
    text-transform: capitalize;
}

/* Order Details */
.order-details {
    background: #fbfcfd;
    border-top: 1px solid #dee2e6;
}

.details-title {
    color: #495057;
    margin-bottom: 1rem;
    font-weight: 600;
}

.items-list {
    max-height: 300px;
    overflow-y: auto;
}

.item-row {
    padding: 0.75rem 0;
}

.item-name {
    font-weight: 500;
    color: #2c3e50;
}

.item-quantity {
    font-size: 0.9rem;
}

.item-price {
    font-weight: 500;
    color: #e74c3c;
}

.delivery-info .info-item {
    margin-bottom: 0.5rem;
}

.order-summary .summary-row {
    margin-bottom: 0.3rem;
}

.view-details-btn {
    border-radius: 20px;
    transition: all 0.3s ease;
}

.view-details-btn:hover {
    background: #e74c3c;
    border-color: #e74c3c;
    color: white;
}

/* Pagination */
.pagination-container {
    margin-top: 3rem;
}

.pagination .page-link {
    color: #e74c3c;
    border-color: #dee2e6;
    padding: 0.5rem 0.75rem;
}

.pagination .page-item.active .page-link {
    background-color: #e74c3c;
    border-color: #e74c3c;
}

.pagination .page-link:hover {
    color: #c0392b;
    background-color: #f8f9fa;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .order-header .row > div {
        margin-bottom: 1rem;
    }
    
    .order-header .row > div:last-child {
        margin-bottom: 0;
        text-align: left !important;
    }
    
    .order-details .row {
        flex-direction: column-reverse;
    }
    
    .delivery-info, .order-summary {
        margin-bottom: 2rem;
    }
}
</style>

<script>
function toggleOrderDetails(orderId) {
    const detailsDiv = document.getElementById('details-' + orderId);
    const btn = event.target.closest('.view-details-btn');
    
    if (detailsDiv.classList.contains('d-none')) {
        // Show details
        detailsDiv.classList.remove('d-none');
        btn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Hide Details';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-outline-secondary');
    } else {
        // Hide details
        detailsDiv.classList.add('d-none');
        btn.innerHTML = '<i class="fas fa-eye me-1"></i>View Details';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-outline-primary');
    }
}

// Add loading animation
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php require_once '../include/footer.php'; ?>