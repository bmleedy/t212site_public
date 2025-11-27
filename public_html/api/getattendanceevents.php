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

// Array to hold all event dates
$eventDates = array();

// Get all events from the events table in the date range
$query = "SELECT id, name, startdate
          FROM events
          WHERE DATE(startdate) >= '" . $mysqli->real_escape_string($start_date) . "'
          AND DATE(startdate) <= '" . $mysqli->real_escape_string($end_date) . "'
          ORDER BY startdate";

$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$date = date('Y-m-d', strtotime($row['startdate']));
		$eventDates[$date] = [
			'date' => $date,
			'event_id' => $row['id'],
			'event_name' => $row['name']
		];
	}
}

// Now add all Tuesdays in the date range as "Troop Meeting" events
$current = strtotime($start_date);
$end = strtotime($end_date);

while ($current <= $end) {
	// Check if this day is a Tuesday (2 = Tuesday in PHP's date('N'))
	if (date('N', $current) == 2) {
		$dateStr = date('Y-m-d', $current);

		// Only add if there's not already an event on this date
		if (!isset($eventDates[$dateStr])) {
			$eventDates[$dateStr] = [
				'date' => $dateStr,
				'event_id' => null,
				'event_name' => 'Troop Meeting'
			];
		}
	}

	// Move to next day
	$current = strtotime('+1 day', $current);
}

// Sort by date
ksort($eventDates);

// Convert to indexed array
$eventDatesArray = array_values($eventDates);

$returnMsg = array(
	'status' => 'Success',
	'dates' => $eventDatesArray
);

echo json_encode($returnMsg);
die();
?>
