<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['pl', 'oe', 'sa', 'wm']);
require_csrf();

header('Content-Type: application/json');
require 'connect.php';

$start_date = validate_date_post('start_date');
$end_date = validate_date_post('end_date');

// Validate date range
if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
    echo json_encode(['status' => 'Error', 'message' => 'Start date must be before end date']);
    die();
}

// Array to hold all event dates
$eventDates = array();

// Get all events from the events table in the date range
$query = "SELECT id, name, startdate
          FROM events
          WHERE DATE(startdate) >= ?
          AND DATE(startdate) <= ?
          ORDER BY startdate";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$results = $stmt->get_result();

if ($results) {
  while ($row = $results->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($row['startdate']));
    $eventDates[$date] = [
      'date' => $date,
      'event_id' => $row['id'],
      'event_name' => escape_html($row['name'])
    ];
  }
}
$stmt->close();

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