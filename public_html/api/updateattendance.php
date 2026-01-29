<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
require_permission(['pl', 'oe', 'sa']);
require_csrf();

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$user_id = validate_int_post('user_id');
$was_present = isset($_POST['was_present']) && ($_POST['was_present'] === 'true' || $_POST['was_present'] === '1' || $_POST['was_present'] === 1) ? 1 : 0;
$date = validate_date_post('date', true); // true = optional

// If no date provided, use current date in Pacific Time Zone
if (!$date) {
  $timezone = new DateTimeZone('America/Los_Angeles');
  $datetime = new DateTime('now', $timezone);
  $date = $datetime->format('Y-m-d');
}

// Check if attendance record already exists for this user and date
$stmt = $mysqli->prepare("SELECT id FROM attendance_daily WHERE user_id = ? AND date = ?");
$stmt->bind_param('is', $user_id, $date);
$stmt->execute();
$results = $stmt->get_result();

if ($results && $results->num_rows > 0) {
  // Record exists - UPDATE it
  $row = $results->fetch_assoc();
  $record_id = $row['id'];
  $stmt->close();

  $stmt = $mysqli->prepare("UPDATE attendance_daily SET was_present = ? WHERE id = ?");
  $stmt->bind_param('ii', $was_present, $record_id);

  if ($stmt->execute()) {
    $stmt->close();

    // Log update
    log_activity(
      $mysqli,
      'update_attendance',
      array('user_id' => $user_id, 'date' => $date, 'was_present' => $was_present),
      true,
      "Attendance updated for user $user_id on $date",
      $current_user_id
    );

    echo json_encode([
      'status' => 'Success',
      'message' => 'Attendance updated',
      'action' => 'updated',
      'user_id' => $user_id,
      'date' => $date,
      'was_present' => (bool)$was_present
    ]);
  } else {
    $error = $stmt->error;
    $stmt->close();

    // Log failure
    log_activity(
      $mysqli,
      'update_attendance',
      array('user_id' => $user_id, 'date' => $date, 'error' => $error),
      false,
      "Failed to update attendance for user $user_id on $date",
      $current_user_id
    );

    http_response_code(500);
    echo json_encode([
      'status' => 'Error',
      'message' => 'Failed to update attendance'
    ]);
  }
} else {
  $stmt->close();

  // Record doesn't exist - INSERT it
  $stmt = $mysqli->prepare("INSERT INTO attendance_daily (user_id, date, was_present) VALUES (?, ?, ?)");
  $stmt->bind_param('isi', $user_id, $date, $was_present);

  if ($stmt->execute()) {
    $stmt->close();

    // Log insert
    log_activity(
      $mysqli,
      'create_attendance',
      array('user_id' => $user_id, 'date' => $date, 'was_present' => $was_present),
      true,
      "New attendance record created for user $user_id on $date",
      $current_user_id
    );

    echo json_encode([
      'status' => 'Success',
      'message' => 'Attendance recorded',
      'action' => 'created',
      'user_id' => $user_id,
      'date' => $date,
      'was_present' => (bool)$was_present
    ]);
  } else {
    $error = $stmt->error;
    $stmt->close();

    // Log failure
    log_activity(
      $mysqli,
      'create_attendance',
      array('user_id' => $user_id, 'date' => $date, 'error' => $error),
      false,
      "Failed to create attendance record for user $user_id on $date",
      $current_user_id
    );

    http_response_code(500);
    echo json_encode([
      'status' => 'Error',
      'message' => 'Failed to record attendance'
    ]);
  }
}

die();
?>
