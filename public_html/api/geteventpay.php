<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$id = validate_int_post('id');
$eventsPay = null;
$eventsApprove = null;
$test = array();

// Check if user can access this data (own data or admin)
require_user_access($id, $current_user_id);

// Get family_id
$family_id = 0;
$query = "SELECT family_id FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$results = $stmt->get_result();
if ($row = $results->fetch_assoc()) {
  $family_id = $row["family_id"] ? $row["family_id"] : 0;
}
$stmt->close();

// Get all family members (scouts and adults in the same family)
$uids = array();
if ($family_id) {
  $query = "SELECT user_id FROM users WHERE family_id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $family_id);
  $stmt->execute();
  $results = $stmt->get_result();
  while ($row = $results->fetch_assoc()) {
    $uids[] = $row['user_id'];
  }
  $stmt->close();
}

// Make sure the current user is included
if (!in_array($id, $uids)) {
  $uids[] = $id;
}

for ($x = 0; $x < count($uids); $x++) {
  $user_id = $uids[$x];

  // Get user info
  $query = "SELECT user_first, user_last, user_type FROM users WHERE user_id=?";
  $stmt = $mysqli->prepare($query);
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $results = $stmt->get_result();
  $row = $results->fetch_assoc();
  $stmt->close();

  $first = $row['user_first'];
  $last = $row['user_last'];
  $type = $row['user_type'];

  // Get registrations to pay
  $query2 = "SELECT id,event_id FROM registration WHERE user_id=? AND attending=1 AND paid=0 AND approved_by<>0";
  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('i', $user_id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  while ($row2 = $results2->fetch_assoc()) {
    $event_id = $row2['event_id'];
    $reg_id = $row2['id'];

    if ($type == "Scout") {
      $query3 = "SELECT name,startdate,cost FROM events WHERE cost>0 AND id=?";
    } else {
      $query3 = "SELECT name,startdate,adult_cost as cost FROM events WHERE adult_cost>0 AND id=?";
    }

    $stmt3 = $mysqli->prepare($query3);
    $stmt3->bind_param('i', $event_id);
    $stmt3->execute();
    $results3 = $stmt3->get_result();

    while ($row3 = $results3->fetch_assoc()) {
      $cost = $row3['cost'];
      $eventsPay[] = [
        'eventname' => escape_html($row3['name']),
        'eventid' => $event_id,
        'regid' => $reg_id,
        'startdate' => escape_html($row3['startdate']),
        'cost' => escape_html($cost),
        'scoutname' => escape_html($first . ' ' . $last)
      ];
    }
    $stmt3->close();
  }
  $stmt2->close();

  // Get registrations to approve
  $query2 = "SELECT id,event_id FROM registration WHERE user_id=? AND attending=1 AND approved_by=0";
  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('i', $user_id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  while ($row2 = $results2->fetch_assoc()) {
    $event_id = $row2['event_id'];
    $reg_id = $row2['id'];

    $query3 = "SELECT name,startdate,cost FROM events WHERE DATE(startdate) >= DATE(NOW()) AND id=?";
    $stmt3 = $mysqli->prepare($query3);
    $stmt3->bind_param('i', $event_id);
    $stmt3->execute();
    $results3 = $stmt3->get_result();

    while ($row3 = $results3->fetch_assoc()) {
      $eventsApprove[] = [
        'eventname' => escape_html($row3['name']),
        'eventid' => $event_id,
        'regid' => $reg_id,
        'startdate' => escape_html($row3['startdate']),
        'cost' => escape_html($row3['cost']),
        'scoutname' => escape_html($first . ' ' . $last)
      ];
    }
    $stmt3->close();
  }
  $stmt2->close();
}

$returnData = 'success';
$returnMsg = array(
  'eventDataPay' => $eventsPay,
  'eventDataApprove' => $eventsApprove,
  'test' => $test
);

echo json_encode($returnMsg);
die();
?>
