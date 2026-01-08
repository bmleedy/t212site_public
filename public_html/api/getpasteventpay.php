<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Validate inputs
$id = validate_int_post('id', true);

// Authorization check - user can only view their own data unless they have permission
if ($id != $current_user_id) {
	require_user_access($id, $current_user_id);
}

$eventsPay = null;
$eventsApprove = null;

$stmt = $mysqli->prepare("SELECT scout_id FROM relationships WHERE adult_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$uids = [];
while ($row = $result->fetch_assoc()) {
	$uids[] = $row['scout_id'];
}
$stmt->close();

$uids[] = $id;

for ($x = 0; $x < count($uids); $x++) {
	$user_id = $uids[$x];
	$stmt = $mysqli->prepare("SELECT user_first, user_last FROM users WHERE user_id=?");
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$first = $row['user_first'];
	$last = $row['user_last'];
	$stmt->close();

	$stmt2 = $mysqli->prepare("SELECT id,event_id FROM registration WHERE user_id=? AND attending=1 AND paid=0 AND approved_by<>0");
	$stmt2->bind_param("i", $user_id);
	$stmt2->execute();
	$result2 = $stmt2->get_result();
	while ($row2 = $result2->fetch_assoc()) {
		$event_id = $row2['event_id'];
		$reg_id = $row2['id'];
		$stmt3 = $mysqli->prepare("SELECT name,startdate,cost FROM events WHERE cost>0 AND id=?");
		$stmt3->bind_param("i", $event_id);
		$stmt3->execute();
		$result3 = $stmt3->get_result();
		while ($row3 = $result3->fetch_assoc()) {
			$eventsPay[] = [
				'eventname' => escape_html($row3['name']),
				'eventid' => $event_id,
				'regid' => $reg_id,
				'startdate'=> escape_html($row3['startdate']),
				'cost'=> escape_html($row3['cost']),
				'scoutname' => escape_html($first) . ' ' . escape_html($last)
			];
		}
		$stmt3->close();
	}
	$stmt2->close();
}

$returnData = 'success';
$returnMsg = array(
	'eventDataPay' => $eventsPay
);

echo json_encode($returnMsg);
die;

?>
