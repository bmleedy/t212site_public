<?php
/**
 * T-Shirt Order Confirmation Page
 *
 * Shows order confirmation after successful payment.
 * No authentication required.
 *
 * SECURITY: Rate limiting and email verification required to prevent order enumeration.
 */

require_once(__DIR__ . '/api/connect.php');
require_once(__DIR__ . '/includes/activity_logger.php');

// Start session with secure cookie settings
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Get source IP - use REMOTE_ADDR only to prevent IP spoofing
$source_ip = $_SERVER['REMOTE_ADDR'];

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$verify_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$order = null;
$order_items = [];
$error_message = '';
$rate_limited = false;

// Rate limiting: Check if this IP has made more than 20 order lookups in the last hour
// We use the activity_log table to count recent lookups
$rate_limit_query = "SELECT COUNT(*) as lookup_count FROM activity_log
                     WHERE action = 'order_lookup'
                     AND values_json LIKE ?
                     AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$ip_pattern = '%"source_ip":"' . $mysqli->real_escape_string($source_ip) . '"%';
$rate_stmt = $mysqli->prepare($rate_limit_query);
$rate_stmt->bind_param('s', $ip_pattern);
$rate_stmt->execute();
$rate_result = $rate_stmt->get_result();
$rate_row = $rate_result->fetch_assoc();
$rate_stmt->close();

if ($rate_row['lookup_count'] >= 20) {
    $rate_limited = true;
    log_activity(
        $mysqli,
        'order_lookup_rate_limited',
        array(
            'source_ip' => $source_ip,
            'order_id_requested' => $order_id,
            'lookup_count_last_hour' => $rate_row['lookup_count']
        ),
        false,
        "Order lookup rate limited - IP $source_ip exceeded 20 lookups/hour",
        0
    );
    $error_message = 'Too many order lookup attempts. Please try again later or check your email for order confirmation.';
}

if (!$rate_limited && $order_id > 0) {
    // Require email verification to prevent enumeration
    if (empty($verify_email)) {
        log_activity(
            $mysqli,
            'order_lookup',
            array(
                'source_ip' => $source_ip,
                'order_id' => $order_id,
                'email_provided' => false,
                'result' => 'email_required'
            ),
            false,
            "Order lookup failed - email not provided for order #$order_id from IP $source_ip",
            0
        );
        $error_message = 'Please use the link from your confirmation email to view your order, or enter your email address.';
    } else {
        // Get order header - only if email matches
        $query = "SELECT * FROM orders WHERE id = ? AND customer_email = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('is', $order_id, $verify_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();

        if ($order) {
            // Log successful lookup
            log_activity(
                $mysqli,
                'order_lookup',
                array(
                    'source_ip' => $source_ip,
                    'order_id' => $order_id,
                    'email_provided' => true,
                    'result' => 'success'
                ),
                true,
                "Order #$order_id viewed successfully from IP $source_ip",
                0
            );

            // Get order items
            $itemsQuery = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC";
            $itemsStmt = $mysqli->prepare($itemsQuery);
            $itemsStmt->bind_param('i', $order_id);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
            while ($item = $itemsResult->fetch_assoc()) {
                $order_items[] = $item;
            }
            $itemsStmt->close();
        } else {
            // Log failed lookup (order not found or email mismatch)
            log_activity(
                $mysqli,
                'order_lookup',
                array(
                    'source_ip' => $source_ip,
                    'order_id' => $order_id,
                    'email_provided' => true,
                    'result' => 'not_found_or_email_mismatch'
                ),
                false,
                "Order lookup failed - order #$order_id not found or email mismatch from IP $source_ip",
                0
            );
            $error_message = 'Order not found. Please check your order number and email address.';
        }
    }
}

require "includes/header.html";
?>

<br>
<div class='row'>
    <?php require "includes/sidebar.html"; ?>
    <div class="large-9 columns">
        <div class="panel">
            <?php if ($order): ?>
                <?php include("templates/TShirtOrderComplete.html"); ?>
            <?php elseif ($rate_limited): ?>
                <h2>Rate Limit Exceeded</h2>
                <div data-alert class="alert-box warning">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <p>For security purposes, order lookups are limited. If you need to view your order, please check your email for the confirmation that was sent when you placed your order.</p>
                <p><a href="TShirtOrder.php" class="button">Back to T-Shirt Orders</a></p>
            <?php elseif (!empty($error_message)): ?>
                <h2>Order Lookup</h2>
                <div data-alert class="alert-box info">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <p>To view your order confirmation, please enter your order number and email address below:</p>
                <form method="get" action="TShirtOrderComplete.php">
                    <div class="row">
                        <div class="large-6 columns">
                            <label>Order Number
                                <input type="number" name="order_id" value="<?php echo intval($order_id); ?>" required min="1" placeholder="Enter order number">
                            </label>
                        </div>
                        <div class="large-6 columns">
                            <label>Email Address
                                <input type="email" name="email" value="<?php echo htmlspecialchars($verify_email); ?>" required placeholder="Enter email used for order">
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="button">View Order</button>
                </form>
                <hr>
                <p><a href="TShirtOrder.php" class="button secondary">Back to T-Shirt Orders</a></p>
            <?php else: ?>
                <h2>Order Lookup</h2>
                <p>To view your order confirmation, please enter your order number and email address below:</p>
                <form method="get" action="TShirtOrderComplete.php">
                    <div class="row">
                        <div class="large-6 columns">
                            <label>Order Number
                                <input type="number" name="order_id" required min="1" placeholder="Enter order number">
                            </label>
                        </div>
                        <div class="large-6 columns">
                            <label>Email Address
                                <input type="email" name="email" required placeholder="Enter email used for order">
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="button">View Order</button>
                </form>
                <hr>
                <p><a href="TShirtOrder.php" class="button secondary">Back to T-Shirt Orders</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
