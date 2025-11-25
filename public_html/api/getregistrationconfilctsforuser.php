<?php
if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {	
	echo "Not sure what you are after, but it ain't here.";
  die();
}
date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');
require 'connect.php';

$user_id = $_GET['user_id'];
$event_id = $_GET['event_id'];

// fetch all registrations that are overlapping with the event's start and end dates
// will return zero rows if no conflicts

$query = <<<SQL
        SELECT regevents.name, regevents.startdate, regevents.enddate  
        FROM registration -- table with previous registrations 
          JOIN events AS regevents -- table with previous event's details 
            ON registration.event_id = regevents.id 
          JOIN events AS e2 ON -- table with start and end dates we're checking against 
            e2.id = $event_id 
            AND regevents.startdate <= e2.enddate 
            AND regevents.enddate >= e2.startdate 
        WHERE registration.user_id=$user_id
        ;
        SQL;

$results = $mysqli->query($query);

// If no results, return false
$response = false;
if( mysqli_num_rows($results) == 0 ) {
  $response = false; // no conflict
} else {
  $response = true; // yes, there is a conflict
}

echo json_encode($response);

?> 