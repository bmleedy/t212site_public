<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

// Get all scouts (user_type = 'Scout')
$scouts = array();

$query = "SELECT u.user_id, u.user_first, u.user_last
          FROM users AS u
          WHERE u.user_type = 'Scout'
          ORDER BY u.user_last, u.user_first";

$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$scouts[] = [
			'user_id' => $row['user_id'],
			'first' => $row['user_first'],
			'last' => $row['user_last']
		];
	}
}

$returnMsg = array(
	'status' => 'Success',
	'scouts' => $scouts
);

echo json_encode($returnMsg);
die();
?>
