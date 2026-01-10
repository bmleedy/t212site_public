<?php
/**
 * Create Order API
 *
 * Creates a new order with line items after payment.
 * This is a public endpoint - no authentication required.
 *
 * Parameters:
 *   customer_name - Customer's full name
 *   customer_email - Customer's email address
 *   customer_phone - Customer's phone number
 *   shipping_address - Delivery/pickup address
 *   order_type - Type of order (e.g., 'tshirt', 'merchandise')
 *   items - JSON array of items: [{"item_code": "tshirt_m", "quantity": 2}, ...]
 *   paypal_order_id - (optional) PayPal order ID for payment tracking
 */

header('Content-Type: application/json');
require 'connect.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
require_once(__DIR__ . '/../includes/store_email.php');

// Validate required fields
$customer_name = validate_string_post('customer_name', true);
$customer_email = validate_email_post('customer_email', true);
$customer_phone = validate_string_post('customer_phone', true);
$shipping_address = validate_string_post('shipping_address', true);
$order_type = validate_string_post('order_type', false, 'merchandise');
$paypal_order_id = validate_string_post('paypal_order_id', false, '');

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

// Get source IP
$source_ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $source_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
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
