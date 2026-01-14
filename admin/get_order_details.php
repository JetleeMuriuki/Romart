<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/db_connect.php';

header('Content-Type: application/json');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    // Get order details
    $database->query('
        SELECT o.*, u.username, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = :order_id
    ');
    $database->bind(':order_id', $order_id);
    $order = $database->single();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items
    $database->query('SELECT * FROM order_items WHERE order_id = :order_id');
    $database->bind(':order_id', $order_id);
    $items = $database->resultSet();
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>