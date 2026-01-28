<?php
/**
 * API endpoint to retrieve all events (past and future)
 * Requires authentication - returns events ordered by start date descending
 */
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Validate request
require_ajax();
require_authentication();
require_csrf();

date_default_timezone_set('America/Los_Angeles');
header('Content-Type: application/json');

require 'connect.php';

// Use prepared statement for consistency with codebase standards
$stmt = $mysqli->prepare("SELECT id, name, description, location, startdate, enddate, cost, reg_open FROM events ORDER BY startdate DESC");
$stmt->execute();
$result = $stmt->get_result();

$events = [];

while ($row = $result->fetch_object()) {
    $events[] = [
        'id' => $row->id,
        'name' => escape_html($row->name),
        'description' => escape_html($row->description),
        'location' => escape_html($row->location),
        'startdate' => $row->startdate,
        'enddate' => $row->enddate,
        'cost' => escape_html($row->cost),
        'reg_open' => $row->reg_open
    ];
}

$stmt->close();
echo json_encode($events);
