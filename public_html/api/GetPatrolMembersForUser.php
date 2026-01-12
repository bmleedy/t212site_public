<?php
/**
 * Get Patrol Members For User API
 *
 * Returns all scouts in the same patrol as the specified user.
 * Used for "Patrol Members" table on user profile page.
 */

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

// Get user_id parameter
$user_id = validate_int_post('user_id');

if (!$user_id) {
    echo json_encode(['status' => 'Error', 'message' => 'User ID required']);
    exit;
}

// Get the patrol_id for the specified user
$query = "SELECT si.patrol_id, p.label as patrol_name
          FROM scout_info si
          JOIN patrols p ON si.patrol_id = p.id
          WHERE si.user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patrol_row = $result->fetch_assoc();
$stmt->close();

if (!$patrol_row || !$patrol_row['patrol_id']) {
    echo json_encode([
        'status' => 'NoPatrol',
        'message' => 'User is not in a patrol'
    ]);
    exit;
}

$patrol_id = $patrol_row['patrol_id'];
$patrol_name = $patrol_row['patrol_name'];

// Get all scouts in this patrol with their info
$query = "SELECT u.user_id, u.user_first, u.user_last,
                 r.label as rank_name, l.label as position_name
          FROM users u
          JOIN scout_info si ON u.user_id = si.user_id
          LEFT JOIN ranks r ON si.rank_id = r.id
          LEFT JOIN leadership l ON si.position_id = l.id
          WHERE si.patrol_id = ? AND u.user_active = 1
          ORDER BY u.user_last, u.user_first";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $patrol_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = [
        'user_id' => (int)$row['user_id'],
        'first_name' => $row['user_first'],
        'last_name' => $row['user_last'],
        'rank' => $row['rank_name'] ?: '',
        'position' => $row['position_name'] ?: ''
    ];
}
$stmt->close();

echo json_encode([
    'status' => 'Success',
    'patrol_id' => $patrol_id,
    'patrol_name' => $patrol_name,
    'members' => $members
]);
