<?php
// Start session if not already started
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

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../user/checkout.php');
    exit();
}

// Get form data
$delivery_address = trim($_POST['delivery_address'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$payment_method = $_POST['payment_method'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// Validate required fields
if (empty($delivery_address) || empty($phone_number) || empty($payment_method)) {
    $_SESSION['checkout_error'] = 'Please fill in all required fields';
    header('Location: ../user/checkout.php');
    exit();
}

// Validate payment method
$valid_payment_methods = ['cash', 'mpesa', 'card'];
if (!in_array($payment_method, $valid_payment_methods)) {
    $_SESSION['checkout_error'] = 'Invalid payment method selected';
    header('Location: ../user/checkout.php');
    exit();
}

try {
    // Get cart items
    $database->query('
        SELECT ci.*, fi.name, fi.price 
        FROM cart_items ci 
        JOIN food_items fi ON ci.food_id = fi.id 
        WHERE ci.user_id = :user_id
    ');
    $database->bind(':user_id', $user_id);
    $cart_items = $database->resultSet();
    
    // Check if cart is empty
    if (empty($cart_items)) {
        $_SESSION['checkout_error'] = 'Your cart is empty';
        header('Location: ../user/cart.php');
        exit();
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $delivery_fee = 100; // KSh 100 delivery fee
    $tax_rate = 0.16; // 16% VAT
    $tax_amount = $subtotal * $tax_rate;
    $total_amount = $subtotal + $delivery_fee + $tax_amount;
    
    // Generate unique order number
    $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Check if order number already exists (very unlikely but just in case)
    $database->query('SELECT id FROM orders WHERE order_number = :order_number');
    $database->bind(':order_number', $order_number);
    if ($database->single()) {
        // Generate new number if exists
        $order_number = 'ORD' . date('YmdHis') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
    
    // Insert order into database
    $database->query('
        INSERT INTO orders (
            user_id, 
            order_number, 
            total_amount, 
            delivery_address, 
            phone_number, 
            payment_method, 
            payment_status,
            order_status,
            notes, 
            delivery_fee, 
            tax_amount,
            created_at
        ) VALUES (
            :user_id, 
            :order_number, 
            :total_amount, 
            :delivery_address, 
            :phone_number, 
            :payment_method,
            :payment_status,
            :order_status,
            :notes, 
            :delivery_fee, 
            :tax_amount,
            :created_at
        )
    ');
    
    $database->bind(':user_id', $user_id);
    $database->bind(':order_number', $order_number);
    $database->bind(':total_amount', $total_amount);
    $database->bind(':delivery_address', $delivery_address);
    $database->bind(':phone_number', $phone_number);
    $database->bind(':payment_method', $payment_method);
    $database->bind(':payment_status', 'pending');
    $database->bind(':order_status', 'pending');
    $database->bind(':notes', $notes);
    $database->bind(':delivery_fee', $delivery_fee);
    $database->bind(':tax_amount', $tax_amount);
    $database->bind(':created_at', date('Y-m-d H:i:s'));
    
    if (!$database->execute()) {
        throw new Exception('Failed to insert order');
    }
    
    // Get the inserted order ID
    $order_id = $database->lastInsertId();
    
    if (!$order_id) {
        throw new Exception('Failed to get order ID');
    }
    
    // Insert order items
    foreach ($cart_items as $item) {
        $database->query('
            INSERT INTO order_items (
                order_id, 
                food_id, 
                food_name, 
                quantity, 
                price,
                created_at
            ) VALUES (
                :order_id, 
                :food_id, 
                :food_name, 
                :quantity, 
                :price,
                :created_at
            )
        ');
        
        $database->bind(':order_id', $order_id);
        $database->bind(':food_id', $item['food_id']);
        $database->bind(':food_name', $item['name']);
        $database->bind(':quantity', $item['quantity']);
        $database->bind(':price', $item['price']);
        $database->bind(':created_at', date('Y-m-d H:i:s'));
        
        if (!$database->execute()) {
            throw new Exception('Failed to insert order item');
        }
    }
    
    // Clear the user's cart
    $database->query('DELETE FROM cart_items WHERE user_id = :user_id');
    $database->bind(':user_id', $user_id);
    $database->execute();
    
    // Set success message in session
    $_SESSION['order_success'] = true;
    $_SESSION['order_number'] = $order_number;
    
    // Redirect to order confirmation page
    header('Location: ../user/order_confirmation.php?order=' . $order_number);
    exit();
    
} catch (Exception $e) {
    // Log error
    error_log('Order placement error: ' . $e->getMessage());
    
    // Set error message
    $_SESSION['checkout_error'] = 'An error occurred while placing your order. Please try again.';
    header('Location: ../user/checkout.php');
    exit();
}
?>