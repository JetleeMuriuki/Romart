<?php
session_start();
require_once 'include/db_connect.php';

// Set content type to JSON for API response
header('Content-Type: application/json');

// Get the item ID from the request
$item_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate item ID
if ($item_id <= 0) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid item ID provided'
    ]);
    exit();
}

try {
    // Get food item details with category information
    $database->query('
        SELECT fi.*, c.name as category_name 
        FROM food_items fi 
        LEFT JOIN categories c ON fi.category_id = c.id 
        WHERE fi.id = :item_id AND fi.is_available = 1
    ');
    $database->bind(':item_id', $item_id);
    $food_item = $database->single();
    
    // Check if item exists and is available
    if (!$food_item) {
        echo json_encode([
            'success' => false, 
            'message' => 'Food item not found or not available'
        ]);
        exit();
    }
    
    // Return successful response with item data
    echo json_encode([
        'success' => true,
        'item' => [
            'id' => intval($food_item['id']),
            'name' => $food_item['name'],
            'description' => $food_item['description'],
            'price' => floatval($food_item['price']),
            'image_url' => $food_item['image_url'],
            'category_name' => $food_item['category_name'],
            'preparation_time' => intval($food_item['preparation_time'] ?? 15),
            'is_available' => intval($food_item['is_available'])
        ]
    ]);
    
} catch (Exception $e) {
    // Handle database errors
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred while fetching item details'
    ]);
}
?>