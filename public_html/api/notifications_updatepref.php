<?php
/**
 * Update Notification Preference API
 *
 * Updates a notification preference for the current user.
 * Stores in the users.notif_preferences JSON column.
 * Requires authentication.
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_csrf();

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Get notification type and enabled status
$notification_type = validate_string_post('notification_type', true);
$enabled = validate_bool_post('enabled', true);

// Validate notification type
$valid_types = ['tshirt_order'];
if (!in_array($notification_type, $valid_types)) {
    http_response_code(400);
    echo json_encode(['status' => 'Error', 'message' => 'Invalid notification type.']);
    die();
}

// Get current preferences from users table
$query = "SELECT notif_preferences FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Parse existing preferences or start with empty array
$preferences = [];
if ($row && !empty($row['notif_preferences'])) {
    $decoded = json_decode($row['notif_preferences'], true);
    if (is_array($decoded)) {
        $preferences = $decoded;
    }
}

// Update the specific preference
$preferences[$notification_type] = $enabled;

// Save back to database
$prefs_json = json_encode($preferences);
$updateQuery = "UPDATE users SET notif_preferences = ? WHERE user_id = ?";
$updateStmt = $mysqli->prepare($updateQuery);
$updateStmt->bind_param('si', $prefs_json, $current_user_id);
$success = $updateStmt->execute();
$updateStmt->close();

if (!$success) {
    log_activity(
        $mysqli,
        'notification_pref_update_failed',
        array('notification_type' => $notification_type, 'enabled' => $enabled, 'error' => $mysqli->error),
        false,
        "Failed to update notification preference",
        $current_user_id
    );

    http_response_code(500);
    echo json_encode(['status' => 'Error', 'message' => 'Failed to update preference.']);
    die();
}

log_activity(
    $mysqli,
    'notification_pref_updated',
    array('notification_type' => $notification_type, 'enabled' => $enabled),
    true,
    "Notification preference updated: $notification_type = " . ($enabled ? 'enabled' : 'disabled'),
    $current_user_id
);

echo json_encode([
    'status' => 'Success',
    'message' => 'Notification preference updated.',
    'notification_type' => $notification_type,
    'enabled' => $enabled
]);

$mysqli->close();
?>
