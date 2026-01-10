<?php
/**
 * T-Shirt Order Confirmation Page
 *
 * Shows order confirmation after successful payment.
 * No authentication required.
 */

require "includes/header.html";
require_once(__DIR__ . '/api/connect.php');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order = null;
$order_items = [];

if ($order_id > 0) {
    // Get order header
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    // Get order items
    if ($order) {
        $itemsQuery = "SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC";
        $itemsStmt = $mysqli->prepare($itemsQuery);
        $itemsStmt->bind_param('i', $order_id);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        while ($item = $itemsResult->fetch_assoc()) {
            $order_items[] = $item;
        }
        $itemsStmt->close();
    }
}
?>

<br>
<div class='row'>
    <?php require "includes/sidebar.html"; ?>
    <div class="large-9 columns">
        <div class="panel">
            <?php if ($order): ?>
                <?php include("templates/TShirtOrderComplete.html"); ?>
            <?php else: ?>
                <h2>Order Not Found</h2>
                <p>Sorry, we couldn't find that order. Please check your email for order confirmation details.</p>
                <p><a href="TShirtOrder.php" class="button">Back to T-Shirt Orders</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require "includes/footer.html"; ?>
