<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');
$user_id = $_POST['user_id'];
$event_id = $_POST['event_id'];
$paid = $_POST['paid'];

	$query = "UPDATE registration SET paid=? WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $paid, $event_id, $user_id);
	if ($statement->execute()) {
		// Log payment status update
		log_activity(
			$mysqli,
			'update_payment_status',
			array('event_id' => $event_id, 'user_id' => $user_id, 'paid' => $paid),
			true,
			"Payment status updated to $paid for user $user_id, event $event_id",
			$user_id
		);

		$returnMsg = array(
			'status' => 'Success',
			'signed_up' => 'Yes',
			'message' => 'Registration for this event has been paid.'
		);
		echo json_encode($returnMsg);
	}else{
		// Log failure
		log_activity(
			$mysqli,
			'update_payment_status',
			array('event_id' => $event_id, 'user_id' => $user_id, 'error' => $mysqli->error),
			false,
			"Failed to update payment status for user $user_id, event $event_id",
			$user_id
		);

		echo ( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
	}
	$statement->close();
	die;

?> 