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

// Validate required parameter
$role_id = validate_int_post('role_id', true);

// Get role name for logging before deleting
$getRoleStmt = $mysqli->prepare("SELECT role_name FROM committee WHERE role_id = ?");
$getRoleStmt->bind_param('i', $role_id);
$getRoleStmt->execute();
$getRoleResult = $getRoleStmt->get_result();
$role = $getRoleResult->fetch_assoc();
$getRoleStmt->close();

if (!$role) {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Committee role not found.'
  ]);
  log_activity(
    $mysqli,
    'delete_committee',
    array('role_id' => $role_id),
    false,
    "Failed to delete committee role: role not found (ID: $role_id)",
    $current_user_id
  );
  die();
}

$roleName = $role['role_name'];

// Delete the committee role using prepared statement
$deleteStmt = $mysqli->prepare("DELETE FROM committee WHERE role_id = ?");
$deleteStmt->bind_param('i', $role_id);

if ($deleteStmt->execute()) {
  if ($deleteStmt->affected_rows > 0) {
    echo json_encode([
      'status' => 'Success',
      'message' => 'Committee role deleted successfully.'
    ]);
    log_activity(
      $mysqli,
      'delete_committee',
      array('role_id' => $role_id, 'role_name' => $roleName),
      true,
      "Committee role deleted: $roleName (ID: $role_id)",
      $current_user_id
    );
  } else {
    echo json_encode([
      'status' => 'Error',
      'message' => 'Committee role not found or already deleted.'
    ]);
    log_activity(
      $mysqli,
      'delete_committee',
      array('role_id' => $role_id),
      false,
      "Failed to delete committee role: role not found (ID: $role_id)",
      $current_user_id
    );
  }
} else {
  // Log the actual error for debugging
  log_activity(
    $mysqli,
    'delete_committee',
    array('role_id' => $role_id, 'error' => $deleteStmt->error),
    false,
    "Failed to delete committee role (ID: $role_id): " . $deleteStmt->error,
    $current_user_id
  );
  echo json_encode([
    'status' => 'Error',
    'message' => 'Failed to delete committee role. Please try again or contact the webmaster.'
  ]);
}

$deleteStmt->close();
