<?php
// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

// Check if user has super admin access
require_permission(['sa']);

header('Content-Type: application/json');
require 'connect.php';

// Get all active users (non-scouts, active accounts)
$users = array();

$query = "SELECT user_id, user_first, user_last
          FROM users
          WHERE user_active = 1 AND is_scout = 0
          ORDER BY user_last ASC, user_first ASC";
$results = $mysqli->query($query);

if ($results) {
  while ($row = $results->fetch_assoc()) {
    $users[] = [
      'user_id' => $row['user_id'],
      'user_first' => $row['user_first'],
      'user_last' => $row['user_last']
    ];
  }
} else {
  echo json_encode([
    'status' => 'Error',
    'message' => 'Database query failed: ' . $mysqli->error
  ]);
  die();
}

echo json_encode([
  'status' => 'Success',
  'users' => $users
]);
