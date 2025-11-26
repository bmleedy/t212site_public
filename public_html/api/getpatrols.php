<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

// Get all patrols from the patrols table
$patrols = array();

// Query patrols table ordered by sort column (common pattern in this codebase)
$query = "SELECT id, label FROM patrols ORDER BY sort";
$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$patrols[] = [
			'id' => $row['id'],
			'label' => $row['label']
		];
	}
}

// Add "None" option as specified in requirements
$patrols[] = [
	'id' => '0',
	'label' => 'None'
];

$returnMsg = array(
	'status' => 'Success',
	'patrols' => $patrols
);

echo json_encode($returnMsg);
die();
?>
