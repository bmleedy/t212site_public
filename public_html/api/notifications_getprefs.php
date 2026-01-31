<?php
/**
 * Get Notification Preferences API
 *
 * Returns the current user's notification preferences.
 * Reads from the users.notif_preferences JSON column.
 * Requires authentication.
 */

session_start();
require 'auth_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_csrf();

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Get user's notification preferences from the users table
$query = "SELECT notif_preferences FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Parse JSON preferences (may be null or empty)
$preferences = [];
if ($row && !empty($row['notif_preferences'])) {
    $decoded = json_decode($row['notif_preferences'], true);
    if (is_array($decoded)) {
        $preferences = $decoded;
    }
}

// Define available notification types with defaults
$available_types = [
    'tshirt_order' => [
        'label' => 'T-Shirt Order Notifications',
        'description' => 'Email me when a new T-shirt order is placed',
        'default' => true,
        'requires_permission' => 'trs'
    ]
];

// Build response with all available types
$response_prefs = [];
foreach ($available_types as $type => $info) {
    // Check if user has required permission
    $has_permission = true;
    if (!empty($info['requires_permission'])) {
        $has_permission = has_permission($info['requires_permission']);
    }

    if ($has_permission) {
        // Use stored preference if set, otherwise use default
        $enabled = isset($preferences[$type]) ? (bool)$preferences[$type] : $info['default'];

        $response_prefs[] = [
            'type' => $type,
            'label' => $info['label'],
            'description' => $info['description'],
            'enabled' => $enabled
        ];
    }
}

echo json_encode([
  'status' => 'Success',
  'preferences' => $response_prefs
]);

// Log activity for audit trail
log_activity(
  $mysqli,
  'notification_prefs_retrieved',
  array('user_id' => $current_user_id, 'pref_count' => count($response_prefs)),
  true,
  "Retrieved notification preferences for user $current_user_id",
  $current_user_id
);

$mysqli->close();
?>
