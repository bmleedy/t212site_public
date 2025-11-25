<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	//echo "Not sure what you are after, but it ain't here.";
  //die();
}

header('Content-Type: application/json');
require 'connect.php';

$reg_ids= explode("," , $_POST['reg_ids']);

$ts_now = date('Y-m-d H:i:s');
$events = null;

$query = "UPDATE registration SET paid=1, ts_paid=? WHERE id=?";
$statement = $mysqli->prepare($query);


foreach ($reg_ids as &$id) {

	$rs = $statement->bind_param('ss', $ts_now, $id);
	$statement->execute();

	$query2 = "SELECT * FROM registration WHERE id='" . $id. "'";
	$results2 = $mysqli->query($query2);
	$row = $results2->fetch_assoc();
	$event_id = $row['event_id'];
	$reg_id = $row['id'];
	$query3 = "SELECT user_first, user_last FROM users WHERE user_id=".$row['user_id'];
	$results3 = $mysqli->query($query3);
	$row3 = $results3->fetch_assoc();
	$first = $row3['user_first'];
	$last = $row3['user_last'];
	
	$query4 = "SELECT name,startdate,cost FROM events WHERE id=".$event_id;
	$results4 = $mysqli->query($query4);
	while ($row4 = $results4->fetch_assoc()) {
		$events[] = [
			'eventname' => $row4['name'],
			'eventid' => $event_id,
			'regid' => $reg_id,
			'startdate'=> $row4['startdate'],
			'cost'=> $row4['cost'],
			'username' => $first . ' ' . $last
		];
	}
}
$statement->close();

$returnMsg = array(
	'status' => 'Success',
	'eventData' => $events
);
echo json_encode($returnMsg);
die;

?>