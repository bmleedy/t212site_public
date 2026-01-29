<?php
/**
 * Store Configuration API
 *
 * Returns item prices, images, and store status for a given category.
 * This is a public endpoint - no authentication required.
 *
 * @security Read-only endpoint. Category parameter uses prepared statements to prevent SQL injection.
 *
 * Parameters:
 *   category - (optional) Item category to filter by (e.g., 'tshirt'). Defaults to all active items.
 */

header('Content-Type: application/json');
require 'connect.php';

// Optional category filter
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
if (empty($category) && isset($_GET['category'])) {
    $category = trim($_GET['category']);
}

// Build query for items
if (!empty($category)) {
    $query = "SELECT item_code, item_name, item_category, price, sort_order
              FROM item_prices
              WHERE item_category = ? AND active = 1
              ORDER BY sort_order ASC";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('s', $category);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT item_code, item_name, item_category, price, sort_order
              FROM item_prices
              WHERE active = 1
              ORDER BY item_category, sort_order ASC";
    $result = $mysqli->query($query);
}

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'code' => $row['item_code'],
            'name' => $row['item_name'],
            'category' => $row['item_category'],
            'price' => floatval($row['price']),
            'sort_order' => intval($row['sort_order'])
        ];
    }
}

if (isset($stmt)) {
    $stmt->close();
}

// Get store configuration
$config = [];
$configQuery = "SELECT config_key, config_value FROM store_config";
$configResult = $mysqli->query($configQuery);

if ($configResult) {
    while ($row = $configResult->fetch_assoc()) {
        $config[$row['config_key']] = $row['config_value'];
    }
}

// Build response
$response = [
    'status' => 'Success',
    'items' => $items,
    'config' => $config,
    'store_enabled' => isset($config['store_enabled']) ? ($config['store_enabled'] === '1') : true
];

// Add category-specific flags for backwards compatibility
if ($category === 'tshirt') {
    $response['orders_enabled'] = isset($config['tshirt_orders_enabled']) ? ($config['tshirt_orders_enabled'] === '1') : true;
    $response['image_url'] = isset($config['tshirt_image_url']) ? $config['tshirt_image_url'] : '/images/tshirt-classb.jpg';
}

echo json_encode($response);
$mysqli->close();
?>
