<?php
session_start();
require 'auth_helper.php';
require 'validation_helper.php';

// Verify AJAX request
require_ajax();

// Verify authentication
$current_user_id = require_authentication();

header('Content-Type: application/json');
require 'connect.php';

// Get patrols that have active scouts
$patrols = array();

// Query patrols table, but only include patrols with active scouts
// No user input, so no need for prepared statement here
$query = "SELECT DISTINCT p.id, p.label, p.sort
          FROM patrols AS p
          INNER JOIN scout_info AS si ON p.id = si.patrol_id
          INNER JOIN users AS u ON si.user_id = u.user_id
          WHERE u.user_type = 'Scout'
          ORDER BY p.sort";
$results = $mysqli->query($query);

if ($results) {
    while ($row = $results->fetch_assoc()) {
        $patrols[] = [
            'id' => $row['id'],
            'label' => $row['label']
        ];
    }
}

// Add "None" option as specified in requirements
$patrols[] = [
    'id' => '0',
    'label' => 'None'
];

$returnMsg = array(
    'status' => 'Success',
    'patrols' => $patrols
);

echo json_encode($returnMsg);
die();
?>
