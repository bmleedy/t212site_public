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

$patrol_id = $_POST['patrol_id'];

$returnMsg = array(
	'patrol_name' => $patrol,
	'leaderData' => $leaderData,
	'pbData' => $pbdata,
	'scouts' => $scouts
);

echo json_encode($returnMsg);
die();
?> 