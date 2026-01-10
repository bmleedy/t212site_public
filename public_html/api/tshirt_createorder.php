<?php
/**
 * T-Shirt Order Creation API
 *
 * Creates a new T-shirt order after PayPal payment.
 * This is a public endpoint - no authentication required.
 */

header('Content-Type: application/json');
require 'connect.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
require_once(__DIR__ . '/../includes/tshirt_email.php');

// Validate required fields
$customer_name = validate_string_post('customer_name', true);
$customer_email = validate_email_post('customer_email', true);
$customer_phone = validate_string_post('customer_phone', true);
$shipping_address = validate_string_post('shipping_address', true);
$paypal_order_id = validate_string_post('paypal_order_id', false, '');

// Validate quantities (all optional but at least one must be > 0)
$qty_xs = validate_int_post('qty_xs', false, 0);
$qty_s = validate_int_post('qty_s', false, 0);
$qty_m = validate_int_post('qty_m', false, 0);
$qty_l = validate_int_post('qty_l', false, 0);
$qty_xl = validate_int_post('qty_xl', false, 0);
$qty_xxl = validate_int_post('qty_xxl', false, 0);

// Ensure quantities are non-negative
$qty_xs = max(0, $qty_xs);
$qty_s = max(0, $qty_s);
$qty_m = max(0, $qty_m);
$qty_l = max(0, $qty_l);
$qty_xl = max(0, $qty_xl);
$qty_xxl = max(0, $qty_xxl);

// Check at least one item is ordered
$total_items = $qty_xs + $qty_s + $qty_m + $qty_l + $qty_xl + $qty_xxl;
if ($total_items === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Please select at least one item to order.']);
    die();
}

// Get prices from database
$prices = [];
$priceQuery = "SELECT item_code, price FROM item_prices WHERE item_category = 'tshirt' AND active = 1";
$priceResult = $mysqli->query($priceQuery);
if ($priceResult) {
    while ($row = $priceResult->fetch_assoc()) {
        $prices[$row['item_code']] = floatval($row['price']);
    }
}

// Calculate total
$total_amount = 0;
$total_amount += $qty_xs * (isset($prices['tshirt_xs']) ? $prices['tshirt_xs'] : 15.00);
$total_amount += $qty_s * (isset($prices['tshirt_s']) ? $prices['tshirt_s'] : 15.00);
$total_amount += $qty_m * (isset($prices['tshirt_m']) ? $prices['tshirt_m'] : 15.00);
$total_amount += $qty_l * (isset($prices['tshirt_l']) ? $prices['tshirt_l'] : 15.00);
$total_amount += $qty_xl * (isset($prices['tshirt_xl']) ? $prices['tshirt_xl'] : 15.00);
$total_amount += $qty_xxl * (isset($prices['tshirt_xxl']) ? $prices['tshirt_xxl'] : 15.00);

// Get source IP
$source_ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $source_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
}

// Insert order into database
$query = "INSERT INTO tshirt_orders (
    customer_name, customer_email, customer_phone, shipping_address,
    qty_xs, qty_s, qty_m, qty_l, qty_xl, qty_xxl,
    total_amount, paid, paid_date, paypal_order_id, source_ip
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?)";

$stmt = $mysqli->prepare($query);
$stmt->bind_param(
    'ssssiiiiiidss',
    $customer_name,
    $customer_email,
    $customer_phone,
    $shipping_address,
    $qty_xs,
    $qty_s,
    $qty_m,
    $qty_l,
    $qty_xl,
    $qty_xxl,
    $total_amount,
    $paypal_order_id,
    $source_ip
);

if (!$stmt->execute()) {
    log_activity(
        $mysqli,
        'tshirt_order_failed',
        array(
            'email' => $customer_email,
            'error' => $mysqli->error,
            'source_ip' => $source_ip
        ),
        false,
        "Failed to create T-shirt order: " . $mysqli->error,
        0
    );

    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Failed to create order. Please try again.']);
    die();
}

$order_id = $mysqli->insert_id;
$stmt->close();

// Log successful order creation
log_activity(
    $mysqli,
    'tshirt_order_created',
    array(
        'order_id' => $order_id,
        'email' => $customer_email,
        'total' => $total_amount,
        'items' => $total_items,
        'source_ip' => $source_ip
    ),
    true,
    "T-shirt order #$order_id created from IP $source_ip - $total_items items, \$$total_amount",
    0
);

// Send confirmation email to customer
send_tshirt_order_confirmation($mysqli, $order_id);

// Send notification to treasurers
send_tshirt_order_notification($mysqli, $order_id);

// Return success
echo json_encode([
    'status' => 'Success',
    'order_id' => $order_id,
    'total' => $total_amount,
    'message' => 'Your order has been placed successfully!'
]);

$mysqli->close();
?>
