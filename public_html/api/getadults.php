<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	echo "Not sure what you are after, but it ain't here.";
  die();
}

header('Content-Type: application/json');
require 'connect.php';
$query="SELECT user_id, user_first, user_last, user_email, user_type, notif_preferences FROM users WHERE user_type not in ('Scout', 'Delete', 'Alumni','Alum-D','Alum-M','Alum-O') ORDER BY user_last asc, user_first asc";
$results = $mysqli->query($query);
$adults = null;

while ($row = $results->fetch_object()) {
	$id =  $row->user_id;

	// Check if this adult wants roster emails (for the email buttons)
	$include_in_roster_emails = true;  // Default: include (opted in)

	if ($row->notif_preferences) {
		$prefs = json_decode($row->notif_preferences, true);
		// Check 'rost' (Roster) preference
		if (isset($prefs['rost']) && $prefs['rost'] === false) {
			$include_in_roster_emails = false;
		}
	}

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
    'email'=> $include_in_roster_emails ? $row->user_email : '',  // Only include email if opted in
	'id'=>$id,
	'phone'=>$phones,
	'user_type'=>$row->user_type
  ];
}

echo json_encode($adults);

?> 
