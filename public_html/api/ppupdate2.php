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
$denied = array(); // Track denied registrations for user feedback

// Get current user's family_id for family relationship check
$current_user_family_id = null;
$family_query = "SELECT family_id FROM users WHERE user_id=?";
$family_stmt = $mysqli->prepare($family_query);
$family_stmt->bind_param('i', $current_user_id);
$family_stmt->execute();
$family_result = $family_stmt->get_result();
$family_row = $family_result->fetch_assoc();
if ($family_row) {
    $current_user_family_id = $family_row['family_id'];
}
$family_stmt->close();

// Prepare update statement
$query = "UPDATE registration SET paid=1, ts_paid=? WHERE id=?";
$statement = $mysqli->prepare($query);

foreach ($validated_ids as $id) {
    // First, check if user has permission to update this registration
    // Also get event info for user feedback in case of denial
    $check_query = "SELECT r.user_id, r.event_id, u.family_id, u.user_first, u.user_last, e.name AS event_name
                    FROM registration r
                    LEFT JOIN users u ON r.user_id = u.user_id
                    LEFT JOIN events e ON r.event_id = e.id
                    WHERE r.id=?";
    $check_stmt = $mysqli->prepare($check_query);
    $check_stmt->bind_param('i', $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();
    $check_stmt->close();

    if (!$check_row) {
        log_activity(
            $mysqli,
            'payment_update_failed',
            array('reg_id' => $id, 'reason' => 'registration_not_found'),
            false,
            "Payment update failed: registration ID $id not found",
            $current_user_id
        );
        $denied[] = array(
            'reg_id' => $id,
            'reason' => 'Registration not found'
        );
        continue; // Registration not found, skip
    }

    // Check authorization: admin, owns registration, or is in same family
    $is_owner = ($check_row['user_id'] == $current_user_id);
    $is_family_member = ($current_user_family_id && $check_row['family_id'] &&
                         $current_user_family_id == $check_row['family_id']);

    if (!$is_admin && !$is_owner && !$is_family_member) {
        // Get user name from check_row (already retrieved in query)
        $denied_user_name = 'Unknown';
        if ($check_row['user_first'] || $check_row['user_last']) {
            $denied_user_name = trim($check_row['user_first'] . ' ' . $check_row['user_last']);
        }
        $denied_event_name = $check_row['event_name'] ? $check_row['event_name'] : 'Unknown Event';

        log_activity(
            $mysqli,
            'payment_update_denied',
            array('reg_id' => $id, 'reg_owner' => $check_row['user_id'], 'requester' => $current_user_id),
            false,
            "Payment update denied: user $current_user_id attempted to update registration $id owned by user " . $check_row['user_id'],
            $current_user_id
        );
        $denied[] = array(
            'reg_id' => $id,
            'username' => $denied_user_name,
            'eventname' => $denied_event_name,
            'reason' => 'You are not authorized to update payment for this person. Please contact a troop administrator.'
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
$successful_count = $events ? count($events) : 0;
$denied_count = count($denied);
log_activity(
    $mysqli,
    'batch_payment_update',
    array('reg_ids' => $validated_ids, 'requested_count' => count($validated_ids), 'successful_count' => $successful_count, 'denied_count' => $denied_count),
    true,
    "Batch payment update completed: $successful_count of " . count($validated_ids) . " registrations processed, $denied_count denied",
    $current_user_id
);

// Determine overall status based on results
$status = 'Success';
if ($denied_count > 0 && $successful_count === 0) {
    $status = 'Error';
} else if ($denied_count > 0) {
    $status = 'Partial';
}

$returnMsg = array(
    'status' => $status,
    'eventData' => $events,
    'denied' => $denied,
    'summary' => array(
        'requested' => count($validated_ids),
        'successful' => $successful_count,
        'denied' => $denied_count
    )
);
echo json_encode($returnMsg);
die;
?>
