<?php
if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ){
  // respond to Ajax request, amd_event.php
} else {
  echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
$name = $_POST['name'];
$location = $_POST['location'];
$description = $_POST['description'];
$startdate = $_POST['startdate'].':00';
$enddate = $_POST['enddate'].':00';
$cost = $_POST['cost'];
$adult_cost = $_POST['adult_cost'];
$sic = $_POST['sic'];
$aic = $_POST['aic'];
$type = $_POST['type'];
$id = $_POST['id'];
$reg_open = $_POST['reg_open'];

validateField($name , "Event Name" , "name");
validateField($location , "Event Location" , "location");
validateField($cost , "Event Cost" , "cost");
validateField($cost , "Adult Cost" , "adult_cost");
validateField($startdate , "Start Date" , "startdate");
validateField($enddate , "End Date" , "enddate");
validateField($description , "Description" , "description");
validateField($type , "Event Type" , "type");

// if $id exists, this is an update, otherwise it is a new event
if ($id != 'New') {
  $query = "UPDATE events SET name=?, location=?, description=?, startdate=?, enddate=?, sic_id=?, aic_id=?, cost=?, adult_cost=?, reg_open=?, type_id=? WHERE id=?";
  $statement = $mysqli->prepare($query);
  if ($statement === false) {
    echo json_encode($mysqli->error);
    die;
  }
  $rs = $statement->bind_param('ssssssssssss', $name, $location, $description, $startdate, $enddate, $sic, $aic, $cost, $adult_cost, $reg_open, $type, $id);
  if($rs == false) {
      echo json_endode($statement->error);
      die;
  }
  if($statement->execute()){
    // Log successful event update
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $id, 'name' => $name, 'location' => $location, 'startdate' => $startdate),
      true,
      "Event $id updated: $name",
      null
    );

    $returnMsg = array(
      'status' => 'Success'
    );
    echo json_encode($returnMsg);
  }else{
    // Log failed event update
    log_activity(
      $mysqli,
      'update_event',
      array('event_id' => $id, 'error' => $mysqli->error),
      false,
      "Failed to update event $id",
      null
    );

    echo json_encode( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
    die;
  }
  $statement->close();

// this is a new event
} else {
  $query = "INSERT INTO events (name, location, description, startdate, enddate, sic_id, aic_id, cost, adult_cost, reg_open, type_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)";
  $statement = $mysqli->prepare($query);
  if ($statement === false) {
    echo json_encode($mysqli->error);
    die;
  }
  $rs = $statement->bind_param('sssssssssss', $name, $location, $description, $startdate, $enddate, $sic, $aic, $cost, $adult_cost, $reg_open, $type);
  if($rs == false) {
      $returnMsg = array(
        'status' => 'error',
        'message' => $startdate
      );
      echo json_encode($returnMsg);
      die;
  }
  if($statement->execute()){
    // Log successful event creation
    $new_event_id = $mysqli->insert_id;
    log_activity(
      $mysqli,
      'create_event',
      array('event_id' => $new_event_id, 'name' => $name, 'location' => $location, 'startdate' => $startdate),
      true,
      "New event created: $name (ID: $new_event_id)",
      null
    );

    $returnMsg = array(
      'status' => 'Success'
    );
    echo json_encode($returnMsg);
  }else{
    // Log failed event creation
    log_activity(
      $mysqli,
      'create_event',
      array('name' => $name, 'error' => $mysqli->error),
      false,
      "Failed to create event: $name",
      null
    );

    echo json_encode( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
    die;
  }
  $statement->close();

}
die;

function validateField( $strValue, $strLabel, $strFieldName) {
  if ($strValue=="") {
    $returnMsg = array(
      'status' => 'validation', 
      'message' => 'Please Enter: ' . $strLabel,
      'field' => $strFieldName
    );
    echo json_encode($returnMsg);
    die;
  }
}
?>