<!-- profile page for customers -->
<?php
session_start();
require_once '../include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                // Validation
                if (empty($username) || empty($email)) {
                    $error = 'Username and email are required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address';
                } else {
                    try {
                        // Check if username is taken by another user
                        $database->query('SELECT id FROM users WHERE username = :username AND id != :user_id');
                        $database->bind(':username', $username);
                        $database->bind(':user_id', $user_id);
                        if ($database->single()) {
                            $error = 'Username is already taken';
                        } else {
                            // Check if email is taken by another user
                            $database->query('SELECT id FROM users WHERE email = :email AND id != :user_id');
                            $database->bind(':email', $email);
                            $database->bind(':user_id', $user_id);
                            if ($database->single()) {
                                $error = 'Email is already registered';
                            } else {
                                // Update profile
                                $database->query('UPDATE users SET username = :username, email = :email, phone = :phone, address = :address, updated_at = :updated_at WHERE id = :user_id');
                                $database->bind(':username', $username);
                                $database->bind(':email', $email);
                                $database->bind(':phone', $phone);
                                $database->bind(':address', $address);
                                $database->bind(':updated_at', date('Y-m-d H:i:s'));
                                $database->bind(':user_id', $user_id);
                                
                                if ($database->execute()) {
                                    $_SESSION['username'] = $username;
                                    $_SESSION['email'] = $email;
                                    $message = 'Profile updated successfully!';
                                } else {
                                    $error = 'Failed to update profile. Please try again.';
                                }
                            }
                        }
                    } catch (Exception $e) {
                        $error = 'Database error. Please try again.';
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } else {
                    try {
                        // Verify current password
                        $database->query('SELECT password FROM users WHERE id = :user_id');
                        $database->bind(':user_id', $user_id);
                        $user_data = $database->single();
                        
                        if (!password_verify($current_password, $user_data['password'])) {
                            $error = 'Current password is incorrect';
                        } else {
                            // Update password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $database->query('UPDATE users SET password = :password, updated_at = :updated_at WHERE id = :user_id');
                            $database->bind(':password', $hashed_password);
                            $database->bind(':updated_at', date('Y-m-d H:i:s'));
                            $database->bind(':user_id', $user_id);
                            
                            if ($database->execute()) {
                                $message = 'Password changed successfully!';
                            } else {
                                $error = 'Failed to change password. Please try again.';
                            }
                        }
                    } catch (Exception $e) {
                        $error = 'Database error. Please try again.';
                    }
                }
                break;
        }
    }
}

// Get user data
try {
    $database->query('SELECT * FROM users WHERE id = :user_id');
    $database->bind(':user_id', $user_id);
    $user = $database->single();
} catch (Exception $e) {
    $error = 'Failed to load user data';
    $user = [];
}

// Get user statistics
try {
    $database->query('SELECT COUNT(*) as total_orders FROM orders WHERE user_id = :user_id');
    $database->bind(':user_id', $user_id);
    $total_orders = $database->single()['total_orders'];
    
    $database->query('SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = :user_id AND order_status = "delivered"');
    $database->bind(':user_id', $user_id);
    $total_spent = $database->single()['total_spent'] ?? 0;
    
    $database->query('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1');
    $database->bind(':user_id', $user_id);
    $last_order = $database->single();
} catch (Exception $e) {
    $total_orders = 0;
    $total_spent = 0;
    $last_order = null;
}

require_once '../include/header.php';
?>

<div class="container py-5">
    <div class="profile-page">
        <!-- Page Header -->
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="page-title">
                    <i class="fas fa-user me-2"></i>My Profile
                </h2>
                <a href="../index.php" class="btn btn-outline-primary back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show success-alert" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show error-alert" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- User Stats Sidebar -->
            <div class="col-lg-4 mb-4">
                <div class="user-stats-card card">
                    <div class="card-body text-center">
                        <div class="user-avatar mb-3">
                            <div class="avatar-circle">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <h5 class="user-name"><?php echo htmlspecialchars($user['username'] ?? 'Unknown'); ?></h5>
                        <p class="user-email text-muted"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                        <p class="member-since text-muted small">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Member since <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?>
                        </p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-container mt-4">
                    <div class="stat-card card mb-3">
                        <div class="card-body">
                            <div class="stat-item d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-bag text-primary"></i>
                                </div>
                                <div class="stat-info ms-3">
                                    <h6 class="stat-number"><?php echo $total_orders; ?></h6>
                                    <p class="stat-label mb-0">Total Orders</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card card mb-3">
                        <div class="card-body">
                            <div class="stat-item d-flex align-items-center">
                                <div class="stat-icon">
                                    <i class="fas fa-money-bill-wave text-success"></i>
                                </div>
                                <div class="stat-info ms-3">
                                    <h6 class="stat-number">KSh <?php echo number_format($total_spent, 2); ?></h6>
                                    <p class="stat-label mb-0">Total Spent</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($last_order): ?>
                    <div class="stat-card card mb-3">
                        <div class="card-body">
                            <div class="stat-item">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="stat-icon">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                    <div class="stat-info ms-3">
                                        <h6 class="stat-label mb-0">Last Order</h6>
                                    </div>
                                </div>
                                <p class="last-order-date text-muted small mb-1">
                                    <?php echo date('M j, Y', strtotime($last_order['created_at'])); ?>
                                </p>
                                <span class="status-badge status-<?php echo $last_order['order_status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'preparing' => 'Preparing',
                                        'out_for_delivery' => 'Out for Delivery',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled'
                                    ];
                                    echo $status_labels[$last_order['order_status']];
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Forms -->
            <div class="col-lg-8">
                <!-- Profile Information Form -->
                <div class="profile-form-card card mb-4">
                    <div class="card-header form-header">
                        <h5 class="form-title mb-0">
                            <i class="fas fa-user-edit me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-2"></i>Username *
                                    </label>
                                    <input type="text" class="form-control input-field" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email Address *
                                    </label>
                                    <input type="email" class="form-control input-field" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-2"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control input-field" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Address
                                    </label>
                                    <input type="text" class="form-control input-field" id="address" name="address" 
                                           value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary update-btn">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Form -->
                <div class="password-form-card card">
                    <div class="card-header form-header">
                        <h5 class="form-title mb-0">
                            <i class="fas fa-lock me-2"></i>Change Password
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="password-form">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-key me-2"></i>Current Password *
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control input-field" id="current_password" 
                                           name="current_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="new_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>New Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control input-field" id="new_password" 
                                               name="new_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-1" id="passwordStrength"></div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirm Password *
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control input-field" id="confirm_password" 
                                               name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-match mt-1" id="passwordMatch"></div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-warning change-password-btn">
                                    <i class="fas fa-shield-alt me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile Page Styles */
