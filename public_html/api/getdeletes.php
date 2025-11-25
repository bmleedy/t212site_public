<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$query="SELECT * FROM users WHERE user_type = 'Delete' ORDER BY user_last asc, user_first asc";
$results = $mysqli->query($query);
$adults = null;

while ($row = $results->fetch_object()) {
	$id =  $row->user_id;
	$phones = null;

  $query2="SELECT * FROM phone WHERE user_id=" . $id;
	$results2 = $mysqli->query($query2);
	if ($results2) {
		while ($row2 = $results2->fetch_object()){
			$phones[] = "<a href='tel:" . $row2->phone . "'>" . $row2->phone . "</a> " . $row2->type ;
		}
	}
		
	$adults[] = [
    'first' => $row->user_first,
    'last' => $row->user_last,
    'email'=> $row->user_email,
		'id'=>$id,
		'phone'=>$phones
  ];
}

echo json_encode($adults);

?> 