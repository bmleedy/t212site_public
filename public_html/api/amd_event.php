<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();
require_permission(['oe', 'sa']);
require_csrf();
header('Content-Type: application/json');

require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Validate string inputs
$name = validate_string_post('name', true, null, 255);
$location = validate_string_post('location', true, null, 255);
$description = validate_string_post('description', true);
$startdate_raw = validate_string_post('startdate', true);
$enddate_raw = validate_string_post('enddate', true);
$cost = validate_string_post('cost', true);
$adult_cost = validate_string_post('adult_cost', true);
$type = validate_int_post('type', true);
$id = validate_string_post('id', true);
$reg_open = validate_string_post('reg_open', false, '0');

// Validate integer inputs for sic/aic (can be 0 for unselected)
$sic = validate_int_post('sic', false, 0);
$aic = validate_int_post('aic', false, 0);

// Validate and normalize datetime strings to MySQL format (Y-m-d H:i:s)
$start_ts = strtotime($startdate_raw);
$end_ts = strtotime($enddate_raw);
if ($start_ts === false || $end_ts === false) {
    http_response_code(400);
    echo json_encode(['status' => 'validation', 'message' => 'Invalid date format', 'field' => 'startdate']);
    die();
}
$startdate = date('Y-m-d H:i:s', $start_ts);
$enddate = date('Y-m-d H:i:s', $end_ts);
if ($start_ts > $end_ts) {
    echo json_encode(['status' => 'validation', 'message' => 'Start date must be before end date', 'field' => 'startdate']);
    die();
}

// Validate required fields using custom validation
validateField($name, "Event Name", "name");
validateField($location, "Event Location", "location");
validateField($cost, "Event Cost", "cost");
validateField($adult_cost, "Adult Cost", "adult_cost");
validateField($startdate_raw, "Start Date", "startdate");
validateField($enddate_raw, "End Date", "enddate");
validateField($description, "Description", "description");

// if $id exists and is not 'New', this is an update, otherwise it is a new event
if ($id != 'New') {
  // Validate that id is numeric for updates
  if (!is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid event ID']);
    die();
  }
  $event_id = (int)$id;

  $query = "UPDATE events SET name=?, location=?, description=?, startdate=?, enddate=?, sic_id=?, aic_id=?, cost=?, adult_cost=?, reg_open=?, type_id=? WHERE id=?";
  $statement = $mysqli->prepare($query);
  if ($statement === false) {
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $event_id, 'error' => $mysqli->error),
      false,
      "Failed to prepare event update for event $event_id",
      $current_user_id
    );
    echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
    die();
  }
  // Fix: sic and aic are integers (i), not strings (s)
  $rs = $statement->bind_param('ssssiissssii', $name, $location, $description, $startdate, $enddate, $sic, $aic, $cost, $adult_cost, $reg_open, $type, $event_id);
  if ($rs == false) {
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $event_id, 'error' => $statement->error),
      false,
      "Failed to bind parameters for event update $event_id",
      $current_user_id
    );
    echo json_encode(['status' => 'error', 'message' => $statement->error]);
    die();
  }
  if ($statement->execute()) {
    // Log successful event update
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $event_id, 'name' => $name, 'location' => $location, 'startdate' => $startdate, 'enddate' => $enddate),
      true,
      "Event $event_id updated: $name",
      $current_user_id
    );

    $returnMsg = array(
      'status' => 'Success'
    );
    echo json_encode($returnMsg);
  } else {
    // Log failed event update
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $event_id, 'error' => $mysqli->error),
      false,
      "Failed to update event $event_id",
      $current_user_id
    );

    echo json_encode(['status' => 'error', 'message' => 'Error : (' . $mysqli->errno . ') ' . $mysqli->error]);
    die();
  }
  $statement->close();

// this is a new event
} else {
  $query = "INSERT INTO events (name, location, description, startdate, enddate, sic_id, aic_id, cost, adult_cost, reg_open, type_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)";
  $statement = $mysqli->prepare($query);
  if ($statement === false) {
    log_activity(
      $mysqli,
      'create_event',
      array('name' => $name, 'error' => $mysqli->error),
      false,
      "Failed to prepare event creation for: $name",
      $current_user_id
    );
    echo json_encode(['status' => 'error', 'message' => $mysqli->error]);
    die();
  }
  // Fix: sic and aic are integers (i), not strings (s)
  $rs = $statement->bind_param('ssssiissssi', $name, $location, $description, $startdate, $enddate, $sic, $aic, $cost, $adult_cost, $reg_open, $type);
  if ($rs == false) {
    log_activity(
      $mysqli,
      'create_event',
      array('name' => $name, 'error' => $statement->error),
      false,
      "Failed to bind parameters for event creation: $name",
      $current_user_id
    );
    $returnMsg = array(
      'status' => 'error',
      'message' => $statement->error
    );
    echo json_encode($returnMsg);
    die();
  }
  if ($statement->execute()) {
    // Log successful event creation
    $new_event_id = $mysqli->insert_id;
    log_activity(
      $mysqli,
      'create_event',
      array('event_id' => $new_event_id, 'name' => $name, 'location' => $location, 'startdate' => $startdate, 'enddate' => $enddate),
      true,
      "New event created: $name (ID: $new_event_id)",
      $current_user_id
    );

    $returnMsg = array(
      'status' => 'Success'
    );
    echo json_encode($returnMsg);
  } else {
    // Log failed event creation
    log_activity(
      $mysqli,
      'create_event',
      array('name' => $name, 'error' => $mysqli->error),
      false,
      "Failed to create event: $name",
      $current_user_id
    );

    echo json_encode(['status' => 'error', 'message' => 'Error : (' . $mysqli->errno . ') ' . $mysqli->error]);
    die();
  }
  $statement->close();

}
die();

function validateField($strValue, $strLabel, $strFieldName) {
  if ($strValue == "") {
    $returnMsg = array(
      'status' => 'validation',
      'message' => 'Please Enter: ' . $strLabel,
      'field' => $strFieldName
    );
    echo json_encode($returnMsg);
    die();
  }
}
?>
