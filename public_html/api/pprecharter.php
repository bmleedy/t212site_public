<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
// Require admin permissions for recharter operations
require_permission(['ue', 'sa', 'wm']);

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

$rc_ids_string = validate_string_post('rc_ids');
$rc_ids = explode(",", $rc_ids_string);

// Process rows, updating scout's user table entry to indicate recharter and boyslife
$recharter = null;
$query = "UPDATE scout_info SET rechartered=1, boyslife=? WHERE user_id=?";
$statement = $mysqli->prepare($query);

foreach ($rc_ids as &$id) {
	if (strpos($id, "bl") === 0) {
		$bl = '1';
		$id = substr($id, 2);
	} else {
		$bl = '0';
	}

	$rs = $statement->bind_param('si', $bl, $id);
	$statement->execute();

	$query2 = "SELECT user_first, user_last FROM users WHERE user_id=?";
	$stmt2 = $mysqli->prepare($query2);
	$stmt2->bind_param('i', $id);
	$stmt2->execute();
	$results2 = $stmt2->get_result();
	$row2 = $results2->fetch_assoc();
	$stmt2->close();

	$first = $row2['user_first'];
	$last = $row2['user_last'];
	$recharter[] = [
		'username' => escape_html($first . ' ' . $last),
		'bl' => $bl
	];
}
$statement->close();

// Log batch recharter update
log_activity(
	$mysqli,
	'batch_recharter_update',
	array('rc_ids' => $rc_ids, 'count' => count($rc_ids)),
	true,
	"Batch recharter update completed for " . count($rc_ids) . " scouts",
	$current_user_id
);

$returnMsg = array(
	'status' => 'Success',
	'recharterData' => $recharter
);
echo json_encode($returnMsg);
die();
?>
