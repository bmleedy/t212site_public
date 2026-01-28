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

// Validate inputs
$event_id = validate_int_post('event_id', true);

$name = "";
$location = "";
$startdate = date("Y-m-d H:i:s");
$enddate = date("Y-m-d H:i:s");
$cost = "";

$stmt = $mysqli->prepare("SELECT * FROM events WHERE id=?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
  $event_id = $row['id'];
  $name = $row['name'];
  $location = $row['location'];
  $startdate = $row['startdate'];
  $enddate = $row['enddate'];
  $cost = $row['cost'];
}
$stmt->close();

$returnData = '<h5>' . escape_html($name) . ' -  *****with Special Instructions - Adult Leader Copy*****</h5>';

$attendingScouts = null;
$stmt = $mysqli->prepare("SELECT reg.user_id, spec_instructions, paid, seat_belts, user_first, user_last, patrol_id, reg.id as register_id, reg.approved_by FROM registration AS reg, users AS u, scout_info AS si WHERE reg.attending=1 AND u.user_type='Scout' AND reg.user_id = u.user_id AND reg.user_id = si.user_id AND reg.event_id=? ORDER BY patrol_id, user_last, user_first");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $adultContactInfo = "";
  $first = "";
  $isFirstRow = 1;
  $scout_user_id = $row['user_id'];

  // Get scout's family_id first
  $stmt_fam = $mysqli->prepare("SELECT family_id FROM users WHERE user_id=?");
  $stmt_fam->bind_param("i", $scout_user_id);
  $stmt_fam->execute();
  $result_fam = $stmt_fam->get_result();
  $scout_family = $result_fam->fetch_assoc();
  $scout_family_id = $scout_family ? $scout_family['family_id'] : 0;
  $stmt_fam->close();

  // Get adult contact info via family_id
  $stmt2 = $mysqli->prepare("SELECT user_first, user_last, p.type, phone FROM users as u, phone as p WHERE u.user_id = p.user_id AND u.user_type != 'Scout' AND u.family_id=? ORDER BY u.user_first");
  $stmt2->bind_param("i", $scout_family_id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();
  while ($row2 = $results2->fetch_assoc()) {
    if ($row2['user_first'] != $first) {
      $first=$row2['user_first'];
      if ($isFirstRow) {
        $name_display = '<strong>' . escape_html($first) . '</strong>';
        $isFirstRow = 0;
      } else {
        $name_display = '. <strong>' . escape_html($first) . '</strong>';
      }
    } else {
      $name_display = '';
    }
    $adultContactInfo = $adultContactInfo . $name_display . " " . escape_html(substr($row2['type'],0,1)) . "-" . escape_html($row2['phone']);
  }
  $stmt2->close();

  $attendingScouts[] = [
    'patrol' => escape_html(getLabel('patrols',$row['patrol_id'],$mysqli)),
    'id' => $row['user_id'],
    'register_id' => $row['register_id'],
    'approved' => $row['approved_by'],
    'paid' => $row['paid'],
    'first' => escape_html($row['user_first']),
    'last' => escape_html($row['user_last']),
    'instructions' => escape_html($row['spec_instructions']),
    'contactInfo' => $adultContactInfo
  ];
}
$stmt->close();

$attendingAdults = null;

$stmt = $mysqli->prepare("SELECT reg.user_id, paid, seat_belts, user_first, user_last, reg.id as register_id FROM registration AS reg, users AS u WHERE reg.attending=1 AND u.user_type<>'Scout' AND reg.user_id = u.user_id AND reg.event_id=? ORDER BY user_last, user_first");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $adultContactInfo = "";
  $adult_user_id = $row['user_id'];

  $stmt2 = $mysqli->prepare("SELECT p.type, phone FROM users as u, phone as p WHERE p.user_id = u.user_id AND u.user_id=? ORDER BY u.user_first");
  $stmt2->bind_param("i", $adult_user_id);
  $stmt2->execute();
  $results2 = $stmt2->get_result();
  while ($row2 = $results2->fetch_assoc()) {
    $adultContactInfo = $adultContactInfo . escape_html(substr($row2['type'],0,1)) . "-" . escape_html($row2['phone']) . '<br>';
  }
  $stmt2->close();

  $attendingAdults[] = [
    'patrol' => 'Adults',
    'id' => $row['user_id'],
    'register_id' => $row['register_id'],
    'paid' => $row['paid'],
    'seat_belts' => $row['seat_belts'],
    'first' => escape_html($row['user_first']),
    'last' => escape_html($row['user_last']),
    'contactInfo' => $adultContactInfo
  ];
}
$stmt->close();


$returnMsg = array(
  'startdate' => $startdate,
  'enddate' => $enddate,
  'outing_name' => $name,
  'cost' => $cost,
  'attendingScouts' => $attendingScouts,
  'attendingAdults' => $attendingAdults,
  'data' => $returnData
);

// Log roster view
log_activity(
    $mysqli,
    'view_event_roster_si',
    array('event_id' => $event_id),
    true,
    "Viewed event roster (SI) for event {$event_id}",
    $current_user_id
);

echo json_encode($returnMsg);
die;

function getLabel($strTable,$id,$mysqli){
  if ($id) {
    // Whitelist allowed tables
    $allowed_tables = ['patrols'];
    if (!in_array($strTable, $allowed_tables)) {
      return '';
    }

    $stmt = $mysqli->prepare("SELECT label FROM $strTable WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['label'];
  } else {
    return "";
  }
}
?>
