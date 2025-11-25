<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');

require 'connect.php';
$reg_id = $_POST['reg_id'];
$user_id = $_POST['user_id'];

$query = "SELECT event_id, user_id FROM registration WHERE id=".$reg_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$scout_id = $row['user_id'];
$event_id = $row['event_id'];

$scout_family_id = "";
$query = "SELECT family_id FROM users WHERE user_id=" . $scout_id;
$results = $mysqli->query($query);
if ($row = $results->fetch_assoc()) {
	$scout_family_id = $row["family_id"];
}

$adult_family_id = "";
$query = "SELECT family_id, user_first, user_last FROM users WHERE user_id=" . $user_id;
$results = $mysqli->query($query);
if ($row = $results->fetch_assoc()) {
	$adult_family_id = $row["family_id"];
	$adult_name[]=$row['user_first'] . ' ' . $row['user_last'];
}

$continue = false;
if ($scout_family_id==$adult_family_id) {
    $continue=true;
}
/* $query = "SELECT adult_id, user_first, user_last FROM relationships, users WHERE adult_id=user_id AND scout_id=".$scout_id;
$results = $mysqli->query($query);
$adult_id = null;
$adult_name = null;

while ($row = $results->fetch_assoc()) {
	$adult_id[]=$row['adult_id'];
	$adult_name[]=$row['user_first'] . ' ' . $row['user_last'];
	if ($row['adult_id']==$user_id) {
		$continue=true;
	}
}
*/

if ($continue==false) {
	$returnMsg = array(
		'data' => "You are not listed as an Approver for this scout."
	);
	echo json_encode($returnMsg);
	die;
}

$query = "SELECT user_first, user_last FROM users WHERE user_id=".$scout_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$first = $row['user_first'];
$last = $row['user_last'];

$query = "SELECT name, startdate, enddate FROM events WHERE id=".$event_id;
$results = $mysqli->query($query);
$row = $results->fetch_assoc();
$eventname = $row['name'];
$startdate = $row['startdate'];
$enddate = $row['enddate'];

$returnData  = '<div class="row"><h4><center>Parental Approval for Troop 212 Event Attendance</center></h4><br>';
$returnData .= '<div class="large-2 columns">Scout</div><div class="large-10 columns">' . $first . " " . $last . '</div>' ;
$returnData .= '<div class="large-2 columns">Event</div><div class="large-10 columns">' . $eventname . '</div>' ;
$returnData .= '<div class="large-2 columns">Start</div><div class="large-10 columns">' . $startdate . '</div>' ;
$returnData .= '<div class="large-2 columns">End</div><div class="large-10 columns">' . $enddate . '<br><br></div>' ;
$returnData .= '<div class="large-12 columns"><h5>Emergency Contacts</h5></div>';

// $max = sizeof($adult_id) ;
// for ($i=0; $i<$max; $i++) {
// 	$returnData .= '<div class="large-12 columns">';
// 	$returnData .= '<div class="large-3 columns"><a href="User.php?id='.$adult_id[$i].'">' . $adult_name[$i] . '</a></div>';
// 	$query = "Select phone, type FROM phone WHERE user_id=".$adult_id[$i];
// 	$results = $mysqli->query($query);
// 	for ($x=0; $x<3; $x++) {
// 		$returnData .= '<div class="large-3 columns">';
// 		$row = $results->fetch_assoc();
// 		if ($row) {
// 			$returnData .= $row['type'] . '<br>' . $row['phone'];
// 		}
// 		$returnData .= '</div>';
// 	}
// 	$returnData .= '</div>';
// }

$returnData .= '<div class="large-12 columns"><p/>If this info is not accurate, or incomplete, please click on your name to open your profile and update it before approving this outing.</p>';
$returnData .= '<h4>Special Instructions</h4>';
$returnData .= '<p>If your son has a medical condition (ie. allergies, etc) or is taking any medications, please specify.<input type="text" id="medical"></p>';
$returnData .= '<p>If your son needs to arrive late or leave early, please specify:<input type="text" id="spec"></p>';
$returnData .= '<p>If you approve of '. $first .' attending this outing, please click the Approve button below. Thanks!</p></div>';
$returnData .= '<div class="clearfix"></div></div>' ;
$returnMsg = array(
	'event_id' => $event_id,
	'data' => $returnData
);

echo json_encode($returnMsg);
die;

?>
