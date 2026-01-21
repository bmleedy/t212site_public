<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

// Check if user has admin/treasurer permission (can update any registration)
$is_admin = has_permission('trs') || has_permission('sa') || has_permission('wm');

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Validate input - reg_ids should be comma-separated integers
if (!isset($_POST['reg_ids']) || empty($_POST['reg_ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing registration IDs']);
    die();
}

$reg_ids = explode(",", $_POST['reg_ids']);
$validated_ids = [];

// Validate each ID is an integer
foreach ($reg_ids as $id) {
    $id = trim($id);
    if (filter_var($id, FILTER_VALIDATE_INT) !== false) {
        $validated_ids[] = (int)$id;
    }
}

if (empty($validated_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No valid registration IDs provided']);
    die();
}

$ts_now = date('Y-m-d H:i:s');
$events = null;

// Prepare update statement
$query = "UPDATE registration SET paid=1, ts_paid=? WHERE id=?";
$statement = $mysqli->prepare($query);

foreach ($validated_ids as $id) {
    // First, check if user has permission to update this registration
    $check_query = "SELECT user_id FROM registration WHERE id=?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    $check_stmt->close();

    if (!$check_row) {
        log_activity(
            $mysqli,
            'ppupdate2.php',
            'payment_update_failed',
            json_encode(['reg_id' => $id, 'reason' => 'registration_not_found']),
            0, // failure
            "Payment update failed: registration ID $id not found",
            $current_user_id
        );
        continue; // Registration not found, skip
    }

    // Allow update if user is admin OR owns this registration
    if (!$is_admin && $check_row['user_id'] != $current_user_id) {
        log_activity(
            $mysqli,
            'ppupdate2.php',
            'payment_update_denied',
            json_encode(['reg_id' => $id, 'reg_owner' => $check_row['user_id'], 'requester' => $current_user_id]),
            0, // failure
            "Payment update denied: user $current_user_id attempted to update registration $id owned by user " . $check_row['user_id'],
            $current_user_id
        );
        continue; // User doesn't have permission to update this registration
    }

    // Update payment status
    $statement->bind_param('si', $ts_now, $id);
    $statement->execute();

    // Get registration details using prepared statement
    $query2 = "SELECT * FROM registration WHERE id=?";
    $stmt2 = $mysqli->prepare($query2);
    $stmt2->bind_param('i', $id);
    $stmt2->execute();
    $results2 = $stmt2->get_result();
    $row = $results2->fetch_assoc();
    $stmt2->close();

    if (!$row) {
        continue; // Skip if registration not found
    }

    $event_id = $row['event_id'];
    $reg_id = $row['id'];
    $user_id = $row['user_id'];

    // Get user details using prepared statement
    $query3 = "SELECT user_first, user_last FROM users WHERE user_id=?";
    $stmt3 = $mysqli->prepare($query3);
    $stmt3->bind_param('i', $user_id);
    $stmt3->execute();
    $results3 = $stmt3->get_result();
    $row3 = $results3->fetch_assoc();
    $stmt3->close();

    if ($row3) {
        $first = $row3['user_first'];
        $last = $row3['user_last'];
    } else {
        $first = '';
        $last = '';
    }

    // Get event details using prepared statement
    $query4 = "SELECT name, startdate, cost FROM events WHERE id=?";
    $stmt4 = $mysqli->prepare($query4);
    $stmt4->bind_param('i', $event_id);
    $stmt4->execute();
    $results4 = $stmt4->get_result();

    while ($row4 = $results4->fetch_assoc()) {
        $events[] = [
            'eventname' => $row4['name'],
            'eventid' => $event_id,
            'regid' => $reg_id,
            'startdate' => $row4['startdate'],
            'cost' => $row4['cost'],
            'username' => $first . ' ' . $last
        ];
    }
    $stmt4->close();
}
$statement->close();

// Log batch payment update
log_activity(
    $mysqli,
    'ppupdate2.php',
    'batch_payment_update',
    json_encode(['reg_ids' => $validated_ids, 'count' => count($validated_ids)]),
    1, // success
    "Batch payment update completed for " . count($validated_ids) . " registrations",
    $current_user_id
);

$returnMsg = array(
    'status' => 'Success',
    'eventData' => $events
);
echo json_encode($returnMsg);
die;
?>
