<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$admin_name = $_SESSION['username'];

// Get dashboard statistics
try {
    // Total orders
    $database->query('SELECT COUNT(*) as total FROM orders');
    $total_orders = $database->single()['total'];
    
    // Pending orders
    $database->query('SELECT COUNT(*) as total FROM orders WHERE order_status = "pending"');
    $pending_orders = $database->single()['total'];
    
    // Total revenue
    $database->query('SELECT SUM(total_amount) as total FROM orders WHERE order_status != "cancelled"');
    $total_revenue = $database->single()['total'] ?? 0;
    
    // Total customers
    $database->query('SELECT COUNT(*) as total FROM users WHERE role = "user"');
    $total_customers = $database->single()['total'];
    
    // Total food items
    $database->query('SELECT COUNT(*) as total FROM food_items WHERE is_available = 1');
    $total_food_items = $database->single()['total'];
    
    // Recent orders (last 10)
    $database->query('
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ');
    $recent_orders = $database->resultSet();
    
    // Orders by status
    $database->query('
        SELECT order_status, COUNT(*) as count 
        FROM orders 
        GROUP BY order_status
    ');
    $orders_by_status = $database->resultSet();
    
} catch (Exception $e) {
    $total_orders = 0;
    $pending_orders = 0;
    $total_revenue = 0;
    $total_customers = 0;
    $total_food_items = 0;
    $recent_orders = [];
    $orders_by_status = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Romart Caterers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
        }
        
        body {
            background: #ecf0f1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: var(--admin-primary);
            color: white;
            padding: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            background: var(--admin-secondary);
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .menu-item {
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 2rem;
        }
        
        .menu-item i {
            width: 30px;
            margin-right: 0.75rem;
        }
        
        /* Main Content */
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        /* Top Navigation */
        .admin-topnav {
            background: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-welcome {
            font-size: 1.5rem;
            color: var(--admin-primary);
            font-weight: 600;
        }
        
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--admin-accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.primary { background: rgba(52, 152, 219, 0.1); color: var(--admin-accent); }
        .stat-icon.success { background: rgba(39, 174, 96, 0.1); color: var(--admin-success); }
        .stat-icon.warning { background: rgba(243, 156, 18, 0.1); color: var(--admin-warning); }
        .stat-icon.danger { background: rgba(231, 76, 60, 0.1); color: var(--admin-danger); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--admin-primary);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .card-header-custom {
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--admin-primary);
            margin: 0;
        }
        
        /* Table Styles */
        .table-custom {
            width: 100%;
        }
        
        .table-custom thead {
            background: #f8f9fa;
        }
        
        .table-custom th {
            padding: 1rem;
            font-weight: 600;
            color: var(--admin-primary);
            border: none;
        }
        
        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table-custom tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #cce5ff; color: #0066cc; }
        .badge-preparing { background: #e1f5fe; color: #0288d1; }
        .badge-delivery { background: #f3e5f5; color: #7b1fa2; }
        .badge-delivered { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Action Buttons */
        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
            margin: 0 0.25rem;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--admin-primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.show {
                transform: translateX(0);
            }
            
            .admin-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="admin.php" class="sidebar-brand">
                <i class="fas fa-utensils me-2"></i>Romart Admin
            </a>
        </div>
        
        <nav class="sidebar-menu">
            <a href="admin.php" class="menu-item active">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="manage_orders.php" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Manage Orders</span>
            </a>
            <a href="manage_food.php" class="menu-item">
                <i class="fas fa-hamburger"></i>
                <span>Manage Food Items</span>
            </a>
            <a href="manage_categories.php" class="menu-item">
                <i class="fas fa-list"></i>
                <span>Categories</span>
            </a>
            <a href="manage_users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="../index.php" class="menu-item">
                <i class="fas fa-globe"></i>
                <span>View Website</span>
            </a>
            <a href="../auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="admin-content">
        <!-- Top Navigation -->
        <div class="admin-topnav">
            <div class="admin-welcome">
                Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 👋
            </div>
            <div class="admin-user-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($admin_name); ?></span>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $pending_orders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">KSh <?php echo number_format($total_revenue, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-hamburger"></i>
                </div>
                <div class="stat-value"><?php echo $total_food_items; ?></div>
                <div class="stat-label">Food Items</div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="content-card">
            <div class="card-header-custom">
                <h3 class="card-title">
                    <i class="fas fa-receipt me-2"></i>Recent Orders
                </h3>
            </div>
            
            <?php if (empty($recent_orders)): ?>
                <p class="text-muted text-center py-4">No orders yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    <td><strong>KSh <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge badge-<?php echo str_replace('_', '-', $order['order_status']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="action-btn btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Orders by Status -->
        <div class="content-card">
            <div class="card-header-custom">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie me-2"></i>Orders by Status
                </h3>
            </div>
            
            <div class="row">
                <?php foreach ($orders_by_status as $status): ?>
                    <div class="col-md-4 mb-3">
                        <div class="p-3 border rounded">
                            <h4><?php echo $status['count']; ?></h4>
                            <p class="mb-0 text-muted"><?php echo ucwords(str_replace('_', ' ', $status['order_status'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>