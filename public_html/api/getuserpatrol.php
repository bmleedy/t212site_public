<?php
/**
 * Get User Patrol API
 *
 * Returns the patrol_id for a given user.
 * Used to determine which patrol a user belongs to.
 *
 * Authorization: User can only query their own patrol_id OR must have wm/sa permission.
 */

session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

require_ajax();
$current_user_id = require_authentication();

// Validate CSRF token
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

$user_id = validate_int_post('user_id', false, null);

if (!$user_id) {
  echo json_encode(['status' => 'Error', 'message' => 'User ID is required']);
  die();
}

// Authorization check: User can only query their own patrol OR must have wm/sa permission
$authorized = false;

// Check if user is querying their own patrol
if ($user_id == $current_user_id) {
    $authorized = true;
}

// Check if user has elevated permissions (wm or sa)
if (!$authorized && (has_permission('wm') || has_permission('sa'))) {
    $authorized = true;
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode(['status' => 'Error', 'message' => 'Not authorized to view this user\'s patrol']);
    log_activity(
        $mysqli,
        'get_user_patrol',
        array('requested_user_id' => $user_id),
        false,
        "Unauthorized attempt to access patrol info for user ID: $user_id",
        $current_user_id
    );
    die();
}

$patrol_id = null;
$user_type = null;

// First, get the user type
$query = "SELECT user_type FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$results = $stmt->get_result();

if ($results && $row = $results->fetch_assoc()) {
  $user_type = $row['user_type'];
}
$stmt->close();

// If user is a Scout, get their patrol from scout_info
if ($user_type == 'Scout') {
  $query = "SELECT patrol_id FROM scout_info WHERE user_id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $results = $stmt->get_result();

  if ($results && $row = $results->fetch_assoc()) {
    $patrol_id = $row['patrol_id'];
  }
  $stmt->close();
}

// If no patrol found (adult or scout without patrol), default to Staff patrol (id=1)
if (!$patrol_id) {
  $patrol_id = 1; // Staff patrol
}

$returnMsg = array(
  'status' => 'Success',
  'patrol_id' => $patrol_id,
  'user_type' => escape_html($user_type)
);

echo json_encode($returnMsg);

// Log access to user patrol information (only log when querying another user's data)
if ($user_id != $current_user_id) {
    log_activity(
        $mysqli,
        'get_user_patrol',
        array('requested_user_id' => $user_id, 'patrol_id' => $patrol_id),
        true,
        "Retrieved patrol info for user ID: $user_id (patrol: $patrol_id)",
        $current_user_id
    );
}

die();
?>
