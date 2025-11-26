<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

$patrol_id = isset($_POST['patrol_id']) ? $_POST['patrol_id'] : null;

if (!$patrol_id || $patrol_id === '0') {
	// No patrol selected or "None" selected
	echo json_encode(['status' => 'Success', 'members' => []]);
	die();
}

// Get patrol members - only active scouts
$members = array();

$query = "SELECT u.user_id, u.user_first, u.user_last
          FROM users AS u
          INNER JOIN scout_info AS si ON u.user_id = si.user_id
          WHERE si.patrol_id = " . intval($patrol_id) . "
          AND u.user_type = 'Scout'
          ORDER BY u.user_last, u.user_first";

$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$members[] = [
			'user_id' => $row['user_id'],
			'first' => $row['user_first'],
			'last' => $row['user_last']
		];
	}
}

$returnMsg = array(
	'status' => 'Success',
	'members' => $members,
	'patrol_id' => $patrol_id
);

echo json_encode($returnMsg);
die();
?>
