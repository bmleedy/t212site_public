<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$signup_id = $_POST['signup_id'];
$user_id = $_POST['user_id'];
$edit = $_POST['edit'];

// Get User Type (Scout, Dad, etc)
$query="SELECT * FROM registration WHERE id=".$signup_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$event_id = $row['event_id'];
$person_signed_up_id = $row['user_id'];
if ($row['paid']=='1') {
	$paid='Yes';
} else {
	$paid='No';
}
if ($row['attending']=='1') {
	$attending='Yes';
} else {
	$attending='No';
}

$approved_by = $row['approved_by'];
$seat_belts = $row['seat_belts'];


// Get User Type (Scout, Dad, etc)
$query="SELECT user_type, user_first, user_last FROM users WHERE person_signed_up_id=".$user_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$user_type = $row['user_type'];
$user_first = $row['user_first'];
$user_last = $row['user_last'];
$returnData = '<div class="row"><div class="large-5 columns"><label>Name</label><p>' . $user_first . ' ' . $user_last . '</p></div></div>';


// Is User signed up already?
$query="SELECT attending FROM registration WHERE person_signed_up_id=".$user_id . " AND event_id=" . $event_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
if (!$row) {
	// No entry in table = No
	$signed_up = "No";
} else {
	// If there is an entry, check attending flag which will be 0 if they had signed up and then cancelled.
	// It is preferred to use attending flag rather than delete entry in case they had parent approval & paid
	// in particular if they accidentally click Plans changed button
	if ($row['attending']==1) {
		$signed_up = "Yes";
	} else {
		$signed_up = "Cancelled";
	}
}

$name = "";
$location = "";
$description = "";
$startdate = date("Y-m-d H:i:s");
$enddate = date("Y-m-d H:i:s");
$cost = "";

$query="SELECT * FROM events WHERE id=".$event_id;
$results = $mysqli->query($query);
if ($results) {
	$row = $results->fetch_assoc();
	$event_id = $row['id'];
	$name = $row['name'];
	$location = $row['location'];
	$description = $row['description'];
	$startdate = $row['startdate'];
	$enddate = $row['enddate'];
	$cost = $row['cost'];
}

$varname = '<p>'. $name . '</p>';
$varlocation = '<p>'. $location . '</p>';
$vardescription = '<p>'. $description . '</p>';
$varstartdate = '<p>'. $startdate . '</p>';
$varenddate = '<p>'. $enddate . '</p>';
$varcost = '<p>$'. $cost . '</p>';

$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Event Name' . $varname . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Location' . $varlocation . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Cost' . $varcost . '</label></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Start Date' . $varstartdate . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>End Date' . $varenddate . '</label></div><div class="large-2 columns"></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-12 columns"><label>Event Description' . $vardescription . '</label></div></div>';


$returnMsg = array(
	'user_type' => $user_type,
	'signed_up' => $signed_up,
	'data' => $returnData
);

echo json_encode($returnMsg);
die;

?>
