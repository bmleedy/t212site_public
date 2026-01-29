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

// Check if user has webmaster or super admin access
require_permission(['wm', 'sa']);

// Verify CSRF token for write operation
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

// Validate required parameter
$id = validate_int_post('id', true);

// Get patrol label for logging before deleting
$getPatrolStmt = $mysqli->prepare("SELECT label FROM patrols WHERE id = ?");
$getPatrolStmt->bind_param('i', $id);
$getPatrolStmt->execute();
$getPatrolResult = $getPatrolStmt->get_result();
$patrol = $getPatrolResult->fetch_assoc();
$getPatrolStmt->close();

if (!$patrol) {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Patrol not found.'
  ]);
  log_activity(
    $mysqli,
    'delete_patrol',
    array('id' => $id),
    false,
    "Failed to delete patrol: patrol not found (ID: $id)",
    $current_user_id
  );
  die();
}

$patrolLabel = $patrol['label'];

// Check if patrol is in use by any scouts
$checkUsageStmt = $mysqli->prepare("SELECT COUNT(*) as count FROM scout_info WHERE patrol_id = ?");
$checkUsageStmt->bind_param('i', $id);
$checkUsageStmt->execute();
$checkUsageResult = $checkUsageStmt->get_result();
$usageRow = $checkUsageResult->fetch_assoc();
$checkUsageStmt->close();

if ($usageRow['count'] > 0) {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Cannot delete patrol "' . $patrolLabel . '" because it is assigned to ' . $usageRow['count'] . ' scout(s). Please reassign the scouts first.'
  ]);
  log_activity(
    $mysqli,
    'delete_patrol',
    array('id' => $id, 'label' => $patrolLabel, 'scouts_count' => $usageRow['count']),
    false,
    "Failed to delete patrol: patrol still in use by scouts (ID: $id, Label: $patrolLabel)",
    $current_user_id
  );
  die();
}

// Delete the patrol using prepared statement
$deleteStmt = $mysqli->prepare("DELETE FROM patrols WHERE id = ?");
$deleteStmt->bind_param('i', $id);

if ($deleteStmt->execute()) {
  if ($deleteStmt->affected_rows > 0) {
    echo json_encode([
      'status' => 'Success',
      'message' => 'Patrol deleted successfully.'
    ]);
    log_activity(
      $mysqli,
      'delete_patrol',
      array('id' => $id, 'label' => $patrolLabel),
      true,
      "Patrol deleted: $patrolLabel (ID: $id)",
      $current_user_id
    );
  } else {
    echo json_encode([
      'status' => 'Error',
      'message' => 'Patrol not found or already deleted.'
    ]);
    log_activity(
      $mysqli,
      'delete_patrol',
      array('id' => $id),
      false,
      "Failed to delete patrol: patrol not found (ID: $id)",
      $current_user_id
    );
  }
} else {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Database error: ' . $deleteStmt->error
  ]);
  log_activity(
    $mysqli,
    'delete_patrol',
    array('id' => $id, 'error' => $deleteStmt->error),
    false,
    "Failed to delete patrol (ID: $id): " . $deleteStmt->error,
    $current_user_id
  );
}

$deleteStmt->close();
