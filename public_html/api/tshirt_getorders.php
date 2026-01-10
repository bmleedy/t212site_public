<?php
/**
 * Get T-Shirt Orders API
 *
 * Returns all T-shirt orders for admin viewing.
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

// Optional filters
$status = validate_string_post('status', false, 'all'); // 'all', 'unfulfilled', 'fulfilled'
$start_date = validate_string_post('start_date', false, '');
$end_date = validate_string_post('end_date', false, '');

// Build query
$query = "SELECT o.*,
          (SELECT CONCAT(user_first, ' ', user_last) FROM users WHERE user_id = o.fulfilled_by) as fulfilled_by_name
          FROM tshirt_orders o
          WHERE 1=1";
$params = [];
$types = '';

// Apply status filter
if ($status === 'unfulfilled') {
    $query .= " AND o.fulfilled = 0";
} elseif ($status === 'fulfilled') {
    $query .= " AND o.fulfilled = 1";
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
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Calculate total quantity
        $total_qty = intval($row['qty_xs']) + intval($row['qty_s']) + intval($row['qty_m']) +
                     intval($row['qty_l']) + intval($row['qty_xl']) + intval($row['qty_xxl']);

        $orders[] = [
            'id' => intval($row['id']),
            'order_date' => $row['order_date'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'customer_phone' => $row['customer_phone'],
            'shipping_address' => $row['shipping_address'],
            'qty_xs' => intval($row['qty_xs']),
            'qty_s' => intval($row['qty_s']),
            'qty_m' => intval($row['qty_m']),
            'qty_l' => intval($row['qty_l']),
            'qty_xl' => intval($row['qty_xl']),
            'qty_xxl' => intval($row['qty_xxl']),
            'total_qty' => $total_qty,
            'total_amount' => floatval($row['total_amount']),
            'paid' => intval($row['paid']),
            'paid_date' => $row['paid_date'],
            'fulfilled' => intval($row['fulfilled']),
            'fulfilled_date' => $row['fulfilled_date'],
            'fulfilled_by' => $row['fulfilled_by'],
            'fulfilled_by_name' => $row['fulfilled_by_name'],
            'notes' => $row['notes']
        ];
    }
}

// Get summary stats
$statsQuery = "SELECT
    COUNT(*) as total_orders,
    SUM(CASE WHEN fulfilled = 0 THEN 1 ELSE 0 END) as unfulfilled_orders,
    SUM(CASE WHEN fulfilled = 1 THEN 1 ELSE 0 END) as fulfilled_orders,
    SUM(total_amount) as total_revenue
    FROM tshirt_orders";
$statsResult = $mysqli->query($statsQuery);
$stats = $statsResult ? $statsResult->fetch_assoc() : null;

echo json_encode([
    'status' => 'Success',
    'orders' => $orders,
    'stats' => [
        'total_orders' => intval($stats['total_orders'] ?? 0),
        'unfulfilled_orders' => intval($stats['unfulfilled_orders'] ?? 0),
        'fulfilled_orders' => intval($stats['fulfilled_orders'] ?? 0),
        'total_revenue' => floatval($stats['total_revenue'] ?? 0)
    ]
]);

$mysqli->close();
?>
