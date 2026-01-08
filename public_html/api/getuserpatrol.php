<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$user_id = validate_int_post('user_id', false, null);

if (!$user_id) {
  echo json_encode(['status' => 'Error', 'message' => 'User ID is required']);
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
die();
?>
