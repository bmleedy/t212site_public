<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');
require 'connect.php';

// Validate inputs - using GET as per original code
$user_id = validate_int_get('user_id', true);
$event_id = validate_int_get('event_id', true);

// Authorization check - user can only check their own conflicts unless they have permission
if ($user_id != $current_user_id) {
  require_user_access($user_id, $current_user_id);
}

// fetch all registrations that are overlapping with the event's start and end dates
// will return zero rows if no conflicts

$stmt = $mysqli->prepare("
  SELECT regevents.name, regevents.startdate, regevents.enddate
  FROM registration
    JOIN events AS regevents
      ON registration.event_id = regevents.id
    JOIN events AS e2 ON
      e2.id = ?
      AND regevents.startdate <= e2.enddate
      AND regevents.enddate >= e2.startdate
  WHERE registration.user_id=?
");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// If no results, return false
$response = false;
if( mysqli_num_rows($result) == 0 ) {
  $response = false; // no conflict
} else {
  $response = true; // yes, there is a conflict
}
$stmt->close();

echo json_encode($response);

?>
