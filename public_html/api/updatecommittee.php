<?php
// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

// Check if user has super admin access
require_permission(['sa']);

header('Content-Type: application/json');
require 'connect.php';

// Validate required parameters
$role_id = validate_int_post('role_id', true);
$role_name = validate_string_post('role_name', true);
$user_id = validate_int_post('user_id', true);
$sort_order = validate_int_post('sort_order', true);

// Validate role name is not empty
if (empty(trim($role_name))) {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Role name cannot be empty.'
  ]);
  log_activity(
    $mysqli,
    'update_committee',
    array('role_id' => $role_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
    false,
    "Failed to update committee role: empty role name",
    $current_user_id
  );
  die();
}

// Verify user exists
$checkUserStmt = $mysqli->prepare("SELECT user_id FROM users WHERE user_id = ?");
$checkUserStmt->bind_param('i', $user_id);
$checkUserStmt->execute();
$checkUserStmt->store_result();

if ($checkUserStmt->num_rows === 0) {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Selected user does not exist.'
  ]);
  $checkUserStmt->close();
  log_activity(
    $mysqli,
    'update_committee',
    array('role_id' => $role_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
    false,
    "Failed to update committee role: user does not exist",
    $current_user_id
  );
  die();
}
$checkUserStmt->close();

// Update the committee role using prepared statement
$updateStmt = $mysqli->prepare("UPDATE committee SET role_name = ?, user_id = ?, sort_order = ? WHERE role_id = ?");
$updateStmt->bind_param('siii', $role_name, $user_id, $sort_order, $role_id);

if ($updateStmt->execute()) {
  if ($updateStmt->affected_rows > 0) {
    echo json_encode([
      'status' => 'Success',
      'message' => 'Committee role updated successfully.'
    ]);
    log_activity(
      $mysqli,
      'update_committee',
      array('role_id' => $role_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
      true,
      "Committee role updated: $role_name (ID: $role_id, User ID: $user_id, Sort: $sort_order)",
      $current_user_id
    );
  } else {
    // No rows affected - either the role doesn't exist or no changes were made
    echo json_encode([
      'status' => 'Success',
      'message' => 'No changes were needed (values unchanged).'
    ]);
    log_activity(
      $mysqli,
      'update_committee',
      array('role_id' => $role_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
      true,
      "Committee role update attempted but no changes needed (ID: $role_id)",
      $current_user_id
    );
  }
} else {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Database error: ' . $updateStmt->error
  ]);
  log_activity(
    $mysqli,
    'update_committee',
    array('role_id' => $role_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order, 'error' => $updateStmt->error),
    false,
    "Failed to update committee role (ID: $role_id): " . $updateStmt->error,
    $current_user_id
  );
}

$updateStmt->close();
