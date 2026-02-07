<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['pl', 'oe', 'sa', 'wm']);

header('Content-Type: application/json');
require 'connect.php';

// Get all scouts (user_type = 'Scout') with their patrol information
$scouts = array();

$query = "SELECT u.user_id, u.user_first, u.user_last, p.label AS patrol_label, p.id AS patrol_id
          FROM users AS u
          LEFT JOIN scout_info AS si ON u.user_id = si.user_id
          LEFT JOIN patrols AS p ON si.patrol_id = p.id
          WHERE u.user_type = 'Scout'
          ORDER BY p.sort, u.user_last, u.user_first";

$stmt = $mysqli->prepare($query);
$stmt->execute();
$results = $stmt->get_result();

if ($results) {
  while ($row = $results->fetch_assoc()) {
    $scouts[] = [
      'user_id' => $row['user_id'],
      'first' => escape_html($row['user_first']),
      'last' => escape_html($row['user_last']),
      'patrol_id' => $row['patrol_id'],
      'patrol' => $row['patrol_label'] ? escape_html($row['patrol_label']) : 'No Patrol'
    ];
  }
  $stmt->close();
  echo json_encode(['status' => 'Success', 'scouts' => $scouts]);
} else {
  echo json_encode(['status' => 'Error', 'message' => 'Failed to load scouts']);
}

die();
?>