.profile-page {
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

.back-btn {
    border-radius: 25px;
    padding: 0.5rem 1.5rem;
}

/* Alert Styles */
.success-alert {
    border-left: 4px solid #28a745;
    background-color: #d4edda;
}

.error-alert {
    border-left: 4px solid #dc3545;
    background-color: #f8d7da;
}

/* User Stats Card */
.user-stats-card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.user-stats-card .card-body {
    padding: 2rem;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2rem;
}

.user-name {
    color: white;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.user-email {
    color: rgba(255,255,255,0.8) !important;
    font-size: 1rem;
}

.member-since {
    color: rgba(255,255,255,0.7) !important;
    font-size: 0.9rem;
}

/* Statistics Cards */
.stats-container {
    margin-top: 1.5rem;
}

.stat-card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-radius: 10px;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-item {
    padding: 0.5rem 0;
}

.stat-icon {
    font-size: 1.5rem;
    width: 40px;
    text-align: center;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.2rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0;
}

.last-order-date {
    font-size: 0.85rem;
}

/* Status Badges (reused from orders page) */
.status-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cce5ff; color: #0066cc; }
.status-preparing { background: #e1f5fe; color: #0288d1; }
.status-out_for_delivery { background: #f3e5f5; color: #7b1fa2; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

/* Form Cards */
.profile-form-card, .password-form-card {
    border: none;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    border-radius: 12px;
}

.form-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0 !important;
}

.form-title {
    color: #495057;
    font-weight: 600;
}

.input-field {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.input-field:focus {
    border-color: #e74c3c;
    box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.15);
}

.form-actions {
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.update-btn {
    background: linear-gradient(45deg, #28a745, #20c997);
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.update-btn:hover {
    background: linear-gradient(45deg, #218838, #1ea87a);
    transform: translateY(-1px);
}

.change-password-btn {
    background: linear-gradient(45deg, #ffc107, #fd7e14);
    border: none;
    color: #212529;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.change-password-btn:hover {
    background: linear-gradient(45deg, #e0a800, #ea6100);
    color: #212529;
    transform: translateY(-1px);
}

.toggle-password {
    border-left: none !important;
    width: 45px;
}

/* Password Strength Indicator */
.password-strength {
    font-size: 0.85rem;
    margin-top: 0.3rem;
}

.password-match {
    font-size: 0.85rem;
    margin-top: 0.3rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .profile-page {
        padding: 0 1rem;
    }
    
    .user-stats-card .card-body {
        padding: 1.5rem;
    }
    
    .avatar-circle {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .form-actions {
        text-align: center;
    }
    
    .update-btn, .change-password-btn {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<script>
// Toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtns = document.querySelectorAll('.toggle-password');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Password strength indicator
    const newPasswordInput = document.getElementById('new_password');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    feedback = '<span class="text-danger">Weak</span>';
                    break;
                case 2:
                case 3:
                    feedback = '<span class="text-warning">Medium</span>';
                    break;
                case 4:
                case 5:
                    feedback = '<span class="text-success">Strong</span>';
                    break;
            }
            
            strengthDiv.innerHTML = password ? `Password strength: ${feedback}` : '';
        });
    }
    
    // Password match indicator
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword) {
                if (password === confirmPassword) {
                    matchDiv.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Passwords match</small>';
                } else {
                    matchDiv.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</small>';
                }
            } else {
                matchDiv.innerHTML = '';
            }
        });
    }
    
    // Form animations
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
</script>

<?php require_once '../include/footer.php'; ?>