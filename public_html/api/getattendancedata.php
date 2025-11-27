<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;

if (!$start_date || !$end_date) {
	echo json_encode(['status' => 'Error', 'message' => 'start_date and end_date are required']);
	die();
}

// Get all attendance records in the date range
// Return as associative array keyed by "user_id-date" for easy lookup
$attendance = array();

$query = "SELECT user_id, date, was_present
          FROM attendance_daily
          WHERE date >= '" . $mysqli->real_escape_string($start_date) . "'
          AND date <= '" . $mysqli->real_escape_string($end_date) . "'";

$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$key = $row['user_id'] . '-' . $row['date'];
		$attendance[$key] = (bool)$row['was_present'];
	}
}

$returnMsg = array(
	'status' => 'Success',
	'attendance' => $attendance
);

echo json_encode($returnMsg);
die();
?>
