<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

$patrol_id = isset($_POST['patrol_id']) ? $_POST['patrol_id'] : null;
$date = isset($_POST['date']) ? $_POST['date'] : null;

if (!$patrol_id || $patrol_id === '0') {
	// No patrol selected or "None" selected
	echo json_encode(['status' => 'Success', 'members' => []]);
	die();
}

// Get patrol members - only active scouts
$members = array();

// Get date for attendance lookup - use provided date or current date in Pacific Time Zone
if (!$date) {
	$timezone = new DateTimeZone('America/Los_Angeles');
	$datetime = new DateTime('now', $timezone);
	$date = $datetime->format('Y-m-d');
}

$query = "SELECT u.user_id, u.user_first, u.user_last
          FROM users AS u
          INNER JOIN scout_info AS si ON u.user_id = si.user_id
          WHERE si.patrol_id = " . intval($patrol_id) . "
          AND u.user_type = 'Scout'
          ORDER BY u.user_last, u.user_first";

$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$user_id = $row['user_id'];
		$was_present = false;

		// Check if attendance exists for today
		$attendanceQuery = "SELECT was_present FROM attendance_daily
		                    WHERE user_id = " . intval($user_id) . "
		                    AND date = '" . $mysqli->real_escape_string($date) . "'";
		$attendanceResults = $mysqli->query($attendanceQuery);

		if ($attendanceResults && $attendanceResults->num_rows > 0) {
			$attendanceRow = $attendanceResults->fetch_assoc();
			$was_present = (bool)$attendanceRow['was_present'];
		}

		$members[] = [
			'user_id' => $row['user_id'],
			'first' => $row['user_first'],
			'last' => $row['user_last'],
			'was_present' => $was_present
		];
	}
}

$returnMsg = array(
	'status' => 'Success',
	'members' => $members,
	'patrol_id' => $patrol_id
);

echo json_encode($returnMsg);
die();
?>
