<?php
/**
 * Create Order API
 *
 * Creates a new order with line items after payment.
 * This is a public endpoint - no authentication required.
 *
 * SECURITY NOTE: This endpoint stores PayPal order ID but does not verify payment
 * with PayPal servers. For production use, implement PayPal webhook/IPN verification.
 * TODO: Implement PayPal payment verification via webhook or server-to-server API call.
 *
 * Parameters:
 *   customer_name - Customer's full name
 *   customer_email - Customer's email address
 *   customer_phone - Customer's phone number
 *   shipping_address - Delivery/pickup address
 *   order_type - Type of order (e.g., 'tshirt', 'merchandise')
 *   items - JSON array of items: [{"item_code": "tshirt_m", "quantity": 2}, ...]
 *   paypal_order_id - PayPal order ID for payment tracking (required)
 */

header('Content-Type: application/json');
require 'connect.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
require_once(__DIR__ . '/../includes/store_email.php');

// Validate required fields with max length constraints
$customer_name = validate_string_post('customer_name', true, null, 100);
$customer_email = validate_email_post('customer_email', true);
$customer_phone = validate_string_post('customer_phone', true, null, 20);
$shipping_address = validate_string_post('shipping_address', true, null, 500);
$order_type = validate_string_post('order_type', false, 'merchandise', 50);
$paypal_order_id = validate_string_post('paypal_order_id', true, null, 50);

// Additional validation for paypal_order_id format
if (empty($paypal_order_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'PayPal order ID is required.']);
    die();
}

// Get items from JSON
$items_json = isset($_POST['items']) ? $_POST['items'] : '';
if (empty($items_json)) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'No items provided.']);
    die();
}

$items = json_decode($items_json, true);
if (!is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Invalid items format or no items selected.']);
    die();
}

// Validate and filter items
$valid_items = [];
$total_quantity = 0;
foreach ($items as $item) {
    if (!isset($item['item_code']) || !isset($item['quantity'])) {
        continue;
    }
    $quantity = intval($item['quantity']);
    if ($quantity > 0) {
        $valid_items[] = [
            'item_code' => trim($item['item_code']),
            'quantity' => $quantity
        ];
        $total_quantity += $quantity;
    }
}

if (count($valid_items) === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Please select at least one item to order.']);
    die();
}

// Get prices from database for all requested items
$item_codes = array_column($valid_items, 'item_code');
$placeholders = str_repeat('?,', count($item_codes) - 1) . '?';
$priceQuery = "SELECT item_code, item_name, price FROM item_prices WHERE item_code IN ($placeholders) AND active = 1";
$stmt = $mysqli->prepare($priceQuery);
$stmt->bind_param(str_repeat('s', count($item_codes)), ...$item_codes);
$stmt->execute();
$priceResult = $stmt->get_result();

$prices = [];
$item_names = [];
while ($row = $priceResult->fetch_assoc()) {
    $prices[$row['item_code']] = floatval($row['price']);
    $item_names[$row['item_code']] = $row['item_name'];
}
$stmt->close();

// Calculate total and prepare order items
$total_amount = 0;
$order_items = [];
foreach ($valid_items as $item) {
    $code = $item['item_code'];
    $qty = $item['quantity'];

    if (!isset($prices[$code])) {
        http_response_code(400);
        echo json_encode(['status' => 'Error', 'message' => "Item '$code' not found or not available."]);
        die();
    }

    $unit_price = $prices[$code];
    $line_total = $qty * $unit_price;
    $total_amount += $line_total;

    $order_items[] = [
        'item_code' => $code,
        'item_name' => $item_names[$code],
        'quantity' => $qty,
        'unit_price' => $unit_price,
        'line_total' => $line_total
    ];
}

// Get source IP - use REMOTE_ADDR only to prevent IP spoofing via X-Forwarded-For
// Note: If behind a trusted proxy, configure the proxy to set a verified header
$source_ip = $_SERVER['REMOTE_ADDR'];

// Rate limiting: Check if same IP has created more than 10 orders in last hour
$rate_limit_query = "SELECT COUNT(*) as order_count FROM orders
                     WHERE source_ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$rate_stmt = $mysqli->prepare($rate_limit_query);
$rate_stmt->bind_param('s', $source_ip);
$rate_stmt->execute();
$rate_result = $rate_stmt->get_result();
$rate_row = $rate_result->fetch_assoc();
$rate_stmt->close();

if ($rate_row['order_count'] >= 10) {
    log_activity(
        $mysqli,
        'order_create_rate_limited',
        array(
            'email' => $customer_email,
            'order_type' => $order_type,
            'source_ip' => $source_ip,
            'order_count_last_hour' => $rate_row['order_count']
        ),
        false,
        "Order creation rate limited - IP $source_ip exceeded 10 orders/hour",
        0
    );

    http_response_code(429);
    echo json_encode(['status' => 'Error', 'message' => 'Too many orders from this IP address. Please try again later.']);
    die();
}

// Start transaction
$mysqli->begin_transaction();

try {
    // Insert order header
    $orderQuery = "INSERT INTO orders (
        order_type, customer_name, customer_email, customer_phone, shipping_address,
        total_amount, paid, paid_date, paypal_order_id, source_ip
    ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?)";

    $orderStmt = $mysqli->prepare($orderQuery);
    $orderStmt->bind_param(
        'sssssdss',
        $order_type,
        $customer_name,
        $customer_email,
        $customer_phone,
        $shipping_address,
        $total_amount,
        $paypal_order_id,
        $source_ip
    );

    if (!$orderStmt->execute()) {
        throw new Exception("Failed to create order: " . $mysqli->error);
    }

    $order_id = $mysqli->insert_id;
    $orderStmt->close();

    // Insert order items
    $itemQuery = "INSERT INTO order_items (order_id, item_code, item_name, quantity, unit_price, line_total)
                  VALUES (?, ?, ?, ?, ?, ?)";
    $itemStmt = $mysqli->prepare($itemQuery);

    foreach ($order_items as $item) {
        $itemStmt->bind_param(
            'issidd',
            $order_id,
            $item['item_code'],
            $item['item_name'],
            $item['quantity'],
            $item['unit_price'],
            $item['line_total']
        );

        if (!$itemStmt->execute()) {
            throw new Exception("Failed to add order item: " . $mysqli->error);
        }
    }
    $itemStmt->close();

    // Commit transaction
    $mysqli->commit();

} catch (Exception $e) {
    $mysqli->rollback();

    log_activity(
        $mysqli,
        'order_create_failed',
        array(
            'email' => $customer_email,
            'order_type' => $order_type,
            'error' => $e->getMessage(),
            'source_ip' => $source_ip
        ),
        false,
        "Failed to create order: " . $e->getMessage(),
        0
    );

    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Failed to create order. Please try again.']);
    die();
}

// Log successful order creation
log_activity(
    $mysqli,
    'order_created',
    array(
        'order_id' => $order_id,
        'order_type' => $order_type,
        'email' => $customer_email,
        'total' => $total_amount,
        'items' => $total_quantity,
        'source_ip' => $source_ip
    ),
    true,
    "Order #$order_id ($order_type) created from IP $source_ip - $total_quantity items, \$$total_amount",
    0
);

// Send confirmation email to customer
send_order_confirmation($mysqli, $order_id);

// Send notification to treasurers
send_order_notification($mysqli, $order_id);

// Return success
echo json_encode([
    'status' => 'Success',
    'order_id' => $order_id,
    'total' => $total_amount,
    'message' => 'Your order has been placed successfully!'
]);

$mysqli->close();
?>
