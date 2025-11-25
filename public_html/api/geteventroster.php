<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$event_id = $_POST['event_id'];

$name = "";
$location = "";
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
	$startdate = $row['startdate'];
	$enddate = $row['enddate'];
	$cost = $row['cost'];
}	

/*	
$varname = '<p>'. $name . '</p>';
$varlocation = '<p>'. $location . '</p>';
$varstartdate = '<p>'. $startdate . '</p>';
$varenddate = '<p>'. $enddate . '</p>';
$varcost = '<p>$'. $cost . '</p>';
*/

$returnData = '<h5>' . $name . ' - Standard Copy</h5>';
$attendingScouts = null;
$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, patrol_id, reg.id as register_id, reg.approved_by FROM registration AS reg, users AS u, scout_info AS si WHERE reg.attending=1 AND u.user_type='Scout' AND reg.user_id = u.user_id AND reg.user_id = si.user_id AND reg.event_id=" . $event_id . " ORDER BY patrol_id, user_last, user_first" ;
$results = $mysqli->query($query);
while ($row = $results->fetch_assoc()) {
	$adultContactInfo = "";
	$first = "";
	$isFirstRow = 1;
	$query2 = "SELECT user_first, user_last, p.type, phone FROM relationships as r, users as u, phone as p WHERE r.adult_id = u.user_id AND r.adult_id = p.user_id AND r.scout_id=" . $row['user_id'] . " ORDER BY u.user_first";
	$results2 =  $mysqli->query($query2);
	while ($row2 = $results2->fetch_assoc()) {
		if ($row2['user_first'] != $first) {
			$first=$row2['user_first'];
			if ($isFirstRow) {
				$name = '<strong>' . $first . '</strong>';
				$isFirstRow = 0;
			} else {
				$name = '. <strong>' . $first . '</strong>';
			}
		} else {
			$name = '';
		}
		$adultContactInfo = $adultContactInfo . $name . " " . substr($row2['type'],0,1) . "-" . $row2['phone'];
	}
	$attendingScouts[] = [
		'patrol' => getLabel('patrols',$row['patrol_id'],$mysqli),
		'id' => $row['user_id'],
		'register_id' => $row['register_id'],
		'approved' => $row['approved_by'],
		'paid' => $row['paid'],
		'first' => $row['user_first'],
		'last' => $row['user_last'],
		'contactInfo' => $adultContactInfo
	];
}

$attendingAdults = null;

$query = "SELECT reg.user_id, paid, seat_belts, user_first, user_last, reg.id as register_id FROM registration AS reg, users AS u WHERE reg.attending=1 AND u.user_type<>'Scout' AND reg.user_id = u.user_id AND reg.event_id=" . $event_id . " ORDER BY user_last, user_first" ;
$results = $mysqli->query($query);
while ($row = $results->fetch_assoc()) {
	$adultContactInfo = null;
	$query2 = "SELECT p.type, phone FROM users as u, phone as p WHERE p.user_id = u.user_id AND u.user_id=" . $row['user_id'] . " ORDER BY u.user_first";
	$results2 =  $mysqli->query($query2);
	while ($row2 = $results2->fetch_assoc()) {
		$adultContactInfo = $adultContactInfo . substr($row2['type'],0,1) . "-" . $row2['phone'] . '<br>';
	}


	$attendingAdults[] = [
		'patrol' => 'Adults',
		'id' => $row['user_id'],
		'register_id' => $row['register_id'],
		'paid' => $row['paid'],
		'seat_belts' => $row['seat_belts'],
		'first' => $row['user_first'],
		'last' => $row['user_last'],
		'contactInfo' => $adultContactInfo
	];
}


$returnMsg = array(
	'startdate' => $startdate,
	'enddate' => $enddate,
	'outing_name' => $name,
	'cost' => $cost,
	'first' => $user_first,
	'user_type' => $user_type,
	'registered' => $registered,
	'attendingScouts' => $attendingScouts,
	'attendingAdults' => $attendingAdults,
	'data' => $returnData
);

echo json_encode($returnMsg);
die;

function getLabel($strTable,$id,$mysqli){
	if ($id) {
		$query = 'SELECT label FROM '.$strTable.' WHERE id='.$id;
		$results = $mysqli->query($query);
		$row = $results->fetch_assoc();
		return $row['label'];
	} else {
		return "";
	}
}	
?>