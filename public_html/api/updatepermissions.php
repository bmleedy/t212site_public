<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
// Only super admins can update permissions
require_permission(['sa']);

// Get update data - must read php://input before CSRF validation
$input = json_decode(file_get_contents('php://input'), true);

// CSRF validation for state-changing operation
// Note: Cannot use require_csrf() since php://input was already consumed above
$csrf_token = $input['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    die();
}

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

if (!isset($input['updates']) || !is_array($input['updates'])) {
  echo json_encode(['status' => 'Error', 'message' => 'Invalid request format']);
  die();
}

$updates = $input['updates'];
$updated_count = 0;
$errors = [];

// Process each update
foreach ($updates as $update) {
  $user_id = (int)($update['user_id'] ?? 0);
  $new_access = $update['user_access'] ?? '';
  $old_access = $update['old_access'] ?? '';

  if ($user_id <= 0) {
    $errors[] = "Invalid user ID";
    continue;
  }

  // Validate permission codes (only allow known codes)
  $valid_codes = ['sa', 'wm', 'ue', 'oe', 'pl', 'trs'];
  $permission_array = array_filter(explode('.', $new_access));

  foreach ($permission_array as $code) {
    if (!in_array($code, $valid_codes)) {
      $errors[] = "Invalid permission code: $code for user $user_id";
      continue 2; // Skip this user
    }
  }

  // Update the database
  $query = "UPDATE users SET user_access = ? WHERE user_id = ?";
  $statement = $mysqli->prepare($query);
  $statement->bind_param('si', $new_access, $user_id);

  if ($statement->execute()) {
    $updated_count++;

    // Log the permission change
    log_activity(
      $mysqli,
      'update_permissions',
      [
        'user_id' => $user_id,
        'old_access' => $old_access,
        'new_access' => $new_access,
        'changed_by' => $current_user_id
      ],
      true,
      "Permissions updated for user $user_id: '$old_access' → '$new_access'",
      $current_user_id
    );
  } else {
    $errors[] = "Database error for user $user_id: " . $mysqli->error;

    // Log the failure
    log_activity(
      $mysqli,
      'update_permissions',
      [
        'user_id' => $user_id,
        'error' => $mysqli->error
      ],
      false,
      "Failed to update permissions for user $user_id",
      $current_user_id
    );
  }

  $statement->close();
}

$mysqli->close();

// Return response
if (count($errors) > 0) {
  echo json_encode([
    'status' => 'Partial',
    'updated' => $updated_count,
    'errors' => $errors,
    'message' => "Updated $updated_count users with " . count($errors) . " errors"
  ]);
} else {
  echo json_encode([
    'status' => 'Success',
    'updated' => $updated_count,
    'message' => "Successfully updated $updated_count users"
  ]);
}

die();
?>
