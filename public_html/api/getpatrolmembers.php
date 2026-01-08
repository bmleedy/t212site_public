<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$patrol_id = validate_int_post('patrol_id', false, 0);
$date = validate_date_post('date', false);

if (!$patrol_id || $patrol_id === 0) {
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

// Use prepared statement for patrol members query
$query = "SELECT u.user_id, u.user_first, u.user_last
          FROM users AS u
          INNER JOIN scout_info AS si ON u.user_id = si.user_id
          WHERE si.patrol_id = ?
          AND u.user_type = 'Scout'
          ORDER BY u.user_last, u.user_first";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $patrol_id);
$stmt->execute();
$results = $stmt->get_result();

if ($results) {
    while ($row = $results->fetch_assoc()) {
        $user_id = $row['user_id'];
        $was_present = false;

        // Check if attendance exists for the date using prepared statement
        $attendanceQuery = "SELECT was_present FROM attendance_daily
                            WHERE user_id = ?
                            AND date = ?";
        $attendanceStmt = $mysqli->prepare($attendanceQuery);
        $attendanceStmt->bind_param('is', $user_id, $date);
        $attendanceStmt->execute();
        $attendanceResults = $attendanceStmt->get_result();

        if ($attendanceResults && $attendanceResults->num_rows > 0) {
            $attendanceRow = $attendanceResults->fetch_assoc();
            $was_present = (bool)$attendanceRow['was_present'];
        }
        $attendanceStmt->close();

        $members[] = [
            'user_id' => $row['user_id'],
            'first' => $row['user_first'],
            'last' => $row['user_last'],
            'was_present' => $was_present
        ];
    }
}
$stmt->close();

$returnMsg = array(
    'status' => 'Success',
    'members' => $members,
    'patrol_id' => $patrol_id
);

echo json_encode($returnMsg);
die();
?>
