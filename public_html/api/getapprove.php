<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$reg_id = validate_int_post('reg_id');
$user_id = validate_int_post('user_id');

// Get registration details
$query = "SELECT event_id, user_id FROM registration WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $reg_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

if (!$row) {
  echo json_encode(['error' => 'Registration not found']);
  die();
}

$scout_id = $row['user_id'];
$event_id = $row['event_id'];

// Get scout's family_id
$scout_family_id = "";
$query = "SELECT family_id FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $scout_id);
$stmt->execute();
$results = $stmt->get_result();
if ($row = $results->fetch_assoc()) {
  $scout_family_id = $row["family_id"];
}
$stmt->close();

// Get adult's family_id
$adult_family_id = "";
$adult_name = array();
$query = "SELECT family_id, user_first, user_last FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$results = $stmt->get_result();
if ($row = $results->fetch_assoc()) {
  $adult_family_id = $row["family_id"];
  $adult_name[] = $row['user_first'] . ' ' . $row['user_last'];
}
$stmt->close();

// Check if adult is in same family as scout
// Authorization: Parent/guardian must be in same family as the scout
// This ensures only family members can VIEW the approval form for their children
$continue = false;
if ($scout_family_id == $adult_family_id) {
  $continue = true;
}

if ($continue == false) {
  $returnMsg = array(
    'data' => "You are not listed as an Approver for this scout."
  );
  echo json_encode($returnMsg);
  die();
}

// Get scout details
$query = "SELECT user_first, user_last FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $scout_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

$first = $row['user_first'];
$last = $row['user_last'];

// Get event details
$query = "SELECT name, startdate, enddate FROM events WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

$eventname = $row['name'];
$startdate = $row['startdate'];
$enddate = $row['enddate'];

$returnData  = '<div class="row"><h4><center>Parental Approval for Troop 212 Event Attendance</center></h4><br>';
$returnData .= '<div class="large-2 columns">Scout</div><div class="large-10 columns">' . escape_html($first) . " " . escape_html($last) . '</div>';
$returnData .= '<div class="large-2 columns">Event</div><div class="large-10 columns">' . escape_html($eventname) . '</div>';
$returnData .= '<div class="large-2 columns">Start</div><div class="large-10 columns">' . escape_html($startdate) . '</div>';
$returnData .= '<div class="large-2 columns">End</div><div class="large-10 columns">' . escape_html($enddate) . '<br><br></div>';
$returnData .= '<div class="large-12 columns"><h5>Emergency Contacts</h5></div>';
$returnData .= '<div class="large-12 columns"><p/>If this info is not accurate, or incomplete, please click on your name to open your profile and update it before approving this outing.</p>';
$returnData .= '<h4>Special Instructions</h4>';
$returnData .= '<p>If your son has a medical condition (ie. allergies, etc) or is taking any medications, please specify.<input type="text" id="medical"></p>';
$returnData .= '<p>If your son needs to arrive late or leave early, please specify:<input type="text" id="spec"></p>';
$returnData .= '<p>If you approve of ' . escape_html($first) . ' attending this outing, please click the Approve button below. Thanks!</p></div>';
$returnData .= '<div class="clearfix"></div></div>';

$returnMsg = array(
  'event_id' => $event_id,
  'data' => $returnData
);

echo json_encode($returnMsg);
die();
?>
