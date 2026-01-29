<?php
/**
 * Get Orders API
 *
 * Returns orders with their items for admin viewing.
 * Requires authentication and treasurer/webmaster/admin permission.
 *
 * Parameters:
 *   status - (optional) Filter: 'all', 'unfulfilled', 'fulfilled'. Default: 'all'
 *   order_type - (optional) Filter by order type (e.g., 'tshirt')
 *   start_date - (optional) Start date filter (YYYY-MM-DD)
 *   end_date - (optional) End date filter (YYYY-MM-DD)
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['trs', 'wm', 'sa']);
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Optional filters
$status = validate_string_post('status', false, 'all');
$order_type = validate_string_post('order_type', false, '');
$start_date = validate_string_post('start_date', false, '');
$end_date = validate_string_post('end_date', false, '');

// Build query
$query = "SELECT o.*,
          (SELECT CONCAT(user_first, ' ', user_last) FROM users WHERE user_id = o.fulfilled_by) as fulfilled_by_name
          FROM orders o
          WHERE 1=1";
$params = [];
$types = '';

// Apply status filter
if ($status === 'unfulfilled') {
    $query .= " AND o.fulfilled = 0";
} elseif ($status === 'fulfilled') {
    $query .= " AND o.fulfilled = 1";
}

// Apply order type filter
if (!empty($order_type)) {
    $query .= " AND o.order_type = ?";
    $params[] = $order_type;
    $types .= 's';
}

// Apply date filter
if (!empty($start_date)) {
    $query .= " AND DATE(o.order_date) >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $query .= " AND DATE(o.order_date) <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$query .= " ORDER BY o.order_date DESC";

// Execute query
if (!empty($params)) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

$orders = [];
$order_ids = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $order_id = intval($row['id']);
        $order_ids[] = $order_id;

        $orders[$order_id] = [
            'id' => $order_id,
            'order_type' => $row['order_type'],
            'order_date' => $row['order_date'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'shipping_address' => $row['shipping_address'],
            'total_amount' => floatval($row['total_amount']),
            'paid' => intval($row['paid']),
            'paid_date' => $row['paid_date'],
            'fulfilled' => intval($row['fulfilled']),
            'fulfilled_date' => $row['fulfilled_date'],
            'fulfilled_by' => $row['fulfilled_by'],
            'fulfilled_by_name' => $row['fulfilled_by_name'],
            'notes' => $row['notes'],
            'items' => []
        ];
    }
}

if (isset($stmt)) {
    $stmt->close();
}

// Get order items for all fetched orders
if (!empty($order_ids)) {
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $itemsQuery = "SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id ASC";
    $itemsStmt = $mysqli->prepare($itemsQuery);
    $itemsStmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    while ($item = $itemsResult->fetch_assoc()) {
        $oid = intval($item['order_id']);
        if (isset($orders[$oid])) {
            $orders[$oid]['items'][] = [
                'item_code' => $item['item_code'],
                'item_name' => $item['item_name'],
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'line_total' => floatval($item['line_total'])
            ];
        }
    }
    $itemsStmt->close();
}

// Calculate total quantity for each order
foreach ($orders as &$order) {
    $total_qty = 0;
    foreach ($order['items'] as $item) {
        $total_qty += $item['quantity'];
    }
    $order['total_qty'] = $total_qty;
}
unset($order);

// Get summary stats
$statsQuery = "SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN fulfilled = 0 THEN 1 ELSE 0 END) as unfulfilled_orders,
    SUM(CASE WHEN fulfilled = 1 THEN 1 ELSE 0 END) as fulfilled_orders,
    SUM(total_amount) as total_revenue
    FROM orders";

// Add type filter to stats if specified
if (!empty($order_type)) {
    $statsQuery .= " WHERE order_type = ?";
    $statsStmt = $mysqli->prepare($statsQuery);
    $statsStmt->bind_param('s', $order_type);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();
} else {
    $statsResult = $mysqli->query($statsQuery);
    $stats = $statsResult ? $statsResult->fetch_assoc() : null;
}

echo json_encode([
    'status' => 'Success',
    'orders' => array_values($orders),
    'stats' => [
        'total_orders' => intval($stats['total_orders'] ?? 0),
        'unfulfilled_orders' => intval($stats['unfulfilled_orders'] ?? 0),
        'fulfilled_orders' => intval($stats['fulfilled_orders'] ?? 0),
        'total_revenue' => floatval($stats['total_revenue'] ?? 0)
    ]
]);

$mysqli->close();
?>
