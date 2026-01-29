<?php
/**
 * Get All Item Prices API
 *
 * Returns all items with their prices for admin management.
 * Requires authentication and webmaster/admin permission.
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['wm', 'sa']);
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Optional filter by category
$category = validate_string_post('category', false, '');

// Build query
$query = "SELECT * FROM item_prices";
$params = [];
$types = '';

if (!empty($category)) {
    $query .= " WHERE item_category = ?";
    $params[] = $category;
    $types .= 's';
}

$query .= " ORDER BY item_category ASC, sort_order ASC";

// Execute query
if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => intval($row['id']),
            'item_category' => $row['item_category'],
            'item_name' => $row['item_name'],
            'item_code' => $row['item_code'],
            'price' => floatval($row['price']),
            'active' => intval($row['active']),
            'sort_order' => intval($row['sort_order']),
            'created_date' => $row['created_date'],
            'modified_date' => $row['modified_date']
        ];
    }
}

// Get available categories
$catQuery = "SELECT DISTINCT item_category FROM item_prices ORDER BY item_category";
$catResult = $mysqli->query($catQuery);
$categories = [];
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['item_category'];
    }
}

echo json_encode([
    'status' => 'Success',
    'items' => $items,
    'categories' => $categories
]);

$mysqli->close();
?>
