<?php
/**
 * Store Email Functions
 *
 * Generic functions to send order confirmation and notification emails.
 * Supports any order type (T-shirts, merchandise, etc.)
 */

require_once(__DIR__ . '/../login/config/config.php');
require_once(__DIR__ . '/../login/libraries/PHPMailer.php');
require_once(__DIR__ . '/activity_logger.php');

/**
 * Create and configure a PHPMailer instance
 *
 * @return PHPMailer Configured mailer instance
 */
function create_mailer() {
    $mail = new PHPMailer;

    if (EMAIL_USE_SMTP) {
        $mail->IsSMTP();
        $mail->SMTPAuth = EMAIL_SMTP_AUTH;
        if (defined('EMAIL_SMTP_ENCRYPTION')) {
            $mail->SMTPSecure = EMAIL_SMTP_ENCRYPTION;
        }
        $mail->Host = EMAIL_SMTP_HOST;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->Port = EMAIL_SMTP_PORT;
    } else {
        $mail->IsMail();
    }

    return $mail;
}

/**
 * Get order with items from database
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return array|null Order data with items, or null if not found
 */
function get_order_with_items($mysqli, $order_id) {
    // Get order header
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        return null;
    }

    // Get order items
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC";
    $itemsStmt = $mysqli->prepare($itemsQuery);
    $itemsStmt->bind_param('i', $order_id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    $order['items'] = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $order['items'][] = $item;
    }
    $itemsStmt->close();

    return $order;
}

/**
 * Build order items summary for email
 *
 * @param array $order Order data with items array
 * @return string Formatted order items list
 */
function build_order_items_list($order) {
    $lines = [];

    if (isset($order['items']) && is_array($order['items'])) {
        foreach ($order['items'] as $item) {
            $qty = intval($item['quantity']);
            $name = $item['item_name'];
            $price = number_format(floatval($item['unit_price']), 2);
            $total = number_format(floatval($item['line_total']), 2);
            $lines[] = "  - $name x $qty @ \$$price = \$$total";
        }
    }

    return implode("\r\n", $lines);
}

/**
 * Get order type display name
 *
 * @param string $order_type The order type code
 * @return string Human-readable order type name
 */
function get_order_type_name($order_type) {
    $types = [
        'tshirt' => 'T-Shirt',
        'merchandise' => 'Merchandise',
    ];
    return isset($types[$order_type]) ? $types[$order_type] : ucfirst($order_type);
}

/**
 * Send order confirmation email to customer
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return bool True on success, false on failure
 */
function send_order_confirmation($mysqli, $order_id) {
    // Get order with items
    $order = get_order_with_items($mysqli, $order_id);

    if (!$order) {
        error_log("Order confirmation: Order #$order_id not found");
        return false;
    }

    $order_type_name = get_order_type_name($order['order_type']);

    $mail = create_mailer();
    $mail->AddAddress($order['customer_email']);
    $mail->From = EMAIL_SMTP_USERNAME;
    $mail->FromName = 'Troop 212';
    $mail->Subject = "Troop 212 $order_type_name Order Confirmation - Order #" . $order_id;

    $items_list = build_order_items_list($order);

    $body = "Thank you for your order!\r\n\r\n";
    $body .= "Order Number: #" . $order_id . "\r\n";
    $body .= "Order Type: " . $order_type_name . "\r\n";
    $body .= "Order Date: " . date('F j, Y', strtotime($order['order_date'])) . "\r\n\r\n";
    $body .= "Items Ordered:\r\n";
    $body .= $items_list . "\r\n\r\n";
    $body .= "Total: $" . number_format($order['total_amount'], 2) . "\r\n\r\n";
    $body .= "Pickup Information:\r\n";
    $body .= "Your order will be available for pickup at the next troop meeting.\r\n";
    $body .= "We will contact you when your order is ready.\r\n\r\n";
    $body .= "If you have any questions, please contact us.\r\n\r\n";
    $body .= "Thank you for supporting Troop 212!\r\n";

    $mail->Body = $body;

    if (!$mail->Send()) {
        log_activity(
            $mysqli,
            'order_confirmation_email_failed',
            array('order_id' => $order_id, 'order_type' => $order['order_type'], 'email' => $order['customer_email'], 'error' => $mail->ErrorInfo),
            false,
            "Failed to send order confirmation for order #$order_id",
            0
        );
        error_log("Failed to send order confirmation: " . $mail->ErrorInfo);
        return false;
    }

    log_activity(
        $mysqli,
        'order_confirmation_email_sent',
        array('order_id' => $order_id, 'order_type' => $order['order_type'], 'email' => $order['customer_email']),
        true,
        "Order confirmation sent for order #$order_id to " . $order['customer_email'],
        0
    );

    return true;
}

