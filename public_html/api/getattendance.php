<?php
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

// Validate inputs
$userid = validate_int_post('userid', true);
$sort = validate_string_post('sort', true, 'startdate', 50);
$order = validate_string_post('order', true, 'DESC', 10);
$typeid = validate_int_post('typeid', true);

// Authorization check - user can only view their own data unless they have permission
if ($userid != $current_user_id) {
  require_user_access($userid, $current_user_id);
}

// Whitelist allowed sort columns
$allowed_sorts = ['name', 'location', 'startdate'];
if (!in_array($sort, $allowed_sorts)) {
  $sort = 'startdate';
}

// Whitelist allowed order directions
$allowed_orders = ['ASC', 'DESC'];
if (!in_array(strtoupper($order), $allowed_orders)) {
  $order = 'DESC';
}

$query = "SELECT ev.name, ev.location, ev.startdate, ev.id, et.label FROM registration AS reg INNER JOIN events AS ev ON reg.event_id=ev.id INNER JOIN event_types AS et ON ev.type_id = et.id WHERE reg.user_id=? AND ev.type_id=? ORDER BY ev.$sort $order";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $userid, $typeid);
$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_object()) {
  $events[] = [
    'name' => escape_html($row->name),
    'location' => escape_html($row->location),
    'startdate'=> escape_html($row->startdate),
    'event_id'=> $row->id
  ];
}
$stmt->close();

echo json_encode($events);
die();


function getLabel($strTable,$id,$mysqli){
  if ($id) {
    // Whitelist allowed tables
    $allowed_tables = ['event_types'];
    if (!in_array($strTable, $allowed_tables)) {
      return '';
    }

    $stmt = $mysqli->prepare("SELECT label FROM $strTable WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['label'];
  } else {
    return "";
  }
}