<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$query="SELECT * FROM mb_counselors AS mbc JOIN mb_list AS mbl WHERE mbc.mb_id = mbl.id ORDER BY mb_name";
$results = $mysqli->query($query);
$counselors = null;

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

?> 