<?php
session_start();
require_once '../include/db_connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$user_id = $_SESSION['user_id'];
$food_id = isset($input['food_id']) ? intval($input['food_id']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;

if ($food_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid food item or quantity']);
    exit();
}

try {
    // Verify food item exists and is available
    $database->query('SELECT * FROM food_items WHERE id = :food_id AND is_available = 1');
    $database->bind(':food_id', $food_id);
    $food_item = $database->single();
    
    if (!$food_item) {
        echo json_encode(['success' => false, 'message' => 'Food item not available']);
        exit();
    }
    
    // Check if item already exists in cart
    $database->query('SELECT * FROM cart_items WHERE user_id = :user_id AND food_id = :food_id');
    $database->bind(':user_id', $user_id);
    $database->bind(':food_id', $food_id);
    $existing_item = $database->single();
    
    if ($existing_item) {
        // Update quantity if item exists
        $new_quantity = $existing_item['quantity'] + $quantity;
        $database->query('UPDATE cart_items SET quantity = :quantity, updated_at = :updated_at WHERE id = :id');
        $database->bind(':quantity', $new_quantity);
        $database->bind(':updated_at', date('Y-m-d H:i:s'));
        $database->bind(':id', $existing_item['id']);
        $database->execute();
    } else {
        // Add new item to cart
        $database->query('INSERT INTO cart_items (user_id, food_id, quantity, price, created_at) VALUES (:user_id, :food_id, :quantity, :price, :created_at)');
        $database->bind(':user_id', $user_id);
        $database->bind(':food_id', $food_id);
        $database->bind(':quantity', $quantity);
        $database->bind(':price', $food_item['price']);
        $database->bind(':created_at', date('Y-m-d H:i:s'));
        $database->execute();
    }
    
    // Get updated cart count
    $database->query('SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id');
    $database->bind(':user_id', $user_id);
    $cart_count = $database->single()['count'];
    
    // Update session cart count
    $_SESSION['cart_count'] = $cart_count;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Item added to cart successfully',
        'cartCount' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error adding item to cart']);
}
?>