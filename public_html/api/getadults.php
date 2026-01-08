<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

$query = "SELECT user_id, user_first, user_last, user_email, user_type, notif_preferences
          FROM users
          WHERE user_type not in ('Scout', 'Delete', 'Alumni','Alum-D','Alum-M','Alum-O')
          ORDER BY user_last asc, user_first asc";
$results = $mysqli->query($query);
$adults = null;

while ($row = $results->fetch_object()) {
	$id = $row->user_id;

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

	$query2 = "SELECT * FROM phone WHERE user_id=?";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param('i', $id);
	$stmt2->execute();
	$results2 = $stmt2->get_result();

	if ($results2) {
		while ($row2 = $results2->fetch_object()) {
			$phones[] = "<a href='tel:" . escape_html($row2->phone) . "'>" . escape_html($row2->phone) . "</a> " . escape_html($row2->type);
		}
	}
	$stmt2->close();

	$adults[] = [
		'first' => escape_html($row->user_first),
		'last' => escape_html($row->user_last),
		'email' => $include_in_roster_emails ? escape_html($row->user_email) : '',  // Only include email if opted in
		'id' => $id,
		'phone' => $phones,
		'user_type' => escape_html($row->user_type)
	];
}

echo json_encode($adults);
die();
?>
