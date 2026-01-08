<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$event_id = validate_int_post('event_id');

// Get event details
$query = "SELECT * FROM events WHERE id=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$results = $stmt->get_result();

if ($results && $row = $results->fetch_assoc()) {
	$name = $row['name'];
	$location = $row['location'];
	$description = $row['description'];
	$startdate = $row['startdate'];
	$enddate = $row['enddate'];
	$cost = $row['cost'];
} else {
	echo json_encode(['error' => 'Event not found']);
	die();
}
$stmt->close();

$varname = '<p>' . escape_html($name) . '</p>';
$varlocation = '<p>' . escape_html($location) . '</p>';
$vardescription = '<p>' . escape_html($description) . '</p>';
$varstartdate = '<p>' . escape_html($startdate) . '</p>';
$varenddate = '<p>' . escape_html($enddate) . '</p>';
$varcost = '<p>$' . escape_html($cost) . '</p>';
$returnData = '<div class="row"><div class="large-5 columns"><label>Event Name' . $varname . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>Location' . $varlocation . '</label></div>';
$returnData = $returnData . '<div class="large-2 columns"><label>Cost' . $varcost . '</label></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-5 columns"><label>Start Date' . $varstartdate . '</label></div>';
$returnData = $returnData . '<div class="large-5 columns"><label>End Date' . $varenddate . '</label></div><div class="large-2 columns"></div></div>';
$returnData = $returnData . '<div class="row"><div class="large-12 columns"><label>Event Description' . $vardescription . '</label></div></div>';

$scouts = null;
$adults = null;

// Get scouts with prepared statement
$query = "SELECT u.user_id, user_first, user_last, user_type, patrol_id
          FROM users AS u, scout_info AS si
          WHERE u.user_type='Scout' AND u.user_id = si.user_id
          ORDER BY patrol_id, user_last, user_first";
$results = $mysqli->query($query);

while ($row = $results->fetch_object()) {
	$id = $row->user_id;

	$query2 = "SELECT approved_by, attending, paid, seat_belts
	           FROM registration
	           WHERE user_id=? AND event_id=?";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param('ii', $id, $event_id);
	$stmt2->execute();
	$results2 = $stmt2->get_result();
	$row2 = $results2->fetch_object();
	$stmt2->close();

	$paid = isset($row2->paid) ? $row2->paid : '0';
	$approved = isset($row2->approved_by) ? $row2->approved_by : '0';
	$attending = isset($row2->attending) ? $row2->attending : '0';
	$seat_belts = isset($row2->seat_belts) ? $row2->seat_belts : '';

	$scouts[] = [
		'patrol' => escape_html(getLabel('patrols', $row->patrol_id, $mysqli)),
		'first' => escape_html($row->user_first),
		'last' => escape_html($row->user_last),
		'user_type' => escape_html($row->user_type),
		'attending' => $attending,
		'paid' => $paid,
		'approved' => $approved,
		'seat_belts' => escape_html($seat_belts),
		'id' => $id
	];
}

// Get adults
$query = "SELECT user_id, user_first, user_last, user_type
          FROM users
          WHERE user_type not in ('Scout','Alumni','Alum-D','Alum-M','Alum-O','Delete')
          ORDER BY user_last, user_first";
$results = $mysqli->query($query);

while ($row = $results->fetch_object()) {
	$id = $row->user_id;

	$query2 = "SELECT attending, seat_belts
	           FROM registration
	           WHERE user_id=? AND event_id=?";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param('ii', $id, $event_id);
	$stmt2->execute();
	$results2 = $stmt2->get_result();
	$row2 = $results2->fetch_object();
	$stmt2->close();

	$attending = isset($row2->attending) ? $row2->attending : '0';
	$seat_belts = isset($row2->seat_belts) ? $row2->seat_belts : 'N/A';

	$adults[] = [
		'patrol' => "Adults",
		'first' => escape_html($row->user_first),
		'last' => escape_html($row->user_last),
		'user_type' => escape_html($row->user_type),
		'attending' => $attending,
		'paid' => '0',
		'approved' => '0',
		'seat_belts' => escape_html($seat_belts),
		'id' => $id
	];
}

$returnMsg = array(
	'outing_name' => escape_html($name),
	'cost' => escape_html($cost),
	'data' => $returnData,
	'scouts' => $scouts,
	'adults' => $adults
);

echo json_encode($returnMsg);
die();

function getLabel($strTable, $id, $mysqli) {
	if ($id) {
		// Whitelist allowed tables to prevent SQL injection
		$allowed_tables = ['patrols', 'ranks', 'leadership'];
		if (!in_array($strTable, $allowed_tables)) {
			return "";
		}

		$query = "SELECT label FROM " . $strTable . " WHERE id=?";
		$stmt = $mysqli->prepare($query);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();
		$stmt->close();
		return $row ? $row['label'] : "";
	} else {
		return "";
	}
}
?>
