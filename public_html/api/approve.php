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
$reg_id = $_POST['reg_id'];
$spec = $_POST['spec_instructions'];
$medical = $_POST['medical'];

// if at least one of the text fields is blank, no need to insert <br> to separate
if ($spec=='' || $medical=='') {
	$spec_inst = $spec . $medical;
} else {
	$spec_inst = $spec . "<br>" . $medical;
}
$ts_now = date('Y-m-d H:i:s');

$query = "UPDATE registration SET approved_by=?, ts_approved=?, spec_instructions=? WHERE id=?";
$statement = $mysqli->prepare($query);
$statement->bind_param('ssss', $user_id, $ts_now, $spec_inst, $reg_id);
$statement->execute();
$statement->close();

// Log approval
log_activity(
	$mysqli,
	'approve_registration',
	array('reg_id' => $reg_id, 'approved_by' => $user_id),
	true,
	"Registration $reg_id approved by user $user_id",
	$user_id
);

$returnMsg = array(
 'status' => 'Success',
 'message' => 'You have approved this event registration.'
);
echo json_encode($returnMsg);
die;
?>
