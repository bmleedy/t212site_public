<?php
/**
 * Update Item Price API
 *
 * Updates the price or active status of an item.
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
require_once(__DIR__ . '/../includes/activity_logger.php');

// Get item ID and new values
$item_id = validate_int_post('item_id', true);
$new_price = isset($_POST['price']) ? floatval($_POST['price']) : null;
$new_active = isset($_POST['active']) ? intval($_POST['active']) : null;

// Validate price if provided
if ($new_price !== null && $new_price < 0) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Price cannot be negative.']);
    die();
}

// Validate price maximum ($9999.99)
if ($new_price !== null && $new_price > 9999.99) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Price cannot exceed $9999.99.']);
    die();
}

// Get current item data
$query = "SELECT * FROM item_prices WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    echo json_encode(['status' => 'Error', 'message' => 'Item not found.']);
    die();
}

// Build update query based on what's provided
$updates = [];
$params = [];
$types = '';
$changes = [];

if ($new_price !== null && $new_price != floatval($item['price'])) {
    $updates[] = "price = ?";
    $params[] = $new_price;
    $types .= 'd';
    $changes['price'] = ['old' => floatval($item['price']), 'new' => $new_price];
}

if ($new_active !== null && $new_active != intval($item['active'])) {
    $updates[] = "active = ?";
    $params[] = $new_active;
    $types .= 'i';
    $changes['active'] = ['old' => intval($item['active']), 'new' => $new_active];
}

if (empty($updates)) {
    echo json_encode(['status' => 'Success', 'message' => 'No changes made.']);
    die();
}

// Add item_id to params
$params[] = $item_id;
$types .= 'i';

$updateQuery = "UPDATE item_prices SET " . implode(', ', $updates) . " WHERE id = ?";
$updateStmt = $mysqli->prepare($updateQuery);
$updateStmt->bind_param($types, ...$params);

if (!$updateStmt->execute()) {
    log_activity(
        $mysqli,
        'item_price_update_failed',
        array('item_id' => $item_id, 'item_code' => $item['item_code'], 'error' => $mysqli->error),
        false,
        "Failed to update item price for " . $item['item_code'],
        $current_user_id
    );

    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Failed to update item.']);
    die();
}

$updateStmt->close();

// Log the changes
$change_description = [];
if (isset($changes['price'])) {
    $change_description[] = "price: $" . number_format($changes['price']['old'], 2) . " -> $" . number_format($changes['price']['new'], 2);
}
if (isset($changes['active'])) {
    $change_description[] = "active: " . ($changes['active']['old'] ? 'Yes' : 'No') . " -> " . ($changes['active']['new'] ? 'Yes' : 'No');
}

log_activity(
    $mysqli,
    'item_price_updated',
    array(
        'item_id' => $item_id,
        'item_code' => $item['item_code'],
        'changes' => $changes
    ),
    true,
    "Item price updated for " . $item['item_code'] . ": " . implode(', ', $change_description),
    $current_user_id
);

echo json_encode([
    'status' => 'Success',
    'message' => 'Item updated successfully.',
    'item_code' => $item['item_code'],
    'changes' => $changes
]);

$mysqli->close();
?>
