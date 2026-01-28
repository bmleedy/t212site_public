<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['er', 'sa']);

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$event_id = validate_int_post('event_id');

$name = "";
$location = "";
$startdate = date("Y-m-d H:i:s");
$enddate = date("Y-m-d H:i:s");
$cost = "";

// Get event details
$query = "SELECT * FROM events WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();

if ($results && $row = $results->fetch_assoc()) {
  $name = $row['name'];
  $location = $row['location'];
  $startdate = $row['startdate'];
  $enddate = $row['enddate'];
  $cost = $row['cost'];
}
$stmt->close();

$returnData = '<h5>' . escape_html($name) . ' - Standard Copy</h5>';
$attendingScouts = null;

// Get attending scouts
$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, patrol_id, reg.id as register_id, reg.approved_by
          FROM registration AS reg, users AS u, scout_info AS si
          WHERE reg.attending=1 AND u.user_type='Scout' AND reg.user_id = u.user_id AND reg.user_id = si.user_id AND reg.event_id=?
          ORDER BY patrol_id, user_last, user_first";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();

while ($row = $results->fetch_assoc()) {
  $adultContactInfo = "";
  $first = "";
  $isFirstRow = 1;

  // Get adult contact info for scout
  $query2 = "SELECT user_first, user_last, p.type, phone
             FROM relationships as r, users as u, phone as p
             WHERE r.adult_id = u.user_id AND r.adult_id = p.user_id AND r.scout_id=?
             ORDER BY u.user_first";
  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('i', $row['user_id']);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  while ($row2 = $results2->fetch_assoc()) {
    if ($row2['user_first'] != $first) {
      $first = $row2['user_first'];
      if ($isFirstRow) {
        $name_part = '<strong>' . escape_html($first) . '</strong>';
        $isFirstRow = 0;
      } else {
        $name_part = '. <strong>' . escape_html($first) . '</strong>';
      }
    } else {
      $name_part = '';
    }
    $adultContactInfo = $adultContactInfo . $name_part . " " . substr(escape_html($row2['type']), 0, 1) . "-" . escape_html($row2['phone']);
  }
  $stmt2->close();

  $attendingScouts[] = [
    'patrol' => escape_html(getLabel('patrols', $row['patrol_id'], $mysqli)),
    'id' => $row['user_id'],
    'register_id' => $row['register_id'],
    'approved' => $row['approved_by'],
    'paid' => $row['paid'],
    'first' => escape_html($row['user_first']),
    'last' => escape_html($row['user_last']),
    'contactInfo' => $adultContactInfo
  ];
}
$stmt->close();

$attendingAdults = null;

// Get attending adults
$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, reg.id as register_id
          FROM registration AS reg, users AS u
          WHERE reg.attending=1 AND u.user_type<>'Scout' AND reg.user_id = u.user_id AND reg.event_id=?
          ORDER BY user_last, user_first";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();

while ($row = $results->fetch_assoc()) {
  $adultContactInfo = null;

  $query2 = "SELECT p.type, phone
             FROM users as u, phone as p
             WHERE p.user_id = u.user_id AND u.user_id=?
             ORDER BY u.user_first";
  $stmt2 = $mysqli->prepare($query2);
  $stmt2->bind_param('i', $row['user_id']);
  $stmt2->execute();
  $results2 = $stmt2->get_result();

  while ($row2 = $results2->fetch_assoc()) {
    $adultContactInfo = $adultContactInfo . substr(escape_html($row2['type']), 0, 1) . "-" . escape_html($row2['phone']) . '<br>';
  }
  $stmt2->close();

  $attendingAdults[] = [
    'patrol' => 'Adults',
    'id' => $row['user_id'],
    'register_id' => $row['register_id'],
    'paid' => $row['paid'],
    'seat_belts' => escape_html($row['seat_belts']),
    'first' => escape_html($row['user_first']),
    'last' => escape_html($row['user_last']),
    'contactInfo' => $adultContactInfo
  ];
}
$stmt->close();

$returnMsg = array(
  'startdate' => escape_html($startdate),
  'enddate' => escape_html($enddate),
  'outing_name' => escape_html($name),
  'cost' => escape_html($cost),
  'attendingScouts' => $attendingScouts,
  'attendingAdults' => $attendingAdults,
  'data' => $returnData
);

// Log roster view
log_activity(
    $mysqli,
    'view_event_roster',
    array('event_id' => $event_id),
    true,
    "Viewed event roster for event {$event_id}",
    $current_user_id
);

echo json_encode($returnMsg);
die();

function getLabel($strTable, $id, $mysqli) {
  if ($id) {
    // Whitelist allowed tables to prevent SQL injection
    $allowed_tables = ['patrols', 'ranks', 'leadership'];
    if (!in_array($strTable, $allowed_tables)) {
      return "";
    }

    $query = "SELECT label FROM " . $strTable . " WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['label'] : "";
  } else {
    return "";
  }
}
?>
