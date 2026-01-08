<?php
// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Check if user has webmaster or super admin access
$access = isset($_SESSION['access']) ? $_SESSION['access'] : [];
$hasAccess = (in_array("wm", $access) || in_array("sa", $access));

if (!$hasAccess) {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Access denied. Webmaster or Super Admin access required.'
	]);
	die();
}

// Get all patrols (not just those with active scouts)
$patrols = array();

// Query patrols table - no user input, no need for prepared statement
$query = "SELECT id, label, sort
          FROM patrols
          ORDER BY sort ASC";
$results = $mysqli->query($query);

if ($results) {
	while ($row = $results->fetch_assoc()) {
		$patrols[] = [
			'id' => $row['id'],
			'label' => $row['label'],
			'sort' => $row['sort']
		];
	}
} else {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Database query failed: ' . $mysqli->error
	]);
	die();
}

echo json_encode([
	'status' => 'Success',
	'patrols' => $patrols
]);