/**
 * Send order notification to treasurers who have enabled notifications
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return int Number of notifications sent
 */
function send_order_notification($mysqli, $order_id) {
    // Get order with items
    $order = get_order_with_items($mysqli, $order_id);

    if (!$order) {
        error_log("Order notification: Order #$order_id not found");
        return 0;
    }

    $order_type = $order['order_type'];
    $order_type_name = get_order_type_name($order_type);

    // Find all users with treasurer (trs) permission
    $query = "SELECT u.user_id, u.user_email, u.user_first, u.user_last, u.notif_preferences
              FROM users u
              WHERE u.user_access LIKE '%trs%'
                AND u.user_active = 1
                AND u.user_email IS NOT NULL
                AND u.user_email != ''";
    $result = $mysqli->query($query);

    if (!$result) {
        error_log("Order notification: Failed to query treasurers");
        return 0;
    }

    $notifications_sent = 0;

    while ($user = $result->fetch_assoc()) {
        // Check notification preferences
        // Supports both specific type (e.g., 'tshirt_order') and generic 'new_order'
        $notify = true;  // Default to enabled

        if (!empty($user['notif_preferences'])) {
            $prefs = json_decode($user['notif_preferences'], true);
            if (is_array($prefs)) {
                // Check for specific order type notification setting
                $specific_key = $order_type . '_order';
                if (isset($prefs[$specific_key])) {
                    $notify = (bool)$prefs[$specific_key];
                }
                // Also check generic 'new_order' if specific not set
                elseif (isset($prefs['new_order'])) {
                    $notify = (bool)$prefs['new_order'];
                }
            }
        }

        if (!$notify) {
            continue;
        }

        // Send notification email
        $mail = create_mailer();
        $mail->AddAddress($user['user_email']);
        $mail->From = EMAIL_SMTP_USERNAME;
        $mail->FromName = 'Troop 212 Website';
        $mail->Subject = "New $order_type_name Order Received - Order #" . $order_id;

        $items_list = build_order_items_list($order);

        $body = "A new $order_type_name order has been placed.\r\n\r\n";
        $body .= "Order Number: #" . $order_id . "\r\n";
        $body .= "Order Type: " . $order_type_name . "\r\n";
        $body .= "Order Date: " . date('F j, Y g:i A', strtotime($order['order_date'])) . "\r\n\r\n";
        $body .= "Customer Information:\r\n";
        $body .= "  Name: " . $order['customer_name'] . "\r\n";
        $body .= "  Email: " . $order['customer_email'] . "\r\n";
        $body .= "  Phone: " . $order['customer_phone'] . "\r\n";
        $body .= "  Address: " . $order['shipping_address'] . "\r\n\r\n";
        $body .= "Items Ordered:\r\n";
        $body .= $items_list . "\r\n\r\n";
        $body .= "Total: $" . number_format($order['total_amount'], 2) . "\r\n\r\n";
        $body .= "View all orders: https://t212.org/ManageTShirtOrders.php\r\n\r\n";
        $body .= "---\r\n";
        $body .= "To disable these notifications, update your preferences in your user profile.\r\n";

        $mail->Body = $body;

        if ($mail->Send()) {
            $notifications_sent++;
            log_activity(
                $mysqli,
                'order_treasurer_notification_sent',
                array('order_id' => $order_id, 'order_type' => $order_type, 'treasurer_id' => $user['user_id'], 'email' => $user['user_email']),
                true,
                "Order notification sent to treasurer " . $user['user_email'],
                0
            );
        } else {
            log_activity(
                $mysqli,
                'order_treasurer_notification_failed',
                array('order_id' => $order_id, 'order_type' => $order_type, 'treasurer_id' => $user['user_id'], 'error' => $mail->ErrorInfo),
                false,
                "Failed to send order notification to treasurer",
                0
            );
            error_log("Failed to send order notification to " . $user['user_email'] . ": " . $mail->ErrorInfo);
        }
    }

    return $notifications_sent;
}

// ============================================================================
// Backward compatibility functions for T-shirt specific code
// ============================================================================

/**
 * Send T-shirt order confirmation (backward compatibility wrapper)
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return bool True on success, false on failure
 */
function send_tshirt_order_confirmation($mysqli, $order_id) {
    return send_order_confirmation($mysqli, $order_id);
}

/**
 * Send T-shirt order notification (backward compatibility wrapper)
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return int Number of notifications sent
 */
function send_tshirt_order_notification($mysqli, $order_id) {
    return send_order_notification($mysqli, $order_id);
}
?>
