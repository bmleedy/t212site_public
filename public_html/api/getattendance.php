<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){

  // respond to Ajax request

} else {

	echo "Not sure what you are after, but it ain't here.";

  die();

}

header('Content-Type: application/json');
require 'connect.php';
$scouts = null;
$userid = $_POST['userid'];
$sort = $_POST['sort'];
$order = $_POST['order'];
$typeid = $_POST['typeid'];
// allowed sorts, name, startdate, location

if ($sort=='name') {
	$query="SELECT ev.name, ev.location, ev.startdate, ev.id, et.label FROM registration AS reg INNER JOIN events AS ev ON reg.event_id=ev.id INNER JOIN event_types AS et ON ev.type_id = et.id WHERE reg.user_id='" . $userid . "' AND ev.type_id='".$typeid."' ORDER BY ev.name " . $order;
} else if ($sort=='location') {
	$query="SELECT ev.name, ev.location, ev.startdate, ev.id, et.label FROM registration AS reg INNER JOIN events AS ev ON reg.event_id=ev.id INNER JOIN event_types AS et ON ev.type_id = et.id WHERE reg.user_id='" . $userid . "' AND ev.type_id='".$typeid."' ORDER BY ev.location " . $order;
} else {		// default is startdate
	$query="SELECT ev.name, ev.location, ev.startdate, ev.id, et.label FROM registration AS reg INNER JOIN events AS ev ON reg.event_id=ev.id INNER JOIN event_types AS et ON ev.type_id = et.id WHERE reg.user_id='" . $userid . "' AND ev.type_id='".$typeid."' ORDER BY ev.startdate " . $order;
}
//echo $query;
//die();

$results = $mysqli->query($query);

$events = [];
while ($row = $results->fetch_object()) {
  /*  $id =  $row->user_id;
	$phones = null;
	$rank = getLabel('ranks',$row->rank_id,$mysqli);
	$patrol = getLabel('patrols',$row->patrol_id,$mysqli);
	$position = getLabel('leadership',$row->position_id,$mysqli);
  
	$query2="SELECT * FROM phone WHERE user_id=" . $id;
	$results2 = $mysqli->query($query2);
	if ($results2) {
		while ($row2 = $results2->fetch_object()){
			$phones[] = "<a href='tel:" . $row2->phone . "'>" . $row2->phone . "</a> " . $row2->type ;
		}
	}. */
	$events[] = [
		'name' => $row->name,
		'location' => $row->location,
		'startdate'=> $row->startdate,
		//'label'=> $row->label,
		'event_id'=> $row->id 
	];
}

echo json_encode($events);
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
