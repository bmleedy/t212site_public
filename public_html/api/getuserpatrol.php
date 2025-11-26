<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

if (!$user_id) {
	echo json_encode(['status' => 'Error', 'message' => 'User ID is required']);
	die();
}

$patrol_id = null;
$user_type = null;

// First, get the user type
$query = "SELECT user_type FROM users WHERE user_id = " . $user_id;
$results = $mysqli->query($query);

if ($results && $row = $results->fetch_assoc()) {
	$user_type = $row['user_type'];
}

// If user is a Scout, get their patrol from scout_info
if ($user_type == 'Scout') {
	$query = "SELECT patrol_id FROM scout_info WHERE user_id = " . $user_id;
	$results = $mysqli->query($query);

	if ($results && $row = $results->fetch_assoc()) {
		$patrol_id = $row['patrol_id'];
	}
}

// If no patrol found (adult or scout without patrol), default to Staff patrol (id=1)
if (!$patrol_id) {
	$patrol_id = 1; // Staff patrol
}

$returnMsg = array(
	'status' => 'Success',
	'patrol_id' => $patrol_id,
	'user_type' => $user_type
);

echo json_encode($returnMsg);
die();
?>
