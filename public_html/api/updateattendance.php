<?php
// Catch all fatal errors and output JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'Error',
            'message' => 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ]);
    }
});

// Enable error reporting temporarily for debugging
ini_set('display_errors', 0); // Don't display, just log
error_reporting(E_ALL);

if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
  echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');

// Try to require connect.php with error handling
try {
    require 'connect.php';
    require_once(__DIR__ . '/../includes/activity_logger.php');
} catch (Exception $e) {
    echo json_encode([
        'status' => 'Error',
        'message' => 'Connection error: ' . $e->getMessage()
    ]);
    die();
}

// Check if mysqli object exists
if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    echo json_encode([
        'status' => 'Error',
        'message' => 'Database connection object not created'
    ]);
    die();
}

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
    // Log update
    log_activity(
      $mysqli,
      'update_attendance',
      array('user_id' => $user_id, 'date' => $date, 'was_present' => $was_present),
      true,
      "Attendance updated for user $user_id on $date",
      $user_id
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
    // Log failure
    log_activity(
      $mysqli,
      'update_attendance',
      array('user_id' => $user_id, 'date' => $date, 'error' => $mysqli->error),
      false,
      "Failed to update attendance for user $user_id on $date",
      $user_id
    );

    echo json_encode([
      'status' => 'Error',
      'message' => 'Failed to update attendance: ' . $mysqli->error
    ]);
  }
} else {
  // Record doesn't exist - INSERT it
  $insertQuery = "INSERT INTO attendance_daily (user_id, date, was_present)
                  VALUES (" . $user_id . ", '" . $mysqli->real_escape_string($date) . "', " . $was_present . ")";

  try {
    if ($mysqli->query($insertQuery)) {
      // Log insert
      log_activity(
        $mysqli,
        'create_attendance',
        array('user_id' => $user_id, 'date' => $date, 'was_present' => $was_present),
        true,
        "New attendance record created for user $user_id on $date",
        $user_id
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
      // Log failure
      log_activity(
        $mysqli,
        'create_attendance',
        array('user_id' => $user_id, 'date' => $date, 'error' => $mysqli->error),
        false,
        "Failed to create attendance record for user $user_id on $date",
        $user_id
      );

      echo json_encode([
        'status' => 'Error',
        'message' => 'Failed to record attendance: ' . $mysqli->error
      ]);
    }
  } catch (Exception $e) {
    // Log exception
    log_activity(
      $mysqli,
      'create_attendance',
      array('user_id' => $user_id, 'date' => $date, 'exception' => $e->getMessage()),
      false,
      "Exception while creating attendance for user $user_id on $date",
      $user_id
    );

    echo json_encode([
      'status' => 'Error',
      'message' => 'Database error: ' . $e->getMessage()
    ]);
  }
}

die();
?>
