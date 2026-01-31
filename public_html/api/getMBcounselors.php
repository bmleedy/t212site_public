<?php
/**
 * API: Get Merit Badge Counselors
 *
 * Returns list of merit badge counselors with their contact information.
 * Requires authentication - counselor list is login-protected.
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

$stmt = $mysqli->prepare("SELECT mbc.user_id, mbc.mb_id, mbl.mb_name FROM mb_counselors AS mbc JOIN mb_list AS mbl ON mbc.mb_id = mbl.id ORDER BY mbl.mb_name");
if (!$stmt) {
  log_activity(
    $mysqli,
    'view_mb_counselors',
    array('error' => 'Query preparation failed'),
    false,
    "Failed to prepare query for MB counselors list",
    $current_user_id
  );
  http_response_code(500);
  echo json_encode(['error' => 'Query failed']);
  die();
}

$stmt->execute();
$result = $stmt->get_result();
$counselors = [];

if (!$result) {
  log_activity(
    $mysqli,
    'view_mb_counselors',
    array('error' => 'Query execution failed'),
    false,
    "Failed to execute query for MB counselors list",
    $current_user_id
  );
  http_response_code(500);
  echo json_encode(['error' => 'Query failed']);
  die();
}

while ($row = $result->fetch_object()) {
  $id = (int)$row->user_id;
  $stmt2 = $mysqli->prepare("SELECT user_first, user_last, user_email FROM users WHERE user_id=?");
  $stmt2->bind_param("i", $id);
  $stmt2->execute();
  $result2 = $stmt2->get_result();
  $row2 = $result2->fetch_object();
  if ($row2) {
    $counselors[] = [
      'mb_name' => escape_html($row->mb_name),
      'mb_id' => (int)$row->mb_id,
      'id' => $id,
      'first' => escape_html($row2->user_first),
      'last' => escape_html($row2->user_last),
      'email' => escape_html($row2->user_email)
    ];
  }
  $stmt2->close();
}
$stmt->close();

// Log successful view
log_activity(
  $mysqli,
  'view_mb_counselors',
  array('count' => count($counselors)),
  true,
  "User viewed MB counselors list (" . count($counselors) . " records)",
  $current_user_id
);

echo json_encode($counselors);