<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$user_id = validate_int_post('user_id');
$event_id = validate_int_post('event_id');
$paid = validate_string_post('paid');

// Check if current user can update payment for this user (own data or admin)
require_user_access($user_id, $current_user_id);

$query = "UPDATE registration SET paid=? WHERE event_id=? AND user_id=?";
$statement = $mysqli->prepare($query);
$statement->bind_param('sii', $paid, $event_id, $user_id);

if ($statement->execute()) {
	// Log payment status update
	log_activity(
		$mysqli,
		'update_payment_status',
		array('event_id' => $event_id, 'user_id' => $user_id, 'paid' => $paid),
		true,
		"Payment status updated to $paid for user $user_id, event $event_id",
		$current_user_id
	);

	$returnMsg = array(
		'status' => 'Success',
		'signed_up' => 'Yes',
		'message' => 'Registration for this event has been paid.'
	);
	echo json_encode($returnMsg);
} else {
	// Log failure
	log_activity(
		$mysqli,
		'update_payment_status',
		array('event_id' => $event_id, 'user_id' => $user_id, 'error' => $mysqli->error),
		false,
		"Failed to update payment status for user $user_id, event $event_id",
		$current_user_id
	);

	echo json_encode([
		'error' => 'Error : (' . escape_html($mysqli->errno) . ') ' . escape_html($mysqli->error)
	]);
}
$statement->close();
die();
?>
