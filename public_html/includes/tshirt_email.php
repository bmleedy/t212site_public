<?php
/**
 * T-Shirt Order Email Functions
 *
 * Functions to send order confirmation and notification emails.
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
 * Build order items summary for email
 *
 * @param array $order Order data from database
 * @return string Formatted order items list
 */
function build_order_items_list($order) {
    $items = [];
    $sizes = [
        'qty_xs' => 'XS',
        'qty_s' => 'S',
        'qty_m' => 'M',
        'qty_l' => 'L',
        'qty_xl' => 'XL',
        'qty_xxl' => 'XXL'
    ];

    foreach ($sizes as $field => $label) {
        if (isset($order[$field]) && intval($order[$field]) > 0) {
            $items[] = "  - Size $label: " . intval($order[$field]);
        }
    }

    return implode("\r\n", $items);
}

/**
 * Send order confirmation email to customer
 *
 * @param mysqli $mysqli Database connection
 * @param int $order_id The order ID
 * @return bool True on success, false on failure
 */
function send_tshirt_order_confirmation($mysqli, $order_id) {
    // Get order details
    $query = "SELECT * FROM tshirt_orders WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        error_log("T-shirt order confirmation: Order #$order_id not found");
        return false;
    }

    $mail = create_mailer();
    $mail->AddAddress($order['customer_email']);
    $mail->From = EMAIL_SMTP_USERNAME;
    $mail->FromName = 'Troop 212';
    $mail->Subject = "Troop 212 T-Shirt Order Confirmation - Order #" . $order_id;

    $items_list = build_order_items_list($order);

    $body = "Thank you for your T-shirt order!\r\n\r\n";
    $body .= "Order Number: #" . $order_id . "\r\n";
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
            'tshirt_confirmation_email_failed',
            array('order_id' => $order_id, 'email' => $order['customer_email'], 'error' => $mail->ErrorInfo),
            false,
            "Failed to send T-shirt order confirmation for order #$order_id",
            0
        );
        error_log("Failed to send T-shirt order confirmation: " . $mail->ErrorInfo);
        return false;
    }

    log_activity(
        $mysqli,
        'tshirt_confirmation_email_sent',
        array('order_id' => $order_id, 'email' => $order['customer_email']),
        true,
        "T-shirt order confirmation sent for order #$order_id to " . $order['customer_email'],
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
function send_tshirt_order_notification($mysqli, $order_id) {
    // Get order details
    $query = "SELECT * FROM tshirt_orders WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        error_log("T-shirt order notification: Order #$order_id not found");
        return 0;
    }

    // Find all users with treasurer (trs) permission
    $query = "SELECT u.user_id, u.user_email, u.user_first, u.user_last
              FROM users u
              WHERE u.user_access LIKE '%trs%'
                AND u.user_active = 1
                AND u.user_email IS NOT NULL
                AND u.user_email != ''";
    $result = $mysqli->query($query);

    if (!$result) {
        error_log("T-shirt order notification: Failed to query treasurers");
        return 0;
    }

    $notifications_sent = 0;

    while ($user = $result->fetch_assoc()) {
        // Check if user has enabled tshirt_order notifications
        // Preferences are stored in users.notif_preferences JSON column
        $notify = true;  // Default to enabled if no preference set

        $prefQuery = "SELECT notif_preferences FROM users WHERE user_id = ?";
        $prefStmt = $mysqli->prepare($prefQuery);
        $prefStmt->bind_param('i', $user['user_id']);
        $prefStmt->execute();
        $prefResult = $prefStmt->get_result();
        $prefRow = $prefResult->fetch_assoc();
        $prefStmt->close();

        if ($prefRow && !empty($prefRow['notif_preferences'])) {
            $prefs = json_decode($prefRow['notif_preferences'], true);
            if (is_array($prefs) && isset($prefs['tshirt_order'])) {
                $notify = (bool)$prefs['tshirt_order'];
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
        $mail->Subject = "New T-Shirt Order Received - Order #" . $order_id;

        $items_list = build_order_items_list($order);

        $body = "A new T-shirt order has been placed.\r\n\r\n";
        $body .= "Order Number: #" . $order_id . "\r\n";
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
                'tshirt_treasurer_notification_sent',
                array('order_id' => $order_id, 'treasurer_id' => $user['user_id'], 'email' => $user['user_email']),
                true,
                "T-shirt order notification sent to treasurer " . $user['user_email'],
                0
            );
        } else {
            log_activity(
                $mysqli,
                'tshirt_treasurer_notification_failed',
                array('order_id' => $order_id, 'treasurer_id' => $user['user_id'], 'error' => $mail->ErrorInfo),
                false,
                "Failed to send T-shirt order notification to treasurer",
                0
            );
            error_log("Failed to send T-shirt notification to " . $user['user_email'] . ": " . $mail->ErrorInfo);
        }
    }

    return $notifications_sent;
}
?>
