<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');

require 'connect.php';
$id = $_POST['id'];
$eventsPay = null;
$eventsApprove = null;

$query = "SELECT scout_id FROM relationships WHERE adult_id=" . $id;
$results = $mysqli->query($query);
while ($row = $results->fetch_assoc()) {
	$uids[] = $row['scout_id'];
}
$uids[] = $id;
for ($x = 0; $x < count($uids); $x++) {
	$user_id = $uids[$x];
	$query = "SELECT user_first, user_last FROM users WHERE user_id=".$user_id;
	$results = $mysqli->query($query);
	$row = $results->fetch_assoc();
	$first = $row['user_first'];
	$last = $row['user_last'];
	
	$query2 = "SELECT id,event_id FROM registration WHERE user_id=".$user_id." AND attending=1 AND paid=0 AND approved_by<>0";
	$results2 = $mysqli->query($query2);
	while ($row2 = $results2->fetch_assoc()) {
		$event_id = $row2['event_id'];
		$reg_id = $row2['id'];
		$query3 = "SELECT name,startdate,cost FROM events WHERE cost>0 AND id=".$event_id;
		$results3 = $mysqli->query($query3);
		while ($row3 = $results3->fetch_assoc()) {
			$eventsPay[] = [
				'eventname' => $row3['name'],
				'eventid' => $event_id,
				'regid' => $reg_id,
				'startdate'=> $row3['startdate'],
				'cost'=> $row3['cost'],
				'scoutname' => $first . ' ' . $last
			];
		}
	}

} 

$returnData = 'success';
$returnMsg = array(
	'eventDataPay' => $eventsPay
);

echo json_encode($returnMsg);
die;

?>