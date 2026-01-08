<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$start_date = validate_date_post('start_date');
$end_date = validate_date_post('end_date');

// Get all attendance records in the date range
// Return as associative array keyed by "user_id-date" for easy lookup
$attendance = array();

$query = "SELECT user_id, date, was_present
          FROM attendance_daily
          WHERE date >= ?
          AND date <= ?";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$results = $stmt->get_result();

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$key = $row['user_id'] . '-' . $row['date'];
		$attendance[$key] = (bool)$row['was_present'];
	}
	$returnMsg = array(
		'status' => 'Success',
		'attendance' => $attendance
	);
	echo json_encode($returnMsg);
} else {
	// error handling:
	echo json_encode([
		'status' => 'Error',
		'message' => 'Database query failed: ' . escape_html($mysqli->error)
	]);
}
$stmt->close();

die();
?>
