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
  // error handling - do not expose database error details to client
  echo json_encode([
    'status' => 'Error',
    'message' => 'Failed to load attendance data'
  ]);
}
$stmt->close();

die();