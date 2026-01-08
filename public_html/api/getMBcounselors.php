<?php
// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Not an AJAX request']);
  die();
}

header('Content-Type: application/json');
require 'connect.php';

// Check for database connection errors
if (!isset($mysqli) || $mysqli->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    die();
}

$query="SELECT * FROM mb_counselors AS mbc JOIN mb_list AS mbl WHERE mbc.mb_id = mbl.id ORDER BY mb_name";
$results = $mysqli->query($query);

if (!$results) {
    echo json_encode(['error' => 'Query failed: ' . $mysqli->error]);
    die();
}

$counselors = [];

while ($row = $results->fetch_object()) {
	$id = $row->user_id;
	$query2="SELECT * FROM users WHERE user_id=".$id;
	$results2 = $mysqli->query($query2);
	$row2 = $results2->fetch_object();
  $counselors[] = [
    'mb_name' => $row->mb_name,
		'mb_id' => $row->mb_id,
    'id'=> $id,
		'first'=>$row2->user_first,
		'last'=>$row2->user_last,
		'email'=>$row2->user_email
  ];
}

echo json_encode($counselors);