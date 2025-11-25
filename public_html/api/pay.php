<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}
header('Content-Type: application/json');
require 'connect.php';
$user_id = $_POST['user_id'];
$event_id = $_POST['event_id'];
$paid = $_POST['paid'];

	$query = "UPDATE registration SET paid=? WHERE event_id=? AND user_id=?";
	$statement = $mysqli->prepare($query);
	$statement->bind_param('sss', $paid, $event_id, $user_id);
	if ($statement->execute()) {
		$returnMsg = array(
			'status' => 'Success',
			'signed_up' => 'Yes',
			'message' => 'Registration for this event has been paid.'
		);
		echo json_encode($returnMsg);
	}else{
		echo ( 'Error : ('. $mysqli->errno .') '. $mysqli->error);
	}
	$statement->close();
	die;

?> 