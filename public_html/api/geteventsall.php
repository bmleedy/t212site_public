<?php
if( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {  
  echo "Not sure what you are after, but it ain't here.";
  die();
}
date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');
require 'connect.php';
$query="SELECT * FROM events WHERE 1 ORDER BY startdate DESC";// ORDER BY startdate";
$results = $mysqli->query($query);
$events = null;

while ($row = $results->fetch_object()) {
  $events[] = [
  'name' => $row->name,
  'description' => $row->description,
  'location'=> $row->location,
  'startdate'=> $row->startdate,
  'enddate'=> $row->enddate,
  'cost'=> $row->cost,
  'reg_open'=> $row->reg_open,
  'id'=>$row->id
  ];
}

echo json_encode($events);

?> 