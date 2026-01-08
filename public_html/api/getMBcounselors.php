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
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();
$current_user_id = require_authentication();


header('Content-Type: application/json');
require 'connect.php';

$stmt = $mysqli->prepare("SELECT * FROM mb_counselors AS mbc JOIN mb_list AS mbl WHERE mbc.mb_id = mbl.id ORDER BY mb_name");
$stmt->execute();
$result = $stmt->get_result();
$counselors = [];

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . $mysqli->error]);
    die();
}

while ($row = $result->fetch_object()) {
	$id = $row->user_id;
	$stmt2 = $mysqli->prepare("SELECT * FROM users WHERE user_id=?");
	$stmt2->bind_param("i", $id);
	$stmt2->execute();
	$result2 = $stmt2->get_result();
	$row2 = $result2->fetch_object();
	$counselors[] = [
    'mb_name' => escape_html($row->mb_name),
		'mb_id' => $row->mb_id,
    'id'=> $id,
		'first'=>escape_html($row2->user_first),
		'last'=>escape_html($row2->user_last),
		'email'=>escape_html($row2->user_email)
  ];
	$stmt2->close();
}
$stmt->close();

echo json_encode($counselors);