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

// Check if user has webmaster or super admin access
require_permission(['wm', 'sa']);

header('Content-Type: application/json');
require 'connect.php';

// Validate required parameters
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
		'create_patrol',
		array('label' => $label, 'sort' => $sort),
		false,
		"Failed to create patrol: invalid patrol name format",
		$current_user_id
	);
	die();
}

// Check for duplicate patrol names
$checkNameStmt = $mysqli->prepare("SELECT id FROM patrols WHERE label = ?");
$checkNameStmt->bind_param('s', $label);
$checkNameStmt->execute();
$checkNameStmt->store_result();

if ($checkNameStmt->num_rows > 0) {
	echo json_encode([
		'status' => 'Error',
		'message' => 'A patrol with the name "' . $label . '" already exists.'
	]);
	$checkNameStmt->close();
	log_activity(
		$mysqli,
		'create_patrol',
		array('label' => $label, 'sort' => $sort),
		false,
		"Failed to create patrol: duplicate patrol name",
		$current_user_id
	);
	die();
}
$checkNameStmt->close();

// Check for duplicate sort values
$checkSortStmt = $mysqli->prepare("SELECT id FROM patrols WHERE sort = ?");
$checkSortStmt->bind_param('i', $sort);
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
		'create_patrol',
		array('label' => $label, 'sort' => $sort),
		false,
		"Failed to create patrol: duplicate sort order",
		$current_user_id
	);
	die();
}
$checkSortStmt->close();

// Insert the new patrol using prepared statement
$insertStmt = $mysqli->prepare("INSERT INTO patrols (label, sort) VALUES (?, ?)");
$insertStmt->bind_param('si', $label, $sort);

if ($insertStmt->execute()) {
	$new_id = $mysqli->insert_id;
	echo json_encode([
		'status' => 'Success',
		'message' => 'Patrol created successfully.',
		'patrol' => [
			'id' => $new_id,
			'label' => $label,
			'sort' => $sort
		]
	]);
	log_activity(
		$mysqli,
		'create_patrol',
		array('id' => $new_id, 'label' => $label, 'sort' => $sort),
		true,
		"Patrol created: $label (ID: $new_id, Sort: $sort)",
		$current_user_id
	);
} else {
	echo json_encode([
		'status' => 'Error',
		'message' => 'Database error: ' . $insertStmt->error
	]);
	log_activity(
		$mysqli,
		'create_patrol',
		array('label' => $label, 'sort' => $sort, 'error' => $insertStmt->error),
		false,
		"Failed to create patrol: " . $insertStmt->error,
		$current_user_id
	);
}

$insertStmt->close();
