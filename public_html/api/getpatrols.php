<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
	// respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
	die();
}

header('Content-Type: application/json');
require 'connect.php';

// Get patrols that have active scouts
$patrols = array();

// Query patrols table, but only include patrols with active scouts
$query = "SELECT DISTINCT p.id, p.label, p.sort
          FROM patrols AS p
          INNER JOIN scout_info AS si ON p.id = si.patrol_id
          INNER JOIN users AS u ON si.user_id = u.user_id
          WHERE u.user_type = 'Scout'
          ORDER BY p.sort";
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
