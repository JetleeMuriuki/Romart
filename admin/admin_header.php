
<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RomArt Prime</title>
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
        
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
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
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="admin.php" class="sidebar-brand">
                <i class="fas fa-utensils me-2"></i>Romart Admin
            </a>
        </div>
        
        <nav class="sidebar-menu">
            <a href="admin.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="manage_orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Manage Orders</span>
            </a>
            <a href="manage_food.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_food.php' ? 'active' : ''; ?>">
                <i class="fas fa-hamburger"></i>
                <span>Manage Food Items</span>
            </a>
            <a href="manage_categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Categories</span>
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
    
    <div class="admin-content">