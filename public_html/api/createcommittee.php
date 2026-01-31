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
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Validate required parameters
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
    'create_committee',
    array('role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
    false,
    "Failed to create committee role: empty role name",
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
    'create_committee',
    array('role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
    false,
    "Failed to create committee role: user does not exist",
    $current_user_id
  );
  die();
}
$checkUserStmt->close();

// Insert the new committee role using prepared statement
$insertStmt = $mysqli->prepare("INSERT INTO committee (role_name, user_id, sort_order) VALUES (?, ?, ?)");
$insertStmt->bind_param('sii', $role_name, $user_id, $sort_order);

if ($insertStmt->execute()) {
  $new_id = $mysqli->insert_id;
  echo json_encode([
    'status' => 'Success',
    'message' => 'Committee role created successfully.',
    'role' => [
      'role_id' => $new_id,
      'role_name' => $role_name,
      'user_id' => $user_id,
      'sort_order' => $sort_order
    ]
  ]);
  log_activity(
    $mysqli,
    'create_committee',
    array('role_id' => $new_id, 'role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order),
    true,
    "Committee role created: $role_name (ID: $new_id, User ID: $user_id, Sort: $sort_order)",
    $current_user_id
  );
} else {
  // Log the actual error for debugging
  log_activity(
    $mysqli,
    'create_committee',
    array('role_name' => $role_name, 'user_id' => $user_id, 'sort_order' => $sort_order, 'error' => $insertStmt->error),
    false,
    "Failed to create committee role: " . $insertStmt->error,
    $current_user_id
  );
  echo json_encode([
    'status' => 'Error',
    'message' => 'Failed to create committee role. Please try again or contact the webmaster.'
  ]);
}

$insertStmt->close();
