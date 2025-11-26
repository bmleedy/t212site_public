<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
$was_present = isset($_POST['was_present']) ? $_POST['was_present'] : null;
$date = isset($_POST['date']) ? $_POST['date'] : null;

// Validate required parameters
if (!$user_id || $was_present === null) {
	echo json_encode([
		'status' => 'Error',
		'message' => 'user_id and was_present are required'
	]);
	die();
}

// Convert was_present to boolean
$was_present = ($was_present === 'true' || $was_present === '1' || $was_present === 1) ? 1 : 0;

// If no date provided, use current date in Pacific Time Zone
if (!$date) {
	$timezone = new DateTimeZone('America/Los_Angeles');
	$datetime = new DateTime('now', $timezone);
	$date = $datetime->format('Y-m-d');
}

// Check if attendance record already exists for this user and date
$query = "SELECT id FROM attendance_daily
          WHERE user_id = " . $user_id . "
          AND date = '" . $mysqli->real_escape_string($date) . "'";
$results = $mysqli->query($query);

if ($results && $results->num_rows > 0) {
	// Record exists - UPDATE it
	$row = $results->fetch_assoc();
	$record_id = $row['id'];

	$updateQuery = "UPDATE attendance_daily
	                SET was_present = " . $was_present . "
	                WHERE id = " . $record_id;

	if ($mysqli->query($updateQuery)) {
		echo json_encode([
			'status' => 'Success',
			'message' => 'Attendance updated',
			'action' => 'updated',
			'user_id' => $user_id,
			'date' => $date,
			'was_present' => (bool)$was_present
		]);
	} else {
		echo json_encode([
			'status' => 'Error',
			'message' => 'Failed to update attendance: ' . $mysqli->error
		]);
	}
} else {
	// Record doesn't exist - INSERT it
	$insertQuery = "INSERT INTO attendance_daily (user_id, date, was_present)
	                VALUES (" . $user_id . ", '" . $mysqli->real_escape_string($date) . "', " . $was_present . ")";

	if ($mysqli->query($insertQuery)) {
		echo json_encode([
			'status' => 'Success',
			'message' => 'Attendance recorded',
			'action' => 'created',
			'user_id' => $user_id,
			'date' => $date,
			'was_present' => (bool)$was_present
		]);
	} else {
		echo json_encode([
			'status' => 'Error',
			'message' => 'Failed to record attendance: ' . $mysqli->error
		]);
	}
}

die();
?>
