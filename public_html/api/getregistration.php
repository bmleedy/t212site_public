<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$signup_id = validate_int_post('signup_id');
$user_id = validate_int_post('user_id');
$edit = validate_string_post('edit', false, '');

// Get registration details
$query = "SELECT * FROM registration WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $signup_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

if (!$row) {
	echo json_encode(['error' => 'Registration not found']);
	die();
}

$event_id = $row['event_id'];
$person_signed_up_id = $row['user_id'];
$paid = ($row['paid'] == '1') ? 'Yes' : 'No';
$attending = ($row['attending'] == '1') ? 'Yes' : 'No';
$approved_by = $row['approved_by'];
$seat_belts = $row['seat_belts'];

// Get user details
$query = "SELECT user_type, user_first, user_last FROM users WHERE user_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

if (!$row) {
	echo json_encode(['error' => 'User not found']);
	die();
}

$user_type = $row['user_type'];
$user_first = $row['user_first'];
$user_last = $row['user_last'];
$returnData = '<div class="row"><div class="large-5 columns"><label>Name</label><p>' . escape_html($user_first) . ' ' . escape_html($user_last) . '</p></div></div>';

// Check if user is already signed up
$query = "SELECT attending FROM registration WHERE user_id=? AND event_id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $user_id, $event_id);
$stmt->execute();
$results = $stmt->get_result();
$row = $results->fetch_assoc();
$stmt->close();

if (!$row) {
	$signed_up = "No";
} else {
	if ($row['attending'] == 1) {
		$signed_up = "Yes";
	} else {
		$signed_up = "Cancelled";
	}
}

// Get event details
$name = "";
$location = "";
$description = "";
$startdate = date("Y-m-d H:i:s");
$enddate = date("Y-m-d H:i:s");
$cost = "";

$query = "SELECT * FROM events WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();

if ($results && $row = $results->fetch_assoc()) {
	$name = $row['name'];
	$location = $row['location'];
	$description = $row['description'];
	$startdate = $row['startdate'];
	$enddate = $row['enddate'];
	$cost = $row['cost'];
}
$stmt->close();

$varname = '<p>' . escape_html($name) . '</p>';
$varlocation = '<p>' . escape_html($location) . '</p>';
$vardescription = '<p>' . escape_html($description) . '</p>';
$varstartdate = '<p>' . escape_html($startdate) . '</p>';
$varenddate = '<p>' . escape_html($enddate) . '</p>';
$varcost = '<p>$' . escape_html($cost) . '</p>';

$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Event Name' . $varname . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Location' . $varlocation . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Cost' . $varcost . '</label></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Start Date' . $varstartdate . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>End Date' . $varenddate . '</label></div><div class="large-2 columns"></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-12 columns"><label>Event Description' . $vardescription . '</label></div></div>';

$returnMsg = array(
	'user_type' => escape_html($user_type),
	'signed_up' => $signed_up,
	'data' => $returnData
);

echo json_encode($returnMsg);
die();
?>
