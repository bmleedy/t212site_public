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
$query="SELECT * FROM events WHERE id=".$event_id;
$results = $mysqli->query($query);
if ($results) {
	$row = $results->fetch_assoc();
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
$returnData = '<div class="row"><div class="large-5 columns"><label>Event Name' . $varname . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Location' . $varlocation . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Cost' . $varcost . '</label></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Start Date' . $varstartdate . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>End Date' . $varenddate . '</label></div><div class="large-2 columns"></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-12 columns"><label>Event Description' . $vardescription . '</label></div></div>';

$scouts = null;
$adults = null;



	$query = "SELECT u.user_id, user_first, user_last, user_type, patrol_id FROM users AS u, scout_info AS si WHERE u.user_type='Scout' AND u.user_id = si.user_id ORDER BY patrol_id, user_last, user_first" ;
	$results = $mysqli->query($query);
	while ($row = $results->fetch_object()) {
		$id = $row->user_id;
		$query2 = "SELECT approved_by, attending, paid, seat_belts FROM registration WHERE user_id='".$id."' AND event_id='".$event_id."'";
		$results2 = $mysqli->query($query2);
		$row2 = $results2->fetch_object();
		
		$paid = $row2->paid;
		if (!isset($paid)) { $paid = '0'; }
		
		$approved = $row2->approved_by;
		if (!isset($approved)) { $approved = '0'; }
		
		$attending = $row2->attending;
		if (!isset($attending)) { $attending = '0'; }
		
		$scouts[] = [
			'patrol' => getLabel('patrols',$row->patrol_id,$mysqli),
			'first' => $row->user_first,
			'last' => $row->user_last,
			'user_type' => $row->user_type,
			'attending' => $attending,
			'paid' => $paid,
			'approved' => $approved,
			'seat_belts' => $row2->seat_belts,
			'id'=>$id
		];
	}

	$query = "SELECT user_id, user_first, user_last, user_type FROM users WHERE user_type not in ('Scout','Alumni','Alum-D','Alum-M','Alum-O','Delete') ORDER BY user_last, user_first" ;
	$results = $mysqli->query($query);
	while ($row = $results->fetch_object()) {
		$id = $row->user_id;
		$query2 = "SELECT attending, seat_belts FROM registration WHERE user_id='".$id."' AND event_id='".$event_id."'";
		$results2 = $mysqli->query($query2);
		$row2 = $results2->fetch_object();
		
		$attending = $row2->attending;
		if (!isset($attending)) { $attending = '0'; }
		
		$seat_belts = $row2->seat_belts;
		if (!isset($seat_belts)) { $seat_belts = 'N/A'; }
		
		$adults[] = [
			'patrol' => "Adults",
			'first' => $row->user_first,
			'last' => $row->user_last,
			'user_type' => $row->user_type,
			'attending' => $attending,
			'paid' => $paid,
			'approved' => $approved,
			'seat_belts' => $seat_belts,
			'id'=>$id
		];
	}

$returnMsg = array(
	'outing_name' => $name,
	'cost' => $cost,
	'data' => $returnData,
	'scouts' => $scouts,
	'adults' => $adults
);

echo json_encode($returnMsg);
die();



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
