<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

require_ajax();
$current_user_id = require_authentication();
// Only treasurer, webmaster, or super admin can access
require_permission(['trs', 'wm', 'sa']);
// Note: CSRF not required for this read-only endpoint

header('Content-Type: application/json');
require 'connect.php';
require_once(__DIR__ . '/../includes/activity_logger.php');

// Get filter parameters
$input = json_decode(file_get_contents('php://input'), true);

$startDate = isset($input['startDate']) ? $input['startDate'] : '';
$endDate = isset($input['endDate']) ? $input['endDate'] : '';
$scoutName = isset($input['scoutName']) ? $input['scoutName'] : '';
$eventType = isset($input['eventType']) ? $input['eventType'] : '';
$eventName = isset($input['eventName']) ? $input['eventName'] : '';
$minAmount = isset($input['minAmount']) && $input['minAmount'] !== '' ? floatval($input['minAmount']) : null;
$maxAmount = isset($input['maxAmount']) && $input['maxAmount'] !== '' ? floatval($input['maxAmount']) : null;

// Build query with filters
// Join registration, events, and users tables
$query = "SELECT
            CONCAT(u.user_first, ' ', u.user_last) as user_name,
            r.ts_paid as payment_date,
            e.startdate as outing_date,
            e.name as event_name,
            CASE
                WHEN u.user_type = 'Scout' THEN e.cost
                ELSE e.adult_cost
            END as amount
          FROM registration r
          JOIN events e ON r.event_id = e.id
          JOIN users u ON r.user_id = u.user_id
          WHERE r.paid = 1
          AND r.attending = 1
          AND u.user_active = 1";

$params = [];
$types = '';

// Filter by date range
if ($startDate !== '') {
  $query .= " AND DATE(r.ts_paid) >= ?";
  $params[] = $startDate;
  $types .= 's';
}

if ($endDate !== '') {
  $query .= " AND DATE(r.ts_paid) <= ?";
  $params[] = $endDate;
  $types .= 's';
}

// Filter by scout name (search in first or last name)
if ($scoutName !== '') {
  $query .= " AND (u.user_first LIKE ? OR u.user_last LIKE ?)";
  $searchTerm = '%' . $scoutName . '%';
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= 'ss';
}

// Filter by event type
if ($eventType !== '') {
  $query .= " AND e.type_id = ?";
  $params[] = intval($eventType);
  $types .= 'i';
}

// Filter by event name
if ($eventName !== '') {
  $query .= " AND e.name LIKE ?";
  $params[] = '%' . $eventName . '%';
  $types .= 's';
}

// Filter by amount range
if ($minAmount !== null) {
  $query .= " AND (CASE WHEN u.user_type = 'Scout' THEN e.cost ELSE e.adult_cost END) >= ?";
  $params[] = $minAmount;
  $types .= 'd';
}

if ($maxAmount !== null) {
  $query .= " AND (CASE WHEN u.user_type = 'Scout' THEN e.cost ELSE e.adult_cost END) <= ?";
  $params[] = $maxAmount;
  $types .= 'd';
}

// Order by payment date (most recent first), then by user name
$query .= " ORDER BY r.ts_paid DESC, u.user_last, u.user_first";

// Prepare and execute
$statement = $mysqli->prepare($query);

if (!empty($params)) {
  $statement->bind_param($types, ...$params);
}

$statement->execute();
$result = $statement->get_result();

$payments = [];
while ($row = $result->fetch_assoc()) {
  $payments[] = [
    'user_name' => escape_html($row['user_name']),
    'payment_date' => $row['payment_date'],
    'outing_date' => $row['outing_date'],
    'event_name' => escape_html($row['event_name']),
    'amount' => $row['amount']
  ];
}

$statement->close();

// Log treasurer report access (audit trail for financial data)
$filter_info = array(
  'startDate' => $startDate,
  'endDate' => $endDate,
  'scoutName' => $scoutName,
  'eventType' => $eventType,
  'eventName' => $eventName,
  'minAmount' => $minAmount,
  'maxAmount' => $maxAmount,
  'results_count' => count($payments)
);
log_activity(
  $mysqli,
  'view_treasurer_report',
  $filter_info,
  true,
  "Treasurer report accessed with " . count($payments) . " results",
  $current_user_id
);

$mysqli->close();

echo json_encode($payments);
die();
?>
