<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['order_status'];
    
    try {
        $database->query('UPDATE orders SET order_status = :status WHERE id = :id');
        $database->bind(':status', $new_status);
        $database->bind(':id', $order_id);
        
        if ($database->execute()) {
            $message = 'Order status updated successfully!';
        } else {
            $error = 'Failed to update order status';
        }
    } catch (Exception $e) {
        $error = 'Error updating order status';
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = [];
$bind_params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = 'o.order_status = :status';
    $bind_params[':status'] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = '(o.order_number LIKE :search OR u.username LIKE :search OR u.email LIKE :search)';
    $bind_params[':search'] = '%' . $search_query . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $database->query("
        SELECT o.*, u.username, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $where_clause
        ORDER BY o.created_at DESC
    ");
    
    foreach ($bind_params as $param => $value) {
        $database->bind($param, $value);
    }
    
    $orders = $database->resultSet();
} catch (Exception $e) {
    $orders = [];
}

include 'admin_header.php';
?>

<div class="admin-page-header mb-4">
    <h2 class="page-title">
        <i class="fas fa-shopping-cart me-2"></i>Manage Orders
    </h2>
    <p class="page-subtitle">View and manage all customer orders</p>
</div>

<!-- Alert Messages -->
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

<!-- Filters -->
<div class="content-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Search Orders</label>
            <input type="text" name="search" class="form-control" placeholder="Order #, Customer name..." 
                   value="<?php echo htmlspecialchars($search_query); ?>">
        </div>
        
        <div class="col-md-3">
            <label class="form-label">Filter by Status</label>
            <select name="status" class="form-select">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Orders</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="preparing" <?php echo $status_filter == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="out_for_delivery" <?php echo $status_filter == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-filter me-1"></i>Filter
            </button>
        </div>
        
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <a href="manage_orders.php" class="btn btn-secondary w-100">
                <i class="fas fa-redo me-1"></i>Reset
            </a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="content-card">
    <div class="card-header-custom mb-3">
        <h3 class="card-title">
            <i class="fas fa-list me-2"></i>Orders List (<?php echo count($orders); ?>)
        </h3>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
            <h4 class="mt-3 text-muted">No orders found</h4>
            <p class="text-muted">Orders will appear here when customers place them</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['username']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($order['phone_number']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td><strong>KSh <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="order_status" class="form-select form-select-sm status-select" 
                                            onchange="this.form.submit()">
                                        <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $order['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="preparing" <?php echo $order['order_status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                        <option value="out_for_delivery" <?php echo $order['order_status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.admin-page-header {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.page-title {
    color: var(--admin-primary);
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #7f8c8d;
    margin-bottom: 0;
}

.status-select {
    min-width: 150px;
    cursor: pointer;
}

.status-select:focus {
    border-color: var(--admin-accent);
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}
</style>

<script>
function viewOrderDetails(orderId) {
    fetch('get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                const items = data.items;
                
                let itemsHtml = '';
                items.forEach(item => {
                    itemsHtml += `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <strong>${item.food_name}</strong><br>
                                <small class="text-muted">Qty: ${item.quantity} × KSh ${parseFloat(item.price).toLocaleString()}</small>
                            </div>
                            <strong>KSh ${(item.quantity * item.price).toLocaleString()}</strong>
                        </div>
                    `;
                });
                
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> ${order.username}</p>
                            <p><strong>Email:</strong> ${order.email}</p>
                            <p><strong>Phone:</strong> ${order.phone_number}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Order #:</strong> ${order.order_number}</p>
                            <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                            <p><strong>Payment:</strong> ${order.payment_method.toUpperCase()}</p>
                        </div>
                    </div>
                    <hr>
                    <h6>Delivery Address</h6>
                    <p>${order.delivery_address}</p>
                    ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ''}
                    <hr>
                    <h6>Order Items</h6>
                    ${itemsHtml}
                    <hr>
                    <div class="row">
                        <div class="col-6 text-end"><strong>Subtotal:</strong></div>
                        <div class="col-6">KSh ${(order.total_amount - order.delivery_fee - order.tax_amount).toLocaleString()}</div>
                    </div>
                    <div class="row">
                        <div class="col-6 text-end"><strong>Delivery Fee:</strong></div>
                        <div class="col-6">KSh ${parseFloat(order.delivery_fee).toLocaleString()}</div>
                    </div>
                    <div class="row">
                        <div class="col-6 text-end"><strong>Tax:</strong></div>
                        <div class="col-6">KSh ${parseFloat(order.tax_amount).toLocaleString()}</div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6 text-end"><h5>Total:</h5></div>
                        <div class="col-6"><h5 class="text-primary">KSh ${parseFloat(order.total_amount).toLocaleString()}</h5></div>
                    </div>
                `;
                
                document.getElementById('orderDetailsContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            } else {
                alert('Error loading order details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading order details');
        });
}
</script>

<?php include 'admin_footer.php'; ?>