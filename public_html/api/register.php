<?php
$activity_log_prefix = __DIR__ . '/../registration_logs/event_';
$activity_log_suffix = '.log';

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
$user_id = $_POST['user_id'];
$event_id = $_POST['event_id'];
$action = $_POST['action'];
$user_type = $_POST['user_type'];
$seat_belts = $_POST['seat_belts'];
$paid = $_POST['paid'];
$drive = $_POST['drive'];
$pay_id = 0;

error_log("you have reached here with action: " . $action . " for user_id: " . $user_id . " event_id: " . $event_id);

// Log the registration actions taken on each event.
$message = date('Y-m-d H:i:s') . " - User ID " . $user_id . " Action: " . $action . " Event ID: " . $event_id . " Seat Belts: " . $seat_belts . " Paid: " . $paid . " Drive: " . $drive . "\n";
file_put_contents($activity_log_prefix . $event_id . $activity_log_suffix, $message, FILE_APPEND | LOCK_EX);


if ($action=="cancel") {
	$attending=0;
	$query = "UPDATE registration SET attending=? WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $attending, $event_id, $user_id);
	$statement->execute();
	$statement->close();
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Cancelled',
		'message' => 'Your registration for this event has been cancelled.'
	);
	echo json_encode($returnMsg);
	die;
}

if ($action=="pay") {
	$paid=1;
	$query = "UPDATE registration SET paid=? WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $paid, $event_id, $user_id);
	$statement->execute();
	$statement->close();
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'Your registration for this event has been paid.'
	);
	echo json_encode($returnMsg);
	die;
}

if ($action=="restore") {
	error_log("Restoring registration for user_id: " . $user_id . " event_id: " . $event_id);
	$attending=1;
	$query = "UPDATE registration SET attending=?, seat_belts=?, drive='na' WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('ssss', $attending, $seat_belts, $event_id, $user_id);
	$statement->execute();
	$statement->close();
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'Your registration for this event has been reinstated.'
	);
	echo json_encode($returnMsg);
	die;
}

if ($action=="seatbelts") {
	$attending=1;
	$query = "UPDATE registration SET seat_belts=?, drive='na' WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $seat_belts, $event_id, $user_id);
	$statement->execute();
	$statement->close();
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'Number of seat belts has been updated.'
	);
	echo json_encode($returnMsg);
	die;
}

// If we get this far, it is an add, first check to make sure it does not already exist
$query = "SELECT id FROM registration WHERE event_id=" . $event_id . " AND user_id=" . $user_id ;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
if ($row) {
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'You are already signed up!'
	);
	echo json_encode($returnMsg);
	die;
}

$query = "INSERT INTO registration (event_id, user_id, approved_by, paid, nbrInGroup, seat_belts, ts_register, seat_belts_return, drive, ts_approved, ts_paid, spec_instructions, pp_token) VALUES(?,?,?,?,0,?,?,0,'no',0,0,'',0)";
$statement = $mysqli->prepare($query);
if ($statement === false) {
	echo ($mysqli->error);
	die;
}
if ($user_type=="Scout") {
	$approved_by = 0; // Scouts are not approved by anyone, so we set this to 0;
} else {
	$approved_by = $user_id ;
}
$ts_now = date('Y-m-d H:i:s');

$rs = $statement->bind_param('ssssss', $event_id, $user_id, $approved_by, $paid, $seat_belts, $ts_now);
if($rs == false) {
	echo ($statement->error);
	die;
}
if($statement->execute()){
	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'You are now signed up for this event!'
	);
	echo json_encode($returnMsg);
}else{
	echo ( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
	die;
}
$statement->close();



?>
