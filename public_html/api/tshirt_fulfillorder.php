<?php
/**
 * Fulfill T-Shirt Order API
 *
 * Marks an order as fulfilled.
 * Requires authentication and treasurer/webmaster/admin permission.
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['trs', 'wm', 'sa']);

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Get order ID
$order_id = validate_int_post('order_id', true);

// Check if order exists
$checkQuery = "SELECT id, fulfilled, customer_name FROM tshirt_orders WHERE id = ?";
$checkStmt = $mysqli->prepare($checkQuery);
$checkStmt->bind_param('i', $order_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$order = $checkResult->fetch_assoc();
$checkStmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'Error', 'message' => 'Order not found.']);
    die();
}

if ($order['fulfilled'] == 1) {
    echo json_encode(['status' => 'Success', 'message' => 'Order was already marked as fulfilled.']);
    die();
}

// Mark order as fulfilled
$updateQuery = "UPDATE tshirt_orders SET fulfilled = 1, fulfilled_date = NOW(), fulfilled_by = ? WHERE id = ?";
$updateStmt = $mysqli->prepare($updateQuery);
$updateStmt->bind_param('ii', $current_user_id, $order_id);

if (!$updateStmt->execute()) {
    log_activity(
        $mysqli,
        'tshirt_order_fulfill_failed',
        array('order_id' => $order_id, 'error' => $mysqli->error),
        false,
        "Failed to fulfill T-shirt order #$order_id",
        $current_user_id
    );

    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Failed to update order.']);
    die();
}

$updateStmt->close();

// Log successful fulfillment
log_activity(
    $mysqli,
    'tshirt_order_fulfilled',
    array('order_id' => $order_id, 'customer_name' => $order['customer_name']),
    true,
    "T-shirt order #$order_id marked as fulfilled",
    $current_user_id
);

echo json_encode([
    'status' => 'Success',
    'message' => 'Order #' . $order_id . ' has been marked as fulfilled.'
]);

$mysqli->close();
?>
