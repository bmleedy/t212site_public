<?php
/**
 * Get Activity Log API
 *
 * Returns activity log entries with optional filters.
 * Only accessible via AJAX.
 */

// Prevent any output before JSON header
error_reporting(0);
ini_set('display_errors', '0');

if( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest' ){
  // respond to Ajax request
} else {
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Not an AJAX request']);
  die();
}

header('Content-Type: application/json');
require 'connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Build query with filters
$query = "SELECT * FROM activity_log WHERE 1=1";
$params = array();
$types = '';

// Date range filters
if (!empty($input['startDate'])) {
	// Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL datetime format
	$startDate = str_replace('T', ' ', $input['startDate']) . ':00';
	$query .= " AND timestamp >= ?";
	$params[] = $startDate;
	$types .= 's';
}

if (!empty($input['endDate'])) {
	// Convert datetime-local format (YYYY-MM-DDTHH:MM) to MySQL datetime format
	$endDate = str_replace('T', ' ', $input['endDate']) . ':59';
	$query .= " AND timestamp <= ?";
	$params[] = $endDate;
	$types .= 's';
}

// Action filter
if (!empty($input['action'])) {
	$query .= " AND action LIKE ?";
	$params[] = '%' . $input['action'] . '%';
	$types .= 's';
}

// Source file filter
if (!empty($input['sourceFile'])) {
	$query .= " AND source_file LIKE ?";
	$params[] = '%' . $input['sourceFile'] . '%';
	$types .= 's';
}

// User filter
if (!empty($input['user'])) {
	$query .= " AND user = ?";
	$params[] = $input['user'];
	$types .= 'i';
}

// Success filter
if (isset($input['success']) && $input['success'] !== '') {
	$query .= " AND success = ?";
	$params[] = $input['success'];
	$types .= 'i';
}

// Freetext filter
if (!empty($input['freetext'])) {
	$query .= " AND freetext LIKE ?";
	$params[] = '%' . $input['freetext'] . '%';
	$types .= 's';
}

// Order by timestamp DESC (most recent first)
$query .= " ORDER BY timestamp DESC";

// Limit results to prevent overwhelming the browser (max 1000)
$query .= " LIMIT 1000";

// Prepare and execute query
if (!empty($params)) {
	$statement = $mysqli->prepare($query);
	if ($statement === false) {
		http_response_code(500);
		echo json_encode(['error' => 'Failed to prepare query: ' . $mysqli->error]);
		die();
	}

	// Bind parameters dynamically
	$bind_params = array_merge([$types], $params);
	$refs = array();
	foreach ($bind_params as $key => $value) {
		$refs[$key] = &$bind_params[$key];
	}
	call_user_func_array(array($statement, 'bind_param'), $refs);

	$statement->execute();
	$result = $statement->get_result();
} else {
	// No parameters, execute directly
	$result = $mysqli->query($query);
}

if (!$result) {
	http_response_code(500);
	echo json_encode(['error' => 'Query failed: ' . $mysqli->error]);
	die();
}

// Fetch all results
$logs = array();
while ($row = $result->fetch_assoc()) {
	$logs[] = [
		'timestamp' => $row['timestamp'],
		'source_file' => $row['source_file'],
		'action' => $row['action'],
		'values_json' => $row['values_json'],
		'success' => $row['success'],
		'freetext' => $row['freetext'],
		'user' => $row['user']
	];
}

// Close statement if used
if (isset($statement)) {
	$statement->close();
}

// Return JSON
echo json_encode($logs);