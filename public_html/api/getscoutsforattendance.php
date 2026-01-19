<?php
if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  echo "Not sure what you are after, but it ain't here.";
  die();
}

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

$results = $mysqli->query($query);

if ($results) {
  while ($row = $results->fetch_assoc()) {
    $scouts[] = [
      'user_id' => $row['user_id'],
      'first' => $row['user_first'],
      'last' => $row['user_last'],
      'patrol_id' => $row['patrol_id'],
      'patrol' => $row['patrol_label'] ? $row['patrol_label'] : 'No Patrol'
    ];
  }
}

$returnMsg = array(
  'status' => 'Success',
  'scouts' => $scouts
);

echo json_encode($returnMsg);
die();
?>
