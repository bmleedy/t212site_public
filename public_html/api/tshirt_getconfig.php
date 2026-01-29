<?php
/**
 * T-Shirt Store Configuration API
 *
 * Returns T-shirt prices, image URL, and store status.
 * This is a public endpoint - no authentication required.
 *
 * @security Read-only endpoint with no user input in queries
 */

header('Content-Type: application/json');
require 'connect.php';

// Get all active T-shirt items with prices
$query = "SELECT item_code, item_name, price, sort_order
          FROM item_prices
          WHERE item_category = 'tshirt' AND active = 1
          ORDER BY sort_order ASC";
$result = $mysqli->query($query);

$items = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'code' => $row['item_code'],
            'name' => $row['item_name'],
            'price' => floatval($row['price']),
            'sort_order' => intval($row['sort_order'])
        ];
    }
}

// Get store configuration
$config = [];
$configQuery = "SELECT config_key, config_value FROM store_config WHERE config_key LIKE 'tshirt_%'";
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
    'orders_enabled' => isset($config['tshirt_orders_enabled']) ? ($config['tshirt_orders_enabled'] === '1') : true,
    'image_url' => isset($config['tshirt_image_url']) ? $config['tshirt_image_url'] : '/images/tshirt-classb.jpg'
];

echo json_encode($response);
$mysqli->close();
?>
