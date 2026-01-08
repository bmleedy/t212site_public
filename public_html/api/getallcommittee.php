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

// Get all committee roles with user information
$roles = array();

$query = "SELECT c.role_id, c.role_name, c.user_id, c.sort_order,
                 u.user_first, u.user_last
          FROM committee c
          LEFT JOIN users u ON c.user_id = u.user_id
          ORDER BY c.sort_order ASC";
$results = $mysqli->query($query);

if ($results) {
  while ($row = $results->fetch_assoc()) {
    $roles[] = [
      'role_id' => $row['role_id'],
      'role_name' => $row['role_name'],
      'user_id' => $row['user_id'],
      'sort_order' => $row['sort_order'],
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
  'roles' => $roles
]);
