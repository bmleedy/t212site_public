<?php
/**
 * T-Shirt Store Configuration Update API
 *
 * Updates T-shirt store configuration settings.
 * Requires treasurer, webmaster, or admin permission.
 */

if ($_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'Error', 'message' => 'Not an AJAX request']);
    die();
}

session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'Error', 'message' => 'Not authenticated']);
    die();
}

// Get user permissions
require 'connect.php';

$user_id = $_SESSION['user_id'];
$query = "SELECT user_access FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$access = explode('.', $row['user_access'] ?? '');

// Check if user has permission
if (!in_array("trs", $access) && !in_array("wm", $access) && !in_array("sa", $access)) {
    echo json_encode(['status' => 'Error', 'message' => 'Access denied']);
    die();
}

// Get the config key and value from POST
if (!isset($_POST['config_key']) || !isset($_POST['config_value'])) {
    echo json_encode(['status' => 'Error', 'message' => 'Missing required parameters']);
    die();
}

$config_key = $_POST['config_key'];
$config_value = $_POST['config_value'];

// Whitelist of allowed config keys
$allowed_keys = ['tshirt_orders_enabled', 'tshirt_image_url'];
if (!in_array($config_key, $allowed_keys)) {
    echo json_encode(['status' => 'Error', 'message' => 'Invalid config key']);
    die();
}

// Check if config key exists
$check_query = "SELECT config_key FROM store_config WHERE config_key = ?";
$check_stmt = $mysqli->prepare($check_query);
$check_stmt->bind_param('s', $config_key);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$exists = $check_result->num_rows > 0;
$check_stmt->close();

if ($exists) {
    // Update existing config
    $update_query = "UPDATE store_config SET config_value = ? WHERE config_key = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param('ss', $config_value, $config_key);
    $success = $update_stmt->execute();
    $update_stmt->close();
} else {
    // Insert new config
    $insert_query = "INSERT INTO store_config (config_key, config_value) VALUES (?, ?)";
    $insert_stmt = $mysqli->prepare($insert_query);
    $insert_stmt->bind_param('ss', $config_key, $config_value);
    $success = $insert_stmt->execute();
    $insert_stmt->close();
}

if ($success) {
    // Log the activity
    require_once(__DIR__ . '/../includes/activity_logger.php');
    log_activity(
        $mysqli,
        'update_store_config',
        array(
            'config_key' => $config_key,
            'config_value' => $config_value
        ),
        true,
        "Store config updated: $config_key = $config_value",
        $user_id
    );

    echo json_encode(['status' => 'Success', 'message' => 'Configuration updated']);
} else {
    echo json_encode(['status' => 'Error', 'message' => 'Failed to update configuration']);
}

$mysqli->close();
?>
