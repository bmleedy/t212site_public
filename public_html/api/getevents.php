<?php
session_start();
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');
require 'connect.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$query="SELECT * FROM events WHERE DATE_ADD(enddate, INTERVAL 1 DAY) > DATE(NOW()) ORDER BY startdate";
$results = $mysqli->query($query);
$events = null;

while ($row = $results->fetch_object()) {
  $registered = "No";

  if ($user_id > 0) {
    $reg_query = "SELECT attending FROM registration WHERE user_id=" . $user_id . " AND event_id=" . $row->id;
    $reg_results = $mysqli->query($reg_query);
    $reg_row = $reg_results->fetch_assoc();

    if ($reg_row) {
      if ($reg_row['attending'] == 1) {
        $registered = "Yes";
      } else {
        $registered = "Cancelled";
      }
    }
  }

  $events[] = [
	'name' => $row->name,
	'description' => $row->description,
	'location'=> $row->location,
	'startdate'=> $row->startdate,
	'enddate'=> $row->enddate,
	'cost'=> $row->cost,
	'reg_open'=> $row->reg_open,
	'id'=>$row->id,
	'registered'=>$registered
  ];
}

echo json_encode($events);

?>
