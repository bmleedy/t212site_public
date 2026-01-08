<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_ajax();

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');
require 'connect.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$stmt = $mysqli->prepare("SELECT * FROM events WHERE DATE_ADD(enddate, INTERVAL 1 DAY) > DATE(NOW()) ORDER BY startdate");
$stmt->execute();
$result = $stmt->get_result();
$events = null;

while ($row = $result->fetch_object()) {
  $registered = "No";

  if ($user_id > 0) {
    $event_id = $row->id;
    $stmt2 = $mysqli->prepare("SELECT attending FROM registration WHERE user_id=? AND event_id=?");
    $stmt2->bind_param("ii", $user_id, $event_id);
    $stmt2->execute();
    $reg_results = $stmt2->get_result();
    $reg_row = $reg_results->fetch_assoc();

    if ($reg_row) {
      if ($reg_row['attending'] == 1) {
        $registered = "Yes";
      } else {
        $registered = "Cancelled";
      }
    }
    $stmt2->close();
  }

  $events[] = [
	'name' => escape_html($row->name),
	'description' => escape_html($row->description),
	'location'=> escape_html($row->location),
	'startdate'=> escape_html($row->startdate),
	'enddate'=> escape_html($row->enddate),
	'cost'=> escape_html($row->cost),
	'reg_open'=> $row->reg_open,
	'id'=>$row->id,
	'registered'=>$registered
  ];
}
$stmt->close();

echo json_encode($events);

?>
