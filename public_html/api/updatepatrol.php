<?php
// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

session_start();
require 'auth_helper.php';
require 'validation_helper.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

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

// Validate required parameters
$id = validate_int_post('id', true);
$label = validate_string_post('label', true);
$sort = validate_int_post('sort', true);

// Additional validation for patrol name: only letters, no spaces
if (!preg_match('/^[a-zA-Z]+$/', $label)) {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Patrol name must be a single word containing only letters (no spaces or special characters).'
	]);
	log_activity(
		$mysqli,
		'update_patrol',
		array('id' => $id, 'label' => $label, 'sort' => $sort),
		false,
		"Failed to update patrol: invalid patrol name format",
		$current_user_id
	);
	die();
}

// Check for duplicate sort values (excluding the current patrol)
$checkSortStmt = $mysqli->prepare("SELECT id FROM patrols WHERE sort = ? AND id != ?");
$checkSortStmt->bind_param('ii', $sort, $id);
$checkSortStmt->execute();
$checkSortStmt->store_result();

if ($checkSortStmt->num_rows > 0) {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Sort order ' . $sort . ' is already used by another patrol. Sort values must be unique.'
	]);
	$checkSortStmt->close();
	log_activity(
		$mysqli,
		'update_patrol',
		array('id' => $id, 'label' => $label, 'sort' => $sort),
		false,
		"Failed to update patrol: duplicate sort order",
		$current_user_id
	);
	die();
}
$checkSortStmt->close();

// Update the patrol using prepared statement
$updateStmt = $mysqli->prepare("UPDATE patrols SET label = ?, sort = ? WHERE id = ?");
$updateStmt->bind_param('sii', $label, $sort, $id);

if ($updateStmt->execute()) {
	if ($updateStmt->affected_rows > 0) {
		echo json_encode([
			'status' => 'Success',
			'message' => 'Patrol updated successfully.'
		]);
		log_activity(
			$mysqli,
			'update_patrol',
			array('id' => $id, 'label' => $label, 'sort' => $sort),
			true,
			"Patrol updated: $label (ID: $id, Sort: $sort)",
			$current_user_id
		);
	} else {
		// No rows affected - either the patrol doesn't exist or no changes were made
		echo json_encode([
			'status' => 'Success',
			'message' => 'No changes were needed (values unchanged).'
		]);
		log_activity(
			$mysqli,
			'update_patrol',
			array('id' => $id, 'label' => $label, 'sort' => $sort),
			true,
			"Patrol update attempted but no changes needed (ID: $id)",
			$current_user_id
		);
	}
} else {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Database error: ' . $updateStmt->error
	]);
	log_activity(
		$mysqli,
		'update_patrol',
		array('id' => $id, 'label' => $label, 'sort' => $sort, 'error' => $updateStmt->error),
		false,
		"Failed to update patrol (ID: $id): " . $updateStmt->error,
		$current_user_id
	);
}

$updateStmt->close();
